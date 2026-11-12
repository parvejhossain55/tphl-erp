<?php
$base_url = $GLOBALS['base_url'] ?? '/TPHL-ERP';
$userRole = (int)($_SESSION['user']['role_id'] ?? 0);
$roleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
// Management / Operations (Super Admin, Admin, Sales Heads, Managers, Coordinators)
$isManagement = in_array($userRole, [1, 2, 5, 9, 15, 33]) || strpos($roleName, 'admin') !== false;
// Accounts / Accounts Head / HR
$isAccounts = in_array($userRole, [1, 2, 3, 6, 30, 33]) || strpos($roleName, 'account') !== false;
$currentUserId = (int)($_SESSION['user']['id'] ?? 0);
$isAccountsHead = ($userRole === 30) || ((int)($_SESSION['user']['secondary_role_id'] ?? 0) === 30) || strpos($roleName, 'accounts head') !== false;
// STRICT PERMISSION: Only designated Accounts Head or Super Admin (1) can trigger AH Approve (Role 3 Management is strictly excluded)
$canApproveAh = $isAccountsHead || ($userRole === 1);
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-bold mb-1 text-dark">
                <i class="fa-solid fa-hand-holding-dollar text-primary me-2"></i>Sales Incentive Accounts
            </h3>
            <p class="text-muted small mb-0">Manage claims, monitor dynamic targets, review external incentive allocations, and manage employee payouts.</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <form action="" method="GET" class="d-flex align-items-center gap-2">
                <label for="monthFilter" class="fw-bold text-muted small mb-0 text-nowrap">Month:</label>
                <input type="month" id="monthFilter" name="month" class="form-control form-control-sm" value="<?= $selected_month ?>">
                
                <label class="fw-bold text-muted small mb-0 ms-2 text-nowrap">Or Custom Range:</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="<?= $selected_from_date ?? '' ?>">
                <span class="text-muted small">to</span>
                <input type="date" name="to_date" class="form-control form-control-sm" value="<?= $selected_to_date ?? '' ?>">
                
                <button type="submit" class="btn btn-sm btn-primary ms-1"><i class="fa-solid fa-search"></i></button>
            </form>
            <a href="<?= $GLOBALS['base_url'] ?>/incentive-config" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-xs">
                <i class="fa-solid fa-sliders me-1"></i>Modality Settings & Rates (%)
            </a>
            <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fs-7 fw-semibold">
                <i class="fa-solid fa-building-columns me-1"></i> Management View
            </span>
        </div>
    </div>

    <!-- Company Dashboard Metrics Cards Row -->
    <div class="row mb-4 ">
        <!-- Total Company Incentive Pool -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-primary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Total Pool</span>
                        <div class="avatar avatar-sm bg-primary-subtle text-primary rounded-2">
                            <i class="fa-solid fa-sack-dollar"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-1 text-dark">৳<?= number_format($companyMetrics['total_incentive_pool'], 2) ?></h4>
                    <span class="text-muted extra-small">Total eligible incentives</span>
                </div>
            </div>
        </div>

        <!-- Pending Claims Queue -->
        <div class="col-xl-3 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-warning">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Pending Claims Queue</span>
                        <div class="avatar avatar-sm bg-warning-subtle text-warning rounded-2">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                        <h4 class="fw-bold mb-1 text-warning">৳<?= number_format($companyMetrics['pending_claims_amount'], 2) ?></h4>
                        <span class="badge bg-warning text-dark extra-small rounded-pill"><?= $companyMetrics['pending_claims_count'] ?> Claims</span>
                    </div>
                    <span class="text-muted extra-small">Awaiting Accounts / Admin Approval</span>
                </div>
            </div>
        </div>

        <!-- Approved Incentive -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-info">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Total Approved</span>
                        <div class="avatar avatar-sm bg-info-subtle text-info rounded-2">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-1 text-info">৳<?= number_format($companyMetrics['total_approved'], 2) ?></h4>
                    <span class="text-muted extra-small">Approved for payout</span>
                </div>
            </div>
        </div>

        <!-- Total Disbursed / Paid -->
        <div class="col-xl-3 col-md-6 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-success">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Total Disbursed</span>
                        <div class="avatar avatar-sm bg-success-subtle text-success rounded-2">
                            <i class="fa-solid fa-money-bill-wave"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-1 text-success">৳<?= number_format($companyMetrics['total_disbursed'], 2) ?></h4>
                    <span class="text-muted extra-small">Paid via Cash/Bank/Cheque</span>
                </div>
            </div>
        </div>

        <!-- Non-Sales / External Member Assignments Card (Clean White Theme matching other cards) -->
        <div class="col-xl-2 col-md-6 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-secondary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">External Members</span>
                        <div class="avatar avatar-sm bg-secondary-subtle text-secondary rounded-2">
                            <i class="fa-solid fa-users-gear"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-1 text-dark"><?= $companyMetrics['external_count'] ?> Members</h4>
                    <span class="text-muted extra-small">Engineers, HR & Others</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Card with Navigation Tabs -->
    <div class="card border-0 shadow-sm rounded-3">
        <!-- Tabs Nav -->
        <div class="card-header bg-white py-2 border-bottom">
            <ul class="nav nav-tabs card-header-tabs border-0" id="incentiveTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold py-2 px-3 border-0 rounded-top-2" id="realtime-stats-tab" data-bs-toggle="tab" data-bs-target="#realtime-stats" type="button" role="tab">
                        <i class="fa-solid fa-chart-pie me-2"></i>Real-Time Month Targets
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold py-2 px-3 border-0 rounded-top-2" id="sales-claims-tab" data-bs-toggle="tab" data-bs-target="#sales-claims" type="button" role="tab">
                        <i class="fa-solid fa-user-check me-2"></i>Sales Claims Queue
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold py-2 px-3 border-0 rounded-top-2 text-indigo" id="external-claims-tab" data-bs-toggle="tab" data-bs-target="#external-claims" type="button" role="tab">
                        <i class="fa-solid fa-user-plus me-2"></i>Non-Sales / External Member Assignments
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold py-2 px-3 border-0 rounded-top-2 text-success" id="wallets-tab" data-bs-toggle="tab" data-bs-target="#wallets" type="button" role="tab">
                        <i class="fa-solid fa-wallet me-2"></i>Employee Wallets & Payout Logs
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold py-2 px-3 border-0 rounded-top-2 text-warning" id="team-overrides-tab" data-bs-toggle="tab" data-bs-target="#team-overrides" type="button" role="tab">
                        <i class="fa-solid fa-people-arrows me-2 text-warning"></i>TL Overrides (0.05%)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold py-2 px-3 border-0 rounded-top-2 text-info" id="cross-splits-tab" data-bs-toggle="tab" data-bs-target="#cross-splits" type="button" role="tab">
                        <i class="fa-solid fa-arrows-split-up-and-left me-2 text-info"></i>Unassigned (70/30) Splits
                    </button>
                </li>
                <li class="nav-item ms-auto" role="presentation">
                    <a href="<?= $GLOBALS['base_url'] ?>/incentive-config" class="btn btn-sm btn-light border text-primary fw-semibold rounded-pill px-3 py-1 my-1">
                        <i class="fa-solid fa-sliders me-1"></i>Configure Modality & Rates (%)
                    </a>
                </li>
            </ul>
        </div>

        <div class="card-body p-0">
            <div class="tab-content" id="incentiveTabsContent">
                
                <!-- TAB 0: Real-Time Month Targets -->
                <div class="tab-pane fade show active" id="realtime-stats" role="tabpanel">
                    <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                        <span class="text-muted extra-small fw-semibold">
                            <i class="fa-solid fa-info-circle me-1"></i>Real-time calculated incentives based on current month's target vs achievement.
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted extra-small text-uppercase">
                                <tr>
                                    <th class="ps-4">Employee</th>
                                    <th>Land Share Target & Achieved</th>
                                    <th>Cycle Status & Earned</th>
                                    <th>Cycle Claims</th>
                                    <th>Total Claimed</th>
                                    <th>Disbursed (Paid)</th>
                                    <th class="pe-4 text-end">Wallet Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($realTimeStats)): ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">No target data found for this month.</td></tr>
                                <?php else: ?>
                                    <?php foreach($realTimeStats as $stat): 
                                        $u = $stat['user'];
                                        $ind = $stat['individual'];
                                        $team = $stat['team'];
                                    ?>
                                    <?php 
                                        $cSum = $stat['claims_summary'] ?? [];
                                        $achievedTotal = $ind['assigned_achieved'] + $ind['unassigned_achieved'];
                                    ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm bg-primary-subtle text-primary rounded-circle fw-bold flex-shrink-0 d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-size: 16px;">
                                                    <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($u['full_name']) ?></h6>
                                                    <span class="text-muted extra-small d-block"><?= htmlspecialchars($u['designation'] ?? 'Sales Executive') ?></span>
                                                    <span class="badge bg-light text-dark border extra-small mt-1"><?= $u['employee_id'] ?></span>
                                                    <?php if(!empty($u['team_name'])): ?>
                                                        <span class="badge bg-info-subtle text-info extra-small mt-1"><i class="fa-solid fa-users me-1"></i><?= htmlspecialchars($u['team_name']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($ind['is_inherited'])): ?>
                                                <span class="badge bg-secondary-subtle text-secondary border extra-small mb-1" title="Carried over from prior month because no new target was set for this month">
                                                    <i class="fa-solid fa-clock-rotate-left me-1"></i>Inherited (<?= date('M Y', strtotime($ind['target_origin_month'])) ?>)
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success-subtle text-success border extra-small mb-1">
                                                    <i class="fa-solid fa-check me-1"></i><?= date('M Y', strtotime($selected_month)) ?> Target
                                                </span>
                                            <?php endif; ?>

                                            <div class="user-select-none" style="cursor: pointer;" onclick="openExecutiveCalculationModal(<?= $u['id'] ?>)" title="Click to view full calculation & sales breakdown">
                                                <?php if($ind['target_amount'] > 0): ?>
                                                    <div class="small">Target: <strong class="text-dark">৳<?= number_format($ind['target_amount'], 2) ?></strong></div>
                                                    <div class="small">Achieved: <strong class="text-success">৳<?= number_format($achievedTotal, 2) ?></strong></div>
                                                    <div class="progress mt-1" style="height: 5px;">
                                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= min(100, $ind['achievement_pct']) ?>%;"></div>
                                                    </div>
                                                    <div class="d-flex align-items-center justify-content-between mt-1">
                                                        <div class="extra-small text-muted"><?= number_format($ind['achievement_pct'], 1) ?>% Achieved</div>
                                                        <span class="badge bg-info-subtle text-info border border-info-subtle extra-small"><i class="fa-solid fa-calculator me-1"></i>Breakdown</span>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="small text-muted">No Explicit Target</div>
                                                    <div class="small">Achieved: <strong class="text-success">৳<?= number_format($achievedTotal, 2) ?></strong></div>
                                                    <span class="badge bg-info-subtle text-info border border-info-subtle extra-small mt-1"><i class="fa-solid fa-calculator me-1"></i>Breakdown</span>
                                                <?php endif; ?>
                                            </div>

                                            <?php if (in_array((int)($_SESSION['user']['role_id'] ?? 0), [1, 3, 6, 20, 30, 32, 33, 34, 35], true) || !empty($isManagement)): ?>
                                                <div class="mt-1">
                                                    <a href="<?= $GLOBALS['base_url'] ?>/set-sales-target/<?= $u['id'] ?>" class="btn btn-xs btn-outline-primary border py-0 px-2" style="font-size: 11px;" title="Set or Edit Target for <?= htmlspecialchars($u['full_name']) ?>">
                                                        <i class="fa-solid fa-pen-to-square me-1"></i>Set / Edit
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="cursor: pointer;" onclick="openExecutiveCalculationModal(<?= $u['id'] ?>)" title="Click to view calculation breakdown">
                                            <?php if(!empty($ind['is_eligible'])): ?>
                                                <span class="badge bg-success-subtle text-success fw-semibold mb-1">
                                                    <i class="fa-solid fa-check me-1"></i>Target Filled Up (≥ 25L)
                                                </span>
                                                <div class="small fw-bold text-success">Earned: ৳<?= number_format($ind['total_earned_incentive'], 2) ?></div>
                                                <div class="extra-small text-primary mt-1"><i class="fa-solid fa-circle-question me-1"></i>How is this calculated?</div>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle extra-small mb-1">
                                                    <i class="fa-solid fa-clock me-1"></i>Pending 25L Min
                                                </span>
                                                <div class="extra-small text-muted">Earned: ৳0.00</div>
                                                <div class="extra-small text-muted mt-1"><i class="fa-solid fa-eye me-1"></i>View Sales Progress</div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if(($cSum['cycle_claims_count'] ?? 0) > 0): ?>
                                                <span class="badge bg-primary text-white rounded-pill px-2 py-1 extra-small">
                                                    <?= (int)$cSum['cycle_claims_count'] ?> Claims
                                                </span>
                                                <div class="small fw-bold text-primary mt-1">৳<?= number_format($cSum['cycle_claimed_amount'] ?? 0, 2) ?></div>
                                            <?php else: ?>
                                                <span class="text-muted extra-small">0 claims</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="small fw-bold text-dark">৳<?= number_format($cSum['total_claimed_amount'] ?? 0, 2) ?></div>
                                            <span class="text-muted extra-small"><?= (int)($cSum['total_claims_count'] ?? 0) ?> all-time</span>
                                        </td>
                                        <td>
                                            <div class="small fw-bold text-success">৳<?= number_format($cSum['total_paid_amount'] ?? 0, 2) ?></div>
                                            <span class="badge bg-success-subtle text-success extra-small">Disbursed</span>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <h6 class="fw-bold text-info mb-0">৳<?= number_format($cSum['wallet_balance'] ?? 0, 2) ?></h6>
                                            <span class="text-muted extra-small">Pending Balance</span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 1: Sales Person Claims Queue -->
                <div class="tab-pane fade" id="sales-claims" role="tabpanel">
                    <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                        <span class="text-muted extra-small fw-semibold">
                            <i class="fa-solid fa-info-circle me-1"></i>Review all company sales bookings and submitted claims. Claims approved here clear the management queue and move to Accounts disbursement.
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted extra-small text-uppercase">
                                <tr>
                                    <th class="ps-4">Booking / Claim Ref</th>
                                    <th>Sales Executive & Dept</th>
                                    <th>Project & Unit</th>
                                    <th>Flat Price & Land Share</th>
                                    <th>Inc. %</th>
                                    <th>Incentive Amount</th>
                                    <th class="text-center">Verification</th>
                                    <th class="text-center">Claim Status</th>
                                    <th class="pe-4 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($salesClaimsQueue)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4 text-muted">No sales bookings or incentive claims found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($salesClaimsQueue as $claim): ?>
                                        <?php
                                            if (!class_exists('ApprovalRequest')) {
                                                require_once __DIR__ . '/../../models/ApprovalRequest.php';
                                            }
                                            $ahInfoClaim = ApprovalRequest::getAccountsHeadApprovalInfo($GLOBALS['db'] ?? ($this->db ?? null), 'project_incentive_claims', (int)$claim['id']);
                                            $isAhApprovedClaim = empty($ahInfoClaim['has_request']) || !empty($ahInfoClaim['is_accounts_head_approved']);
                                        ?>
                                        <tr>
                                            <!-- Code & Date -->
                                            <td class="ps-4">
                                                <span class="fw-bold text-dark d-block"><?= htmlspecialchars($claim['claim_code']) ?></span>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle extra-small mb-1" title="Sales Cycle Month">
                                                    <i class="fa-regular fa-calendar me-1"></i>Cycle: <?= htmlspecialchars($claim['sale_cycle_display'] ?? '') ?>
                                                </span>
                                                <?php if (!empty($claim['booking_id'])): ?>
                                                    <div class="extra-small text-muted">
                                                        <i class="fa-solid fa-file-invoice me-1"></i>Booking #<?= $claim['booking_id'] ?><?= !empty($claim['booking_date']) ? ' (' . date('d M, Y', strtotime($claim['booking_date'])) . ')' : '' ?>
                                                    </div>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-xs btn-outline-info rounded-pill px-2 py-0 extra-small mt-1 shadow-2xs fw-semibold" onclick="openClaimSaleDetailsModal(<?= htmlspecialchars(json_encode($claim), ENT_QUOTES, 'UTF-8') ?>)">
                                                    <i class="fa-solid fa-circle-info me-1"></i>Sale Details
                                                </button>
                                            </td>

                                            <!-- Employee Info -->
                                            <td>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($claim['user_name']) ?></div>
                                                <span class="badge bg-secondary-subtle text-secondary extra-small"><?= htmlspecialchars($claim['employee_id']) ?></span>
                                                <span class="text-muted extra-small">| <?= htmlspecialchars($claim['department']) ?></span>
                                            </td>

                                            <!-- Project Unit -->
                                            <td>
                                                <?php if ($claim['split_type'] === 'tl_override'): ?>
                                                    <span class="badge bg-info-subtle text-info border border-info-subtle extra-small mb-1">
                                                        <i class="fa-solid fa-users me-1"></i>TL 0.05% Override
                                                    </span>
                                                    <div class="small fw-semibold text-dark">
                                                        From: <?= htmlspecialchars($claim['original_seller_name'] ?: 'Team Member') ?> <?= !empty($claim['original_seller_code']) ? '[' . htmlspecialchars($claim['original_seller_code']) . ']' : '' ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="fw-semibold text-dark d-block"><?= htmlspecialchars($claim['project_name']) ?></span>
                                                    <span class="text-primary extra-small"><?= htmlspecialchars($claim['flat_name']) ?><?= !empty($claim['floor_no']) ? ' (Floor ' . htmlspecialchars($claim['floor_no']) . ')' : '' ?></span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Flat Price & Land Share -->
                                            <td>
                                                <span class="fw-bold text-dark d-block">৳<?= number_format($claim['sale_amount'], 2) ?></span>
                                                <span class="badge bg-primary-subtle text-primary extra-small" title="Land Share Value">
                                                    <i class="fa-solid fa-map-location-dot me-1"></i>Land Share: ৳<?= number_format($claim['land_share_value'], 2) ?>
                                                </span>
                                            </td>

                                            <!-- Inc % -->
                                            <td>
                                                <span class="badge bg-info-subtle text-info fw-bold"><?= number_format($claim['incentive_percentage'], 2) ?>%</span>
                                            </td>

                                            <!-- Incentive Amount -->
                                            <td>
                                                <span class="fw-bold text-success fs-6">৳<?= number_format($claim['incentive_amount'], 2) ?></span>
                                            </td>

                                            <!-- Verification Status -->
                                            <td class="text-center">
                                                <?php if ($claim['split_type'] === 'tl_override'): ?>
                                                    <span class="badge bg-info-subtle text-info border border-info-subtle extra-small rounded-pill d-block mb-1" title="Team Leader Override on Member Achievement">
                                                        <i class="fa-solid fa-users me-1"></i>Team Override
                                                    </span>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle extra-small rounded-pill d-block" title="Team Member achieved ≥25 Lakh target">
                                                        <i class="fa-solid fa-award me-1"></i>Qualified (≥25L)
                                                    </span>
                                                <?php else: ?>
                                                    <!-- Booking Money Status -->
                                                    <?php if (($claim['booking_verification_status'] ?? '') === 'verified'): ?>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle extra-small rounded-pill d-block mb-1" title="Booking Money verified by Accounts">
                                                            <i class="fa-solid fa-check-circle me-1"></i>Booking Verified
                                                        </span>
                                                    <?php elseif (($claim['booking_verification_status'] ?? '') === 'pending'): ?>
                                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle extra-small rounded-pill d-block mb-1" title="Booking Money submitted, pending Accounts verification">
                                                            <i class="fa-solid fa-clock me-1"></i>Booking Unverified
                                                        </span>
                                                    <?php elseif (($claim['booking_verification_status'] ?? '') === 'rejected'): ?>
                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle extra-small rounded-pill d-block mb-1" title="Booking Money payment rejected">
                                                            <i class="fa-solid fa-circle-xmark me-1"></i>Booking Rejected
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle extra-small rounded-pill d-block mb-1" title="Booking Money payment not submitted">
                                                            <i class="fa-solid fa-circle-exclamation me-1"></i>Booking Incomplete
                                                        </span>
                                                    <?php endif; ?>

                                                    <!-- Registration Status -->
                                                    <?php if (($claim['reg_verification_status'] ?? '') === 'verified'): ?>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle extra-small rounded-pill d-block" title="Registration Fee verified by Accounts">
                                                            <i class="fa-solid fa-check-circle me-1"></i>Reg Verified
                                                        </span>
                                                    <?php elseif (($claim['reg_verification_status'] ?? '') === 'pending'): ?>
                                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle extra-small rounded-pill d-block" title="Registration Fee submitted, pending Accounts verification">
                                                            <i class="fa-solid fa-clock me-1"></i>Reg Unverified
                                                        </span>
                                                    <?php elseif (($claim['reg_verification_status'] ?? '') === 'rejected'): ?>
                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle extra-small rounded-pill d-block" title="Registration Fee payment rejected">
                                                            <i class="fa-solid fa-circle-xmark me-1"></i>Reg Rejected
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle extra-small rounded-pill d-block" title="Registration payment not completed yet">
                                                            <i class="fa-solid fa-minus-circle me-1"></i>Registration Incomplete
                                                        </span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Claim Status -->
                                            <td class="text-center py-2">
                                                <?php if ($claim['claim_status'] === 'not_claimed'): ?>
                                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-1 extra-small border">
                                                        <i class="fa-solid fa-hourglass-start me-1"></i>Not Claimed
                                                    </span>
                                                <?php elseif ($claim['claim_status'] === 'claimed'): ?>
                                                    <?php
                                                    $isOver1LacClaim = ((float)$claim['incentive_amount'] > 10000);
                                                    $isOpHeadApprovedClaim = (!empty($ahInfoClaim['is_op_head_approved']) || ($ahInfoClaim['op_head_status'] ?? '') === 'approved');
                                                    ?>
                                                    <?php if ($isOver1LacClaim && !$isOpHeadApprovedClaim): ?>
                                                        <span class="badge bg-warning text-dark rounded-pill px-2 py-1 extra-small">
                                                            <i class="fa-solid fa-hourglass-half me-1"></i>Pending Op Head (>10K)
                                                        </span>
                                                    <?php elseif ($isOver1LacClaim && !$isAhApprovedClaim): ?>
                                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2 py-1 extra-small fw-semibold">
                                                            <i class="fa-solid fa-lock me-1"></i>Pending AH Approval
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark rounded-pill px-2 py-1 extra-small">
                                                            <i class="fa-solid fa-clock me-1"></i>Awaiting Mgmt Approval
                                                        </span>
                                                    <?php endif; ?>
                                                <?php elseif ($claim['claim_status'] === 'approved'): ?>
                                                    <?php if (!$isAhApprovedClaim): ?>
                                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2 py-1 extra-small fw-semibold">
                                                            <i class="fa-solid fa-lock me-1"></i>Pending AH Approval
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-info text-white rounded-pill px-2 py-1 extra-small">
                                                            <i class="fa-solid fa-thumbs-up me-1"></i>Ready to Disburse
                                                        </span>
                                                    <?php endif; ?>
                                                <?php elseif ($claim['claim_status'] === 'partially_paid'): ?>
                                                    <span class="badge bg-primary text-white rounded-pill px-2 py-1 extra-small">
                                                        <i class="fa-solid fa-pie-chart me-1"></i>Partially Paid
                                                    </span>
                                                <?php elseif ($claim['claim_status'] === 'paid'): ?>
                                                    <span class="badge bg-success text-white rounded-pill px-2 py-1 extra-small">
                                                        <i class="fa-solid fa-check-circle me-1"></i>Paid & Settled
                                                    </span>
                                                    <?php if (!empty($claim['is_received'])): ?>
                                                        <div class="mt-1">
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill extra-small px-2 py-0" title="Employee confirmed payout receipt">
                                                                <i class="fa-solid fa-hand-holding-dollar me-1"></i>Received by Employee
                                                            </span>
                                                            <div class="text-muted" style="font-size:0.64rem;"><?= date('d M, h:i A', strtotime($claim['received_at'])) ?></div>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="mt-1">
                                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill extra-small px-2 py-0" title="Payout disbursed by Accounts, awaiting employee receipt confirmation">
                                                                <i class="fa-solid fa-clock me-1"></i>Awaiting Receipt
                                                            </span>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php elseif ($claim['claim_status'] === 'rejected'): ?>
                                                    <span class="badge bg-danger text-white rounded-pill px-2 py-1 extra-small">
                                                        <i class="fa-solid fa-times-circle me-1"></i>Rejected
                                                    </span>
                                                <?php endif; ?>

                                                <!-- Compact Approval History Audit Trail -->
                                                <?php if (!empty($claim['mgmt_approved_at']) || !empty($claim['ah_approved_at']) || (in_array($claim['claim_status'], ['approved', 'partially_paid']) && !$isAhApprovedClaim)): ?>
                                                    <div class="mt-1 text-start px-2 py-1 rounded bg-light border border-light-subtle d-inline-block" style="font-size: 0.69rem; line-height: 1.3; min-width: 175px;">
                                                        <?php if (!empty($claim['mgmt_approved_at'])): ?>
                                                            <div class="text-nowrap text-success fw-semibold">
                                                                <i class="fa-solid fa-circle-check text-success me-1"></i>Mgmt: <span class="text-dark"><?= htmlspecialchars($claim['mgmt_approver_name'] ?? 'Management') ?></span> <span class="text-muted" style="font-size:0.64rem;">(<?= date('d M, h:i A', strtotime($claim['mgmt_approved_at'])) ?>)</span>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($claim['ah_approved_at'])): ?>
                                                            <div class="text-nowrap text-success fw-semibold">
                                                                <i class="fa-solid fa-stamp text-success me-1"></i>AH: <span class="text-dark"><?= htmlspecialchars($claim['ah_approver_name'] ?? 'Accounts Head') ?><?= !empty($claim['ah_approver_code']) ? ' [' . htmlspecialchars($claim['ah_approver_code']) . ']' : '' ?></span> <span class="text-muted" style="font-size:0.64rem;">(<?= date('d M, h:i A', strtotime($claim['ah_approved_at'])) ?>)</span>
                                                            </div>
                                                        <?php elseif (in_array($claim['claim_status'], ['approved', 'partially_paid']) && !$isAhApprovedClaim): ?>
                                                            <div class="text-nowrap text-warning fw-semibold">
                                                                <i class="fa-solid fa-lock text-warning me-1"></i>AH: <span class="text-dark">Accounts Head</span> <span class="badge bg-warning text-dark py-0 px-1" style="font-size:0.60rem;">Pending</span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Actions -->
                                            <td class="pe-4 text-end">
                                                <?php if ($claim['claim_status'] === 'not_claimed'): ?>
                                                    <button class="btn btn-sm btn-light text-muted rounded-2 disabled" disabled title="Waiting for Sales Executive to click Claim">
                                                        <i class="fa-solid fa-hourglass-start me-1"></i>Not Claimed Yet
                                                    </button>
                                                <?php elseif ($claim['claim_status'] === 'claimed'): ?>
                                                    <?php
                                                    $userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
                                                    $userRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
                                                    $isOpHeadUserClaim = (in_array($userRoleId, [20, 32], true) || str_contains($userRoleName, 'operation head'));
                                                    $isMgmtUserClaim = in_array($userRoleId, [1, 3], true);
                                                    $canApproveThisClaim = ($isOpHeadUserClaim || $isMgmtUserClaim || $isManagement);
                                                    ?>
                                                    <?php if ($canApproveThisClaim): ?>
                                                        <button class="btn btn-sm btn-success rounded-2 px-2 me-1 btn-approve-claim" data-id="<?= $claim['id'] ?>" title="Approve Claim (Role 20 or Management 3)">
                                                            <i class="fa-solid fa-check me-1"></i>Approve
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger rounded-2 px-2 btn-reject-claim" data-id="<?= $claim['id'] ?>" title="Reject Claim">
                                                            <i class="fa-solid fa-times"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-muted extra-small">Waiting for Approval</span>
                                                    <?php endif; ?>
                                                <?php elseif (in_array($claim['claim_status'], ['approved', 'partially_paid'])): ?>
                                                    <?php if (!$isAhApprovedClaim): ?>
                                                        <?php if ($canApproveAh && !empty($ahInfoClaim['approval_request_id'])): ?>
                                                            <button type="button" class="btn btn-sm btn-primary rounded-2 px-3 shadow-sm" onclick="triggerAhApprove(<?= (int)$ahInfoClaim['approval_request_id'] ?>, 'Sales Incentive Claim: <?= htmlspecialchars(addslashes(($claim['claim_code'] ?? '') . ' (' . ($claim['project_name'] ?? '') . ' - ' . ($claim['flat_name'] ?? '') . ')'), ENT_QUOTES) ?>', '<?= number_format((float)$claim['incentive_amount'], 2) ?>')">
                                                                <i class="fa-solid fa-stamp me-1"></i>AH Approve
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="badge bg-light text-muted border py-1 px-2" style="font-size:10px;" title="Payment locked: Accounts Head approval required">
                                                                <i class="fa-solid fa-lock me-1 text-warning"></i>Awaiting Accounts Head
                                                            </span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <?php if ($isAccounts): ?>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-primary rounded-2 px-3 shadow-sm btn-disburse-modal"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#disbursePaymentModal"
                                                                    data-toggle="modal"
                                                                    data-target="#disbursePaymentModal"
                                                                    onclick="window.populateDisburseModal(this)"
                                                                    data-id="<?= (int)$claim['id'] ?>"
                                                                    data-recipient="<?= htmlspecialchars(($claim['user_name'] ?? 'Sales Executive') . ' (' . ($claim['employee_id'] ?? '') . ')' . (!empty($claim['department']) ? ' - ' . $claim['department'] : ''), ENT_QUOTES) ?>"
                                                                    data-claim="<?= htmlspecialchars(($claim['claim_code'] ?? '') . (!empty($claim['project_name']) ? ' | ' . $claim['project_name'] . ' (' . ($claim['flat_name'] ?? '') . ')' : ''), ENT_QUOTES) ?>"
                                                                    data-amount="<?= number_format((float)(($claim['due_amount'] ?? 0) > 0 ? $claim['due_amount'] : $claim['incentive_amount']), 2, '.', '') ?>"
                                                                    data-account-id="<?= (int)($claim['account_id'] ?? 0) ?>"
                                                                    data-emp-bank="<?= htmlspecialchars($claim['emp_bank_name'] ?? '', ENT_QUOTES) ?>"
                                                                    data-emp-account="<?= htmlspecialchars($claim['emp_account_number'] ?? '', ENT_QUOTES) ?>">
                                                                <i class="fa-solid fa-money-bill-transfer me-1"></i>Disburse Payout
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="text-info extra-small fw-bold">Approved (Pending Payout)</span>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <?php if (!empty($claim['is_received'])): ?>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle extra-small px-2 py-1"><i class="fa-solid fa-check-double me-1"></i>Receipt Confirmed</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light text-muted border extra-small px-2 py-1"><i class="fa-solid fa-clock me-1 text-warning"></i>Awaiting Receipt</span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 2: Non-Sales / External Member Incentive Assignment -->
                <div class="tab-pane fade" id="external-claims" role="tabpanel">
                    <div class="p-3 bg-light border-bottom d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="fw-bold text-dark mb-1">
                                <i class="fa-solid fa-user-plus text-primary me-2"></i>Non-Sales Employee & External Member Incentives
                            </h6>
                            <span class="text-muted extra-small">Assign project sales incentive to Engineers, HR, IT, or external staff who contributed to project conversion.</span>
                        </div>
                        <button class="btn btn-sm btn-primary rounded-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#assignExternalIncentiveModal">
                            <i class="fa-solid fa-plus me-1"></i> New External Assignment
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted extra-small text-uppercase">
                                <tr>
                                    <th class="ps-4">Assignment Code</th>
                                    <th>Beneficiary (Non-Sales Staff)</th>
                                    <th>Department</th>
                                    <th>Project & Flat Unit</th>
                                    <th>Flat Price & Land Share</th>
                                    <th>Incentive % / Amount</th>
                                    <th>Assigned By</th>
                                    <th class="text-center">Status</th>
                                    <th class="pe-4 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($externalClaimsList)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4 text-muted">No external / non-sales member incentive assignments created yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($externalClaimsList as $ext): ?>
                                        <?php
                                            if (!class_exists('ApprovalRequest')) {
                                                require_once __DIR__ . '/../../models/ApprovalRequest.php';
                                            }
                                            $ahInfoExt = ApprovalRequest::getAccountsHeadApprovalInfo($GLOBALS['db'] ?? ($this->db ?? null), 'project_incentive_claims', (int)$ext['id']);
                                            $isAhApprovedExt = empty($ahInfoExt['has_request']) || !empty($ahInfoExt['is_accounts_head_approved']);
                                        ?>
                                        <tr>
                                            <td class="ps-4">
                                                <span class="fw-bold text-dark d-block"><?= htmlspecialchars($ext['claim_code']) ?></span>
                                                <span class="text-muted extra-small"><?= date('d M, Y', strtotime($ext['assigned_at'])) ?></span>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($ext['user_name']) ?></div>
                                                <span class="badge bg-secondary-subtle text-secondary extra-small"><?= htmlspecialchars($ext['employee_id']) ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info-subtle text-info fw-semibold"><?= htmlspecialchars($ext['department']) ?></span>
                                            </td>
                                            <td>
                                                <span class="fw-semibold text-dark d-block"><?= htmlspecialchars($ext['project_name']) ?></span>
                                                <span class="text-primary extra-small"><?= htmlspecialchars($ext['flat_name']) ?></span>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-dark d-block">৳<?= number_format($ext['sale_amount'], 2) ?></span>
                                                <span class="badge bg-primary-subtle text-primary extra-small" title="Land Share Value">
                                                    <i class="fa-solid fa-map-location-dot me-1"></i>Land Share: ৳<?= number_format($ext['land_share_value'], 2) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-success">৳<?= number_format($ext['incentive_amount'], 2) ?></span>
                                                <div class="extra-small text-muted"><?= $ext['incentive_percentage'] ?>% Incentive</div>
                                            </td>
                                            <td>
                                                <span class="fw-semibold text-dark extra-small"><?= htmlspecialchars($ext['assigned_by_name']) ?></span>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($ext['claim_status'] === 'approved'): ?>
                                                    <?php if (!$isAhApprovedExt): ?>
                                                        <span class="badge bg-warning text-dark rounded-pill px-3 py-1 extra-small">
                                                            <i class="fa-solid fa-lock me-1"></i>Pending Accounts Head
                                                        </span>
                                                        <div class="extra-small text-muted mt-1">Accounts Head</div>
                                                    <?php else: ?>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 extra-small mb-1">
                                                            <i class="fa-solid fa-check me-1"></i>AH Approved
                                                        </span>
                                                        <br>
                                                        <span class="badge bg-info text-white rounded-pill px-3 py-1 extra-small">Approved</span>
                                                    <?php endif; ?>
                                                <?php elseif ($ext['claim_status'] === 'paid'): ?>
                                                    <span class="badge bg-success text-white rounded-pill px-3 py-1 extra-small">Disbursed</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="pe-4 text-end">
                                                <?php if ($ext['claim_status'] === 'approved'): ?>
                                                    <?php if (!$isAhApprovedExt): ?>
                                                        <?php if ($canApproveAh && !empty($ahInfoExt['approval_request_id'])): ?>
                                                            <button type="button" class="btn btn-sm btn-primary rounded-2 px-3 shadow-sm" onclick="triggerAhApprove(<?= (int)$ahInfoExt['approval_request_id'] ?>, 'External Member Incentive: <?= htmlspecialchars(addslashes(($ext['claim_code'] ?? '') . ' (' . ($ext['project_name'] ?? '') . ' - ' . ($ext['flat_name'] ?? '') . ')'), ENT_QUOTES) ?>', '<?= number_format((float)$ext['incentive_amount'], 2) ?>')">
                                                                <i class="fa-solid fa-stamp me-1"></i>AH Approve
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="badge bg-light text-muted border py-1 px-2" style="font-size:10px;" title="Payment locked: Accounts Head approval required">
                                                                <i class="fa-solid fa-lock me-1 text-warning"></i>Awaiting Accounts Head
                                                            </span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <?php if ($isAccounts): ?>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-primary rounded-2 px-3 shadow-sm btn-disburse-modal"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#disbursePaymentModal"
                                                                    data-toggle="modal"
                                                                    data-target="#disbursePaymentModal"
                                                                    onclick="window.populateDisburseModal(this)"
                                                                    data-id="<?= (int)$ext['id'] ?>"
                                                                    data-recipient="<?= htmlspecialchars(($ext['user_name'] ?? 'External Member') . ' (' . ($ext['employee_id'] ?? '') . ')' . (!empty($ext['department']) ? ' - ' . $ext['department'] : ''), ENT_QUOTES) ?>"
                                                                    data-claim="<?= htmlspecialchars(($ext['claim_code'] ?? '') . (!empty($ext['project_name']) ? ' | ' . $ext['project_name'] . ' (' . ($ext['flat_name'] ?? '') . ')' : ''), ENT_QUOTES) ?>"
                                                                    data-amount="<?= number_format((float)(($ext['due_amount'] ?? 0) > 0 ? $ext['due_amount'] : $ext['incentive_amount']), 2, '.', '') ?>"
                                                                    data-account-id="<?= (int)($ext['account_id'] ?? 0) ?>"
                                                                    data-emp-bank="<?= htmlspecialchars($ext['emp_bank_name'] ?? '', ENT_QUOTES) ?>"
                                                                    data-emp-account="<?= htmlspecialchars($ext['emp_account_number'] ?? '', ENT_QUOTES) ?>">
                                                                <i class="fa-solid fa-money-bill-transfer me-1"></i>Pay Now
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="text-info extra-small fw-bold">Approved (Pending Payout)</span>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-success extra-small fw-bold"><i class="fa-solid fa-check-circle me-1"></i>Settled</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 3: Employee Wallets & Payout History -->
                <div class="tab-pane fade" id="wallets" role="tabpanel">
                    <div class="row p-3 g-4">
                        <!-- Employee Wallet Summary Table -->
                        <div class="col-xl-6 col-12 border-end">
                            <h6 class="fw-bold text-dark mb-3">
                                <i class="fa-solid fa-wallet text-indigo me-2"></i>Employee Wallet Balances
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="bg-light text-muted extra-small text-uppercase">
                                        <tr>
                                            <th>Employee</th>
                                            <th>Role Type</th>
                                            <th>Approved</th>
                                            <th>Paid</th>
                                            <th class="text-end">Wallet Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($employeeWallets)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-3">No employee wallet data available.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($employeeWallets as $wallet): ?>
                                                <tr>
                                                    <td>
                                                        <div class="fw-bold text-dark extra-small"><?= htmlspecialchars($wallet['user_name']) ?></div>
                                                        <span class="text-muted extra-small"><?= htmlspecialchars($wallet['employee_id']) ?> | <?= htmlspecialchars($wallet['department']) ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary-subtle text-secondary extra-small"><?= htmlspecialchars($wallet['role_type']) ?></span>
                                                    </td>
                                                    <td class="extra-small fw-semibold">৳<?= number_format($wallet['approved_amt']) ?></td>
                                                    <td class="extra-small text-success fw-semibold">৳<?= number_format($wallet['paid_amt']) ?></td>
                                                    <td class="text-end">
                                                        <span class="fw-bold text-dark bg-warning-subtle px-2 py-1 rounded extra-small">৳<?= number_format($wallet['wallet_balance'], 2) ?></span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Accounts Disbursement Logs -->
                        <div class="col-xl-6 col-12">
                            <h6 class="fw-bold text-dark mb-3">
                                <i class="fa-solid fa-receipt text-success me-2"></i>Accounts Disbursement Log
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="bg-light text-muted extra-small text-uppercase">
                                        <tr>
                                            <th>Payout No & Date</th>
                                            <th>Recipient</th>
                                            <th>Method & Ref</th>
                                            <th class="text-end">Disbursed</th>
                                            <th class="text-center">Receipt Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($disbursementLogs)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-3">No disbursement logs found.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($disbursementLogs as $log): ?>
                                                <tr>
                                                    <td>
                                                        <span class="fw-bold text-dark extra-small d-block"><?= htmlspecialchars($log['disbursement_no']) ?></span>
                                                        <span class="text-muted extra-small d-block"><?= date('d M, Y', strtotime($log['payment_date'])) ?></span>
                                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle extra-small mt-1" title="Sales Cycle Month">
                                                            <i class="fa-regular fa-calendar me-1"></i>Cycle: <?= htmlspecialchars($log['sale_cycle_display'] ?? '') ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="fw-semibold text-dark extra-small d-block"><?= htmlspecialchars($log['recipient_name']) ?></span>
                                                        <?php if ($log['split_type'] === 'tl_override'): ?>
                                                            <div class="extra-small text-dark fw-semibold mt-1">
                                                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle extra-small"><i class="fa-solid fa-users me-1"></i>TL Override</span>
                                                                From: <?= htmlspecialchars($log['original_seller_name']) ?> <?= !empty($log['original_seller_code']) ? '[' . htmlspecialchars($log['original_seller_code']) . ']' : '' ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="extra-small text-dark fw-semibold mt-1">
                                                                <i class="fa-solid fa-building me-1 text-primary"></i><?= htmlspecialchars($log['project_name']) ?> - <?= htmlspecialchars($log['flat_name']) ?><?= !empty($log['floor_no']) ? ' (Floor ' . $log['floor_no'] . ')' : '' ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div class="d-flex align-items-center gap-1 mt-1">
                                                            <code class="extra-small text-muted"><?= htmlspecialchars($log['claim_code']) ?></code>
                                                            <?php if (!empty($log['booking_id'])): ?>
                                                                <span class="extra-small text-muted">| #<?= $log['booking_id'] ?></span>
                                                            <?php endif; ?>
                                                            <button type="button" class="btn btn-xs btn-outline-info rounded-pill px-2 py-0 extra-small shadow-2xs ms-1" onclick="openPayoutSaleDetailsModal(<?= htmlspecialchars(json_encode($log), ENT_QUOTES, 'UTF-8') ?>)">
                                                                <i class="fa-solid fa-circle-info me-1"></i>Sale Details
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info-subtle text-info extra-small me-1"><?= htmlspecialchars($log['payment_method']) ?></span>
                                                        <code class="extra-small"><?= htmlspecialchars($log['transaction_ref']) ?></code>
                                                        <?php if (!empty($log['company_bank_name']) || !empty($log['bank_name']) && $log['bank_name'] !== 'N/A'): ?>
                                                            <div class="extra-small text-muted mt-1">
                                                                <i class="fa-solid fa-building-columns me-1 text-primary"></i>From: <?= htmlspecialchars($log['company_bank_name'] ?: $log['bank_name']) ?>
                                                                <?php if (!empty($log['to_bank_name'])): ?>
                                                                    <br><i class="fa-solid fa-money-check me-1 text-success"></i>To: <?= htmlspecialchars($log['to_bank_name']) ?>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-end">
                                                        <span class="fw-bold text-success extra-small d-block">৳<?= number_format($log['payment_amount'], 2) ?></span>
                                                        <?php if ($log['incentive_percentage'] > 0): ?>
                                                            <span class="extra-small text-muted" style="font-size:0.65rem;">
                                                                <?= number_format($log['incentive_percentage'], 2) ?>% on ৳<?= number_format($log['land_share_value'], 0) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if (!empty($log['is_received'])): ?>
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle extra-small rounded-pill px-2 py-1">
                                                                <i class="fa-solid fa-check-circle me-1"></i>Received
                                                            </span>
                                                            <div class="text-muted mt-1" style="font-size:0.65rem;">
                                                                <?= date('d M, h:i A', strtotime($log['received_at'])) ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle extra-small rounded-pill px-2 py-1" title="Awaiting employee receipt confirmation">
                                                                <i class="fa-solid fa-clock me-1"></i>Awaiting Receipt
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

                <!-- TAB 5: Team Leader Overrides (0.05%) -->
                <div class="tab-pane fade" id="team-overrides" role="tabpanel">
                    <div class="p-3">
                        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                            <div>
                                <h6 class="fw-bold text-dark mb-0">
                                    <i class="fa-solid fa-people-arrows text-warning me-2"></i>Team Leader 0.05% Overrides From Member Sales
                                </h6>
                                <span class="text-muted extra-small">Tracks override incentives gained by Team Leaders when their team members achieve ≥ ৳25 Lakh in land share sales.</span>
                            </div>
                            <?php 
                            $totalTlOverridesSum = 0.00;
                            if (!empty($allTeamOverrides)) {
                                foreach ($allTeamOverrides as $to) {
                                    $totalTlOverridesSum += (float)($to['tl_gain'] ?? 0);
                                }
                            }
                            ?>
                            <span class="badge bg-warning-subtle text-dark border px-3 py-2 rounded-pill fw-bold fs-7">
                                Total TL Overrides: ৳<?= number_format($totalTlOverridesSum, 2) ?>
                            </span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light text-muted extra-small text-uppercase">
                                    <tr>
                                        <th class="ps-4">Sales Team</th>
                                        <th>Team Leader</th>
                                        <th>Team Member</th>
                                        <th>Member Target</th>
                                        <th>Achieved Land Share</th>
                                        <th>Qualification Status</th>
                                        <th>Override Rate</th>
                                        <th>TL Incentive Gained</th>
                                        <th>Claim Status</th>
                                        <th class="pe-4 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($allTeamOverrides)): ?>
                                        <tr>
                                            <td colspan="10" class="text-center py-4 text-muted">
                                                <i class="fa-solid fa-people-carry-box mb-2 d-block fs-3 opacity-50"></i>
                                                No team members have achieved qualifying land share sales in this cycle yet.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($allTeamOverrides as $to): ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="badge bg-primary-subtle text-primary border fw-semibold">
                                                        <i class="fa-solid fa-users me-1"></i><?= htmlspecialchars($to['team_name']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-dark d-block"><?= htmlspecialchars($to['tl_name']) ?></span>
                                                    <code class="extra-small text-muted">[<?= htmlspecialchars($to['tl_code']) ?>]</code>
                                                </td>
                                                <td>
                                                    <span class="fw-semibold text-dark d-block"><?= htmlspecialchars($to['member_name']) ?></span>
                                                    <code class="extra-small text-muted">[<?= htmlspecialchars($to['member_code']) ?>]</code>
                                                    <span class="badge bg-light text-dark border extra-small ms-1"><?= htmlspecialchars($to['member_designation']) ?></span>
                                                </td>
                                                <td>
                                                    <span class="text-muted small">৳<?= number_format($to['member_target'], 2) ?></span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-dark small">৳<?= number_format($to['member_achieved'], 2) ?></span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($to['is_qualified'])): ?>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                            <i class="fa-solid fa-circle-check me-1"></i>Qualified (≥ 25L)
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1">
                                                            <i class="fa-solid fa-clock me-1"></i>Pending 25L
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info-subtle text-info fw-bold"><?= $to['tl_rate'] ?? 0.05 ?>%</span>
                                                </td>
                                                <td>
                                                    <?php if ($to['tl_gain'] > 0): ?>
                                                        <span class="fw-bold text-success fs-6">+৳<?= number_format($to['tl_gain'], 2) ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted small">৳0.00</span>
                                                    <?php endif; ?>
                                                </td>
                                                <?php
                                                $toClaimStatus = $to['claim_status'] ?? null;
                                                $toClaimId = (int)($to['claim_id'] ?? 0);
                                                $toAppId = (int)($to['approval_request_id'] ?? 0);
                                                $isAhApprovedTo = empty($toAppId) || ($to['accounts_head_status'] ?? '') === 'approved';
                                                ?>
                                                <td>
                                                    <?php if (empty($toClaimStatus)): ?>
                                                        <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1 extra-small border">
                                                            <i class="fa-solid fa-hourglass-start me-1"></i>Not Claimed Yet
                                                        </span>
                                                    <?php elseif ($toClaimStatus === 'claimed'): ?>
                                                        <span class="badge bg-warning text-dark rounded-pill px-3 py-1 extra-small shadow-sm">
                                                            <i class="fa-solid fa-clock me-1"></i>Pending Mgmt
                                                        </span>
                                                    <?php elseif ($toClaimStatus === 'approved'): ?>
                                                        <?php if (!$isAhApprovedTo): ?>
                                                            <span class="badge bg-warning text-dark rounded-pill px-3 py-1 extra-small">
                                                                <i class="fa-solid fa-lock me-1"></i>Pending Accounts Head
                                                            </span>
                                                            <div class="extra-small text-muted mt-1">Accounts Head</div>
                                                        <?php else: ?>
                                                            <span class="badge bg-info text-white rounded-pill px-3 py-1 extra-small">
                                                                <i class="fa-solid fa-thumbs-up me-1"></i>Ready to Disburse
                                                            </span>
                                                        <?php endif; ?>
                                                    <?php elseif ($toClaimStatus === 'paid'): ?>
                                                        <span class="badge bg-success text-white rounded-pill px-3 py-1 extra-small">
                                                            <i class="fa-solid fa-check-circle me-1"></i>Paid & Settled
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="pe-4 text-end">
                                                    <?php if (empty($toClaimStatus)): ?>
                                                        <span class="text-muted extra-small">Awaiting TL Claim</span>
                                                    <?php elseif ($toClaimStatus === 'claimed'): ?>
                                                        <?php if ($canApproveThisClaim): ?>
                                                            <button class="btn btn-sm btn-success rounded-2 px-2 me-1 btn-approve-claim" data-id="<?= $toClaimId ?>" title="Approve TL Override">
                                                                <i class="fa-solid fa-check me-1"></i>Approve
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-danger rounded-2 px-2 btn-reject-claim" data-id="<?= $toClaimId ?>" title="Reject Claim">
                                                                <i class="fa-solid fa-times"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="text-muted extra-small">Waiting for Approval</span>
                                                        <?php endif; ?>
                                                    <?php elseif ($toClaimStatus === 'approved'): ?>
                                                        <?php if (!$isAhApprovedTo): ?>
                                                            <?php if ($canApproveAh && !empty($toAppId)): ?>
                                                                <button type="button" class="btn btn-sm btn-primary rounded-2 px-3 shadow-sm" onclick="triggerAhApprove(<?= $toAppId ?>, 'TL 0.05% Override: <?= htmlspecialchars(addslashes($to['tl_name'] . ' from ' . $to['member_name']), ENT_QUOTES) ?>', '<?= number_format((float)$to['tl_gain'], 2) ?>')">
                                                                    <i class="fa-solid fa-stamp me-1"></i>AH Approve
                                                                </button>
                                                            <?php else: ?>
                                                                <span class="badge bg-light text-muted border py-1 px-2" style="font-size:10px;">
                                                                    <i class="fa-solid fa-lock me-1 text-warning"></i>Awaiting Accounts Head
                                                                </span>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <?php if ($isAccounts): ?>
                                                                <button type="button" 
                                                                        class="btn btn-sm btn-primary rounded-2 px-3 shadow-sm btn-disburse-modal"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#disbursePaymentModal"
                                                                        data-toggle="modal"
                                                                        data-target="#disbursePaymentModal"
                                                                        onclick="window.populateDisburseModal(this)"
                                                                        data-id="<?= $toClaimId ?>"
                                                                        data-recipient="<?= htmlspecialchars($to['tl_name'] . ' (' . $to['tl_code'] . ') - Sales', ENT_QUOTES) ?>"
                                                                        data-claim="INC-TL-<?= str_pad($toClaimId, 3, '0', STR_PAD_LEFT) ?> | TL 0.05% Override (From <?= htmlspecialchars($to['member_name'], ENT_QUOTES) ?>)"
                                                                        data-amount="<?= number_format((float)$to['tl_gain'], 2, '.', '') ?>"
                                                                        data-account-id="0"
                                                                        data-emp-bank=""
                                                                        data-emp-account="">
                                                                    <i class="fa-solid fa-money-bill-transfer me-1"></i>Disburse Payout
                                                                </button>
                                                            <?php else: ?>
                                                                <span class="text-info extra-small fw-bold">Approved (Pending Payout)</span>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    <?php elseif ($toClaimStatus === 'paid'): ?>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 extra-small">
                                                            <i class="fa-solid fa-check me-1"></i>Disbursed
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

                <!-- TAB 6: Unassigned (70/30) Splits -->
                <div class="tab-pane fade" id="cross-splits" role="tabpanel">
                    <div class="p-3">
                        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                            <div>
                                <h6 class="fw-bold text-dark mb-0">
                                    <i class="fa-solid fa-arrows-split-up-and-left text-info me-2"></i>Cross-Project Sales & Split Tracking (70% Seller / 30% Designated)
                                </h6>
                                <span class="text-muted extra-small">Sales of flats outside the selling agent's designated target. Tracks 70% seller share and 30% designated project holder credit.</span>
                            </div>
                            <?php 
                            $totalCrossVolume = 0.00;
                            if (!empty($allCrossProjectSplits)) {
                                foreach ($allCrossProjectSplits as $cs) {
                                    $totalCrossVolume += (float)($cs['land_share_value'] ?? 0);
                                }
                            }
                            ?>
                            <span class="badge bg-info-subtle text-info border px-3 py-2 rounded-pill fw-bold fs-7">
                                Cross Sales Volume: ৳<?= number_format($totalCrossVolume, 2) ?>
                            </span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light text-muted extra-small text-uppercase">
                                    <tr>
                                        <th class="ps-4">Booking Ref</th>
                                        <th>Project & Flat</th>
                                        <th>Land Share Base</th>
                                        <th>Selling Agent (70% Share)</th>
                                        <th>70% Status</th>
                                        <th>Designated Agent (30% Share)</th>
                                        <th class="pe-4 text-end">Total Split (100%)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($allCrossProjectSplits)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">
                                                <i class="fa-solid fa-map-location-dot mb-2 d-block fs-3 opacity-50"></i>
                                                No outside/cross-project sales recorded in this cycle.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($allCrossProjectSplits as $cs): ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="badge bg-light text-dark border">BK-<?= str_pad($cs['booking_id'], 5, '0', STR_PAD_LEFT) ?></span>
                                                    <div class="extra-small text-muted"><?= date('d M, Y', strtotime($cs['booking_date'])) ?></div>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-dark d-block"><?= htmlspecialchars($cs['project_name']) ?></span>
                                                    <span class="badge bg-primary-subtle text-primary extra-small"><?= htmlspecialchars($cs['flat_name']) ?></span>
                                                </td>
                                                <td>
                                                    <span class="text-dark fw-semibold">৳<?= number_format($cs['land_share_value'], 2) ?></span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-dark d-block"><?= htmlspecialchars($cs['seller_name']) ?></span>
                                                    <code class="extra-small text-muted">[<?= htmlspecialchars($cs['seller_code']) ?>]</code>
                                                    <span class="fw-bold text-success d-block small">70%: ৳<?= number_format($cs['seller_share'], 2) ?></span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($cs['is_on_hold'])): ?>
                                                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1" title="Seller has not achieved 25L min qualification">
                                                            <i class="fa-solid fa-lock me-1 text-warning"></i>On Hold (Pending 25L)
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                            <i class="fa-solid fa-unlock me-1"></i>Unlocked (≥ 25L)
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="fw-semibold text-dark d-block">
                                                        <i class="fa-solid fa-user-check me-1 text-primary"></i><?= htmlspecialchars($cs['designated_user_name'] ?? 'Unassigned / Open') ?>
                                                    </span>
                                                    <span class="fw-bold text-indigo small">30%: ৳<?= number_format($cs['designated_share'], 2) ?></span>
                                                </td>
                                                <td class="pe-4 text-end">
                                                    <span class="fw-bold text-dark fs-6">৳<?= number_format(($cs['seller_share'] + $cs['designated_share']), 2) ?></span>
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
</div>

<!-- Modal: Assign External Incentive (Non-Sales Member) -->
<div id="assignExternalIncentiveModal" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form id="externalIncentiveForm">
                <div class="modal-header bg-gradient text-white" style="background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);">
                    <h5 class="modal-title fw-bold" id="assignExternalIncentiveModalLabel">
                        <i class="fa-solid fa-user-plus me-2"></i>Assign Incentive to Non-Sales Employee
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-light border rounded-3 mb-4 d-flex align-items-center">
                        <i class="fa-solid fa-circle-info fs-3 text-indigo me-3"></i>
                        <div class="small text-muted">
                            Use this form to assign sales incentives to non-sales team members (e.g. Engineers, HR, IT, Architects). Select the beneficiary from the employee list, choose the project sale, and set the custom incentive amount.
                        </div>
                    </div>

                    <div class="row">
                        <!-- Beneficiary Select (Name, Employee ID, Department) -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-dark fw-bold small">Select Employee / Beneficiary <span class="text-danger">*</span></label>
                            <select class="form-select border-primary-subtle" name="user_id" required>
                                <option value="">-- Choose Employee / User --</option>
                                <?php foreach ($nonSalesEmployees as $emp): ?>
                                    <option value="<?= $emp['user_id'] ?>">
                                        <?= htmlspecialchars($emp['name']) ?> (<?= htmlspecialchars($emp['employee_id']) ?> - <?= htmlspecialchars($emp['department']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Project & Flat Unit Select -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-dark fw-bold small">Select Project & Flat Unit <span class="text-danger">*</span></label>
                            <select class="form-select border-primary-subtle" id="projectFlatSelect" name="flat_id" required>
                                <option value="">-- Choose Project Sale --</option>
                                <?php foreach ($availableProjectsFlats as $pf): ?>
                                    <option value="<?= $pf['flat_id'] ?>" data-price="<?= $pf['flat_price'] ?>" data-landshare="<?= $pf['land_share_value'] ?>">
                                        <?= htmlspecialchars($pf['project_name']) ?> - <?= htmlspecialchars($pf['flat_name']) ?> (Price: ৳<?= number_format($pf['flat_price']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Flat Total Price -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted small fw-semibold">Flat Total Price (`flats.price_value`)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">৳</span>
                                <input type="text" id="extSaleAmount" class="form-control bg-light fw-bold" value="0.00" readonly>
                            </div>
                        </div>

                        <!-- Land Share Value (Auto-populated) -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted small fw-semibold">Land Share Value (`flats.land_share_value`)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">৳</span>
                                <input type="text" id="extLandShareAmount" class="form-control bg-light fw-bold text-primary" value="0.00" readonly>
                            </div>
                        </div>

                        <!-- Custom Incentive % -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-dark fw-bold small">Custom Incentive %</label>
                            <div class="input-group">
                                <input type="number" step="0.1" id="extIncentivePercent" name="incentive_percentage" class="form-control" placeholder="e.g. 0.5" value="0.5">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>

                        <!-- Calculated Incentive Amount -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-dark fw-bold small">Calculated / Fixed Amount</label>
                            <div class="input-group">
                                <span class="input-group-text bg-success-subtle text-success font-bold">৳</span>
                                <input type="number" step="0.01" id="extIncentiveAmount" name="custom_amount" class="form-control bg-success-subtle text-success fw-bold fs-6" value="0">
                            </div>
                        </div>

                        <!-- Reason / Remarks -->
                        <div class="col-12 mb-3">
                            <label class="form-label text-dark fw-bold small">Reason & Assignment Remarks <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="remarks" rows="3" placeholder="Provide justification or project referral details for assigning this incentive..." required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-outline-secondary rounded-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-indigo rounded-2 px-4 text-white" style="background-color: #4f46e5;" id="btnSubmitExternal">
                        <i class="fa-solid fa-check me-1"></i> Create Assignment Claim
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Disburse Payment (Accounts) -->
<div id="disbursePaymentModal" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="disbursePaymentForm">
                <input type="hidden" id="modalDisburseClaimId" name="claim_id" value="">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold" id="disbursePaymentModalLabel">
                        <i class="fa-solid fa-money-bill-transfer me-2"></i>Record Incentive Payment Disbursement
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Recipient Employee</label>
                        <input type="text" id="modalDisburseRecipient" class="form-control bg-light fw-bold" value="" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Claim Code</label>
                        <input type="text" id="modalDisburseClaim" class="form-control bg-light" value="" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <?= renderCOADropdown($coaList ?? [], null, 'account_id', false, 'salesincentive') ?>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-dark fw-bold small">Payment Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">৳</span>
                                <input type="number" step="0.01" id="modalDisburseAmount" name="payment_amount" class="form-control fw-bold text-success" value="" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-dark fw-bold small">Payment Method <span class="text-danger">*</span></label>
                            <select class="form-select" name="payment_method" id="modalDisbursePaymentMethod" onchange="window.handleDisburseMethodChange()" required>
                                <option value="Bank Transfer" selected>Bank Transfer</option>
                                <option value="Cheque">Cheque</option>
                                <option value="Cash">Cash</option>
                                <option value="Mobile Banking">Mobile Banking</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3" id="rowDisburseBankDetails">
                        <!-- Company Bank (Paying From) -->
                        <div class="col-md-6" id="colDisburseCompanyBank">
                            <label class="form-label text-dark fw-bold small">
                                <i class="fa-solid fa-building-columns me-1 text-primary"></i> Company Bank (Paying From) <span class="text-danger" id="reqStarCompanyBank">*</span>
                            </label>
                            <select name="company_bank_id" id="modalDisburseCompanyBank" class="form-select">
                                <option value="">-- Select Company Bank Account --</option>
                                <?php foreach (($companyBanks ?? []) as $cb): ?>
                                    <option value="<?= $cb['id'] ?>" data-bank-name="<?= htmlspecialchars($cb['bank_name']) ?>" <?= !empty($cb['is_default']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cb['bank_name'] . (!empty($cb['account_name']) ? ' - ' . $cb['account_name'] : '') . (!empty($cb['account_number']) ? ' (' . $cb['account_number'] . ')' : '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- To Bank (Recipient Bank) -->
                        <div class="col-md-6" id="colDisburseToBank">
                            <label class="form-label text-dark fw-bold small" id="lblDisburseToBank">
                                <i class="fa-solid fa-money-check me-1 text-success"></i> To Bank (Recipient Bank)
                            </label>
                            <input type="text" name="to_bank_name" id="modalDisburseToBank" class="form-control" placeholder="e.g. EBL / City Bank Ltd / Brac Bank" list="commonBankList">
                            <datalist id="commonBankList">
                                <option value="EBL">
                                <option value="BRAC Bank PLC">
                                <option value="City Bank Ltd">
                                <option value="Dutch-Bangla Bank PLC">
                                <option value="Eastern Bank PLC">
                                <option value="Islami Bank Bangladesh">
                                <option value="Pubali Bank">
                                <option value="Mutual Trust Bank PLC">
                                <option value="Standard Chartered">
                            </datalist>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-dark fw-bold small">Transaction / Cheque Ref</label>
                            <input type="text" name="transaction_ref" id="modalDisburseTxnRef" class="form-control" placeholder="e.g. TXN-998812 / Cheque No">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark fw-bold small">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" id="modalDisbursePaymentDate" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark fw-bold small">Payment Notes</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Internal accounts notes for disbursement..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-outline-secondary rounded-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-2 px-4" id="btnConfirmDisburse">
                        <i class="fa-solid fa-circle-check me-1"></i> Confirm Disbursement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Executive Real-Time Calculation Breakdown -->
<div class="modal fade" id="viewExecutiveCalculationModal" tabindex="-1" aria-labelledby="viewExecutiveCalculationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-white text-dark d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="fa-solid fa-calculator text-primary fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0" id="viewExecutiveCalculationModalLabel">Executive Incentive Calculation Breakdown</h5>
                        <div class="extra-small text-white-50" id="modalCalcSubtitle">Real-time target performance & modality audit</div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="modalCalcBody">
                <!-- Dynamically Populated by JS -->
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary rounded-2 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Claim / Payout Sale Details Drilldown -->
<div class="modal fade" id="claimSaleDetailsModal" tabindex="-1" aria-labelledby="claimSaleDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-white text-dark d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="fa-solid fa-file-invoice text-success fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0" id="claimSaleDetailsModalLabel">Sale & Incentive Detailed Breakdown</h5>
                        <div class="extra-small text-white-50" id="modalSaleDetailsSubtitle">Booking, Project & Payment Verification Audit</div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="modalSaleDetailsBody">
                <!-- Dynamically Populated by JS -->
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary rounded-2 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Real-time calculation dataset for instantaneous popup inspection
window.realtimeExecBreakdowns = <?= json_encode($realTimeStats ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatCurrency(val) {
    const num = parseFloat(val);
    if (isNaN(num)) return '0.00';
    return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function showModalInstance(modalId) {
    const modalEl = document.getElementById(modalId);
    if (!modalEl) return;
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    } else if (typeof window.jQuery !== 'undefined') {
        window.jQuery('#' + modalId).modal('show');
    }
}

window.openExecutiveCalculationModal = function (userId) {
    if (!window.realtimeExecBreakdowns || !window.realtimeExecBreakdowns.length) {
        alert('No calculation data available.');
        return;
    }

    const item = window.realtimeExecBreakdowns.find(function (r) {
        return r && r.user && parseInt(r.user.id, 10) === parseInt(userId, 10);
    });

    if (!item) {
        alert('Calculation details for this member could not be loaded.');
        return;
    }

    const u = item.user || {};
    const ind = item.individual || {};
    const cSum = item.claims_summary || {};
    const team = item.team || {};

    const subtitleEl = document.getElementById('modalCalcSubtitle');
    if (subtitleEl) {
        subtitleEl.innerHTML = `${escapeHtml(u.full_name || 'Member')} (${escapeHtml(u.employee_id || '')}) &bull; ${escapeHtml(u.department || 'Sales')} &bull; Cycle: <?= htmlspecialchars($cycleMonthDisplay ?? ($month ?? '')) ?>`;
    }

    const targetAmt = parseFloat(ind.target_amount || 0);
    const achievedAmt = parseFloat(ind.total_achieved_land_share || 0);
    const minRequired = parseFloat(ind.min_eligible_sales || 2500000);
    const earnedAmt = parseFloat(ind.total_earned_incentive || 0);
    const onHoldAmt = parseFloat(ind.total_on_hold_incentive || 0);
    const pct = parseFloat(ind.achievement_pct || 0);
    const isEligible = !!ind.is_eligible;

    const assignedProjects = (ind.target_project_names && ind.target_project_names.length)
        ? ind.target_project_names.join(', ')
        : 'All Assigned Company Projects';

    let html = `
        <!-- Header Info Card -->
        <div class="card border-0 bg-light rounded-3 p-3 mb-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-lg bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold fs-4" style="width: 50px; height: 50px;">
                        ${escapeHtml((u.full_name || 'U').charAt(0))}
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark mb-0">${escapeHtml(u.full_name || '')}</h5>
                        <div class="text-muted extra-small">
                            <span class="badge bg-secondary-subtle text-secondary me-1">${escapeHtml(u.employee_id || '')}</span>
                            ${escapeHtml(u.designation || 'Sales Executive')} &bull; <i class="fa-solid fa-users me-1 text-primary"></i>${escapeHtml(u.team_name || 'Direct Sales')}
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    <span class="badge ${isEligible ? 'bg-success' : 'bg-warning text-dark'} px-3 py-2 fs-6">
                        <i class="fa-solid ${isEligible ? 'fa-circle-check' : 'fa-clock'} me-1"></i>
                        ${isEligible ? 'Target Qualified (≥ 25 Lakh)' : 'Pending Minimum Target (< 25 Lakh)'}
                    </span>
                    <div class="extra-small text-muted mt-1">Assigned Projects: <strong class="text-dark">${escapeHtml(assignedProjects)}</strong></div>
                </div>
            </div>
        </div>

        <!-- 4 KPI Summary Cards -->
        <div class="row  mb-4">
            <div class="col-md-3 col-6">
                <div class="card border border-primary-subtle shadow-none rounded-3 p-3 h-100 bg-white">
                    <div class="extra-small text-muted text-uppercase fw-bold mb-1">Target (Land Share)</div>
                    <h5 class="fw-bold text-dark mb-1">৳${formatCurrency(targetAmt)}</h5>
                    <div class="extra-small text-muted">
                        ${ind.is_inherited ? '<span class="badge bg-info-subtle text-info">Inherited Target</span>' : '<span class="badge bg-primary-subtle text-primary">Current Cycle Target</span>'}
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card border border-success-subtle shadow-none rounded-3 p-3 h-100 bg-white">
                    <div class="extra-small text-muted text-uppercase fw-bold mb-1">Achieved (Land Share)</div>
                    <h5 class="fw-bold text-success mb-1">৳${formatCurrency(achievedAmt)}</h5>
                    <div class="extra-small text-muted">
                        <strong>${pct.toFixed(1)}%</strong> of target fulfilled
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card border border-info-subtle shadow-none rounded-3 p-3 h-100 bg-white">
                    <div class="extra-small text-muted text-uppercase fw-bold mb-1">Minimum Qualification</div>
                    <h5 class="fw-bold text-info mb-1">৳${formatCurrency(minRequired)}</h5>
                    <div class="extra-small text-muted">
                        ${achievedAmt >= minRequired ? '<span class="text-success fw-bold"><i class="fa-solid fa-check me-1"></i>Requirement Met</span>' : '<span class="text-danger fw-bold"><i class="fa-solid fa-times me-1"></i>Short by ৳' + formatCurrency(minRequired - achievedAmt) + '</span>'}
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card border border-success shadow-none rounded-3 p-3 h-100 bg-success-subtle">
                    <div class="extra-small text-success text-uppercase fw-bold mb-1">Total Earned Incentive</div>
                    <h4 class="fw-bold text-success mb-1">৳${formatCurrency(earnedAmt)}</h4>
                    ${onHoldAmt > 0 ? '<div class="extra-small text-warning fw-semibold">+ ৳' + formatCurrency(onHoldAmt) + ' on hold</div>' : '<div class="extra-small text-success"><i class="fa-solid fa-check-double me-1"></i>Ready for claim / paid</div>'}
                </div>
            </div>
        </div>

        <!-- Section 1: Direct Bookings Table -->
        <div class="card border shadow-none rounded-3 mb-4">
            <div class="card-header bg-light py-2 px-3 d-flex align-items-center justify-content-between">
                <span class="fw-bold text-dark small">
                    <i class="fa-solid fa-list-check me-1 text-primary"></i> 1. Direct Sales Bookings in Cycle (${(ind.detailed_bookings || []).length})
                </span>
                <span class="text-muted extra-small">Cycle Date: ${escapeHtml(ind.cycle_start || '')} to ${escapeHtml(ind.cycle_end || '')}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="bg-light text-muted extra-small text-uppercase">
                        <tr>
                            <th class="ps-3">Booking # & Date</th>
                            <th>Project & Flat</th>
                            <th class="text-end">Agreement Price</th>
                            <th class="text-end">Land Share Value</th>
                            <th>Modality Rule</th>
                            <th class="text-center">Rate %</th>
                            <th class="text-end">Incentive</th>
                            <th class="pe-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>`;

    if (ind.detailed_bookings && ind.detailed_bookings.length > 0) {
        ind.detailed_bookings.forEach(function (b) {
            const bLand = parseFloat(b.land_share_value || 0);
            const bPrice = parseFloat(b.flat_price || 0);
            const bRate = parseFloat(b.applicable_rate || 0);
            const bCalc = parseFloat(b.calculated_incentive || 0);
            const bEarned = parseFloat(b.seller_incentive || 0);
            const bHold = b.is_on_hold == 1;

            html += `
                <tr>
                    <td class="ps-3">
                        <strong class="text-dark">#${escapeHtml(b.booking_id)}</strong>
                        <div class="extra-small text-muted">${escapeHtml(b.booking_date || '')}</div>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark">${escapeHtml(b.project_name || 'Project')}</div>
                        <span class="text-primary extra-small">${escapeHtml(b.flat_name || '')}${b.floor_no ? ' (Floor ' + escapeHtml(b.floor_no) + ')' : ''}</span>
                    </td>
                    <td class="text-end fw-semibold text-dark">৳${formatCurrency(bPrice)}</td>
                    <td class="text-end fw-bold text-primary">৳${formatCurrency(bLand)}</td>
                    <td>
                        <span class="badge bg-secondary-subtle text-secondary extra-small">${escapeHtml(b.rate_type || 'Standard')}</span>
                        ${b.is_assigned ? '<span class="badge bg-info-subtle text-info extra-small ms-1">Assigned</span>' : '<span class="badge bg-warning-subtle text-warning extra-small ms-1">Outside Assigned</span>'}
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border fw-bold">${bRate.toFixed(2)}%</span>
                    </td>
                    <td class="text-end">
                        <strong class="${bHold ? 'text-warning' : 'text-success'} fs-6">৳${formatCurrency(bEarned || bCalc)}</strong>
                        <div class="extra-small text-muted" style="font-size: 0.7rem;">(৳${formatCurrency(bLand)} &times; ${bRate.toFixed(2)}%)</div>
                    </td>
                    <td class="pe-3 text-center">
                        ${bHold 
                            ? '<span class="badge bg-warning-subtle text-warning border border-warning-subtle extra-small">On Hold (70%)</span>' 
                            : '<span class="badge bg-success-subtle text-success border border-success-subtle extra-small"><i class="fa-solid fa-check me-1"></i>Earned</span>'}
                        <div class="extra-small text-muted mt-1" style="font-size: 0.65rem;">${escapeHtml(b.status_note || '')}</div>
                    </td>
                </tr>`;
        });
    } else {
        html += `
            <tr>
                <td colspan="8" class="text-center text-muted py-3">No direct sales bookings recorded for this executive in this cycle.</td>
            </tr>`;
    }

    html += `
                    </tbody>
                </table>
            </div>
        </div>`;

    // Section 2: 30% Designated Credits from outside sales (if any)
    if (ind.designated_credits && ind.designated_credits.length > 0) {
        html += `
            <div class="card border border-info-subtle shadow-none rounded-3 mb-4">
                <div class="card-header bg-info-subtle py-2 px-3 d-flex align-items-center justify-content-between">
                    <span class="fw-bold text-dark small">
                        <i class="fa-solid fa-share-nodes me-1 text-info"></i> 2. Passive 30% Designated Credits (${ind.designated_credits.length})
                    </span>
                    <span class="text-info extra-small fw-semibold">Earned from assigned project flats sold by other agents</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="bg-light text-muted extra-small text-uppercase">
                            <tr>
                                <th class="ps-3">Booking #</th>
                                <th>Sold By</th>
                                <th>Project & Flat</th>
                                <th class="text-end">Land Share Value</th>
                                <th class="text-center">Rate Calculation</th>
                                <th class="pe-3 text-end">Passive 30% Credit</th>
                            </tr>
                        </thead>
                        <tbody>`;
        ind.designated_credits.forEach(function (dc) {
            const dcAmt = parseFloat(dc.credit_amount || 0);
            const dcLand = parseFloat(dc.land_share_value || 0);
            html += `
                <tr>
                    <td class="ps-3 fw-bold text-dark">#${escapeHtml(dc.booking_id)}</td>
                    <td>
                        <span class="fw-semibold text-dark">${escapeHtml(dc.seller_name || 'Agent')}</span>
                    </td>
                    <td>
                        <span class="fw-semibold text-dark">${escapeHtml(dc.project_name || '')}</span> - 
                        <span class="text-primary">${escapeHtml(dc.flat_name || '')}</span>
                    </td>
                    <td class="text-end fw-bold text-primary">৳${formatCurrency(dcLand)}</td>
                    <td class="text-center text-muted extra-small">
                        Land Share &times; Base Rate &times; 30%
                    </td>
                    <td class="pe-3 text-end fw-bold text-success fs-6">৳${formatCurrency(dcAmt)}</td>
                </tr>`;
        });
        html += `
                        </tbody>
                    </table>
                </div>
            </div>`;
    }

    // Section 3: Team Leader Overrides (if applicable)
    if (team && team.eligible && team.member_breakdown && team.member_breakdown.length > 0) {
        const tlEarned = parseFloat(team.incentive_earned || 0);
        html += `
            <div class="card border border-warning-subtle shadow-none rounded-3 mb-4">
                <div class="card-header bg-warning-subtle py-2 px-3 d-flex align-items-center justify-content-between">
                    <span class="fw-bold text-dark small">
                        <i class="fa-solid fa-users me-1 text-warning"></i> 3. Team Leader 0.05% Overrides (${escapeHtml(team.team_name || 'Team')})
                    </span>
                    <span class="text-dark extra-small fw-semibold">Qualified Members: ${team.qualified_members_count || 0} / ${team.total_members_count || 0} &bull; Earned: <strong>৳${formatCurrency(tlEarned)}</strong></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="bg-light text-muted extra-small text-uppercase">
                            <tr>
                                <th class="ps-3">Team Member</th>
                                <th>Designation</th>
                                <th class="text-end">Achieved Land Share</th>
                                <th class="text-center">Qualification (≥25L)</th>
                                <th class="pe-3 text-end">TL 0.05% Gain</th>
                            </tr>
                        </thead>
                        <tbody>`;
        team.member_breakdown.forEach(function (mb) {
            if (mb.is_self) return; // Skip self in member breakdown
            const mbAch = parseFloat(mb.achieved_land_share || 0);
            const mbGain = parseFloat(mb.tl_gain || 0);
            html += `
                <tr>
                    <td class="ps-3 fw-semibold text-dark">${escapeHtml(mb.full_name)} <span class="badge bg-light text-muted border extra-small">${escapeHtml(mb.employee_id || '')}</span></td>
                    <td class="extra-small text-muted">${escapeHtml(mb.designation || '')}</td>
                    <td class="text-end fw-bold text-dark">৳${formatCurrency(mbAch)}</td>
                    <td class="text-center">
                        ${mb.is_qualified 
                            ? '<span class="badge bg-success-subtle text-success extra-small"><i class="fa-solid fa-check me-1"></i>Qualified</span>' 
                            : '<span class="badge bg-secondary-subtle text-muted extra-small">Not Qualified (<25L)</span>'}
                    </td>
                    <td class="pe-3 text-end fw-bold ${mbGain > 0 ? 'text-success' : 'text-muted'}">৳${formatCurrency(mbGain)}</td>
                </tr>`;
        });
        html += `
                        </tbody>
                    </table>
                </div>
            </div>`;
    }

    // Section 4: Mathematical Formula Summary
    let directSum = 0;
    (ind.detailed_bookings || []).forEach(function (b) {
        if (!b.is_on_hold) directSum += parseFloat(b.seller_incentive || b.calculated_incentive || 0);
    });
    let passiveSum = 0;
    (ind.designated_credits || []).forEach(function (dc) {
        passiveSum += parseFloat(dc.credit_amount || 0);
    });
    let tlSum = (team && team.eligible) ? parseFloat(team.incentive_earned || 0) : 0;

    html += `
        <div class="card bg-light border-0 rounded-3 p-3">
            <h6 class="fw-bold text-dark mb-2">
                <i class="fa-solid fa-calculator text-primary me-2"></i>Incentive Calculation Formula & Audit
            </h6>
            <div class="p-3 bg-white rounded-2 border">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small">1. Direct Sales Bookings Incentive:</span>
                    <strong class="text-dark small">৳${formatCurrency(directSum)}</strong>
                </div>
                ${passiveSum > 0 ? `
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small">2. 30% Designated Cross-Project Credits:</span>
                    <strong class="text-info small">+ ৳${formatCurrency(passiveSum)}</strong>
                </div>` : ''}
                ${tlSum > 0 ? `
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small">3. Team Leader 0.05% Overrides:</span>
                    <strong class="text-warning small">+ ৳${formatCurrency(tlSum)}</strong>
                </div>` : ''}
                <hr class="my-2">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-dark">Total Real-Time Earned Incentive:</span>
                    <h5 class="fw-bold text-success mb-0">৳${formatCurrency(earnedAmt)}</h5>
                </div>
            </div>
        </div>`;

    const bodyEl = document.getElementById('modalCalcBody');
    if (bodyEl) {
        bodyEl.innerHTML = html;
    }

    showModalInstance('viewExecutiveCalculationModal');
};

window.renderSaleDetailsModal = function (data, type) {
    const isPayout = (type === 'payout');
    const bodyEl = document.getElementById('modalSaleDetailsBody');
    const subtitleEl = document.getElementById('modalSaleDetailsSubtitle');
    if (!bodyEl) return;

    const refCode = isPayout ? (data.disbursement_no || 'PAYOUT') : (data.claim_code || 'CLAIM');
    const cycleMonth = data.sale_cycle_display || data.cycle_month || 'Active Cycle';

    if (subtitleEl) {
        subtitleEl.innerHTML = `${escapeHtml(refCode)} &bull; Sales Cycle: <strong>${escapeHtml(cycleMonth)}</strong> &bull; ${isPayout ? 'Disbursed Payout Audit' : 'Sales Claim Audit'}`;
    }

    const saleAmount = parseFloat(data.sale_amount || 0);
    const landShare = parseFloat(data.land_share_value || 0);
    const incPct = parseFloat(data.incentive_percentage || 0);
    const incAmount = parseFloat(isPayout ? (data.payment_amount || data.incentive_amount || 0) : (data.incentive_amount || 0));

    let html = `
        <!-- Main Ref & Beneficiary Card -->
        <div class="card border-0 bg-light rounded-3 p-3 mb-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <span class="badge ${isPayout ? 'bg-success' : 'bg-primary'} px-3 py-1 mb-1">
                        <i class="fa-solid ${isPayout ? 'fa-receipt' : 'fa-certificate'} me-1"></i>
                        ${escapeHtml(refCode)}
                    </span>
                    <h5 class="fw-bold text-dark mb-0">${escapeHtml(data.recipient_name || data.user_name || 'Sales Executive')}</h5>
                    <div class="text-muted extra-small mt-1">
                        <span class="badge bg-secondary-subtle text-secondary me-1">${escapeHtml(data.recipient_code || data.employee_id || '')}</span>
                        ${escapeHtml(data.department || 'Sales Dept')}
                    </div>
                </div>
                <div class="text-end">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fs-6">
                        <i class="fa-regular fa-calendar me-1"></i>Cycle: ${escapeHtml(cycleMonth)}
                    </span>
                    <div class="extra-small text-muted mt-1">
                        ${data.created_at ? 'Created: ' + escapeHtml(data.created_at) : (data.payment_date ? 'Paid: ' + escapeHtml(data.payment_date) : '')}
                    </div>
                </div>
            </div>
        </div>

        <!-- Project & Booking Details Card -->
        <div class="card border shadow-none rounded-3 mb-4">
            <div class="card-header bg-light py-2 px-3 fw-bold small text-dark">
                <i class="fa-solid fa-building me-1 text-primary"></i> Project & Unit Information
            </div>
            <div class="card-body p-3">
                <div class="row ">
                    <div class="col-md-6 col-12">
                        <div class="extra-small text-muted">Project Name</div>
                        <div class="fw-bold text-dark fs-6">${escapeHtml(data.project_name || 'N/A')}</div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="extra-small text-muted">Flat / Unit</div>
                        <div class="fw-bold text-primary">${escapeHtml(data.flat_name || 'N/A')}</div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="extra-small text-muted">Floor Number</div>
                        <div class="fw-bold text-dark">${data.floor_no ? 'Floor ' + escapeHtml(data.floor_no) : 'N/A'}</div>
                    </div>
                    <div class="col-md-6 col-12">
                        <div class="extra-small text-muted">Booking Reference & Date</div>
                        <div class="fw-bold text-dark">
                            ${data.booking_id || data.booking_pk ? '#' + escapeHtml(data.booking_id || data.booking_pk) : 'N/A'}
                            ${data.booking_date ? ' &bull; ' + escapeHtml(data.booking_date) : ''}
                        </div>
                    </div>
                    <div class="col-md-6 col-12">
                        <div class="extra-small text-muted">Client Information</div>
                        <div class="fw-semibold text-dark">
                            ${escapeHtml(data.client_name || 'Client on file')}
                            ${data.client_phone ? ' (' + escapeHtml(data.client_phone) + ')' : ''}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial & Incentive Calculation Card -->
        <div class="card border shadow-none rounded-3 mb-4">
            <div class="card-header bg-light py-2 px-3 fw-bold small text-dark">
                <i class="fa-solid fa-calculator me-1 text-success"></i> Financial & Incentive Breakdown
            </div>
            <div class="card-body p-3">
                <div class="row ">
                    <div class="col-md-4 col-12">
                        <div class="p-3 bg-light rounded-2 text-center">
                            <div class="extra-small text-muted text-uppercase fw-semibold">Agreement Flat Price</div>
                            <div class="fw-bold text-dark fs-6 mt-1">৳${formatCurrency(saleAmount)}</div>
                        </div>
                    </div>
                    <div class="col-md-4 col-12">
                        <div class="p-3 bg-primary-subtle rounded-2 text-center">
                            <div class="extra-small text-primary text-uppercase fw-semibold">Land Share Value</div>
                            <div class="fw-bold text-primary fs-6 mt-1">৳${formatCurrency(landShare)}</div>
                        </div>
                    </div>
                    <div class="col-md-4 col-12">
                        <div class="p-3 bg-success-subtle rounded-2 text-center">
                            <div class="extra-small text-success text-uppercase fw-semibold">Incentive (${incPct.toFixed(2)}%)</div>
                            <div class="fw-bold text-success fs-5 mt-1">৳${formatCurrency(incAmount)}</div>
                        </div>
                    </div>
                </div>

                <div class="mt-3 p-3 bg-light rounded-2 border">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <span class="badge bg-secondary-subtle text-secondary me-1">Modality Split</span>
                            <strong class="text-dark small">${escapeHtml(data.split_type || 'Regular Direct Sale')}</strong>
                            ${data.original_seller_name ? `
                                <div class="extra-small text-muted mt-1">
                                    <i class="fa-solid fa-user-tag me-1 text-primary"></i>Original Seller: <strong>${escapeHtml(data.original_seller_name)}</strong> [${escapeHtml(data.original_seller_code || '')}]
                                </div>
                            ` : ''}
                        </div>
                        <div class="text-end">
                            <div class="extra-small text-muted">Applied Formula:</div>
                            <div class="small fw-semibold text-dark">৳${formatCurrency(landShare)} &times; ${incPct.toFixed(2)}% = <strong>৳${formatCurrency(incAmount)}</strong></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;

    if (isPayout) {
        // Payout Disbursement Information
        html += `
            <div class="card border border-success-subtle shadow-none rounded-3 mb-2">
                <div class="card-header bg-success-subtle py-2 px-3 fw-bold small text-success">
                    <i class="fa-solid fa-receipt me-1"></i> Accounts Disbursement Audit
                </div>
                <div class="card-body p-3">
                    <div class="row ">
                        <div class="col-md-4 col-6">
                            <div class="extra-small text-muted">Payment Method</div>
                            <div class="fw-bold text-dark"><span class="badge bg-info-subtle text-info">${escapeHtml(data.payment_method || 'Cash')}</span></div>
                        </div>
                        <div class="col-md-4 col-6">
                            <div class="extra-small text-muted">Txn / Cheque Ref</div>
                            <code class="fw-bold">${escapeHtml(data.transaction_ref || 'N/A')}</code>
                        </div>
                        <div class="col-md-4 col-12">
                            <div class="extra-small text-muted">Payment Date</div>
                            <div class="fw-semibold text-dark">${escapeHtml(data.payment_date || '')}</div>
                        </div>
                        ${(data.company_bank_name || data.bank_name) ? `
                        <div class="col-md-6 col-12">
                            <div class="extra-small text-muted">Company Bank (Paid From)</div>
                            <div class="small fw-semibold text-dark"><i class="fa-solid fa-building-columns me-1 text-primary"></i>${escapeHtml(data.company_bank_name || data.bank_name)}</div>
                        </div>` : ''}
                        ${data.to_bank_name ? `
                        <div class="col-md-6 col-12">
                            <div class="extra-small text-muted">Recipient Bank / Account</div>
                            <div class="small fw-semibold text-success"><i class="fa-solid fa-money-check me-1"></i>${escapeHtml(data.to_bank_name)}</div>
                        </div>` : ''}
                        ${data.notes ? `
                        <div class="col-12">
                            <div class="extra-small text-muted">Accounts Notes</div>
                            <div class="extra-small text-muted bg-light p-2 rounded">${escapeHtml(data.notes)}</div>
                        </div>` : ''}
                    </div>
                </div>
            </div>`;
    }

    bodyEl.innerHTML = html;
    showModalInstance('claimSaleDetailsModal');
};

window.openClaimSaleDetailsModal = function (claim) {
    if (!claim) return;
    window.renderSaleDetailsModal(claim, 'claim');
};

window.openPayoutSaleDetailsModal = function (log) {
    if (!log) return;
    window.renderSaleDetailsModal(log, 'payout');
};
window.handleDisburseMethodChange = function () {
    const methodSelect = document.getElementById('modalDisbursePaymentMethod');
    if (!methodSelect) return;
    const method = methodSelect.value;

    const rowBank = document.getElementById('rowDisburseBankDetails');
    const selectCompanyBank = document.getElementById('modalDisburseCompanyBank');
    const inputToBank = document.getElementById('modalDisburseToBank');
    const lblToBank = document.getElementById('lblDisburseToBank');
    const reqStarCompanyBank = document.getElementById('reqStarCompanyBank');
    const inputTxnRef = document.getElementById('modalDisburseTxnRef');

    if (method === 'Cash') {
        // HIDE Company Bank and To Bank completely for Cash payout
        if (rowBank) rowBank.style.display = 'none';
        if (selectCompanyBank) {
            selectCompanyBank.value = '';
            selectCompanyBank.required = false;
        }
        if (inputToBank) {
            inputToBank.value = '';
            inputToBank.required = false;
        }
        if (inputTxnRef) {
            inputTxnRef.placeholder = 'e.g. Cash Voucher No / Memo (Optional)';
        }
    } else if (method === 'Mobile Banking') {
        if (rowBank) rowBank.style.display = 'flex';
        if (reqStarCompanyBank) reqStarCompanyBank.style.display = 'none';
        if (selectCompanyBank) selectCompanyBank.required = false;
        if (lblToBank) {
            lblToBank.innerHTML = '<i class="fa-solid fa-mobile-screen-button me-1 text-info"></i> To Mobile Wallet / Provider';
        }
        if (inputToBank) {
            inputToBank.placeholder = 'e.g. bKash / Nagad / Rocket (017xxxxxxxx)';
        }
        if (inputTxnRef) {
            inputTxnRef.placeholder = 'e.g. TrxID / Ref #';
        }
    } else {
        // Bank Transfer or Cheque
        if (rowBank) rowBank.style.display = 'flex';
        if (reqStarCompanyBank) reqStarCompanyBank.style.display = 'inline';
        if (selectCompanyBank) selectCompanyBank.required = true;
        if (lblToBank) {
            lblToBank.innerHTML = '<i class="fa-solid fa-money-check me-1 text-success"></i> To Bank (Recipient Bank)';
        }
        if (inputToBank) {
            inputToBank.placeholder = 'e.g. EBL / City Bank Ltd / Brac Bank';
            if (!inputToBank.value && inputToBank.getAttribute('data-default-bank')) {
                inputToBank.value = inputToBank.getAttribute('data-default-bank');
            }
        }
        if (inputTxnRef) {
            inputTxnRef.placeholder = method === 'Cheque' ? 'e.g. Cheque No / Leaf #' : 'e.g. TXN-998812 / Slip #';
        }
    }
};

window.populateDisburseModal = function (btn) {
    if (!btn) return;
    const id         = btn.getAttribute('data-id') || '';
    const recipient  = btn.getAttribute('data-recipient') || '';
    const claim      = btn.getAttribute('data-claim') || '';
    const amount     = btn.getAttribute('data-amount') || '';
    const accountId  = btn.getAttribute('data-account-id') || '';
    const empBank    = btn.getAttribute('data-emp-bank') || '';
    const empAccount = btn.getAttribute('data-emp-account') || '';

    const elId        = document.getElementById('modalDisburseClaimId');
    const elRecipient = document.getElementById('modalDisburseRecipient');
    const elClaim     = document.getElementById('modalDisburseClaim');
    const elAmount    = document.getElementById('modalDisburseAmount');
    const elCoa       = document.getElementById('account_id_salesincentive') || document.querySelector('#disbursePaymentModal select[name="account_id"]');
    const elToBank    = document.getElementById('modalDisburseToBank');
    const elMethod    = document.getElementById('modalDisbursePaymentMethod');

    if (elId) elId.value = id;
    if (elRecipient) elRecipient.value = recipient;
    if (elClaim) elClaim.value = claim;
    if (elAmount) {
        const numAmt = parseFloat(String(amount).replace(/,/g, ''));
        elAmount.value = !isNaN(numAmt) ? numAmt.toFixed(2) : amount;
    }
    if (elToBank) {
        let bankText = empBank;
        if (empAccount) {
            bankText += (bankText ? ' - A/C: ' : 'A/C: ') + empAccount;
        }
        elToBank.value = bankText;
        elToBank.setAttribute('data-default-bank', bankText);
    }
    if (elCoa) {
        if (accountId && parseInt(accountId, 10) > 0) {
            elCoa.value = String(accountId);
        } else {
            elCoa.value = '';
        }
        if (typeof window.jQuery !== 'undefined' && window.jQuery(elCoa).hasClass('select2-hidden-accessible')) {
            window.jQuery(elCoa).val(elCoa.value).trigger('change');
        }
    }

    // Call method change handler to sync bank fields visibility
    window.handleDisburseMethodChange();
};

document.addEventListener('DOMContentLoaded', function () {
    // --- Persistent Active Tab across Page Reloads & Actions ---
    const tabStorageKey = 'activeIncentiveManagementTab';
    const tabButtons = document.querySelectorAll('#incentiveTabs button[data-bs-toggle="tab"]');

    function activateIncentiveTab(targetSelector) {
        if (!targetSelector) return false;
        const targetBtn = document.querySelector(`#incentiveTabs button[data-bs-target="${targetSelector}"]`);
        const targetPane = document.querySelector(targetSelector);
        if (!targetBtn || !targetPane) return false;

        tabButtons.forEach(btn => {
            btn.classList.remove('active');
            btn.setAttribute('aria-selected', 'false');
        });
        document.querySelectorAll('#incentiveTabsContent > .tab-pane').forEach(pane => {
            pane.classList.remove('show', 'active');
        });

        targetBtn.classList.add('active');
        targetBtn.setAttribute('aria-selected', 'true');
        targetPane.classList.add('show', 'active');

        if (typeof bootstrap !== 'undefined' && bootstrap.Tab) {
            try {
                bootstrap.Tab.getOrCreateInstance(targetBtn).show();
            } catch (e) {}
        }
        return true;
    }

    const urlParams = new URLSearchParams(window.location.search);
    let initialTab = window.location.hash || sessionStorage.getItem(tabStorageKey);
    if (!initialTab && urlParams.get('ref') === 'all_approvals') {
        initialTab = '#sales-claims';
    }
    if (initialTab) {
        activateIncentiveTab(initialTab);
    }

    tabButtons.forEach(btn => {
        const saveTabState = function (el) {
            const target = el.getAttribute('data-bs-target');
            if (target) {
                sessionStorage.setItem(tabStorageKey, target);
                if (history.replaceState) {
                    history.replaceState(null, null, window.location.pathname + window.location.search + target);
                }
            }
        };
        btn.addEventListener('click', function () { saveTabState(this); });
        btn.addEventListener('shown.bs.tab', function (e) { saveTabState(e.target); });
    });

    // Project Flat selection logic
    const projectFlatSelect = document.getElementById('projectFlatSelect');
    const extIncentivePercent = document.getElementById('extIncentivePercent');

    function updateCalculatedAmount() {
        if (!projectFlatSelect) return;
        const selectedOption = projectFlatSelect.options[projectFlatSelect.selectedIndex];
        const price = parseFloat(selectedOption.getAttribute('data-price') || 0);
        const landshare = parseFloat(selectedOption.getAttribute('data-landshare') || 0);
        
        document.getElementById('extSaleAmount').value = price.toLocaleString('en-US', {minimumFractionDigits: 2});
        document.getElementById('extLandShareAmount').value = landshare.toLocaleString('en-US', {minimumFractionDigits: 2});
        
        const pct = parseFloat(extIncentivePercent.value || 0);
        const calc = (price * pct) / 100;
        document.getElementById('extIncentiveAmount').value = calc.toFixed(2);
    }

    if (projectFlatSelect) projectFlatSelect.addEventListener('change', updateCalculatedAmount);
    if (extIncentivePercent) extIncentivePercent.addEventListener('input', updateCalculatedAmount);

    // Global function to populate Disbursement Modal reliably (works before/after jQuery & bypasses .app__slide-wrapper stopPropagation)
    window.populateDisburseModal = function (btn) {
        if (!btn) return;
        const id         = btn.getAttribute('data-id') || '';
        const recipient  = btn.getAttribute('data-recipient') || '';
        const claim      = btn.getAttribute('data-claim') || '';
        const amount     = btn.getAttribute('data-amount') || '';
        const accountId  = btn.getAttribute('data-account-id') || '';
        const empBank    = btn.getAttribute('data-emp-bank') || '';
        const empAccount = btn.getAttribute('data-emp-account') || '';

        const elId        = document.getElementById('modalDisburseClaimId');
        const elRecipient = document.getElementById('modalDisburseRecipient');
        const elClaim     = document.getElementById('modalDisburseClaim');
        const elAmount    = document.getElementById('modalDisburseAmount');
        const elCoa       = document.getElementById('account_id_salesincentive') || document.querySelector('#disbursePaymentModal select[name="account_id"]');
        const elToBank    = document.getElementById('modalDisburseToBank');

        if (elId) elId.value = id;
        if (elRecipient) elRecipient.value = recipient;
        if (elClaim) elClaim.value = claim;
        if (elAmount) {
            const numAmt = parseFloat(String(amount).replace(/,/g, ''));
            elAmount.value = !isNaN(numAmt) ? numAmt.toFixed(2) : amount;
        }
        if (elToBank) {
            let bankText = empBank;
            if (empAccount) {
                bankText += (bankText ? ' - A/C: ' : 'A/C: ') + empAccount;
            }
            elToBank.value = bankText;
            elToBank.setAttribute('data-default-bank', bankText);
        }
        if (elCoa) {
            if (accountId && parseInt(accountId, 10) > 0) {
                elCoa.value = String(accountId);
            } else {
                elCoa.value = '';
            }
            if (typeof window.jQuery !== 'undefined' && window.jQuery(elCoa).hasClass('select2-hidden-accessible')) {
                window.jQuery(elCoa).val(elCoa.value).trigger('change');
            }
        }

        // Call method change handler to sync bank fields visibility
        window.handleDisburseMethodChange();
    };

    // Move modals to body once on load to prevent stacking/backdrop clipping
    ['disbursePaymentModal', 'assignExternalIncentiveModal', 'viewExecutiveCalculationModal', 'claimSaleDetailsModal'].forEach(function (modalId) {
        const mEl = document.getElementById(modalId);
        if (mEl && mEl.parentNode !== document.body) {
            document.body.appendChild(mEl);
        }
    });

    // 1. Direct listener on every Disburse button
    document.querySelectorAll('.btn-disburse-modal').forEach(function (btn) {
        btn.addEventListener('click', function () {
            window.populateDisburseModal(this);
        });
    });

    // 2. Capture-phase listener on document (runs before .app__slide-wrapper stopPropagation in main.js)
    document.addEventListener('click', function (e) {
        const btn = e.target && e.target.closest ? e.target.closest('.btn-disburse-modal') : null;
        if (btn) {
            window.populateDisburseModal(btn);
        }
    }, true);

    // 3. Bootstrap 5 native show.bs.modal listener
    const disburseModalEl = document.getElementById('disbursePaymentModal');
    if (disburseModalEl) {
        disburseModalEl.addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget || document.querySelector('.btn-disburse-modal:focus');
            if (btn && btn.classList && btn.classList.contains('btn-disburse-modal')) {
                window.populateDisburseModal(btn);
            }
        });
    }

    // Helper function for Swal alert
    function showSwalMsg(icon, title, message, reload = false) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: icon,
                title: title,
                text: message,
                timer: reload ? 1300 : undefined,
                showConfirmButton: !reload
            }).then(() => {
                if (reload) location.reload();
            });
        } else {
            if (reload) location.reload();
        }
    }

    // Approve claim buttons
    const approveButtons = document.querySelectorAll('.btn-approve-claim');
    approveButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const claimId = this.getAttribute('data-id');
            const doApprove = () => {
                fetch('<?= $base_url ?>/incentives/approve', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest' 
                    },
                    body: 'claim_id=' + claimId
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        showSwalMsg('success', 'Approved!', data.message, true);
                    } else {
                        showSwalMsg('error', 'Approval Failed', data.message);
                    }
                })
                .catch(err => {
                    showSwalMsg('error', 'Error', 'Error: ' + err);
                });
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Approve Claim',
                    text: 'Are you sure you want to approve this incentive claim?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Approve',
                    confirmButtonColor: '#198754'
                }).then(res => {
                    if (res.isConfirmed) doApprove();
                });
            } else if (confirm('Are you sure you want to approve this incentive claim?')) {
                doApprove();
            }
        });
    });

    // Reject claim buttons
    const rejectButtons = document.querySelectorAll('.btn-reject-claim');
    rejectButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const claimId = this.getAttribute('data-id');
            const doReject = () => {
                fetch('<?= $base_url ?>/incentives/reject', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest' 
                    },
                    body: 'claim_id=' + claimId
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        showSwalMsg('success', 'Rejected', data.message, true);
                    } else {
                        showSwalMsg('error', 'Rejection Failed', data.message);
                    }
                })
                .catch(err => {
                    showSwalMsg('error', 'Error', 'Error: ' + err);
                });
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Reject Claim',
                    text: 'Are you sure you want to reject this claim?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Reject',
                    confirmButtonColor: '#dc3545'
                }).then(res => {
                    if (res.isConfirmed) doReject();
                });
            } else if (confirm('Are you sure you want to reject this claim?')) {
                doReject();
            }
        });
    });

    // Submit External Form
    const externalIncentiveForm = document.getElementById('externalIncentiveForm');
    if(externalIncentiveForm) {
        externalIncentiveForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitExternal');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Creating...';

            const formData = new FormData(this);
            fetch('<?= $base_url ?>/incentives/assign-external', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Create Assignment Claim';
                if(data.success) {
                    showSwalMsg('success', 'Created!', data.message, true);
                } else {
                    showSwalMsg('error', 'Error', data.message);
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Create Assignment Claim';
                showSwalMsg('error', 'Error', 'An error occurred submitting assignment.');
            });
        });
    }

    // Submit Disburse Form
    const disbursePaymentForm = document.getElementById('disbursePaymentForm');
    if(disbursePaymentForm) {
        disbursePaymentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnConfirmDisburse');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Processing...';

            const formData = new FormData(this);
            fetch('<?= $base_url ?>/incentives/disburse', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i> Confirm Disbursement';
                if(data.success) {
                    const modalEl = document.getElementById('disbursePaymentModal');
                    if (modalEl && typeof bootstrap !== 'undefined') {
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();
                    }
                    showSwalMsg('success', 'Disbursed!', data.message, true);
                } else {
                    showSwalMsg('error', 'Disbursement Failed', data.message);
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i> Confirm Disbursement';
                showSwalMsg('error', 'Error', 'An error occurred recording disbursement.');
            });
        });
    }
});
</script>

<?php 
$ahInfo = null;
include __DIR__ . '/../layouts/accounts_head_approval_card.php'; 
?>

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
                    <h4 class="fw-bold mb-1 text-dark">TK<?= number_format($companyMetrics['total_incentive_pool'], 2) ?></h4>
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
                        <h4 class="fw-bold mb-1 text-warning">TK<?= number_format($companyMetrics['pending_claims_amount'], 2) ?></h4>
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
                    <h4 class="fw-bold mb-1 text-info">TK<?= number_format($companyMetrics['total_approved'], 2) ?></h4>
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
                    <h4 class="fw-bold mb-1 text-success">TK<?= number_format($companyMetrics['total_disbursed'], 2) ?></h4>
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

                                            <?php if($ind['target_amount'] > 0): ?>
                                                <div class="small">Target: <strong class="text-dark">TK<?= number_format($ind['target_amount'], 2) ?></strong></div>
                                                <div class="small">Achieved: <strong class="text-success">TK<?= number_format($achievedTotal, 2) ?></strong></div>
                                                <div class="progress mt-1" style="height: 5px;">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= min(100, $ind['achievement_pct']) ?>%;"></div>
                                                </div>
                                                <div class="extra-small text-muted mt-1"><?= number_format($ind['achievement_pct'], 1) ?>% Achieved</div>
                                            <?php else: ?>
                                                <div class="small text-muted">No Explicit Target</div>
                                                <div class="small">Achieved: <strong class="text-success">TK<?= number_format($achievedTotal, 2) ?></strong></div>
                                            <?php endif; ?>

                                            <?php if (in_array((int)($_SESSION['user']['role_id'] ?? 0), [1, 3, 6, 20, 30, 32, 33, 34, 35], true) || !empty($isManagement)): ?>
                                                <div class="mt-1">
                                                    <a href="<?= $GLOBALS['base_url'] ?>/set-sales-target/<?= $u['id'] ?>" class="btn btn-xs btn-outline-primary border py-0 px-2" style="font-size: 11px;" title="Set or Edit Target for <?= htmlspecialchars($u['full_name']) ?>">
                                                        <i class="fa-solid fa-pen-to-square me-1"></i>Set / Edit
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if(!empty($ind['is_eligible'])): ?>
                                                <span class="badge bg-success-subtle text-success fw-semibold mb-1">
                                                    <i class="fa-solid fa-check me-1"></i>Target Filled Up (≥ 25L)
                                                </span>
                                                <div class="small fw-bold text-success">Earned: TK<?= number_format($ind['total_earned_incentive'], 2) ?></div>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle extra-small mb-1">
                                                    <i class="fa-solid fa-clock me-1"></i>Pending 25L Min
                                                </span>
                                                <div class="extra-small text-muted">Earned: TK0.00</div>
                                            <?php endif; ?>
                                            <div class="mt-1">
                                                <button type="button" class="btn btn-xs btn-outline-info rounded-pill py-0 px-2 fw-semibold btn-member-breakdown" 
                                                        data-member='<?= htmlspecialchars(json_encode([
                                                            'user_name' => $u['full_name'],
                                                            'employee_id' => $u['employee_id'],
                                                            'designation' => $u['designation'] ?? 'Sales Executive',
                                                            'team_name' => $u['team_name'] ?? 'N/A',
                                                            'target_amount' => (float)$ind['target_amount'],
                                                            'effective_target' => (float)$ind['effective_target'],
                                                            'achieved_total' => (float)$achievedTotal,
                                                            'assigned_achieved' => (float)$ind['assigned_achieved'],
                                                            'unassigned_achieved' => (float)$ind['unassigned_achieved'],
                                                            'achievement_pct' => (float)$ind['achievement_pct'],
                                                            'is_eligible' => !empty($ind['is_eligible']),
                                                            'total_earned' => (float)$ind['total_earned_incentive'],
                                                            'total_on_hold' => (float)$ind['total_on_hold_incentive'],
                                                            'cycle_month' => $selected_month,
                                                            'detailed_bookings' => $ind['detailed_bookings'] ?? [],
                                                            'designated_credits' => $ind['designated_credits'] ?? []
                                                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>'
                                                        onclick="openMemberBreakdownModal(this)"
                                                        title="View calculation breakdown of target, bookings, rates, and incentive">
                                                    <i class="fa-solid fa-calculator me-1"></i>Breakdown
                                                </button>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if(($cSum['cycle_claims_count'] ?? 0) > 0): ?>
                                                <span class="badge bg-primary text-white rounded-pill px-2 py-1 extra-small">
                                                    <?= (int)$cSum['cycle_claims_count'] ?> Claims
                                                </span>
                                                <div class="small fw-bold text-primary mt-1">TK<?= number_format($cSum['cycle_claimed_amount'] ?? 0, 2) ?></div>
                                            <?php else: ?>
                                                <span class="text-muted extra-small">0 claims</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="small fw-bold text-dark">TK<?= number_format($cSum['total_claimed_amount'] ?? 0, 2) ?></div>
                                            <span class="text-muted extra-small"><?= (int)($cSum['total_claims_count'] ?? 0) ?> all-time</span>
                                        </td>
                                        <td>
                                            <div class="small fw-bold text-success">TK<?= number_format($cSum['total_paid_amount'] ?? 0, 2) ?></div>
                                            <span class="badge bg-success-subtle text-success extra-small">Disbursed</span>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <h6 class="fw-bold text-info mb-0">TK<?= number_format($cSum['wallet_balance'] ?? 0, 2) ?></h6>
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
                                        <tr id="claim-<?= (int)$claim['id'] ?>" class="<?= (!empty($highlight_claim_id) && (int)$claim['id'] === (int)$highlight_claim_id) ? 'table-warning border-start border-4 border-warning shadow-sm' : '' ?>">
                                            <!-- Code & Date -->
                                            <td class="ps-4">
                                                <span class="fw-bold text-dark d-block"><?= htmlspecialchars($claim['claim_code']) ?></span>
                                                <span class="text-muted extra-small d-block"><?= date('d M, Y', strtotime($claim['claimed_at'])) ?></span>
                                                <?php if (!empty($claim['booking_date'])): ?>
                                                    <div class="extra-small text-muted mt-1" title="Original Sale Date">
                                                        <i class="fa-regular fa-calendar-check me-1 text-primary"></i>Sale: <?= date('d M, Y', strtotime($claim['booking_date'])) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (!empty($claim['cycle_month'])): ?>
                                                    <span class="badge bg-light text-secondary border extra-small mt-1" title="Incentive Cycle Month">
                                                        <i class="fa-regular fa-calendar me-1"></i><?= date('M Y', strtotime($claim['cycle_month'] . '-01')) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Employee Info -->
                                            <td>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($claim['user_name']) ?></div>
                                                <span class="badge bg-secondary-subtle text-secondary extra-small"><?= htmlspecialchars($claim['employee_id']) ?></span>
                                                <span class="text-muted extra-small">| <?= htmlspecialchars($claim['department']) ?></span>
                                            </td>

                                            <!-- Project Unit -->
                                            <td>
                                                <span class="fw-semibold text-dark d-block"><?= htmlspecialchars($claim['project_name']) ?></span>
                                                <span class="text-primary extra-small"><?= htmlspecialchars($claim['flat_name']) ?></span>
                                            </td>

                                            <!-- Flat Price & Land Share -->
                                            <td>
                                                <span class="fw-bold text-dark d-block">TK<?= number_format($claim['sale_amount'], 2) ?></span>
                                                <span class="badge bg-primary-subtle text-primary extra-small" title="Land Share Value">
                                                    <i class="fa-solid fa-map-location-dot me-1"></i>Land Share: TK<?= number_format($claim['land_share_value'], 2) ?>
                                                </span>
                                            </td>

                                            <!-- Inc % -->
                                            <td>
                                                <span class="badge bg-info-subtle text-info fw-bold"><?= number_format($claim['incentive_percentage'], 2) ?>%</span>
                                            </td>

                                            <!-- Incentive Amount -->
                                            <td>
                                                <?php 
                                                $isPartiallyApproved = (!empty($claim['approved_amount']) && (float)$claim['approved_amount'] > 0 && (float)$claim['approved_amount'] != (float)$claim['incentive_amount']);
                                                $dispAmt = (!empty($claim['approved_amount']) && (float)$claim['approved_amount'] > 0) ? (float)$claim['approved_amount'] : (float)$claim['incentive_amount'];
                                                ?>
                                                <span class="fw-bold text-success fs-6">TK<?= number_format($dispAmt, 2) ?></span>
                                                <?php if ($isPartiallyApproved): ?>
                                                    <div class="mt-1">
                                                        <span class="badge bg-secondary-subtle text-muted text-decoration-line-through extra-small" title="Original Claimed Amount">
                                                            TK<?= number_format($claim['incentive_amount'], 2) ?>
                                                        </span>
                                                        <span class="badge bg-warning-subtle text-warning extra-small">Partially Approved</span>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ((float)($claim['paid_amount'] ?? 0) > 0): ?>
                                                    <div class="extra-small text-muted mt-1" style="font-size:0.67rem;">
                                                        <span class="text-primary fw-semibold">Paid: TK<?= number_format($claim['paid_amount'], 2) ?></span>
                                                        <?php if ((float)$claim['due_amount'] > 0): ?>
                                                             <br><span class="text-danger fw-semibold">Due: TK<?= number_format($claim['due_amount'], 2) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
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
                                                    $claimShStatus = strtolower($claim['claim_sh_status'] ?? ($claim['sales_head_status'] ?? 'pending'));
                                                    $isShApprovedClaim = ($claimShStatus === 'approved');
                                                    $isOpHeadApprovedClaim = (!empty($ahInfoClaim['is_op_head_approved']) || ($ahInfoClaim['op_head_status'] ?? '') === 'approved');
                                                    $isStage1Done = ($isShApprovedClaim || $isOpHeadApprovedClaim);
                                                    ?>
                                                    <?php if (!$isStage1Done): ?>
                                                        <span class="badge bg-warning text-dark rounded-pill px-2 py-1 extra-small">
                                                            <i class="fa-solid fa-hourglass-half me-1"></i>Pending Sales Head or Operation Head
                                                        </span>
                                                    <?php elseif (!$isAhApprovedClaim): ?>
                                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2 py-1 extra-small fw-semibold">
                                                            <i class="fa-solid fa-lock me-1"></i>Pending Accounts Head
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
                                                    <div class="mt-1 text-start px-2 py-1 rounded bg-danger-subtle border border-danger-subtle d-inline-block" style="font-size: 0.69rem; line-height: 1.3; min-width: 175px;">
                                                        <div class="text-danger fw-semibold">
                                                            <i class="fa-solid fa-user-xmark me-1"></i>By: <span class="text-dark"><?= htmlspecialchars($claim['rejected_by_name'] ?? 'Approver') ?><?= !empty($claim['rejected_by_code']) ? ' [' . htmlspecialchars($claim['rejected_by_code']) . ']' : '' ?></span>
                                                            <?php if (!empty($claim['rejected_at'])): ?>
                                                                <span class="text-muted" style="font-size:0.64rem;">(<?= date('d M, h:i A', strtotime($claim['rejected_at'])) ?>)</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if (!empty($claim['rejection_note'])): ?>
                                                            <div class="text-muted mt-1 fst-italic" style="font-size:0.65rem;">
                                                                Reason: <?= htmlspecialchars($claim['rejection_note']) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Compact Approval History Audit Trail -->
                                                <?php 
                                                $hasShAudit = (($claim['sales_head_status'] ?? '') === 'approved' || ($claim['claim_sh_status'] ?? '') === 'approved') && !empty($claim['sales_head_at']);
                                                $hasMgmtAudit = (($claim['claim_mgmt_status'] ?? '') === 'approved' || ($claim['mgmt_status'] ?? '') === 'approved' || in_array($claim['claim_status'] ?? '', ['approved', 'paid', 'partially_paid'])) && !empty($claim['mgmt_approved_at']);
                                                $hasAhAudit = (($claim['accounts_head_status'] ?? '') === 'approved' || ($claim['claim_ah_status'] ?? '') === 'approved') && !empty($claim['ah_approved_at']);
                                                ?>
                                                <?php if ($hasShAudit || $hasMgmtAudit || $hasAhAudit || (in_array($claim['claim_status'], ['approved', 'partially_paid']) && !$isAhApprovedClaim)): ?>
                                                    <div class="mt-1 text-start px-2 py-1 rounded bg-light border border-light-subtle d-inline-block" style="font-size: 0.69rem; line-height: 1.3; min-width: 175px;">
                                                        <?php if ($hasShAudit): ?>
                                                            <div class="text-nowrap text-success fw-semibold">
                                                                <i class="fa-solid fa-check text-success me-1"></i>SH/Op: <span class="text-dark"><?= htmlspecialchars($claim['sh_approver_name'] ?? 'Sales Team Head') ?><?= !empty($claim['sh_approver_code']) ? ' [' . htmlspecialchars($claim['sh_approver_code']) . ']' : '' ?></span> <?= !empty($claim['sales_head_at']) ? '<span class="text-muted" style="font-size:0.64rem;">(' . date('d M, h:i A', strtotime($claim['sales_head_at'])) . ')</span>' : '' ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if ($hasMgmtAudit): ?>
                                                            <div class="text-nowrap text-success fw-semibold">
                                                                <i class="fa-solid fa-circle-check text-success me-1"></i>Mgmt: <span class="text-dark"><?= htmlspecialchars($claim['mgmt_approver_name'] ?? 'Management') ?></span> <span class="text-muted" style="font-size:0.64rem;">(<?= date('d M, h:i A', strtotime($claim['mgmt_approved_at'])) ?>)</span>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if ($hasAhAudit): ?>
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
                                                    $userSecRoleId = (int)($_SESSION['user']['secondary_role_id'] ?? 0);
                                                    $userRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
                                                    $isSalesHeadUser = in_array(9, [$userRoleId, $userSecRoleId], true) || str_contains($userRoleName, 'sales head') || str_contains($userRoleName, 'sales team head');
                                                    $isOpHeadUserClaim = (in_array($userRoleId, [20, 32], true) || str_contains($userRoleName, 'operation head') || str_contains($userRoleName, 'admin & operation'));
                                                    $isMgmtUserClaim = in_array($userRoleId, [1, 3], true);
                                                    $isStage1ClaimDone = (($claim['sales_head_status'] ?? '') === 'approved' || ($claim['claim_sh_status'] ?? '') === 'approved' || !empty($ahInfoClaim['is_op_head_approved']) || ($ahInfoClaim['op_head_status'] ?? '') === 'approved');
                                                    ?>
                                                    <?php if (!$isStage1ClaimDone): ?>
                                                        <?php if ($isSalesHeadUser || $isOpHeadUserClaim || $isMgmtUserClaim || $isManagement): ?>
                                                            <button class="btn btn-sm btn-success rounded-2 px-2 me-1 btn-approve-claim" 
                                                                data-id="<?= $claim['id'] ?>" 
                                                                data-code="<?= htmlspecialchars($claim['claim_code']) ?>"
                                                                data-recipient="<?= htmlspecialchars($claim['user_name'] . ' (' . $claim['employee_id'] . ')') ?>"
                                                                data-amount="<?= number_format($claim['incentive_amount'], 2, '.', '') ?>"
                                                                data-approved-amount="<?= number_format(!empty($claim['approved_amount']) ? $claim['approved_amount'] : $claim['incentive_amount'], 2, '.', '') ?>"
                                                                title="Approve Claim (Sales Head or Operation Head)">
                                                                <i class="fa-solid fa-check me-1"></i>Approve
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-danger rounded-2 px-2 btn-reject-claim" 
                                                                data-id="<?= $claim['id'] ?>" 
                                                                data-code="<?= htmlspecialchars($claim['claim_code']) ?>"
                                                                data-recipient="<?= htmlspecialchars($claim['user_name']) ?>"
                                                                title="Reject Claim">
                                                                <i class="fa-solid fa-times"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="text-muted extra-small">Waiting for Sales/Op Head</span>
                                                        <?php endif; ?>
                                                    <?php elseif (!$isAhApprovedClaim): ?>
                                                        <?php if ($canApproveAh && !empty($ahInfoClaim['approval_request_id'])): ?>
                                                            <button type="button" class="btn btn-sm btn-primary rounded-2 px-3 shadow-sm" onclick="triggerAhApprove(<?= (int)$ahInfoClaim['approval_request_id'] ?>, 'Sales Incentive Claim: <?= htmlspecialchars(addslashes(($claim['claim_code'] ?? '') . ' (' . ($claim['project_name'] ?? '') . ' - ' . ($claim['flat_name'] ?? '') . ')'), ENT_QUOTES) ?>', '<?= number_format((float)($claim['approved_amount'] > 0 ? $claim['approved_amount'] : $claim['incentive_amount']), 2) ?>')">
                                                                <i class="fa-solid fa-stamp me-1"></i>AH Approve
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="badge bg-light text-muted border py-1 px-2" style="font-size:10px;" title="Stage 1 Approved. Waiting for Accounts Head verification">
                                                                <i class="fa-solid fa-lock me-1 text-warning"></i>Awaiting Accounts Head
                                                            </span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <?php if ($isMgmtUserClaim || $isManagement): ?>
                                                            <button class="btn btn-sm btn-success rounded-2 px-2 me-1 btn-approve-claim" 
                                                                data-id="<?= $claim['id'] ?>" 
                                                                data-stage="management"
                                                                data-code="<?= htmlspecialchars($claim['claim_code']) ?>"
                                                                data-recipient="<?= htmlspecialchars($claim['user_name'] . ' (' . $claim['employee_id'] . ')') ?>"
                                                                data-amount="<?= number_format($claim['incentive_amount'], 2, '.', '') ?>"
                                                                data-approved-amount="<?= number_format(!empty($claim['approved_amount']) ? $claim['approved_amount'] : $claim['incentive_amount'], 2, '.', '') ?>"
                                                                title="Final Management Approval">
                                                                <i class="fa-solid fa-circle-check me-1"></i>Mgmt Approve
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="badge bg-light text-muted border py-1 px-2" style="font-size:10px;">
                                                                <i class="fa-solid fa-clock me-1 text-warning"></i>Awaiting Mgmt Approval
                                                            </span>
                                                        <?php endif; ?>
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
                                                                    data-amount="<?= number_format((float)(($claim['due_amount'] ?? 0) > 0 ? $claim['due_amount'] : (!empty($claim['approved_amount']) ? $claim['approved_amount'] : $claim['incentive_amount'])), 2, '.', '') ?>"
                                                                    data-approved-amount="<?= number_format((float)(!empty($claim['approved_amount']) ? $claim['approved_amount'] : $claim['incentive_amount']), 2, '.', '') ?>"
                                                                    data-paid-amount="<?= number_format((float)($claim['paid_amount'] ?? 0), 2, '.', '') ?>"
                                                                    data-due-amount="<?= number_format((float)(($claim['due_amount'] ?? 0) > 0 ? $claim['due_amount'] : (!empty($claim['approved_amount']) ? $claim['approved_amount'] : $claim['incentive_amount'])), 2, '.', '') ?>"
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
                                                <span class="fw-bold text-dark d-block">TK<?= number_format($ext['sale_amount'], 2) ?></span>
                                                <span class="badge bg-primary-subtle text-primary extra-small" title="Land Share Value">
                                                    <i class="fa-solid fa-map-location-dot me-1"></i>Land Share: TK<?= number_format($ext['land_share_value'], 2) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-success">TK<?= number_format($ext['incentive_amount'], 2) ?></span>
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
                                            <th>Earned</th>
                                            <th>Approved</th>
                                            <th>Paid</th>
                                            <th class="text-end">Wallet Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($employeeWallets)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-3">No employee wallet data available.</td>
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
                                                    <td class="extra-small text-muted fw-semibold">
                                                        TK<?= number_format($wallet['total_earned'], 2) ?>
                                                        <?php if (!empty($wallet['rejected_amt']) && (float)$wallet['rejected_amt'] > 0): ?>
                                                            <div class="text-danger extra-small" style="font-size:0.62rem;" title="Rejected Claims">
                                                                Rej: -TK<?= number_format($wallet['rejected_amt'], 2) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="extra-small fw-semibold text-success">TK<?= number_format($wallet['approved_amt'], 2) ?></td>
                                                    <td class="extra-small text-primary fw-semibold">TK<?= number_format($wallet['paid_amt'], 2) ?></td>
                                                    <td class="text-end">
                                                        <span class="fw-bold text-dark bg-warning-subtle px-2 py-1 rounded extra-small">TK<?= number_format($wallet['wallet_balance'], 2) ?></span>
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
                                                        <span class="text-muted extra-small"><?= date('d M, Y', strtotime($log['payment_date'])) ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="fw-semibold text-dark extra-small"><?= htmlspecialchars($log['recipient_name']) ?></span>
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
                                                        <span class="fw-bold text-success extra-small">TK<?= number_format($log['payment_amount'], 2) ?></span>
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
                                <span class="text-muted extra-small">Tracks override incentives gained by Team Leaders when their team members achieve ≥ TK25 Lakh in land share sales.</span>
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
                                Total TL Overrides: TK<?= number_format($totalTlOverridesSum, 2) ?>
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
                                                    <span class="text-muted small">TK<?= number_format($to['member_target'], 2) ?></span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-dark small">TK<?= number_format($to['member_achieved'], 2) ?></span>
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
                                                        <span class="fw-bold text-success fs-6">+TK<?= number_format($to['tl_gain'], 2) ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted small">TK0.00</span>
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
                                                            <button class="btn btn-sm btn-success rounded-2 px-2 me-1 btn-approve-claim" 
                                                                data-id="<?= $toClaimId ?>" 
                                                                data-code="TL Override Claim #<?= $toClaimId ?>"
                                                                data-recipient="<?= htmlspecialchars($to['tl_name'] . ' (' . $to['tl_code'] . ')') ?>"
                                                                data-amount="<?= number_format((float)($to['tl_gain'] ?? 0), 2, '.', '') ?>"
                                                                data-approved-amount="<?= number_format((float)($to['tl_gain'] ?? 0), 2, '.', '') ?>"
                                                                title="Approve TL Override">
                                                                <i class="fa-solid fa-check me-1"></i>Approve
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-danger rounded-2 px-2 btn-reject-claim" 
                                                                data-id="<?= $toClaimId ?>" 
                                                                data-code="TL Override Claim #<?= $toClaimId ?>"
                                                                data-recipient="<?= htmlspecialchars($to['tl_name']) ?>"
                                                                title="Reject Claim">
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
                                <span class="text-muted extra-small">Sales of flats from unassigned projects. Tracks 70% seller share and 30% designated project holder credit.</span>
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
                                Cross Sales Volume: TK<?= number_format($totalCrossVolume, 2) ?>
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
                                                No unassigned / cross-project sales recorded in this cycle.
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
                                                    <span class="text-dark fw-semibold">TK<?= number_format($cs['land_share_value'], 2) ?></span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-dark d-block"><?= htmlspecialchars($cs['seller_name']) ?></span>
                                                    <code class="extra-small text-muted">[<?= htmlspecialchars($cs['seller_code']) ?>]</code>
                                                    <span class="fw-bold text-success d-block small">70%: TK<?= number_format($cs['seller_share'], 2) ?></span>
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
                                                    <?php 
                                                    $hasHolder = !empty($cs['has_designated_holder']) || (!empty($cs['designated_user_name']) && $cs['designated_user_name'] !== 'Unassigned / Open');
                                                    ?>
                                                    <?php if ($hasHolder): ?>
                                                        <span class="fw-semibold text-dark d-block">
                                                            <i class="fa-solid fa-user-check me-1 text-primary"></i><?= htmlspecialchars($cs['designated_user_name']) ?>
                                                        </span>
                                                        <span class="fw-bold text-indigo small">30% Share: TK<?= number_format($cs['designated_share'], 2) ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger-subtle text-danger border extra-small mb-1">
                                                            <i class="fa-solid fa-circle-exclamation me-1"></i>No Agent Assigned to Project
                                                        </span>
                                                        <div class="extra-small text-muted">Open Project (30% Share: TK<?= number_format($cs['designated_share'], 2) ?>)</div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="pe-4 text-end">
                                                    <span class="fw-bold text-dark fs-6">TK<?= number_format(($cs['seller_share'] + $cs['designated_share']), 2) ?></span>
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
                                        <?= htmlspecialchars($pf['project_name']) ?> - <?= htmlspecialchars($pf['flat_name']) ?> (Price: TK<?= number_format($pf['flat_price']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Flat Total Price -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted small fw-semibold">Flat Total Price (`flats.price_value`)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">TK</span>
                                <input type="text" id="extSaleAmount" class="form-control bg-light fw-bold" value="0.00" readonly>
                            </div>
                        </div>

                        <!-- Land Share Value (Auto-populated) -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted small fw-semibold">Land Share Value (`flats.land_share_value`)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">TK</span>
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
                                <span class="input-group-text bg-success-subtle text-success font-bold">TK</span>
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

                    <!-- Approval & Due Summary Card -->
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="row g-2 text-center">
                            <div class="col-4">
                                <span class="text-muted extra-small d-block text-uppercase fw-semibold">Approved Amt</span>
                                <span class="fw-bold text-success fs-15" id="disburseApprovedDisplay">TK 0.00</span>
                            </div>
                            <div class="col-4 border-start border-end">
                                <span class="text-muted extra-small d-block text-uppercase fw-semibold">Already Paid</span>
                                <span class="fw-bold text-primary fs-15" id="disbursePaidDisplay">TK 0.00</span>
                            </div>
                            <div class="col-4">
                                <span class="text-muted extra-small d-block text-uppercase fw-semibold">Remaining Due</span>
                                <span class="fw-bold text-danger fs-15" id="disburseDueDisplay">TK 0.00</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <?= renderCOADropdown($coaList ?? [], null, 'account_id', false, 'salesincentive') ?>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-dark fw-bold small">Payment Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">TK</span>
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
                <div class="modal-footer bg-light py-2 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-danger rounded-2" id="btnDisburseReject" onclick="window.rejectClaimFromDisburseModal()">
                        <i class="fa-solid fa-times-circle me-1"></i> Reject Claim
                    </button>
                    <div>
                        <button type="button" class="btn btn-outline-secondary rounded-2 me-1" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success rounded-2 px-4" id="btnConfirmDisburse">
                            <i class="fa-solid fa-circle-check me-1"></i> Confirm Disbursement
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Member Incentive Calculation Breakdown Modal -->
<div id="memberBreakdownModal" class="modal custom-modal fade" role="dialog" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-light py-3 border-bottom d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="modal-title fw-bold text-dark mb-0">
                        <i class="fa-solid fa-calculator text-primary me-2"></i>Incentive Calculation Breakdown: <span id="mbb_user_name" class="text-primary"></span> <code class="fs-7 text-muted" id="mbb_emp_id"></code>
                    </h5>
                    <span class="text-muted extra-small" id="mbb_team_designation"></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <!-- Summary Metrics Cards -->
                <div class="row  mb-4">
                    <div class="col-md-3 col-sm-6">
                        <div class="card border-0 shadow-xs rounded-3 p-3 bg-white h-100 border-start border-4 border-primary">
                            <span class="text-muted extra-small fw-semibold d-block">Monthly Target</span>
                            <h4 class="fw-bold mb-0 text-dark" id="mbb_target">TK0.00</h4>
                            <span class="text-muted extra-small" id="mbb_target_sub">Assigned Land Share</span>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="card border-0 shadow-xs rounded-3 p-3 bg-white h-100 border-start border-4 border-success">
                            <span class="text-muted extra-small fw-semibold d-block">Achieved Land Share</span>
                            <h4 class="fw-bold mb-0 text-success" id="mbb_achieved">TK0.00</h4>
                            <div class="progress mt-2" style="height: 5px;">
                                <div class="progress-bar bg-success" id="mbb_progress_bar" role="progressbar" style="width: 0%;"></div>
                            </div>
                            <span class="text-muted extra-small mt-1 d-block" id="mbb_achieved_pct">0% Achieved</span>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="card border-0 shadow-xs rounded-3 p-3 bg-white h-100 border-start border-4 border-warning">
                            <span class="text-muted extra-small fw-semibold d-block">Qualification Status</span>
                            <div class="mt-1" id="mbb_qualification_badge">
                                <span class="badge bg-warning-subtle text-dark border px-2 py-1">Pending 25L</span>
                            </div>
                            <span class="text-muted extra-small mt-1 d-block">Min. TK25 Lakh Required</span>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="card border-0 shadow-xs rounded-3 p-3 bg-white h-100 border-start border-4 border-info">
                            <span class="text-muted extra-small fw-semibold d-block">Calculated Incentive</span>
                            <h4 class="fw-bold mb-0 text-primary" id="mbb_earned">TK0.00</h4>
                            <span class="text-muted extra-small d-block" id="mbb_on_hold_sub">On Hold: TK0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Section: Sales Bookings Breakdown Table -->
                <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="fa-solid fa-list-check text-primary me-2"></i>Sales Bookings in Current Cycle (<span id="mbb_cycle_text"></span>)
                        </h6>
                        <span class="badge bg-light text-muted border" id="mbb_bookings_count">0 Bookings</span>
                    </div>
                    <div class="table-responsive bg-white">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted extra-small text-uppercase">
                                <tr>
                                    <th class="ps-3">Booking Ref & Date</th>
                                    <th>Project & Unit</th>
                                    <th>Land Share Base</th>
                                    <th>Applicable Rate (%)</th>
                                    <th>Incentive Amount</th>
                                    <th>Scope / Policy</th>
                                    <th class="pe-3 text-center">Claim Status</th>
                                </tr>
                            </thead>
                            <tbody id="mbb_bookings_table_body">
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No sales bookings found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Section: 30% Designated Share Credits (if any) -->
                <div class="card border-0 shadow-sm rounded-3 mb-2 overflow-hidden" id="mbb_designated_section" style="display: none;">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="fa-solid fa-arrows-split-up-and-left text-info me-2"></i>30% Designated Credits (From Other Agents Selling in My Assigned Project)
                        </h6>
                        <span class="badge bg-info-subtle text-info border" id="mbb_designated_count">0 Credits</span>
                    </div>
                    <div class="table-responsive bg-white">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted extra-small text-uppercase">
                                <tr>
                                    <th class="ps-3">Booking Ref</th>
                                    <th>Project & Flat</th>
                                    <th>Seller</th>
                                    <th>Land Share</th>
                                    <th>Applicable Rate (30% Share)</th>
                                    <th>30% Share Earned</th>
                                    <th class="pe-3 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody id="mbb_designated_table_body"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white border-top py-2 px-3">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-2 px-3" data-bs-dismiss="modal" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
window.openMemberBreakdownModal = function (btn) {
    if (!btn) return;
    const rawData = btn.getAttribute('data-member');
    if (!rawData) return;
    let data = {};
    try {
        data = JSON.parse(rawData);
    } catch (e) {
        console.error('Invalid member JSON', e);
        return;
    }

    document.getElementById('mbb_user_name').innerText = data.user_name || 'Executive';
    document.getElementById('mbb_emp_id').innerText = data.employee_id ? '[' + data.employee_id + ']' : '';
    document.getElementById('mbb_team_designation').innerText = (data.designation || 'Sales Executive') + (data.team_name ? ' | Team: ' + data.team_name : '');
    document.getElementById('mbb_cycle_text').innerText = data.cycle_month || 'Current Cycle';

    const fmtMoney = (val) => 'TK' + (parseFloat(val) || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    document.getElementById('mbb_target').innerText = fmtMoney(data.effective_target || data.target_amount);
    document.getElementById('mbb_target_sub').innerText = (data.target_amount > 0 ? 'Assigned Target' : 'Min 25L Benchmark');
    document.getElementById('mbb_achieved').innerText = fmtMoney(data.achieved_total);
    const pct = parseFloat(data.achievement_pct) || 0;
    document.getElementById('mbb_achieved_pct').innerText = pct.toFixed(1) + '% Achieved';
    const pBar = document.getElementById('mbb_progress_bar');
    if (pBar) pBar.style.width = Math.min(100, pct) + '%';

    const qBadge = document.getElementById('mbb_qualification_badge');
    if (qBadge) {
        if (data.is_eligible) {
            qBadge.innerHTML = '<span class="badge bg-success text-white px-2 py-1"><i class="fa-solid fa-circle-check me-1"></i>Qualified (≥ 25L)</span>';
        } else {
            const shortFall = Math.max(0, 2500000 - (parseFloat(data.achieved_total) || 0));
            qBadge.innerHTML = '<span class="badge bg-warning text-dark px-2 py-1"><i class="fa-solid fa-clock me-1"></i>Pending 25L Min</span><div class="extra-small text-danger fw-semibold mt-1">Need TK ' + (shortFall).toLocaleString('en-US', {minimumFractionDigits: 2}) + ' to qualify</div>';
        }
    }

    document.getElementById('mbb_earned').innerText = fmtMoney(data.total_earned);
    const onHold = parseFloat(data.total_on_hold) || 0;
    document.getElementById('mbb_on_hold_sub').innerText = onHold > 0 ? 'On Hold (70%): ' + fmtMoney(onHold) + ' (Unlocks at 25L)' : 'No Hold Amount';

    // Populate Bookings Table
    const tbody = document.getElementById('mbb_bookings_table_body');
    const bookings = data.detailed_bookings || [];
    document.getElementById('mbb_bookings_count').innerText = bookings.length + ' Booking' + (bookings.length === 1 ? '' : 's');

    if (bookings.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted"><i class="fa-solid fa-folder-open fs-3 d-block mb-2 opacity-50"></i>No sales bookings made by this executive in this cycle.</td></tr>';
    } else {
        let html = '';
        bookings.forEach((b) => {
            const bRef = 'BK-' + String(b.booking_id || '').padStart(5, '0');
            const bDate = b.booking_date ? new Date(b.booking_date).toLocaleDateString('en-GB', {day: 'numeric', month: 'short', year: 'numeric'}) : 'N/A';
            const ls = fmtMoney(b.land_share_value);
            const rateStr = (parseFloat(b.applicable_rate) || 0).toFixed(2) + '%';
            const baseUnitRateStr = (parseFloat(b.base_unit_rate || b.applicable_rate) || 0).toFixed(2) + '%';
            const earnedAmt = fmtMoney(b.calculated_incentive || b.seller_incentive);
            const isOutside = !b.is_assigned || (b.rate_type && b.rate_type.indexOf('Unassigned') !== -1) || (b.rate_type && b.rate_type.indexOf('Outside') !== -1) || b.project_scope === 'unassigned';
            
            let projectBadge = '';
            if (isOutside) {
                const hasHolder = b.designated_user_name && b.designated_user_name !== 'Unassigned / Open' && b.designated_user_name !== 'Open';
                const holderNotice = hasHolder
                    ? `<div class="extra-small text-muted mt-1" style="font-size:0.75rem;"><i class="fa-solid fa-bullseye text-info me-1"></i>Target Assigned To: <strong class="text-dark">${b.designated_user_name}</strong></div>`
                    : `<div class="extra-small text-danger fw-semibold mt-1" style="font-size:0.75rem;"><i class="fa-solid fa-circle-exclamation text-danger me-1"></i>No Agent Assigned to Project (Open Pool)</div>`;

                projectBadge = `
                    <span class="badge bg-warning-subtle text-dark border extra-small mt-1"><i class="fa-solid fa-arrows-split-up-and-left me-1 text-warning"></i>From Unassigned Project</span>
                    ${holderNotice}
                `;
            } else {
                projectBadge = '<span class="badge bg-success-subtle text-success border extra-small mt-1"><i class="fa-solid fa-circle-check me-1"></i>Assigned Project</span>';
            }

            const scopeBadge = isOutside 
                ? '<span class="badge bg-warning text-dark border extra-small"><i class="fa-solid fa-arrows-split-up-and-left me-1"></i>Unassigned (70% Share)</span>' 
                : '<span class="badge bg-success text-white extra-small"><i class="fa-solid fa-circle-check me-1"></i>Assigned (100% Share)</span>';

            let rateDisplayHtml = '';
            if (isOutside) {
                const baseRateVal = (parseFloat(b.base_unit_rate || 0.80) || 0.80).toFixed(2);
                const sellerRateVal = (parseFloat(b.applicable_rate || 0.56) || 0.56).toFixed(2);
                const desRateVal = (parseFloat(baseRateVal * 0.30) || 0.24).toFixed(2);
                const desAmt = fmtMoney(b.designated_incentive || ((parseFloat(b.land_share_value) || 0) * (parseFloat(baseRateVal) / 100) * 0.30));
                const hasHolder = b.designated_user_name && b.designated_user_name !== 'Unassigned / Open' && b.designated_user_name !== 'Open';

                const targetHolderRow = hasHolder
                    ? `<div class="extra-small text-muted mt-1 pt-1 border-top border-light-subtle" style="font-size:0.74rem;">
                           <span class="text-secondary">Target Holder Earns 30%:</span> 
                           <strong class="text-dark"><i class="fa-solid fa-user-tag text-primary me-1"></i>${b.designated_user_name}</strong>
                           <span class="text-success fw-bold">(${desAmt})</span>
                       </div>`
                    : `<div class="extra-small text-danger fw-semibold mt-1 pt-1 border-top border-light-subtle" style="font-size:0.74rem;">
                           <i class="fa-solid fa-circle-exclamation me-1"></i>No Agent Assigned to Project (Open Pool)
                       </div>`;

                rateDisplayHtml = `
                    <div class="d-flex align-items-center gap-1 mb-1">
                        <span class="badge bg-warning text-dark fw-bold fs-7">${rateStr}</span>
                        <span class="badge bg-warning-subtle text-dark border extra-small">70% Seller Share</span>
                    </div>
                    <div class="extra-small text-dark fw-semibold" style="line-height:1.35; font-size:0.76rem;">
                        ${baseRateVal}% × 70% = <span class="text-primary fw-bold">${sellerRateVal}%</span>
                    </div>
                    <div class="extra-small text-muted" style="line-height:1.35; font-size:0.74rem;">
                        Remaining 30%: ${baseRateVal}% × 30% = <strong class="text-dark">${desRateVal}%</strong>
                    </div>
                    ${targetHolderRow}
                `;
            } else {
                rateDisplayHtml = `
                    <div class="d-flex align-items-center gap-1 mb-1">
                        <span class="badge bg-success-subtle text-success fw-bold fs-7">${rateStr}</span>
                        <span class="text-muted extra-small">(100% Share)</span>
                    </div>
                    <div class="extra-small text-muted">${b.floor_name || b.rate_type || 'General Flat'}</div>
                `;
            }

            let statusBadge = '<span class="badge bg-secondary-subtle text-secondary extra-small">Ineligible</span>';
            if (b.claim_status === 'paid') {
                statusBadge = b.is_received 
                    ? '<span class="badge bg-success text-white extra-small"><i class="fa-solid fa-circle-check me-1"></i>Received</span>'
                    : '<span class="badge bg-success-subtle text-success border extra-small"><i class="fa-solid fa-check me-1"></i>Paid</span>';
            } else if (b.claim_status === 'approved') {
                statusBadge = '<span class="badge bg-info text-white extra-small"><i class="fa-solid fa-check-double me-1"></i>Approved</span>';
            } else if (b.claim_status === 'claimed') {
                statusBadge = '<span class="badge bg-warning text-dark extra-small"><i class="fa-solid fa-clock me-1"></i>Claimed</span>';
            } else if (data.is_eligible) {
                statusBadge = '<span class="badge bg-primary-subtle text-primary border extra-small">Ready to Claim</span>';
            } else {
                statusBadge = '<span class="badge bg-warning-subtle text-dark border extra-small"><i class="fa-solid fa-lock me-1 text-warning"></i>Need 25L</span>';
            }

            let amtDisplayHtml = `<span class="fw-bold text-success fs-6">${earnedAmt}</span>`;
            if (isOutside && !data.is_eligible) {
                amtDisplayHtml += `<div class="extra-small text-warning fw-semibold mt-1"><i class="fa-solid fa-lock me-1"></i>On Hold (Need 25L)</div>`;
            }

            html += `<tr>
                <td class="ps-3">
                    <span class="fw-bold text-dark d-block">${bRef}</span>
                    <span class="text-muted extra-small">${bDate}</span>
                </td>
                <td>
                    <span class="fw-bold text-dark d-block">${b.project_name || 'N/A'}</span>
                    <span class="badge bg-light text-primary border extra-small">${b.flat_name || 'N/A'} (Floor ${b.floor_no || 'N/A'})</span>
                    <div>${projectBadge}</div>
                </td>
                <td><span class="fw-semibold text-dark">${ls}</span></td>
                <td>${rateDisplayHtml}</td>
                <td>${amtDisplayHtml}</td>
                <td>${scopeBadge}</td>
                <td class="pe-3 text-center">${statusBadge}</td>
            </tr>`;
        });
        tbody.innerHTML = html;
    }

    // Populate Designated Section
    const desSec = document.getElementById('mbb_designated_section');
    const desBody = document.getElementById('mbb_designated_table_body');
    const desCredits = data.designated_credits || [];
    if (desCredits.length > 0) {
        desSec.style.display = 'block';
        document.getElementById('mbb_designated_count').innerText = desCredits.length + ' Credit' + (desCredits.length === 1 ? '' : 's');
        let dHtml = '';
        desCredits.forEach(dc => {
            const bRef = 'BK-' + String(dc.booking_id || '').padStart(5, '0');
            const baseRateVal = (parseFloat(dc.base_unit_rate || (dc.is_floor_special ? 0.80 : 0.70) || 0.80)).toFixed(2);
            const desRateVal = (parseFloat(dc.designated_rate || (baseRateVal * 0.30)) || 0.24).toFixed(2);
            const sellerRateVal = (parseFloat(dc.seller_rate || (baseRateVal * 0.70)) || 0.56).toFixed(2);
            const floorLabel = (dc.floor_no == 1 || dc.is_floor_special) ? '1st Floor' : 'General Floor';

            const rateHtml = `
                <div class="d-flex align-items-center gap-1 mb-1">
                    <span class="badge bg-info text-white fw-bold fs-7">${desRateVal}%</span>
                    <span class="badge bg-info-subtle text-info border extra-small">30% Project Share</span>
                </div>
                <div class="extra-small text-dark fw-semibold" style="line-height:1.3; font-size:0.75rem;">
                    ${baseRateVal}% (${floorLabel}) × 30% = <span class="text-info fw-bold">${desRateVal}%</span>
                </div>
                <div class="extra-small text-muted" style="line-height:1.3; font-size:0.72rem;">
                    (Remaining 70% to Seller ${dc.seller_name || ''}: ${sellerRateVal}%)
                </div>
            `;

            dHtml += `<tr>
                <td class="ps-3"><span class="badge bg-light text-dark border">${bRef}</span></td>
                <td>
                    <span class="fw-bold text-dark d-block">${dc.project_name}</span>
                    <span class="badge bg-light text-primary border extra-small">${dc.flat_name}${dc.floor_no ? ' (Floor ' + dc.floor_no + ')' : ''}</span>
                </td>
                <td><i class="fa-solid fa-user-tie text-muted me-1"></i><strong class="text-dark">${dc.seller_name}</strong></td>
                <td><span class="fw-semibold text-dark">${fmtMoney(dc.land_share_value)}</span></td>
                <td>${rateHtml}</td>
                <td><span class="fw-bold text-success fs-6">${fmtMoney(dc.credit_amount)}</span></td>
                <td class="pe-3 text-center">
                    ${data.is_eligible 
                        ? '<span class="badge bg-success-subtle text-success border extra-small"><i class="fa-solid fa-circle-check me-1"></i>Unlocked</span>' 
                        : '<span class="badge bg-warning-subtle text-dark border extra-small"><i class="fa-solid fa-lock me-1"></i>On Hold</span>'}
                </td>
            </tr>`;
        });
        desBody.innerHTML = dHtml;
    } else {
        desSec.style.display = 'none';
    }

    // Open Modal
    const modalEl = document.getElementById('memberBreakdownModal');
    if (modalEl) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
            bsModal.show();
        } else if (typeof $ !== 'undefined') {
            $('#memberBreakdownModal').modal('show');
        }
    }
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
    const approvedAmt= btn.getAttribute('data-approved-amount') || amount;
    const paidAmt    = btn.getAttribute('data-paid-amount') || '0';
    const dueAmt     = btn.getAttribute('data-due-amount') || amount;
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

    const elDispApproved = document.getElementById('disburseApprovedDisplay');
    const elDispPaid     = document.getElementById('disbursePaidDisplay');
    const elDispDue      = document.getElementById('disburseDueDisplay');

    if (elId) elId.value = id;
    if (elRecipient) elRecipient.value = recipient;
    if (elClaim) elClaim.value = claim;

    const numApproved = parseFloat(String(approvedAmt).replace(/,/g, '')) || 0;
    const numPaid = parseFloat(String(paidAmt).replace(/,/g, '')) || 0;
    const numDue = parseFloat(String(dueAmt).replace(/,/g, '')) || 0;

    if (elDispApproved) elDispApproved.innerText = 'TK ' + numApproved.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    if (elDispPaid) elDispPaid.innerText = 'TK ' + numPaid.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    if (elDispDue) elDispDue.innerText = 'TK ' + numDue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    if (elAmount) {
        const numAmt = parseFloat(String(amount).replace(/,/g, ''));
        elAmount.value = !isNaN(numAmt) ? numAmt.toFixed(2) : amount;
        if (numDue > 0) {
            elAmount.max = numDue.toFixed(2);
        }
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

window.rejectClaimFromDisburseModal = function() {
    const claimId = document.getElementById('modalDisburseClaimId') ? document.getElementById('modalDisburseClaimId').value : '';
    if (!claimId) {
        showSwalMsg('error', 'Error', 'No claim selected.');
        return;
    }

    const doReject = (note) => {
        fetch('<?= $base_url ?>/incentives/reject', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest' 
            },
            body: 'claim_id=' + encodeURIComponent(claimId) + '&note=' + encodeURIComponent(note)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Close modal
                const modalEl = document.getElementById('disbursePaymentModal');
                if (modalEl && typeof bootstrap !== 'undefined') {
                    const inst = bootstrap.Modal.getInstance(modalEl);
                    if (inst) inst.hide();
                }
                showSwalMsg('success', 'Claim Rejected', data.message, true);
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
            title: 'Reject Claim at Disbursement',
            text: 'Are you sure Accounts wants to reject this approved incentive claim? Please enter the rejection note/reason:',
            input: 'textarea',
            inputPlaceholder: 'Enter reason for rejection...',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '<i class="fa-solid fa-times me-1"></i> Yes, Reject Claim',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            inputValidator: (val) => {
                if (!val || !val.trim()) {
                    return 'Rejection reason is required!';
                }
            }
        }).then(res => {
            if (res.isConfirmed && res.value) {
                doReject(res.value.trim());
            }
        });
    } else {
        const note = prompt('Enter rejection reason:');
        if (note) {
            doReject(note.trim());
        }
    }
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
    ['disbursePaymentModal', 'assignExternalIncentiveModal'].forEach(function (modalId) {
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
            const claimCode = this.getAttribute('data-code') || ('Claim #' + claimId);
            const recipient = this.getAttribute('data-recipient') || 'Employee';
            const approvedAmt = this.getAttribute('data-approved-amount') || this.getAttribute('data-amount') || '0';

            const doApprove = (amt, note) => {
                let body = 'claim_id=' + encodeURIComponent(claimId);
                if (amt !== undefined && amt !== null && amt !== '') {
                    body += '&approved_amount=' + encodeURIComponent(amt);
                }
                if (note) {
                    body += '&note=' + encodeURIComponent(note);
                }

                fetch('<?= $base_url ?>/incentives/approve', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest' 
                    },
                    body: body
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
                    html: `
                        <div class="text-start">
                            <p class="mb-2 text-muted">Review and confirm approval details for <strong>${claimCode}</strong> (${recipient}):</p>
                            <div class="mb-3">
                                <label class="form-label extra-small fw-bold text-dark">Approved Amount (৳) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">৳</span>
                                    <input type="number" step="0.01" id="swalApprovedAmount" class="form-control fw-bold text-success" value="${approvedAmt}" required>
                                </div>
                                <span class="text-muted" style="font-size:0.75rem;">You may change this amount if granting a partial approval.</span>
                            </div>
                            <div class="mb-2">
                                <label class="form-label extra-small fw-bold text-dark">Approval Note (Optional)</label>
                                <textarea id="swalApprovalNote" class="form-control form-control-sm" rows="2" placeholder="e.g. Sales verified and approved."></textarea>
                            </div>
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fa-solid fa-check me-1"></i> Confirm Approval',
                    confirmButtonColor: '#198754',
                    cancelButtonColor: '#6c757d',
                    preConfirm: () => {
                        const amtEl = document.getElementById('swalApprovedAmount');
                        const noteEl = document.getElementById('swalApprovalNote');
                        const val = amtEl ? parseFloat(amtEl.value) : 0;
                        if (isNaN(val) || val <= 0) {
                            Swal.showValidationMessage('Please enter a valid approved amount greater than 0.');
                            return false;
                        }
                        return {
                            amount: val,
                            note: noteEl ? noteEl.value.trim() : ''
                        };
                    }
                }).then(res => {
                    if (res.isConfirmed && res.value) {
                        doApprove(res.value.amount, res.value.note);
                    }
                });
            } else {
                const amt = prompt('Approved Amount (৳):', approvedAmt);
                if (amt !== null) {
                    const note = prompt('Approval Note (Optional):', '');
                    doApprove(amt, note);
                }
            }
        });
    });

    // Reject claim buttons
    const rejectButtons = document.querySelectorAll('.btn-reject-claim');
    rejectButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const claimId = this.getAttribute('data-id');
            const claimCode = this.getAttribute('data-code') || ('Claim #' + claimId);

            const doReject = (note) => {
                fetch('<?= $base_url ?>/incentives/reject', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest' 
                    },
                    body: 'claim_id=' + encodeURIComponent(claimId) + '&note=' + encodeURIComponent(note)
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
                    title: 'Reject Incentive Claim',
                    text: `Are you sure you want to reject ${claimCode}? Please provide a reason:`,
                    input: 'textarea',
                    inputPlaceholder: 'Enter reason for rejection...',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fa-solid fa-times me-1"></i> Yes, Reject',
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    inputValidator: (val) => {
                        if (!val || !val.trim()) {
                            return 'Rejection reason is required!';
                        }
                    }
                }).then(res => {
                    if (res.isConfirmed && res.value) {
                        doReject(res.value.trim());
                    }
                });
            } else {
                const reason = prompt('Enter rejection reason:');
                if (reason) {
                    doReject(reason);
                }
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

    // Auto-scroll and highlight target claim if claim_id is in query string
    const urlParams = new URLSearchParams(window.location.search);
    const targetClaimId = urlParams.get('claim_id');
    if (targetClaimId) {
        const claimRow = document.getElementById('claim-' + targetClaimId);
        if (claimRow) {
            setTimeout(() => {
                claimRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 350);
        }
    }
});
</script>

<?php 
$ahInfo = null;
include __DIR__ . '/../layouts/accounts_head_approval_card.php'; 
?>

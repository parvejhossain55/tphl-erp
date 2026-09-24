<?php
$userRole = (int)($_SESSION['user']['role_id'] ?? 0);
$roleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
// Management / Operations (Super Admin, Admin, Sales Heads, Managers, Coordinators)
$isManagement = in_array($userRole, [1, 2, 5, 9, 15, 33]) || strpos($roleName, 'admin') !== false;
// Accounts / Accounts Head / HR
$isAccounts = in_array($userRole, [1, 2, 3, 6, 30, 33]) || strpos($roleName, 'account') !== false;
$isAccountsHead = ($userRole === 30) || strpos($roleName, 'accounts head') !== false;
$canApproveAh = $isAccountsHead || in_array($userRole, [1, 3]) || strpos($roleName, 'admin') !== false;
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
                <label for="monthFilter" class="fw-bold text-muted small mb-0 text-nowrap">Filter Month:</label>
                <input type="month" id="monthFilter" name="month" class="form-control form-control-sm" value="<?= $selected_month ?>" onchange="this.form.submit()">
            </form>
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
                                    <th>Individual Target & Achieve</th>
                                    <th>Indiv. Incentive %</th>
                                    <th>Team Target & Achieve</th>
                                    <th>Team Incentive Earned</th>
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
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm bg-primary-subtle text-primary rounded-circle fw-bold flex-shrink-0 d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-size: 16px;">
                                                    <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($u['full_name']) ?></h6>
                                                    <span class="text-muted extra-small d-block"><?= htmlspecialchars($u['designation'] ?? 'N/A') ?> | <?= htmlspecialchars($u['department'] ?? 'N/A') ?></span>
                                                    <span class="badge bg-light text-dark border extra-small mt-1"><?= $u['employee_id'] ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if($ind['target_amount'] > 0): ?>
                                                <div class="small">Target: <strong class="text-dark">৳<?= number_format($ind['target_amount'], 2) ?></strong></div>
                                                <div class="small">Achieved: <strong class="text-success">৳<?= number_format($ind['assigned_achieved'] + $ind['unassigned_achieved'], 2) ?></strong></div>
                                                <div class="progress mt-1" style="height: 5px;">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= min(100, $ind['achievement_pct']) ?>%;" aria-valuenow="<?= $ind['achievement_pct'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <div class="extra-small text-muted mt-1"><?= number_format($ind['achievement_pct'], 2) ?>% Achieved</div>
                                            <?php else: ?>
                                                <span class="text-muted small">No Indv. Target</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($ind['target_amount'] > 0): ?>
                                                <div class="small">Eligible Payout: <strong><?= $ind['eligible_payout_pct'] ?>%</strong></div>
                                                <div class="small">Slab: <strong><?= $ind['base_slab_pct'] ?>%</strong></div>
                                                <?php if($ind['final_assigned_pct'] > 0): ?>
                                                    <span class="badge bg-primary mt-1">Final: <?= $ind['final_assigned_pct'] ?>%</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($team['eligible'] && $team['team_target'] > 0): ?>
                                                <div class="small">Target: <strong class="text-dark">৳<?= number_format($team['team_target'], 2) ?></strong></div>
                                                <div class="small">Achieved: <strong class="text-success">৳<?= number_format($team['team_achieved'], 2) ?></strong></div>
                                                <div class="progress mt-1" style="height: 5px;">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= min(100, $team['team_achievement_pct']) ?>%;" aria-valuenow="<?= $team['team_achievement_pct'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <div class="extra-small text-muted mt-1"><?= number_format($team['team_achievement_pct'], 2) ?>% Achieved</div>
                                            <?php else: ?>
                                                <span class="text-muted small">Not Eligible</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($team['eligible'] && $team['team_target'] > 0): ?>
                                                <?php if($team['incentive_earned'] > 0): ?>
                                                    <h6 class="text-success fw-bold mb-0">৳<?= number_format($team['incentive_earned'], 2) ?></h6>
                                                    <span class="extra-small text-muted"><?= $team['slabs_hit'] ?> slabs hit (<?= $team['eligible_payout_pct'] ?>% payout)</span>
                                                <?php else: ?>
                                                    <span class="text-muted small">৳0.00</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
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
                                                <span class="text-muted extra-small"><?= date('d M, Y', strtotime($claim['claimed_at'])) ?></span>
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
                                                <?php if ($claim['booking_completed']): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle extra-small rounded-pill d-block mb-1">
                                                        <i class="fa-solid fa-check me-1"></i>Booking Verified
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle extra-small rounded-pill d-block mb-1">
                                                        <i class="fa-solid fa-clock me-1"></i>Booking Pending
                                                    </span>
                                                <?php endif; ?>

                                                <?php if ($claim['registration_completed']): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle extra-small rounded-pill d-block">
                                                        <i class="fa-solid fa-check me-1"></i>Registration Verified
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle extra-small rounded-pill d-block">
                                                        <i class="fa-solid fa-times me-1"></i>Reg Incomplete
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Claim Status -->
                                            <td class="text-center">
                                                <?php if ($claim['claim_status'] === 'not_claimed'): ?>
                                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1 extra-small border">
                                                        <i class="fa-solid fa-hourglass-start me-1"></i>Not Claimed Yet
                                                    </span>
                                                <?php elseif ($claim['claim_status'] === 'claimed'): ?>
                                                    <span class="badge bg-warning text-dark rounded-pill px-3 py-1 extra-small">
                                                        <i class="fa-solid fa-clock me-1"></i>Awaiting Mgmt Approval
                                                    </span>
                                                <?php elseif ($claim['claim_status'] === 'approved'): ?>
                                                    <?php if (!$isAhApprovedClaim): ?>
                                                        <span class="badge bg-warning text-dark rounded-pill px-3 py-1 extra-small">
                                                            <i class="fa-solid fa-lock me-1"></i>Pending Accounts Head
                                                        </span>
                                                        <div class="extra-small text-muted mt-1">Md. Mamunoor Rashid [1309]</div>
                                                    <?php else: ?>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 extra-small mb-1">
                                                            <i class="fa-solid fa-check me-1"></i>AH Approved
                                                        </span>
                                                        <br>
                                                        <span class="badge bg-info text-white rounded-pill px-3 py-1 extra-small">
                                                            <i class="fa-solid fa-thumbs-up me-1"></i>Ready to Disburse
                                                        </span>
                                                    <?php endif; ?>
                                                <?php elseif ($claim['claim_status'] === 'partially_paid'): ?>
                                                    <span class="badge bg-primary text-white rounded-pill px-3 py-1 extra-small">
                                                        <i class="fa-solid fa-pie-chart me-1"></i>Partially Paid (Due: ৳<?= number_format($claim['due_amount']) ?>)
                                                    </span>
                                                <?php elseif ($claim['claim_status'] === 'paid'): ?>
                                                    <span class="badge bg-success text-white rounded-pill px-3 py-1 extra-small">
                                                        <i class="fa-solid fa-check-circle me-1"></i>Paid & Settled
                                                    </span>
                                                <?php elseif ($claim['claim_status'] === 'rejected'): ?>
                                                    <span class="badge bg-danger text-white rounded-pill px-3 py-1 extra-small">
                                                        <i class="fa-solid fa-times-circle me-1"></i>Rejected
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Actions -->
                                            <td class="pe-4 text-end">
                                                <?php if ($claim['claim_status'] === 'not_claimed'): ?>
                                                    <button class="btn btn-sm btn-light text-muted rounded-2 disabled" disabled title="Waiting for Sales Executive to click Claim">
                                                        <i class="fa-solid fa-hourglass-start me-1"></i>Not Claimed Yet
                                                    </button>
                                                <?php elseif ($claim['claim_status'] === 'claimed'): ?>
                                                    <?php if ($isManagement): ?>
                                                        <button class="btn btn-sm btn-success rounded-2 px-2 me-1 btn-approve-claim" data-id="<?= $claim['id'] ?>" title="Approve Claim">
                                                            <i class="fa-solid fa-check me-1"></i>Approve
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger rounded-2 px-2 btn-reject-claim" data-id="<?= $claim['id'] ?>" title="Reject Claim">
                                                            <i class="fa-solid fa-times"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-muted extra-small">Waiting for Mgmt Approval</span>
                                                    <?php endif; ?>
                                                <?php elseif (in_array($claim['claim_status'], ['approved', 'partially_paid'])): ?>
                                                    <?php if (!$isAhApprovedClaim): ?>
                                                        <?php if ($canApproveAh && !empty($ahInfoClaim['approval_request_id'])): ?>
                                                            <button type="button" class="btn btn-sm btn-primary rounded-2 px-3 shadow-sm" onclick="triggerAhApprove(<?= (int)$ahInfoClaim['approval_request_id'] ?>)">
                                                                <i class="fa-solid fa-stamp me-1"></i>AH Approve
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="badge bg-light text-muted border py-1 px-2" style="font-size:10px;" title="Payment locked: Accounts Head approval required (Md. Mamunoor Rashid [1309])">
                                                                <i class="fa-solid fa-lock me-1 text-warning"></i>Awaiting AH (1309)
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
                                                                    data-id="<?= $claim['id'] ?>"
                                                                    data-recipient="<?= htmlspecialchars($claim['user_name']) ?> (<?= htmlspecialchars($claim['employee_id']) ?>)"
                                                                    data-claim="<?= htmlspecialchars($claim['claim_code']) ?>"
                                                                    data-amount="<?= $claim['due_amount'] > 0 ? $claim['due_amount'] : $claim['incentive_amount'] ?>">
                                                                <i class="fa-solid fa-money-bill-transfer me-1"></i>Disburse Payout
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="text-info extra-small fw-bold">Approved (Pending Payout)</span>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted extra-small">Settled / Closed</span>
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
                                                        <div class="extra-small text-muted mt-1">Md. Mamunoor Rashid [1309]</div>
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
                                                            <button type="button" class="btn btn-sm btn-primary rounded-2 px-3 shadow-sm" onclick="triggerAhApprove(<?= (int)$ahInfoExt['approval_request_id'] ?>)">
                                                                <i class="fa-solid fa-stamp me-1"></i>AH Approve
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="badge bg-light text-muted border py-1 px-2" style="font-size:10px;" title="Payment locked: Accounts Head approval required (Md. Mamunoor Rashid [1309])">
                                                                <i class="fa-solid fa-lock me-1 text-warning"></i>Awaiting AH (1309)
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
                                                                    data-id="<?= $ext['id'] ?>"
                                                                    data-recipient="<?= htmlspecialchars($ext['user_name']) ?> (<?= htmlspecialchars($ext['employee_id']) ?>)"
                                                                    data-claim="<?= htmlspecialchars($ext['claim_code']) ?>"
                                                                    data-amount="<?= $ext['incentive_amount'] ?>">
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
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($disbursementLogs)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-3">No disbursement logs found.</td>
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
                                                    </td>
                                                    <td class="text-end">
                                                        <span class="fw-bold text-success extra-small">৳<?= number_format($log['payment_amount'], 2) ?></span>
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
                            <select class="form-select" name="payment_method" required>
                                <option value="Bank Transfer" selected>Bank Transfer</option>
                                <option value="Cheque">Cheque</option>
                                <option value="Cash">Cash</option>
                                <option value="Mobile Banking">Mobile Banking</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-dark fw-bold small">Bank / Provider Name</label>
                            <input type="text" name="bank_name" class="form-control" placeholder="e.g. City Bank Ltd">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark fw-bold small">Transaction / Cheque Ref</label>
                            <input type="text" name="transaction_ref" class="form-control" placeholder="e.g. TXN-998812">
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

<script>
document.addEventListener('DOMContentLoaded', function () {
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

    // Move modals to body as soon as shown to escape stacking context issues
    $(document).on('show.bs.modal', '.modal', function () {
        if (this.parentNode !== document.body) {
            document.body.appendChild(this);
        }
    });

    // Disburse modal trigger logic (100% identical pattern to my-incentives.php)
    $(document).on('click', '.btn-disburse-modal', function (e) {
        e.preventDefault();
        const id = $(this).attr('data-id');
        const recipient = $(this).attr('data-recipient');
        const claim = $(this).attr('data-claim');
        const amount = $(this).attr('data-amount');
        
        $('#modalDisburseClaimId').val(id || '');
        $('#modalDisburseRecipient').val(recipient || '');
        $('#modalDisburseClaim').val(claim || '');
        $('#modalDisburseAmount').val(amount || '');

        $('#disbursePaymentModal').modal('show');
    });

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

<style>
    /* 5 Columns per row responsive grid for compact mini-cards */
    .module-mini-card-col {
        flex: 0 0 20%;
        max-width: 20%;
        padding: 4px;
        margin-bottom: 6px;
    }
    @media (max-width: 1400px) {
        .module-mini-card-col {
            flex: 0 0 20%;
            max-width: 20%;
        }
    }
    @media (max-width: 1200px) {
        .module-mini-card-col {
            flex: 0 0 25%;
            max-width: 25%;
        }
    }
    @media (max-width: 992px) {
        .module-mini-card-col {
            flex: 0 0 33.333333%;
            max-width: 33.333333%;
        }
    }
    @media (max-width: 768px) {
        .module-mini-card-col {
            flex: 0 0 50%;
            max-width: 50%;
        }
    }
    @media (max-width: 500px) {
        .module-mini-card-col {
            flex: 0 0 100%;
            max-width: 100%;
        }
    }

    .module-mini-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 8px 10px;
        transition: all 0.2s ease-in-out;
        cursor: pointer;
        display: flex;
        align-items: center;
        text-decoration: none !important;
        position: relative;
        min-height: 64px;
        height: 64px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        user-select: none;
    }
    .module-mini-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 14px rgba(0, 0, 0, 0.07);
        border-color: #cbd5e1;
    }
    .module-mini-card.is-active-filter {
        border: 2px solid #3b82f6 !important;
        background: #f0f7ff !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.18) !important;
    }
    .pulse-dot-amber {
        display: inline-block;
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background-color: #f59e0b;
        animation: pulseAnimationAmber 1.6s infinite;
        margin-right: 3px;
    }
    @keyframes pulseAnimationAmber {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 4px rgba(245, 158, 11, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
    }
</style>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-bold mb-1 text-dark">
                <i class="fa-solid fa-list-check text-primary me-2"></i>All System Approval Requests & Payout Tracking
            </h3>
            <p class="text-muted small mb-0">Unified dashboard for Management & Accounts to monitor all company requests
                across Sales, Construction Finance, HR, and Accounts.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= $base_url ?>/branch-disbursements" class="btn btn-sm btn-outline-success rounded-pill px-3 shadow-sm fw-semibold">
                <i class="fa-solid fa-building-columns me-1"></i>Office Payout Audit
            </a>
            <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fs-7 fw-semibold">
                <i class="fa-solid fa-shield-halved me-1"></i> Management & Accounts Audit Panel
            </span>
        </div>
    </div>

    <!-- Company Dashboard Summary Metrics Row -->
    <div class="row mb-4">
        <!-- Total System Requests -->
        <div class="col-xl-3 col-md-6 col-sm-6">
            <a href="<?= $base_url ?>/all-approval-requests?status=all<?= !empty($filters['department']) ? '&department=' . urlencode($filters['department']) : '' ?><?= !empty($filters['source_table']) ? '&source_table=' . urlencode($filters['source_table']) : '' ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-primary hover-shadow transition-all">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold">Total Requests Created</span>
                            <div class="avatar avatar-sm bg-primary-subtle text-primary rounded-2">
                                <i class="fa-solid fa-folder-tree"></i>
                            </div>
                        </div>
                        <h3 class="fw-bold mb-1 text-dark">
                            <?= number_format($companyMetrics['total_requests']) ?> Items
                        </h3>
                        <span class="text-muted extra-small">Across all company departments &bull; View All</span>
                    </div>
                </div>
            </a>
        </div>

        <!-- Pending Approval Queue -->
        <div class="col-xl-3 col-md-6 col-sm-6">
            <a href="<?= $base_url ?>/all-approval-requests?status=all_pending<?= !empty($filters['department']) ? '&department=' . urlencode($filters['department']) : '' ?><?= !empty($filters['source_table']) ? '&source_table=' . urlencode($filters['source_table']) : '' ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-warning hover-shadow transition-all">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold">Pending Approval Queue</span>
                            <div class="avatar avatar-sm bg-warning-subtle text-warning rounded-2">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </div>
                        </div>
                        <h3 class="fw-bold mb-1 text-warning">৳
                            <?= number_format($companyMetrics['pending_amount'], 2) ?>
                        </h3>
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle extra-small rounded-pill">
                            <?= $companyMetrics['pending_count'] ?> Requests Awaiting Review
                        </span>
                    </div>
                </div>
            </a>
        </div>

        <!-- Total Approved Amount -->
        <div class="col-xl-3 col-md-6 col-sm-6">
            <a href="<?= $base_url ?>/all-approval-requests?status=pending_payout<?= !empty($filters['department']) ? '&department=' . urlencode($filters['department']) : '' ?><?= !empty($filters['source_table']) ? '&source_table=' . urlencode($filters['source_table']) : '' ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-info hover-shadow transition-all">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold">Total Approved Value</span>
                            <div class="avatar avatar-sm bg-info-subtle text-info rounded-2">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                        </div>
                        <h3 class="fw-bold mb-1 text-info">৳
                            <?= number_format($companyMetrics['approved_amount'], 2) ?>
                        </h3>
                        <span class="badge bg-info-subtle text-info border border-info-subtle extra-small rounded-pill">
                            <?= $companyMetrics['approved_count'] ?> Approved Requests (View Queue)
                        </span>
                    </div>
                </div>
            </a>
        </div>

        <!-- Total Accounts Disbursed / Paid -->
        <div class="col-xl-3 col-md-6 col-sm-6">
            <a href="<?= $base_url ?>/all-approval-requests?status=paid<?= !empty($filters['department']) ? '&department=' . urlencode($filters['department']) : '' ?><?= !empty($filters['source_table']) ? '&source_table=' . urlencode($filters['source_table']) : '' ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-success hover-shadow transition-all">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold">Total Accounts Disbursed</span>
                            <div class="avatar avatar-sm bg-success-subtle text-success rounded-2">
                                <i class="fa-solid fa-money-bill-wave"></i>
                            </div>
                        </div>
                        <h3 class="fw-bold mb-1 text-success">৳
                            <?= number_format($companyMetrics['total_disbursed'], 2) ?>
                        </h3>
                        <span class="badge bg-success-subtle text-success border border-success-subtle extra-small rounded-pill">
                            <i class="fa-solid fa-check-double me-1"></i>View Paid & Settled History
                        </span>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Module-Wise Requisition & Expense Tracking (Compact Cards: 5 Per Row) -->
    <?php if (!empty($moduleMetrics)): 
        $currentStatus = $filters['status'] ?? '';
        $isAllPendingMode = ($currentStatus === 'all_pending');
        $isMyPendingMode = (empty($currentStatus) || $currentStatus === 'pending' || $currentStatus === 'my_pending');
        $modeTitle = $isAllPendingMode ? 'All Departments Pending' : ($isMyPendingMode ? 'My Actionable Pending' : ucfirst($currentStatus));
        $totalCardPendingCount = array_sum(array_column($moduleMetrics, 'pending_count'));
        $totalCardPendingAmount = array_sum(array_column($moduleMetrics, 'pending_amount'));
    ?>
        <div class="mb-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-2">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <h6 class="fw-bold text-dark mb-0 d-flex align-items-center">
                        <i class="fa-solid fa-boxes-stacked text-primary me-2"></i>Expense & Requisition Modules
                    </h6>
                    <!-- View mode indicator pill -->
                    <span class="badge <?= $isAllPendingMode ? 'bg-indigo-subtle text-indigo border border-indigo-subtle' : 'bg-primary-subtle text-primary border border-primary-subtle' ?> rounded-pill px-2 py-1 extra-small fw-semibold" id="cardViewModeBadge">
                        <i class="fa-solid <?= $isAllPendingMode ? 'fa-building-shield' : 'fa-user-check' ?> me-1"></i>
                        Showing: <?= htmlspecialchars($modeTitle) ?> (<?= number_format($totalCardPendingCount) ?> items &bull; ৳<?= number_format($totalCardPendingAmount, 0) ?>)
                    </span>
                </div>
                <div class="d-flex align-items-center gap-2 mt-2 mt-sm-0">
                    <!-- Quick Switch Toggle: My Actionable vs All Departments Pending -->
                    <div class="btn-group btn-group-sm p-0 rounded-pill bg-light border shadow-sm">
                        <a href="<?= $base_url ?>/all-approval-requests?status=my_pending<?= !empty($filters['department']) ? '&department=' . urlencode($filters['department']) : '' ?><?= !empty($filters['source_table']) ? '&source_table=' . urlencode($filters['source_table']) : '' ?>" 
                           class="btn btn-xs rounded-pill px-3 py-1 fw-semibold <?= $isMyPendingMode ? 'btn-primary text-white shadow-sm' : 'text-muted' ?>"
                           style="font-size: 11px;">
                           <i class="fa-solid fa-user-check me-1"></i>My Actionable
                        </a>
                        <a href="<?= $base_url ?>/all-approval-requests?status=all_pending<?= !empty($filters['department']) ? '&department=' . urlencode($filters['department']) : '' ?><?= !empty($filters['source_table']) ? '&source_table=' . urlencode($filters['source_table']) : '' ?>" 
                           class="btn btn-xs rounded-pill px-3 py-1 fw-semibold <?= $isAllPendingMode ? 'btn-primary text-white shadow-sm' : 'text-muted' ?>"
                           style="font-size: 11px; <?= $isAllPendingMode ? 'background-color: #6366f1; border-color: #6366f1;' : '' ?>">
                           <i class="fa-solid fa-building-shield me-1"></i>All Dept Pending
                        </a>
                    </div>
                    <?php if (!empty($filters['source_table'])): ?>
                        <a href="<?= $base_url ?>/all-approval-requests?status=<?= urlencode($currentStatus) ?><?= !empty($filters['department']) ? '&department=' . urlencode($filters['department']) : '' ?>#approvalRequestsTable" 
                           class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1 text-decoration-none extra-small" title="Clear Module Filter">
                            <i class="fa-solid fa-circle-xmark me-1"></i><?= htmlspecialchars($moduleMetrics[$filters['source_table']]['name'] ?? $filters['source_table']) ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 5 Cards Per Row Compact Grid -->
            <div class="d-flex flex-wrap" style="margin-left: -4px; margin-right: -4px;" id="moduleCardsGrid">
                <?php foreach ($moduleMetrics as $modKey => $mod): 
                    $isActive = ($filters['source_table'] ?? '') === $modKey;
                    $hasPending = $mod['pending_count'] > 0;
                    if ($isActive) {
                        $cardClickUrl = $base_url . "/all-approval-requests?status=" . urlencode($currentStatus) . (!empty($filters['department']) ? '&department=' . urlencode($filters['department']) : '') . "#approvalRequestsTable";
                    } else {
                        $cardClickUrl = $base_url . "/all-approval-requests?source_table=" . urlencode($modKey) . "&status=" . urlencode($currentStatus) . (!empty($filters['department']) ? '&department=' . urlencode($filters['department']) : '') . "#approvalRequestsTable";
                    }
                    $portalUrl = !empty($mod['portal_url']) ? $base_url . '/' . $mod['portal_url'] : '#';
                ?>
                    <div class="module-mini-card-col">
                        <div class="module-mini-card <?= $isActive ? 'is-active-filter' : '' ?>" 
                             id="card-<?= htmlspecialchars($modKey) ?>"
                             onclick="window.location.href='<?= $cardClickUrl ?>'"
                             data-mod-key="<?= htmlspecialchars($modKey) ?>"
                             data-pending-count="<?= (int)$mod['pending_count'] ?>"
                             data-pending-amount="<?= (float)$mod['pending_amount'] ?>"
                             title="Click to <?= $isActive ? 'clear filter' : 'filter by ' . htmlspecialchars($mod['name']) ?>">
                            <div class="d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center gap-2 text-truncate me-1">
                                    <div class="mini-card-icon rounded-2 d-flex align-items-center justify-content-center flex-shrink-0"
                                         style="background-color: <?= $mod['accent_hex'] ?>18; color: <?= $mod['accent_hex'] ?>; width: 34px; height: 34px;">
                                        <i class="<?= $mod['icon'] ?> fs-14"></i>
                                    </div>
                                    <div class="text-truncate">
                                        <div class="fw-bold text-dark text-truncate" style="font-size: 11.5px; line-height: 1.25;" title="<?= htmlspecialchars($mod['name']) ?>">
                                            <?= htmlspecialchars($mod['name']) ?>
                                        </div>
                                        <div class="extra-small text-truncate" style="font-size: 10.5px; line-height: 1.2;">
                                            <?php if ($hasPending): ?>
                                                <span class="text-dark fw-semibold">৳ <?= number_format($mod['pending_amount'], 0) ?></span>
                                            <?php else: ?>
                                                <span class="text-success"><i class="fa-solid fa-circle-check extra-small me-1"></i>Clear</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                    <span class="badge <?= $hasPending ? 'bg-warning text-dark' : 'bg-light text-muted border' ?> rounded-pill px-2 py-1 extra-small fw-bold mod-badge-count" 
                                          data-mod-key="<?= htmlspecialchars($modKey) ?>"
                                          style="font-size: 10px;">
                                        <?= $mod['pending_count'] ?><?= $hasPending ? ' Req' : '' ?>
                                    </span>
                                    <?php if ($isActive): ?>
                                        <i class="fa-solid fa-check text-primary extra-small ms-1"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content Table Card -->
    <div class="card border-0 shadow-sm rounded-3" id="approvalRequestsTable">
        <!-- Filter Toolbar -->
        <div class="card-header bg-white py-3 border-bottom">
            <form method="GET" action="<?= $base_url ?>/all-approval-requests" class="row align-items-center">
                <!-- Search / Filter Title -->
                <div class="col-lg-3 col-md-6">
                    <label class="form-label text-muted extra-small fw-bold text-uppercase mb-1">Department
                        Filter</label>
                    <select name="department" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Departments</option>
                        <?php if (!empty($departmentsList)): ?>
                            <?php foreach ($departmentsList as $deptName): ?>
                                <option value="<?= htmlspecialchars($deptName) ?>" <?= strtolower($filters['department'] ?? '') === strtolower($deptName) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($deptName) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="col-lg-3 col-md-6">
                    <label class="form-label text-muted extra-small fw-bold text-uppercase mb-1">Approval Status</label>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="" <?= empty($filters['status']) || $filters['status'] === 'pending' || $filters['status'] === 'my_pending' ? 'selected' : '' ?>>My Actionable Pending (Default)</option>
                        <option value="pending_accounts_head" <?= ($filters['status'] ?? '') === 'pending_accounts_head' ? 'selected' : '' ?>>Approved — Pending Accounts Head (অ্যাকাউন্টস হেড বাকি)</option>
                        <option value="pending_payout" <?= ($filters['status'] ?? '') === 'pending_payout' ? 'selected' : '' ?>>Approved — Pending Accounts Payout (পেমেন্ট বাকি)</option>
                        <option value="paid" <?= ($filters['status'] ?? '') === 'paid' ? 'selected' : '' ?>>Paid & Settled History (পরিশোধিত)</option>
                        <option value="approved" <?= ($filters['status'] ?? '') === 'approved' ? 'selected' : '' ?>>All Approved & Paid (সকল অনুমোদিত ও পরিশোধিত)</option>
                        <option value="rejected" <?= ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected History</option>
                        <option value="all_pending" <?= ($filters['status'] ?? '') === 'all_pending' ? 'selected' : '' ?>>All Departments Pending (Audit)</option>
                        <option value="all" <?= ($filters['status'] ?? '') === 'all' ? 'selected' : '' ?>>All Company Requests (Complete Audit)</option>
                    </select>
                </div>

                <!-- Source Table / Module Filter -->
                <div class="col-lg-4 col-md-6">
                    <label class="form-label text-muted extra-small fw-bold text-uppercase mb-1">Module / Source
                        Type</label>
                    <select name="source_table" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Approval Source Types</option>
                        <option value="fund_requisitions" <?= ($filters['source_table'] ?? '') === 'fund_requisitions' ? 'selected' : '' ?>>Fund Requisitions / Petty Cash</option>
                        <option value="project_incentive_claims" <?= ($filters['source_table'] ?? '') === 'project_incentive_claims' ? 'selected' : '' ?>>Sales Incentive Claims</option>
                        <option value="vendor_payment_requests" <?= ($filters['source_table'] ?? '') === 'vendor_payment_requests' ? 'selected' : '' ?>>Vendor Payment Requests (Construction)
                        </option>
                        <option value="contractor_bill_requests" <?= ($filters['source_table'] ?? '') === 'contractor_bill_requests' ? 'selected' : '' ?>>Contractor Bills (Construction)
                        </option>
                        <option value="con_labor_requisitions" <?= ($filters['source_table'] ?? '') === 'con_labor_requisitions' ? 'selected' : '' ?>>Labor Bill by SE (Construction)</option>
                        <option value="operational_fund_requests" <?= ($filters['source_table'] ?? '') === 'operational_fund_requests' ? 'selected' : '' ?>>Operational Fund / Requisitions
                        </option>
                        <option value="cashout_requests" <?= ($filters['source_table'] ?? '') === 'cashout_requests' ? 'selected' : '' ?>>Cashout Requests (Accounts)</option>
                        <option value="advance_salaries" <?= ($filters['source_table'] ?? '') === 'advance_salaries' ? 'selected' : '' ?>>Advance Salary Requests (HR & Accounts)</option>
                        <option value="payslips" <?= ($filters['source_table'] ?? '') === 'payslips' ? 'selected' : '' ?>>Payslips & Salary (HR)</option>
                        <option value="project_other_costs" <?= ($filters['source_table'] ?? '') === 'project_other_costs' ? 'selected' : '' ?>>Project Other Costs (Construction)</option>
                        <option value="project_extra_assets" <?= in_array($filters['source_table'] ?? '', ['project_extra_assets', 'purchase_requests']) ? 'selected' : '' ?>>Office Procurement Requests</option>
                        <option value="client_refund_requests" <?= ($filters['source_table'] ?? '') === 'client_refund_requests' ? 'selected' : '' ?>>Client Refund Requests</option>
                        <option value="con_work_orders" <?= ($filters['source_table'] ?? '') === 'con_work_orders' ? 'selected' : '' ?>>Work Orders (Construction)</option>
                        <option value="lead_bookings" <?= ($filters['source_table'] ?? '') === 'lead_bookings' ? 'selected' : '' ?>>Booking Discounts (Sales)</option>
                        <option value="manpower_requisitions" <?= ($filters['source_table'] ?? '') === 'manpower_requisitions' ? 'selected' : '' ?>>Manpower Requisitions</option>
                        <option value="land_fund_requisitions" <?= in_array($filters['source_table'] ?? '', ['land_fund_requisitions', 'con_land_requisitions']) ? 'selected' : '' ?>>Land Fund Requisitions</option>
                    </select>
                </div>

                <!-- Reset Filter Button -->
                <div class="col-lg-2 col-md-6 text-end pt-3">
                    <a href="<?= $base_url ?>/all-approval-requests" class="btn btn-sm btn-outline-secondary w-100">
                        <i class="fa-solid fa-arrow-rotate-left me-1"></i> Reset Filters
                    </a>
                </div>
            </form>
        </div>

        <!-- Bulk Action & Page Length Bar -->
        <?php
        $userRoleId = (int) ($_SESSION['user']['role_id'] ?? 0);
        $sessionRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
        $isChiefOfficer = (in_array($userRoleId, [33, 34, 35], true) || in_array($sessionRoleName, ['chief officer', 'management viewer', 'seal ceo'], true));
        ?>
        <div class="p-3 bg-light border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2">
                <?php if (!$isChiefOfficer): ?>
                    <span class="badge bg-primary fs-12 px-3 py-2" id="selectedApprovalCountBadge">0 Selected</span>
                    <button type="button" class="btn btn-sm btn-success fw-bold px-3"
                        onclick="processBulkApprovalAction('approve')">
                        <i class="fa-solid fa-circle-check me-1"></i> Bulk Approve Selected
                    </button>
                    <button type="button" class="btn btn-sm btn-danger fw-bold px-3"
                        onclick="processBulkApprovalAction('reject')">
                        <i class="fa-solid fa-circle-xmark me-1"></i> Bulk Reject Selected
                    </button>
                <?php else: ?>
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-12 px-3 py-2">
                        <i class="fa-solid fa-eye me-1"></i> View-Only Access (Role: <?= htmlspecialchars($_SESSION['user']['role_name'] ?? 'View-Only') ?>)
                    </span>
                <?php endif; ?>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label class="form-label mb-0 fs-13 fw-bold text-muted">Rows per page:</label>
                <select id="approvalPageLengthSelect" class="form-select form-select-sm" style="width: 110px;"
                    onchange="changeApprovalTablePageLength(this.value)">
                    <option value="25" selected>25 Rows</option>
                    <option value="50">50 Rows</option>
                    <option value="70">70 Rows</option>
                    <option value="100">100 Rows</option>
                    <option value="200">200 Rows</option>
                    <option value="-1">All Rows</option>
                </select>
            </div>
        </div>
        
        <!-- Approval History Table -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="allApprovalRequestsTable">
                    <thead class="bg-light text-muted extra-small text-uppercase">
                        <tr>
                            <th class="ps-3" style="width: 40px;">
                                <input type="checkbox" id="masterApprovalCheckbox" class="form-check-input"
                                    onclick="toggleSelectAllApprovalRequests(this)" <?= $isChiefOfficer ? 'disabled title="Bulk approval disabled for Chief Officer"' : '' ?>>
                            </th>
                            <th>Req # & Date</th>
                            <th>Department & Module</th>
                            <th>Purpose / Title</th>
                            <th>Requested By</th>
                            <th>Requested Amount</th>
                            <th>Approved Amount</th>
                            <th class="text-center">Approval Status & Pending At</th>
                            <th class="text-center">Accounts Payout</th>
                            <th class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($approvalHistory)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-folder-open fs-3 d-block mb-2"></i>
                                    No approval requests found matching your filter criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($approvalHistory as $req): ?>
                                <?php
                                // Direct module target URL resolution so clicking review/view opens the exact module page!
                                $targetUrl = 'javascript:void(0)';
                                $sourceTable = strtolower($req['source_table'] ?? '');
                                $requestType = strtolower($req['request_type'] ?? '');

                                if ($sourceTable === 'lead_payments') {
                                    if (!empty($req['booking_id'])) {
                                        $targetUrl = $base_url . '/clients-cash-in-history-view/' . $req['booking_id'];
                                    } else {
                                        $targetUrl = $base_url . '/ced-dashboard';
                                    }
                                } elseif ($sourceTable === 'lead_bookings') {
                                    $bookingId = $req['source_id'] ?? $req['booking_id'] ?? null;
                                    if (!empty($bookingId)) {
                                        $targetUrl = $base_url . '/clients-cash-in-history-view/' . $bookingId;
                                    } else {
                                        $targetUrl = $base_url . '/ced-dashboard';
                                    }
                                } elseif ($sourceTable === 'project_incentive_claims') {
                                    $targetUrl = $base_url . '/incentive-management';
                                } elseif ($sourceTable === 'vendor_payment_requests') {
                                    $targetUrl = $base_url . '/con-vendor-payment-show/' . $req['source_id'];
                                } elseif ($sourceTable === 'contractor_bill_requests') {
                                    $targetUrl = $base_url . '/con-contractor-bill/' . $req['source_id'];
                                } elseif ($sourceTable === 'con_land_requisitions' || $sourceTable === 'land_fund_requisitions') {
                                    $targetUrl = $base_url . '/con-land-requisitions?pay_id=' . $req['source_id'];
                                } elseif ($sourceTable === 'con_labor_requisitions') {
                                    $targetUrl = $base_url . '/con-labor-requisition/' . $req['source_id'];
                                } elseif ($sourceTable === 'con_work_orders') {
                                    $targetUrl = $base_url . '/con-work-order/' . $req['source_id'];
                                } elseif ($sourceTable === 'fund_requisitions' || $sourceTable === 'operational_fund_requests') {
                                    $targetUrl = $base_url . '/fund-requisitions/view/' . $req['source_id'];
                                } elseif ($sourceTable === 'cashout_requests') {
                                    $targetUrl = $base_url . '/view-cashout-request?id=' . $req['source_id'];
                                } elseif ($sourceTable === 'payslips' || $sourceTable === 'salary_requests' || $requestType === 'salary') {
                                    $targetUrl = $base_url . '/show-payslip/' . $req['source_id'];
                                } elseif ($sourceTable === 'client_refund_requests') {
                                    $targetUrl = $base_url . '/client-refund/' . $req['source_id'];
                                } elseif ($sourceTable === 'employee_leaves' || $sourceTable === 'leave') {
                                    $targetUrl = $base_url . '/hrm-leave-history';
                                } elseif ($sourceTable === 'manpower_requisitions') {
                                    $targetUrl = $base_url . '/manpower-requisitions';
                                } elseif ($sourceTable === 'advance_salaries') {
                                    $targetUrl = $base_url . '/advance-salary';
                                } elseif ($sourceTable === 'project_other_costs') {
                                    $targetUrl = $base_url . '/project-other-costs/review/' . $req['source_id'];
                                } elseif ($sourceTable === 'project_extra_assets' || $sourceTable === 'purchase_requests') {
                                    $targetUrl = $base_url . '/asset-extra-purchases?view=' . $req['source_id'];
                                } elseif ($sourceTable === 'expense_adjustments') {
                                    $targetUrl = $base_url . '/expense-adjustments/show?id=' . $req['source_id'];
                                }
                                
                                if ($targetUrl !== 'javascript:void(0)') {
                                    $targetUrl .= (strpos($targetUrl, '?') !== false ? '&' : '?') . 'ref=all_approvals';
                                }

                                $reqApproveRole = strtolower($req['approve_role'] ?? 'management');
                                if (empty($reqApproveRole)) {
                                    $reqApproveRole = 'management';
                                }
                                $isMgmtRequest = ($reqApproveRole === 'management');
                                $userRoleId = (int) ($_SESSION['user']['role_id'] ?? 0);
                                $sessionRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
                                $isChiefOfficer = (in_array($userRoleId, [33, 34, 35], true) || in_array($sessionRoleName, ['chief officer', 'management viewer', 'seal ceo'], true));

                                $isManagementUser = (in_array($userRoleId, [1, 3], true) || in_array($sessionRoleName, ['super admin', 'admin', 'management'], true)) && !$isChiefOfficer;
                                $isSiteOperator = in_array($userRoleId, [20, 32], true) || strpos($sessionRoleName, 'operation head') !== false || strpos($sessionRoleName, 'admin & operation') !== false;
                                $isAccountsHeadUser = ($userRoleId === 30) || strpos($sessionRoleName, 'accounts head') !== false;
                                $isAccountsStaffUser = ($userRoleId === 6) || (strpos($sessionRoleName, 'account') !== false && !$isAccountsHeadUser);
                                $isAccountsUser = in_array($userRoleId, [6, 30], true) || strpos($sessionRoleName, 'account') !== false;
                                $isQcUser = in_array($userRoleId, [13, 24, 25], true) || strpos($sessionRoleName, 'qc') !== false || strpos($sessionRoleName, 'audit') !== false;
                                $isHrUser = ($userRoleId === 4) || strpos($sessionRoleName, 'hr') !== false;
                                $isPurchaseUser = in_array($userRoleId, [17, 23, 31], true) || strpos($sessionRoleName, 'purchase') !== false;
                                $isDeptHeadUser = in_array($userRoleId, [8, 9, 10, 11, 12, 14, 15, 16, 18, 19], true) || strpos($sessionRoleName, 'head') !== false || strpos($sessionRoleName, 'manager') !== false;

                                $statusLower = strtolower($req['status'] ?? '');
                                $isPendingStatus = in_array($statusLower, ['pending', 'partially_approved', '']);
                                $reqAmount = (float)($req['request_amount'] ?? 0);
                                $payoutStatus = $req['payout_status'] ?? '';
                                $ahStatus = strtolower($req['accounts_head_status'] ?? 'pending');
                                $isPendingAccountsHead = ($statusLower === 'approved' || $req['status'] === 'approved') && ($ahStatus !== 'approved' || $reqApproveRole === 'accounts_head');
                                $isAccountsHeadApproved = ($ahStatus === 'approved');

                                // Determine whether this request is actionable by the logged-in user right now
                                $isActionableByMe = false;
                                $canUserApprove = false;
                                if ($isChiefOfficer) {
                                    // Chief Officer has view-only access across all requests; strictly cannot approve or reject
                                    $isActionableByMe = false;
                                    $canUserApprove = false;
                                } elseif ($isPendingAccountsHead) {
                                    // Pending Accounts Head approval: Actionable by Accounts Head (Role 30) and Super Admin / Admin (1, 3)
                                    if ($isAccountsHeadUser || $isManagementUser) {
                                        $isActionableByMe = true;
                                        $canUserApprove = true;
                                    } else {
                                        $isActionableByMe = false;
                                        $canUserApprove = false;
                                    }
                                } elseif ($isAccountsHeadApproved && in_array($payoutStatus, ['Pending Accounts Payout', 'Partially Paid'])) {
                                    // Approved by Accounts Head & awaiting payout: Actionable by BOTH Accounts (Role 6) and Accounts Head (Role 30)
                                    if ($isAccountsUser) {
                                        $isActionableByMe = true;
                                        $canUserApprove = true;
                                    }
                                } elseif ($isAccountsUser) {
                                    if (in_array($payoutStatus, ['Pending Accounts Payout', 'Partially Paid']) || ($reqApproveRole === 'accounts' && in_array($statusLower, ['pending', 'approved', 'partially_approved', 'partially_paid']) && $isAccountsHeadApproved)) {
                                        $isActionableByMe = true;
                                        $canUserApprove = true;
                                    }
                                } elseif ($isSiteOperator) {
                                    $reqOpStatus = strtolower($req['op_head_status'] ?? 'pending');
                                    if ($isPendingStatus && in_array($reqApproveRole, ['management', 'operations', '']) && $reqOpStatus !== 'approved') {
                                        $isActionableByMe = true;
                                        $canUserApprove = true;
                                    }
                                } elseif ($isManagementUser) {
                                    if ($isPendingStatus && in_array($reqApproveRole, ['management', 'operations', ''])) {
                                        $isActionableByMe = true;
                                        $canUserApprove = true;
                                    }
                                } elseif ($isQcUser) {
                                    if ($isPendingStatus && in_array($reqApproveRole, ['qc', 'qc_head'])) {
                                        $isActionableByMe = true;
                                        $canUserApprove = true;
                                    }
                                } elseif ($isPurchaseUser) {
                                    if ($isPendingStatus && in_array($reqApproveRole, ['purchase', 'purchase team leader', 'purchase_lead'])) {
                                        $isActionableByMe = true;
                                        $canUserApprove = true;
                                    }
                                } elseif ($isHrUser) {
                                    if ($isPendingStatus && in_array($reqApproveRole, ['hr', 'hr_head'])) {
                                        $isActionableByMe = true;
                                        $canUserApprove = true;
                                    }
                                } elseif ($isDeptHeadUser) {
                                    if ($isPendingStatus && ($reqApproveRole === 'dept_head' || $reqApproveRole === 'dept_head_' . ($_SESSION['user']['id'] ?? 0))) {
                                        $isActionableByMe = true;
                                        $canUserApprove = true;
                                    }
                                } else {
                                    if ($isPendingStatus && !empty($reqApproveRole) && strpos($sessionRoleName, $reqApproveRole) !== false) {
                                        $isActionableByMe = true;
                                        $canUserApprove = true;
                                    }
                                }

                                $rowBgStyle = $isActionableByMe ? 'background-color: #f8fafc;' : '';
                                ?>
                                <tr data-source-table="<?= htmlspecialchars($req['source_table']) ?>" <?= !empty($targetUrl) ? 'data-href="' . htmlspecialchars($targetUrl) . '" class="clickable-approval-row' . ($isActionableByMe ? ' border-start border-primary border-3' : '') . '" style="cursor: pointer; ' . $rowBgStyle . '"' : '' ?>>
                                    <td class="ps-3">
                                        <input type="checkbox" class="approval-req-checkbox form-check-input"
                                            value="<?= $req['id'] ?>" <?= ((!in_array($req['status'], ['pending', 'partially_approved', 'partially_paid']) && !($isPendingAccountsHead && ($isAccountsHeadUser || $isManagementUser))) || !$canUserApprove || $isChiefOfficer) ? 'disabled' : '' ?> onchange="updateBulkApprovalCount()">
                                    </td>
                                    <!-- Req # & Date -->
                                    <td>
                                        <?php if (!empty($targetUrl)): ?>
                                            <a href="<?= $targetUrl ?>" class="text-decoration-none d-block">
                                                <?php if ($sourceTable === 'vendor_payment_requests'): ?>
                                                    <span class="fw-bold text-dark">VPR-<?= (int)$req['source_id'] ?></span>
                                                    <?php if (!empty($req['po_number'])): ?>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded px-1 ms-1 extra-small">PO-<?= htmlspecialchars($req['po_number']) ?></span>
                                                    <?php endif; ?>
                                                <?php elseif ($sourceTable === 'expense_adjustments'): ?>
                                                    <span class="fw-bold text-dark">ADJ-<?= (int)$req['source_id'] ?></span>
                                                <?php elseif (!empty($req['po_number'])): ?>
                                                    <span class="fw-bold text-success">PO-
                                                        <?= htmlspecialchars($req['po_number']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="fw-bold text-dark">#REQ-
                                                        <?= str_pad($req['id'], 5, '0', STR_PAD_LEFT) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </a>
                                        <?php else: ?>
                                            <?php if ($sourceTable === 'vendor_payment_requests'): ?>
                                                <span class="fw-bold text-dark">VPR-<?= (int)$req['source_id'] ?></span>
                                                <?php if (!empty($req['po_number'])): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded px-1 ms-1 extra-small">PO-<?= htmlspecialchars($req['po_number']) ?></span>
                                                <?php endif; ?>
                                            <?php elseif ($sourceTable === 'expense_adjustments'): ?>
                                                <span class="fw-bold text-dark d-block">ADJ-<?= (int)$req['source_id'] ?></span>
                                            <?php elseif (!empty($req['po_number'])): ?>
                                                <span class="fw-bold text-success d-block">PO-
                                                    <?= htmlspecialchars($req['po_number']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="fw-bold text-dark d-block">#REQ-
                                                    <?= str_pad($req['id'], 5, '0', STR_PAD_LEFT) ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <span class="text-muted extra-small">
                                            <?= date('d M, Y', strtotime($req['requested_at'])) ?>
                                        </span>
                                    </td>

                                    <!-- Department & Module -->
                                    <td>
                                        <span
                                            class="badge bg-success text-white rounded-pill px-3 py-1 extra-small d-block mb-1">
                                            <?= htmlspecialchars($req['department'] ?? 'General') ?>
                                        </span>
                                        <span class="badge bg-light text-muted border extra-small">
                                            <?= htmlspecialchars($req['request_type'] ?? $req['source_table']) ?>
                                        </span>
                                    </td>

                                    <!-- Title -->
                                    <td style="max-width: 250px;">
                                        <div class="fw-semibold text-dark text-truncate"
                                            title="<?= htmlspecialchars($req['title']) ?>">
                                            <?php if (!empty($targetUrl)): ?>
                                                <a href="<?= $targetUrl ?>" class="text-dark text-decoration-none hover-primary">
                                                    <?= htmlspecialchars($req['title']) ?>
                                                </a>
                                            <?php else: ?>
                                                <?= htmlspecialchars($req['title']) ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($sourceTable === 'expense_adjustments'): ?>
                                            <div class="mt-1 d-flex flex-wrap gap-1">
                                                <?php if (($req['request_type'] ?? '') === 'Extra Expense' || strpos(strtolower($req['title'] ?? ''), 'extra') !== false): ?>
                                                    <span class="badge bg-warning text-dark border border-warning-subtle extra-small fw-bold">
                                                        <i class="fa-solid fa-plus-circle me-1"></i>Extra Amount Request
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-info-subtle text-info border border-info-subtle extra-small fw-bold">
                                                        <i class="fa-solid fa-arrow-rotate-left me-1"></i>Return Unused Money
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($req['project_name'])): ?>
                                            <div class="mt-1">
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle extra-small">
                                                    <i class="fa-solid fa-building me-1"></i><?= htmlspecialchars($req['project_name']) ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($req['attachment'])): ?>
                                            <div class="mt-1">
                                                <a href="<?= $base_url ?>/<?= htmlspecialchars(ltrim($req['attachment'], '/')) ?>" target="_blank" class="badge bg-primary-subtle text-primary border border-primary-subtle extra-small text-decoration-none" onclick="event.stopPropagation();" title="View Attachment / Supporting Document">
                                                    <i class="fa-solid fa-paperclip me-1"></i>Attachment Document
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        <span class="text-muted extra-small">Source Table:
                                            <code><?= htmlspecialchars($req['source_table']) ?></code></span>
                                    </td>

                                    <!-- Requested By -->
                                    <td>
                                        <div class="fw-bold text-dark extra-small">
                                            <?= htmlspecialchars($req['requester_name'] ?? 'N/A') ?>
                                        </div>
                                        <span class="badge bg-light text-secondary border extra-small">
                                            <?= !empty($req['requester_code']) ? htmlspecialchars($req['requester_code']) : 'null' ?>
                                        </span>
                                    </td>

                                    <!-- Requested Amount -->
                                    <td>
                                        <?php
                                        $displayAmount = $req['request_amount'];
                                        if ($sourceTable === 'advance_salaries' && $displayAmount <= 0) {
                                            $db = $GLOBALS['db'] ?? null;
                                            if ($db) {
                                                $res = $db->query("SELECT amount FROM advance_salaries WHERE id = " . (int) $req['source_id']);
                                                if ($res && $row = $res->fetch_assoc()) {
                                                    $displayAmount = $row['amount'];
                                                    // Self-heal the record in the DB
                                                    $db->query("UPDATE approval_requests SET request_amount = {$displayAmount} WHERE id = " . (int) $req['id']);
                                                }
                                            }
                                        }
                                        ?>
                                        <?php if ($displayAmount > 0): ?>
                                            <span class="fw-bold text-dark">৳
                                                <?= number_format($displayAmount, 2) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted extra-small">N/A</span>
                                        <?php endif; ?>
                                        <?php if ($sourceTable === 'vendor_payment_requests' && !empty($req['vpr_payment_type'])): ?>
                                            <?php
                                            $vprType = strtolower($req['vpr_payment_type']);
                                            $typeLabel = match ($vprType) {
                                                'advance' => 'Advance',
                                                'partial' => 'Partially',
                                                'final', 'full', 'fully' => 'Fully',
                                                default => ucfirst($vprType)
                                            };
                                            $typeClass = match ($vprType) {
                                                'advance' => 'bg-primary-subtle text-primary border-primary-subtle',
                                                'partial' => 'bg-warning-subtle text-warning border-warning-subtle',
                                                'final', 'full', 'fully' => 'bg-success-subtle text-success border-success-subtle',
                                                default => 'bg-secondary-subtle text-secondary border-secondary-subtle'
                                            };
                                            ?>
                                            <div class="mt-1"><span class="badge <?= $typeClass ?> border extra-small">
                                                    <?= $typeLabel ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Approved Amount -->
                                    <td>
                                        <?php 
                                        $appAmt = (float)($req['approved_amount'] ?? 0);
                                        $qcAmt = (float)($req['qc_approved_amount'] ?? 0);
                                        $isPendingMgmt = in_array(strtolower($req['approve_role'] ?? ''), ['management', 'operation_head']);
                                        ?>
                                        <?php if ($appAmt > 0): ?>
                                            <span class="fw-bold <?= ($qcAmt > 0 && $appAmt == $qcAmt && $isPendingMgmt) ? 'text-primary' : 'text-success' ?>">
                                                ৳ <?= number_format($appAmt, 2) ?>
                                            </span>
                                            <?php if ($qcAmt > 0 && $appAmt == $qcAmt && $isPendingMgmt): ?>
                                                <div class="mt-1"><span class="badge bg-info-subtle text-info border border-info-subtle extra-small" style="font-size: 9px; padding: 2px 6px;"><i class="bi bi-shield-check me-1"></i>QC Approved</span></div>
                                            <?php endif; ?>
                                        <?php elseif ($qcAmt > 0): ?>
                                            <span class="fw-bold text-primary">৳ <?= number_format($qcAmt, 2) ?></span>
                                            <div class="mt-1"><span class="badge bg-info-subtle text-info border border-info-subtle extra-small" style="font-size: 9px; padding: 2px 6px;"><i class="bi bi-shield-check me-1"></i>QC Approved</span></div>
                                        <?php else: ?>
                                            <span class="text-muted extra-small">--</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Approval Status & Pending At -->
                                    <td class="text-center">
                                        <?php if ($req['status'] === 'pending'): ?>
                                            <?php
                                            $stageLabel = str_replace(['\u0026', '&amp;'], ['&', '&'], $req['pending_stage_label'] ?? 'Pending Review');
                                            $pendingFor = str_replace(['\u0026', '&amp;'], ['&', '&'], $req['pending_for_text'] ?? 'Management');
                                            $pendingFor = trim(preg_replace('/\s*\(Role[^\)]*\)/i', '', $pendingFor));

                                            $badgeClass = 'bg-warning text-dark';
                                            if (stripos($stageLabel, 'QC') !== false || stripos($stageLabel, 'Audit') !== false) {
                                                $badgeClass = 'bg-info text-dark';
                                            } elseif (stripos($stageLabel, 'Dept') !== false) {
                                                $badgeClass = 'bg-secondary text-white';
                                            } elseif (stripos($stageLabel, 'HR') !== false) {
                                                $badgeClass = 'bg-primary text-white';
                                            } elseif (stripos($stageLabel, 'Purchase') !== false) {
                                                $badgeClass = 'bg-secondary text-white';
                                            } elseif (stripos($stageLabel, 'PM') !== false) {
                                                $badgeClass = 'bg-info text-white';
                                            } elseif (stripos($stageLabel, 'Management') !== false) {
                                                $badgeClass = 'bg-warning text-dark';
                                            } elseif (stripos($stageLabel, 'Accounts') !== false) {
                                                $badgeClass = 'bg-success text-white';
                                            }
                                            ?>
                                            <span class="badge <?= $badgeClass ?> rounded-pill px-3 py-1 extra-small fw-semibold">
                                                <i class="fa-solid fa-clock me-1"></i><?= htmlspecialchars($stageLabel) ?>
                                            </span>
                                            <div class="extra-small text-dark mt-1">
                                                <i class="fa-solid fa-hourglass-half text-warning me-1"></i><strong>Pending at:</strong>
                                                <span class="text-primary fw-semibold d-block"><?= htmlspecialchars($pendingFor) ?></span>
                                            </div>
                                            <?php if (strtolower($req['op_head_status'] ?? '') === 'approved' || !empty($req['op_head_by'])): ?>
                                                <div class="mt-2 pt-1 border-top border-success-subtle text-start">
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle extra-small fw-semibold d-inline-block mb-1" style="font-size: 9.5px; padding: 2px 6px;">
                                                        <i class="fa-solid fa-circle-check me-1"></i>Last Approved: Operation Head
                                                    </span>
                                                    <div class="extra-small text-dark fw-bold" style="font-size: 11px;">
                                                        <i class="fa-solid fa-user-check text-success me-1"></i><?= htmlspecialchars(!empty($req['op_head_name']) ? $req['op_head_name'] : 'Admin & Operation Head') ?>
                                                        <?php if (!empty($req['op_head_code'])): ?>
                                                            <span class="badge bg-light text-secondary border extra-small"><?= htmlspecialchars($req['op_head_code']) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if (!empty($req['op_head_at'])): ?>
                                                        <span class="text-muted extra-small d-block" style="font-size: 10px;">
                                                            <i class="fa-solid fa-calendar-check me-1"></i><?= date('d M, Y h:i A', strtotime($req['op_head_at'])) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php elseif (in_array($req['status'], ['approved', 'partially_approved', 'partially_paid'])): ?>
                                            <span
                                                class="badge <?= $req['status'] === 'approved' ? 'bg-success' : 'bg-info' ?> text-white rounded-pill px-3 py-1 extra-small d-block mb-1">
                                                <i class="fa-solid fa-user-shield me-1"></i>
                                                <?= $req['status'] === 'approved' ? 'Approved from Management' : 'Partially Approved' ?>
                                            </span>
                                            <div class="fw-bold text-dark extra-small">
                                                <i class="fa-solid fa-user-check text-success me-1"></i>
                                                <?= htmlspecialchars($req['approver_name'] ?? 'Management') ?>
                                            </div>
                                            <?php if (!empty($req['approver_designation'])): ?>
                                                <span
                                                    class="badge bg-primary-subtle text-primary border border-primary-subtle extra-small d-block my-1">
                                                    <?= htmlspecialchars($req['approver_designation']) ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($req['approver_email'])): ?>
                                                <span class="text-muted extra-small d-block"><i class="fa-solid fa-envelope me-1"></i>
                                                    <?= htmlspecialchars($req['approver_email']) ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($req['approved_at']): ?>
                                                <span class="text-muted extra-small d-block"><i
                                                        class="fa-solid fa-calendar-check me-1"></i>
                                                    <?= date('d M, Y h:i A', strtotime($req['approved_at'])) ?>
                                                </span>
                                            <?php endif; ?>

                                            <?php if ($isPendingAccountsHead): ?>
                                                <div class="mt-2 pt-2 border-top border-warning-subtle text-start">
                                                    <span class="badge bg-warning text-dark rounded-pill px-2 py-1 extra-small d-inline-block mb-1 fw-bold">
                                                        <i class="fa-solid fa-hourglass-half me-1"></i>Pending Accounts Head
                                                    </span>
                                                    <div class="extra-small text-dark fw-bold">
                                                        <i class="fa-solid fa-user-clock text-warning me-1"></i>Md. Mamunoor Rashid
                                                        <span class="badge bg-light text-secondary border extra-small">1309</span>
                                                    </div>
                                                    <span class="text-muted extra-small d-block">AGM - Finance & Accounts</span>
                                                </div>
                                            <?php elseif ($isAccountsHeadApproved): ?>
                                                <div class="mt-2 pt-2 border-top border-primary-subtle text-start">
                                                    <span class="badge bg-primary text-white rounded-pill px-2 py-1 extra-small d-inline-block mb-1 fw-bold">
                                                        <i class="fa-solid fa-user-check me-1"></i>Accounts Head Approved
                                                    </span>
                                                    <div class="extra-small text-dark fw-bold">
                                                        <i class="fa-solid fa-check-double text-primary me-1"></i><?= htmlspecialchars(!empty($req['accounts_head_name']) ? $req['accounts_head_name'] : 'Md. Mamunoor Rashid') ?>
                                                        <span class="badge bg-light text-secondary border extra-small"><?= htmlspecialchars(!empty($req['accounts_head_employee_code']) ? $req['accounts_head_employee_code'] : '1309') ?></span>
                                                    </div>
                                                    <span class="text-muted extra-small d-block"><?= htmlspecialchars(!empty($req['accounts_head_designation']) ? $req['accounts_head_designation'] : 'AGM - Finance & Accounts') ?></span>
                                                    <?php if (!empty($req['accounts_head_at'])): ?>
                                                        <span class="text-muted extra-small d-block"><i class="fa-solid fa-calendar-check me-1"></i><?= date('d M, Y h:i A', strtotime($req['accounts_head_at'])) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php elseif ($req['status'] === 'rejected'): ?>
                                            <span
                                                class="badge bg-danger text-white rounded-pill px-3 py-1 extra-small d-block mb-1">
                                                <i class="fa-solid fa-circle-xmark me-1"></i>Rejected
                                            </span>
                                            <span class="extra-small text-muted">By:
                                                <?= htmlspecialchars($req['approver_name'] ?? 'Management') ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Accounts Payout Status -->
                                    <td class="text-center">
                                        <?php if ($req['payout_status'] === 'Paid & Settled'): ?>
                                            <span
                                                class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 extra-small d-block mb-1">
                                                <i class="fa-solid fa-money-check-dollar me-1"></i>Paid & Settled
                                            </span>
                                            <span class="extra-small fw-bold text-success">৳
                                                <?= number_format($req['payout_amount'], 2) ?>
                                            </span>
                                            <?php if (!empty($req['disbursed_by_name'])): ?>
                                                <div class="extra-small text-dark fw-semibold mt-1" title="Disbursed by <?= htmlspecialchars($req['disbursed_by_name']) ?> (<?= htmlspecialchars($req['disbursed_by_code'] ?? '') ?>)">
                                                    <i class="fa-solid fa-user-check text-success me-1"></i><?= htmlspecialchars($req['disbursed_by_name']) ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($req['disbursed_branch_name'])): ?>
                                                <div>
                                                    <span class="badge bg-light text-secondary border px-1 py-0 mt-1" style="font-size: 10px;">
                                                        <i class="fa-solid fa-building me-1 text-muted"></i><?= htmlspecialchars($req['disbursed_branch_name']) ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($req['payout_date']): ?>
                                                <div class="extra-small text-muted mt-1">
                                                    <?= date('d M, Y', strtotime($req['payout_date'])) ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php elseif ($req['payout_status'] === 'Partially Paid'): ?>
                                            <span
                                                class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 extra-small d-block mb-1">
                                                <i class="fa-solid fa-money-check-dollar me-1"></i>Partially Paid
                                            </span>
                                            <span class="extra-small fw-bold text-success">৳
                                                <?= number_format($req['payout_amount'], 2) ?>
                                            </span>
                                            <?php if (!empty($req['disbursed_by_name'])): ?>
                                                <div class="extra-small text-dark fw-semibold mt-1" title="Disbursed by <?= htmlspecialchars($req['disbursed_by_name']) ?> (<?= htmlspecialchars($req['disbursed_by_code'] ?? '') ?>)">
                                                    <i class="fa-solid fa-user-check text-success me-1"></i><?= htmlspecialchars($req['disbursed_by_name']) ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($req['disbursed_branch_name'])): ?>
                                                <div>
                                                    <span class="badge bg-light text-secondary border px-1 py-0 mt-1" style="font-size: 10px;">
                                                        <i class="fa-solid fa-building me-1 text-muted"></i><?= htmlspecialchars($req['disbursed_branch_name']) ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($req['payout_date']): ?>
                                                <div class="extra-small text-muted mt-1">
                                                    <?= date('d M, Y', strtotime($req['payout_date'])) ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php elseif ($isPendingAccountsHead): ?>
                                            <span
                                                class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-1 extra-small">
                                                <i class="fa-solid fa-user-clock me-1"></i>Pending Accounts Head Approval
                                            </span>
                                        <?php elseif ($req['payout_status'] === 'Pending Accounts Payout' || $req['status'] === 'partially_approved'): ?>
                                            <span
                                                class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-1 extra-small">
                                                <i class="fa-solid fa-wallet me-1"></i>Pending Accounts Payout
                                            </span>
                                        <?php elseif (in_array($req['status'], ['approved', 'partially_approved'])): ?>
                                            <span
                                                class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-3 py-1 extra-small">
                                                <i class="fa-solid fa-check me-1"></i>Approved
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border extra-small">N/A</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Action Button (Directly routes to specific module page or Debit Voucher!) -->
                                    <td class="pe-4 text-end text-nowrap">
                                        <?php
                                        $isVoucherSupported = true;
                                        $voucherUrl = $base_url . '/print-debit-voucher?module=' . urlencode($sourceTable) . '&id=' . (int)$req['source_id'];
                                        $isPaidOrSettled = in_array($payoutStatus, ['Paid & Settled', 'Settled']) || in_array(strtolower($req['status']), ['paid', 'fully_paid', 'labor_disbursed', 'deducted']);
                                        $isPartiallyPaid = ($payoutStatus === 'Partially Paid') || strtolower($req['status']) === 'partially_paid';
                                        ?>

                                        <?php if ($isVoucherSupported && !$isPaidOrSettled): ?>
                                            <a href="<?= $voucherUrl ?>&is_approval=1" target="_blank" class="btn btn-sm btn-outline-info rounded-2 px-2 shadow-sm fw-semibold mb-1" title="View & Print Approval Voucher">
                                                <i class="fa-solid fa-file-signature me-1"></i>Approval Voucher
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($isPartiallyPaid): ?>
                                            <?php if ($isAccountsUser && $isAccountsHeadApproved): ?>
                                                <a href="<?= $targetUrl ?>" class="btn btn-sm btn-success rounded-2 px-2 shadow-sm fw-semibold me-1" title="Disburse remaining amount">
                                                    <i class="fa-solid fa-money-check-dollar me-1"></i>Pay
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($isVoucherSupported): ?>
                                                <a href="<?= $voucherUrl ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-2 px-2 shadow-sm fw-semibold" title="View, Print & Download Payment Voucher">
                                                    <i class="fa-solid fa-file-invoice-dollar me-1"></i>Payment Voucher
                                                </a>
                                            <?php endif; ?>

                                        <?php elseif ($isPaidOrSettled): ?>
                                            <?php if ($isVoucherSupported): ?>
                                                <a href="<?= $voucherUrl ?>" target="_blank" class="btn btn-sm btn-primary rounded-2 px-3 shadow-sm fw-semibold me-1" title="View, Print & Download Payment Voucher">
                                                    <i class="fa-solid fa-file-invoice-dollar me-1"></i>Payment Voucher
                                                </a>
                                            <?php endif; ?>
                                            <a href="<?= $targetUrl ?>" class="btn btn-sm btn-light text-dark rounded-2 px-2 border" title="View Module Page">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>

                                        <?php elseif ($isPendingAccountsHead): ?>
                                            <?php if ($isAccountsHeadUser || $isManagementUser): ?>
                                                <button type="button" class="btn btn-sm btn-primary rounded-2 px-3 shadow-sm fw-semibold me-1" 
                                                    onclick="openAccountsHeadApprovalModal(<?= (int)$req['id'] ?>, '<?= htmlspecialchars(addslashes($req['title'] ?? '')) ?>', '<?= number_format($req['approved_amount'] > 0 ? $req['approved_amount'] : $req['request_amount'], 2) ?>')"
                                                    title="Approve as Accounts Head (Md. Mamunoor Rashid [1309])">
                                                    <i class="fa-solid fa-stamp me-1"></i>Approve (Accounts Head)
                                                </button>
                                                <a href="<?= $targetUrl ?>" class="btn btn-sm btn-light text-dark rounded-2 px-2 border" title="View Module Page">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            <?php elseif ($isAccountsStaffUser): ?>
                                                <span class="badge bg-light text-muted border rounded-2 px-2 py-1 extra-small me-1" title="Payment locked until Accounts Head (Md. Mamunoor Rashid [1309]) approves">
                                                    <i class="fa-solid fa-lock text-warning me-1"></i>Awaiting Accounts Head
                                                </span>
                                                <a href="<?= $targetUrl ?>" class="btn btn-sm btn-light text-dark rounded-2 px-2 border" title="View Module Page">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= $targetUrl ?>" class="btn btn-sm btn-light text-dark rounded-2 px-2 border" title="View Module Page">
                                                    <i class="fa-solid fa-eye me-1"></i>View Module Page
                                                </a>
                                            <?php endif; ?>

                                        <?php elseif ($isAccountsUser && in_array($payoutStatus, ['Pending Accounts Payout']) && $isAccountsHeadApproved): ?>
                                            <a href="<?= $targetUrl ?>" class="btn btn-sm btn-success rounded-2 px-3 shadow-sm fw-semibold">
                                                <i class="fa-solid fa-money-check-dollar me-1"></i>Pay / Disburse
                                            </a>

                                        <?php elseif (in_array($req['status'], ['pending', 'partially_approved'])): ?>
                                            <?php if ($isChiefOfficer): ?>
                                                <a href="<?= !empty($targetUrl) && $targetUrl !== 'javascript:void(0)' ? $targetUrl : 'javascript:void(0)' ?>"
                                                    class="btn btn-sm btn-light text-dark rounded-2 px-2 border"
                                                    title="Pending for <?= htmlspecialchars($req['pending_for_text'] ?? 'Management') ?>">
                                                    <i class="fa-solid fa-eye me-1"></i>View Details
                                                </a>
                                            <?php elseif ($canUserApprove): ?>
                                                <?php if ($sourceTable === 'lead_bookings'): ?>
                                                    <button type="button" class="btn btn-sm btn-primary rounded-2 px-3 shadow-sm fw-semibold"
                                                        onclick="openDiscountApprovalModal(<?= htmlspecialchars(json_encode([
                                                            'id' => (int)$req['id'],
                                                            'source_id' => (int)$req['source_id'],
                                                            'title' => $req['title'] ?? 'Lead Booking Discount Approval',
                                                            'amount' => (float)$req['request_amount'],
                                                            'client_name' => $req['booking_client_name'] ?? '',
                                                            'project_name' => $req['booking_project_name'] ?? '',
                                                            'flat_name' => $req['booking_flat_name'] ?? '',
                                                            'floor_no' => $req['booking_floor_no'] ?? '',
                                                            'sales_agent' => $req['booking_sales_agent'] ?? '',
                                                            'sales_code' => $req['booking_sales_code'] ?? '',
                                                            'agreement_value' => $req['booking_agreement_value'] ?? 0,
                                                            'attachment' => $req['attachment'] ?? '',
                                                            'op_head_status' => $req['op_head_status'] ?? '',
                                                            'op_head_name' => $req['op_head_name'] ?? '',
                                                            'op_head_at' => $req['op_head_at'] ?? '',
                                                            'status' => $req['status'] ?? '',
                                                            'booking_url' => $targetUrl
                                                        ])) ?>)"
                                                        title="Review & Process Booking Discount">
                                                        <i class="fa-solid fa-stamp me-1"></i>Review / Process
                                                    </button>
                                                <?php else: ?>
                                                    <a href="<?= $targetUrl ?>" class="btn btn-sm btn-primary rounded-2 px-3 shadow-sm fw-semibold">
                                                        <i class="fa-solid fa-up-right-from-square me-1"></i>Review / Process
                                                    </a>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <a href="javascript:void(0)"
                                                    class="btn btn-sm btn-light text-muted rounded-2 px-2 border"
                                                    title="<?= ($reqAmount > 100000.01 && ($req['op_head_status'] ?? '') !== 'approved' && $isManagementUser) ? 'Amount exceeds ৳1,00,000 - Admin & Operation Head (Role 20) approval required first' : 'Pending for ' . htmlspecialchars($req['pending_for_text'] ?? 'Other Role') ?>">
                                                    <i class="fa-solid fa-lock me-1 text-warning"></i><?= ($reqAmount > 100000.01 && ($req['op_head_status'] ?? '') !== 'approved' && $isManagementUser) ? 'Awaiting Op Head' : 'Pending Other Role' ?>
                                                </a>
                                            <?php endif; ?>

                                        <?php else: ?>
                                            <a href="<?= $targetUrl ?>"
                                                class="btn btn-sm btn-light text-dark rounded-2 px-2 border">
                                                <i class="fa-solid fa-eye me-1"></i>View Module Page
                                            </a>
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

<!-- Accounts Head Quick Approval Modal -->
<div class="modal fade" id="accountsHeadApprovalModal" tabindex="-1" aria-labelledby="accountsHeadApprovalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title fw-bold" id="accountsHeadApprovalModalLabel">
                    <i class="fa-solid fa-stamp me-2"></i>Accounts Head Approval
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="p-3 bg-light rounded-3 mb-3 border">
                    <div class="extra-small text-muted text-uppercase fw-bold mb-1">Requisition / Request Title</div>
                    <div class="fw-bold text-dark fs-14" id="ahModalReqTitle">--</div>
                    <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                        <span class="extra-small text-muted">Approved Amount:</span>
                        <span class="fs-15 fw-bold text-success" id="ahModalReqAmount">৳ 0.00</span>
                    </div>
                </div>

                <div class="alert alert-info py-2 px-3 extra-small mb-3 border-info-subtle">
                    <i class="fa-solid fa-user-shield me-1"></i>
                    <strong>Designated Approver:</strong> Md. Mamunoor Rashid [1309] (AGM - Finance & Accounts)
                </div>

                <input type="hidden" id="ahModalReqId" value="">
                
                <div class="mb-3" id="ahModalCoaContainer">
                    <label for="ahModalCoaId" class="form-label extra-small fw-bold text-muted text-uppercase">Chart of Accounts Category (Required for Posting)</label>
                    <select class="form-select form-select-sm ah-coa-select2" id="ahModalCoaId" name="coa_id">
                        <option value="">-- Select Expense / Asset Ledger --</option>
                        <?php if(!empty($coaList)): ?>
                            <?php 
                            $currentGroup = '';
                            foreach($coaList as $coa): 
                                $group = $coa['account_type'];
                                if($group !== $currentGroup):
                                    if($currentGroup !== '') echo '</optgroup>';
                                    echo '<optgroup label="'.htmlspecialchars($group).'">';
                                    $currentGroup = $group;
                                endif;
                            ?>
                                <option value="<?= $coa['id'] ?>"><?= htmlspecialchars($coa['account_code'] . ' - ' . $coa['account_name']) ?></option>
                            <?php endforeach; ?>
                            <?php if($currentGroup !== '') echo '</optgroup>'; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="ahModalNote" class="form-label extra-small fw-bold text-muted text-uppercase">Approval / Rejection Note (Optional)</label>
                    <textarea class="form-control" id="ahModalNote" rows="2" placeholder="e.g. Verified and approved for payment disbursement."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light px-4 py-3 d-flex justify-content-between">
                <button type="button" class="btn btn-outline-danger btn-sm px-3" onclick="submitAccountsHeadAction('reject')">
                    <i class="fa-solid fa-times me-1"></i>Reject Request
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm px-4 fw-bold" id="ahModalSubmitBtn" onclick="submitAccountsHeadAction('approve')">
                        <i class="fa-solid fa-check me-1"></i>Approve as Accounts Head
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Lead Booking Discount Approval Modal -->
<div class="modal fade" id="discountApprovalModal" tabindex="-1" aria-labelledby="discountApprovalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">
            <div class="modal-header text-white px-4 py-3" style="background: linear-gradient(135deg, #006837 0%, #004d29 100%);">
                <div>
                    <h6 class="modal-title fw-bold fs-16 mb-0" id="discountApprovalModalLabel">
                        <i class="fa-solid fa-tags me-2 text-warning"></i>Review & Process Booking Discount
                    </h6>
                    <small class="text-white-50" id="discModalReqCode">#REQ-00000</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="discModalReqId" value="">
                <input type="hidden" id="discModalBookingId" value="">

                <!-- Workflow Step Notice -->
                <div id="discModalWorkflowNotice" class="alert alert-warning py-2 px-3 extra-small mb-3 border-warning-subtle">
                    <!-- Dynamic -->
                </div>

                <!-- Client & Unit Info Grid -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <div class="text-muted extra-small text-uppercase fw-bold mb-2 pb-1 border-bottom"><i class="fa-solid fa-user me-1 text-primary"></i> Client Details</div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted extra-small">Client Name:</span>
                                <span class="fw-bold text-dark extra-small text-end" id="discModalClient">--</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted extra-small">Project:</span>
                                <span class="fw-semibold text-dark extra-small text-end" id="discModalProject">--</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted extra-small">Flat / Floor:</span>
                                <span class="fw-semibold text-dark extra-small text-end" id="discModalFlat">--</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <div class="text-muted extra-small text-uppercase fw-bold mb-2 pb-1 border-bottom"><i class="fa-solid fa-receipt me-1 text-success"></i> Financial & Sales Info</div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted extra-small">Sales Agent:</span>
                                <span class="fw-semibold text-dark extra-small text-end" id="discModalSalesAgent">--</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted extra-small">Agreement Value:</span>
                                <span class="fw-bold text-dark extra-small text-end" id="discModalAgreement">--</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center pt-1 border-top">
                                <span class="text-muted extra-small fw-bold">Requested Discount:</span>
                                <span class="fs-15 fw-bold text-success" id="discModalAmount">৳ 0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attachment Container -->
                <div class="mb-3" id="discModalAttachmentContainer" style="display: none;">
                    <!-- Dynamic Attachment -->
                </div>

                <!-- Note Input -->
                <div class="mb-2">
                    <label for="discModalNote" class="form-label extra-small fw-bold text-muted text-uppercase mb-1">Approval / Rejection Note</label>
                    <textarea class="form-control" id="discModalNote" rows="2" placeholder="Enter optional comments or instructions..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light px-4 py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-danger btn-sm px-3" onclick="submitDiscountAction('reject')">
                        <i class="fa-solid fa-times me-1"></i>Reject Discount
                    </button>
                    <a href="#" id="discModalHistoryLink" target="_blank" class="btn btn-outline-info btn-sm px-3" title="Open client transaction & cash-in history">
                        <i class="fa-solid fa-up-right-from-square me-1"></i>Client History
                    </a>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success btn-sm px-4 fw-bold" id="discModalSubmitBtn" onclick="submitDiscountAction('approve')">
                        <i class="fa-solid fa-check me-1"></i>Approve Discount
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let approvalTable;

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof $.fn.DataTable !== 'undefined' && $('#allApprovalRequestsTable tbody tr td[colspan]').length === 0) {
            if ($.fn.DataTable.isDataTable('#allApprovalRequestsTable')) {
                $('#allApprovalRequestsTable').DataTable().destroy();
            }
            approvalTable = $('#allApprovalRequestsTable').DataTable({
                pageLength: 25,
                lengthMenu: [[25, 50, 70, 100, 200, -1], [25, 50, 70, 100, 200, "All"]],
                order: [],
                dom: '<"d-flex justify-content-between align-items-center p-3"f>rt<"d-flex justify-content-between align-items-center p-3"ip>',
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search approval requests..."
                }
            });

            // Dynamically update top mini card counts when searching or filtering
            approvalTable.on('search.dt draw.dt', function () {
                const searchVal = approvalTable.search();
                if (!searchVal || searchVal.trim() === '') {
                    // Restore original counts
                    $('.module-mini-card').each(function() {
                        const origCount = parseInt($(this).attr('data-pending-count')) || 0;
                        const $badge = $(this).find('.mod-badge-count');
                        if (origCount > 0) {
                            $badge.removeClass('bg-light text-muted border bg-primary text-white')
                                  .addClass('bg-warning text-dark')
                                  .text(origCount + ' Req');
                        } else {
                            $badge.removeClass('bg-warning text-dark bg-primary text-white')
                                  .addClass('bg-light text-muted border')
                                  .text('0');
                        }
                    });
                } else {
                    // Count matching rows per module
                    const moduleCounts = {};
                    approvalTable.rows({ search: 'applied' }).every(function () {
                        const node = this.node();
                        const srcTable = $(node).attr('data-source-table') || '';
                        if (srcTable) {
                            moduleCounts[srcTable] = (moduleCounts[srcTable] || 0) + 1;
                        }
                    });

                    $('.module-mini-card').each(function() {
                        const modKey = $(this).attr('data-mod-key');
                        const count = moduleCounts[modKey] || 0;
                        const $badge = $(this).find('.mod-badge-count');
                        if (count > 0) {
                            $badge.removeClass('bg-light text-muted border bg-warning text-dark')
                                  .addClass('bg-primary text-white')
                                  .text(count + ' found');
                        } else {
                            $badge.removeClass('bg-warning text-dark bg-primary text-white')
                                  .addClass('bg-light text-muted border')
                                  .text('0');
                        }
                    });
                }
            });
        }

        // Row click navigates to requisition view page (skipping buttons, inputs, links, checkboxes)
        $('#allApprovalRequestsTable tbody').on('click', 'tr.clickable-approval-row', function (e) {
            if ($(e.target).closest('input, button, a, .form-check, .dropdown-menu, .modal').length) {
                return;
            }
            const href = $(this).attr('data-href');
            if (href) {
                window.location.href = href;
            }
        });
    });

    function changeApprovalTablePageLength(val) {
        if (approvalTable) {
            approvalTable.page.len(parseInt(val)).draw();
        }
    }

    function toggleSelectAllApprovalRequests(master) {
        const rows = approvalTable ? approvalTable.rows({ search: 'applied' }).nodes() : document.querySelectorAll('#allApprovalRequestsTable tbody tr');
        $(rows).find('.approval-req-checkbox:not(:disabled)').prop('checked', master.checked);
        updateBulkApprovalCount();
    }

    function getCheckedApprovalCheckboxes() {
        if (approvalTable) {
            return approvalTable.$('.approval-req-checkbox:checked');
        }
        return $('.approval-req-checkbox:checked');
    }

    function updateBulkApprovalCount() {
        const checkedCount = getCheckedApprovalCheckboxes().length;
        const badge = document.getElementById('selectedApprovalCountBadge');
        if (badge) {
            badge.innerText = checkedCount + ' Selected';
        }
    }

    function processBulkApprovalAction(action) {
        const selectedIds = [];
        getCheckedApprovalCheckboxes().each(function () {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            alert('Please select at least one pending approval request.');
            return;
        }

        const actionTitle = action === 'reject' ? 'REJECT' : 'APPROVE';
        if (!confirm(`Are you sure you want to BULK ${actionTitle} ${selectedIds.length} selected request(s)?`)) {
            return;
        }

        fetch('<?= $base_url ?>/batch-process-approval-requests', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                request_ids: selectedIds,
                action: action,
                note: 'Bulk ' + actionTitle + ' from Management All Approval Requests Dashboard'
            })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to process bulk approval.');
                }
            })
            .catch(err => {
                alert('Error processing bulk approval: ' + err);
            });
    }

    function openAccountsHeadApprovalModal(reqId, title, amount) {
        document.getElementById('ahModalReqId').value = reqId;
        document.getElementById('ahModalReqTitle').innerText = title;
        document.getElementById('ahModalReqAmount').innerText = '৳ ' + amount;
        document.getElementById('ahModalNote').value = '';
        const modal = new bootstrap.Modal(document.getElementById('accountsHeadApprovalModal'));
        modal.show();
    }

    function submitAccountsHeadAction(action) {
        const reqId = document.getElementById('ahModalReqId').value;
        const note = document.getElementById('ahModalNote').value;
        const btn = document.getElementById('ahModalSubmitBtn');

        if (action === 'reject') {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Confirm Rejection',
                    text: 'Are you sure you want to REJECT this request as Accounts Head?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Reject Request'
                }).then((result) => {
                    if (result.isConfirmed) {
                        executeAhAction(reqId, note, btn, action);
                    }
                });
                return;
            } else if (!confirm('Are you sure you want to REJECT this request as Accounts Head?')) {
                return;
            }
        }

        executeAhAction(reqId, note, btn, action);
    }

    function executeAhAction(reqId, note, btn, action) {

        const endpoint = action === 'reject' 
            ? '<?= $base_url ?>/approval-requests/reject-accounts-head' 
            : '<?= $base_url ?>/approval-requests/approve-accounts-head';

        const coaId = document.getElementById('ahModalCoaId') ? document.getElementById('ahModalCoaId').value : null;

        if (action === 'approve' && !coaId) {
            alert('Please select a Chart of Accounts category before approving.');
            return;
        }

        if (btn) btn.disabled = true;

        fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                approval_request_id: reqId,
                note: note || (action === 'reject' ? 'Rejected by Accounts Head' : 'Approved by Accounts Head'),
                coa_id: coaId
            })
        })
        .then(res => res.json())
        .then(data => {
            if (btn) btn.disabled = false;
            if (data.success) {
                const modalEl = document.getElementById('accountsHeadApprovalModal');
                if (modalEl) {
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: (action === 'reject') ? 'Rejected!' : 'Approved!',
                        text: data.message || 'Accounts Head action completed.',
                        timer: 1200,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    window.location.reload();
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Operation Failed',
                        text: data.message || 'Operation failed'
                    });
                } else if (typeof toastr !== 'undefined') {
                    toastr.error(data.message || 'Operation failed');
                } else {
                    console.error('Operation failed:', data.message);
                }
            }
        })
        .catch(err => {
            if (btn) btn.disabled = false;
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error: ' + err
                });
            } else if (typeof toastr !== 'undefined') {
                toastr.error('Error: ' + err);
            } else {
                console.error('Error:', err);
            }
        });
    }

    // =========================================================================
    // Booking Discount Approval Modal Handlers
    // =========================================================================
    let currentDiscountData = null;
    const currentLoggedRoleId = <?= (int)($_SESSION['user']['role_id'] ?? 0) ?>;
    const currentLoggedRoleName = <?= json_encode(strtolower(trim($_SESSION['user']['role_name'] ?? ''))) ?>;

    function openDiscountApprovalModal(data) {
        currentDiscountData = data;
        document.getElementById('discModalReqId').value = data.id;
        document.getElementById('discModalBookingId').value = data.source_id;
        document.getElementById('discModalReqCode').textContent = '#REQ-' + String(data.id).padStart(5, '0');
        document.getElementById('discModalClient').textContent = data.client_name || 'N/A';
        document.getElementById('discModalProject').textContent = data.project_name || 'N/A';
        
        let flatText = '';
        if (data.flat_name) flatText += 'UNIT ' + data.flat_name;
        if (data.floor_no) flatText += (flatText ? ' - ' : '') + 'FLOOR ' + data.floor_no;
        document.getElementById('discModalFlat').textContent = flatText || 'N/A';

        let agentText = data.sales_agent || 'N/A';
        if (data.sales_code) agentText += ' [' + data.sales_code + ']';
        document.getElementById('discModalSalesAgent').textContent = agentText;

        document.getElementById('discModalAgreement').textContent = '৳ ' + parseFloat(data.agreement_value || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('discModalAmount').textContent = '৳ ' + parseFloat(data.amount || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('discModalNote').value = '';

        const historyLink = document.getElementById('discModalHistoryLink');
        if (historyLink) {
            historyLink.href = data.booking_url || ('<?= $base_url ?>/clients-cash-in-history-view/' + data.source_id + '?ref=all_approvals');
        }

        const attContainer = document.getElementById('discModalAttachmentContainer');
        if (data.attachment) {
            const cleanAtt = data.attachment.replace(/^\/+/, '');
            const attUrl = '<?= $base_url ?>/' + cleanAtt;
            const isImage = cleanAtt.match(/\.(jpeg|jpg|png|gif|webp)$/i);
            if (isImage) {
                attContainer.innerHTML = `
                    <div class="p-2 border rounded bg-white shadow-sm">
                        <div class="d-flex align-items-center justify-content-between mb-1 pb-1 border-bottom">
                            <span class="extra-small fw-bold text-muted"><i class="fa-solid fa-paperclip text-success me-1"></i> Discount Supporting Document</span>
                            <a href="${attUrl}" target="_blank" class="btn btn-xs btn-outline-primary py-0 px-2 extra-small"><i class="fa-solid fa-up-right-from-square me-1"></i> Full Size</a>
                        </div>
                        <div class="text-center mt-2">
                            <a href="${attUrl}" target="_blank">
                                <img src="${attUrl}" alt="Discount Attachment" class="img-fluid rounded border shadow-sm" style="max-height: 160px; object-fit: contain;">
                            </a>
                        </div>
                    </div>
                `;
            } else {
                attContainer.innerHTML = `
                    <div class="p-2 border rounded bg-white shadow-sm d-flex align-items-center justify-content-between">
                        <span class="extra-small fw-bold text-muted"><i class="fa-solid fa-file-pdf text-danger me-1"></i> Discount Attachment Document</span>
                        <a href="${attUrl}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-download me-1"></i> View / Download Document</a>
                    </div>
                `;
            }
            attContainer.style.display = 'block';
        } else {
            attContainer.innerHTML = '';
            attContainer.style.display = 'none';
        }

        const isOver1Lac = (parseFloat(data.amount) > 100000.01);
        const isOpHeadApproved = (String(data.op_head_status).toLowerCase() === 'approved');
        const noticeEl = document.getElementById('discModalWorkflowNotice');
        const submitBtn = document.getElementById('discModalSubmitBtn');

        const isRole20 = (currentLoggedRoleId === 20 || currentLoggedRoleId === 32 || currentLoggedRoleName.includes('operation head') || currentLoggedRoleName.includes('admin & operation'));
        const isManagement = (currentLoggedRoleId === 1 || currentLoggedRoleId === 3 || currentLoggedRoleName.includes('management') || currentLoggedRoleName.includes('super admin'));

        if (isOver1Lac) {
            if (!isOpHeadApproved) {
                if (isRole20 && !isManagement) {
                    noticeEl.className = 'alert alert-warning py-2 px-3 extra-small mb-3 border-warning-subtle';
                    noticeEl.innerHTML = '<i class="fa-solid fa-shield-halved me-1 text-warning"></i> <strong>Tier 1 Verification:</strong> Discount exceeds ৳1,00,000. Approving will verify and forward this request to Management (Role 3).';
                    submitBtn.innerHTML = '<i class="fa-solid fa-shield-check me-1"></i> Verify & Forward to Management';
                    submitBtn.className = 'btn btn-success btn-sm px-4 fw-bold';
                    submitBtn.disabled = false;
                } else if (isManagement) {
                    noticeEl.className = 'alert alert-info py-2 px-3 extra-small mb-3 border-info-subtle';
                    noticeEl.innerHTML = '<i class="fa-solid fa-crown me-1 text-primary"></i> <strong>Direct Management Approval:</strong> Discount exceeds ৳1,00,000. As Top Management, you can approve directly (Operation Head verification will be bypassed automatically).';
                    submitBtn.innerHTML = '<i class="fa-solid fa-check-double me-1"></i> Approve Discount (Management Direct)';
                    submitBtn.className = 'btn btn-success btn-sm px-4 fw-bold';
                    submitBtn.disabled = false;
                } else {
                    noticeEl.className = 'alert alert-warning py-2 px-3 extra-small mb-3 border-warning-subtle';
                    noticeEl.innerHTML = '<i class="fa-solid fa-lock me-1"></i> Pending Admin & Operation Head verification.';
                    submitBtn.innerHTML = '<i class="fa-solid fa-lock me-1"></i> Action Restricted';
                    submitBtn.className = 'btn btn-secondary btn-sm px-4 fw-bold';
                    submitBtn.disabled = true;
                }
            } else {
                noticeEl.className = 'alert alert-info py-2 px-3 extra-small mb-3 border-info-subtle';
                noticeEl.innerHTML = '<i class="fa-solid fa-check-circle me-1 text-success"></i> <strong>Tier 1 Approved:</strong> Verified & approved by Admin & Operation Head' + (data.op_head_name ? (' (' + data.op_head_name + ')') : '') + '. Ready for Top Management final sign-off.';
                if (isManagement) {
                    submitBtn.innerHTML = '<i class="fa-solid fa-check-double me-1"></i> Approve Discount (Management)';
                    submitBtn.className = 'btn btn-success btn-sm px-4 fw-bold';
                    submitBtn.disabled = false;
                } else {
                    submitBtn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Verified by Op Head';
                    submitBtn.className = 'btn btn-secondary btn-sm px-4 fw-bold';
                    submitBtn.disabled = true;
                }
            }
        } else {
            noticeEl.className = 'alert alert-primary py-2 px-3 extra-small mb-3 border-primary-subtle';
            noticeEl.innerHTML = '<i class="fa-solid fa-info-circle me-1"></i> <strong>Direct Approval:</strong> Discount is within ৳1,00,000 threshold. Approving directly finalizes this booking discount.';
            if (isRole20 || isManagement) {
                submitBtn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Approve Discount';
                submitBtn.className = 'btn btn-success btn-sm px-4 fw-bold';
                submitBtn.disabled = false;
            } else {
                submitBtn.innerHTML = '<i class="fa-solid fa-lock me-1"></i> Action Restricted';
                submitBtn.className = 'btn btn-secondary btn-sm px-4 fw-bold';
                submitBtn.disabled = true;
            }
        }

        const modalEl = document.getElementById('discountApprovalModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }

    function submitDiscountAction(action) {
        if (!currentDiscountData) return;
        const reqId = currentDiscountData.id;
        const note = document.getElementById('discModalNote').value.trim();
        const isOver1Lac = (parseFloat(currentDiscountData.amount) > 100000.01);
        const isOpHeadApproved = (String(currentDiscountData.op_head_status).toLowerCase() === 'approved');
        const isRole20 = (currentLoggedRoleId === 20 || currentLoggedRoleId === 32 || currentLoggedRoleName.includes('operation head') || currentLoggedRoleName.includes('admin & operation'));
        const isManagement = (currentLoggedRoleId === 1 || currentLoggedRoleId === 3 || currentLoggedRoleName.includes('management') || currentLoggedRoleName.includes('super admin'));

        let endpoint = '';
        if (action === 'reject') {
            endpoint = (isRole20 && !isManagement && (!isOver1Lac || !isOpHeadApproved))
                ? '<?= $base_url ?>/approval-requests/reject-operation-head'
                : '<?= $base_url ?>/approval-requests/reject-management';
        } else {
            endpoint = (isRole20 && !isManagement && (!isOver1Lac || !isOpHeadApproved))
                ? '<?= $base_url ?>/approval-requests/approve-operation-head'
                : '<?= $base_url ?>/approval-requests/approve-management';
        }

        const btn = document.getElementById('discModalSubmitBtn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Processing...';

        fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                approval_request_id: reqId,
                source_table: 'lead_bookings',
                source_id: currentDiscountData.source_id,
                note: note || (action === 'approve' ? 'Approved Booking Discount' : 'Rejected Booking Discount')
            })
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            if (data.success) {
                const modalEl = document.getElementById('discountApprovalModal');
                if (modalEl) {
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: action === 'approve' ? 'Discount Approved!' : 'Discount Rejected',
                        text: data.message || 'Operation completed successfully.',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    window.location.reload();
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Operation Failed',
                        text: data.message || 'Failed to process request.'
                    });
                } else {
                    alert(data.message || 'Failed to process request.');
                }
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Error: ' + err
                });
            } else {
                alert('Network Error: ' + err);
            }
        });
    }
</script>

<style>
.clickable-approval-row:hover td {
    background-color: rgba(var(--bs-primary-rgb), 0.05) !important;
}
.hover-primary:hover {
    color: var(--bs-primary) !important;
}
</style>
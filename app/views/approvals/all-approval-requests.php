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
        <div>
            <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fs-7 fw-semibold">
                <i class="fa-solid fa-shield-halved me-1"></i> Management & Accounts Audit Panel
            </span>
        </div>
    </div>

    <!-- Company Dashboard Summary Metrics Row -->
    <div class="row mb-4 ">
        <!-- Total System Requests -->
        <div class="col-xl-3 col-md-6 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-primary">
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
                    <span class="text-muted extra-small">Across all company departments</span>
                </div>
            </div>
        </div>

        <!-- Pending Approval Queue -->
        <div class="col-xl-3 col-md-6 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-warning">
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
                    <span
                        class="badge bg-warning-subtle text-warning border border-warning-subtle extra-small rounded-pill">
                        <?= $companyMetrics['pending_count'] ?> Requests Awaiting Review
                    </span>
                </div>
            </div>
        </div>

        <!-- Total Approved Amount -->
        <div class="col-xl-3 col-md-6 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-info">
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
                        <?= $companyMetrics['approved_count'] ?> Approved Requests
                    </span>
                </div>
            </div>
        </div>

        <!-- Total Accounts Disbursed / Paid -->
        <div class="col-xl-3 col-md-6 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-success">
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
                    <span class="text-muted extra-small">Paid via Bank/Cash to Vendors & Staff</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Table Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <!-- Filter Toolbar -->
        <div class="card-header bg-white py-3 border-bottom">
            <form method="GET" action="<?= $base_url ?>/all-approval-requests" class="row  align-items-center">
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
                        <option value="approved" <?= ($filters['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved History</option>
                        <option value="rejected" <?= ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected History</option>
                        <?php if (in_array((int)($_SESSION['user']['role_id'] ?? 0), [1, 3, 33])): ?>
                            <option value="all_pending" <?= ($filters['status'] ?? '') === 'all_pending' ? 'selected' : '' ?>>All Departments Pending (Audit)</option>
                            <option value="all" <?= ($filters['status'] ?? '') === 'all' ? 'selected' : '' ?>>All Company Requests (Complete Audit)</option>
                        <?php endif; ?>
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
        <div class="p-3 bg-light border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary fs-12 px-3 py-2" id="selectedApprovalCountBadge">0 Selected</span>
                <button type="button" class="btn btn-sm btn-success fw-bold px-3"
                    onclick="processBulkApprovalAction('approve')">
                    <i class="fa-solid fa-circle-check me-1"></i> Bulk Approve Selected
                </button>
                <button type="button" class="btn btn-sm btn-danger fw-bold px-3"
                    onclick="processBulkApprovalAction('reject')">
                    <i class="fa-solid fa-circle-xmark me-1"></i> Bulk Reject Selected
                </button>
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
                                    onclick="toggleSelectAllApprovalRequests(this)">
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
                                } elseif ($sourceTable === 'con_land_requisitions') {
                                    $targetUrl = $base_url . '/con-land-requisitions';
                                } elseif ($sourceTable === 'con_labor_requisitions') {
                                    $targetUrl = $base_url . '/con-labor-requisition/' . $req['source_id'];
                                } elseif ($sourceTable === 'con_work_orders') {
                                    $targetUrl = $base_url . '/con-work-order/' . $req['source_id'];
                                } elseif ($sourceTable === 'fund_requisitions' || $sourceTable === 'operational_fund_requests') {
                                    $targetUrl = $base_url . '/fund-requisitions/view/' . $req['source_id'];
                                } elseif ($sourceTable === 'cashout_requests') {
                                    $targetUrl = $base_url . '/admin-view-cashout-request?id=' . $req['source_id'];
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
                                }
                                $reqApproveRole = strtolower($req['approve_role'] ?? 'management');
                                if (empty($reqApproveRole)) {
                                    $reqApproveRole = 'management';
                                }
                                $isMgmtRequest = ($reqApproveRole === 'management');
                                $userRoleId = (int) ($_SESSION['user']['role_id'] ?? 0);
                                $sessionRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));

                                $isManagementUser = in_array($userRoleId, [1, 3, 33], true) || in_array($sessionRoleName, ['super admin', 'admin', 'management', 'chief officer'], true);
                                $isSiteOperator = in_array($userRoleId, [20, 32], true) || strpos($sessionRoleName, 'operation head') !== false || strpos($sessionRoleName, 'admin & operation') !== false;
                                $isAccountsUser = in_array($userRoleId, [6, 30], true) || strpos($sessionRoleName, 'account') !== false;
                                $isQcUser = in_array($userRoleId, [13, 24, 25], true) || strpos($sessionRoleName, 'qc') !== false || strpos($sessionRoleName, 'audit') !== false;
                                $isHrUser = ($userRoleId === 4) || strpos($sessionRoleName, 'hr') !== false;
                                $isPurchaseUser = in_array($userRoleId, [17, 23, 31], true) || strpos($sessionRoleName, 'purchase') !== false;
                                $isDeptHeadUser = in_array($userRoleId, [8, 9, 10, 11, 12, 14, 15, 16, 18, 19], true) || strpos($sessionRoleName, 'head') !== false || strpos($sessionRoleName, 'manager') !== false;

                                $statusLower = strtolower($req['status'] ?? '');
                                $isPendingStatus = in_array($statusLower, ['pending', 'partially_approved', '']);
                                $reqAmount = (float)($req['request_amount'] ?? 0);
                                $payoutStatus = $req['payout_status'] ?? '';

                                // Determine whether this request is actionable by the logged-in user right now
                                $isActionableByMe = false;
                                $canUserApprove = false;
                                if ($isAccountsUser) {
                                    if (in_array($payoutStatus, ['Pending Accounts Payout', 'Partially Paid']) || ($reqApproveRole === 'accounts' && in_array($statusLower, ['pending', 'approved', 'partially_approved', 'partially_paid']))) {
                                        $isActionableByMe = true;
                                        $canUserApprove = true;
                                    }
                                } elseif ($isSiteOperator) {
                                    if ($isPendingStatus && in_array($reqApproveRole, ['management', '']) && $reqAmount <= 100000.01) {
                                        $isActionableByMe = true;
                                        $canUserApprove = true;
                                    }
                                } elseif ($isManagementUser) {
                                    if ($isPendingStatus && in_array($reqApproveRole, ['management', ''])) {
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
                                <tr <?= !empty($targetUrl) ? 'data-href="' . htmlspecialchars($targetUrl) . '" class="clickable-approval-row' . ($isActionableByMe ? ' border-start border-primary border-3' : '') . '" style="cursor: pointer; ' . $rowBgStyle . '"' : '' ?>>
                                    <td class="ps-3">
                                        <input type="checkbox" class="approval-req-checkbox form-check-input"
                                            value="<?= $req['id'] ?>" <?= (!in_array($req['status'], ['pending', 'partially_approved', 'partially_paid']) || !$canUserApprove) ? 'disabled' : '' ?> onchange="updateBulkApprovalCount()">
                                    </td>
                                    <!-- Req # & Date -->
                                    <td>
                                        <?php if (!empty($targetUrl)): ?>
                                            <a href="<?= $targetUrl ?>" class="text-decoration-none d-block">
                                                <?php if ($sourceTable === 'vendor_payment_requests' && !empty($req['po_number'])): ?>
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
                                            <?php if ($sourceTable === 'vendor_payment_requests' && !empty($req['po_number'])): ?>
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
                                        <?php if ($req['approved_amount'] > 0): ?>
                                            <span class="fw-bold text-success">৳
                                                <?= number_format($req['approved_amount'], 2) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted extra-small">--</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Approval Status & Pending At -->
                                    <td class="text-center">
                                        <?php if ($req['status'] === 'pending'): ?>
                                            <?php
                                            $badgeClass = 'bg-warning text-dark';
                                            $stageLabel = $req['pending_stage_label'] ?? 'Pending Review';
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
                                                <span class="text-primary fw-semibold d-block"><?= htmlspecialchars($req['pending_for_text'] ?? 'Management') ?></span>
                                            </div>
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
                                            <?php if ($req['payout_date']): ?>
                                                <div class="extra-small text-muted">
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
                                            <?php if ($req['payout_date']): ?>
                                                <div class="extra-small text-muted">
                                                    <?= date('d M, Y', strtotime($req['payout_date'])) ?>
                                                </div>
                                            <?php endif; ?>
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
                                        $voucherSupportedModules = [
                                            'advance_salaries', 'contractor_bill_requests', 'vendor_payment_requests',
                                            'fund_requisitions', 'project_other_costs', 'cashout_requests',
                                            'payslips', 'project_extra_assets', 'purchase_requests',
                                            'con_labor_requisitions', 'con_land_requisitions', 'land_fund_requisitions',
                                            'client_refund_requests', 'project_incentive_claims', 'operational_fund_requests',
                                            'expense_adjustments'
                                        ];
                                        $isVoucherSupported = in_array($sourceTable, $voucherSupportedModules);
                                        $voucherUrl = $base_url . '/print-debit-voucher?module=' . urlencode($sourceTable) . '&id=' . (int)$req['source_id'];
                                        $isPaidOrSettled = in_array($payoutStatus, ['Paid & Settled', 'Settled']) || in_array(strtolower($req['status']), ['paid', 'fully_paid', 'labor_disbursed', 'deducted']);
                                        $isPartiallyPaid = ($payoutStatus === 'Partially Paid') || strtolower($req['status']) === 'partially_paid';
                                        ?>

                                        <?php if ($isPartiallyPaid): ?>
                                            <?php if ($isAccountsUser): ?>
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

                                        <?php elseif ($isAccountsUser && in_array($payoutStatus, ['Pending Accounts Payout'])): ?>
                                            <a href="<?= $targetUrl ?>" class="btn btn-sm btn-success rounded-2 px-3 shadow-sm fw-semibold">
                                                <i class="fa-solid fa-money-check-dollar me-1"></i>Pay / Disburse
                                            </a>

                                        <?php elseif (in_array($req['status'], ['pending', 'partially_approved'])): ?>
                                            <?php if ($canUserApprove): ?>
                                                <a href="<?= $targetUrl ?>" class="btn btn-sm btn-primary rounded-2 px-3 shadow-sm fw-semibold">
                                                    <i class="fa-solid fa-up-right-from-square me-1"></i>Review / Process
                                                </a>
                                            <?php else: ?>
                                                <a href="javascript:void(0)"
                                                    class="btn btn-sm btn-light text-muted rounded-2 px-2 border"
                                                    title="<?= ($isSiteOperator && $reqAmount > 100000) ? 'Amount exceeds ৳1,00,000 threshold - Full Management approval required' : 'Pending for ' . htmlspecialchars($req['pending_for_text'] ?? 'Other Role') ?>">
                                                    <i class="fa-solid fa-lock me-1 text-warning"></i><?= ($isSiteOperator && $reqAmount > 100000) ? 'Management Only' : 'Pending Other Role' ?>
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
</div>

<script>
    let approvalTable;

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof $.fn.DataTable !== 'undefined') {
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
</script>

<style>
.clickable-approval-row:hover td {
    background-color: rgba(var(--bs-primary-rgb), 0.05) !important;
}
.hover-primary:hover {
    color: var(--bs-primary) !important;
}
</style>
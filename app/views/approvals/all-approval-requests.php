<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-bold mb-1 text-dark">
                <i class="fa-solid fa-list-check text-primary me-2"></i>All System Approval Requests & Payout Tracking
            </h3>
            <p class="text-muted small mb-0">Unified dashboard for Management & Accounts to monitor all company requests across Sales, Construction Finance, HR, and Accounts.</p>
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
                    <h3 class="fw-bold mb-1 text-dark"><?= number_format($companyMetrics['total_requests']) ?> Items</h3>
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
                    <h3 class="fw-bold mb-1 text-warning">৳<?= number_format($companyMetrics['pending_amount'], 2) ?></h3>
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle extra-small rounded-pill">
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
                    <h3 class="fw-bold mb-1 text-info">৳<?= number_format($companyMetrics['approved_amount'], 2) ?></h3>
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
                    <h3 class="fw-bold mb-1 text-success">৳<?= number_format($companyMetrics['total_disbursed'], 2) ?></h3>
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
                <div class="col-l col-md-6">
                    <label class="form-label text-muted extra-small fw-bold text-uppercase mb-1">Department Filter</label>
                    <select name="department" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Departments</option>
                        <option value="Sales" <?= ($filters['department'] ?? '') === 'Sales' ? 'selected' : '' ?>>Sales & Incentives</option>
                        <option value="construction" <?= strtolower($filters['department'] ?? '') === 'construction' ? 'selected' : '' ?>>Construction (`con-dashboard`)</option>
                        <option value="HR" <?= ($filters['department'] ?? '') === 'HR' ? 'selected' : '' ?>>HR & Payroll</option>
                        <option value="Accounts" <?= ($filters['department'] ?? '') === 'Accounts' ? 'selected' : '' ?>>Accounts & Finance</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="col-l col-md-6">
                    <label class="form-label text-muted extra-small fw-bold text-uppercase mb-1">Approval Status</label>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Statuses (Pending, Approved, Rejected)</option>
                        <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending Review</option>
                        <option value="approved" <?= ($filters['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="rejected" <?= ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>

                <!-- Source Table / Module Filter -->
                <div class="col-lg-4 col-md-6">
                    <label class="form-label text-muted extra-small fw-bold text-uppercase mb-1">Module / Source Type</label>
                    <select name="source_table" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Approval Source Types</option>
                        <option value="project_incentive_claims" <?= ($filters['source_table'] ?? '') === 'project_incentive_claims' ? 'selected' : '' ?>>Sales Incentive Claims</option>
                        <option value="vendor_payment_requests" <?= ($filters['source_table'] ?? '') === 'vendor_payment_requests' ? 'selected' : '' ?>>Vendor Payment Requests (Construction)</option>
                        <option value="contractor_bill_requests" <?= ($filters['source_table'] ?? '') === 'contractor_bill_requests' ? 'selected' : '' ?>>Contractor Bills (Construction)</option>
                        <option value="operational_fund_requests" <?= ($filters['source_table'] ?? '') === 'operational_fund_requests' ? 'selected' : '' ?>>Operational Fund / Requisitions</option>
                        <option value="cashout_requests" <?= ($filters['source_table'] ?? '') === 'cashout_requests' ? 'selected' : '' ?>>Cashout Requests (Accounts)</option>
                        <option value="payslips" <?= ($filters['source_table'] ?? '') === 'payslips' ? 'selected' : '' ?>>Payslips & Salary (HR)</option>
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

        <!-- Approval History Table -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted extra-small text-uppercase">
                        <tr>
                            <th class="ps-4">Req # & Date</th>
                            <th>Department & Module</th>
                            <th>Purpose / Title</th>
                            <th>Requested By</th>
                            <th>Requested Amount</th>
                            <th>Approved Amount</th>
                            <th class="text-center">Management Status</th>
                            <th class="text-center">Accounts Payout</th>
                            <th class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($approvalHistory)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-folder-open fs-3 d-block mb-2"></i>
                                    No approval requests found matching your filter criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($approvalHistory as $req): ?>
                                <?php
                                    // Direct module target URL resolution so clicking review/view opens the exact module page!
                                    $targetUrl = $base_url . '/approve-request/' . $req['id'];
                                    $sourceTable = strtolower($req['source_table'] ?? '');

                                    if ($sourceTable === 'project_incentive_claims') {
                                        $targetUrl = $base_url . '/incentive-management';
                                    } elseif ($sourceTable === 'vendor_payment_requests') {
                                        $targetUrl = $base_url . '/con-vendor-payment-requests';
                                    } elseif ($sourceTable === 'contractor_bill_requests') {
                                        $targetUrl = $base_url . '/con-contractor-bills';
                                    } elseif ($sourceTable === 'con_land_requisitions') {
                                        $targetUrl = $base_url . '/con-land-requisitions';
                                    } elseif ($sourceTable === 'fund_requisitions' || $sourceTable === 'operational_fund_requests') {
                                        $targetUrl = $base_url . '/fund-requisitions';
                                    } elseif ($sourceTable === 'cashout_requests') {
                                        $targetUrl = $base_url . '/admin-cashout-requests';
                                    } elseif ($sourceTable === 'payslips' || $sourceTable === 'salary_requests') {
                                        $targetUrl = $base_url . '/payslip-lists';
                                    } elseif ($sourceTable === 'client_refund_requests') {
                                        $targetUrl = $base_url . '/client-refunds';
                                    }
                                ?>
                                <tr>
                                    <!-- Req # & Date -->
                                    <td class="ps-4">
                                        <span class="fw-bold text-dark d-block">#REQ-<?= str_pad($req['id'], 5, '0', STR_PAD_LEFT) ?></span>
                                        <span class="text-muted extra-small"><?= date('d M, Y', strtotime($req['requested_at'])) ?></span>
                                    </td>

                                    <!-- Department & Module -->
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary fw-semibold d-block mb-1 text-capitalize">
                                            <?= htmlspecialchars($req['department'] ?? 'General') ?>
                                        </span>
                                        <span class="badge bg-light text-muted border extra-small">
                                            <?= htmlspecialchars($req['request_type'] ?? $req['source_table']) ?>
                                        </span>
                                    </td>

                                    <!-- Title -->
                                    <td style="max-width: 250px;">
                                        <div class="fw-semibold text-dark text-truncate" title="<?= htmlspecialchars($req['title']) ?>">
                                            <?= htmlspecialchars($req['title']) ?>
                                        </div>
                                        <span class="text-muted extra-small">Source Table: <code><?= htmlspecialchars($req['source_table']) ?></code></span>
                                    </td>

                                    <!-- Requested By -->
                                    <td>
                                        <div class="fw-bold text-dark extra-small"><?= htmlspecialchars($req['requester_name'] ?? 'N/A') ?></div>
                                        <span class="badge bg-light text-secondary border extra-small"><?= htmlspecialchars($req['requester_code'] ?? '') ?></span>
                                    </td>

                                    <!-- Requested Amount -->
                                    <td>
                                        <?php if ($req['request_amount'] > 0): ?>
                                            <span class="fw-bold text-dark">৳<?= number_format($req['request_amount'], 2) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted extra-small">N/A</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Approved Amount -->
                                    <td>
                                        <?php if ($req['status'] === 'approved' && $req['approved_amount'] > 0): ?>
                                            <span class="fw-bold text-success">৳<?= number_format($req['approved_amount'], 2) ?></span>
                                        <?php elseif ($req['status'] === 'approved'): ?>
                                            <span class="fw-semibold text-success">Approved</span>
                                        <?php else: ?>
                                            <span class="text-muted extra-small">--</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Management Status -->
                                    <td class="text-center">
                                        <?php if ($req['status'] === 'pending'): ?>
                                            <span class="badge bg-warning text-dark rounded-pill px-3 py-1 extra-small">
                                                <i class="fa-solid fa-clock me-1"></i>Pending Review
                                            </span>
                                        <?php elseif ($req['status'] === 'approved'): ?>
                                            <span class="badge bg-success text-white rounded-pill px-3 py-1 extra-small d-block mb-1">
                                                <i class="fa-solid fa-user-shield me-1"></i>Approved from Management
                                            </span>
                                            <div class="fw-bold text-dark extra-small">
                                                <i class="fa-solid fa-user-check text-success me-1"></i><?= htmlspecialchars($req['approver_name'] ?? 'Management') ?>
                                            </div>
                                            <?php if (!empty($req['approver_designation'])): ?>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle extra-small d-block my-1"><?= htmlspecialchars($req['approver_designation']) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($req['approver_email'])): ?>
                                                <span class="text-muted extra-small d-block"><i class="fa-solid fa-envelope me-1"></i><?= htmlspecialchars($req['approver_email']) ?></span>
                                            <?php endif; ?>
                                            <?php if ($req['approved_at']): ?>
                                                <span class="text-muted extra-small d-block"><i class="fa-solid fa-calendar-check me-1"></i><?= date('d M, Y h:i A', strtotime($req['approved_at'])) ?></span>
                                            <?php endif; ?>
                                        <?php elseif ($req['status'] === 'rejected'): ?>
                                            <span class="badge bg-danger text-white rounded-pill px-3 py-1 extra-small d-block mb-1">
                                                <i class="fa-solid fa-circle-xmark me-1"></i>Rejected
                                            </span>
                                            <span class="extra-small text-muted">By: <?= htmlspecialchars($req['approver_name'] ?? 'Management') ?></span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Accounts Payout Status -->
                                    <td class="text-center">
                                        <?php if ($req['payout_status'] === 'Paid & Settled'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 extra-small d-block mb-1">
                                                <i class="fa-solid fa-money-check-dollar me-1"></i>Paid & Settled
                                            </span>
                                            <span class="extra-small fw-bold text-success">৳<?= number_format($req['payout_amount'], 2) ?></span>
                                            <?php if ($req['payout_date']): ?>
                                                <div class="extra-small text-muted"><?= date('d M, Y', strtotime($req['payout_date'])) ?></div>
                                            <?php endif; ?>
                                        <?php elseif ($req['payout_status'] === 'Pending Accounts Payout'): ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-1 extra-small">
                                                <i class="fa-solid fa-wallet me-1"></i>Pending Accounts Payout
                                            </span>
                                        <?php elseif ($req['status'] === 'approved'): ?>
                                            <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-3 py-1 extra-small">
                                                <i class="fa-solid fa-check me-1"></i>Approved
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border extra-small">N/A</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Action Button (Directly routes to specific module page!) -->
                                    <td class="pe-4 text-end">
                                        <?php if ($req['status'] === 'pending'): ?>
                                            <a href="<?= $targetUrl ?>" class="btn btn-sm btn-primary rounded-2 px-3 shadow-sm">
                                                <i class="fa-solid fa-up-right-from-square me-1"></i>Review / Process
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= $targetUrl ?>" class="btn btn-sm btn-light text-dark rounded-2 px-2 border">
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

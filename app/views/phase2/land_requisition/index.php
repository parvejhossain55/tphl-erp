<?php
$roleId = (int)($_SESSION['user']['role_id'] ?? 0);
$canRequest = in_array($roleId, [1, 3, 14, 15, 20, 22, 32, 33]);
?>


<div class="mb-3 pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4 class="mb-2">Land Fund Requisitions</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Land Requisitions</li>
            </ol>
        </nav>
    </div>
    <?php if ($canRequest): ?>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#requestFundModal">
        <i class="fa-light fa-plus me-1"></i> New Fund Requisition
    </button>
    <?php endif; ?>
</div>

<!-- Top KPI Stats Row -->
<div class="row mb-4 align-items-stretch">
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold">Total Portfolio Valuation</div>
                <h3 class="mb-0 mt-1 text-primary">BDT <?= number_format((float)($topStats['total_portfolio_valuation'] ?? 0), 2) ?></h3>
                <div class="mt-2 text-muted small">
                    Across <strong><?= count($acquisitions) ?></strong> Land Deals
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold">Paid by Accounts (Land Price)</div>
                <h3 class="mb-0 mt-1 text-success">BDT <?= number_format((float)($topStats['total_land_paid'] ?? 0), 2) ?></h3>
                <div class="mt-2 text-muted small">
                    Bayana & Registration Paid
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold">Extra Costs Paid</div>
                <h3 class="mb-0 mt-1 text-info">BDT <?= number_format((float)($topStats['total_extra_paid'] ?? 0), 2) ?></h3>
                <div class="mt-2 text-muted small">
                    Brokerage, Legal, Mutation etc.
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold">Remaining Land Price Due</div>
                <h3 class="mb-0 mt-1 text-danger">BDT <?= number_format((float)($topStats['total_land_due'] ?? 0), 2) ?></h3>
                <div class="mt-2 text-muted small">
                    Pending In Pipeline: <strong>BDT <?= number_format((float)($topStats['total_pending'] ?? 0), 2) ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="mb-0">All Land Fund Requisitions</h5>
                    <?php if (!empty($selectedAcqId)): ?>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                            Filtered by Land Deal #<?= $selectedAcqId ?>
                        </span>
                        <a href="<?= $base_url ?>/con-land-requisitions" class="btn btn-sm btn-outline-secondary py-0" style="font-size:11px;">Clear Filter</a>
                    <?php endif; ?>
                </div>
                <form method="GET" action="<?= $base_url ?>/con-land-requisitions" class="d-flex align-items-center gap-2">
                    <label class="form-label mb-0 small text-muted text-nowrap">Filter by Project:</label>
                    <select name="project_id" class="form-select form-select-sm select2-filter-project" style="min-width: 240px;">
                        <option value="">All Projects (<?= count($projects ?? []) ?>)</option>
                        <?php foreach (($projects ?? []) as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= ($selectedProjectId ?? 0) == $p['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['name']) ?> <?= !empty($p['project_status']) ? '('.$p['project_status'].')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($selectedProjectId)): ?>
                        <a href="<?= $base_url ?>/con-land-requisitions" class="btn btn-sm btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
            <!-- Workflow Filter Tabs -->
            <div class="px-3 py-2 bg-light border-bottom d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="small fw-semibold text-muted me-1"><i class="fa-light fa-filter me-1"></i>Filter by Stage:</span>
                    <?php
                        $baseFilterParams = array_filter([
                            'project_id' => $selectedProjectId ?: null,
                            'land_acquisition_id' => $selectedAcqId ?: null
                        ]);
                        $currStatus = $statusFilter ?? '';
                        $makeFilterUrl = function($status = null) use ($base_url, $baseFilterParams) {
                            $params = $baseFilterParams;
                            if ($status) $params['status_filter'] = $status;
                            return $base_url . "/con-land-requisitions" . (!empty($params) ? '?' . http_build_query($params) : '');
                        };
                    ?>
                    <a href="<?= $makeFilterUrl() ?>" class="btn btn-sm <?= empty($currStatus) ? 'btn-primary text-white fw-bold' : 'btn-outline-secondary' ?> py-1 px-2" style="font-size:11px;">
                        All (<?= (int)($workflowStats['total_count'] ?? 0) ?>)
                    </a>
                    <a href="<?= $makeFilterUrl('pending_mgmt') ?>" class="btn btn-sm <?= $currStatus === 'pending_mgmt' ? 'btn-warning text-dark fw-bold' : 'btn-outline-warning text-dark' ?> py-1 px-2" style="font-size:11px;">
                        <i class="fa-light fa-hourglass-half me-1"></i>Pending Mgmt (<?= (int)($workflowStats['pending_mgmt_count'] ?? 0) ?>) 
                        <span class="badge bg-white text-dark ms-1">BDT <?= number_format(((float)($workflowStats['pending_mgmt_amount'] ?? 0)) / 1000, 0) ?>k</span>
                    </a>
                    <a href="<?= $makeFilterUrl('pending_accounts') ?>" class="btn btn-sm <?= $currStatus === 'pending_accounts' ? 'btn-info text-white fw-bold' : 'btn-outline-info' ?> py-1 px-2" style="font-size:11px;">
                        <i class="fa-light fa-wallet me-1"></i>Pending Accounts (<?= (int)($workflowStats['pending_accounts_count'] ?? 0) ?>)
                        <span class="badge bg-white text-dark ms-1">BDT <?= number_format(((float)($workflowStats['pending_accounts_amount'] ?? 0)) / 1000, 0) ?>k</span>
                    </a>
                    <a href="<?= $makeFilterUrl('paid') ?>" class="btn btn-sm <?= $currStatus === 'paid' ? 'btn-success text-white fw-bold' : 'btn-outline-success' ?> py-1 px-2" style="font-size:11px;">
                        <i class="fa-light fa-check-double me-1"></i>Paid (<?= (int)($workflowStats['paid_count'] ?? 0) ?>)
                        <span class="badge bg-white text-dark ms-1">BDT <?= number_format(((float)($workflowStats['paid_amount'] ?? 0)) / 1000, 0) ?>k</span>
                    </a>
                    <?php if (!empty($workflowStats['rejected_count'])): ?>
                        <a href="<?= $makeFilterUrl('rejected') ?>" class="btn btn-sm <?= $currStatus === 'rejected' ? 'btn-danger text-white' : 'btn-outline-danger' ?> py-1 px-2" style="font-size:11px;">
                            <i class="fa-light fa-xmark me-1"></i>Rejected (<?= (int)$workflowStats['rejected_count'] ?>)
                        </a>
                    <?php endif; ?>
                </div>
                <div class="small text-muted">
                    Total Pipeline: <strong>BDT <?= number_format((float)($workflowStats['total_amount'] ?? 0), 2) ?></strong>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Project & Land Deal</th>
                                <th>Req Type & Purpose</th>
                                <th>Amount Requested</th>
                                <th>Requested By</th>
                                <th>Mgmt Status</th>
                                <th>Accounts Status</th>
                                <th>Payment & Expense Destination</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($requisitions)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">No fund requisitions found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($requisitions as $index => $row): ?>
                                    <?php 
                                        $fin = $row['land_financial'] ?? null;
                                        $isLandCost = in_array($row['requisition_type'], ['Bayana', 'Registration']);
                                        if (!class_exists('ApprovalRequest')) {
                                            require_once __DIR__ . '/../../models/ApprovalRequest.php';
                                        }
                                        $ahInfoRow = ApprovalRequest::getAccountsHeadApprovalInfo($GLOBALS['db'] ?? ($this->db ?? null), 'land_fund_requisitions', (int)$row['id']);
                                        if (empty($ahInfoRow['has_request'])) {
                                            $ahInfoRow = ApprovalRequest::getAccountsHeadApprovalInfo($GLOBALS['db'] ?? ($this->db ?? null), 'con_land_requisitions', (int)$row['id']);
                                        }
                                        $isAhApprovedRow = empty($ahInfoRow['has_request']) || !empty($ahInfoRow['is_accounts_head_approved']);
                                        $canAhApproveRow = ($roleId === 30 || in_array($roleId, [1, 3]));
                                    ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td>
                                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-1">
                                                <div class="fw-bold text-primary fs-6"><?= htmlspecialchars($row['effective_project_name'] ?? 'Tentative Deal') ?></div>
                                                <?php if (!empty($row['is_bulk_import'])): ?>
                                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:9px;" title="Uploaded via Bulk CSV Import">
                                                        <i class="fa-light fa-file-csv me-1 text-primary"></i>Bulk CSV Uploaded
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:9px;" title="Created manually in ERP">
                                                        <i class="fa-light fa-keyboard me-1"></i>ERP Manual
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="small fw-semibold text-dark">
                                                <i class="fa-light fa-user me-1 text-muted"></i>Owner: <?= htmlspecialchars($row['land_owner_name'] ?? 'N/A') ?>
                                            </div>
                                            <?php if (!empty($row['land_receiver_name'])): ?>
                                                <div class="small text-success" style="font-size:11px;">
                                                    <i class="fa-light fa-hand-holding-hand me-1"></i>Receiver: <?= htmlspecialchars($row['land_receiver_name']) ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($fin): ?>
                                                <div class="mt-1 d-flex flex-wrap gap-1 align-items-center">
                                                    <span class="badge bg-light text-secondary border" style="font-size:10px;">
                                                        Val: BDT <?= number_format($fin['total_amount'] / 100000, 1) ?>L | Paid: BDT <?= number_format($fin['paid_land'] / 100000, 1) ?>L | Due: BDT <?= number_format($fin['remaining_due'] / 100000, 1) ?>L
                                                    </span>
                                                    <?php if (!empty($fin['pending_mgmt_amount']) && $fin['pending_mgmt_amount'] > 0): ?>
                                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size:10px;" title="Requisitions waiting for Management Approval">
                                                            <i class="fa-light fa-clock me-1"></i>Mgmt Pending: BDT <?= number_format($fin['pending_mgmt_amount'] / 100000, 1) ?>L
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($fin['pending_accounts_amount']) && $fin['pending_accounts_amount'] > 0): ?>
                                                        <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size:10px;" title="Approved by Mgmt, Waiting for Accounts Payment">
                                                            <i class="fa-light fa-wallet me-1"></i>Acc Pending: BDT <?= number_format($fin['pending_accounts_amount'] / 100000, 1) ?>L
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="mb-1">
                                                <span class="badge bg-secondary"><?= htmlspecialchars($row['requisition_type']) ?></span>
                                                <?php if (!empty($row['cost_purpose'])): ?>
                                                    <span class="badge bg-info-subtle text-info border border-info-subtle"><?= htmlspecialchars($row['cost_purpose']) ?></span>
                                                <?php endif; ?>
                                                <?php if (!$isLandCost): ?>
                                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size:9px;">Extra Cost</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-muted small" style="font-size:11px;"><?= htmlspecialchars($row['purpose']) ?></div>
                                            <?php if (!empty($row['req_attachment_path'])): ?>
                                                <div class="mt-1">
                                                    <a href="<?= $base_url ?>/public/<?= htmlspecialchars($row['req_attachment_path']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:10px;">
                                                        <i class="fa-light fa-paperclip me-1"></i>Req Attachment
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-success fs-6">
                                                BDT <?= number_format((float)($row['requested_amount'] ?? 0), 2) ?>
                                            </div>
                                            <small class="text-muted" style="font-size:10px;">
                                                <?= $isLandCost ? '<i class="fa-light fa-landmark me-1 text-primary"></i>Land Price' : '<i class="fa-light fa-plus-circle me-1 text-info"></i>Overhead / Extra' ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars($row['requester_name'] ?? 'N/A') ?></div>
                                            <small class="text-muted" style="font-size:10px;"><?= date('M d, Y', strtotime($row['created_at'])) ?></small>
                                        </td>
                                        <td>
                                            <?php if ($row['mgmt_status'] === 'Approved'): ?>
                                                <span class="badge bg-success"><i class="fa-light fa-check me-1"></i>Approved</span>
                                                <?php if (!empty($row['mgmt_name'])): ?>
                                                    <div style="font-size:10px;" class="text-muted mt-1">By: <?= htmlspecialchars($row['mgmt_name']) ?></div>
                                                <?php endif; ?>
                                                <?php if (!empty($row['mgmt_approved_at'])): ?>
                                                    <div style="font-size:10px;" class="text-muted">On: <?= date('d M Y h:i A', strtotime($row['mgmt_approved_at'])) ?></div>
                                                <?php endif; ?>
                                                <?php if (!empty($row['mgmt_note'])): ?>
                                                    <div style="font-size:10px; font-style:italic;" class="text-muted">"<?= htmlspecialchars($row['mgmt_note']) ?>"</div>
                                                <?php endif; ?>
                                            <?php elseif ($row['mgmt_status'] === 'Rejected'): ?>
                                                <span class="badge bg-danger"><i class="fa-light fa-xmark me-1"></i>Rejected</span>
                                                <?php if (!empty($row['mgmt_name'])): ?>
                                                    <div style="font-size:10px;" class="text-muted mt-1">By: <?= htmlspecialchars($row['mgmt_name']) ?></div>
                                                <?php endif; ?>
                                                <?php if (!empty($row['mgmt_note'])): ?>
                                                    <div style="font-size:10px; font-style:italic;" class="text-danger">"<?= htmlspecialchars($row['mgmt_note']) ?>"</div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <?php 
                                                    $reqAmt = (float)($row['requested_amount'] ?? 0);
                                                    $isOver1LacRow = !empty($ahInfoRow['is_over_1lac']) || ($reqAmt > 100000);
                                                    $isOpHeadApprovedRow = !empty($ahInfoRow['is_op_head_approved']);
                                                    $isPendingOpHeadRow = $isOver1LacRow && !$isOpHeadApprovedRow;
                                                ?>
                                                <?php if ($isPendingOpHeadRow): ?>
                                                    <span class="badge bg-warning text-dark"><i class="fa-light fa-hourglass-half me-1"></i>Pending Op Head (&gt;1L)</span>
                                                    <div style="font-size:10px;" class="text-warning mt-1">Requires Role 20 first</div>
                                                <?php elseif ($isOver1LacRow): ?>
                                                    <span class="badge bg-info text-dark"><i class="fa-light fa-hourglass-half me-1"></i>Pending Mgmt (&gt;1L)</span>
                                                    <div style="font-size:10px;" class="text-success mt-1"><i class="fa-light fa-check me-1"></i>Op Head Approved</div>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark"><i class="fa-light fa-hourglass-half me-1"></i>Pending Review</span>
                                                    <div style="font-size:10px;" class="text-warning mt-1">Awaiting Approval (≤1L)</div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($row['accounts_status'] === 'Paid'): ?>
                                                <span class="badge bg-success"><i class="fa-light fa-check-double me-1"></i>Paid</span>
                                                <?php if (!empty($row['accounts_name'])): ?>
                                                    <div style="font-size:10px;" class="text-muted mt-1">By: <?= htmlspecialchars($row['accounts_name']) ?></div>
                                                <?php endif; ?>
                                                <?php if (!empty($row['accounts_paid_at'])): ?>
                                                    <div style="font-size:10px;" class="text-muted">On: <?= date('d M Y h:i A', strtotime($row['accounts_paid_at'])) ?></div>
                                                <?php endif; ?>
                                            <?php elseif ($row['accounts_status'] === 'Rejected'): ?>
                                                <span class="badge bg-danger"><i class="fa-light fa-xmark me-1"></i>Accounts Rejected</span>
                                            <?php elseif ($row['mgmt_status'] === 'Approved'): ?>
                                                <?php if (!$isAhApprovedRow): ?>
                                                    <span class="badge bg-warning text-dark shadow-sm">
                                                        <i class="fa-solid fa-lock me-1"></i>Pending Accounts Head
                                                    </span>
                                                    <div style="font-size:10px;" class="text-secondary fw-semibold mt-1">
                                                        Md. Mamunoor Rashid [1309]
                                                    </div>
                                                <?php else: ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle mb-1">
                                                        <i class="fa-solid fa-check me-1"></i>AH Approved
                                                    </span>
                                                    <br>
                                                    <span class="badge bg-warning text-dark shadow-sm"><i class="fa-light fa-wallet me-1"></i>Pending Payment</span>
                                                    <div style="font-size:10px;" class="text-success fw-bold mt-1">
                                                        <i class="fa-light fa-circle-check me-1"></i>Ready for Accounts
                                                    </div>
                                                <?php endif; ?>
                                            <?php elseif ($row['mgmt_status'] === 'Rejected'): ?>
                                                <span class="badge bg-light text-muted border">Cancelled</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border">
                                                    <i class="fa-light fa-clock me-1"></i>Waiting for Mgmt
                                                </span>
                                                <div style="font-size:10px;" class="text-muted mt-1">Pending Mgmt first</div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="min-width: 220px;">
                                            <?php if ($row['accounts_status'] === 'Paid'): ?>
                                                <div class="border rounded p-2 bg-light-subtle shadow-sm" style="font-size:11px; line-height: 1.4;">
                                                    <!-- Expense Destination: কি কোথায় খরচ করেছে -->
                                                    <div class="mb-1 pb-1 border-bottom">
                                                        <span class="text-muted d-block" style="font-size:10px;">Expense Destination (কোথায় খরচ হয়েছে):</span>
                                                        <?php if ($row['requisition_type'] === 'Bayana'): ?>
                                                            <strong class="text-primary"><i class="fa-light fa-user-check me-1"></i>Paid to Landowner:</strong> 
                                                            <span class="fw-semibold text-dark"><?= htmlspecialchars($row['land_owner_name'] ?? 'Landowner') ?></span>
                                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1" style="font-size:9px;">Bayana Advance</span>
                                                        <?php elseif ($row['requisition_type'] === 'Registration'): ?>
                                                            <strong class="text-success"><i class="fa-light fa-file-signature me-1"></i>Deed Registration Cost</strong>
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle ms-1" style="font-size:9px;">Land Registry</span>
                                                        <?php else: ?>
                                                            <strong class="text-info"><i class="fa-light fa-tag me-1"></i><?= htmlspecialchars($row['cost_purpose'] ?? 'Extra Cost') ?></strong>
                                                            <span class="badge bg-info-subtle text-info border border-info-subtle ms-1" style="font-size:9px;">Project Overhead</span>
                                                        <?php endif; ?>
                                                    </div>

                                                    <!-- Payment method & Ref -->
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <span>Method: <strong><?= htmlspecialchars($row['payment_method'] ?? 'N/A') ?></strong></span>
                                                        <?php if (!empty($row['transaction_no'])): ?>
                                                            <span>Txn/Cheque: <code><?= htmlspecialchars($row['transaction_no']) ?></code></span>
                                                        <?php endif; ?>
                                                    </div>

                                                    <!-- Accounts remarks note if present -->
                                                    <?php if (!empty($row['accounts_note'])): ?>
                                                        <div class="bg-white p-1 rounded border text-secondary mb-1" style="font-size:10px;">
                                                            <i class="fa-light fa-comment-dots me-1 text-primary"></i><strong>Accounts Note:</strong> "<?= htmlspecialchars($row['accounts_note']) ?>"
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- Date & voucher links -->
                                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-1 mt-1 pt-1 border-top" style="font-size:10px;">
                                                        <span class="text-muted"><i class="fa-light fa-calendar-check me-1"></i><?= date('d M Y', strtotime($row['accounts_paid_at'])) ?></span>
                                                        <div class="d-flex gap-1">
                                                            <?php if (!empty($row['attachment_path'])): ?>
                                                                <a href="<?= $base_url ?>/public/<?= htmlspecialchars($row['attachment_path']) ?>" target="_blank" class="badge bg-info-subtle text-info border border-info-subtle text-decoration-none">
                                                                    <i class="fa-light fa-paperclip me-1"></i>Receipt
                                                                </a>
                                                            <?php endif; ?>
                                                            <a href="<?= $base_url ?>/print-debit-voucher?module=land_requisition&id=<?= $row['id'] ?>" target="_blank" class="badge bg-primary-subtle text-primary border border-primary-subtle text-decoration-none" title="Print Debit Voucher">
                                                                <i class="fa-light fa-file-invoice-dollar me-1"></i>Voucher
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php elseif ($row['mgmt_status'] === 'Approved'): ?>
                                                <div class="p-2 rounded bg-warning-subtle border border-warning-subtle text-warning text-center" style="font-size:11px;">
                                                    <i class="fa-light fa-clock-rotate-left me-1"></i><strong>Pending Payment</strong>
                                                    <div style="font-size:10px;" class="text-muted mt-1">Awaiting Accounts disbursement</div>
                                                </div>
                                            <?php elseif ($row['mgmt_status'] === 'Rejected'): ?>
                                                <span class="text-danger small"><i class="fa-light fa-circle-xmark me-1"></i>Rejected by Mgmt</span>
                                            <?php else: ?>
                                                <span class="text-muted small"><i class="fa-light fa-hourglass-start me-1"></i>Pending Mgmt Approval</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-1">
                                                <!-- Management & Operation Head (Role 20) Action -->
                                                <?php 
                                                    $isMgmt = in_array($roleId, [1, 3, 33]);
                                                    $isOpHead = in_array($roleId, [20, 32]);
                                                    $reqAmt = (float)($row['requested_amount'] ?? 0);
                                                    $isOver1LacRow = !empty($ahInfoRow['is_over_1lac']) || ($reqAmt > 100000);
                                                    $isOpHeadApprovedRow = !empty($ahInfoRow['is_op_head_approved']);
                                                ?>
                                                <?php if ($row['mgmt_status'] === 'Pending'): ?>
                                                    <?php if (!$isOver1LacRow): ?>
                                                        <!-- <= 1 Lac: Both Op Head and Management can approve directly -->
                                                        <?php if ($isMgmt || $isOpHead): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-success" 
                                                                    data-bs-toggle="modal" data-bs-target="#mgmtApproveModal"
                                                                    data-id="<?= $row['id'] ?>" data-amount="<?= $row['requested_amount'] ?>">
                                                                <i class="fa-light fa-check-to-slot me-1"></i>Approve/Reject
                                                            </button>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <!-- > 1 Lac: Op Head must approve first -->
                                                        <?php if (!$isOpHeadApprovedRow): ?>
                                                            <?php if ($isOpHead): ?>
                                                                <button type="button" class="btn btn-sm btn-outline-success" 
                                                                        data-bs-toggle="modal" data-bs-target="#mgmtApproveModal"
                                                                        data-id="<?= $row['id'] ?>" data-amount="<?= $row['requested_amount'] ?>"
                                                                        data-ophead="1">
                                                                    <i class="fa-light fa-check-to-slot me-1"></i>Approve (Op Head)
                                                                </button>
                                                            <?php elseif ($isMgmt): ?>
                                                                <span class="badge bg-light text-muted border py-1 px-2" style="font-size:10px;" title="Requests over ৳1,00,000 require Admin & Operation Head approval first">
                                                                    <i class="fa-light fa-lock me-1 text-warning"></i>Locked (Awaiting Op Head)
                                                                </span>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <!-- Op Head approved, now Management approves -->
                                                            <?php if ($isMgmt): ?>
                                                                <button type="button" class="btn btn-sm btn-success" 
                                                                        data-bs-toggle="modal" data-bs-target="#mgmtApproveModal"
                                                                        data-id="<?= $row['id'] ?>" data-amount="<?= $row['requested_amount'] ?>">
                                                                    <i class="fa-light fa-check-double me-1"></i>Approve (Mgmt)
                                                                </button>
                                                            <?php elseif ($isOpHead): ?>
                                                                <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2" style="font-size:10px;">
                                                                    <i class="fa-light fa-check me-1"></i>Op Head Done (Pending Mgmt)
                                                                </span>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                <?php endif; ?>

                                                <!-- Accounts Head Direct Approval / Waiting Action -->
                                                <?php if ($row['mgmt_status'] === 'Approved' && $row['accounts_status'] === 'Pending' && !$isAhApprovedRow): ?>
                                                    <?php if ($canAhApproveRow && !empty($ahInfoRow['approval_request_id'])): ?>
                                                        <button type="button" class="btn btn-sm btn-primary shadow-sm"
                                                                onclick="triggerAhApprove(<?= (int)$ahInfoRow['approval_request_id'] ?>)">
                                                            <i class="fa-solid fa-stamp me-1"></i>AH Approve
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="badge bg-light text-muted border py-1 px-2" style="font-size:10px;" title="Accounts Head approval required (Md. Mamunoor Rashid [1309])">
                                                            <i class="fa-solid fa-lock me-1 text-warning"></i>Awaiting AH (1309)
                                                        </span>
                                                    <?php endif; ?>
                                                <?php endif; ?>

                                                <!-- Accounts Action (Allowed only after Accounts Head approved) -->
                                                <?php if ($row['mgmt_status'] === 'Approved' && $row['accounts_status'] === 'Pending' && $isAhApprovedRow && in_array($roleId, [1, 6, 30])): ?>
                                                    <button type="button" class="btn btn-sm btn-success" 
                                                            data-bs-toggle="modal" data-bs-target="#accountsPayModal"
                                                            data-id="<?= $row['id'] ?>" data-amount="<?= $row['requested_amount'] ?>">
                                                        <i class="fa-solid fa-money-bill-wave me-1"></i>Record Payment
                                                    </button>
                                                <?php endif; ?>

                                                <?php if ($row['accounts_status'] === 'Paid'): ?>
                                                    <a href="<?= $base_url ?>/print-debit-voucher?module=land_requisition&id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Print Debit Voucher">
                                                        <i class="fa-light fa-file-invoice-dollar"></i>
                                                    </a>
                                                <?php endif; ?>

                                                <?php if ($roleId === 1 || $roleId === 3): ?>
                                                <form action="<?= $base_url ?>/con-land-requisitions/delete" method="POST" onsubmit="return confirm('Are you sure you want to delete this requisition?');" class="d-inline">
                                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fa-light fa-trash"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </div>
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

<!-- Request Fund Modal -->
<div id="requestFundModal" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-light fa-money-check-dollar me-2"></i>Create Land Fund Requisition</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= $base_url ?>/con-land-requisitions/store" method="POST" enctype="multipart/form-data">
                    <div class="row gy-10">
                        <div class="col-xl-12">
                            <div class="card__wrapper">
                                <div class="row gy-20">
                                    <!-- Project Select (Optional) -->
                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Project <span class="text-muted small">(Optional - auto-assigned from Land Deal)</span></label>
                                            </div>
                                            <div class="form__input">
                                                <select class="form-control select2-project-req-modal" name="project_id" id="modal_req_project_id" data-placeholder="Choose or change project (Optional)...">
                                                    <option value="">-- No Project Selected / In Planning --</option>
                                                    <?php foreach ($projects as $p): ?>
                                                        <option value="<?= $p['id'] ?>" <?= ($selectedProjectId ?? 0) == $p['id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($p['name']) ?> <?= !empty($p['project_status']) ? '('.$p['project_status'].')' : '' ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Land Acquisition Deal Select -->
                                    <div class="col-lg-12" id="portfolio_select_group">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Select Land Acquisition Deal / Landowner <span class="text-danger">*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <select class="form-control" name="land_acquisition_id" id="modal_req_la_id" required>
                                                    <option value="" disabled selected>-- Choose Land Acquisition Deal --</option>
                                                    <?php foreach ($acquisitions as $la): ?>
                                                        <option value="<?= $la['id'] ?>" 
                                                                data-project-id="<?= $la['project_id'] ?>"
                                                                data-val="<?= (float)$la['total_amount'] ?>"
                                                                data-paid-land="<?= (float)$la['paid_land'] ?>"
                                                                data-paid-extra="<?= (float)$la['paid_extra'] ?>"
                                                                data-due="<?= (float)$la['remaining_due'] ?>"
                                                                data-pending="<?= (float)$la['pending_amount'] ?>"
                                                                data-name="<?= htmlspecialchars($la['effective_project_name']) ?>"
                                                                data-owner="<?= htmlspecialchars($la['land_owner_name']) ?>"
                                                                <?= ($selectedAcqId ?? 0) == $la['id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($la['effective_project_name']) ?> — Owner: <?= htmlspecialchars($la['land_owner_name']) ?> (Val: BDT <?= number_format($la['total_amount'] / 100000, 1) ?>L, Due: BDT <?= number_format($la['remaining_due'] / 100000, 1) ?>L)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <small class="text-muted" style="font-size: 11px;">Select the specific land acquisition portfolio to track against its agreed valuation.</small>
                                        </div>
                                    </div>

                                    <!-- Interactive Land Financial Summary Widget -->
                                    <div class="col-12" id="modal_la_financial_widget" style="display:none;">
                                        <div class="card border rounded-3 bg-light-subtle shadow-sm p-3 mb-1">
                                            <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2">
                                                <div>
                                                    <strong class="text-primary fs-6" id="card_la_name">Land Deal Details</strong>
                                                    <span class="text-muted small ms-2" id="card_la_owner"></span>
                                                </div>
                                                <span class="badge bg-secondary" id="card_la_status_badge">Active Deal</span>
                                            </div>
                                            <div class="row g-2 text-center">
                                                <div class="col-md-3 col-6">
                                                    <div class="p-2 border rounded bg-white">
                                                        <span class="text-muted d-block small" style="font-size:11px;">Agreed Land Valuation</span>
                                                        <strong class="text-dark fs-6" id="card_la_val">BDT 0.00</strong>
                                                    </div>
                                                </div>
                                                <div class="col-md-3 col-6">
                                                    <div class="p-2 border rounded bg-white">
                                                        <span class="text-muted d-block small" style="font-size:11px;">Paid to Landowner</span>
                                                        <strong class="text-success fs-6" id="card_la_paid">BDT 0.00</strong>
                                                    </div>
                                                </div>
                                                <div class="col-md-3 col-6">
                                                    <div class="p-2 border rounded bg-white">
                                                        <span class="text-muted d-block small" style="font-size:11px;">Remaining Land Due</span>
                                                        <strong class="text-danger fs-6" id="card_la_due">BDT 0.00</strong>
                                                    </div>
                                                </div>
                                                <div class="col-md-3 col-6">
                                                    <div class="p-2 border rounded bg-white">
                                                        <span class="text-muted d-block small" style="font-size:11px;">Extra Costs Paid</span>
                                                        <strong class="text-info fs-6" id="card_la_extra">BDT 0.00</strong>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Requisition Type -->
                                    <div class="col-lg-6">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Requisition Type <span class="text-danger">*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <select class="form-control" name="requisition_type" id="modal_requisition_type" required>
                                                    <option value="Bayana" selected>Bayana (Advance Payment)</option>
                                                    <option value="Registration">Deed Registration Fee</option>
                                                    <option value="Extra Cost">Extra Cost (Mutation/Legal/Brokerage)</option>
                                                    <option value="Other">Other</option>
                                                </select>
                                            </div>
                                            <small class="text-muted" style="font-size:11px;" id="req_type_helper_text">Bayana & Registration reduce the agreed Land Price balance.</small>
                                        </div>
                                    </div>

                                    <!-- Extra Cost Purpose (Conditional) -->
                                    <div class="col-lg-6" id="cost_purpose_group" style="display:none;">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Extra Cost Category <span class="text-danger">*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <select class="form-control" name="cost_purpose" id="modal_cost_purpose">
                                                    <option value="Media / Broker Commission">Media / Broker Commission (মিডিয়া / ব্রোকার কমিশন)</option>
                                                    <option value="Mutation Fee">Mutation Fee (নামজারি খরচ)</option>
                                                    <option value="Legal & Deed Vetting Fee">Legal & Deed Vetting Fee (আইনি ও দলিল যাচাই ফি)</option>
                                                    <option value="Land Demarcation & Survey">Land Demarcation & Survey (সীমানা নির্ধারণ ও সার্ভে)</option>
                                                    <option value="Sub-Registry Documentation">Sub-Registry Documentation (সাব-রেজিস্ট্রি ডকুমেন্টেশন)</option>
                                                    <option value="Stamp Duty & Taxes">Stamp Duty & Taxes (স্ট্যাম্প ডিউটি ও ট্যাক্স)</option>
                                                    <option value="Other Extra Cost">Other Extra Cost (অন্যান্য অতিরিক্ত খরচ)</option>
                                                </select>
                                            </div>
                                            <small class="text-info" style="font-size:11px;"><i class="fa-light fa-info-circle me-1"></i>Extra Cost is accounted separately from the Land Price (50 Lac limit).</small>
                                        </div>
                                    </div>

                                    <!-- Requested Amount -->
                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Requested Amount (BDT) <span class="text-danger">*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <input type="number" step="0.01" class="form-control" name="requested_amount" id="modal_requested_amount" required placeholder="0.00">
                                            </div>
                                            <div id="modal_amount_feedback" class="mt-1 small"></div>
                                        </div>
                                    </div>
                                    <!-- Account Head -->
                                    <div class="col-lg-12">
                                        <div class="from__input-box mb-3">
                                            <?= renderCOADropdown($coaList ?? [], null, 'account_id', false, 'landreq') ?>
                                        </div>
                                    </div>

                                    <!-- Purpose -->
                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Detailed Purpose <span class="text-danger">*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <textarea class="form-control" name="purpose" rows="3" required placeholder="Describe the reason/purpose for requesting this amount"></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Attachment -->
                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Requisition Attachment (Receipt / Estimation / Document)</label>
                                            </div>
                                            <div class="form__input">
                                                <input type="file" class="form-control" name="req_attachment">
                                                <small class="text-muted">Max file size: 2MB. Format: JPG, PNG, PDF, DOCX</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="submit__btn text-center mt-3">
                        <button class="btn btn-primary px-5" type="submit" id="modal_submit_btn"><i class="fa-light fa-paper-plane me-1"></i> Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Management Approval Modal -->
<div id="mgmtApproveModal" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-light fa-stamp me-2 text-success"></i>Requisition Approval Decision</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= $base_url ?>/con-land-requisitions/approve" method="POST">
                    <input type="hidden" name="id" id="mgmt_req_id">
                    <div class="row gy-10">
                        <div class="col-xl-12">
                            <div class="card__wrapper">
                                <div class="row gy-20">
                                    <div class="col-lg-12">
                                        <h5 class="mb-2">Requested Amount: <strong class="text-success">BDT <span id="mgmt_req_amount"></span></strong></h5>
                                        <?php if (in_array($roleId, [20, 32])): ?>
                                            <div class="alert alert-info py-2 small mb-3">
                                                <i class="fa-light fa-info-circle me-1"></i><strong>Operation Head Authorization:</strong> For requisitions up to BDT 100,000 (1 Lac), you can approve directly. For requisitions over BDT 100,000, your approval verifies the request (Step 1) and forwards it to Management.
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Approval Decision</label>
                                            </div>
                                            <div class="form__input">
                                                <select class="form-control" name="status" required>
                                                    <option value="Approved" selected>Approve Request</option>
                                                    <option value="Rejected">Reject Request</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Remarks / Notes</label>
                                            </div>
                                            <div class="form__input">
                                                <textarea class="form-control" name="mgmt_note" rows="3" placeholder="Provide reason or comments"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="submit__btn text-center mt-3">
                        <button class="btn btn-primary" type="submit">Save Decision</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Accounts Payment Modal -->
<div id="accountsPayModal" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Payment Detail</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= $base_url ?>/con-land-requisitions/pay" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="accounts_req_id">
                    <div class="row gy-10">
                        <div class="col-xl-12">
                            <div class="card__wrapper">
                                <div class="row gy-20">
                                    <div class="col-lg-12">
                                        <h5 class="mb-3">Pay Amount: <strong class="text-success">BDT <span id="accounts_req_amount"></span></strong></h5>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Payment Decision</label>
                                            </div>
                                            <div class="form__input">
                                                <select class="form-control" name="status" required>
                                                    <option value="Paid" selected>Mark as Paid</option>
                                                    <option value="Rejected">Reject Payment</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Payment Method</label>
                                            </div>
                                            <div class="form__input">
                                                <select class="form-control" name="payment_method" required>
                                                    <option value="Bank" selected>Bank Transfer / Cheque</option>
                                                    <option value="Cash">Cash</option>
                                                    <option value="Mobile Banking">Mobile Banking (bKash/Nagad/Rocket)</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Transaction / Reference / Cheque No.</label>
                                            </div>
                                            <div class="form__input">
                                                <input type="text" class="form-control" name="transaction_no" placeholder="Txn ID, Cheque Number etc.">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Payment Attachment (Receipt / Document)</label>
                                            </div>
                                            <div class="form__input">
                                                <input type="file" class="form-control" name="attachment">
                                                <small class="text-muted">Max file size: 2MB. Format: JPG, PNG, PDF, DOCX</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Accounts Note</label>
                                            </div>
                                            <div class="form__input">
                                                <textarea class="form-control" name="accounts_note" rows="3" placeholder="Provide payment details or note"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="submit__btn text-center mt-3">
                        <button class="btn btn-primary" type="submit">Record Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<?php
$page_script = "(function() {
    function init() {
        // Set up event listeners for modal data binding
        const mgmtApproveModal = document.getElementById('mgmtApproveModal');
        if (mgmtApproveModal) {
            mgmtApproveModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const amount = button.getAttribute('data-amount');
                
                document.getElementById('mgmt_req_id').value = id;
                document.getElementById('mgmt_req_amount').textContent = parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2});
            });
        }

        const accountsPayModal = document.getElementById('accountsPayModal');
        if (accountsPayModal) {
            accountsPayModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                if (!button) return;
                const id = button.getAttribute('data-id');
                const amount = button.getAttribute('data-amount');
                
                document.getElementById('accounts_req_id').value = id;
                document.getElementById('accounts_req_amount').textContent = parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2});
            });

            // Auto-open modal if pay_id or req_id is in URL query parameters
            const urlParams = new URLSearchParams(window.location.search);
            const targetPayId = urlParams.get('pay_id') || urlParams.get('req_id');
            if (targetPayId) {
                const payBtn = document.querySelector('button[data-bs-target=\"#accountsPayModal\"][data-id=\"' + targetPayId + '\"]');
                if (payBtn) {
                    setTimeout(function() {
                        payBtn.click();
                    }, 250);
                }
            }
        }

        // Initialize Select2 for Project filter and Modal Project select
        if (typeof $ !== 'undefined' && $.fn && $.fn.select2) {
            $('.select2-filter-project').select2({
                width: '260px',
                placeholder: 'Search Project...'
            }).on('select2:select', function() {
                $(this).closest('form').submit();
            });

            $('#requestFundModal').on('shown.bs.modal', function() {
                $('#modal_req_project_id').select2({
                    dropdownParent: $('#requestFundModal'),
                    width: '100%',
                    placeholder: 'Search or Select Project...'
                });
            });
        }

        // Filter Land Acquisition Portfolios based on selected project
        const projSelect = document.getElementById('modal_req_project_id');
        const laSelect = document.getElementById('modal_req_la_id');
        const reqTypeSelect = document.getElementById('modal_requisition_type');
        const costPurposeGroup = document.getElementById('cost_purpose_group');
        const reqTypeHelper = document.getElementById('req_type_helper_text');
        const amtInput = document.getElementById('modal_requested_amount');
        const feedbackDiv = document.getElementById('modal_amount_feedback');
        const submitBtn = document.getElementById('modal_submit_btn');

        function updateLaFinancialWidget() {
            if (!laSelect) return;
            const selOpt = laSelect.options[laSelect.selectedIndex];
            const widget = document.getElementById('modal_la_financial_widget');
            if (!selOpt || !selOpt.value) {
                if (widget) widget.style.display = 'none';
                validateAmount();
                return;
            }
            const val = parseFloat(selOpt.getAttribute('data-val')) || 0;
            const paidLand = parseFloat(selOpt.getAttribute('data-paid-land')) || 0;
            const paidExtra = parseFloat(selOpt.getAttribute('data-paid-extra')) || 0;
            const due = parseFloat(selOpt.getAttribute('data-due')) || 0;
            const name = selOpt.getAttribute('data-name') || '';
            const owner = selOpt.getAttribute('data-owner') || '';

            if (widget) {
                widget.style.display = 'block';
                const elName = document.getElementById('card_la_name');
                const elOwner = document.getElementById('card_la_owner');
                const elVal = document.getElementById('card_la_val');
                const elPaid = document.getElementById('card_la_paid');
                const elDue = document.getElementById('card_la_due');
                const elExtra = document.getElementById('card_la_extra');

                if (elName) elName.textContent = name;
                if (elOwner) elOwner.textContent = '• Owner: ' + owner;
                if (elVal) elVal.textContent = 'BDT ' + val.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                if (elPaid) elPaid.textContent = 'BDT ' + paidLand.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                if (elDue) elDue.textContent = 'BDT ' + due.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                if (elExtra) elExtra.textContent = 'BDT ' + paidExtra.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }

            // If acquisition has a project_id, sync projSelect if not set
            const optProjId = selOpt.getAttribute('data-project-id');
            if (optProjId && projSelect && (!projSelect.value || projSelect.value !== optProjId)) {
                projSelect.value = optProjId;
                if (typeof $ !== 'undefined' && $('#modal_req_project_id').data('select2')) {
                    $('#modal_req_project_id').val(optProjId).trigger('change.select2');
                }
            }

            validateAmount();
        }

        function handleReqTypeChange() {
            if (!reqTypeSelect) return;
            const type = reqTypeSelect.value;
            if (type === 'Extra Cost' || type === 'Other') {
                if (costPurposeGroup) costPurposeGroup.style.display = 'block';
                if (reqTypeHelper) {
                    reqTypeHelper.innerHTML = '<span class=\"badge bg-info-subtle text-info me-1\"><i class=\"fa-light fa-info-circle\"></i> Outside Land Valuation</span> Extra Cost & Other are tracked separately from the agreed Land Price (will NOT reduce remaining land due).';
                }
            } else {
                if (costPurposeGroup) costPurposeGroup.style.display = 'none';
                if (reqTypeHelper) {
                    reqTypeHelper.innerHTML = '<span class=\"badge bg-warning-subtle text-warning me-1\"><i class=\"fa-light fa-coins\"></i> Deducts from Land Price</span> Bayana & Registration are capped by the agreed Land Valuation and reduce the remaining balance.';
                }
            }
            validateAmount();
        }

        function validateAmount() {
            if (!amtInput || !feedbackDiv) return;
            const val = parseFloat(amtInput.value) || 0;
            const selOpt = laSelect && laSelect.selectedIndex >= 0 ? laSelect.options[laSelect.selectedIndex] : null;
            const type = reqTypeSelect ? reqTypeSelect.value : 'Bayana';

            if (!selOpt || !selOpt.value) {
                feedbackDiv.innerHTML = '';
                if (submitBtn) submitBtn.disabled = false;
                amtInput.classList.remove('is-invalid', 'is-valid');
                return;
            }

            const due = parseFloat(selOpt.getAttribute('data-due')) || 0;

            if (type === 'Bayana' || type === 'Registration') {
                if (val > due + 0.01) {
                    feedbackDiv.innerHTML = '<div class=\"alert alert-danger py-1 px-2 mb-0 d-flex align-items-center gap-2\" style=\"font-size:12px;\"><i class=\"fa-solid fa-circle-exclamation text-danger fs-6\"></i> <div><strong>Budget Limit Exceeded:</strong> Requested amount BDT ' + val.toLocaleString('en-US', {minimumFractionDigits: 2}) + ' exceeds remaining land price balance of BDT ' + due.toLocaleString('en-US', {minimumFractionDigits: 2}) + '. (Extra costs like mutation, broker commission etc. should be requested under <b>Extra Cost</b>).</div></div>';
                    amtInput.classList.add('is-invalid');
                    amtInput.classList.remove('is-valid');
                    if (submitBtn) submitBtn.disabled = true;
                } else if (val > 0) {
                    const projectedDue = Math.max(0, due - val);
                    feedbackDiv.innerHTML = '<div class=\"alert alert-success py-1 px-2 mb-0 d-flex align-items-center gap-2\" style=\"font-size:12px;\"><i class=\"fa-solid fa-circle-check text-success fs-6\"></i> <div><strong>Valid Land Payment:</strong> Projected remaining landowner balance after this payment: <b>BDT ' + projectedDue.toLocaleString('en-US', {minimumFractionDigits: 2}) + '</b></div></div>';
                    amtInput.classList.remove('is-invalid');
                    amtInput.classList.add('is-valid');
                    if (submitBtn) submitBtn.disabled = false;
                } else {
                    feedbackDiv.innerHTML = '';
                    amtInput.classList.remove('is-invalid', 'is-valid');
                    if (submitBtn) submitBtn.disabled = false;
                }
            } else {
                // Extra Cost or Other
                if (val > 0) {
                    feedbackDiv.innerHTML = '<div class=\"alert alert-info py-1 px-2 mb-0 d-flex align-items-center gap-2\" style=\"font-size:12px;\"><i class=\"fa-solid fa-circle-info text-info fs-6\"></i> <div><strong>Extra Overhead:</strong> This amount will be booked as extra project overhead without deducting from the landowner agreed price balance.</div></div>';
                    amtInput.classList.remove('is-invalid');
                    amtInput.classList.add('is-valid');
                    if (submitBtn) submitBtn.disabled = false;
                } else {
                    feedbackDiv.innerHTML = '';
                    amtInput.classList.remove('is-invalid', 'is-valid');
                    if (submitBtn) submitBtn.disabled = false;
                }
            }
        }

        if (projSelect && laSelect) {
            const filterPortfolios = function() {
                const selProjId = projSelect.value;
                const options = laSelect.querySelectorAll('option');
                let matchCount = 0;
                options.forEach(function(opt) {
                    const optProj = opt.getAttribute('data-project-id');
                    if (!selProjId || !optProj) {
                        opt.style.display = '';
                        if (opt.value) matchCount++;
                    } else if (optProj === selProjId) {
                        opt.style.display = '';
                        matchCount++;
                    } else {
                        opt.style.display = 'none';
                    }
                });
                if (matchCount > 0 && selProjId) {
                    for (let i = 0; i < options.length; i++) {
                        if (options[i].getAttribute('data-project-id') === selProjId) {
                            laSelect.value = options[i].value;
                            break;
                        }
                    }
                }
                updateLaFinancialWidget();
            };

            if (typeof $ !== 'undefined') {
                $('#modal_req_project_id').on('change select2:select', filterPortfolios);
            } else {
                projSelect.addEventListener('change', filterPortfolios);
            }

            laSelect.addEventListener('change', updateLaFinancialWidget);
            if (reqTypeSelect) reqTypeSelect.addEventListener('change', handleReqTypeChange);
            if (amtInput) amtInput.addEventListener('input', validateAmount);

            // Initial trigger
            if (laSelect.value) {
                updateLaFinancialWidget();
            } else if (projSelect.value) {
                filterPortfolios();
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
";
?>

<?php 
$ahInfo = null;
include __DIR__ . '/../../layouts/accounts_head_approval_card.php'; 
?>
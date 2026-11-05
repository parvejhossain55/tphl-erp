<?php
$page_title = "Expense Adjustments";
$page = 'Accounts';
$show_breadcrumb = true;
?>
<!-- Executive Management Approval Schedule Notice -->
<?php include __DIR__ . '/../../layouts/management_approval_notice.php'; ?>

<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="card__header d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h5 class="card__title mb-1">
                        <i class="fa-light fa-sliders me-2 text-primary"></i>Requisition Settlements & Adjustments
                    </h5>
                    <small class="text-muted">Manage return of unused funds and extra budget requests across all modules</small>
                </div>
                
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <?php if ($isApprover): ?>
                        <div class="btn-group shadow-sm">
                            <a href="<?= $GLOBALS['base_url'] ?>/expense-adjustments?view=actionable" 
                               class="btn btn-sm <?= $viewType === 'actionable' ? 'btn-primary active' : 'btn-outline-primary' ?>">
                                <i class="fa-light fa-bell me-1"></i>
                                <?= htmlspecialchars($actionableTabName ?? 'Action Required') ?>
                                <?php if (!empty($counts['actionable']) && $counts['actionable'] > 0): ?>
                                    <span class="badge bg-danger rounded-pill ms-1"><?= $counts['actionable'] ?></span>
                                <?php endif; ?>
                            </a>

                            <a href="<?= $GLOBALS['base_url'] ?>/expense-adjustments?view=my" 
                               class="btn btn-sm <?= $viewType === 'my' ? 'btn-primary active' : 'btn-outline-primary' ?>">
                                <i class="fa-light fa-user me-1"></i>
                                My Submissions
                                <?php if (!empty($counts['my']) && $counts['my'] > 0): ?>
                                    <span class="badge bg-secondary rounded-pill ms-1"><?= $counts['my'] ?></span>
                                <?php endif; ?>
                            </a>

                            <a href="<?= $GLOBALS['base_url'] ?>/expense-adjustments?view=all" 
                               class="btn btn-sm <?= $viewType === 'all' ? 'btn-primary active' : 'btn-outline-primary' ?>">
                                <i class="fa-light fa-list-check me-1"></i>
                                All Adjustments
                                <?php if (!empty($counts['all']) && $counts['all'] > 0): ?>
                                    <span class="badge bg-light text-dark border rounded-pill ms-1"><?= $counts['all'] ?></span>
                                <?php endif; ?>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="btn-group shadow-sm">
                            <span class="btn btn-sm btn-primary active">
                                <i class="fa-light fa-user me-1"></i> My Adjustments
                                <?php if (!empty($counts['my']) && $counts['my'] > 0): ?>
                                    <span class="badge bg-white text-primary rounded-pill ms-1"><?= $counts['my'] ?></span>
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <a href="<?= $GLOBALS['base_url'] ?>/expense-adjustments/create" class="btn btn-sm btn-success shadow-sm">
                        <i class="fa-light fa-plus-circle me-1"></i> New Settlement Request
                    </a>
                </div>
            </div>
            
            <div class="card__body">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= $_SESSION['success']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <?php unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= $_SESSION['error']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <?php unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <?php if ($canMgmt && $canQc && $viewType === 'actionable'): ?>
                    <div class="d-flex align-items-center flex-wrap gap-2 mb-3 bg-light p-2 rounded border">
                        <span class="small fw-bold text-secondary me-1"><i class="fa-light fa-filter me-1"></i> Stage View:</span>
                        <a href="<?= $GLOBALS['base_url'] ?>/expense-adjustments?view=actionable&stage=qc" 
                           class="badge text-decoration-none py-2 px-3 <?= ($actionableRole === 'qc') ? 'bg-warning text-dark shadow-sm' : 'bg-white text-secondary border' ?>">
                            <i class="fa-light fa-magnifying-glass-chart me-1"></i> QC Audit Queue
                        </a>
                        <a href="<?= $GLOBALS['base_url'] ?>/expense-adjustments?view=actionable&stage=management" 
                           class="badge text-decoration-none py-2 px-3 <?= ($actionableRole === 'management') ? 'bg-primary text-white shadow-sm' : 'bg-white text-secondary border' ?>">
                            <i class="fa-light fa-user-check me-1"></i> Management Queue
                        </a>
                        <a href="<?= $GLOBALS['base_url'] ?>/expense-adjustments?view=actionable&stage=accounts" 
                           class="badge text-decoration-none py-2 px-3 <?= ($actionableRole === 'accounts') ? 'bg-info text-dark shadow-sm' : 'bg-white text-secondary border' ?>">
                            <i class="fa-light fa-receipt me-1"></i> Accounts Action Queue
                        </a>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0" id="adjustmentTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 80px;">ID</th>
                                <th>Type</th>
                                <th>Requester</th>
                                <th>Original Requisition Info</th>
                                <th>Requested / Approved</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th class="text-end" style="width: 120px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        
                                <?php foreach ($adjustments as $row): 
                                    $isReturn = ($row['adjustment_type'] === 'return');
                                    
                                    // Status Badge & Text
                                    $statusBadge = 'bg-secondary';
                                    $statusText = strtoupper(str_replace('_', ' ', $row['status']));
                                    if ($row['status'] === 'pending') {
                                        $statusBadge = 'bg-warning text-dark';
                                        $statusText = $isReturn ? 'PENDING ACCOUNTS' : 'PENDING QC AUDIT';
                                    } elseif ($row['status'] === 'qc_approved') {
                                        $statusBadge = 'bg-primary text-white';
                                        $statusText = 'QC AUDITED (PENDING MGMT)';
                                    } elseif ($row['status'] === 'approved') {
                                        $statusBadge = 'bg-info text-dark';
                                        $statusText = 'APPROVED (PENDING PAYOUT)';
                                    } elseif ($row['status'] === 'partially_paid') {
                                        $statusBadge = 'bg-info text-dark';
                                        $statusText = 'PARTIALLY SETTLED';
                                    } elseif ($row['status'] === 'fully_paid') {
                                        $statusBadge = 'bg-success';
                                        $statusText = $isReturn ? 'VERIFIED & RETURNED' : 'SETTLED & PAID';
                                    } elseif ($row['status'] === 'rejected') {
                                        $statusBadge = 'bg-danger';
                                        $statusText = 'REJECTED';
                                    }

                                    // Actionability check for current user
                                    $isActionable = false;
                                    $actionBtnText = 'View';
                                    $actionBtnClass = 'btn-outline-primary';

                                    if ($isAccounts) {
                                        if ($isReturn && in_array($row['status'], ['pending', 'partially_paid'])) {
                                            $isActionable = true;
                                            $actionBtnText = 'Verify & Receive';
                                            $actionBtnClass = 'btn-success';
                                        } elseif (!$isReturn && in_array($row['status'], ['approved', 'partially_paid'])) {
                                            $isActionable = true;
                                            $actionBtnText = 'Disburse Payout';
                                            $actionBtnClass = 'btn-primary';
                                        }
                                    } elseif ($canQc && !$isReturn && $row['status'] === 'pending') {
                                        $isActionable = true;
                                        $actionBtnText = 'QC Audit';
                                        $actionBtnClass = 'btn-warning text-dark';
                                    } elseif ($canMgmt && !$isReturn && $row['status'] === 'qc_approved') {
                                        $isActionable = true;
                                        $actionBtnText = 'Review & Approve';
                                        $actionBtnClass = 'btn-primary';
                                    }

                                    // Parent links
                                    $parentLink = '#';
                                    $parentPrefix = 'REQ';
                                    $parentName = 'Cashout';
                                    if ($row['parent_type'] === 'vendor') {
                                        $parentLink = $GLOBALS['base_url'] . '/vendor-payment-requests/show?id=' . $row['cashout_request_id'];
                                        $parentPrefix = 'VENDORREQ';
                                        $parentName = 'Vendor Payment';
                                    } elseif ($row['parent_type'] === 'contractor') {
                                        $parentLink = $GLOBALS['base_url'] . '/con-contractor-bill/' . $row['cashout_request_id'];
                                        $parentPrefix = 'CONTRACTORREQ';
                                        $parentName = 'Contractor Bill';
                                    } elseif ($row['parent_type'] === 'purchase') {
                                        $parentLink = $GLOBALS['base_url'] . '/purchase-requests/show?id=' . $row['cashout_request_id'];
                                        $parentPrefix = 'PURCHASEREQ';
                                        $parentName = 'Purchase Request';
                                    } elseif ($row['parent_type'] === 'other_cost') {
                                        $parentLink = $GLOBALS['base_url'] . '/project-other-costs';
                                        $parentPrefix = 'OTHERCOST';
                                        $parentName = 'Project Other Cost';
                                    } elseif ($row['parent_type'] === 'extra_purchase') {
                                        $parentLink = $GLOBALS['base_url'] . '/asset-extra-purchases';
                                        $parentPrefix = 'EXTRAASSET';
                                        $parentName = 'Extra Asset Purchase';
                                    } elseif ($row['parent_type'] === 'cashout') {
                                        $parentLink = $GLOBALS['base_url'] . '/view-cashout-request?id=' . $row['cashout_request_id'];
                                        $parentPrefix = 'REQ';
                                        $parentName = 'Cashout Request';
                                    }
                                ?>
                                    <tr class="<?= $isActionable ? 'table-warning-subtle' : '' ?>">
                                        <td>
                                            <a href="<?= $GLOBALS['base_url'] ?>/expense-adjustments/show?id=<?= $row['id'] ?>" class="fw-bold text-primary">
                                                ADJ-<?= $row['id'] ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?php if ($isReturn): ?>
                                                <span class="badge bg-info-subtle text-info border border-info px-2 py-1">
                                                    <i class="fa-light fa-arrow-turn-down-left me-1"></i> Return Unused
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-2 py-1">
                                                    <i class="fa-light fa-circle-plus me-1"></i> Extra Amount
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($row['requester_name']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($row['requester_role'] ?? 'Staff') ?></small>
                                        </td>
                                        <td>
                                            <div>
                                                <a href="<?= $parentLink ?>" target="_blank" class="fw-semibold text-decoration-none">
                                                    <?= $parentPrefix ?>-<?= $row['cashout_request_id'] ?> <i class="fa-light fa-external-link extra-small"></i>
                                                </a>
                                                <small class="text-muted">(<?= htmlspecialchars($parentName) ?>)</small>
                                            </div>
                                            <div class="small text-truncate" style="max-width: 260px;" title="<?= htmlspecialchars($row['original_purpose'] ?? '') ?>">
                                                <?= htmlspecialchars($row['original_purpose'] ?? '—') ?>
                                            </div>
                                            <?php if (!empty($row['project_name'])): ?>
                                                <span class="badge bg-light text-secondary border extra-small mt-1">
                                                    <i class="fa-light fa-building me-1"></i><?= htmlspecialchars($row['project_name']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div>
                                                <small class="text-muted d-block">Requested:</small>
                                                <strong class="<?= $isReturn ? 'text-success' : 'text-danger' ?>">
                                                    TK. <?= number_format($row['amount'], 2) ?>
                                                </strong>
                                            </div>
                                            <?php if (!empty($row['approved_amount'])): ?>
                                                <div class="mt-1">
                                                    <small class="text-muted d-block">Approved:</small>
                                                    <strong class="text-primary">
                                                        TK. <?= number_format($row['approved_amount'], 2) ?>
                                                    </strong>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div><?= date('d M Y', strtotime($row['created_at'])) ?></div>
                                            <small class="text-muted"><?= date('h:i A', strtotime($row['created_at'])) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge <?= $statusBadge ?> px-2 py-1"><?= $statusText ?></span>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= $GLOBALS['base_url'] ?>/expense-adjustments/show?id=<?= $row['id'] ?>" 
                                               class="btn btn-sm <?= $actionBtnClass ?> text-nowrap shadow-sm">
                                                <?php if ($isActionable): ?>
                                                    <i class="fa-light fa-arrow-right-to-bracket me-1"></i> <?= $actionBtnText ?>
                                                <?php else: ?>
                                                    <i class="fa-light fa-eye me-1"></i> View
                                                <?php endif; ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof jQuery !== 'undefined' && $.fn.DataTable) {
        $('#adjustmentTable').DataTable({
            "order": [[0, "desc"]],
            "pageLength": 25
        });
    }
});
</script>

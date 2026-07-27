<?php
$page_title = "My Cashout Requests";
$page = 'Cashout';
$show_breadcrumb = true;

// Get user role name
$roleNames = [
    1 => 'Admin',
    2 => 'Employee',
    3 => 'Super Admin',
    4 => 'HR',
    5 => 'Sales Head',
    6 => 'Accounts',
    7 => 'Clients Experiment',
    30 => 'Accounts Head'
];
$userRoleName = $roleNames[$userRole ?? 2] ?? 'Employee';

?>
<style>
    .status-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .status-pending {
        background: #fff3cd;
        color: #856404;
    }

    .status-approved {
        background: #d4edda;
        color: #155724;
    }

    .status-qc_approved {
        background: #e2e3e5;
        color: #383d41;
    }

    .status-rejected {
        background: #f8d7da;
        color: #721c24;
    }

    .status-partial {
        background: #cce5ff;
        color: #004085;
    }

    .status-paid {
        background: #d1ecf1;
        color: #0c5460;
    }
</style>

<div class="row">
    <!-- User Info Card -->
    <div class="col-xxl-12 mb-4">
        <div class="card__wrapper">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="card__sub-title mb-1">Welcome, <?= $_SESSION['user']['full_name'] ?? 'User' ?></h6>
                    <p class="text-muted mb-0">Role: <?= $userRoleName ?></p>
                </div>
                <div class="text-end">
                    <a href="<?= $GLOBALS['base_url'] ?>/create-cashout-request" class="btn btn-success">
                        <i class="fa-light fa-plus me-2"></i> New Cashout Request
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-list"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">My Requests</h6>
                    <h4 class="card__title fs-6"><?= $statistics['total_requests'] ?? 0 ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-money-bill-wave"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Amount</h6>
                    <h4 class="card__title fs-6">TK.<?= number_format($statistics['total_amount'] ?? 0, 2) ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-clock"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Due Amount</h6>
                    <h4 class="card__title fs-6">TK.<?= number_format($statistics['total_due'] ?? 0, 2) ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-check-circle"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Approved</h6>
                    <h4 class="card__title fs-6"><?= $statistics['approved_count'] ?? 0 ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- NEW: Approved Amount Statistics Card -->
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-check-double"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Approved Amount</h6>
                    <h4 class="card__title fs-6">TK.<?= number_format($statistics['total_approved'] ?? 0, 2) ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Requests Table -->
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="card__header d-flex justify-content-between align-items-center mb-3">
                <h5 class="card__title">My Cashout Requests</h5>
                <div>
                    <?php if ($isAdmin): ?>
                        <a href="<?= $GLOBALS['base_url'] ?>/admin-cashout-requests" class="btn btn-info me-2">
                            <i class="fa-light fa-users me-2"></i> View All Requests
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card__body">
                <?php if (!empty($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= $_SESSION['success']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <?php unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= $_SESSION['error']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <?php unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Purpose/Paid To</th>
                                <th>Amount</th>
                                <th>Approved Amount</th>
                                <th>Due Amount</th>
                                <th>Status</th>
                                <th>Approved By</th>
                                <th>Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($requests)): ?>
                                <?php foreach ($requests as $request): ?>
                                    <?php

                                    // Use the display_status from synced data
                                    $displayStatus = $request['display_status'] ?? $request['status'] ?? 'pending';

                                    // Get approver name from approval system
                                    $approvedBy = $request['approved_by_name'] ?? '';

                                    // Check if request has approved amount
                                    $hasApprovedAmount = !empty($request['approved_amount']);
                                    $approvedAmount = $hasApprovedAmount ? $request['approved_amount'] : null;

                                    // Check approval status for styling
                                    $isApproved = in_array($request['approval_status'] ?? '', ['approved', 'partial']);
                                    $isPending = ($request['approval_status'] ?? '') === 'pending';
                                    ?>

                                    <tr>
                                        <td>#<?= $request['id'] ?></td>
                                        <td><?= htmlspecialchars($request['purpose_paid_to']) ?></td>
                                        <td><strong>TK.<?= number_format($request['amount'], 2) ?></strong></td>
                                        <td>
                                            <?php
                                            // Check BOTH approved_amount fields
                                            $hasApprovedAmount = !empty($request['approved_amount']) || !empty($request['approval_approved_amount']);
                                            $approvedAmount = $hasApprovedAmount ?
                                                ($request['approved_amount'] ?? $request['approval_approved_amount'] ?? 0) : 0;
                                            ?>

                                            <?php if ($hasApprovedAmount && $approvedAmount > 0): ?>
                                                <div class="d-flex flex-column">
                                                    <strong class="text-success">TK.<?= number_format($approvedAmount, 2) ?></strong>
                                                    <?php if ($request['approval_status'] === 'partial'): ?>
                                                        <small class="text-info">(Partial Approval)</small>
                                                    <?php endif; ?>
                                                    <?php if (!empty($request['approval_note'])): ?>
                                                        <small class="text-muted" title="<?= htmlspecialchars($request['approval_note']) ?>">
                                                            <i class="fa-light fa-note"></i>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <?php if ($isPending): ?>
                                                    <span class="text-warning">Pending Approval</span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($request['due_amount'] > 0): ?>
                                                <span class="text-danger">TK.<?= number_format($request['due_amount'], 2) ?></span>
                                            <?php else: ?>
                                                <span class="text-success">TK.0.00</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            // Fix: Ensure we always have a status value
                                            $displayStatus = $request['display_status'] ?? $request['status'] ?? 'pending';

                                            if ($displayStatus !== 'rejected' && $displayStatus !== 'pending' && isset($request['due_amount']) && $request['due_amount'] <= 0) {
                                                $displayStatus = 'fully_paid';
                                            }

                                            // If displayStatus is empty or null, set a default
                                            if (empty($displayStatus)) {
                                                // Determine based on other fields
                                                if ($request['due_amount'] <= 0) {
                                                    $displayStatus = 'fully_paid';
                                                } elseif (($request['approval_status'] ?? '') === 'approved') {
                                                    $displayStatus = 'approved';
                                                } elseif (($request['approval_status'] ?? '') === 'partial') {
                                                    $displayStatus = 'partial';
                                                } else {
                                                    $displayStatus = 'pending';
                                                }
                                            }

                                            // Status styling logic (keep your existing code but ensure $displayStatus has value)
                                            $statusClass = 'status-pending';
                                            $statusText = $displayStatus;

                                            // Adjust status display based on approval system
                                            if ($isApproved && $displayStatus === 'pending') {
                                                $statusClass = 'status-approved';
                                                $statusText = 'approved';
                                            } elseif ($request['approval_status'] === 'partial') {
                                                $statusClass = 'status-partial';
                                                $statusText = 'partial';
                                            } else {
                                                switch (strtolower($displayStatus)) { // Add strtolower for consistency
                                                    case 'approved':
                                                        $statusClass = 'status-approved';
                                                        break;
                                                    case 'qc_approved':
                                                        $statusClass = 'status-qc_approved';
                                                        break;
                                                    case 'rejected':
                                                    case 'reject':
                                                        $statusClass = 'status-rejected';
                                                        $statusText = 'rejected';
                                                        break;
                                                    case 'partially_paid':
                                                    case 'partial':
                                                    case 'partially paid':
                                                        $statusClass = 'status-partial';
                                                        $statusText = 'partial';
                                                        break;
                                                    case 'fully_paid':
                                                    case 'paid':
                                                    case 'fully paid':
                                                        $statusClass = 'status-paid';
                                                        $statusText = 'fully paid';
                                                        break;
                                                    case 'pending':
                                                    default:
                                                        $statusClass = 'status-pending';
                                                        $statusText = 'pending';
                                                        break;
                                                }
                                            }
                                            ?>
                                            <span class="status-badge <?= $statusClass ?>" title="<?= $request['approval_status'] ? 'Approval: ' . $request['approval_status'] : '' ?>">
                                                <?= ucfirst(str_replace('_', ' ', $statusText)) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($approvedBy): ?>
                                                <div class="d-flex flex-column">
                                                    <small><?= htmlspecialchars($approvedBy) ?></small>
                                                    <?php if (!empty($request['approval_approved_at'])): ?>
                                                        <small class="text-muted"><?= date('d M, Y', strtotime($request['approval_approved_at'])) ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('d M, Y', strtotime($request['created_at'])) ?></td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <a href="<?= $GLOBALS['base_url'] ?>/view-cashout-request?id=<?= $request['id'] ?>"
                                                    class="btn btn-sm btn-info" title="View Details">
                                                    <i class="fa-light fa-eye"></i>
                                                </a>
                                                <?php
                                                // Only show delete if pending and not approved from approval system
                                                $canDelete = $displayStatus == 'pending' && !$isApproved;
                                                ?>
                                                <?php if ($canDelete): ?>
                                                    <a href="<?= $GLOBALS['base_url'] ?>/delete-cashout-request?id=<?= $request['id'] ?>"
                                                        class="btn btn-sm btn-danger" title="Delete"
                                                        onclick="return confirm('Are you sure you want to delete this request?')">
                                                        <i class="fa-light fa-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fa-light fa-inbox fa-2x mb-3"></i><br>
                                            No cashout requests found
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
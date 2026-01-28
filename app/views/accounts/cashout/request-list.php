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
    7 => 'Clients Experiment'
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
    .status-pending { background: #fff3cd; color: #856404; }
    .status-approved { background: #117a299b; color: #155724; }
    .status-rejected { background: #f8d7da; color: #721c24; }
    .status-partial { background: #cce5ff; color: #004085; }
    .status-paid { background: #d1ecf1; color: #0c5460; }
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
                    <h4 class="card__title fs-6">৳<?= number_format($statistics['total_amount'] ?? 0, 2) ?></h4>
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
                    <h4 class="card__title fs-6">৳<?= number_format($statistics['total_due'] ?? 0, 2) ?></h4>
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
                                    // Get approver names
                                    $approvedBy = '';
                                    if (!empty($request['hr_approver_name'])) {
                                        $approvedBy = 'HR: ' . $request['hr_approver_name'];
                                    }
                                    if (!empty($request['admin_approver_name'])) {
                                        $approvedBy = 'Admin: ' . $request['admin_approver_name'];
                                    }
                                    if (!empty($request['sadmin_approver_name'])) {
                                        $approvedBy = 'Super Admin: ' . $request['sadmin_approver_name'];
                                    }
                                    ?>
                                    
                                    <tr>
                                        <td>#<?= $request['id'] ?></td>
                                        <td><?= htmlspecialchars($request['purpose_paid_to']) ?></td>
                                        <td><strong>৳<?= number_format($request['amount'], 2) ?></strong></td>
                                        <td>
                                            <?php if ($request['due_amount'] > 0): ?>
                                                <span class="text-danger">৳<?= number_format($request['due_amount'], 2) ?></span>
                                            <?php else: ?>
                                                <span class="text-success">৳0.00</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = 'status-pending';
                                            switch($request['status']) {
                                                case 'approved_by_HR':
                                                case 'approved_by_Admin':
                                                case 'approved_by_Super_Admin':
                                                    $statusClass = 'status-approved';
                                                    break;
                                                case 'rejected':
                                                    $statusClass = 'status-rejected';
                                                    break;
                                                case 'partially_paid':
                                                    $statusClass = 'status-partial';
                                                    break;
                                                case 'fully_paid':
                                                    $statusClass = 'status-paid';
                                                    break;
                                            }
                                            ?>
                                            <span class="status-badge <?= $statusClass ?>">
                                                <?= ucfirst(str_replace('_', ' ', $request['status'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($approvedBy): ?>
                                                <small><?= htmlspecialchars($approvedBy) ?></small>
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
                                                <?php if (($request['status'] ?? '') == 'pending'): ?>
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
                                    <td colspan="8" class="text-center py-4">
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


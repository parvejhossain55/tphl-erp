<?php
$page_title = "Cashout Requests Management";
$page = 'Accounts';
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
$userRoleName = $roleNames[$userRole ?? 1] ?? 'Admin';

// Get current user ID from session
$currentUserId = $_SESSION['user']['id'] ?? null;
?>
<style>
    .status-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .status-pending {
        background: #b4f4b5;
        color: #085f06;
    }

    .status-approved {
        background: #d4edda;
        color: #155724;
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

    .filter-card {
        background: #f8f9fa;
        border-left: 4px solid #6c5ffc;
    }
</style>

<div class="row">
    <!-- Admin Header -->
    <div class="col-xxl-12 mb-4">
        <div class="card__wrapper">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="card__title mb-1">Cashout Requests Management</h5>
                    <p class="text-muted mb-0">Role: <strong><?= $userRoleName ?></strong> | Viewing All Requests</p>
                </div>
                <div class="text-end">
                    <a href="<?= $GLOBALS['base_url'] ?>/export-cashout-requests" class="btn btn-primary me-2">
                        <i class="fa-light fa-download me-2"></i> Export CSV
                    </a>
                    <a href="<?= $GLOBALS['base_url'] ?>/cashout-requests" class="btn btn-info">
                        <i class="fa-light fa-user me-2"></i> View My Requests
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-list"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Requests</h6>
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
                    <h6 class="card__sub-title mb-10">Pending</h6>
                    <h4 class="card__title fs-6"><?= $statistics['pending_count'] ?? 0 ?></h4>
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

    <!-- Filter Section -->
    <div class="col-xxl-12 mb-4">
        <div class="card__wrapper filter-card">
            <div class="card__header">
                <h6 class="card__title"><i class="fa-light fa-filter me-2"></i>Filter Requests</h6>
            </div>
            <div class="card__body">
                <form method="GET" action="<?= $GLOBALS['base_url'] ?>/admin-cashout-requests" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="pending" <?= ($filters['status'] ?? '') == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="approved_by_HR" <?= ($filters['status'] ?? '') == 'approved_by_HR' ? 'selected' : '' ?>>Approved by HR</option>
                            <option value="approved_by_Admin" <?= ($filters['status'] ?? '') == 'approved_by_Admin' ? 'selected' : '' ?>>Approved by Admin</option>
                            <option value="approved_by_Super_Admin" <?= ($filters['status'] ?? '') == 'approved_by_Super_Admin' ? 'selected' : '' ?>>Approved by Super Admin</option>
                            <option value="rejected" <?= ($filters['status'] ?? '') == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                            <option value="partially_paid" <?= ($filters['status'] ?? '') == 'partially_paid' ? 'selected' : '' ?>>Partially Paid</option>
                            <option value="fully_paid" <?= ($filters['status'] ?? '') == 'fully_paid' ? 'selected' : '' ?>>Fully Paid</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">From Date</label>
                        <input type="date" name="start_date" class="form-control"
                            value="<?= $filters['start_date'] ?? '' ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To Date</label>
                        <input type="date" name="end_date" class="form-control"
                            value="<?= $filters['end_date'] ?? '' ?>">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fa-light fa-filter me-2"></i> Apply Filter
                        </button>
                        <a href="<?= $GLOBALS['base_url'] ?>/admin-cashout-requests" class="btn btn-secondary">
                            <i class="fa-light fa-times me-2"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Requests Table -->
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="card__header d-flex justify-content-between align-items-center mb-3">
                <h5 class="card__title">All Cashout Requests</h5>
                <div>
                    <span class="badge bg-light text-dark me-2">
                        <i class="fa-light fa-file-invoice me-1"></i> <?= count($requests) ?> Requests
                    </span>
                    <span class="badge bg-info">
                        <i class="fa-light fa-users me-1"></i> Admin View
                    </span>
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
                                <th>Requester</th>
                                <th>Purpose/Paid To</th>
                                <th>Amount</th>
                                <th>Due Amount</th>
                                <th>Status</th>
                                <th>Approved By</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($requests)): ?>
                                <?php foreach ($requests as $request): ?>
                                    <?php
                                    // Get approver name
                                    $approvedBy = '';
                                    if (!empty($request['approved_by_name'])) {
                                        $approvedBy = '<div>
                                      <small>' . htmlspecialchars($request['approved_by_name']) . '</small><br>
                                      <small class="text-muted">
                                      ' . (!empty($request['approved_at']) ? date('d M, Y h:i A', strtotime($request['approved_at'])) : '') . '
                                      </small>
                                  </div>';
                                    }
                                    
                                    // Determine status for display
                                    $displayStatus = $request['display_status'] ?? $request['status'] ?? 'pending';
                                    ?>

                                    <tr>
                                        <td>#<?= $request['id'] ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <strong><?= htmlspecialchars($request['requester_name']) ?></strong>
                                                    <?php if ($currentUserId && $request['requested_by'] == $currentUserId): ?>
                                                        <span class="badge bg-info ms-1">You</span>
                                                    <?php endif; ?>
                                                    <br>
                                                    <small class="text-muted">ID: <?= $request['requested_by'] ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 200px;"
                                                title="<?= htmlspecialchars($request['purpose_paid_to']) ?>">
                                                <?= htmlspecialchars($request['purpose_paid_to']) ?>
                                            </div>
                                        </td>
                                        <td><strong class="text-primary">৳<?= number_format($request['amount'], 2) ?></strong></td>
                                        <td>
                                            <?php if ($request['due_amount'] > 0): ?>
                                                <strong class="text-danger">৳<?= number_format($request['due_amount'], 2) ?></strong>
                                            <?php else: ?>
                                                <span class="text-success">৳0.00</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = 'status-pending';
                                            switch ($displayStatus) {
                                                case 'approved':
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
                                                <?= ucfirst(str_replace('_', ' ', $displayStatus)) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($approvedBy): ?>
                                                <small><?= $approvedBy ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">Not approved yet</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= date('d M, Y', strtotime($request['created_at'])) ?><br>
                                            <small class="text-muted"><?= date('h:i A', strtotime($request['created_at'])) ?></small>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <a href="<?= $GLOBALS['base_url'] ?>/view-cashout-request?id=<?= $request['id'] ?>"
                                                    class="btn btn-sm btn-info" title="View Details">
                                                    <i class="fa-light fa-eye"></i>
                                                </a>

                                                <!-- Admin actions button (uncomment if needed) -->
                                                <!--
                                                <a href="<?= $GLOBALS['base_url'] ?>/admin-view-cashout-request?id=<?= $request['id'] ?>"
                                                    class="btn btn-sm btn-warning" title="Admin Actions">
                                                    <i class="fa-light fa-gavel"></i>
                                                </a>
                                                -->

                                                <!-- DELETE BUTTON FIXED -->
                                                <?php 
                                                // Determine if delete button should be shown
                                                $canDelete = false;
                                                
                                                // Super Admin can delete any request
                                                if ($userRole == 3) {
                                                    $canDelete = true;
                                                }
                                                // Regular users can delete their own pending requests
                                                elseif ($currentUserId && $request['requested_by'] == $currentUserId && $displayStatus == 'pending') {
                                                    $canDelete = true;
                                                }
                                                // Admin users can delete pending requests (optional)
                                                elseif (in_array($userRole, [1, 3, 4, 6]) && $displayStatus == 'pending') {
                                                    $canDelete = true;
                                                }
                                                ?>
                                                
                                                <?php if ($canDelete): ?>
                                                    <a href="<?= $GLOBALS['base_url'] ?>/delete-cashout-request?id=<?= $request['id'] ?>"
                                                        class="btn btn-sm btn-danger" title="Delete"
                                                        onclick="return confirm('Are you sure you want to delete this request? This action cannot be undone.')">
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
                                            <?php if (!empty($filters['status']) || !empty($filters['start_date'])): ?>
                                                <br><small class="mt-2">Try changing your filters</small>
                                            <?php endif; ?>
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

<!-- Debug info (remove in production) -->
<?php if (isset($debug) && $debug): ?>
<div style="position: fixed; bottom: 10px; right: 10px; background: #f8f9fa; padding: 10px; border: 1px solid #ccc; font-size: 12px;">
    <strong>Debug Info:</strong><br>
    User Role: <?= $userRole ?><br>
    Current User ID: <?= $currentUserId ?><br>
    Session: <?= print_r($_SESSION['user'] ?? [], true) ?>
</div>
<?php endif; ?>
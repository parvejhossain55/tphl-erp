<?php
$roleId = (int)($_SESSION['user']['role_id'] ?? 0);
$userId = (int)($_SESSION['user']['id'] ?? 0);

$canCreate = true; // Enable New Request button for all logged in users

// Approval Pipeline Role Permissions (Admin=1, super Admin=3)
$isAdminOrMgmt = in_array($roleId, [1, 3, 33]); 
$isOperations   = $isAdminOrMgmt || in_array($roleId, [20, 32]); // Operations Step: Role 20, 32
$isPurchaseHead = $isAdminOrMgmt || in_array($roleId, [17, 31]); // Purchase Head Step: Role 17, 31
$isQcAudit      = $isAdminOrMgmt || in_array($roleId, [24, 25]); // QC & Internal Audit Step
$isManagement   = $isAdminOrMgmt;                               // Management Step
$isAccounts     = $isAdminOrMgmt || in_array($roleId, [6, 30]);  // Accounts Payment

// Global approvers / viewers include Management, Operations, Purchase, QC/Audit, Accounts roles
$isGlobalViewer = in_array($roleId, [1, 3, 33, 6, 17, 20, 24, 25, 30, 31, 32]);
$isApprover     = $isGlobalViewer;
$activeTab      = $_GET['tab'] ?? ($isApprover ? 'all_requests' : 'my_requests');

$overallBadges = [
    'pending' => 'warning', 'in_review' => 'info', 'approved' => 'success',
    'rejected' => 'danger', 'purchased' => 'primary'
];
$stepBadges = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <h1 class="page-title fw-semibold fs-18 mb-0">Office Procurement Requests</h1>
    </div>

    <!-- Alert Messages -->
    <div class="row">
        <div class="col-xl-12">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Service Manual Note & Approval Flow -->
    <div class="alert alert-warning-transparent border fs-12 mb-3">
        <i class="fa-solid fa-triangle-exclamation me-1"></i>
        <strong>Service Manual:</strong> No construction materials requisition is allowed here for this system.
    </div>
    <div class="alert alert-info-transparent border fs-12 mb-4">
        <i class="fa-solid fa-circle-info me-1"></i>
        <strong>Approval Flow:</strong> 
        Requisition → Department Head / Reporting Boss (if applicable) → QC & Internal Audit → Management → Accounts (Payment)
    </div>

    <!-- Main Card -->
    <div class="card custom-card">
        <div class="card-header justify-content-between align-items-center">
            <div class="card-title">Purchase Requests</div>
            <div class="d-flex gap-2">
                <?php if (!empty($canCreate)): ?>
                    <button type="button" class="btn btn-primary btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#newRequestModal">
                        <i class="fa-solid fa-plus"></i> New Request
                    </button>
                <?php endif; ?>
                <?php if (!empty($isOperations)): ?>
                    <button type="button" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#manageCategoryModal">
                        <i class="fa-solid fa-gear"></i> Manage Categories
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($isApprover)): ?>
        <div class="card-body border-bottom pb-0 pt-3 px-3">
            <ul class="nav nav-tabs mb-0 border-bottom-0">
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab === 'pending_approval' ? 'active' : '' ?>" href="?tab=pending_approval<?= !empty($_GET['department_id']) ? '&department_id='.$_GET['department_id'] : '' ?><?= !empty($_GET['workplace']) ? '&workplace='.urlencode($_GET['workplace']) : '' ?><?= !empty($_GET['status']) ? '&status='.$_GET['status'] : '' ?>">
                        <i class="fa-solid fa-clock-rotate-left me-1"></i>Pending My Approval
                        <?php if (!empty($pendingCount) && $pendingCount > 0): ?>
                            <span class="badge bg-warning text-dark rounded-pill ms-1"><?= $pendingCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab === 'my_requests' ? 'active' : '' ?>" href="?tab=my_requests<?= !empty($_GET['department_id']) ? '&department_id='.$_GET['department_id'] : '' ?><?= !empty($_GET['workplace']) ? '&workplace='.urlencode($_GET['workplace']) : '' ?><?= !empty($_GET['status']) ? '&status='.$_GET['status'] : '' ?>"><i class="fa-solid fa-user me-1"></i>My Requests</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab === 'all_requests' ? 'active' : '' ?>" href="?tab=all_requests<?= !empty($_GET['department_id']) ? '&department_id='.$_GET['department_id'] : '' ?><?= !empty($_GET['workplace']) ? '&workplace='.urlencode($_GET['workplace']) : '' ?><?= !empty($_GET['status']) ? '&status='.$_GET['status'] : '' ?>"><i class="fa-solid fa-list-check me-1"></i>All / Processed Requests</a>
                </li>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="card-body border-bottom pb-3">
            <form method="GET" class="row g-2 align-items-end">
                <?php if (!empty($isApprover)): ?>
                    <input type="hidden" name="tab" value="<?= htmlspecialchars($activeTab) ?>">
                <?php endif; ?>
                <div class="col-md-3">
                    <label class="form-label fs-11 fw-semibold mb-1">Department</label>
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="">All Departments</option>
                        <?php foreach ($departments ?? [] as $dept): ?>
                            <option value="<?= $dept['id'] ?>" <?= ($_GET['department_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fs-11 fw-semibold mb-1">Branch / Location</label>
                    <select name="office_branch_id" class="form-select form-select-sm">
                        <option value="">All Branches</option>
                        <?php foreach ($officeBranches ?? [] as $b): ?>
                            <option value="<?= $b['id'] ?>" <?= ((string)($_GET['office_branch_id'] ?? '') === (string)$b['id'] || ($_GET['workplace'] ?? '') === $b['name']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($b['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fs-11 fw-semibold mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="in_review" <?= ($_GET['status'] ?? '') === 'in_review' ? 'selected' : '' ?>>In Review</option>
                        <option value="approved" <?= ($_GET['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="rejected" <?= ($_GET['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        <option value="purchased" <?= ($_GET['status'] ?? '') === 'purchased' ? 'selected' : '' ?>>Purchased</option>
                    </select>
                </div>
                <div class="col-md-4 mt-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                    <a href="<?= $GLOBALS['base_url'] ?>/asset-extra-purchases" class="btn btn-light btn-sm flex-fill text-center"><i class="fa-solid fa-arrows-rotate"></i> Reset</a>
                </div>
            </form>
        </div>

        <!-- Requests Table -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table text-nowrap table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Project</th>
                            <th>Item</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Total (BDT)</th>
                            <th>Requested By</th>
                            <th class="text-center">Dept Head</th>
                            <th class="text-center">Operations</th>
                            <th class="text-center">Purchase Head</th>
                            <th class="text-center">QC & Audit</th>
                            <th class="text-center">Management</th>
                            <th class="text-center">Payment</th>
                            <th>Overall</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($requests)): ?>
                            <tr>
                                <td colspan="15" class="text-center py-4 text-muted">No purchase requests found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($requests as $r): ?>
                                <tr data-request-id="<?= $r['id'] ?>" class="<?= (!empty($autoOpenRequest) && $autoOpenRequest['id'] == $r['id']) ? 'table-primary' : '' ?>">
                                    <td class="fw-semibold">#<?= $r['id'] ?></td>
                                    <td class="fs-12"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                                    <td class="fs-12">
                                        <?php if (!empty($r['project_name'])): ?>
                                            <span class="fw-semibold"><?= htmlspecialchars($r['project_name']) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-secondary border fs-11"><i class="fa-solid fa-building me-1"></i><?= htmlspecialchars(!empty($r['workplace_name']) ? $r['workplace_name'] : 'Head Office') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="fs-12 fw-semibold"><?= htmlspecialchars($r['item_name']) ?></span>
                                        <?php if (!empty($r['category_name'])): ?>
                                            <br><small class="text-muted fs-10"><?= htmlspecialchars($r['category_name']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><?= $r['quantity'] ?></td>
                                    <td class="text-end fw-semibold">
                                        <?= number_format($r['total_price'], 2) ?>
                                        <?php if (!empty($r['paid_amount']) && $r['paid_amount'] > 0): ?>
                                            <br><small class="text-success fs-10">Paid: <?= number_format($r['paid_amount'], 2) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fs-12">
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars(!empty($r['requested_by_name']) ? $r['requested_by_name'] : 'System Admin') ?></div>
                                        <?php if (!empty($r['workplace_name'])): ?>
                                            <small class="text-muted fs-11 d-block"><i class="fa-solid fa-location-dot me-1 text-primary"></i><?= htmlspecialchars($r['workplace_name']) ?></small>
                                        <?php elseif (!empty($r['department_name'])): ?>
                                            <small class="text-muted fs-11 d-block"><i class="fa-solid fa-building me-1 text-info"></i><?= htmlspecialchars($r['department_name']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <!-- Step: Dept Head -->
                                    <td class="text-center">
                                        <?php
                                        $dhStatus = $r['dept_head_status'] ?? 'not_required';
                                        if ($dhStatus === 'not_required'):
                                        ?>
                                            <span class="badge fs-10 px-2 py-1" style="background:#e9ecef;color:#6c757d;font-weight:600;">
                                                N/A
                                            </span>
                                        <?php else:
                                            $dhColor = $dhStatus === 'approved' ? '#198754' : ($dhStatus === 'rejected' ? '#dc3545' : '#e6a817');
                                            $dhBg = $dhStatus === 'approved' ? '#d1e7dd' : ($dhStatus === 'rejected' ? '#f8d7da' : '#fff3cd');
                                        ?>
                                            <span class="badge fs-10 px-2 py-1" style="background:<?= $dhBg ?>;color:<?= $dhColor ?>;font-weight:600;">
                                                <?= ucfirst($dhStatus) ?>
                                            </span>
                                            <?php if ($dhStatus !== 'pending' && !empty($r['dept_head_by_name'])): ?>
                                                <small class="d-block text-muted fs-10 mt-1" style="line-height:1.2;" title="<?= !empty($r['dept_head_at']) ? date('d M Y, h:i A', strtotime($r['dept_head_at'])) : '' ?>">
                                                    <i class="fa-solid fa-user-check text-success me-1 fs-9"></i><?= htmlspecialchars($r['dept_head_by_name']) ?>
                                                    <?php if (!empty($r['dept_head_at'])): ?>
                                                        <br><span class="text-secondary fs-9"><?= date('d M, h:i A', strtotime($r['dept_head_at'])) ?></span>
                                                    <?php endif; ?>
                                                </small>
                                            <?php elseif ($dhStatus === 'pending' && !empty($r['reporting_boss_name'])): ?>
                                                <small class="d-block text-muted fs-9 mt-1" style="line-height:1.2;">
                                                    <i class="fa-solid fa-user-clock me-1 text-warning"></i><?= htmlspecialchars($r['reporting_boss_name']) ?>
                                                </small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <!-- Step: Operations -->
                                    <td class="text-center">
                                        <?php
                                        $opColor = $r['operations_status'] === 'approved' ? '#198754' : ($r['operations_status'] === 'rejected' ? '#dc3545' : '#e6a817');
                                        $opBg = $r['operations_status'] === 'approved' ? '#d1e7dd' : ($r['operations_status'] === 'rejected' ? '#f8d7da' : '#fff3cd');
                                        ?>
                                        <span class="badge fs-10 px-2 py-1" style="background:<?= $opBg ?>;color:<?= $opColor ?>;font-weight:600;">
                                            <?= ucfirst($r['operations_status']) ?>
                                        </span>
                                        <?php if ($r['operations_status'] !== 'pending' && !empty($r['operations_by_name'])): ?>
                                            <small class="d-block text-muted fs-10 mt-1" style="line-height:1.2;" title="<?= !empty($r['operations_at']) ? date('d M Y, h:i A', strtotime($r['operations_at'])) : '' ?>">
                                                <i class="fa-solid fa-user-check text-success me-1 fs-9"></i><?= htmlspecialchars($r['operations_by_name']) ?>
                                                <?php if (!empty($r['operations_at'])): ?>
                                                    <br><span class="text-secondary fs-9"><?= date('d M, h:i A', strtotime($r['operations_at'])) ?></span>
                                                <?php endif; ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <!-- Step: Purchase Head -->
                                    <td class="text-center">
                                        <?php
                                        $phColor = $r['purchase_head_status'] === 'approved' ? '#198754' : ($r['purchase_head_status'] === 'rejected' ? '#dc3545' : '#e6a817');
                                        $phBg = $r['purchase_head_status'] === 'approved' ? '#d1e7dd' : ($r['purchase_head_status'] === 'rejected' ? '#f8d7da' : '#fff3cd');
                                        ?>
                                        <span class="badge fs-10 px-2 py-1" style="background:<?= $phBg ?>;color:<?= $phColor ?>;font-weight:600;">
                                            <?= ucfirst($r['purchase_head_status']) ?>
                                        </span>
                                        <?php if ($r['purchase_head_status'] !== 'pending' && !empty($r['purchase_head_by_name'])): ?>
                                            <small class="d-block text-muted fs-10 mt-1" style="line-height:1.2;" title="<?= !empty($r['purchase_head_at']) ? date('d M Y, h:i A', strtotime($r['purchase_head_at'])) : '' ?>">
                                                <i class="fa-solid fa-user-check text-success me-1 fs-9"></i><?= htmlspecialchars($r['purchase_head_by_name']) ?>
                                                <?php if (!empty($r['purchase_head_at'])): ?>
                                                    <br><span class="text-secondary fs-9"><?= date('d M, h:i A', strtotime($r['purchase_head_at'])) ?></span>
                                                <?php endif; ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <!-- Step: QC & Audit -->
                                    <td class="text-center">
                                        <?php
                                        $qcColor = $r['qc_audit_status'] === 'approved' ? '#198754' : ($r['qc_audit_status'] === 'rejected' ? '#dc3545' : '#e6a817');
                                        $qcBg = $r['qc_audit_status'] === 'approved' ? '#d1e7dd' : ($r['qc_audit_status'] === 'rejected' ? '#f8d7da' : '#fff3cd');
                                        ?>
                                        <span class="badge fs-10 px-2 py-1" style="background:<?= $qcBg ?>;color:<?= $qcColor ?>;font-weight:600;">
                                            <?= ucfirst($r['qc_audit_status']) ?>
                                        </span>
                                        <?php if ($r['qc_audit_status'] !== 'pending' && !empty($r['qc_audit_by_name'])): ?>
                                            <small class="d-block text-muted fs-10 mt-1" style="line-height:1.2;" title="<?= !empty($r['qc_audit_at']) ? date('d M Y, h:i A', strtotime($r['qc_audit_at'])) : '' ?>">
                                                <i class="fa-solid fa-user-check text-success me-1 fs-9"></i><?= htmlspecialchars($r['qc_audit_by_name']) ?>
                                                <?php if (!empty($r['qc_audit_at'])): ?>
                                                    <br><span class="text-secondary fs-9"><?= date('d M, h:i A', strtotime($r['qc_audit_at'])) ?></span>
                                                <?php endif; ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <!-- Step: Management -->
                                    <td class="text-center">
                                        <?php
                                        $mgColor = $r['mgmt_status'] === 'approved' ? '#198754' : ($r['mgmt_status'] === 'rejected' ? '#dc3545' : '#e6a817');
                                        $mgBg = $r['mgmt_status'] === 'approved' ? '#d1e7dd' : ($r['mgmt_status'] === 'rejected' ? '#f8d7da' : '#fff3cd');
                                        ?>
                                        <span class="badge fs-10 px-2 py-1" style="background:<?= $mgBg ?>;color:<?= $mgColor ?>;font-weight:600;">
                                            <?= ucfirst($r['mgmt_status']) ?>
                                        </span>
                                        <?php if ($r['mgmt_status'] !== 'pending' && !empty($r['mgmt_by_name'])): ?>
                                            <small class="d-block text-muted fs-10 mt-1" style="line-height:1.2;" title="<?= !empty($r['mgmt_at']) ? date('d M Y, h:i A', strtotime($r['mgmt_at'])) : '' ?>">
                                                <i class="fa-solid fa-user-check text-success me-1 fs-9"></i><?= htmlspecialchars($r['mgmt_by_name']) ?>
                                                <?php if (!empty($r['mgmt_at'])): ?>
                                                    <br><span class="text-secondary fs-9"><?= date('d M, h:i A', strtotime($r['mgmt_at'])) ?></span>
                                                <?php endif; ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <!-- Step: Payment -->
                                    <td class="text-center">
                                        <?php
                                        $pyColor = $r['payment_status'] === 'paid' ? '#198754' : '#e6a817';
                                        $pyBg = $r['payment_status'] === 'paid' ? '#d1e7dd' : '#fff3cd';
                                        ?>
                                        <span class="badge fs-10 px-2 py-1" style="background:<?= $pyBg ?>;color:<?= $pyColor ?>;font-weight:600;">
                                            <?= ucfirst($r['payment_status']) ?>
                                        </span>
                                        <?php if ($r['payment_status'] === 'paid' && !empty($r['paid_by_name'])): ?>
                                            <small class="d-block text-muted fs-10 mt-1" style="line-height:1.2;" title="<?= !empty($r['paid_at']) ? date('d M Y, h:i A', strtotime($r['paid_at'])) : '' ?>">
                                                <i class="fa-solid fa-user-check text-success me-1 fs-9"></i><?= htmlspecialchars($r['paid_by_name']) ?>
                                                <?php if (!empty($r['paid_at'])): ?>
                                                    <br><span class="text-secondary fs-9"><?= date('d M, h:i A', strtotime($r['paid_at'])) ?></span>
                                                <?php endif; ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $ovColors = [
                                            'pending' => ['#e6a817','#fff3cd'], 'in_review' => ['#0d6efd','#cfe2ff'],
                                            'approved' => ['#198754','#d1e7dd'], 'rejected' => ['#dc3545','#f8d7da'],
                                            'paid' => ['#0d6efd','#cfe2ff'],
                                            'purchased' => ['#6f42c1','#e2d9f3']
                                        ];
                                        $oc = $ovColors[$r['overall_status']] ?? ['#6c757d','#e9ecef'];
                                        ?>
                                        <span class="badge fs-10 px-2 py-1" style="background:<?= $oc[1] ?>;color:<?= $oc[0] ?>;font-weight:700;">
                                            <?= ucfirst(str_replace('_', ' ', $r['overall_status'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $isCreator = (int)$r['requested_by'] === $userId;
                                        $reportingBossId = (int)($r['reporting_user_id'] ?? $r['requester_reporting_boss_id'] ?? 0);
                                        $isReporterBoss = ($reportingBossId === $userId);

                                        // Strict self-approval prevention: Creator can NEVER approve/reject their own request
                                        $canApprove = false;
                                        $step = '';
                                        $reqTotal = (float)($r['total_price'] ?? 0);
                                        $isRole20Eligible = (in_array($roleId, [20, 32]) && $reqTotal <= 100000);
                                        $rowDhStatus = $r['dept_head_status'] ?? 'not_required';
                                        
                                        if (!$isCreator) {
                                            if (($isReporterBoss || $isAdminOrMgmt) && $rowDhStatus === 'pending') {
                                                $canApprove = true; $step = 'dept_head';
                                            } elseif ($isOperations && in_array($rowDhStatus, ['approved', 'not_required']) && $r['operations_status'] === 'pending') {
                                                $canApprove = true; $step = 'operations';
                                            } elseif ($isPurchaseHead && $r['operations_status'] === 'approved' && $r['purchase_head_status'] === 'pending') {
                                                $canApprove = true; $step = 'purchase_head';
                                            } elseif ($isQcAudit && $r['purchase_head_status'] === 'approved' && $r['qc_audit_status'] === 'pending') {
                                                $canApprove = true; $step = 'qc_audit';
                                            } elseif (($isManagement || $isRole20Eligible) && $r['qc_audit_status'] === 'approved' && $r['mgmt_status'] === 'pending') {
                                                $canApprove = true; $step = 'mgmt';
                                            }
                                        }
                                        
                                        $canPay = !empty($isAccounts) && $r['mgmt_status'] === 'approved' && $r['payment_status'] === 'unpaid';
                                        $canConfirmPurchase = !empty($isOperations) && $r['payment_status'] === 'paid' && $r['overall_status'] === 'paid';

                                        // Creator or Admin can delete BEFORE any approval step has passed
                                        $isInitialPending = in_array($rowDhStatus, ['pending', 'not_required']) && ($r['operations_status'] === 'pending' && $r['purchase_head_status'] === 'pending' && $r['qc_audit_status'] === 'pending' && $r['mgmt_status'] === 'pending');
                                        $canDelete = ($isCreator || $isAdminOrMgmt) && $isInitialPending;
                                        $r['can_approve'] = $canApprove;
                                        $r['approval_step'] = $step;
                                        $r['can_pay'] = $canPay;
                                        $r['can_confirm_purchase'] = $canConfirmPurchase;
                                        ?>

                                        <!-- Always show View button -->
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-icon" title="View"
                                            data-id="<?= $r['id'] ?>"
                                            data-info='<?= htmlspecialchars(json_encode($r), ENT_QUOTES, "UTF-8") ?>'
                                            onclick="openDetailModal(this)">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>

                                        <?php if ($r['payment_status'] === 'paid' || in_array($r['overall_status'], ['paid', 'purchased'])): ?>
                                            <a href="<?= $GLOBALS['base_url'] ?>/expense-adjustments/create?parent_type=extra_purchase&cashout_request_id=<?= $r['id'] ?>&project_id=<?= $r['project_id'] ?? '' ?>" class="btn btn-sm btn-outline-warning btn-icon" title="Settlement / Adjustment">
                                                <i class="fa-solid fa-sliders"></i>
                                            </a>
                                            <a href="<?= $GLOBALS['base_url'] ?>/print-debit-voucher?module=project_extra_assets&id=<?= $r['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary btn-icon" title="Print Debit Voucher">
                                                <i class="fa-solid fa-file-invoice-dollar"></i>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($canApprove): ?>
                                            <button type="button" class="btn btn-sm btn-outline-success btn-icon" title="Approve"
                                                onclick="openApprovalModal(<?= $r['id'] ?>, 'approved', '<?= $step ?>')">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-icon" title="Reject"
                                                onclick="openApprovalModal(<?= $r['id'] ?>, 'rejected', '<?= $step ?>')">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        <?php elseif ($canPay): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary" title="Process Payment"
                                                onclick="openPaymentModal(<?= $r['id'] ?>, <?= (float)$r['total_price'] ?>)">
                                                <i class="fa-solid fa-money-bill me-1"></i> Pay
                                            </button>
                                        <?php elseif ($canConfirmPurchase): ?>
                                            <button type="button" class="btn btn-sm btn-outline-success" title="Mark as Purchased"
                                                data-info='<?= htmlspecialchars(json_encode($r), ENT_QUOTES, "UTF-8") ?>'
                                                onclick="openConfirmPurchaseModal(this)">
                                                <i class="fa-solid fa-box-check me-1"></i> Mark Purchased
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($canDelete): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-icon" title="Delete / Cancel Request"
                                                onclick="openDeleteRequestModal(<?= $r['id'] ?>)">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
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

<?php if (!empty($canCreate)): ?>
<!-- ========================================================================= -->
<!-- MODAL: NEW PURCHASE REQUEST -->
<!-- ========================================================================= -->
<div class="modal fade" id="newRequestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= $GLOBALS['base_url'] ?>/asset-extra-purchases/store" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Office Procurement Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row ">
                        <div class="col-md-12">
                            <label class="form-label">Department</label>
                            <select name="department_id" class="form-select">
                                <option value="">-- Select Department --</option>
                                <?php foreach ($departments ?? [] as $dept): ?>
                                    <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Branch / Location <span class="text-danger">*</span></label>
                            <select name="office_branch_id" id="new_request_branch_id" class="form-select" required onchange="var opt = this.options[this.selectedIndex]; document.getElementById('new_request_asset_location').value = (opt && opt.value) ? opt.text : '';">
                                <option value="">-- Select Office Branch --</option>
                                <?php foreach ($officeBranches ?? [] as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="asset_location" id="new_request_asset_location" value="">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Assign To (Person)</label>
                            <select name="assigned_to" id="new_request_assigned_to" class="form-select select2-assign">
                                <option value=""></option>
                                <?php foreach ($users ?? [] as $u): ?>
                                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1">Item Category <span class="text-danger">*</span></label>
                            <select name="item_category_id" class="form-select" required>
                                <option value="">-- Select Category --</option>
                                <?php foreach ($categories ?? [] as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1">Request Type <span class="text-danger">*</span></label>
                            <select name="request_type" class="form-select" required>
                                <option value="Purchase">Purchase</option>
                                <option value="Servicing">Servicing</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Item Name <span class="text-danger">*</span></label>
                            <input type="text" name="item_name" id="itemName" class="form-control" placeholder="e.g. Dell Laptop 15-inch" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" id="qty" class="form-control" value="1" min="1" required onchange="calcTotal()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Unit Price (BDT) </label>
                            <input type="number" name="unit_price" id="unitPrice" class="form-control" step="0.01" min="0" onchange="calcTotal()">
                        </div>
                        <div class="col-12">
                            <div class="bg-light p-2 rounded text-end">
                                <strong>Total: ৳ <span id="totalDisplay">0.00</span></strong>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Justification <span class="text-danger">*</span></label>
                            <textarea name="justification" class="form-control" rows="3" placeholder="Why is this purchase needed?" required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Attachment (Optional)</label>
                            <input type="file" name="attachment" class="form-control" accept="image/*,application/pdf">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Submit Request</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($canCreate)): ?>
<!-- ========================================================================= -->
<!-- MODAL: MANAGE CATEGORIES -->
<!-- ========================================================================= -->
<div class="modal fade" id="manageCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage Item Categories</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3  border-bottom">
                    <form action="<?= $GLOBALS['base_url'] ?>/asset-extra-purchases/category/store" method="POST" class="row gx-2 align-items-center">
                        <div class="col">
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="New Category Name" required>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fa-solid fa-plus"></i> Add
                            </button>
                        </div>
                    </form>
                </div>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Category Name</th>
                                <th class="text-end" width="100">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="2" class="text-center py-3 text-muted">No categories found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $cat): ?>
                                    <tr>
                                        <td class="fw-semibold align-middle"><?= htmlspecialchars($cat['name']) ?></td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-primary btn-icon" title="Edit"
                                                onclick="openEditCategoryModal(<?= $cat['id'] ?>, '<?= htmlspecialchars(addslashes($cat['name'])) ?>')">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger btn-icon" title="Delete"
                                                onclick="openDeleteCategoryModal(<?= $cat['id'] ?>)">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>



<!-- ========================================================================= -->
<!-- MODAL: EDIT CATEGORY -->
<!-- ========================================================================= -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <form action="<?= $GLOBALS['base_url'] ?>/asset-extra-purchases/category/update" method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="editCategoryId">
                    <label class="form-label">Category Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="editCategoryName" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#manageCategoryModal" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Update</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: DELETE CATEGORY -->
<!-- ========================================================================= -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <form action="<?= $GLOBALS['base_url'] ?>/asset-extra-purchases/category/delete" method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Delete Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <input type="hidden" name="id" id="deleteCategoryId">
                    <i class="fa-solid fa-triangle-exclamation text-danger fs-1 mb-3"></i>
                    <p class="mb-0">Are you sure you want to delete this category? This cannot be undone.</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#manageCategoryModal" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger">Yes, Delete</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- MODAL: DELETE PURCHASE REQUEST -->
<!-- ========================================================================= -->
<div class="modal fade" id="deleteRequestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <form id="deleteRequestForm" action="" method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Delete Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="fa-solid fa-triangle-exclamation text-danger fs-1 mb-3"></i>
                    <p class="mb-0 fs-13">Are you sure you want to delete this purchase request? This cannot be undone.</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger">Yes, Delete</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: APPROVAL ACTION -->
<!-- ========================================================================= -->
<div class="modal fade" id="approvalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="approvalForm" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="approvalModalTitle">Approve Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="approval_status" id="approvalStatus">
                    <input type="hidden" name="step" id="approvalStep">
                    <div class="mb-3">
                        <label class="form-label fs-12 fw-semibold">Remarks / Notes</label>
                        <textarea name="approval_note" class="form-control" rows="3" placeholder="Enter your reason or remarks..."></textarea>
                    </div>
                    <div class="mb-3 d-none" id="purchaseHeadDocGroup">
                        <label class="form-label fs-12 fw-semibold">Purchase Head Document</label>
                        <input type="file" name="purchase_head_document" class="form-control form-control-sm">
                        <div class="form-text fs-11">Optional document to attach (e.g. final quotation)</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm" id="approvalSubmitBtn">Submit</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: PAYMENT -->
<!-- ========================================================================= -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="paymentForm" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Process Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Paid Amount (BDT) <span class="text-danger">*</span></label>
                        <input type="number" name="paid_amount" class="form-control" step="0.01" min="0" required placeholder="Enter amount paid">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment Account (Chart of Accounts) <span class="text-danger">*</span></label>
                        <select name="payment_account_id" class="form-select" required>
                            <option value="">-- Select Cash or Bank Account --</option>
                            <?php foreach ($bankAccounts ?? [] as $bAcc): ?>
                                <option value="<?= $bAcc['id'] ?>">
                                    <?= htmlspecialchars($bAcc['account_code'] . ' - ' . $bAcc['account_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Cheque">Cheque</option>
                            <option value="bKash">bKash</option>
                            <option value="Nagad">Nagad</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Receipt (Optional)</label>
                        <input type="file" name="payment_attachment" class="form-control" accept="image/*,application/pdf">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm">Process Payment</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: DETAIL VIEW (Professional) -->
<!-- ========================================================================= -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="detailTitle">Purchase Request #1</h5>
                    <small class="text-muted" id="detailDate"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2" id="detailModalBody">
                <!-- Filled by JS -->
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light btn-sm px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
.approval-pipeline { display: flex; align-items: flex-start; justify-content: space-between; position: relative; padding: 0 10px; margin: 20px 0 10px; }
.approval-pipeline::before { content: ''; position: absolute; top: 20px; left: 40px; right: 40px; height: 3px; background: #e9ecef; z-index: 0; }
.approval-step { text-align: center; position: relative; z-index: 1; flex: 1; }
.step-circle { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px; font-weight: 700; font-size: 14px; border: 3px solid #e9ecef; background: #fff; transition: all 0.3s; }
.step-circle.pending { border-color: #ffc107; color: #ffc107; background: #fff8e1; }
.step-circle.approved { border-color: #198754; color: #fff; background: #198754; }
.step-circle.rejected { border-color: #dc3545; color: #fff; background: #dc3545; }
.step-circle.waiting { border-color: #0d6efd; color: #0d6efd; background: #e7f1ff; animation: pulse-ring 1.5s infinite; }
@keyframes pulse-ring { 0% { box-shadow: 0 0 0 0 rgba(13,110,253,0.4); } 70% { box-shadow: 0 0 0 8px rgba(13,110,253,0); } 100% { box-shadow: 0 0 0 0 rgba(13,110,253,0); } }
.step-label { font-size: 11px; font-weight: 600; color: #495057; }
.step-person { font-size: 10px; color: #6c757d; margin-top: 2px; }
.step-time { font-size: 9px; color: #adb5bd; margin-top: 1px; }
.detail-info-card { background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%); border: 1px solid #e9ecef; border-radius: 10px; padding: 16px; }
.detail-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #6c757d; margin-bottom: 2px; }
.detail-value { font-size: 13px; font-weight: 600; color: #212529; }
.next-step-banner { border-radius: 8px; padding: 10px 16px; display: flex; align-items: center; gap: 10px; }
.next-step-banner i { font-size: 18px; }
</style>

<script>

function calcTotal() {
    var qty = parseFloat(document.getElementById('qty').value) || 0;
    var price = parseFloat(document.getElementById('unitPrice').value) || 0;
    document.getElementById('totalDisplay').textContent = (qty * price).toLocaleString('en-US', {minimumFractionDigits: 2});
}

function openEditCategoryModal(id, name) {
    document.getElementById('editCategoryId').value = id;
    document.getElementById('editCategoryName').value = name;
    
    // Hide the manage modal and show the edit modal
    var manageModalEl = document.getElementById('manageCategoryModal');
    var manageModal = bootstrap.Modal.getInstance(manageModalEl);
    if(manageModal) manageModal.hide();
    
    new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
}

function openDeleteCategoryModal(id) {
    document.getElementById('deleteCategoryId').value = id;
    
    var manageModalEl = document.getElementById('manageCategoryModal');
    var manageModal = bootstrap.Modal.getInstance(manageModalEl);
    if(manageModal) manageModal.hide();
    
    new bootstrap.Modal(document.getElementById('deleteCategoryModal')).show();
}

function openDeleteRequestModal(id) {
    document.getElementById('deleteRequestForm').action = '<?= $GLOBALS['base_url'] ?>/asset-extra-purchases/delete/' + id;
    new bootstrap.Modal(document.getElementById('deleteRequestModal')).show();
}

function openApprovalModal(id, status, step) {
    var form = document.getElementById('approvalForm');
    form.action = '<?= $GLOBALS['base_url'] ?>/asset-extra-purchases/approve/' + id;
    document.getElementById('approvalStatus').value = status;
    document.getElementById('approvalStep').value = step;
    
    if (step === 'purchase_head' && status === 'approved') {
        document.getElementById('purchaseHeadDocGroup').classList.remove('d-none');
    } else {
        document.getElementById('purchaseHeadDocGroup').classList.add('d-none');
    }
    
    var title = status === 'approved' ? 'Approve Request #' + id : 'Reject Request #' + id;
    document.getElementById('approvalModalTitle').textContent = title;
    
    var btn = document.getElementById('approvalSubmitBtn');
    btn.className = 'btn btn-sm btn-' + (status === 'approved' ? 'success' : 'danger');
    btn.textContent = status === 'approved' ? 'Approve' : 'Reject';
    
    new bootstrap.Modal(document.getElementById('approvalModal')).show();
}

function openPaymentModal(id, amount) {
    var form = document.getElementById('paymentForm');
    form.action = '<?= $GLOBALS['base_url'] ?>/asset-extra-purchases/purchased/' + id;
    var paidInput = form.querySelector('input[name="paid_amount"]');
    if (paidInput && amount !== undefined) {
        paidInput.value = parseFloat(amount).toFixed(2);
        paidInput.max = parseFloat(amount).toFixed(2);
    }
    new bootstrap.Modal(document.getElementById('paymentModal')).show();
}

function getStepClass(status) {
    if (status === 'approved') return 'approved';
    if (status === 'rejected') return 'rejected';
    return 'pending';
}

function getStepIcon(status) {
    if (status === 'approved') return '<i class="fa-solid fa-check"></i>';
    if (status === 'rejected') return '<i class="fa-solid fa-xmark"></i>';
    return '<i class="fa-solid fa-clock"></i>';
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    var d = new Date(dateStr);
    return d.toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'numeric'}) + ', ' + d.toLocaleTimeString('en-US', {hour:'2-digit', minute:'2-digit'});
}

function getNextStep(data) {
    if (data.overall_status === 'rejected') return { text: 'Request was rejected', color: 'danger', icon: 'fa-circle-xmark' };
    if (data.overall_status === 'purchased') return { text: 'Completed — Payment processed & assets registered', color: 'success', icon: 'fa-circle-check' };
    if (data.dept_head_status === 'pending') return { text: 'Waiting for Department Head / Reporting Boss approval', color: 'info', icon: 'fa-hourglass-half' };
    if (data.operations_status === 'pending') return { text: 'Waiting for Operations (Role 20, 32) approval', color: 'secondary', icon: 'fa-hourglass-half' };
    if (data.purchase_head_status === 'pending') return { text: 'Waiting for Purchase Head (Role 17, 31) approval', color: 'primary', icon: 'fa-hourglass-half' };
    if (data.qc_audit_status === 'pending') return { text: 'Waiting for QC & Internal Audit review', color: 'info', icon: 'fa-hourglass-half' };
    if (data.mgmt_status === 'pending') return { text: 'Waiting for Management approval', color: 'warning', icon: 'fa-hourglass-half' };
    if (data.payment_status === 'unpaid') return { text: 'Approved — Waiting for Accounts to process payment', color: 'success', icon: 'fa-money-bill-wave' };
    return { text: 'Processing', color: 'info', icon: 'fa-clock' };
}

function getStepTitle(step) {
    var titles = {
        'dept_head': 'Department Head / Reporting Boss Approval',
        'operations': 'Operations Approval (Role 20, 32)',
        'purchase_head': 'Purchase Head Approval (Role 17, 31)',
        'qc_audit': 'QC & Internal Audit Review',
        'mgmt': 'Management Approval'
    };
    return titles[step] || 'Step Approval';
}

function openDetailModal(btn) {
    var data = JSON.parse(btn.getAttribute('data-info'));
    renderDetailModal(data);
}

function renderDetailModal(data) {
    document.getElementById('detailTitle').textContent = 'Purchase Request #' + data.id;
    document.getElementById('detailDate').textContent = 'Submitted on ' + formatDate(data.created_at);

    var nextStep = getNextStep(data);
    
    var dhClass = getStepClass(data.dept_head_status);
    var opClass = getStepClass(data.operations_status);
    var phClass = getStepClass(data.purchase_head_status);
    var qcClass = getStepClass(data.qc_audit_status);
    var mgClass = getStepClass(data.mgmt_status);
    var pyClass = data.payment_status === 'paid' ? 'approved' : 'pending';
    var pcClass = data.overall_status === 'purchased' ? 'approved' : 'pending';
    
    // Add "waiting" animation to the current active step
    if (data.overall_status !== 'rejected' && data.overall_status !== 'purchased') {
        if (data.dept_head_status === 'pending') dhClass = 'waiting';
        else if (data.operations_status === 'pending') opClass = 'waiting';
        else if (data.purchase_head_status === 'pending') phClass = 'waiting';
        else if (data.qc_audit_status === 'pending') qcClass = 'waiting';
        else if (data.mgmt_status === 'pending') mgClass = 'waiting';
        else if (data.payment_status === 'unpaid') pyClass = 'waiting';
        else if (data.overall_status !== 'purchased' && data.payment_status === 'paid') pcClass = 'waiting';
    }

    var body = document.getElementById('detailModalBody');
    body.innerHTML = `
        <!-- Next Step Banner -->
        <div class="next-step-banner bg-${nextStep.color}-transparent mb-3">
            <i class="fa-solid ${nextStep.icon} text-${nextStep.color}"></i>
            <div>
                <div class="fw-bold fs-12 text-${nextStep.color}">${nextStep.text}</div>
            </div>
        </div>

        <!-- Request Info Card -->
        <div class="detail-info-card mb-3">
            <div class="row g-2">
                <div class="col-md-4">
                    <div class="detail-label">Project</div>
                    <div class="detail-value"><i class="fa-solid fa-building text-primary me-1 fs-11"></i>${data.project_name || '—'}</div>
                </div>
                <div class="col-md-4">
                    <div class="detail-label">Location</div>
                    <div class="detail-value"><i class="fa-solid fa-location-dot text-info me-1 fs-11"></i>${data.branch_name || data.asset_location || '—'}</div>
                </div>
                <div class="col-md-4">
                    <div class="detail-label">Requested By</div>
                    <div class="detail-value"><i class="fa-solid fa-user text-secondary me-1 fs-11"></i>${data.requested_by_name || '—'}</div>
                </div>
                <div class="col-md-4 mt-2">
                    <div class="detail-label">Assign To</div>
                    <div class="detail-value"><i class="fa-solid fa-user-check text-success me-1 fs-11"></i>${data.assigned_to_name || '—'}</div>
                </div>
                <div class="col-md-8 mt-2">
                    <div class="detail-label">Item</div>
                    <div class="detail-value">
                        ${data.item_name || '—'}
                        ${data.category_name ? `<br><small class="text-muted fs-10">${data.category_name}</small>` : ''}
                    </div>
                </div>
                <div class="col-md-3 mt-2">
                    <div class="detail-label">Quantity</div>
                    <div class="detail-value">${data.quantity} pcs</div>
                </div>
                <div class="col-md-3 mt-2">
                    <div class="detail-label">Total Amount</div>
                    <div class="detail-value text-success">৳ ${parseFloat(data.total_price || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                </div>
                ${(data.payment_status === 'paid' || data.overall_status === 'paid' || data.overall_status === 'purchased') ? `
                <div class="col-md-3 mt-2">
                    <div class="detail-label text-success">Paid Amount</div>
                    <div class="detail-value text-success fw-bold">৳ ${parseFloat(data.paid_amount || data.total_price || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                </div>
                <div class="col-md-3 mt-2">
                    <div class="detail-label text-muted">Payment Method</div>
                    <div class="detail-value text-muted"><span class="badge bg-light text-dark border">${data.payment_method || 'Cash'}</span></div>
                </div>
                ` : ''}
                ${parseFloat(data.return_amount || 0) > 0 ? `
                <div class="col-md-3 mt-2">
                    <div class="detail-label text-danger">Returned Bal.</div>
                    <div class="detail-value text-danger">৳ ${parseFloat(data.return_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                </div>
                <div class="col-md-3 mt-2">
                    <div class="detail-label text-primary">Spent Amount</div>
                    <div class="detail-value text-primary">৳ ${(parseFloat(data.paid_amount || 0) - parseFloat(data.return_amount || 0)).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                </div>
                ` : ''}
                <div class="col-12 mt-2">
                    <div class="detail-label">Justification</div>
                    <div class="bg-white border rounded p-2 fs-12 mt-1">${data.justification || 'No justification provided.'}</div>
                </div>
            </div>
        </div>

        <!-- Approval Pipeline -->
        <div class="detail-label mb-1 px-1">APPROVAL PIPELINE</div>
        <div class="approval-pipeline">
            <div class="approval-step">
                <div class="step-circle approved"><i class="fa-solid fa-paper-plane" style="font-size:14px"></i></div>
                <div class="step-label">Requested</div>
                <div class="step-person">${data.requested_by_name || '—'}</div>
                <div class="step-time">${data.created_at ? formatDate(data.created_at) : ''}</div>
            </div>
            ${data.dept_head_status !== 'not_required' ? `
            <div class="approval-step">
                <div class="step-circle ${dhClass}">${getStepIcon(data.dept_head_status)}</div>
                <div class="step-label">Dept Head</div>
                <div class="step-person">${data.dept_head_by_name || (data.reporting_boss_name ? data.reporting_boss_name : '<span class="text-muted">Awaiting</span>')}</div>
                <div class="step-time">${data.dept_head_at ? formatDate(data.dept_head_at) : ''}</div>
            </div>
            ` : ''}
            <div class="approval-step">
                <div class="step-circle ${opClass}">${getStepIcon(data.operations_status)}</div>
                <div class="step-label">Operations</div>
                <div class="step-person">${data.operations_by_name || '<span class="text-muted">Awaiting</span>'}</div>
                <div class="step-time">${data.operations_at ? formatDate(data.operations_at) : ''}</div>
            </div>
            <div class="approval-step">
                <div class="step-circle ${phClass}">${getStepIcon(data.purchase_head_status)}</div>
                <div class="step-label">Purchase Head</div>
                <div class="step-person">${data.purchase_head_by_name || '<span class="text-muted">Awaiting</span>'}</div>
                <div class="step-time">${data.purchase_head_at ? formatDate(data.purchase_head_at) : ''}</div>
            </div>
            <div class="approval-step">
                <div class="step-circle ${qcClass}">${getStepIcon(data.qc_audit_status)}</div>
                <div class="step-label">QC & Audit</div>
                <div class="step-person">${data.qc_audit_by_name || '<span class="text-muted">Awaiting</span>'}</div>
                <div class="step-time">${data.qc_audit_at ? formatDate(data.qc_audit_at) : ''}</div>
            </div>
            <div class="approval-step">
                <div class="step-circle ${mgClass}">${getStepIcon(data.mgmt_status)}</div>
                <div class="step-label">Management</div>
                <div class="step-person">${data.mgmt_by_name || '<span class="text-muted">Awaiting</span>'}</div>
                <div class="step-time">${data.mgmt_at ? formatDate(data.mgmt_at) : ''}</div>
            </div>
            <div class="approval-step">
                <div class="step-circle ${pyClass}">${data.payment_status === 'paid' ? '<i class="fa-solid fa-check"></i>' : '<i class="fa-solid fa-bangladeshi-taka-sign" style="font-size:14px"></i>'}</div>
                <div class="step-label">Payment</div>
                <div class="step-person">${data.paid_by_name || '<span class="text-muted">Awaiting</span>'}</div>
                <div class="step-time">${data.paid_at ? formatDate(data.paid_at) : ''}</div>
            </div>
            <div class="approval-step">
                <div class="step-circle ${pcClass}">${data.overall_status === 'purchased' ? '<i class="fa-solid fa-box-open"></i>' : '<i class="fa-solid fa-box" style="font-size:14px"></i>'}</div>
                <div class="step-label">Purchased</div>
                <div class="step-person">${data.purchased_by_name || '<span class="text-muted">Awaiting</span>'}</div>
                <div class="step-time">${data.purchase_confirmed_at ? formatDate(data.purchase_confirmed_at) : ''}</div>
            </div>
        </div>

        <!-- Approval Notes (if any) -->
        ${(data.dept_head_note || data.operations_note || data.purchase_head_note || data.qc_audit_note || data.mgmt_note || data.purchase_note || data.return_note) ? `
        <div class="mt-3">
            <div class="detail-label mb-1 px-1">APPROVAL NOTES</div>
            <div class="bg-light rounded p-2">
                ${data.dept_head_note ? '<div class="fs-11 mb-1"><strong class="text-info">Dept Head:</strong> ' + data.dept_head_note + '</div>' : ''}
                ${data.operations_note ? '<div class="fs-11 mb-1"><strong class="text-secondary">Operations:</strong> ' + data.operations_note + '</div>' : ''}
                ${data.purchase_head_note ? '<div class="fs-11 mb-1"><strong class="text-primary">Purchase Head:</strong> ' + data.purchase_head_note + '</div>' : ''}
                ${data.qc_audit_note ? '<div class="fs-11 mb-1"><strong class="text-info">QC & Audit:</strong> ' + data.qc_audit_note + '</div>' : ''}
                ${data.mgmt_note ? '<div class="fs-11 mb-1"><strong class="text-warning">Management:</strong> ' + data.mgmt_note + '</div>' : ''}
                ${data.purchase_note ? '<div class="fs-11 mb-1"><strong class="text-success">Operations / Purchaser:</strong> ' + data.purchase_note + '</div>' : ''}
                ${data.return_note ? '<div class="fs-11"><strong class="text-danger">Return Note:</strong> ' + data.return_note + '</div>' : ''}
            </div>
        </div>
        ` : ''}

        <!-- Documents & Vouchers -->
        ${(data.attachment || data.payment_attachment || data.purchase_document || data.purchase_head_document || data.payment_status === 'paid' || data.overall_status === 'paid' || data.overall_status === 'purchased') ? `
        <div class="mt-3">
            <div class="detail-label mb-1 px-1">SUBMITTED DOCUMENTS & VOUCHERS</div>
            <div class="d-flex flex-wrap gap-2">
                ${(data.payment_status === 'paid' || data.overall_status === 'paid' || data.overall_status === 'purchased') ? '<a href="<?= $GLOBALS['base_url'] ?>/expense-adjustments/create?parent_type=extra_purchase&cashout_request_id=' + data.id + '&project_id=' + (data.project_id || '') + '" class="btn btn-sm btn-warning shadow-sm"><i class="fa-solid fa-sliders me-1"></i> Settlement / Adjustment</a>' : ''}
                ${(data.payment_status === 'paid' || data.overall_status === 'paid' || data.overall_status === 'purchased') ? '<a href="<?= $GLOBALS['base_url'] ?>/print-debit-voucher?module=project_extra_assets&id=' + data.id + '" target="_blank" class="btn btn-sm btn-primary shadow-sm"><i class="fa-solid fa-file-invoice-dollar me-1"></i> Print Debit Voucher</a>' : ''}
                ${data.attachment ? '<a href="<?= $GLOBALS['base_url'] ?>/' + data.attachment + '" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-file-pdf me-1"></i> Request Doc</a>' : ''}
                ${data.purchase_head_document ? '<a href="<?= $GLOBALS['base_url'] ?>/' + data.purchase_head_document + '" target="_blank" class="btn btn-sm btn-outline-info"><i class="fa-solid fa-file-invoice me-1"></i> Purchase Head Doc</a>' : ''}
                ${data.payment_attachment ? '<a href="<?= $GLOBALS['base_url'] ?>/' + data.payment_attachment + '" target="_blank" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-receipt me-1"></i> Payment Receipt</a>' : ''}
                ${data.purchase_document ? '<a href="<?= $GLOBALS['base_url'] ?>/' + data.purchase_document + '" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-file-invoice me-1"></i> Purchase Document</a>' : ''}
            </div>
        </div>
        ` : ''}

        <!-- Action / Approval Section if User Can Approve -->
        ${data.can_approve ? `
        <div class="mt-4 p-3 border border-success rounded-3 bg-light shadow-sm">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fw-bold fs-13 text-success">
                    <i class="fa-solid fa-shield-check me-1"></i> Ready for Approval: ${getStepTitle(data.approval_step)}
                </span>
                <span class="badge bg-success-subtle text-success border border-success-subtle">Authorized Approver</span>
            </div>
            <form id="detailInlineApprovalForm" action="<?= $GLOBALS['base_url'] ?>/asset-extra-purchases/approve/${data.id}" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="step" value="${data.approval_step}">
                <input type="hidden" name="approval_status" id="detailApprovalStatus_${data.id}" value="approved">

                ${data.approval_step === 'purchase_head' ? `
                <div class="mb-2">
                    <label class="form-label fs-11 fw-semibold mb-1">Upload Quotation / Document (Optional)</label>
                    <input type="file" name="purchase_head_document" class="form-control form-control-sm">
                </div>
                ` : ''}

                <div class="mb-3">
                    <label class="form-label fs-11 fw-semibold mb-1">Approval / Rejection Note (Optional)</label>
                    <input type="text" name="approval_note" class="form-control form-control-sm" placeholder="Add approval or rejection note (optional)...">
                </div>

                <div class="d-flex justify-content-end gap-2 pt-1">
                    <button type="submit" class="btn btn-sm btn-outline-danger px-3" onclick="document.getElementById('detailApprovalStatus_${data.id}').value='rejected'; return confirm('Are you sure you want to REJECT this purchase request?');">
                        <i class="fa-solid fa-xmark me-1"></i> Reject Request
                    </button>
                    <button type="submit" class="btn btn-sm btn-success px-4 fw-semibold" onclick="document.getElementById('detailApprovalStatus_${data.id}').value='approved';">
                        <i class="fa-solid fa-check me-1"></i> Approve Request
                    </button>
                </div>
            </form>
        </div>
        ` : ''}
    `;
    var modalEl = document.getElementById('detailModal');
    if (modalEl) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        } else if (typeof $ !== 'undefined' && $.fn.modal) {
            $(modalEl).modal('show');
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    function triggerAutoOpen() {
        <?php if (!empty($autoOpenRequest)): ?>
            renderDetailModal(<?= json_encode($autoOpenRequest) ?>);
            var targetRow = document.querySelector(`tr[data-request-id="<?= $autoOpenRequest['id'] ?>"]`);
            if (targetRow) {
                targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                targetRow.classList.add('table-primary');
            }
        <?php else: ?>
            const urlParams = new URLSearchParams(window.location.search);
            const viewId = urlParams.get('view') || urlParams.get('id');
            if (viewId) {
                const btn = document.querySelector(`button[data-id="${viewId}"]`);
                if (btn) {
                    openDetailModal(btn);
                    btn.closest('tr')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    btn.closest('tr')?.classList.add('table-primary');
                }
            }
        <?php endif; ?>
    }

    // Small delay ensures bootstrap bundle from footer has initialized
    setTimeout(triggerAutoOpen, 150);
});

function openConfirmPurchaseModal(btn) {
    var data = JSON.parse(btn.getAttribute('data-info'));
    document.getElementById('confirmPurchaseForm').action = '<?= $GLOBALS['base_url'] ?>/asset-extra-purchases/confirm/' + data.id;
    document.getElementById('confirm_purchase_note').value = '';
    document.getElementById('confirm_purchase_document').value = '';
    
    // Set default assignment based on request data if available
    new bootstrap.Modal(document.getElementById('confirmPurchaseModal')).show();
}
</script>

<!-- ========================================================================= -->
<!-- MODAL: CONFIRM PURCHASE (Operations) -->
<!-- ========================================================================= -->
<div class="modal fade" id="confirmPurchaseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="confirmPurchaseForm" action="" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header bg-success-transparent">
                    <h6 class="modal-title fw-semibold"><i class="fa-solid fa-box-check me-2"></i>Confirm Purchase Received</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted fs-12 mb-3">Confirm that the purchased items have been received. This will register the assets in the system.</p>
                    

                    <div class="mb-3">
                        <label class="form-label">Upload Document <span class="text-muted fs-11">(Invoice, Receipt, Challan etc.)</span></label>
                        <input type="file" name="purchase_document" id="confirm_purchase_document" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Returned Balance <span class="text-muted fs-11">(optional, if money was saved)</span></label>
                        <input type="number" name="return_amount" class="form-control" step="0.01" min="0" placeholder="0.00">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Return Note <span class="text-muted fs-11">(optional)</span></label>
                        <input type="text" name="return_note" class="form-control" placeholder="E.g., Vendor discount applied">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Purchase Notes <span class="text-muted fs-11">(optional)</span></label>
                        <textarea name="purchase_note" id="confirm_purchase_note" class="form-control" rows="2" placeholder="Any remarks about the received items..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm">Confirm & Register Assets</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    if (typeof jQuery !== 'undefined') {
        jQuery(document).on('shown.bs.modal', '#newRequestModal', function () {
            if (jQuery.fn && jQuery.fn.select2) {
                jQuery('#new_request_assigned_to').select2({
                    dropdownParent: jQuery('#newRequestModal'),
                    placeholder: "-- Optional: Assign to Person --",
                    allowClear: true,
                    width: '100%'
                });
            }
        });
    }
});
</script>

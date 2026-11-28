<?php
$roleId = (int)($_SESSION['user']['role_id'] ?? 0);
$userId = (int)($_SESSION['user']['id'] ?? 0);

$canCreate = true; // Enable New Request button for all logged in users

$roleNameSession = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
$isChiefOfficer = (in_array($roleId, [33, 34, 35], true) || in_array($roleNameSession, ['chief officer', 'management viewer', 'seal ceo'], true));

// Approval Pipeline Role Permissions (Admin=1, Super Admin=3, Board/DCEO=38)
$isAdminOrMgmt = (in_array($roleId, [1, 3, 38], true) || in_array($roleNameSession, ['super admin', 'admin', 'management', 'board of director', 'board of directors'], true) || strpos($roleNameSession, 'director') !== false || strpos($roleNameSession, 'dceo') !== false) && !$isChiefOfficer; 
$isOperations   = ($isAdminOrMgmt || in_array((int)$roleId, [20, 32], true) || strpos($roleNameSession, 'operation head') !== false || strpos($roleNameSession, 'admin & operation') !== false) && !$isChiefOfficer; // Admin & Operation Step: Role 20, 32
$isPurchaseHead = ($isAdminOrMgmt || in_array($roleId, [17, 31])) && !$isChiefOfficer; // Purchase Head Step: Role 17, 31
$isQcAudit      = ($isAdminOrMgmt || in_array($roleId, [24, 25])) && !$isChiefOfficer; // QC & Internal Audit Step
$isManagement   = $isAdminOrMgmt;                               // Management Step
$isAccounts     = ($isAdminOrMgmt || in_array($roleId, [6, 30]) || strpos($roleNameSession, 'account') !== false) && !$isChiefOfficer;  // Accounts Payment

// Global approvers / viewers include Management, Operations, Purchase, QC/Audit, Accounts roles
$isGlobalViewer = in_array($roleId, [1, 3, 38, 33, 34, 35, 6, 17, 20, 24, 25, 30, 31, 32]);
$isApprover     = ($isGlobalViewer || $userId === 242) && !$isChiefOfficer;
$activeTab      = $_GET['tab'] ?? ($isApprover ? 'pending_approval' : 'all_requests');

$overallBadges = [
    'pending' => 'warning', 'in_review' => 'info', 'approved' => 'success',
    'rejected' => 'danger', 'purchased' => 'primary'
];
$stepBadges = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between mb-3 page-header-breadcrumb">
        <h4 class="mb-0 fw-semibold">Office Procurement Requests</h4>
        <div class="d-flex gap-2 mt-2 mt-md-0">
            <a href="<?= $GLOBALS['base_url'] ?>/office-procurement/orders" class="btn btn-primary btn-sm fw-semibold shadow-sm">
                <i class="fa-solid fa-file-invoice-dollar me-1"></i> Office Procurement Orders (OPO)
            </a>
        </div>
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
    <div class="d-flex align-items-start gap-2.5 p-3 mb-3 rounded-3 border bg-warning-transparent fs-12 lh-base">
        <i class="fa-solid fa-triangle-exclamation text-warning mt-0.5 flex-shrink-0"></i>
        <div><strong>Service Manual:</strong> No construction materials requisition is allowed here for this system.</div>
    </div>
    <div class="d-flex align-items-start gap-2.5 p-3 mb-4 rounded-3 border bg-info-transparent fs-12 lh-base">
        <i class="fa-solid fa-circle-info text-info mt-0.5 flex-shrink-0"></i>
        <div>
            <strong>Office Procurement Flow:</strong> 
            1. PR Request → 2. Dept Head Approval → 3. Admin &amp; Operation Review &amp; Assign CST → 4. Assigned Employee Multi-Vendor CST &amp; OPO → 5. OPO Approval (Purchase Head → QC &amp; Internal Audit Head → Admin &amp; Operation Head) → 6. Accounts Head / Management (&gt;10K) → Disbursement → 7. Process on Purchase → Purchased → 8. Requester Received Asset (Asset Tracker &amp; Financial Statements)
        </div>
    </div>

    <!-- Filter Card Wrapper (Matching flat-lists) -->
    <div class="card__wrapper mb-3">
        <form method="GET" action="<?= $GLOBALS['base_url'] ?>/asset-extra-purchases">
            <?php if (!empty($isApprover)): ?>
                <input type="hidden" name="tab" value="<?= htmlspecialchars($activeTab) ?>">
            <?php endif; ?>

            <!-- Row 1: Filter inputs -->
            <div class="row g-2 align-items-center mb-2">
                <div class="col-xl-3 col-md-3 col-sm-6">
                    <select name="department_id" class="form-control form-control-sm select2">
                        <option value="">All Departments</option>
                        <?php foreach ($departments ?? [] as $dept): ?>
                            <option value="<?= $dept['id'] ?>" <?= ($_GET['department_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-xl-3 col-md-3 col-sm-6">
                    <select name="office_branch_id" class="form-control form-control-sm select2">
                        <option value="">All Branches / Locations</option>
                        <?php foreach ($officeBranches ?? [] as $b): ?>
                            <option value="<?= $b['id'] ?>" <?= ((string)($_GET['office_branch_id'] ?? '') === (string)$b['id'] || ($_GET['workplace'] ?? '') === $b['name']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($b['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-xl-2 col-md-3 col-sm-6">
                    <select name="project_id" class="form-control form-control-sm select2">
                        <option value="">All Projects</option>
                        <?php foreach ($projects ?? [] as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= ((string)($_GET['project_id'] ?? '') === (string)$p['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-xl-2 col-md-3 col-sm-6">
                    <select name="status" class="form-control form-control-sm">
                        <option value="">All Statuses</option>
                        <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="in_review" <?= ($_GET['status'] ?? '') === 'in_review' ? 'selected' : '' ?>>In Review</option>
                        <option value="approved" <?= ($_GET['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="rejected" <?= ($_GET['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        <option value="purchased" <?= ($_GET['status'] ?? '') === 'purchased' ? 'selected' : '' ?>>Purchased</option>
                    </select>
                </div>
                <div class="col-xl-2 col-md-12 col-sm-6">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-secondary btn-sm flex-fill"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                        <a href="<?= $GLOBALS['base_url'] ?>/asset-extra-purchases" class="btn btn-success btn-sm flex-fill text-center"><i class="fa-solid fa-rotate-right me-1"></i> Reset</a>
                    </div>
                </div>
            </div>

            <!-- Row 2: Action Buttons -->
            <div class="row mt-3">
                <div class="col-12">
                    <div class="d-flex flex-wrap gap-2">
                        <?php if (!empty($canCreate)): ?>
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newRequestModal">
                                <i class="fa-solid fa-plus me-1"></i> New Request
                            </button>
                        <?php endif; ?>
                        <?php if (!empty($isOperations)): ?>
                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#manageCategoryModal">
                                <i class="fa-solid fa-gear me-1"></i> Manage Categories
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Table Card Wrapper -->
    <div class="row">
        <div class="col-xxl-12">
            <div class="card__wrapper">
                <?php
                $qsFilter = (!empty($_GET['department_id']) ? '&department_id=' . urlencode($_GET['department_id']) : '')
                    . (!empty($_GET['office_branch_id']) ? '&office_branch_id=' . urlencode($_GET['office_branch_id']) : '')
                    . (!empty($_GET['project_id']) ? '&project_id=' . urlencode($_GET['project_id']) : '')
                    . (!empty($_GET['status']) ? '&status=' . urlencode($_GET['status']) : '');
                ?>
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 pb-2 border-bottom">
                    <ul class="nav nav-tabs nav-tabs-bordered mb-0">
                        <li class="nav-item">
                            <a class="nav-link <?= $activeTab === 'pending_approval' ? 'active' : '' ?> fw-semibold" href="?tab=pending_approval<?= $qsFilter ?>">
                                <i class="fa-solid fa-clock-rotate-left me-1 text-warning"></i>Pending My Action
                                <?php if (!empty($pendingCount) && $pendingCount > 0): ?>
                                    <span class="badge bg-warning text-dark rounded-pill ms-1"><?= (int)$pendingCount ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $activeTab === 'assigned_to_me' ? 'active' : '' ?> fw-semibold" href="?tab=assigned_to_me<?= $qsFilter ?>">
                                <i class="fa-solid fa-scale-balanced me-1 text-info"></i>Assigned to Me (CST)
                                <?php if (!empty($assignedToMeCount) && $assignedToMeCount > 0): ?>
                                    <span class="badge bg-info text-white rounded-pill ms-1"><?= (int)$assignedToMeCount ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $activeTab === 'my_requests' ? 'active' : '' ?> fw-semibold" href="?tab=my_requests<?= $qsFilter ?>">
                                <i class="fa-solid fa-user me-1"></i>My Requests
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $activeTab === 'all_requests' ? 'active' : '' ?> fw-semibold" href="?tab=all_requests<?= $qsFilter ?>">
                                <i class="fa-solid fa-list-check me-1"></i>All Requests
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="table__wrapper table-responsive">
                    <table class="table mb-20" id="procurementTable">
                        <thead>
                            <tr class="table__title">
                                <th style="width:40px" class="text-center">#</th>
                                <th>PR # &amp; Date</th>
                                <th>Item &amp; Location</th>
                                <th class="text-end">Qty &amp; Amount</th>
                                <th>Requested By</th>
                                <th class="text-center">1. Dept Head</th>
                                <th class="text-center">2. Admin &amp; Operation / CST</th>
                                <th class="text-center">3. OPO &amp; Current Stage</th>
                                <th class="text-center no-print text-nowrap" style="min-width:170px; white-space:nowrap;">Action</th>
                            </tr>
                        </thead>
                        <tbody class="table__body">
                            <?php if (!empty($requests)): ?>
                                <?php
                                $opoStageLabels = [
                                    'pending_purchase_head' => ['Pending Purchase Head', '#b45309', '#fef3c7'],
                                    'pending_qc'            => ['Pending QC & Internal Audit', '#0369a1', '#e0f2fe'],
                                    'pending_op_head'       => ['Pending Admin & Operation Head', '#1d4ed8', '#dbeafe'],
                                    'revision_requested'    => ['Rate Revision Needed', '#dc2626', '#fee2e2'],
                                    'approved'              => ['OPO Approved (Payment)', '#15803d', '#dcfce7'],
                                    'paid'                  => ['Disbursed (Confirm Receipt)', '#1d4ed8', '#dbeafe'],
                                    'process_on_purchase'   => ['Process on Purchase', '#0284c7', '#e0f2fe'],
                                    'purchased'             => ['Purchased (Confirm Asset)', '#6d28d9', '#ede9fe'],
                                    'received'              => ['Received & In Asset Tracker', '#15803d', '#dcfce7'],
                                    'rejected'              => ['Rejected', '#dc2626', '#fee2e2'],
                                ];
                                $epEditMap = [];
                                if (function_exists('db')) {
                                    $epDb = db();
                                    if ($epDb) {
                                        require_once __DIR__ . '/../../../services/RequisitionRevisionService.php';
                                        $epEditMap = (new RequisitionRevisionService($epDb))->getLastEditMap('project_extra_assets');
                                    }
                                }
                                ?>
                                <?php foreach ($requests as $index => $r):
                                    $dhStatus  = $r['dept_head_status'] ?? 'not_required';
                                    $opStatus  = $r['operations_status'] ?? 'pending';
                                    $isRejected = ($r['overall_status'] === 'rejected' || $dhStatus === 'rejected' || $opStatus === 'rejected' || ($r['opo_status'] ?? '') === 'rejected');
                                    $isCreator = ((int)$r['requested_by'] === $userId);
                                    $isAssignee = ((int)($r['assigned_cst_user_id'] ?? 0) === $userId);
                                    $reportingBossId = (int)($r['reporting_user_id'] ?? $r['requester_reporting_boss_id'] ?? 0);
                                    $isReporterBoss = ($reportingBossId === $userId);

                                    $isRole32AdminOps = ($roleId === 32 || $roleNameSession === 'admin & operation');
                                    $canDeptHeadApprove = !$isCreator && !$isChiefOfficer && !$isRejected && $dhStatus === 'pending' && ($isReporterBoss || $isAdminOrMgmt);
                                    $canRole32Assign = !$isChiefOfficer && !$isRejected && in_array($dhStatus, ['approved', 'not_required'], true) && empty($r['opo_id']) && $isRole32AdminOps;
                                    $canDoCst = !$isRejected && !empty($r['assigned_cst_user_id']) && ($isAssignee || $isAdminOrMgmt) && (empty($r['opo_id']) || ($r['opo_status'] ?? '') === 'revision_requested');
                                    $isInitialPending = in_array($dhStatus, ['pending', 'not_required'], true) && $opStatus === 'pending' && empty($r['assigned_cst_user_id']) && empty($r['opo_id']);
                                    $canDelete = ($isCreator || $isAdminOrMgmt) && $isInitialPending;
                                    $displayName = (!empty($r['item_name']) && trim((string)$r['item_name']) !== '0')
                                        ? $r['item_name']
                                        : (!empty($r['justification']) ? mb_substr($r['justification'], 0, 50) . '...' : 'Office Asset');
                                ?>
                                    <tr data-request-id="<?= $r['id'] ?>" class="<?= (!empty($autoOpenRequest) && $autoOpenRequest['id'] == $r['id']) ? 'table-primary' : '' ?>">
                                        <td class="text-center fw-bold text-muted"><?= $index + 1 ?></td>
                                        <td>
                                            <a href="<?= $GLOBALS['base_url'] ?>/asset-extra-purchase/<?= $r['id'] ?>" class="fw-bold text-primary text-decoration-none">PR-#<?= $r['id'] ?></a>
                                            <div class="text-muted fs-11"><?= date('d M Y, h:i A', strtotime($r['created_at'])) ?></div>
                                        </td>
                                        <td>
                                            <div class="fs-13 fw-semibold text-dark"><?= htmlspecialchars($displayName) ?></div>
                                            <div class="d-flex flex-wrap gap-1 mt-0.5">
                                                <?php if (!empty($r['category_name'])): ?>
                                                    <span class="badge bg-light text-secondary border fs-10"><?= htmlspecialchars($r['category_name']) ?></span>
                                                <?php endif; ?>
                                                <span class="badge bg-light text-dark border fs-10"><i class="fa-solid fa-location-dot text-danger me-1"></i><?= htmlspecialchars(!empty($r['workplace_name']) ? $r['workplace_name'] : 'Head Office') ?></span>
                                                <?php if (!empty($r['vendor_name'])): ?>
                                                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle fs-10"><i class="fa-solid fa-store me-1"></i><?= htmlspecialchars($r['vendor_name']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="text-end text-nowrap">
                                            <div class="fw-bold text-dark fs-12"><?= (int)$r['quantity'] ?> pcs</div>
                                            <?php if ((float)($r['total_price'] ?? 0) > 0): ?>
                                                <div class="fw-bold text-primary fs-12">TK <?= number_format((float)$r['total_price'], 2) ?></div>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border fs-10">Rate via CST</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fs-12">
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars(!empty($r['requested_by_name']) ? $r['requested_by_name'] : 'System Admin') ?></div>
                                            <?php if (!empty($r['department_name'])): ?>
                                                <small class="text-muted fs-10"><?= htmlspecialchars($r['department_name']) ?></small>
                                            <?php endif; ?>
                                            <?php $epEdit = $epEditMap[(int)($r['id'] ?? 0)] ?? null; ?>
                                            <?php if (!empty($epEdit)): ?>
                                                <div class="mt-1" style="font-size:10px; color:#b45309; line-height:1.25;" title="<?= htmlspecialchars($epEdit['change_summary'] ?? 'Edited') ?>">
                                                    <i class="fa-solid fa-pen-to-square me-1"></i>Edited: <strong><?= htmlspecialchars($epEdit['edited_by_name']) ?></strong>
                                                    <?php if (!empty($epEdit['created_at'])): ?>
                                                        <div class="text-muted" style="font-size:9.5px;"><?= date('d M Y, h:i A', strtotime($epEdit['created_at'])) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <!-- Step 1: Dept Head -->
                                        <td class="text-center">
                                            <?php if ($dhStatus === 'not_required'): ?>
                                                <span class="badge fs-10 px-2 py-1" style="background:#e9ecef;color:#6c757d;">N/A</span>
                                            <?php else:
                                                $dhColor = $dhStatus === 'approved' ? '#198754' : ($dhStatus === 'rejected' ? '#dc3545' : '#b45309');
                                                $dhBg    = $dhStatus === 'approved' ? '#d1e7dd' : ($dhStatus === 'rejected' ? '#f8d7da' : '#fef3c7');
                                            ?>
                                                <span class="badge fs-10 px-2 py-1 fw-semibold" style="background:<?= $dhBg ?>;color:<?= $dhColor ?>;"><?= ucfirst($dhStatus) ?></span>
                                                <?php if ($dhStatus !== 'pending' && !empty($r['dept_head_by_name'])): ?>
                                                    <small class="d-block text-muted fs-10 mt-1" style="line-height:1.2;">
                                                        <?= htmlspecialchars($r['dept_head_by_name']) ?>
                                                        <?= !empty($r['dept_head_at']) ? '<br>' . date('d M, h:i A', strtotime($r['dept_head_at'])) : '' ?>
                                                    </small>
                                                <?php elseif ($dhStatus === 'pending' && !empty($r['reporting_boss_name'])): ?>
                                                    <small class="d-block text-muted fs-10 mt-1"><i class="fa-solid fa-user-clock text-warning me-1"></i><?= htmlspecialchars($r['reporting_boss_name']) ?></small>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <!-- Step 2: Admin & Operation & CST Assignee -->
                                        <td class="text-center">
                                            <?php if (!empty($r['assigned_cst_name'])): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle fs-10 px-2 py-1">Assigned for CST</span>
                                                <div class="fw-bold text-dark fs-11 mt-1"><i class="fa-solid fa-user-tag text-primary me-1"></i><?= htmlspecialchars($r['assigned_cst_name']) ?></div>
                                                <?php if (!empty($r['assigned_cst_at'])): ?>
                                                    <small class="text-muted fs-10 d-block"><?= date('d M, h:i A', strtotime($r['assigned_cst_at'])) ?></small>
                                                <?php endif; ?>
                                            <?php elseif ($opStatus === 'rejected'): ?>
                                                <span class="badge bg-danger-subtle text-danger fs-10">Rejected</span>
                                            <?php elseif (in_array($r['overall_status'] ?? '', ['approved', 'paid', 'purchased', 'received'], true)): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle fs-10 px-2 py-1">Approved</span>
                                                <?php if (!empty($r['operations_by_name'])): ?>
                                                    <small class="d-block text-muted fs-10 mt-1"><?= htmlspecialchars($r['operations_by_name']) ?><?= !empty($r['operations_at']) ? '<br>' . date('d M, h:i A', strtotime($r['operations_at'])) : '' ?></small>
                                                <?php endif; ?>
                                            <?php elseif (in_array($dhStatus, ['approved', 'not_required'], true)): ?>
                                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle fs-10 px-2 py-1">Awaiting Admin &amp; Operation</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border fs-10">Waiting Dept Head</span>
                                            <?php endif; ?>
                                        </td>
                                        <!-- Step 3: OPO & Live Stage -->
                                        <td class="text-center">
                                            <?php if (!empty($r['opo_id']) && !empty($r['opo_number'])):
                                                $ost = $r['opo_status'] ?? 'pending_purchase_head';
                                                $oMeta = $opoStageLabels[$ost] ?? [ucfirst(str_replace('_', ' ', $ost)), '#475569', '#f1f5f9'];
                                            ?>
                                                <a href="<?= $GLOBALS['base_url'] ?>/office-procurement/order/<?= (int)$r['opo_id'] ?>" class="badge bg-primary text-white text-decoration-none fs-11 px-2 py-1 mb-1">
                                                    <i class="fa-solid fa-file-invoice me-1"></i><?= htmlspecialchars($r['opo_number']) ?>
                                                </a>
                                                <br>
                                                <span class="badge fs-10 px-2 py-1 fw-semibold" style="background:<?= $oMeta[2] ?>;color:<?= $oMeta[1] ?>;">
                                                    <?= htmlspecialchars($oMeta[0]) ?>
                                                </span>
                                            <?php elseif ($isRejected): ?>
                                                <span class="badge bg-danger text-white fs-10 px-2 py-1">Rejected</span>
                                            <?php elseif (!empty($r['assigned_cst_user_id'])): ?>
                                                <span class="badge bg-info-subtle text-info border border-info-subtle fs-10 px-2 py-1">Pending Multi-Vendor CST</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border fs-10"><?= ucfirst(str_replace('_', ' ', $r['overall_status'] ?? 'pending')) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <!-- Action Buttons (Role-Specific) -->
                                        <td class="text-center no-print text-nowrap" style="white-space:nowrap;">
                                            <div class="d-inline-flex align-items-center justify-content-center gap-1 flex-nowrap">
                                                <a href="<?= $GLOBALS['base_url'] ?>/asset-extra-purchase/<?= $r['id'] ?>" class="btn btn-sm btn-outline-secondary btn-icon" title="View PR Details">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>

                                                <?php if ($canDeptHeadApprove): ?>
                                                    <button type="button" class="btn btn-sm btn-success fw-semibold" title="Dept Head Approve"
                                                        onclick="openApprovalModal(<?= $r['id'] ?>, 'approved', 'dept_head', '')">
                                                        <i class="fa-solid fa-check me-1"></i> Approve
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger btn-icon" title="Dept Head Reject"
                                                        onclick="openApprovalModal(<?= $r['id'] ?>, 'rejected', 'dept_head', '')">
                                                        <i class="fa-solid fa-xmark"></i>
                                                    </button>
                                                <?php endif; ?>

                                                <?php if ($canRole32Assign): ?>
                                                    <a href="<?= $GLOBALS['base_url'] ?>/asset-extra-purchase/<?= $r['id'] ?>" class="btn btn-sm btn-primary fw-semibold" title="Review Requisition & Assign CST">
                                                        <i class="fa-solid fa-clipboard-check me-1"></i> Review &amp; Assign
                                                    </a>
                                                <?php endif; ?>

                                                <?php if ($canDoCst): ?>
                                                    <a href="<?= $GLOBALS['base_url'] ?>/office-procurement/cst/<?= $r['id'] ?>" class="btn btn-sm <?= (($r['opo_status'] ?? '') === 'revision_requested') ? 'btn-warning text-dark' : 'btn-success' ?> fw-bold">
                                                        <i class="fa-solid fa-scale-balanced me-1"></i> <?= (($r['opo_status'] ?? '') === 'revision_requested') ? 'Edit CST (Rev)' : 'Prepare CST' ?>
                                                    </a>
                                                <?php endif; ?>

                                                <?php if (!empty($r['opo_id'])): ?>
                                                    <a href="<?= $GLOBALS['base_url'] ?>/office-procurement/order/<?= (int)$r['opo_id'] ?>" class="btn btn-sm btn-info text-white fw-semibold" title="Open Office Procurement Order">
                                                        <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> OPO
                                                    </a>
                                                <?php endif; ?>

                                                <?php
                                                $epUid = (int)($_SESSION['user']['id'] ?? 0);
                                                $epRid = (int)($_SESSION['user']['role_id'] ?? 0);
                                                $epUnapproved = (($r['overall_status'] ?? 'pending') === 'pending')
                                                    && (($r['dept_head_status'] ?? '') !== 'approved') && empty($r['dept_head_by'])
                                                    && (($r['it_head_status'] ?? '') !== 'approved') && empty($r['it_head_by'])
                                                    && (($r['operations_status'] ?? '') !== 'approved') && empty($r['operations_by'])
                                                    && (($r['purchase_head_status'] ?? '') !== 'approved') && empty($r['purchase_head_by'])
                                                    && (($r['qc_audit_status'] ?? '') !== 'approved') && empty($r['qc_audit_by'])
                                                    && (($r['mgmt_status'] ?? '') !== 'approved') && empty($r['mgmt_by']);
                                                if ($epUnapproved && (((int)($r['requested_by'] ?? 0) === $epUid) || in_array($epRid, [1, 3], true))):
                                                    $epPayload = [
                                                        'id'            => (int)$r['id'],
                                                        'item_name'     => $r['item_name'] ?? '',
                                                        'quantity'      => (int)($r['quantity'] ?? 1),
                                                        'unit_price'    => $r['unit_price'] ?? 0,
                                                        'justification' => $r['justification'] ?? '',
                                                    ];
                                                ?>
                                                    <button type="button"
                                                            class="btn btn-sm btn-warning text-dark btn-icon btn-edit-extra-purchase"
                                                            title="Edit Request"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editExtraPurchaseIndexModal"
                                                            data-item="<?= htmlspecialchars(json_encode($epPayload), ENT_QUOTES, 'UTF-8') ?>">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </button>
                                                <?php endif; ?>

                                                <?php if ($canDelete): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-danger btn-icon" title="Delete Request"
                                                        onclick="openDeleteRequestModal(<?= $r['id'] ?>)">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
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

<!-- ========================================================================= -->
<!-- MODAL: ROLE 32 ADMIN & OPERATION — APPROVE & ASSIGN EMPLOYEE FOR CST -->
<!-- ========================================================================= -->
<div class="modal fade" id="assignCstModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary-transparent py-2.5 px-3">
                <h6 class="modal-title fw-bold text-primary"><i class="fa-solid fa-user-tag me-1.5"></i>Approve &amp; Assign Employee for Multi-Vendor CST</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <form id="assignCstForm" action="" method="POST">
                    <input type="hidden" name="redirect" value="list">
                    <div class="alert alert-light border py-2 px-3 fs-12 mb-3">
                        PR Item: <strong id="assignCstItemLabel">—</strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fs-12 fw-semibold">Select Employee (Admin &amp; Operation / Purchase / IT Dept) <span class="text-danger">*</span></label>
                        <select name="assigned_cst_user_id" id="assignCstUserSelect" class="form-select form-select-sm" style="width:100%;" required>
                            <option value="">-- Search by Employee ID, Name or Dept --</option>
                            <?php foreach ($assignableCstEmployees ?? [] as $emp): ?>
                                <option value="<?= $emp['id'] ?>">
                                    [<?= htmlspecialchars($emp['emp_code'] ?? ('UID-' . $emp['id'])) ?>] <?= htmlspecialchars($emp['full_name']) ?> — (<?= htmlspecialchars($emp['dept_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text fs-11">The selected employee will receive an instant notification and see this PR in their <strong>Assigned to Me (CST)</strong> tab.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fs-12 fw-semibold">Instructions / Remarks (Optional)</label>
                        <textarea name="assign_note" class="form-control form-control-sm" rows="2" placeholder="Any notes or deadline for collecting vendor quotations..."></textarea>
                    </div>
                    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold">
                            <i class="fa-solid fa-paper-plane me-1"></i> Approve &amp; Assign CST
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openAssignCstModal(prId, itemName, currentAssigneeId) {
    var form = document.getElementById('assignCstForm');
    if (form) form.action = '<?= $GLOBALS['base_url'] ?>/asset-extra-purchases/assign-cst/' + prId;
    var lbl = document.getElementById('assignCstItemLabel');
    if (lbl) lbl.textContent = 'PR-#' + prId + ' — ' + itemName;
    var sel = document.getElementById('assignCstUserSelect');
    var targetVal = currentAssigneeId > 0 ? String(currentAssigneeId) : '';
    if (sel) sel.value = targetVal;
    var modalEl = document.getElementById('assignCstModal');
    if (modalEl) {
        if (modalEl.parentNode !== document.body) document.body.appendChild(modalEl);
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
        if (typeof jQuery !== 'undefined' && jQuery.fn && jQuery.fn.select2) {
            var $sel = jQuery('#assignCstUserSelect');
            if ($sel.hasClass('select2-hidden-accessible')) {
                $sel.select2('destroy');
            }
            $sel.select2({
                dropdownParent: jQuery('#assignCstModal'),
                placeholder: '-- Search by Employee ID, Name or Dept --',
                allowClear: true,
                width: '100%'
            });
            $sel.val(targetVal).trigger('change');
        }
    }
}
</script>

<?php if (!empty($canCreate)): ?>
<!-- ========================================================================= -->
<!-- MODAL: NEW PURCHASE REQUEST -->
<!-- ========================================================================= -->
<div class="modal fade" id="newRequestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 780px;">
        <form action="<?= $GLOBALS['base_url'] ?>/asset-extra-purchases/store" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Office Procurement Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row ">
                        <div class="col-md-12">
                            <label class="form-label mb-1">Department</label>
                            <select name="department_id" class="form-select">
                                <option value="">-- Select Department --</option>
                                <?php 
                                $userDeptId = (int)($_SESSION['user']['department_id'] ?? 0);
                                foreach ($departments ?? [] as $dept): 
                                ?>
                                    <option value="<?= $dept['id'] ?>" <?= ($dept['id'] == $userDeptId) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($dept['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <?= renderCOADropdown($coaList ?? [], null, 'account_id', false, 'officeproc') ?>
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
                            <label class="form-label mb-1"><i class="fa-solid fa-store me-1 text-primary"></i>Vendor <span class="text-muted fs-11">(Optional)</span></label>
                            <select name="vendor_id" id="new_request_vendor_id" class="form-select select2-vendor" style="width: 100%;">
                                <option value="">-- Optional: Select Vendor --</option>
                                <?php foreach ($vendors ?? [] as $vnd): ?>
                                    <?php
                                    $vComp = trim($vnd['company_name'] ?? '');
                                    $vName = trim($vnd['name'] ?? '');
                                    if (!empty($vComp) && !empty($vName) && strcasecmp($vComp, $vName) !== 0) {
                                        $vLabel = $vComp . ' (' . $vName . ')';
                                    } else {
                                        $vLabel = !empty($vComp) ? $vComp : $vName;
                                    }
                                    ?>
                                    <option value="<?= $vnd['id'] ?>">
                                        <?= htmlspecialchars($vLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Item Name <span class="text-danger">*</span></label>
                            <input type="text" name="item_name" id="itemName" class="form-control" placeholder="e.g. Dell Laptop 15-inch" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" id="qty" class="form-control" value="1" min="1" required>
                        </div>
                        <div class="col-12">
                            <div class="bg-light border rounded px-3 py-2 fs-11 text-muted">
                                <i class="fa-solid fa-circle-info text-primary me-1"></i>
                                Unit price &amp; final vendor rate will be determined during the <strong>Multi-Vendor CST</strong> stage.
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
                    <div class="mb-3 d-none" id="purchaseHeadVendorGroup">
                        <label class="form-label fs-12 fw-semibold"><i class="fa-solid fa-store me-1 text-primary"></i>Select / Finalize Vendor (Optional)</label>
                        <select name="vendor_id" id="approvalVendorId" class="form-select form-select-sm">
                            <option value="">-- Keep Existing / No Vendor --</option>
                            <?php foreach ($vendors ?? [] as $vnd): ?>
                                <?php
                                $vComp = trim($vnd['company_name'] ?? '');
                                $vName = trim($vnd['name'] ?? '');
                                $vLabel = (!empty($vComp) && !empty($vName) && strcasecmp($vComp, $vName) !== 0)
                                    ? ($vComp . ' (' . $vName . ')')
                                    : (!empty($vComp) ? $vComp : $vName);
                                ?>
                                <option value="<?= $vnd['id'] ?>"><?= htmlspecialchars($vLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text fs-11">Assign or update the selected vendor for this procurement</div>
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
    <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width: 1180px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header border-bottom py-3 px-4 bg-light" style="border-radius: 14px 14px 0 0;">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="detailTitle">Purchase Request #1</h5>
                    <small class="text-muted" id="detailDate"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="detailModalBody">
                <!-- Filled by JS -->
            </div>
            <div class="modal-footer border-top py-2.5 px-4 bg-light" style="border-radius: 0 0 14px 14px;">
                <button type="button" class="btn btn-secondary btn-sm px-4 fw-semibold" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
.detail-info-card { background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%); border: 1px solid #e9ecef; border-radius: 12px; padding: 20px; }
.detail-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #6c757d; margin-bottom: 4px; }
.detail-value { font-size: 13px; font-weight: 600; color: #212529; line-height: 1.4; }
.next-step-banner { border-radius: 10px; padding: 12px 16px; display: flex; align-items: center; gap: 12px; }
.next-step-banner i { font-size: 18px; }
.ep-timeline-card { background: #fff; border: 1px solid #e9ecef; border-radius: 12px; padding: 20px; }
.ep-timeline-item { display: flex; gap: 14px; position: relative; padding-bottom: 18px; align-items: flex-start; }
.ep-timeline-item:last-child { padding-bottom: 0; }
.ep-timeline-item:not(:last-child)::before { content: ''; position: absolute; left: 16px; top: 34px; bottom: 0; width: 2px; background: #e9ecef; z-index: 0; }
.ep-timeline-item.ep-step-approved:not(:last-child)::before { background: #198754; }
.ep-timeline-circle { width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; z-index: 1; font-size: 13px; font-weight: 700; border: 2px solid #dee2e6; background: #f8f9fa; color: #6c757d; }
.ep-timeline-circle.approved { background: #198754; border-color: #198754; color: #fff; }
.ep-timeline-circle.rejected { background: #dc3545; border-color: #dc3545; color: #fff; }
.ep-timeline-circle.waiting { background: #fff8e1; border-color: #ffc107; color: #d97706; animation: ep-pulse-ring 1.5s infinite; }
.ep-timeline-circle.pending { background: #f8f9fa; border-color: #dee2e6; color: #adb5bd; }
@keyframes ep-pulse-ring { 0% { box-shadow: 0 0 0 0 rgba(245,158,11,0.45); } 70% { box-shadow: 0 0 0 7px rgba(245,158,11,0); } 100% { box-shadow: 0 0 0 0 rgba(245,158,11,0); } }
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

function openApprovalModal(id, status, step, overrideStage) {
    var form = document.getElementById('approvalForm');
    form.action = '<?= $GLOBALS['base_url'] ?>/asset-extra-purchases/approve/' + id;
    form.setAttribute('data-override-stage', overrideStage || '');
    document.getElementById('approvalStatus').value = status;
    document.getElementById('approvalStep').value = step;
    
    if (step === 'purchase_head' && status === 'approved') {
        document.getElementById('purchaseHeadDocGroup').classList.remove('d-none');
        var vGroup = document.getElementById('purchaseHeadVendorGroup');
        if (vGroup) vGroup.classList.remove('d-none');
    } else {
        document.getElementById('purchaseHeadDocGroup').classList.add('d-none');
        var vGroup = document.getElementById('purchaseHeadVendorGroup');
        if (vGroup) vGroup.classList.add('d-none');
    }
    
    var title = status === 'approved' ? 'Approve Request #' + id : 'Reject Request #' + id;
    document.getElementById('approvalModalTitle').textContent = title;
    
    var btn = document.getElementById('approvalSubmitBtn');
    btn.className = 'btn btn-sm btn-' + (status === 'approved' ? 'success' : 'danger');
    btn.textContent = status === 'approved' ? 'Approve' : 'Reject';
    
    new bootstrap.Modal(document.getElementById('approvalModal')).show();
}

document.addEventListener('DOMContentLoaded', function() {
    var approvalFormEl = document.getElementById('approvalForm');
    if (approvalFormEl) {
        approvalFormEl.addEventListener('submit', function(e) {
            var statusVal = document.getElementById('approvalStatus').value;
            var overrideStage = approvalFormEl.getAttribute('data-override-stage') || '';
            if (statusVal === 'approved' && overrideStage !== '' && !approvalFormEl.getAttribute('data-confirmed-override')) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Pending ' + overrideStage + "'s Approval",
                        html: 'This purchase request is pending for <strong>' + overrideStage + "'s</strong> approval. Choose whether to approve <strong>only " + overrideStage + '</strong> and forward to the next stage, or override all stages &amp; finalize now.',
                        icon: 'warning',
                        showCancelButton: true,
                        showDenyButton: true,
                        confirmButtonColor: '#0d6efd',
                        denyButtonColor: '#198754',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Approve Only ' + overrideStage,
                        denyButtonText: 'Approve All & Finalize'
                    }).then(function(result) {
                        var modeVal = result.isConfirmed ? 'single' : (result.isDenied ? 'override' : '');
                        if (modeVal !== '') {
                            var inp = approvalFormEl.querySelector('input[name="approve_mode"]');
                            if (!inp) { inp = document.createElement('input'); inp.type = 'hidden'; inp.name = 'approve_mode'; approvalFormEl.appendChild(inp); }
                            inp.value = modeVal;
                            approvalFormEl.setAttribute('data-confirmed-override', '1');
                            approvalFormEl.submit();
                        }
                    });
                } else if (confirm("Pending " + overrideStage + "'s Approval. Are you ready to approve all bypassed stages and complete final Management approval?")) {
                    approvalFormEl.setAttribute('data-confirmed-override', '1');
                    approvalFormEl.submit();
                }
            }
        });
    }
});

function submitDetailInlineApproval(id, overrideStage) {
    document.getElementById('detailApprovalStatus_' + id).value = 'approved';
    var form = document.getElementById('detailInlineApprovalForm');
    if (!form) return;
    if (overrideStage && overrideStage !== '') {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Pending ' + overrideStage + "'s Approval",
                html: 'This purchase request is pending for <strong>' + overrideStage + "'s</strong> approval. Choose whether to approve <strong>only " + overrideStage + '</strong> and forward to the next stage, or override all stages &amp; finalize now.',
                icon: 'warning',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonColor: '#0d6efd',
                denyButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Approve Only ' + overrideStage,
                denyButtonText: 'Approve All & Finalize'
            }).then(function(result) {
                var modeVal = result.isConfirmed ? 'single' : (result.isDenied ? 'override' : '');
                if (modeVal !== '') {
                    var inp = form.querySelector('input[name="approve_mode"]');
                    if (!inp) { inp = document.createElement('input'); inp.type = 'hidden'; inp.name = 'approve_mode'; form.appendChild(inp); }
                    inp.value = modeVal;
                    form.submit();
                }
            });
            return;
        } else if (!confirm("Pending " + overrideStage + "'s Approval. Are you ready to approve all bypassed stages and complete final Management approval?")) {
            return;
        }
    }
    form.submit();
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
    if (status === 'waiting') return '<i class="fa-solid fa-clock"></i>';
    return '<i class="fa-solid fa-hourglass-start"></i>';
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    var d = new Date(dateStr);
    return d.toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'numeric'}) + ', ' + d.toLocaleTimeString('en-US', {hour:'2-digit', minute:'2-digit'});
}

function getNextStep(data) {
    var isOver10K = parseFloat(data.total_price || 0) > 10000.01;
    var isPaidOrDone = (data.payment_status === 'paid' || data.overall_status === 'paid' || data.overall_status === 'purchased');
    var isOpHeadPostQcApproved = (data.op_head_status === 'approved') || (!isOver10K && data.mgmt_status === 'approved') || isPaidOrDone;
    var isAhApproved = (data.accounts_head_status === 'approved') || isPaidOrDone;
    var isMgmtApproved = (data.mgmt_status === 'approved') || isPaidOrDone;

    var isRejected = (data.overall_status === 'rejected' || data.dept_head_status === 'rejected' || data.it_head_status === 'rejected' || data.operations_status === 'rejected' || data.purchase_head_status === 'rejected' || data.qc_audit_status === 'rejected' || data.mgmt_status === 'rejected' || data.op_head_status === 'rejected' || data.accounts_head_status === 'rejected');
    
    if (isRejected) {
        var rejBy = '';
        var rejStep = '';
        var rejAt = '';
        var rejNote = '';
        if (data.dept_head_status === 'rejected') {
            rejStep = 'Department Head';
            rejBy = data.dept_head_by_name || 'Dept Head';
            rejAt = data.dept_head_at;
            rejNote = data.dept_head_note;
        } else if (data.it_head_status === 'rejected') {
            rejStep = 'IT Head';
            rejBy = data.it_head_by_name || 'IT Head';
            rejAt = data.it_head_at;
            rejNote = data.it_head_note;
        } else if (data.operations_status === 'rejected') {
            rejStep = 'Admin & Operation Head';
            rejBy = data.operations_by_name || 'Operation Head';
            rejAt = data.operations_at;
            rejNote = data.operations_note;
        } else if (data.purchase_head_status === 'rejected') {
            rejStep = 'Purchase Head';
            rejBy = data.purchase_head_by_name || 'Purchase Head';
            rejAt = data.purchase_head_at;
            rejNote = data.purchase_head_note;
        } else if (data.qc_audit_status === 'rejected') {
            rejStep = 'QC & Audit';
            rejBy = data.qc_audit_by_name || 'QC & Audit';
            rejAt = data.qc_audit_at;
            rejNote = data.qc_audit_note;
        } else if (data.op_head_status === 'rejected') {
            rejStep = 'Operation Head';
            rejBy = data.op_head_by_name || 'Operation Head';
            rejAt = data.op_head_at;
            rejNote = data.op_head_note;
        } else if (data.accounts_head_status === 'rejected') {
            rejStep = 'Accounts Head';
            rejBy = data.accounts_head_by_name || 'Accounts Head';
            rejAt = data.accounts_head_at;
            rejNote = data.accounts_head_note;
        } else if (data.mgmt_status === 'rejected') {
            rejStep = 'Management / DCEO';
            rejBy = data.mgmt_by_name || 'Management';
            rejAt = data.mgmt_at;
            rejNote = data.mgmt_note;
        } else {
            rejBy = data.mgmt_by_name || data.operations_by_name || '';
            rejAt = data.updated_at || '';
        }

        var mainText = 'Purchase Request Rejected';
        var subText = '';
        if (rejBy) {
            subText = 'Rejected by ' + rejBy + (rejStep ? ' (' + rejStep + ')' : '');
        }
        if (rejAt) {
            subText += (subText ? ' • ' : '') + formatDate(rejAt);
        }
        return {
            text: mainText,
            subtext: subText,
            note: rejNote || '',
            color: 'danger',
            icon: 'fa-circle-xmark'
        };
    }
    if (data.overall_status === 'purchased') return { text: 'Completed — Payment processed & assets registered', color: 'success', icon: 'fa-circle-check' };
    if (data.dept_head_status === 'pending') return { text: 'Waiting for Department Head / Reporting Boss approval', color: 'info', icon: 'fa-hourglass-half' };
    if (data.it_head_status === 'pending') return { text: 'Waiting for IT Head approval', color: 'info', icon: 'fa-hourglass-half' };
    if (data.operations_status === 'pending') return { text: 'Waiting for Admin & Operation (Role 20 / 32) initial review', color: 'secondary', icon: 'fa-hourglass-half' };
    if (data.purchase_head_status === 'pending') return { text: 'Waiting for Purchase Head (Role 17, 31) approval', color: 'primary', icon: 'fa-hourglass-half' };
    if (data.qc_audit_status === 'pending') return { text: 'Waiting for QC & Internal Audit review', color: 'info', icon: 'fa-hourglass-half' };
    if (!isOpHeadPostQcApproved) return { text: 'Waiting for Operation Head (Role 20 / 32) approval', color: 'warning', icon: 'fa-hourglass-half' };
    if (!isAhApproved) return { text: 'Waiting for Accounts Head (Role 30) verification & approval', color: 'warning', icon: 'fa-hourglass-half' };
    if (isOver10K && !isMgmtApproved) return { text: 'Waiting for DCEO / Management final approval (> TK10,000)', color: 'warning', icon: 'fa-hourglass-half' };
    if (data.payment_status === 'unpaid') return { text: 'Approved — Waiting for Accounts to process payment', color: 'success', icon: 'fa-money-bill-wave' };
    return { text: 'Payment Disbursed — Waiting for Purchase & Asset Confirmation', color: 'primary', icon: 'fa-box-open' };
}

function getStepTitle(step, data) {
    if (step === 'mgmt') {
        var isOver1L = data && (parseFloat(data.total_price) > 10000.01);
        var opDone = data && (String(data.op_head_status).toLowerCase() === 'approved');
        if (isOver1L) {
            if (!opDone) return 'Operation Head Approval (Post-QC)';
            return 'DCEO / Management Final Approval (> TK10,000)';
        }
        return 'Operation Head Approval (≤ TK10,000)';
    }
    var titles = {
        'dept_head': 'Department Head / Reporting Boss Approval',
        'operations': 'Admin & Operation Initial Review (Role 20 / 32)',
        'purchase_head': 'Purchase Head Approval (Role 17, 31)',
        'qc_audit': 'QC & Internal Audit Review',
        'mgmt': 'DCEO / Management Approval'
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
    var isOver10K = parseFloat(data.total_price || 0) > 10000.01;
    var isPaidOrDone = (data.payment_status === 'paid' || data.overall_status === 'paid' || data.overall_status === 'purchased');

    // Post-QC Operation Head resolution (for <= 10K, Operation Head approval may have populated mgmt_status/mgmt_by_name)
    var isOpHeadPostQcApproved = (data.op_head_status === 'approved') || (!isOver10K && data.mgmt_status === 'approved') || isPaidOrDone;
    var isOpHeadPostQcRejected = (data.op_head_status === 'rejected') || (!isOver10K && data.mgmt_status === 'rejected');
    var opPostQcName = data.op_head_by_name || (!isOver10K ? data.mgmt_by_name : '') || (isOpHeadPostQcApproved ? 'Admin & Operation Head' : '');
    var opPostQcAt = data.op_head_at || (!isOver10K ? data.mgmt_at : '') || '';
    var opPostQcNote = data.op_head_note || (!isOver10K ? data.mgmt_note : '') || '';

    var isAhApproved = (data.accounts_head_status === 'approved') || isPaidOrDone;
    var isAhRejected = (data.accounts_head_status === 'rejected');
    var ahName = data.accounts_head_by_name || (isAhApproved ? 'Accounts Head' : '');
    var ahAt = data.accounts_head_at || '';
    var ahNote = data.accounts_head_note || '';

    // Management / DCEO step: shown for > 10,000, or if a distinct Management user approved/rejected
    var showMgmtStep = isOver10K || (data.op_head_status === 'approved' && (data.mgmt_status === 'approved' || data.mgmt_status === 'rejected'));
    var isMgmtApproved = (data.mgmt_status === 'approved') || (isOver10K && isPaidOrDone);
    var isMgmtRejected = (data.mgmt_status === 'rejected');

    var dhClass = getStepClass(data.dept_head_status);
    var itClass = getStepClass(data.it_head_status);
    var opClass = getStepClass(data.operations_status);
    var phClass = getStepClass(data.purchase_head_status);
    var qcClass = getStepClass(data.qc_audit_status);
    var opPostQcClass = isOpHeadPostQcApproved ? 'approved' : (isOpHeadPostQcRejected ? 'rejected' : 'pending');
    var ahClass = isAhApproved ? 'approved' : (isAhRejected ? 'rejected' : 'pending');
    var mgClass = isMgmtApproved ? 'approved' : (isMgmtRejected ? 'rejected' : 'pending');
    var pyClass = isPaidOrDone ? 'approved' : 'pending';
    var pcClass = data.overall_status === 'purchased' ? 'approved' : 'pending';

    // Highlight current waiting step in the sequence
    if (data.overall_status !== 'rejected' && data.overall_status !== 'purchased') {
        if (data.dept_head_status === 'pending') dhClass = 'waiting';
        else if (data.it_head_status === 'pending') itClass = 'waiting';
        else if (data.operations_status === 'pending') opClass = 'waiting';
        else if (data.purchase_head_status === 'pending') phClass = 'waiting';
        else if (data.qc_audit_status === 'pending') qcClass = 'waiting';
        else if (!isOpHeadPostQcApproved) opPostQcClass = 'waiting';
        else if (!isAhApproved) ahClass = 'waiting';
        else if (showMgmtStep && !isMgmtApproved) mgClass = 'waiting';
        else if (!isPaidOrDone) pyClass = 'waiting';
        else if (data.overall_status !== 'purchased') pcClass = 'waiting';
    }

    function buildStepBadge(cls, customText) {
        if (cls === 'approved') return `<span class="badge bg-success-subtle text-success border border-success-subtle fs-10">${customText || 'Approved'}</span>`;
        if (cls === 'rejected') return `<span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-10">${customText || 'Rejected'}</span>`;
        if (cls === 'waiting') return `<span class="badge bg-warning-subtle text-dark border border-warning-subtle fs-10">${customText || 'Awaiting Action'}</span>`;
        return `<span class="badge bg-light text-muted border fs-10">${customText || 'Pending'}</span>`;
    }

    var body = document.getElementById('detailModalBody');
    body.innerHTML = `
        <!-- Next Step Banner -->
        <div class="next-step-banner bg-${nextStep.color}-transparent mb-3 ${nextStep.color === 'danger' ? 'border border-danger-subtle' : ''}">
            <i class="fa-solid ${nextStep.icon} text-${nextStep.color} fs-16"></i>
            <div>
                <div class="fw-bold fs-13 text-${nextStep.color}">${nextStep.text}</div>
                ${nextStep.subtext ? `<div class="fs-11 text-muted mt-1">${nextStep.subtext}</div>` : ''}
                ${nextStep.note ? `<div class="fs-11 text-danger mt-1 fst-italic"><i class="fa-solid fa-quote-left me-1"></i>"${nextStep.note}"</div>` : ''}
            </div>
        </div>

        <div class="row g-4 align-items-start">
            <!-- LEFT COLUMN: Request Info + Documents + Notes -->
            <div class="col-lg-6">
                <div class="detail-info-card">
                    <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-3">
                        <span class="fw-bold fs-13 text-dark"><i class="fa-solid fa-circle-info text-primary me-1.5"></i>Request Information</span>
                        <span class="badge ${isOver10K ? 'bg-warning-subtle text-dark border border-warning-subtle' : 'bg-info-subtle text-info border border-info-subtle'} fs-10">
                            ${isOver10K ? '> TK10,000 Flow (DCEO / Mgmt Required)' : '≤ TK10,000 Flow'}
                        </span>
                    </div>
                    <div class="row ">
                        <div class="col-sm-6">
                            <div class="detail-label">Project</div>
                            <div class="detail-value"><i class="fa-solid fa-building text-primary me-1 fs-11"></i>${data.project_name || '—'}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Location</div>
                            <div class="detail-value"><i class="fa-solid fa-location-dot text-info me-1 fs-11"></i>${data.branch_name || data.asset_location || '—'}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Requested By</div>
                            <div class="detail-value"><i class="fa-solid fa-user text-secondary me-1 fs-11"></i>${data.requested_by_name || '—'}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Assign To</div>
                            <div class="detail-value"><i class="fa-solid fa-user-check text-success me-1 fs-11"></i>${data.assigned_to_name || '—'}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Vendor</div>
                            <div class="detail-value"><i class="fa-solid fa-store text-warning me-1 fs-11"></i>${data.vendor_name || '—'}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Item & Category</div>
                            <div class="detail-value">
                                ${data.item_name || '—'}
                                ${data.category_name ? `<br><small class="text-muted fs-10">${data.category_name}</small>` : ''}
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Quantity</div>
                            <div class="detail-value">${data.quantity} pcs</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Total Amount</div>
                            <div class="detail-value text-success fs-15 fw-bold">TK ${parseFloat(data.total_price || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                        </div>
                        ${isPaidOrDone ? `
                        <div class="col-sm-6">
                            <div class="detail-label text-success">Paid Amount</div>
                            <div class="detail-value text-success fw-bold">TK ${parseFloat(data.paid_amount || data.total_price || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label text-muted">Payment Method</div>
                            <div class="detail-value text-muted"><span class="badge bg-light text-dark border">${data.payment_method || 'Cash'}</span></div>
                        </div>
                        ` : ''}
                        ${parseFloat(data.return_amount || 0) > 0 ? `
                        <div class="col-sm-6">
                            <div class="detail-label text-danger">Returned Bal.</div>
                            <div class="detail-value text-danger">TK ${parseFloat(data.return_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label text-primary">Spent Amount</div>
                            <div class="detail-value text-primary">TK ${(parseFloat(data.paid_amount || 0) - parseFloat(data.return_amount || 0)).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                        </div>
                        ` : ''}
                        <div class="col-12">
                            <div class="detail-label">Justification</div>
                            <div class="bg-white border rounded-2 p-2.5 px-3 fs-12 mt-1 lh-base">${data.justification || 'No justification provided.'}</div>
                        </div>
                    </div>
                </div>

                <!-- Approval Notes (if any) -->
                ${(data.dept_head_note || data.it_head_note || data.operations_note || data.op_head_note || data.purchase_head_note || data.qc_audit_note || data.mgmt_note || data.accounts_head_note || data.purchase_note || data.return_note) ? `
                <div class="detail-info-card mt-3">
                    <div class="detail-label mb-2">APPROVAL / REJECTION NOTES</div>
                    <div class="d-flex flex-column gap-1.5 lh-base">
                        ${data.dept_head_note ? '<div class="fs-12"><strong class="text-info">Dept Head:</strong> ' + data.dept_head_note + '</div>' : ''}
                        ${data.it_head_note ? '<div class="fs-12"><strong class="text-info">IT Head:</strong> ' + data.it_head_note + '</div>' : ''}
                        ${data.operations_note ? '<div class="fs-12"><strong class="text-secondary">Admin & Operation (Initial):</strong> ' + data.operations_note + '</div>' : ''}
                        ${data.purchase_head_note ? '<div class="fs-12"><strong class="text-primary">Purchase Head:</strong> ' + data.purchase_head_note + '</div>' : ''}
                        ${data.qc_audit_note ? '<div class="fs-12"><strong class="text-info">QC & Audit:</strong> ' + data.qc_audit_note + '</div>' : ''}
                        ${opPostQcNote ? '<div class="fs-12"><strong class="text-primary">Operation Head:</strong> ' + opPostQcNote + '</div>' : ''}
                        ${ahNote ? '<div class="fs-12"><strong class="text-dark">Accounts Head:</strong> ' + ahNote + '</div>' : ''}
                        ${(showMgmtStep && data.mgmt_note) ? '<div class="fs-12"><strong class="text-warning">DCEO / Management:</strong> ' + data.mgmt_note + '</div>' : ''}
                        ${data.purchase_note ? '<div class="fs-12"><strong class="text-success">Operations / Purchaser:</strong> ' + data.purchase_note + '</div>' : ''}
                        ${data.return_note ? '<div class="fs-12"><strong class="text-danger">Return Note:</strong> ' + data.return_note + '</div>' : ''}
                    </div>
                </div>
                ` : ''}

                <!-- Documents & Vouchers -->
                ${(data.attachment || data.payment_attachment || data.purchase_document || data.purchase_head_document || isPaidOrDone) ? `
                <div class="detail-info-card mt-3">
                    <div class="detail-label mb-2">SUBMITTED DOCUMENTS & VOUCHERS</div>
                    <div class="d-flex flex-wrap gap-2">
                        ${isPaidOrDone ? '<a href="<?= $GLOBALS['base_url'] ?>/expense-adjustments/create?parent_type=extra_purchase&cashout_request_id=' + data.id + '&project_id=' + (data.project_id || '') + '" class="btn btn-sm btn-warning shadow-sm"><i class="fa-solid fa-sliders me-1"></i> Settlement / Adjustment</a>' : ''}
                        ${isPaidOrDone ? '<a href="<?= $GLOBALS['base_url'] ?>/print-debit-voucher?module=project_extra_assets&id=' + data.id + '" target="_blank" class="btn btn-sm btn-primary shadow-sm"><i class="fa-solid fa-file-invoice-dollar me-1"></i> Print Debit Voucher</a>' : ''}
                        ${data.attachment ? '<a href="<?= $GLOBALS['base_url'] ?>/' + data.attachment + '" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-file-pdf me-1"></i> Request Doc</a>' : ''}
                        ${data.purchase_head_document ? '<a href="<?= $GLOBALS['base_url'] ?>/' + data.purchase_head_document + '" target="_blank" class="btn btn-sm btn-outline-info"><i class="fa-solid fa-file-invoice me-1"></i> Purchase Head Doc</a>' : ''}
                        ${data.payment_attachment ? '<a href="<?= $GLOBALS['base_url'] ?>/' + data.payment_attachment + '" target="_blank" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-receipt me-1"></i> Payment Receipt</a>' : ''}
                        ${data.purchase_document ? '<a href="<?= $GLOBALS['base_url'] ?>/' + data.purchase_document + '" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-file-invoice me-1"></i> Purchase Document</a>' : ''}
                    </div>
                </div>
                ` : ''}
            </div>

            <!-- RIGHT COLUMN: Vertical Approval & Payment Timeline -->
            <div class="col-lg-6">
                <div class="ep-timeline-card">
                    <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-3">
                        <span class="fw-bold fs-13 text-dark"><i class="fa-solid fa-timeline text-primary me-1.5"></i>Approval & Payment Timeline</span>
                        <span class="text-muted fs-11">Sequential Verification Flow</span>
                    </div>

                    <!-- 1. Requested -->
                    <div class="ep-timeline-item ep-step-approved">
                        <div class="ep-timeline-circle approved"><i class="fa-solid fa-paper-plane"></i></div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <span class="fw-bold fs-13 text-dark">Requisition Submitted</span>
                                ${buildStepBadge('approved', 'Submitted')}
                            </div>
                            <div class="fs-12 text-muted mt-0.5">
                                Submitted by <strong>${data.requested_by_name || '—'}</strong>
                                ${data.created_at ? ' • ' + formatDate(data.created_at) : ''}
                            </div>
                        </div>
                    </div>

                    <!-- 2. Department Head -->
                    ${data.dept_head_status !== 'not_required' ? `
                    <div class="ep-timeline-item ${dhClass === 'approved' ? 'ep-step-approved' : ''}">
                        <div class="ep-timeline-circle ${dhClass}">${getStepIcon(dhClass)}</div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <span class="fw-bold fs-13 text-dark">Department Head Approval</span>
                                ${buildStepBadge(dhClass)}
                            </div>
                            <div class="fs-12 text-muted mt-0.5">
                                ${data.dept_head_by_name ? `By <strong>${data.dept_head_by_name}</strong>` : (data.reporting_boss_name ? `Designated: <strong>${data.reporting_boss_name}</strong>` : 'Awaiting Department Head / Reporting Boss')}
                                ${data.dept_head_at ? ' • ' + formatDate(data.dept_head_at) : ''}
                            </div>
                        </div>
                    </div>
                    ` : ''}

                    <!-- 3. IT Head (if required) -->
                    ${data.it_head_status !== 'not_required' ? `
                    <div class="ep-timeline-item ${itClass === 'approved' ? 'ep-step-approved' : ''}">
                        <div class="ep-timeline-circle ${itClass}">${getStepIcon(itClass)}</div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <span class="fw-bold fs-13 text-dark">IT Head Review</span>
                                ${buildStepBadge(itClass)}
                            </div>
                            <div class="fs-12 text-muted mt-0.5">
                                ${data.it_head_by_name ? `By <strong>${data.it_head_by_name}</strong>` : 'Awaiting IT Head verification'}
                                ${data.it_head_at ? ' • ' + formatDate(data.it_head_at) : ''}
                            </div>
                        </div>
                    </div>
                    ` : ''}

                    <!-- 4. Admin & Operation (Initial Review) -->
                    <div class="ep-timeline-item ${opClass === 'approved' ? 'ep-step-approved' : ''}">
                        <div class="ep-timeline-circle ${opClass}">${getStepIcon(opClass)}</div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <span class="fw-bold fs-13 text-dark">Admin & Operation Initial Review</span>
                                ${buildStepBadge(opClass)}
                            </div>
                            <div class="fs-12 text-muted mt-0.5">
                                ${data.operations_by_name ? `Verified by <strong>${data.operations_by_name}</strong>` : 'Awaiting Admin & Operation (Role 20 / 32) initial check'}
                                ${data.operations_at ? ' • ' + formatDate(data.operations_at) : ''}
                            </div>
                        </div>
                    </div>

                    <!-- 5. Purchase Head -->
                    <div class="ep-timeline-item ${phClass === 'approved' ? 'ep-step-approved' : ''}">
                        <div class="ep-timeline-circle ${phClass}">${getStepIcon(phClass)}</div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <span class="fw-bold fs-13 text-dark">Purchase Head Approval</span>
                                ${buildStepBadge(phClass)}
                            </div>
                            <div class="fs-12 text-muted mt-0.5">
                                ${data.purchase_head_by_name ? `Approved by <strong>${data.purchase_head_by_name}</strong>` : 'Awaiting Purchase Head (Role 17 / 31) quotation & approval'}
                                ${data.purchase_head_at ? ' • ' + formatDate(data.purchase_head_at) : ''}
                            </div>
                        </div>
                    </div>

                    <!-- 6. QC & Internal Audit -->
                    <div class="ep-timeline-item ${qcClass === 'approved' ? 'ep-step-approved' : ''}">
                        <div class="ep-timeline-circle ${qcClass}">${getStepIcon(qcClass)}</div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <span class="fw-bold fs-13 text-dark">QC & Internal Audit Review</span>
                                ${buildStepBadge(qcClass)}
                            </div>
                            <div class="fs-12 text-muted mt-0.5">
                                ${data.qc_audit_by_name ? `Verified by <strong>${data.qc_audit_by_name}</strong>` : 'Awaiting QC & Internal Audit verification'}
                                ${data.qc_audit_at ? ' • ' + formatDate(data.qc_audit_at) : ''}
                            </div>
                        </div>
                    </div>

                    <!-- 7. Operation Head Approval (Post-QC) -->
                    <div class="ep-timeline-item ${opPostQcClass === 'approved' ? 'ep-step-approved' : ''}">
                        <div class="ep-timeline-circle ${opPostQcClass}">${getStepIcon(opPostQcClass)}</div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <span class="fw-bold fs-13 text-dark">Operation Head Approval</span>
                                ${buildStepBadge(opPostQcClass)}
                            </div>
                            <div class="fs-12 text-muted mt-0.5">
                                ${isOpHeadPostQcApproved
                                    ? `Approved by <strong>${opPostQcName}</strong>${opPostQcAt ? ' • ' + formatDate(opPostQcAt) : ''}`
                                    : (isOpHeadPostQcRejected
                                        ? `Rejected by <strong>${opPostQcName || 'Operation Head'}</strong>${opPostQcAt ? ' • ' + formatDate(opPostQcAt) : ''}`
                                        : 'Awaiting Head of Operation (Role 20 / 32) post-QC approval')}
                            </div>
                        </div>
                    </div>

                    <!-- 8. Accounts Head Approval -->
                    <div class="ep-timeline-item ${ahClass === 'approved' ? 'ep-step-approved' : ''}">
                        <div class="ep-timeline-circle ${ahClass}">${getStepIcon(ahClass)}</div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <span class="fw-bold fs-13 text-dark">Accounts Head Approval</span>
                                ${buildStepBadge(ahClass)}
                            </div>
                            <div class="fs-12 text-muted mt-0.5">
                                ${isAhApproved
                                    ? `Verified & Approved by <strong>${ahName}</strong>${ahAt ? ' • ' + formatDate(ahAt) : ''}`
                                    : (isAhRejected
                                        ? `Rejected by <strong>${ahName || 'Accounts Head'}</strong>${ahAt ? ' • ' + formatDate(ahAt) : ''}`
                                        : 'Awaiting Accounts Head (Role 30) verification & sign-off')}
                            </div>
                        </div>
                    </div>

                    <!-- 9. DCEO / Management Final Approval (Shown if amount > 10,000 or Management acted) -->
                    ${showMgmtStep ? `
                    <div class="ep-timeline-item ${mgClass === 'approved' ? 'ep-step-approved' : ''}">
                        <div class="ep-timeline-circle ${mgClass}">${getStepIcon(mgClass)}</div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <span class="fw-bold fs-13 text-dark">DCEO / Management Approval <span class="text-muted fw-normal fs-11">(&gt; TK10,000)</span></span>
                                ${buildStepBadge(mgClass)}
                            </div>
                            <div class="fs-12 text-muted mt-0.5">
                                ${isMgmtApproved
                                    ? `Approved by <strong>${data.mgmt_by_name || 'Management'}</strong>${data.mgmt_at ? ' • ' + formatDate(data.mgmt_at) : ''}`
                                    : (isMgmtRejected
                                        ? `Rejected by <strong>${data.mgmt_by_name || 'Management'}</strong>${data.mgmt_at ? ' • ' + formatDate(data.mgmt_at) : ''}`
                                        : 'Awaiting DCEO / Management (Role 1 / 3) final approval')}
                            </div>
                        </div>
                    </div>
                    ` : ''}

                    <!-- 10. Accounts Payment -->
                    <div class="ep-timeline-item ${pyClass === 'approved' ? 'ep-step-approved' : ''}">
                        <div class="ep-timeline-circle ${pyClass}">${isPaidOrDone ? '<i class="fa-solid fa-bangladeshi-taka-sign"></i>' : getStepIcon(pyClass)}</div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <span class="fw-bold fs-13 text-dark">Accounts Disbursement</span>
                                ${buildStepBadge(pyClass, isPaidOrDone ? 'Paid' : null)}
                            </div>
                            <div class="fs-12 text-muted mt-0.5">
                                ${isPaidOrDone
                                    ? `Disbursed by <strong>${data.paid_by_name || 'Accounts'}</strong>${data.paid_at ? ' • ' + formatDate(data.paid_at) : ''}`
                                    : 'Awaiting payment disbursement by Accounts'}
                            </div>
                        </div>
                    </div>

                    <!-- 11. Asset Purchased -->
                    <div class="ep-timeline-item ${pcClass === 'approved' ? 'ep-step-approved' : ''}">
                        <div class="ep-timeline-circle ${pcClass}">${data.overall_status === 'purchased' ? '<i class="fa-solid fa-box-open"></i>' : getStepIcon(pcClass)}</div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <span class="fw-bold fs-13 text-dark">Asset Purchased & Registered</span>
                                ${buildStepBadge(pcClass, data.overall_status === 'purchased' ? 'Purchased' : null)}
                            </div>
                            <div class="fs-12 text-muted mt-0.5">
                                ${data.overall_status === 'purchased'
                                    ? `Confirmed by <strong>${data.purchased_by_name || 'Operations'}</strong>${data.purchase_confirmed_at ? ' • ' + formatDate(data.purchase_confirmed_at) : ''}`
                                    : 'Awaiting purchase confirmation & asset registration'}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action / Approval Section if User Can Approve -->
        ${data.can_approve ? `
        <div class="mt-4 p-3 border border-success rounded-3 bg-light shadow-sm">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fw-bold fs-13 text-success">
                    <i class="fa-solid fa-shield-check me-1"></i> Ready for Approval: ${getStepTitle(data.approval_step, data)}
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
                    <button type="button" class="btn btn-sm btn-success px-4 fw-semibold" onclick="submitDetailInlineApproval(${data.id}, '${(data.override_warning_stage || '').replace(/'/g, "\\'")}')">
                        <i class="fa-solid fa-check me-1"></i> Approve Request
                    </button>
                </div>
            </form>
        </div>
        ` : ''}

        <!-- Action: Accounts Head Verification inside Detail Modal -->
        ${(data.can_approve_ah && data.approval_request_id) ? `
        <div class="mt-4 p-3 border border-primary rounded-3 bg-primary-subtle shadow-sm d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <span class="fw-bold fs-13 text-primary d-block">
                    <i class="fa-solid fa-stamp me-1"></i> Ready for Accounts Head Verification & Approval
                </span>
                <small class="text-muted">Operation Head has approved this request. Verify and approve as Accounts Head (Role 30)${isOver10K ? ' to forward to DCEO / Management for final sign-off' : ' to authorize payment disbursement'}.</small>
            </div>
            <button type="button" class="btn btn-primary btn-sm px-3 fw-bold shadow-sm" onclick="bootstrap.Modal.getInstance(document.getElementById('detailModal'))?.hide(); triggerAhApprove(${data.approval_request_id});">
                <i class="fa-solid fa-stamp me-1"></i> Verify & Approve (Accounts Head)
            </button>
        </div>
        ` : ''}

        <!-- Action: Accounts Disbursement / Process Payment inside Detail Modal -->
        ${data.can_pay ? `
        <div class="mt-4 p-3 border border-success rounded-3 bg-success-subtle shadow-sm d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <span class="fw-bold fs-13 text-success d-block">
                    <i class="fa-solid fa-money-bill-wave me-1"></i> Ready for Payment Disbursement
                </span>
                <small class="text-muted">All required approvals are complete. Process and disburse payment (TK ${parseFloat(data.total_price || 0).toLocaleString(undefined, {minimumFractionDigits:2})}) for this procurement request.</small>
            </div>
            <button type="button" class="btn btn-success btn-sm px-4 fw-bold shadow-sm" onclick="closeDetailModalAndPay(${data.id}, ${parseFloat(data.total_price || 0)})">
                <i class="fa-solid fa-money-bill-wave me-1"></i> Disburse / Process Payment
            </button>
        </div>
        ` : (data.is_waiting_ah && !data.can_approve_ah && data.payment_status === 'unpaid' ? `
        <div class="mt-4 p-3 border border-warning rounded-3 bg-warning-subtle shadow-sm d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <span class="fw-bold fs-13 text-dark d-block">
                    <i class="fa-solid fa-lock me-1 text-warning"></i> Payment Locked — Awaiting Accounts Head Verification
                </span>
                <small class="text-muted">Accounts Head (Role 30) verification is required before payment can be disbursed.</small>
            </div>
            <span class="badge bg-warning text-dark px-2 py-1"><i class="fa-solid fa-hourglass-half me-1"></i>Pending AH</span>
        </div>
        ` : '')}

        <!-- Action: Confirm Purchase Received & Register in Asset Tracker (When Paid) -->
        ${(data.payment_status === 'paid' && data.overall_status !== 'purchased') ? `
        <div class="mt-4 p-3 border border-primary rounded-3 bg-primary-subtle shadow-sm d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <span class="fw-bold fs-13 text-primary d-block">
                    <i class="fa-solid fa-box-check me-1"></i> Ready to Confirm Purchase & Add to Asset Tracker
                </span>
                <small class="text-muted">Payment has been completed. Confirm that items are purchased/received to register them into Product Master & Asset Tracker.</small>
            </div>
            <button type="button" class="btn btn-success btn-sm px-3 fw-bold shadow-sm" onclick="closeDetailModalAndConfirm(${data.id})">
                <i class="fa-solid fa-box-check me-1"></i> Confirm & Mark Purchased
            </button>
        </div>
        ` : ''}

        <!-- Action: Already Registered in Asset Tracker -->
        ${data.overall_status === 'purchased' ? `
        <div class="mt-4 p-3 border border-success rounded-3 bg-success-subtle shadow-sm d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <span class="fw-bold fs-13 text-success d-block">
                    <i class="fa-solid fa-circle-check me-1"></i> Purchased & Registered in Asset Tracker
                </span>
                <small class="text-muted">This asset has been auto-coded and capitalized under Fixed Assets in Financial Hub Balance Sheet.</small>
            </div>
            <a href="<?= $GLOBALS['base_url'] ?>/office-assets" class="btn btn-success btn-sm px-3 fw-semibold">
                <i class="fa-solid fa-boxes-stacked me-1"></i> View in Asset Register
            </a>
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

function closeDetailModalAndPay(id, amount) {
    var detailModalEl = document.getElementById('detailModal');
    if (detailModalEl) {
        var modal = bootstrap.Modal.getInstance(detailModalEl);
        if (modal) modal.hide();
    }
    setTimeout(function() {
        openPaymentModal(id, amount);
    }, 200);
}

function closeDetailModalAndConfirm(id) {
    var detailModalEl = document.getElementById('detailModal');
    if (detailModalEl) {
        var modal = bootstrap.Modal.getInstance(detailModalEl);
        if (modal) modal.hide();
    }
    var btn = document.querySelector(`button[data-id="${id}"]`);
    if (btn) {
        openConfirmPurchaseModal(btn);
    }
}

function openConfirmPurchaseModal(btn) {
    var data = null;
    try {
        if (typeof btn === 'object' && btn.getAttribute) {
            data = JSON.parse(btn.getAttribute('data-info'));
        } else if (typeof btn === 'object') {
            data = btn;
        }
    } catch(e) {
        console.error("Error parsing purchase data:", e);
    }
    if (!data) return;

    document.getElementById('confirmPurchaseForm').action = '<?= $GLOBALS['base_url'] ?>/asset-extra-purchases/confirm/' + data.id;
    document.getElementById('confirm_purchase_note').value = '';
    document.getElementById('confirm_purchase_document').value = '';

    // Auto fill Purchase Date from payment date or today
    var todayStr = new Date().toISOString().substring(0, 10);
    var pDate = data.paid_at ? data.paid_at.substring(0, 10) : todayStr;
    var dateInput = document.getElementById('confirm_purchase_date');
    if (dateInput) {
        dateInput.value = pDate;
    }

    // Populate Item Summary Card
    var summaryEl = document.getElementById('confirmPurchaseSummary');
    if (summaryEl) {
        var assignedText = data.assigned_to_name ? data.assigned_to_name : (data.requested_by_name ? data.requested_by_name : 'Unassigned');
        var branchText = data.asset_location ? data.asset_location : 'Default Office';
        var itemNameText = (data.item_name && data.item_name !== '0') ? data.item_name : (data.justification ? data.justification.substring(0, 50) : (data.category_name || 'Procurement Item'));
        summaryEl.innerHTML = `
            <div class="row g-2">
                <div class="col-6"><strong>Item:</strong> ${itemNameText} (${data.quantity} pcs)</div>
                <div class="col-6"><strong>Total Price:</strong> TK ${parseFloat(data.total_price || 0).toLocaleString(undefined, {minimumFractionDigits:2})}</div>
                <div class="col-6"><strong>Assign To:</strong> <span class="badge bg-primary-subtle text-primary">${assignedText}</span></div>
                <div class="col-6"><strong>Office Branch:</strong> <span class="badge bg-info-subtle text-info">${branchText}</span></div>
            </div>
        `;
    }
    
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
                    <p class="text-muted fs-12 mb-2">Confirm that the purchased items have been received. This will automatically generate a Product Code, register the assets in the Asset Tracker, and capitalize them in Financial Hub Balance Sheet.</p>
                    
                    <div class="alert alert-light border p-2 mb-3 fs-12" id="confirmPurchaseSummary">
                        <!-- Populated by JS -->
                    </div>

                    <div class="mb-3">
                        <label class="form-label fs-12 fw-semibold">Purchase Date <span class="text-danger">*</span></label>
                        <input type="date" name="purchase_date" id="confirm_purchase_date" class="form-control form-control-sm" required value="<?= date('Y-m-d') ?>">
                        <small class="text-muted fs-11">Date when the asset was purchased or received.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fs-12 fw-semibold">Upload Invoice / Voucher <span class="text-muted fs-11">(Invoice, Receipt, Challan etc.)</span></label>
                        <input type="file" name="purchase_document" id="confirm_purchase_document" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fs-12 fw-semibold">Returned Balance <span class="text-muted fs-11">(optional, if money was saved)</span></label>
                        <input type="number" name="return_amount" class="form-control form-control-sm" step="0.01" min="0" placeholder="0.00">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fs-12 fw-semibold">Return Note <span class="text-muted fs-11">(optional)</span></label>
                        <input type="text" name="return_note" class="form-control form-control-sm" placeholder="E.g., Vendor discount applied">
                    </div>
                    <div class="mb-0">
                        <label class="form-label fs-12 fw-semibold">Purchase Notes <span class="text-muted fs-11">(optional)</span></label>
                        <textarea name="purchase_note" id="confirm_purchase_note" class="form-control form-control-sm" rows="2" placeholder="Any remarks about the received items..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm fw-bold">
                        <i class="fa-solid fa-box-check me-1"></i> Confirm & Register Assets
                    </button>
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
                jQuery('#new_request_vendor_id').select2({
                    dropdownParent: jQuery('#newRequestModal'),
                    placeholder: "-- Optional: Select Vendor --",
                    allowClear: true,
                    width: '100%'
                });
            }
        });

        // Initialize DataTable for Office Procurement (#procurementTable)
        if (typeof jQuery.fn.DataTable !== 'undefined') {
            jQuery('#procurementTable').DataTable({
                destroy: true,
                responsive: true,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                pageLength: 10,
                order: [], // Preserve SQL order (pea.id DESC)
                columnDefs: [
                    { orderable: false, searchable: false, targets: 0 },  // # serial column
                    { orderable: false, searchable: false, className: 'text-nowrap', targets: -1 } // Actions column
                ],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Quick filter in table...",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    emptyTable: "No purchase requests found.",
                    paginate: {
                        first: '<i class="fa-solid fa-angles-left"></i>',
                        previous: '<i class="fa-solid fa-angle-left"></i>',
                        next: '<i class="fa-solid fa-angle-right"></i>',
                        last: '<i class="fa-solid fa-angles-right"></i>'
                    }
                },
                drawCallback: function(settings) {
                    var api = this.api();
                    var startIndex = api.context[0]._iDisplayStart;
                    api.column(0, {search:'applied', order:'applied'}).nodes().each(function(cell, i) {
                        cell.innerHTML = startIndex + i + 1;
                    });
                }
            });
        }
    }
});
</script>

<?php if (!empty($autoOpenRequest)): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    var autoData = <?= json_encode($autoOpenRequest) ?>;
    if (autoData) {
        setTimeout(function() {
            renderDetailModal(autoData);
        }, 250);
    }
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/../../layouts/accounts_head_approval_card.php'; ?>

<!-- Edit Office Procurement Request Modal (Before 1st Approval — List View) -->
<div class="modal custom-modal fade" id="editExtraPurchaseIndexModal" tabindex="-1" aria-labelledby="editExtraPurchaseIndexModalLabel" aria-hidden="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header pb-3 mb-2 border-bottom">
                <h5 class="modal-title fw-bold text-dark" id="editExtraPurchaseIndexModalLabel">
                    <i class="fa-solid fa-pen-to-square me-2 text-warning"></i> Edit Procurement Request <span id="edit_ep_title_id"></span>
                </h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body p-0">
                <form method="POST" action="" id="editExtraPurchaseIndexForm">
                    <input type="hidden" name="redirect_to" value="index">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Item / Service Name <span class="text-danger">*</span></label>
                        <input type="text" name="item_name" id="edit_ep_item_name" class="form-control" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Quantity <span class="text-danger">*</span></label>
                            <input type="number" min="1" name="quantity" id="edit_ep_qty" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Estimated Unit Price (৳) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="unit_price" id="edit_ep_unit_price" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Justification / Remarks</label>
                        <textarea name="justification" id="edit_ep_justification" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="modal-footer px-0 pb-0 pt-3 border-top d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">
                            <i class="fa-solid fa-save me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
(function() {
    function initEpIndexEditModal() {
        if (typeof jQuery === 'undefined' || typeof bootstrap === 'undefined') {
            setTimeout(initEpIndexEditModal, 50);
            return;
        }
        jQuery(function($) {
            if ($('#editExtraPurchaseIndexModal').length && $('#editExtraPurchaseIndexModal').parent().prop('tagName') !== 'BODY') {
                $('#editExtraPurchaseIndexModal').appendTo('body');
            }
            function populateEp(btn) {
                var raw = btn.attr('data-item');
                if (!raw) return;
                try {
                    var d = (typeof raw === 'object') ? raw : JSON.parse(raw);
                    $('#editExtraPurchaseIndexForm').attr('action', '<?= $GLOBALS['base_url'] ?>/asset-extra-purchases/update/' + (d.id || ''));
                    $('#edit_ep_title_id').text('#' + (d.id || ''));
                    $('#edit_ep_item_name').val(d.item_name || '');
                    $('#edit_ep_qty').val(d.quantity || 1);
                    $('#edit_ep_unit_price').val(d.unit_price || 0);
                    $('#edit_ep_justification').val(d.justification || '');
                } catch (e) {}
            }
            $('#editExtraPurchaseIndexModal').on('show.bs.modal', function(e) {
                if (e.relatedTarget) populateEp($(e.relatedTarget));
            });
            $(document).on('click', '.btn-edit-extra-purchase', function() {
                populateEp($(this));
            });
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEpIndexEditModal);
    } else {
        initEpIndexEditModal();
    }
})();
</script>


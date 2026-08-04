<?php
$sessionUserId = $sessionUserId ?? (int)($_SESSION['user']['id'] ?? 0);
$sessionRoleId = $sessionRoleId ?? (int)($_SESSION['user']['role_id'] ?? 0);
$base          = $GLOBALS['base_url'];

$isOwner    = ($req['requested_by'] == $sessionUserId);
$isAccounts = in_array($sessionRoleId, [1, 6, 30]);
$isAdmin    = in_array($sessionRoleId, [1, 3]);
$canApprove = in_array($sessionRoleId, [1, 3, 20]);
$isVerified = !empty($req['accounts_settlement_verified']);

$statusColors = [
    'pending_dept' => 'bg-warning text-dark',
    'pending'      => 'bg-info text-white',
    'approved'     => 'bg-primary text-white',
    'paid'         => 'bg-success text-white',
    'rejected'     => 'bg-danger text-white'
];
$statusClass = $statusColors[$req['status']] ?? 'bg-secondary text-white';
$statusLabel = ($req['status'] === 'pending_dept') ? 'Pending Dept Head' : (($req['status'] === 'pending') ? 'Pending Management' : ucfirst($req['status']));

$settleBadgeMap = [
    'pending'         => ['bg-warning-transparent text-warning border border-warning', 'Pending Settlement'],
    'settled'         => ['bg-success-transparent text-success border border-success', 'Settled'],
    'extra_requested' => ['bg-danger-transparent text-danger border border-danger', 'Extra Fund Requested'],
];
[$settleClass, $settleLabel] = $settleBadgeMap[$req['settlement_status'] ?? 'pending'] ?? ['bg-secondary-transparent text-secondary', '---'];

$spentPercent = ($req['amount'] > 0) ? min(100, round(($totalSpent / $req['amount']) * 100, 1)) : 0;
?>
<div class="container-fluid py-3">
    <!-- PAGE BREADCRUMB & HEADER -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 page-header-breadcrumb">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary-transparent text-primary fs-12 fw-bold px-2 py-1">REQ-<?php echo $req['id']; ?></span>
                <span class="badge <?php echo $statusClass; ?> fs-12 px-2 py-1"><?php echo $statusLabel; ?></span>
                <span class="badge <?php echo $settleClass; ?> fs-12 px-2 py-1"><?php echo $settleLabel; ?></span>
            </div>
          
            <nav><ol class="breadcrumb mb-0 mt-1 fs-12">
                <li class="breadcrumb-item"><a href="<?php echo $base; ?>/fund-requisitions" class="text-primary text-decoration-none">Fund Requisitions</a></li>
                <li class="breadcrumb-item active">REQ-<?php echo $req['id']; ?></li>
            </ol></nav>
        </div>
        
        <div class="d-flex flex-wrap gap-2 no-print align-items-center">
            <?php 
            $isRequisitionApproved = (
                ($req['dept_head_status'] ?? '') === 'approved' ||
                !empty($req['dept_head_approved_by']) ||
                in_array($req['status'] ?? '', ['approved', 'paid']) ||
                !empty($req['approved_by']) ||
                !empty($req['paid_by'])
            );
            $canDeleteReq = !$isRequisitionApproved && ($isOwner || $isAdmin);
            if ($canDeleteReq): 
            ?>
                <form action="<?php echo $base; ?>/fund-requisitions/delete" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this requisition request?')">
                    <input type="hidden" name="id" value="<?php echo $req['id']; ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm px-3 shadow-sm">
                        <i class="fa-solid fa-trash-can me-1"></i> Delete Request
                    </button>
                </form>
            <?php endif; ?>

            <?php if ($req['status'] === 'paid' && ($isOwner || $isAdmin)): ?>
                <button class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                    <i class="fa-solid fa-plus me-1"></i> Log Expense
                </button>
            <?php endif; ?>
            
            <?php if ($req['status'] === 'paid' && ($isOwner || $isAdmin) && ($req['settlement_status'] ?? '') !== 'extra_requested'): ?>
                <button class="btn btn-outline-danger btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#extraFundModal">
                    <i class="fa-solid fa-hand-holding-dollar me-1"></i> Request Extra Fund
                </button>
            <?php endif; ?>

            <?php if ($req['status'] === 'paid'): ?>
                <a href="<?php echo $base; ?>/print-debit-voucher?module=fund_requisition&id=<?php echo $req['id']; ?>" target="_blank" class="btn btn-outline-primary btn-sm px-3 shadow-sm">
                    <i class="fa-solid fa-file-invoice-dollar me-1"></i> Debit Voucher
                </a>
                <a href="<?php echo $base; ?>/general-journal" class="btn btn-outline-success btn-sm px-3 shadow-sm">
                    <i class="fa-solid fa-book-journal-whills me-1"></i> Journal Entry
                </a>
            <?php endif; ?>
            
            <button type="button" class="btn btn-outline-secondary btn-sm px-3 shadow-sm" onclick="window.print()">
                <i class="fa-solid fa-print me-1"></i> Print
            </button>
            
            <a href="<?php echo $base; ?>/fund-requisitions" class="btn btn-light btn-sm border px-3">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

    <!-- PRINT-ONLY HEADER -->
    <div class="print-only-header d-none">
        <div class="text-center mb-3">
            <h4 class="fw-bold mb-1">TPHL — Fund Requisition</h4>
            <div class="fs-13 text-muted">REQ-<?php echo $req['id']; ?> &nbsp;|&nbsp; Printed on: <span id="printDateTime"></span></div>
            <hr>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-4"><small class="text-muted d-block">Requested By</small><strong><?php echo htmlspecialchars(($req['requester_name'] ?? '---') . (!empty($req['requester_designation_name']) ? ' (' . $req['requester_designation_name'] . ')' : '')); ?></strong></div>
            <div class="col-4"><small class="text-muted d-block">Department</small><strong><?php echo htmlspecialchars($req['department_name'] ?? '---'); ?></strong></div>
            <div class="col-4"><small class="text-muted d-block">Department Head / Boss</small><strong><?php echo !empty($req['requester_is_dept_head']) ? 'Dept Head (Self)' : htmlspecialchars(($req['reporting_boss_name'] ?? 'N/A') . (!empty($req['reporting_boss_designation_name']) ? ' (' . $req['reporting_boss_designation_name'] . ')' : '')); ?></strong></div>
            <div class="col-4"><small class="text-muted d-block">Project</small><strong><?php echo $req['project_name'] ? htmlspecialchars($req['project_name']) : 'Office General'; ?></strong></div>
            <div class="col-4"><small class="text-muted d-block">Type</small><strong><?php echo ucfirst(str_replace('_', ' ', $req['requisition_type'] ?? '')); ?> — <?php echo htmlspecialchars($req['category'] ?? ''); ?></strong></div>
            <div class="col-4"><small class="text-muted d-block">Amount Requested</small><strong>&#2547; <?php echo number_format($req['amount'], 2); ?></strong></div>
            <div class="col-4"><small class="text-muted d-block">Date</small><strong><?php echo date('d M Y', strtotime($req['created_at'])); ?></strong></div>
            <div class="col-12"><small class="text-muted d-block">Purpose</small><p class="mb-0"><?php echo nl2br(htmlspecialchars($req['purpose'])); ?></p></div>
            <?php if ($req['status'] === 'paid'): ?>
                <div class="col-4"><small class="text-muted d-block">Status</small><strong>Paid via <?php echo htmlspecialchars($req['payment_method'] ?? 'Cash'); ?></strong></div>
                <div class="col-4"><small class="text-muted d-block">Total Spent</small><strong>&#2547; <?php echo number_format($totalSpent, 2); ?></strong></div>
                <div class="col-4"><small class="text-muted d-block">Balance</small><strong>&#2547; <?php echo number_format($balance, 2); ?></strong></div>
            <?php endif; ?>
        </div>
        <hr>
    </div>

    <!-- ALERT MESSAGES -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- TOP SUMMARY STATS -->
    <div class="row mb-4 no-print">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 rounded-3">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted fs-11 text-uppercase fw-bold d-block">Amount Requested</span>
                        <h4 class="fw-extrabold text-primary mb-0 mt-1">&#2547; <?php echo number_format($req['amount'], 2); ?></h4>
                    </div>
                    <div class="avatar avatar-md bg-primary-transparent rounded-circle fs-18">
                        <i class="fa-solid fa-wallet text-primary"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 rounded-3">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted fs-11 text-uppercase fw-bold d-block">Total Spent</span>
                        <h4 class="fw-extrabold text-info mb-0 mt-1">&#2547; <?php echo number_format($totalSpent, 2); ?></h4>
                    </div>
                    <div class="avatar avatar-md bg-info-transparent rounded-circle fs-18">
                        <i class="fa-solid fa-receipt text-info"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 rounded-3">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted fs-11 text-uppercase fw-bold d-block">Balance Remaining</span>
                        <h4 class="fw-extrabold mb-0 mt-1 <?php echo $balance > 0 ? 'text-success' : 'text-muted'; ?>">&#2547; <?php echo number_format($balance, 2); ?></h4>
                    </div>
                    <div class="avatar avatar-md bg-success-transparent rounded-circle fs-18">
                        <i class="fa-solid fa-coins text-success"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 rounded-3">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted fs-11 text-uppercase fw-bold d-block">Requisition Status</span>
                        <div class="mt-1">
                            <span class="badge <?php echo $statusClass; ?> px-3 py-1 fs-12 text-capitalize"><?php echo ucfirst($req['status']); ?></span>
                        </div>
                    </div>
                    <div class="avatar avatar-md bg-warning-transparent rounded-circle fs-18">
                        <i class="fa-solid fa-circle-nodes text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT ROW -->
    <div class="row g-4 print-area">
        <!-- LEFT COLUMN: Requisition Info & Timeline Stepper -->
        <div class="col-lg-5">
            <!-- REQUISITION INFO CARD -->
            <div class="card border-0 shadow-sm mb-4 rounded-3">
                <div class="card-header bg-transparent border-bottom py-3 d-flex align-items-center justify-content-between">
                    <h6 class="card-title fw-bold text-dark mb-0 fs-14">
                        <i class="fa-solid fa-circle-info text-primary me-2"></i>Requisition Information
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="row ">
                        <div class="col-6">
                            <div class="p-2 bg-light rounded border border-light-subtle">
                                <small class="text-muted d-block fs-11 text-uppercase fw-semibold mb-1"><i class="fa-solid fa-hashtag me-1"></i>Requisition ID</small>
                                <span class="fw-bold text-dark fs-13">REQ-<?php echo $req['id']; ?></span>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="p-2 bg-light rounded border border-light-subtle">
                                <small class="text-muted d-block fs-11 text-uppercase fw-semibold mb-1"><i class="fa-regular fa-calendar me-1"></i>Date</small>
                                <span class="fs-13 text-dark fw-medium"><?php echo date('d M Y', strtotime($req['created_at'])); ?></span>
                            </div>
                        </div>

                        <div class="col-6">
                            <small class="text-muted d-block fs-11 text-uppercase fw-semibold"><i class="fa-solid fa-user me-1"></i>Requested By</small>
                            <span class="fs-13 fw-bold text-dark d-block mt-1"><?php echo htmlspecialchars($req['requester_name'] ?? '---'); ?></span>
                            <?php if (!empty($req['requester_designation_name'])): ?>
                                <small class="text-muted fs-11 d-block"><i class="fa-solid fa-id-badge me-1"></i><?php echo htmlspecialchars($req['requester_designation_name']); ?></small>
                            <?php endif; ?>
                        </div>

                        <div class="col-6">
                            <small class="text-muted d-block fs-11 text-uppercase fw-semibold"><i class="fa-solid fa-building me-1"></i>Department</small>
                            <span class="fs-13 text-dark d-block mt-1"><?php echo htmlspecialchars($req['department_name'] ?? '---'); ?></span>
                        </div>

                        <div class="col-6 mt-2">
                            <small class="text-muted d-block fs-11 text-uppercase fw-semibold"><i class="fa-solid fa-user-shield me-1"></i>Reporting Boss / Dept Head</small>
                            <?php if (!empty($req['requester_is_dept_head'])): ?>
                                <span class="badge bg-success-transparent text-success mt-1 fs-11"><i class="fa-solid fa-check me-1"></i>Department Head (Self)</span>
                            <?php else: ?>
                                <span class="fs-13 fw-semibold text-dark d-block mt-1"><?php echo htmlspecialchars($req['reporting_boss_name'] ?? 'N/A'); ?></span>
                                <?php if (!empty($req['reporting_boss_designation_name'])): ?>
                                    <small class="text-muted fs-11 d-block"><?php echo htmlspecialchars($req['reporting_boss_designation_name']); ?></small>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <div class="col-6 mt-2">
                            <small class="text-muted d-block fs-11 text-uppercase fw-semibold"><i class="fa-solid fa-diagram-project me-1"></i>Project</small>
                            <span class="fs-13 fw-semibold text-primary d-block mt-1"><?php echo htmlspecialchars($req['project_name'] ?? 'Office General'); ?></span>
                        </div>

                        <div class="col-6">
                            <small class="text-muted d-block fs-11 text-uppercase fw-semibold"><i class="fa-solid fa-tags me-1"></i>Type</small>
                            <div class="mt-1">
                                <?php if ($req['requisition_type'] === 'petty_cash'): ?>
                                    <span class="badge bg-warning text-dark fs-11">Petty Cash</span>
                                <?php else: ?>
                                    <span class="badge bg-info text-white fs-11">General</span>
                                <?php endif; ?>
                                <?php if (!empty($req['category'])): ?>
                                    <span class="badge bg-light text-dark border fs-11 ms-1"><?php echo htmlspecialchars($req['category']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!empty($req['asset_code'])): ?>
                            <div class="col-12">
                                <div class="p-3 bg-primary-transparent border border-primary border-opacity-25 rounded-3 fs-12">
                                    <div class="fw-bold text-primary mb-1"><i class="fa-solid fa-cube me-1"></i> Asset Info</div>
                                    <div class="fw-semibold"><?= htmlspecialchars($req['asset_code']) ?></div>
                                    <div class="text-muted fs-11 mt-1"><?= htmlspecialchars($req['asset_item_name'] ?? '') ?></div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="col-12">
                            <small class="text-muted d-block fs-11 text-uppercase fw-semibold mb-1"><i class="fa-solid fa-align-left me-1"></i>Purpose</small>
                            <div class="bg-light-subtle p-3 rounded-3 border fs-12 text-secondary" style="background-color: #f9fafb;">
                                <?php echo nl2br(htmlspecialchars($req['purpose'])); ?>
                            </div>
                        </div>

                        <?php if (!empty($req['receipt_path'])): ?>
                            <div class="col-12">
                                <a href="<?php echo $base . '/' . $req['receipt_path']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary border shadow-sm w-100 text-start d-flex align-items-center justify-content-between">
                                    <span><i class="fa-solid fa-paperclip me-2 text-primary"></i>View Requisition Receipt</span>
                                    <i class="fa-solid fa-external-link fs-11"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- APPROVAL & WORKFLOW TIMELINE CARD -->
            <div class="card border-0 shadow-sm mb-4 rounded-3">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h6 class="card-title fw-bold text-dark mb-0 fs-14">
                        <i class="fa-solid fa-list-check text-success me-2"></i>Approval & Payment Timeline
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="timeline-stepper">
                        <!-- Step 1: Requested -->
                        <div class="d-flex gap-3 mb-4 position-relative align-items-start">
                            <div class="avatar avatar-sm bg-success text-white rounded-circle flex-shrink-0 z-1 fs-12">
                                <i class="fa-solid fa-check"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold fs-13 mb-1 text-dark">Requisition Submitted</h6>
                                <p class="fs-12 text-muted mb-0">
                                    Submitted by <strong><?php echo htmlspecialchars($req['requester_name']); ?></strong>
                                    <?php if (!empty($req['requester_designation_name'])): ?>
                                        <em>(<?php echo htmlspecialchars($req['requester_designation_name']); ?>)</em>
                                    <?php endif; ?>
                                    — Department: <strong><?php echo htmlspecialchars($req['department_name'] ?? 'N/A'); ?></strong>
                                    on <?php echo date('d M Y, h:i A', strtotime($req['created_at'])); ?>
                                </p>
                            </div>
                        </div>

                        <!-- Step 2: Department Head Approval -->
                        <div class="d-flex gap-3 mb-4 position-relative align-items-start">
                            <?php if (!empty($req['dept_head_status']) && $req['dept_head_status'] === 'approved'): ?>
                                <div class="avatar avatar-sm bg-success text-white rounded-circle flex-shrink-0 z-1 fs-12">
                                    <i class="fa-solid fa-check"></i>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <h6 class="fw-bold fs-13 mb-0 text-dark">Department Head Approved</h6>
                                        <span class="badge bg-success-transparent text-success fs-10">Approved</span>
                                    </div>
                                    <p class="fs-12 text-muted mb-0 mt-1">
                                        Approved by <strong><?php echo htmlspecialchars($req['dept_head_approved_by_name'] ?? $req['reporting_boss_name'] ?? 'Department Head'); ?></strong>
                                        <?php if (!empty($req['dept_head_approved_by_designation_name'])): ?>
                                            <em>(<?php echo htmlspecialchars($req['dept_head_approved_by_designation_name']); ?>)</em>
                                        <?php endif; ?>
                                        <?php echo !empty($req['dept_head_approved_at']) ? 'on ' . date('d M Y, h:i A', strtotime($req['dept_head_approved_at'])) : ''; ?>
                                    </p>
                                    <?php if (!empty($req['dept_head_approval_note'])): ?>
                                        <div class="bg-light p-2 rounded fs-12 mt-2 text-dark border">
                                            <i class="fa-solid fa-quote-left text-muted me-1 fs-11"></i><?php echo htmlspecialchars($req['dept_head_approval_note']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php elseif (!empty($req['dept_head_status']) && $req['dept_head_status'] === 'rejected'): ?>
                                <div class="avatar avatar-sm bg-danger text-white rounded-circle flex-shrink-0 z-1 fs-12">
                                    <i class="fa-solid fa-xmark"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold fs-13 mb-0 text-danger">Department Head Rejected</h6>
                                    <p class="fs-12 text-muted mb-0 mt-1">
                                        Rejected by <strong><?php echo htmlspecialchars($req['dept_head_approved_by_name'] ?? $req['reporting_boss_name'] ?? 'Department Head'); ?></strong>
                                        <?php if (!empty($req['dept_head_approved_by_designation_name'])): ?>
                                            <em>(<?php echo htmlspecialchars($req['dept_head_approved_by_designation_name']); ?>)</em>
                                        <?php endif; ?>
                                    </p>
                                    <?php if (!empty($req['dept_head_approval_note'])): ?>
                                        <p class="fs-12 text-muted mb-0 mt-1 bg-light p-2 rounded border"><?php echo htmlspecialchars($req['dept_head_approval_note']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php elseif (!empty($req['dept_head_status']) && $req['dept_head_status'] === 'not_required'): ?>
                                <div class="avatar avatar-sm bg-light text-muted rounded-circle flex-shrink-0 z-1 fs-12 border">
                                    <i class="fa-solid fa-user-check text-success"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold fs-13 mb-0 text-dark">Department Head Approval</h6>
                                    <p class="fs-12 text-muted mb-0 mt-1"><span class="badge bg-secondary-transparent text-secondary fs-10">Not Required (Submitter is Dept Head)</span></p>
                                </div>
                            <?php else: ?>
                                <div class="avatar avatar-sm bg-warning text-dark rounded-circle flex-shrink-0 z-1 fs-12">
                                    <i class="fa-solid fa-clock"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold fs-13 mb-0 text-dark">Pending Department Head Approval</h6>
                                    <p class="fs-12 text-muted mb-0 mt-1">
                                        Awaiting approval from Reporting Boss: <strong><?php echo htmlspecialchars($req['reporting_boss_name'] ?? 'Dept Head'); ?></strong>
                                        <?php if (!empty($req['reporting_boss_designation_name'])): ?>
                                            <em>(<?php echo htmlspecialchars($req['reporting_boss_designation_name']); ?>)</em>
                                        <?php endif; ?>
                                    </p>
                                    <?php 
                                    $canApproveDept = ($sessionUserId == ($req['requester_reporting_boss_id'] ?? 0) || in_array($sessionRoleId, [1, 3]));
                                    if ($req['status'] === 'pending_dept' && $canApproveDept): 
                                    ?>
                                        <button class="btn btn-sm btn-warning text-dark mt-2 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#deptApproveModal">
                                            <i class="fa-solid fa-stamp me-1"></i> Dept Head Approve / Reject
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Step 3: Management Approval -->
                        <div class="d-flex gap-3 mb-4 position-relative align-items-start">
                            <?php if (in_array($req['status'], ['approved', 'paid'])): ?>
                                <div class="avatar avatar-sm bg-success text-white rounded-circle flex-shrink-0 z-1 fs-12">
                                    <i class="fa-solid fa-check"></i>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <h6 class="fw-bold fs-13 mb-0 text-dark">Management Approved</h6>
                                        <span class="badge bg-success-transparent text-success fs-10">Approved</span>
                                    </div>
                                    <p class="fs-12 text-muted mb-0 mt-1">
                                        Approved by <strong><?php echo htmlspecialchars($req['approved_by_name'] ?? 'Management'); ?></strong>
                                        <?php if (!empty($req['approved_by_designation_name'])): ?>
                                            <em>(<?php echo htmlspecialchars($req['approved_by_designation_name']); ?>)</em>
                                        <?php endif; ?>
                                        <?php echo $req['approved_at'] ? 'on ' . date('d M Y, h:i A', strtotime($req['approved_at'])) : ''; ?>
                                    </p>
                                    <?php if (!empty($req['approval_note'])): ?>
                                        <div class="bg-light p-2 rounded fs-12 mt-2 text-dark border">
                                            <i class="fa-solid fa-quote-left text-muted me-1 fs-11"></i><?php echo htmlspecialchars($req['approval_note']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($req['status'] === 'rejected' && (!empty($req['dept_head_status']) && $req['dept_head_status'] !== 'rejected')): ?>
                                <div class="avatar avatar-sm bg-danger text-white rounded-circle flex-shrink-0 z-1 fs-12">
                                    <i class="fa-solid fa-xmark"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold fs-13 mb-0 text-danger">Management Rejected</h6>
                                    <p class="fs-12 text-muted mb-0 mt-1">
                                        Rejected by <strong><?php echo htmlspecialchars($req['approved_by_name'] ?? 'Management'); ?></strong>
                                        <?php if (!empty($req['approved_by_designation_name'])): ?>
                                            <em>(<?php echo htmlspecialchars($req['approved_by_designation_name']); ?>)</em>
                                        <?php endif; ?>
                                    </p>
                                    <?php if (!empty($req['approval_note'])): ?>
                                        <p class="fs-12 text-muted mb-0 mt-1 bg-light p-2 rounded border"><?php echo htmlspecialchars($req['approval_note']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($req['status'] === 'pending'): ?>
                                <div class="avatar avatar-sm bg-warning text-dark rounded-circle flex-shrink-0 z-1 fs-12">
                                    <i class="fa-solid fa-clock"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold fs-13 mb-0 text-dark">Pending Management Approval</h6>
                                    <p class="fs-12 text-muted mb-0 mt-1">Awaiting approval from Management / Operations</p>
                                    <?php if ($canApprove): ?>
                                        <button class="btn btn-sm btn-primary mt-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#approveModal">
                                            <i class="fa-solid fa-stamp me-1"></i> Management Approve / Reject
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="avatar avatar-sm bg-light text-muted rounded-circle flex-shrink-0 z-1 fs-12 border">
                                    <i class="fa-solid fa-hourglass-start"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold fs-13 mb-0 text-muted">Pending Management Approval</h6>
                                    <p class="fs-12 text-muted mb-0 mt-1">Will be enabled after Department Head approval</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Step 4: Accounts Disbursement -->
                        <div class="d-flex gap-3 position-relative align-items-start">
                            <?php if ($req['status'] === 'paid'): ?>
                                <div class="avatar avatar-sm bg-success text-white rounded-circle flex-shrink-0 z-1 fs-12">
                                    <i class="fa-solid fa-building-columns"></i>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <h6 class="fw-bold fs-13 mb-0 text-dark">Accounts Disbursed</h6>
                                        <span class="badge bg-success-transparent text-success fs-10">Paid</span>
                                    </div>
                                    <p class="fs-12 text-muted mb-0 mt-1">
                                        Paid by <strong><?php echo htmlspecialchars($req['paid_by_name'] ?? 'Accounts'); ?></strong>
                                        <?php if (!empty($req['paid_by_designation_name'])): ?>
                                            <em>(<?php echo htmlspecialchars($req['paid_by_designation_name']); ?>)</em>
                                        <?php endif; ?>
                                        via <strong><?php echo htmlspecialchars($req['payment_method'] ?? 'Cash'); ?></strong>
                                        <?php echo $req['paid_at'] ? 'on ' . date('d M Y, h:i A', strtotime($req['paid_at'])) : ''; ?>
                                    </p>
                                    <?php if (!empty($req['accounts_note'])): ?>
                                        <div class="bg-light p-2 rounded fs-12 mt-2 text-dark border">
                                            <i class="fa-solid fa-comment-dots text-muted me-1 fs-11"></i><?php echo htmlspecialchars($req['accounts_note']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($req['payment_attachment_path'])): ?>
                                        <a href="<?php echo $base . '/' . $req['payment_attachment_path']; ?>" target="_blank" class="btn btn-sm btn-outline-success border mt-2">
                                            <i class="fa-solid fa-receipt me-1"></i> Payment Voucher / Attachment
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($req['status'] === 'approved' && $isAccounts): ?>
                                <div class="avatar avatar-sm bg-info text-white rounded-circle flex-shrink-0 z-1 fs-12">
                                    <i class="fa-solid fa-hand-holding-dollar"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold fs-13 mb-0 text-dark">Ready for Accounts Disbursement</h6>
                                    <p class="fs-12 text-muted mb-0 mt-1">Requisition is approved. Accounts team can process payout.</p>
                                    <button class="btn btn-sm btn-success mt-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#payModal">
                                        <i class="fa-solid fa-file-invoice-dollar me-1"></i> Process Payment Now
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="avatar avatar-sm bg-light text-muted rounded-circle flex-shrink-0 z-1 fs-12 border">
                                    <i class="fa-solid fa-hourglass-start"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold fs-13 mb-0 text-muted">Awaiting Payment</h6>
                                    <p class="fs-12 text-muted mb-0 mt-1">Will be enabled after management approval</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Expense Log & Utilization Table -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-transparent border-bottom py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h6 class="card-title fw-bold text-dark mb-0 fs-15">
                            <i class="fa-solid fa-receipt text-primary me-2"></i>Expense Log & Utilization
                        </h6>
                        <small class="text-muted fs-12">Track all itemized expenses logged against this requisition</small>
                    </div>
                    
                    <?php if ($req['status'] === 'paid' && ($isOwner || $isAdmin)): ?>
                        <button class="btn btn-sm btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                            <i class="fa-solid fa-plus me-1"></i> Log Expense
                        </button>
                    <?php endif; ?>
                </div>

                <?php if ($req['status'] === 'paid'): ?>
                    <div class="px-4 pt-3 pb-2 bg-light-subtle border-bottom">
                        <div class="d-flex justify-content-between align-items-center mb-1 fs-12">
                            <span class="fw-semibold text-dark">Utilization Progress</span>
                            <span class="fw-bold text-primary"><?php echo $spentPercent; ?>% Spent</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar <?php echo $spentPercent > 90 ? 'bg-danger' : ($spentPercent > 50 ? 'bg-warning' : 'bg-success'); ?>" role="progressbar" style="width: <?php echo $spentPercent; ?>%"></div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2 fs-11 text-muted">
                            <span>Disbursed: <strong>&#2547; <?php echo number_format($req['amount'], 2); ?></strong></span>
                            <span>Spent: <strong>&#2547; <?php echo number_format($totalSpent, 2); ?></strong></span>
                            <span>Remaining: <strong class="<?php echo $balance > 0 ? 'text-success' : 'text-muted'; ?>">&#2547; <?php echo number_format($balance, 2); ?></strong></span>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table text-nowrap table-hover align-middle mb-0 fs-12">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-3" style="width: 40px;">#</th>
                                    <th>Date</th>
                                    <th>Purpose</th>
                                    <th>Project</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-center">Receipt</th>
                                    <th>Logged By</th>
                                    <?php if ($isAdmin || $isOwner): ?><th class="text-center pe-3" style="width: 50px;">Del</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($expenses)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="fa-regular fa-folder-open fs-36 text-light-dark mb-2 d-block opacity-50"></i>
                                            <h6 class="fw-semibold fs-13 mb-1">No Expenses Logged Yet</h6>
                                            <p class="fs-12 text-muted mb-0">Itemized expenses will appear here once submitted.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($expenses as $i => $exp): ?>
                                <tr>
                                    <td class="ps-3 fw-bold text-muted"><?php echo $i+1; ?></td>
                                    <td>
                                        <span class="fw-medium text-dark"><?php echo date('d M Y', strtotime($exp['created_at'])); ?></span>
                                    </td>
                                    <td class="text-wrap" style="max-width: 220px">
                                        <span class="text-dark fw-medium"><?php echo htmlspecialchars($exp['purpose']); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?php echo $exp['project_name'] ? htmlspecialchars($exp['project_name']) : ($exp['other_project_name'] ? htmlspecialchars($exp['other_project_name']) : 'Office'); ?>
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold text-success">
                                        &#2547; <?php echo number_format($exp['amount'], 2); ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($exp['attachment_path'])): ?>
                                            <a href="<?php echo $base.'/'.$exp['attachment_path']; ?>" target="_blank" class="btn btn-icon btn-sm btn-light border py-0 px-2" title="View Attachment">
                                                <i class="fa-solid fa-paperclip fs-11 text-primary"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">---</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="text-secondary fs-12"><?php echo htmlspecialchars($exp['creator_name'] ?? 'Staff'); ?></span>
                                    </td>
                                    <?php if ($isAdmin || $isOwner): ?>
                                    <td class="text-center pe-3">
                                        <form action="<?php echo $base; ?>/fund-requisitions/delete-expense" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this expense?')">
                                            <input type="hidden" name="id" value="<?php echo $exp['id']; ?>">
                                            <button class="btn btn-icon btn-sm btn-outline-danger border-0 py-1 px-2" title="Delete Expense">
                                                <i class="fa-solid fa-trash-can fs-11"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <?php endif; ?>
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
</div>

<?php 
$canApproveDept = ($sessionUserId == ($req['requester_reporting_boss_id'] ?? 0) || in_array($sessionRoleId, [1, 3]));
if ($req['status'] === 'pending_dept' && $canApproveDept): 
?>
<div class="modal fade" id="deptApproveModal" tabindex="-1">
    <div class="modal-dialog"><form action="<?php echo $base; ?>/fund-requisitions/approve-dept" method="POST">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Department Head Approval</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="id" value="<?php echo $req['id']; ?>">
                <div class="mb-3"><label class="form-label">Action</label>
                    <select name="status" class="form-select" required><option value="approved">Approve (Send to Management)</option><option value="rejected">Reject</option></select></div>
                <div class="mb-3"><label class="form-label">Remarks / Note</label>
                    <textarea name="approval_note" class="form-control" rows="3" placeholder="Enter remarks..."></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-warning btn-sm">Submit Decision</button></div>
        </div>
    </form></div>
</div>
<?php endif; ?>

<?php if ($req['status'] === 'pending' && $canApprove): ?>
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog"><form action="<?php echo $base; ?>/fund-requisitions/approve" method="POST">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Approve / Reject Requisition</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="id" value="<?php echo $req['id']; ?>">
                <div class="mb-3"><label class="form-label">Action</label>
                    <select name="status" class="form-select" required><option value="approved">Approve</option><option value="rejected">Reject</option></select></div>
                <div class="mb-3"><label class="form-label">Remarks</label>
                    <textarea name="approval_note" class="form-control" rows="3"></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary btn-sm">Submit</button></div>
        </div>
    </form></div>
</div>
<?php endif; ?>

<?php if ($req['status'] === 'approved' && $isAccounts): ?>
<div class="modal fade" id="payModal" tabindex="-1">
    <div class="modal-dialog"><form action="<?php echo $base; ?>/fund-requisitions/pay" method="POST" enctype="multipart/form-data">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Process Payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="id" value="<?php echo $req['id']; ?>">
                <div class="mb-2 p-3 bg-light rounded border">
                    <strong><?php echo htmlspecialchars($req['requester_name']??'---'); ?></strong> &mdash; <?php echo htmlspecialchars($req['department_name']??''); ?>
                    <div class="fs-18 text-success fw-bold mt-1">&#2547; <?php echo number_format($req['amount'],2); ?></div>
                </div>
                <div class="mb-3"><label class="form-label">Payment Method</label>
                    <select name="payment_method" class="form-select"><option value="Cash" selected>Cash</option><option value="Bank Transfer">Bank Transfer</option><option value="Cheque">Cheque</option><option value="bKash">bKash</option><option value="Nagad">Nagad</option></select></div>
                <div class="mb-3"><label class="form-label">Payment Slip (Optional)</label><input type="file" name="payment_attachment" class="form-control" accept="image/*,application/pdf"></div>
                <div class="mb-3"><label class="form-label">Accounts Note</label><textarea name="accounts_note" class="form-control" rows="2"></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success btn-sm">Confirm Payment</button></div>
        </div>
    </form></div>
</div>
<?php endif; ?>

<?php if ($req['status'] === 'paid' && ($isOwner || $isAdmin)): ?>
<div class="modal fade" id="addExpenseModal" tabindex="-1">
    <div class="modal-dialog"><form action="<?php echo $base; ?>/fund-requisitions/store-expense" method="POST" enctype="multipart/form-data">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Log an Expense</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="fund_requisition_id" value="<?php echo $req['id']; ?>">
                <div class="mb-3"><label class="form-label">Amount (&#2547;) <span class="text-danger">*</span></label>
                    <input type="number" name="amount" class="form-control" step="0.01" min="0.01" max="<?php echo $balance; ?>" placeholder="0.00" required></div>
                <div class="mb-3"><label class="form-label">Purpose <span class="text-danger">*</span></label>
                    <textarea name="purpose" class="form-control" rows="2" required></textarea></div>

                <div class="mb-3"><label class="form-label">Receipt (Optional)</label><input type="file" name="attachment" class="form-control" accept="image/*,application/pdf"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm" <?php echo $balance<=0?'disabled':''; ?>>Log Expense</button></div>
        </div>
    </form></div>
</div>
<?php endif; ?>

<?php if ($req['status']==='paid' && ($isOwner||$isAdmin) && ($req['settlement_status']??'')!=='extra_requested'): ?>
<div class="modal fade" id="extraFundModal" tabindex="-1">
    <div class="modal-dialog"><form action="<?php echo $base; ?>/fund-requisitions/extra-fund/<?php echo $req['id']; ?>" method="POST">
        <div class="modal-content">
            <div class="modal-header bg-danger bg-opacity-10"><h5 class="modal-title text-danger">Request Extra Fund</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="alert alert-warning py-2 fs-12">Original: <strong>&#2547; <?php echo number_format($req['amount'],2); ?></strong> | Spent: <strong>&#2547; <?php echo number_format($totalSpent,2); ?></strong></div>
                <div class="mb-3"><label class="form-label">Extra Amount (&#2547;) <span class="text-danger">*</span></label>
                    <input type="number" name="extra_amount" class="form-control" step="0.01" min="0.01" value="<?php echo max(0,$totalSpent-$req['amount']); ?>" required></div>
                <div class="mb-3"><label class="form-label">Reason <span class="text-danger">*</span></label>
                    <textarea name="extra_reason" class="form-control" rows="3" required></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger btn-sm">Submit</button></div>
        </div>
    </form></div>
</div>
<?php endif; ?>

<style>
.timeline-stepper .avatar,
.timeline-step-icon {
    width: 32px !important;
    height: 32px !important;
    min-width: 32px !important;
    min-height: 32px !important;
    max-width: 32px !important;
    max-height: 32px !important;
    border-radius: 50% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-shrink: 0 !important;
    align-self: flex-start !important;
}

.avatar.avatar-md {
    width: 42px !important;
    height: 42px !important;
    min-width: 42px !important;
    min-height: 42px !important;
    max-width: 42px !important;
    max-height: 42px !important;
    border-radius: 50% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-shrink: 0 !important;
}

@media print {
    body * { visibility: hidden; }
    .print-area, .print-area * { visibility: visible; }
    .print-only-header, .print-only-header * { visibility: visible; display: block !important; }
    .print-only-header {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        background: white;
        padding: 10px 20px;
        z-index: 9999;
    }
    .print-area {
        position: absolute;
        top: 220px;
        left: 0;
        width: 100%;
        padding: 10px 20px;
    }
    .no-print, .modal, .btn-close,
    .page-header-breadcrumb { display: none !important; visibility: hidden !important; }
    .table { width: 100% !important; border-collapse: collapse !important; }
    .table th, .table td {
        border: 1px solid #ccc !important;
        padding: 6px 8px !important;
        font-size: 11px !important;
    }
    .table thead th { background: #f0f0f0 !important; }
    .card, .custom-card { border: 1px solid #ccc !important; box-shadow: none !important; margin-bottom: 10px !important; }
    .card-header { background: #f5f5f5 !important; }
    .bg-success, .bg-info, .bg-warning, .bg-danger, .bg-primary, .bg-secondary,
    .bg-success-transparent, .bg-info-transparent, .bg-warning-transparent, .bg-danger-transparent {
        background: transparent !important;
        border: 1px solid #999 !important;
        color: #000 !important;
    }
    .row { page-break-inside: avoid; }
    @page { margin: 10mm; }
}
</style>

<script>
(function() {
    function formatDate(d) {
        var days   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        var day    = days[d.getDay()];
        var date   = String(d.getDate()).padStart(2, '0');
        var month  = months[d.getMonth()];
        var year   = d.getFullYear();
        var hours  = d.getHours();
        var mins   = String(d.getMinutes()).padStart(2, '0');
        var ampm   = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;
        return day + ', ' + date + ' ' + month + ' ' + year + ', ' + hours + ':' + mins + ' ' + ampm;
    }

    function setPrintDate() {
        var el = document.getElementById('printDateTime');
        if (el) el.textContent = formatDate(new Date());
    }

    if (window.matchMedia) {
        var mql = window.matchMedia('print');
        mql.addListener(function(m) { if (m.matches) setPrintDate(); });
    }
    window.onbeforeprint = setPrintDate;
    document.addEventListener('DOMContentLoaded', setPrintDate);
})();
</script>

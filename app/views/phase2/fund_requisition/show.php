<?php
$sessionUserId = $sessionUserId ?? (int)($_SESSION['user']['id'] ?? 0);
$sessionRoleId = $sessionRoleId ?? (int)($_SESSION['user']['role_id'] ?? 0);
$base          = $GLOBALS['base_url'];

$isOwner    = ($req['requested_by'] == $sessionUserId);
$isAccounts = in_array($sessionRoleId, [1, 6, 30]);
require_once __DIR__ . '/../../../models/ApprovalRequest.php';
$ahInfo = ApprovalRequest::getAccountsHeadApprovalInfo($GLOBALS['db'] ?? ($this->db ?? null), 'fund_requisitions', (int)$req['id']);

$isOver1Lac = ((float)$req['amount'] > 100000.01);
$isOpHeadApproved = (!empty($ahInfo['is_op_head_approved']));
// Legacy: Management approved (> 1L) before Op Head sign-off -> Op Head step is bypassed, not pending
$isOpHeadBypassed = (!empty($ahInfo['is_op_head_bypassed']));

$canOpHeadApprove = in_array($sessionRoleId, [20, 32]) && ($isOver1Lac ? !$isOpHeadApproved : true);
$canMgmtApprove = in_array($sessionRoleId, [1, 3]) && ($isOver1Lac ? $isOpHeadApproved : true);
$canApprove = $canOpHeadApprove || $canMgmtApprove;
$isVerified = !empty($req['accounts_settlement_verified']);

$effectiveApprovedAmount = (!empty($req['approved_amount']) && (float)$req['approved_amount'] > 0)
    ? (float)$req['approved_amount']
    : ((!empty($req['qc_approved_amount']) && (float)$req['qc_approved_amount'] > 0)
        ? (float)$req['qc_approved_amount']
        : (float)$req['amount']);

$balance = max(0, $effectiveApprovedAmount - (float)($totalSpent ?? 0));
$dueToPay = max(0, $effectiveApprovedAmount - (float)($req['paid_amount'] ?? 0));

$statusColors = [
    'pending_dept' => 'bg-warning text-dark',
    'pending'      => 'bg-info text-white',
    'approved'     => 'bg-primary text-white',
    'paid'         => 'bg-success text-white',
    'rejected'     => 'bg-danger text-white'
];
$statusClass = $statusColors[$req['status']] ?? 'bg-secondary text-white';
$statusLabel = ($req['status'] === 'pending_dept') ? 'Pending Dept Head' : (($req['status'] === 'pending') ? 'Pending Management' : ucwords(str_replace('_', ' ', $req['status'])));

// Management / Operation Head approval breakdown (who approved how much)
$mgmtApprovals = (isset($mgmtApprovals) && is_array($mgmtApprovals)) ? $mgmtApprovals : [];
$mgmtApprovedTotal = (float)($req['approved_amount'] ?? 0);
$mgmtRemaining = max(0, (float)$req['amount'] - $mgmtApprovedTotal);
$opHeadStatusLc = strtolower($ahInfo['op_head_status'] ?? 'pending');
$showOpHeadStep = $isOver1Lac || $opHeadStatusLc === 'approved' || $opHeadStatusLc === 'rejected';

$settleBadgeMap = [
    'pending'         => ['bg-warning-transparent text-warning border border-warning', 'Pending Settlement'],
    'settled'         => ['bg-success-transparent text-success border border-success', 'Settled'],
    'extra_requested' => ['bg-danger-transparent text-danger border border-danger', 'Extra Fund Requested'],
];
[$settleClass, $settleLabel] = $settleBadgeMap[$req['settlement_status'] ?? 'pending'] ?? ['bg-secondary-transparent text-secondary', '---'];

$baseAmount = ($effectiveApprovedAmount > 0) ? $effectiveApprovedAmount : (float)$req['amount'];
$spentPercent = ($baseAmount > 0) ? min(100, round(((float)($totalSpent ?? 0) / $baseAmount) * 100, 1)) : 0;
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
                        <span class="text-muted fs-11 text-uppercase fw-bold d-block">Req / Approved</span>
                        <div class="fw-extrabold text-primary mt-1 fs-15">&#2547; <?php echo number_format($req['amount'], 2); ?></div>
                        <?php if (isset($req['qc_approved_amount']) && (float)$req['qc_approved_amount'] > 0): ?>
                            <div class="fs-11 text-info mt-0 fw-medium">QC: &#2547; <?php echo number_format((float)$req['qc_approved_amount'], 2); ?></div>
                        <?php endif; ?>
                        <div class="fs-12 text-muted mt-1 fw-medium">App: &#2547; <?php echo number_format($effectiveApprovedAmount, 2); ?></div>
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
                        <span class="text-muted fs-11 text-uppercase fw-bold d-block">Paid / Spent</span>
                        <div class="fw-extrabold text-success mt-1 fs-15">&#2547; <?php echo number_format($req['paid_amount'] ?? ($req['status'] === 'paid' ? $effectiveApprovedAmount : 0), 2); ?></div>
                        <div class="fs-12 text-muted mt-1 fw-medium">Spent: &#2547; <?php echo number_format($totalSpent, 2); ?></div>
                    </div>
                    <div class="avatar avatar-md bg-success-transparent rounded-circle fs-18">
                        <i class="fa-solid fa-receipt text-success"></i>
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
                    <div class="avatar avatar-md bg-warning-transparent rounded-circle fs-18">
                        <i class="fa-solid fa-coins text-warning"></i>
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
                            <span class="badge <?php echo $statusClass; ?> px-3 py-1 fs-12 text-capitalize"><?php echo ucfirst(str_replace('_', ' ', $req['status'])); ?></span>
                        </div>
                    </div>
                    <div class="avatar avatar-md bg-secondary-transparent rounded-circle fs-18">
                        <i class="fa-solid fa-circle-nodes text-secondary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ACCOUNTS HEAD APPROVAL STATUS & ACTION CARD -->
    <?php 
    if (!empty($ahInfo['has_request'])) {
        $sourceTable = 'fund_requisitions';
        $sourceId = (int)$req['id'];
        include __DIR__ . '/../../layouts/accounts_head_approval_card.php';
    }
    ?>

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

                        <?php 
                        if (!empty($req['receipt_path'])): 
                            $receiptList = [];
                            $decoded = json_decode($req['receipt_path'], true);
                            if (is_array($decoded)) {
                                $receiptList = $decoded;
                            } else {
                                $receiptList = [$req['receipt_path']];
                            }
                            $receiptList = array_values(array_filter($receiptList));
                            if (!empty($receiptList)):
                        ?>
                            <div class="col-12">
                                <small class="text-muted d-block fs-11 text-uppercase fw-semibold mb-2">
                                    <i class="fa-solid fa-paperclip me-1"></i>Attached Receipts & Documents (<?= count($receiptList) ?>)
                                </small>
                                <div class="d-flex flex-column gap-2">
                                    <?php foreach ($receiptList as $idx => $rPath): 
                                        $rUrl = (strpos($rPath, 'public/') === 0 ? $base . '/' : $base . '/public/') . ltrim($rPath, '/');
                                        $ext = strtolower(pathinfo($rPath, PATHINFO_EXTENSION));
                                        $isPdf = ($ext === 'pdf');
                                        $iconClass = $isPdf ? 'fa-file-pdf text-danger' : 'fa-image text-primary';
                                        $fileName = basename($rPath);
                                    ?>
                                        <a href="<?= htmlspecialchars($rUrl); ?>" target="_blank" class="btn btn-sm btn-outline-secondary border shadow-sm w-100 text-start d-flex align-items-center justify-content-between py-2 px-3 bg-white">
                                            <span class="d-inline-flex align-items-center gap-2">
                                                <i class="fa-solid <?= $iconClass ?> fs-14"></i>
                                                <span class="fw-semibold text-dark fs-12">Attachment #<?= $idx + 1 ?></span>
                                                <small class="text-muted fs-11 ms-1 d-none d-sm-inline">(<?= htmlspecialchars($fileName) ?>)</small>
                                            </span>
                                            <span class="badge bg-light text-secondary border fs-11 d-inline-flex align-items-center gap-1">
                                                <span>View</span>
                                                <i class="fa-solid fa-arrow-up-right-from-square fs-10"></i>
                                            </span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php 
                            endif;
                        endif; 
                        ?>
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
                                            <em>(<?php echo htmlspecialchars($req['dept_head_approved_by_designation_name']); ?>
                                            <?php if (!empty($req['dept_head_approved_by_department_name'])) echo ' - ' . htmlspecialchars($req['dept_head_approved_by_department_name']); ?>)</em>
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
                                            <em>(<?php echo htmlspecialchars($req['dept_head_approved_by_designation_name']); ?>
                                            <?php if (!empty($req['dept_head_approved_by_department_name'])) echo ' - ' . htmlspecialchars($req['dept_head_approved_by_department_name']); ?>)</em>
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
                                    $canApproveDept = ($sessionUserId == ($req['requester_reporting_boss_id'] ?? 0) || (!empty($req['department_id']) && in_array((int)$req['department_id'], $deptHeadDeptIds ?? [], true)) || in_array($sessionRoleId, [1, 3, 33]));
                                    if ($req['status'] === 'pending_dept' && $canApproveDept): 
                                    ?>
                                        <button class="btn btn-sm btn-warning text-dark mt-2 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#deptApproveModal">
                                            <i class="fa-solid fa-stamp me-1"></i> Dept Head Approve / Reject
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Step 2.5: QC & Internal Audit Head Approval -->
                        <div class="d-flex gap-3 mb-4 position-relative align-items-start">
                            <?php if ($req['status'] === 'pending_qc'): ?>
                                <div class="avatar avatar-sm bg-warning text-dark rounded-circle flex-shrink-0 z-1 fs-12">
                                    <i class="fa-solid fa-clock"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold fs-13 mb-0 text-dark">Pending QC & Internal Audit Head Approval</h6>
                                    <p class="fs-12 text-muted mb-0 mt-1">
                                        Awaiting approval from QC & Internal Audit Head (Role 24).
                                    </p>
                                    <?php if (in_array($sessionRoleId, [13, 24, 25, 1, 3])): ?>
                                        <button class="btn btn-sm btn-info text-white mt-2 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#qcApproveModal">
                                            <i class="fa-solid fa-stamp me-1"></i> QC Head Approve / Reject
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php elseif (!empty($req['qc_approved_by']) || ($req['status'] === 'rejected' && $req['dept_head_status'] === 'approved' && empty($req['approved_by']))): ?>
                                <?php $qcStatus = ($req['status'] === 'rejected' && empty($req['approved_by'])) ? 'rejected' : 'approved'; ?>
                                <?php if ($qcStatus === 'approved'): ?>
                                    <div class="avatar avatar-sm bg-success text-white rounded-circle flex-shrink-0 z-1 fs-12">
                                        <i class="fa-solid fa-check"></i>
                                    </div>
                                    <div>
                                        <div class="d-flex align-items-center gap-2">
                                            <h6 class="fw-bold fs-13 mb-0 text-dark">QC & Internal Audit Head Approved</h6>
                                            <span class="badge bg-success-transparent text-success fs-10">Approved</span>
                                        </div>
                                        <p class="fs-12 text-muted mb-0 mt-1">
                                            Approved by <strong><?php echo htmlspecialchars($req['qc_approved_by_name'] ?? 'QC Head'); ?></strong>
                                            <?php if (!empty($req['qc_approved_by_designation_name'])): ?>
                                                <em>(<?php echo htmlspecialchars($req['qc_approved_by_designation_name']); ?>)</em>
                                            <?php endif; ?>
                                            <?php echo !empty($req['qc_approved_at']) ? 'on ' . date('d M Y, h:i A', strtotime($req['qc_approved_at'])) : ''; ?>
                                        </p>
                                        <?php if (isset($req['qc_approved_amount']) && (float)$req['qc_approved_amount'] > 0): ?>
                                            <div class="mt-1">
                                                <span class="badge bg-info-transparent text-info fs-11">
                                                    <i class="fa-solid fa-bangladeshi-taka-sign me-1"></i>QC Approved Amount: &#2547; <?php echo number_format((float)$req['qc_approved_amount'], 2); ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (in_array($sessionRoleId, [24, 1, 3]) && (float)($req['qc_approved_amount'] ?? 0) < (float)$req['amount']): ?>
                                            <div class="mt-2">
                                                <button class="btn btn-sm btn-outline-info shadow-sm fw-medium" data-bs-toggle="modal" data-bs-target="#qcApproveModal">
                                                    <i class="fa-solid fa-pen-to-square me-1"></i> Update / Approve Remaining (QC)
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($req['qc_approval_note'])): ?>
                                            <div class="bg-light p-2 rounded fs-12 mt-2 text-dark border">
                                                <i class="fa-solid fa-quote-left text-muted me-1 fs-11"></i><?php echo htmlspecialchars($req['qc_approval_note']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="avatar avatar-sm bg-danger text-white rounded-circle flex-shrink-0 z-1 fs-12">
                                        <i class="fa-solid fa-xmark"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold fs-13 mb-0 text-danger">QC & Internal Audit Head Rejected</h6>
                                        <p class="fs-12 text-muted mb-0 mt-1">
                                            Rejected by <strong><?php echo htmlspecialchars($req['qc_approved_by_name'] ?? 'QC Head'); ?></strong>
                                            <?php if (!empty($req['qc_approved_by_designation_name'])): ?>
                                                <em>(<?php echo htmlspecialchars($req['qc_approved_by_designation_name']); ?>)</em>
                                            <?php endif; ?>
                                        </p>
                                        <?php if (!empty($req['qc_approval_note'])): ?>
                                            <p class="fs-12 text-muted mb-0 mt-1 bg-light p-2 rounded border"><?php echo htmlspecialchars($req['qc_approval_note']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php elseif (in_array($req['status'], ['pending', 'approved', 'partially_approved', 'paid', 'partially_paid']) && empty($req['qc_approved_by'])): ?>
                                <div class="avatar avatar-sm bg-light text-muted rounded-circle flex-shrink-0 z-1 fs-12 border">
                                    <i class="fa-solid fa-forward-step text-secondary"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold fs-13 mb-0 text-dark">QC & Internal Audit Head Approval</h6>
                                    <p class="fs-12 text-muted mb-0 mt-1"><span class="badge bg-secondary-transparent text-secondary fs-10">Bypassed (Legacy Request)</span></p>
                                </div>
                            <?php else: ?>
                                <div class="avatar avatar-sm bg-light text-muted rounded-circle flex-shrink-0 z-1 fs-12 border">
                                    <i class="fa-solid fa-hourglass-start"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold fs-13 mb-0 text-muted">Pending QC & Internal Audit Head Approval</h6>
                                    <p class="fs-12 text-muted mb-0 mt-1">Will be enabled after Department Head approval.</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($showOpHeadStep): ?>
                        <!-- Step 2.75: Admin & Operation Head Approval (mandatory for > ৳1,00,000) -->
                        <div class="d-flex gap-3 mb-4 position-relative align-items-start">
                            <?php if ($opHeadStatusLc === 'approved'): ?>
                                <div class="avatar avatar-sm bg-success text-white rounded-circle flex-shrink-0 z-1 fs-12">
                                    <i class="fa-solid fa-check"></i>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <h6 class="fw-bold fs-13 mb-0 text-dark">Admin &amp; Operation Head Approved</h6>
                                        <span class="badge bg-success-transparent text-success fs-10">Approved</span>
                                    </div>
                                    <p class="fs-12 text-muted mb-0 mt-1">
                                        Approved by <strong><?php echo htmlspecialchars($ahInfo['op_head_name'] ?? 'Admin & Operation Head'); ?></strong>
                                        <?php if (!empty($ahInfo['op_head_code'])): ?>[<?php echo htmlspecialchars($ahInfo['op_head_code']); ?>]<?php endif; ?>
                                        <?php if (!empty($ahInfo['op_head_designation'])): ?>
                                            <em>(<?php echo htmlspecialchars($ahInfo['op_head_designation']); ?>)</em>
                                        <?php endif; ?>
                                        <?php echo !empty($ahInfo['op_head_at']) ? 'on ' . date('d M Y, h:i A', strtotime($ahInfo['op_head_at'])) : ''; ?>
                                    </p>
                                    <?php if ($isOver1Lac): ?>
                                        <div class="mt-1"><span class="badge bg-info-transparent text-info fs-10">Forwarded to Management (Request &gt; &#2547;1,00,000)</span></div>
                                    <?php endif; ?>
                                    <?php if (!empty($ahInfo['op_head_note'])): ?>
                                        <div class="bg-light p-2 rounded fs-12 mt-2 text-dark border">
                                            <i class="fa-solid fa-quote-left text-muted me-1 fs-11"></i><?php echo htmlspecialchars($ahInfo['op_head_note']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($opHeadStatusLc === 'rejected'): ?>
                                <div class="avatar avatar-sm bg-danger text-white rounded-circle flex-shrink-0 z-1 fs-12">
                                    <i class="fa-solid fa-xmark"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold fs-13 mb-0 text-danger">Admin &amp; Operation Head Rejected</h6>
                                    <p class="fs-12 text-muted mb-0 mt-1">
                                        Rejected by <strong><?php echo htmlspecialchars($ahInfo['op_head_name'] ?? 'Admin & Operation Head'); ?></strong>
                                        <?php echo !empty($ahInfo['op_head_at']) ? 'on ' . date('d M Y, h:i A', strtotime($ahInfo['op_head_at'])) : ''; ?>
                                    </p>
                                    <?php if (!empty($ahInfo['op_head_note'])): ?>
                                        <p class="fs-12 text-muted mb-0 mt-1 bg-light p-2 rounded border"><?php echo htmlspecialchars($ahInfo['op_head_note']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($isOpHeadBypassed): ?>
                                <div class="avatar avatar-sm bg-secondary text-white rounded-circle flex-shrink-0 z-1 fs-12">
                                    <i class="fa-solid fa-forward"></i>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <h6 class="fw-bold fs-13 mb-0 text-muted">Admin &amp; Operation Head Step</h6>
                                        <span class="badge bg-secondary-transparent text-secondary fs-10">Bypassed</span>
                                    </div>
                                    <p class="fs-12 text-muted mb-0 mt-1">Management approved this request directly before Admin &amp; Operation Head sign-off (legacy flow). This step is no longer required &mdash; forwarded to Accounts Head.</p>
                                </div>
                            <?php elseif (in_array($req['status'], ['pending_dept', 'pending_qc'])): ?>
                                <div class="avatar avatar-sm bg-light text-muted rounded-circle flex-shrink-0 z-1 fs-12 border">
                                    <i class="fa-solid fa-hourglass-start"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold fs-13 mb-0 text-muted">Pending Admin &amp; Operation Head Approval</h6>
                                    <p class="fs-12 text-muted mb-0 mt-1">Will be enabled after QC &amp; Internal Audit Head approval. Mandatory for requests above &#2547;1,00,000.</p>
                                </div>
                            <?php elseif ($req['status'] === 'rejected'): ?>
                                <div class="avatar avatar-sm bg-light text-muted rounded-circle flex-shrink-0 z-1 fs-12 border">
                                    <i class="fa-solid fa-ban"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold fs-13 mb-0 text-muted">Admin &amp; Operation Head Approval</h6>
                                    <p class="fs-12 text-muted mb-0 mt-1">Not reached (request rejected).</p>
                                </div>
                            <?php else: ?>
                                <div class="avatar avatar-sm bg-warning text-dark rounded-circle flex-shrink-0 z-1 fs-12">
                                    <i class="fa-solid fa-clock"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold fs-13 mb-0 text-dark">Pending Admin &amp; Operation Head Approval</h6>
                                    <p class="fs-12 text-muted mb-0 mt-1">Awaiting approval from Admin &amp; Operation Head (Role 20) &mdash; mandatory before Management sign-off for requests above &#2547;1,00,000.</p>
                                    <?php if ($canOpHeadApprove && $req['status'] === 'pending'): ?>
                                        <button class="btn btn-sm btn-primary mt-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#approveModal">
                                            <i class="fa-solid fa-stamp me-1"></i> Approve as Operation Head (Role 20)
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

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
                                    <?php if (count($mgmtApprovals) > 1): ?>
                                        <div class="mt-2 border rounded p-2 bg-white">
                                            <div class="fs-11 text-uppercase fw-semibold text-muted mb-1"><i class="fa-solid fa-layer-group me-1"></i>Approval Breakdown</div>
                                            <?php foreach ($mgmtApprovals as $ma): ?>
                                                <div class="d-flex justify-content-between align-items-start gap-2 fs-12 py-1 border-bottom border-light">
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($ma['name']); ?></strong>
                                                        <?php if (!empty($ma['emp_code'])): ?><span class="text-muted">[<?php echo htmlspecialchars($ma['emp_code']); ?>]</span><?php endif; ?>
                                                        <span class="badge bg-light text-secondary border fs-10"><?php echo htmlspecialchars($ma['stage_label']); ?></span>
                                                        <?php if (!empty($ma['created_at'])): ?><div class="text-muted fs-11"><?php echo date('d M Y, h:i A', strtotime($ma['created_at'])); ?></div><?php endif; ?>
                                                    </div>
                                                    <span class="fw-bold text-success text-nowrap">&#2547; <?php echo number_format($ma['amount'], 2); ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php 
                                    if (in_array($sessionRoleId, [1, 3])) {
                                        $maxApprovableMgmt = (float)$req['amount'];
                                    } else {
                                        // Operation Head (20, 32): max 1 lakh
                                        $qcCapMgmt = (isset($req['qc_approved_amount']) && (float)$req['qc_approved_amount'] > 0)
                                            ? (float)$req['qc_approved_amount']
                                            : (float)$req['amount'];
                                        $maxApprovableMgmt = min(100000, $qcCapMgmt);
                                    }
                                    $dueToApproveMgmt = max(0, $maxApprovableMgmt - (float)($req['approved_amount'] ?? 0));
                                    if ($canApprove && $dueToApproveMgmt > 0): 
                                    ?>
                                        <div class="mt-2">
                                            <span class="badge bg-warning-transparent text-dark border border-warning fs-11 me-2">
                                                Remaining Unapproved: &#2547; <?php echo number_format($dueToApproveMgmt, 2); ?>
                                            </span>
                                            <button class="btn btn-sm btn-outline-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#approveModal">
                                                <i class="fa-solid fa-stamp me-1"></i> Approve Remaining Balance
                                            </button>
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
                            <?php elseif (in_array($req['status'], ['pending', 'partially_approved', 'partially_paid'])): ?>
                                <?php $isMgmtPartial = ($req['status'] !== 'pending'); ?>
                                <div class="avatar avatar-sm <?php echo $isMgmtPartial ? 'bg-info text-white' : 'bg-warning text-dark'; ?> rounded-circle flex-shrink-0 z-1 fs-12">
                                    <i class="fa-solid <?php echo $isMgmtPartial ? 'fa-circle-half-stroke' : 'fa-clock'; ?>"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2">
                                        <h6 class="fw-bold fs-13 mb-0 text-dark">
                                            <?php
                                            if ($isMgmtPartial) {
                                                echo 'Management Partially Approved';
                                            } elseif ($isOver1Lac) {
                                                echo 'Pending Management Approval';
                                            } else {
                                                echo 'Pending Management / Operation Head Approval';
                                            }
                                            ?>
                                        </h6>
                                        <?php if ($isMgmtPartial): ?><span class="badge bg-info-transparent text-info fs-10">Partial</span><?php endif; ?>
                                    </div>
                                    <p class="fs-12 text-muted mb-0 mt-1">
                                        <?php
                                        if ($isMgmtPartial) {
                                            echo 'Awaiting Management approval for the remaining balance.';
                                        } elseif ($isOver1Lac && !$isOpHeadApproved) {
                                            echo 'Will be enabled after Admin & Operation Head approval (request above ৳1,00,000).';
                                        } elseif ($isOver1Lac) {
                                            echo 'Admin & Operation Head approved. Awaiting final Management sign-off.';
                                        } else {
                                            echo 'Awaiting approval from Management (Any Amount) or Operation Head (Up to ৳1,00,000)';
                                        }
                                        ?>
                                    </p>

                                    <?php if ($isMgmtPartial): ?>
                                        <?php if (!empty($mgmtApprovals)): ?>
                                            <div class="mt-2 border rounded p-2 bg-white">
                                                <div class="fs-11 text-uppercase fw-semibold text-muted mb-1"><i class="fa-solid fa-user-shield me-1"></i>Approved By</div>
                                                <?php foreach ($mgmtApprovals as $ma): ?>
                                                    <div class="d-flex justify-content-between align-items-start gap-2 fs-12 py-1 border-bottom border-light">
                                                        <div>
                                                            <strong class="text-dark"><?php echo htmlspecialchars($ma['name']); ?></strong>
                                                            <?php if (!empty($ma['emp_code'])): ?><span class="text-muted">[<?php echo htmlspecialchars($ma['emp_code']); ?>]</span><?php endif; ?>
                                                            <?php if (!empty($ma['designation'])): ?><em class="text-muted">(<?php echo htmlspecialchars($ma['designation']); ?>)</em><?php endif; ?>
                                                            <span class="badge bg-light text-secondary border fs-10"><?php echo htmlspecialchars($ma['stage_label']); ?></span>
                                                            <?php if (!empty($ma['created_at'])): ?><div class="text-muted fs-11"><i class="fa-regular fa-clock me-1"></i><?php echo date('d M Y, h:i A', strtotime($ma['created_at'])); ?></div><?php endif; ?>
                                                            <?php if (!empty($ma['note'])): ?><div class="fs-11 fst-italic text-secondary">"<?php echo htmlspecialchars($ma['note']); ?>"</div><?php endif; ?>
                                                        </div>
                                                        <span class="fw-bold text-info text-nowrap">&#2547; <?php echo number_format($ma['amount'], 2); ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <p class="fs-12 text-muted mb-0 mt-1">
                                                Partially approved by <strong><?php echo htmlspecialchars($req['approved_by_name'] ?? 'Management'); ?></strong>
                                                <?php if (!empty($req['approved_by_designation_name'])): ?><em>(<?php echo htmlspecialchars($req['approved_by_designation_name']); ?>)</em><?php endif; ?>
                                                <?php echo !empty($req['approved_at']) ? 'on ' . date('d M Y, h:i A', strtotime($req['approved_at'])) : ''; ?>
                                            </p>
                                        <?php endif; ?>
                                        <div class="d-flex flex-wrap gap-2 mt-2 fs-12">
                                            <span class="badge bg-success-transparent text-success border border-success-subtle fs-11">Approved So Far: &#2547; <?php echo number_format($mgmtApprovedTotal, 2); ?></span>
                                            <span class="badge bg-danger-transparent text-danger border border-danger-subtle fs-11">Remaining: &#2547; <?php echo number_format($mgmtRemaining, 2); ?></span>
                                            <span class="badge bg-light text-dark border fs-11">Requested: &#2547; <?php echo number_format((float)$req['amount'], 2); ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($canApprove && ($req['amount'] - ($req['approved_amount'] ?? 0)) > 0): ?>
                                        <?php if ($isOver1Lac && !$isOpHeadApproved && in_array($sessionRoleId, [20, 32])): ?>
                                            <?php /* Op Head action button is shown in the Admin & Operation Head step above */ ?>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-primary mt-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#approveModal">
                                                <i class="fa-solid fa-stamp me-1"></i> <?php echo $isMgmtPartial ? 'Approve Remaining Balance' : 'Management Approve'; ?>
                                            </button>
                                        <?php endif; ?>
                                    <?php elseif ($isOver1Lac && !$isOpHeadApproved && in_array($sessionRoleId, [1, 3])): ?>
                                        <div class="alert alert-warning py-2 px-3 extra-small mt-2 mb-0">
                                            <i class="fa-solid fa-lock me-1 text-warning"></i> Requests exceeding ৳1,00,000 require Admin &amp; Operation Head (Role 20) approval before Management can approve.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="avatar avatar-sm bg-light text-muted rounded-circle flex-shrink-0 z-1 fs-12 border">
                                    <i class="fa-solid fa-hourglass-start"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold fs-13 mb-0 text-muted">Pending Management / Operation Head Approval</h6>
                                    <p class="fs-12 text-muted mb-0 mt-1">Will be enabled after QC & Internal Audit Head approval. <span class="badge bg-info-transparent text-info fs-10">Operation Head: Up to ৳1,00,000</span></p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Step 4: Accounts Disbursement -->
                        <div class="d-flex gap-3 mb-4 position-relative align-items-start">
                            <?php if ($req['status'] === 'paid' || (!empty($req['paid_at']) && $dueToPay <= 0)): ?>
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
                            <?php elseif (in_array($req['status'], ['approved', 'partially_approved', 'partially_paid'])): ?>
                                <div class="avatar avatar-sm bg-info text-white rounded-circle flex-shrink-0 z-1 fs-12">
                                    <i class="fa-solid fa-hand-holding-dollar"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold fs-13 mb-0 text-dark">Ready for Accounts Disbursement</h6>
                                    <p class="fs-12 text-muted mb-0 mt-1">Accounts team can process payout for the approved amount.</p>
                                    
                                    <?php if ($req['status'] === 'partially_paid'): ?>
                                        <div class="bg-light p-2 rounded fs-12 mt-2 text-dark border">
                                            <strong>Partially Paid So Far:</strong> &#2547; <?php echo number_format($req['paid_amount'] ?? 0, 2); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($isAccounts && $dueToPay > 0): ?>
                                        <?php if (!empty($ahInfo['is_accounts_head_approved'])): ?>
                                            <button class="btn btn-sm btn-success mt-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#payModal">
                                                <i class="fa-solid fa-file-invoice-dollar me-1"></i> Process Payment Now
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-light text-muted mt-2 border" disabled title="Payment locked pending Accounts Head (Md. Mamunoor Rashid [1309]) approval">
                                                <i class="fa-solid fa-lock text-warning me-1"></i> Payment Locked (Awaiting Accounts Head Approval)
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if (!empty($ahInfo['is_pending_accounts_head']) && in_array($sessionRoleId, [1, 3, 30])): ?>
                                        <a href="#accountsHeadApprovalCard" class="btn btn-sm btn-primary mt-2 shadow-sm">
                                            <i class="fa-solid fa-stamp me-1"></i> Approve as Accounts Head
                                        </a>
                                    <?php endif; ?>
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

                        <!-- Step 5: Money Received -->
                        <div class="d-flex gap-3 position-relative align-items-start">
                            <?php if (!empty($req['is_received'])): ?>
                                <div class="avatar avatar-sm bg-success text-white rounded-circle flex-shrink-0 z-1 fs-12">
                                    <i class="fa-solid fa-check-double"></i>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <h6 class="fw-bold fs-13 mb-0 text-dark">Money Received</h6>
                                        <span class="badge bg-success-transparent text-success fs-10">Confirmed</span>
                                    </div>
                                    <p class="fs-12 text-muted mb-0 mt-1">
                                        Confirmed by <strong><?php echo htmlspecialchars($req['requester_name'] ?? 'Requester'); ?></strong>
                                        <?php if (!empty($req['requester_designation_name'])): ?>
                                            <em>(<?php echo htmlspecialchars($req['requester_designation_name']); ?>)</em>
                                        <?php endif; ?>
                                        on <strong><?php echo date('d M Y, h:i A', strtotime($req['received_at'])); ?></strong>
                                    </p>
                                </div>
                            <?php elseif (in_array($req['status'], ['paid', 'partially_paid']) || (!empty($req['paid_at']) && $dueToPay <= 0)): ?>
                                <div class="avatar avatar-sm bg-warning text-white rounded-circle flex-shrink-0 z-1 fs-12">
                                    <i class="fa-solid fa-spinner fa-spin"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold fs-13 mb-0 text-dark">Pending Receipt Confirmation</h6>
                                    <p class="fs-12 text-muted mb-0 mt-1">Requester has not confirmed receiving the funds yet.</p>
                                    <?php if ($isOwner || $isAdmin): ?>
                                        <form action="<?php echo $base; ?>/fund-requisitions/confirm-receipt/<?php echo $req['id']; ?>" method="POST" class="mt-2" onsubmit="return confirm('Confirm that you have received the funds?')">
                                            <button type="submit" class="btn btn-success btn-sm shadow-sm">
                                                <i class="fa-solid fa-handshake me-1"></i> Confirm Received
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="avatar avatar-sm bg-light text-muted rounded-circle flex-shrink-0 z-1 fs-12 border">
                                    <i class="fa-solid fa-hourglass-start"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold fs-13 mb-0 text-muted">Money Received</h6>
                                    <p class="fs-12 text-muted mb-0 mt-1">Awaiting disbursement first</p>
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
                            <span>Disbursed / Approved: <strong>&#2547; <?php echo number_format($effectiveApprovedAmount, 2); ?></strong></span>
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
$canApproveQc = in_array($sessionRoleId, [24, 1, 3]);
$canShowQcModal = $canApproveQc && ($req['status'] === 'pending_qc' || (!empty($req['qc_approved_by']) && (float)($req['qc_approved_amount'] ?? 0) < (float)$req['amount']));
if ($canShowQcModal): 
?>
<div class="modal fade" id="qcApproveModal" tabindex="-1">
    <div class="modal-dialog"><form action="<?php echo $base; ?>/fund-requisitions/approve-qc" method="POST">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">QC & Internal Audit Head Approval</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="id" value="<?php echo $req['id']; ?>">
                <div class="mb-3 p-3 bg-light rounded border">
                    <small class="text-muted d-block fs-11 text-uppercase fw-semibold">Requested Amount</small>
                    <div class="fs-15 text-dark fw-bold mt-1">&#2547; <?php echo number_format($req['amount'], 2); ?></div>
                </div>
                <div class="mb-3"><label class="form-label fw-semibold">Action</label>
                    <select name="status" id="qc_action_select" class="form-select" required>
                        <option value="approved">Approve (Send to Management)</option>
                        <option value="rejected">Reject</option>
                    </select>
                </div>
                <div class="mb-3" id="qc_amount_div">
                    <label class="form-label fw-semibold">QC Approved Amount (&#2547;) <span class="text-danger">*</span></label>
                    <input type="number" name="qc_approved_amount" id="qc_approved_amount_input" class="form-control" 
                           step="0.01" min="0.01" max="<?php echo $req['amount']; ?>" 
                           value="<?php echo number_format($req['amount'], 2, '.', ''); ?>" required>
                    <small class="text-muted">You can adjust the approved amount up to requested &#2547; <?php echo number_format($req['amount'], 2); ?></small>
                </div>
                <div class="mb-3"><label class="form-label fw-semibold">Remarks / Observation Note</label>
                    <textarea name="approval_note" class="form-control" rows="3" placeholder="Enter remarks or audit observation notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-info text-white btn-sm fw-bold">Submit Decision</button></div>
        </div>
    </form></div>
</div>
<?php endif; ?>

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

<?php 
if (in_array($sessionRoleId, [1, 3])) {
    $maxApprovable = (float)$req['amount'];
} else {
    $effectiveReqAmount = (isset($req['qc_approved_amount']) && (float)$req['qc_approved_amount'] > 0)
        ? (float)$req['qc_approved_amount']
        : (float)$req['amount'];
    $maxApprovable = min(100000, $effectiveReqAmount);
}
$dueToApprove = max(0, $maxApprovable - (float)($req['approved_amount'] ?? 0));
if ($canApprove && $dueToApprove > 0 && !in_array($req['status'], ['rejected', 'pending_dept', 'pending_qc'])): ?>
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog"><form action="<?php echo $base; ?>/fund-requisitions/approve" method="POST">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Approve / Reject Requisition</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="id" value="<?php echo $req['id']; ?>">
                
                <?php if (isset($req['qc_approved_amount']) && (float)$req['qc_approved_amount'] > 0): ?>
                    <div class="alert alert-info py-2 px-3 mb-2 small">
                        <i class="fa-solid fa-shield-check me-1"></i>
                        <strong>QC & Internal Audit Approved:</strong> &#2547; <?php echo number_format((float)$req['qc_approved_amount'], 2); ?>
                        <?php if ((float)$req['qc_approved_amount'] < (float)$req['amount']): ?>
                            <span class="text-muted">(Reduced from requested &#2547; <?php echo number_format($req['amount'], 2); ?>)</span>
                        <?php endif; ?>
                        <?php if (!empty($req['qc_approval_note'])): ?>
                            <div class="fst-italic mt-1">"<?php echo htmlspecialchars($req['qc_approval_note']); ?>"</div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="mb-2 p-3 bg-light rounded border">
                    <div class="fs-15 text-dark fw-bold mt-1">Due to Approve: &#2547; <?php echo number_format($dueToApprove, 2); ?></div>
                </div>
                <div class="mb-3"><label class="form-label">Action</label>
                    <select name="status" id="approve_action_select" class="form-select" required>
                        <option value="approved">Approve (Full Due Amount - &#2547; <?php echo number_format($dueToApprove, 2); ?>)</option>
                        <option value="partially_approved">Partially Approve</option>
                        <option value="rejected">Reject</option>
                    </select>
                </div>
                <div class="mb-3 d-none" id="partial_amount_div">
                    <label class="form-label">Approved Amount (&#2547;)</label>
                    <input type="number" name="approved_amount" class="form-control" step="0.01" max="<?php echo $dueToApprove; ?>" placeholder="0.00">
                </div>
                <div class="mb-3"><label class="form-label">Remarks</label>
                    <textarea name="approval_note" class="form-control" rows="3"></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary btn-sm">Submit</button></div>
        </div>
    </form></div>
</div>
<?php endif; ?>

<?php 
if (in_array($req['status'], ['approved', 'partially_approved', 'partially_paid']) && $isAccounts && $dueToPay > 0 && !empty($ahInfo['is_accounts_head_approved'])): ?>
<div class="modal fade" id="payModal" tabindex="-1">
    <div class="modal-dialog"><form action="<?php echo $base; ?>/fund-requisitions/pay" method="POST" enctype="multipart/form-data">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Process Payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="id" value="<?php echo $req['id']; ?>">
                <div class="mb-2 p-3 bg-light rounded border">
                    <strong><?php echo htmlspecialchars($req['requester_name']??'---'); ?></strong> &mdash; <?php echo htmlspecialchars($req['department_name']??''); ?>
                    <div class="fs-18 text-success fw-bold mt-1">Due to Pay: &#2547; <?php echo number_format($dueToPay, 2); ?></div>
                </div>
                <div class="mb-3"><label class="form-label">Action</label>
                    <select name="status" id="pay_action_select" class="form-select" required>
                        <option value="paid">Pay (Full Due Amount)</option>
                        <option value="partially_paid">Partially Pay</option>
                    </select>
                </div>
                <div class="mb-3 d-none" id="pay_amount_div">
                    <label class="form-label">Paid Amount (&#2547;)</label>
                    <input type="number" name="paid_amount" class="form-control" step="0.01" max="<?php echo $dueToPay; ?>" placeholder="0.00">
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
    /* Reveal print-only header normally */
    .print-only-header { display: block !important; margin-bottom: 20px; }
    .print-only-header.d-none { display: block !important; }

    /* Hide non-print elements */
    .no-print, .modal, .btn-close, .page-header-breadcrumb { display: none !important; }

    /* Fix text readability - force darker colors */
    .print-area, .print-only-header { color: #000 !important; }
    .text-muted { color: #444 !important; }
    .text-secondary { color: #222 !important; }
    .text-primary { color: #11502e !important; }
    .text-success { color: #198754 !important; }
    .text-danger { color: #dc3545 !important; }
    .text-warning { color: #ffc107 !important; }
    .text-info { color: #0dcaf0 !important; }
    
    .print-area * { color: inherit; }
    .print-area h6, .print-area strong, .print-area span { color: #000 !important; }
    .bg-light, .bg-light-subtle { background-color: #f8f9fa !important; border-color: #ddd !important; -webkit-print-color-adjust: exact; }

    /* Table styling for print */
    .table { width: 100% !important; border-collapse: collapse !important; margin-bottom: 15px !important; }
    .table th, .table td {
        border: 1px solid #bbb !important;
        padding: 8px 10px !important;
        font-size: 11px !important;
        color: #000 !important;
    }
    .table thead th { background: #e9ecef !important; color: #000 !important; -webkit-print-color-adjust: exact; font-weight: bold !important; }

    /* Card styling for print */
    .card, .custom-card { 
        border: 1px solid #bbb !important; 
        box-shadow: none !important; 
        margin-bottom: 15px !important; 
        page-break-inside: avoid;
    }
    .card-header { 
        background: #e9ecef !important; 
        border-bottom: 1px solid #bbb !important; 
        -webkit-print-color-adjust: exact; 
        color: #000 !important; 
    }
    
    /* Timeline stepper borders/colors for print */
    .timeline-stepper .avatar {
        border: 1px solid #999 !important;
        background: #fff !important;
        color: #000 !important;
        -webkit-print-color-adjust: exact;
    }
    
    /* Badge styling */
    .bg-success, .bg-info, .bg-warning, .bg-danger, .bg-primary, .bg-secondary,
    .bg-success-transparent, .bg-info-transparent, .bg-warning-transparent, .bg-danger-transparent {
        background: transparent !important;
        border: 1px solid #666 !important;
        color: #000 !important;
        font-weight: bold;
    }
    
    .row { page-break-inside: auto; }
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

    // Toggle QC approve amount field
    var qcSelect = document.getElementById('qc_action_select');
    var qcAmountDiv = document.getElementById('qc_amount_div');
    if (qcSelect && qcAmountDiv) {
        qcSelect.addEventListener('change', function() {
            var input = qcAmountDiv.querySelector('input');
            if (this.value === 'rejected') {
                qcAmountDiv.classList.add('d-none');
                if (input) input.removeAttribute('required');
            } else {
                qcAmountDiv.classList.remove('d-none');
                if (input) input.setAttribute('required', 'required');
            }
        });
    }

    // Toggle partial approve/pay fields
    var approveSelect = document.getElementById('approve_action_select');
    var approveDiv = document.getElementById('partial_amount_div');
    if (approveSelect && approveDiv) {
        approveSelect.addEventListener('change', function() {
            if (this.value === 'partially_approved') {
                approveDiv.classList.remove('d-none');
                approveDiv.querySelector('input').setAttribute('required', 'required');
            } else {
                approveDiv.classList.add('d-none');
                approveDiv.querySelector('input').removeAttribute('required');
            }
        });
    }

    var paySelect = document.getElementById('pay_action_select');
    var payDiv = document.getElementById('pay_amount_div');
    if (paySelect && payDiv) {
        paySelect.addEventListener('change', function() {
            if (this.value === 'partially_paid') {
                payDiv.classList.remove('d-none');
                payDiv.querySelector('input').setAttribute('required', 'required');
            } else {
                payDiv.classList.add('d-none');
                payDiv.querySelector('input').removeAttribute('required');
            }
        });
    }
})();
</script>

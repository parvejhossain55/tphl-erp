<?php
$base = $GLOBALS['base_url'];

$statusBadge = [
    'pending'              => 'bg-warning text-dark',
    'pm_approved'          => 'bg-info text-dark',
    'work_completed'       => 'bg-primary',
    'payment_requested'    => 'bg-warning',
    'qc_approved'          => 'bg-teal',
    'pm_payment_approved'  => 'bg-indigo ',
    'management_approved'  => 'bg-success',
    'partially_paid'       => 'bg-orange text-white',
    'paid'                 => 'bg-success',
    'labor_disbursed'      => 'bg-success',
    'rejected'             => 'bg-danger',
];
$statusLabel = [
    'pending'              => 'Pending Dept. Head Approval',
    'pm_approved'          => 'Dept. Head Approved',
    'work_completed'       => 'Work Completed',
    'payment_requested'    => 'Payment Requested',
    'qc_approved'          => 'QC Approved',
    'pm_payment_approved'  => 'PM Payment Verified',
    'management_approved'  => 'Management Approved',
    'partially_paid'       => 'Partially Paid',
    'paid'                 => 'Paid by Accounts',
    'labor_disbursed'      => ' Labour Paid & Confirmed',
    'rejected'             => 'Rejected',
];

$st = $req['status'];
$isOwner = ($req['requested_by'] == $userId);

require_once __DIR__ . '/../../../models/ApprovalRequest.php';
$ahInfo = ApprovalRequest::getAccountsHeadApprovalInfo($GLOBALS['db'] ?? null, 'con_labor_requisitions', (int)$req['id']);
$isOpHeadApprovedTimeline = !empty($ahInfo['is_op_head_approved']) || in_array($st, ['management_approved','partially_paid','paid','labor_disbursed']);
$isAhApprovedTimeline = !empty($ahInfo['is_accounts_head_approved']) || in_array($st, ['paid','labor_disbursed']);
$lrEffectiveAmount = (float)($req['qc_approved_amount'] ?? $req['total_amount'] ?? 0);
$isLrOver10K = ($lrEffectiveAmount > 10000.01);
$showLrMgmtStep = $isLrOver10K || (!empty($req['management_approved_by']) && (int)$req['management_approved_by'] !== (int)($ahInfo['op_head_by'] ?? 0) && (int)$req['management_approved_by'] !== (int)($ahInfo['accounts_head_by'] ?? 0));

// Timeline steps (Submitted -> PM -> Work Completed -> Payment Requested -> QC -> Admin & Operation Head -> Accounts Head -> [Management if > 10K] -> Paid)
$steps = [
    [
        'icon'       => 'fa-file-pen',
        'title'      => 'Submitted',
        'done'       => true,
        'color'      => 'primary',
        'user'       => $req['requested_by_name'] ?? null,
        'role'       => $req['requested_by_role'] ?? null,
        'department' => $req['requested_by_dept'] ?? null,
        'at'         => $req['created_at'] ?? $req['requisition_date'] ?? null,
        'amount'     => $req['total_amount'] ?? null
    ],
    [
        'icon'       => 'fa-user-tie',
        'title'      => 'PM Approved',
        'done'       => !in_array($st, ['pending']),
        'color'      => 'info',
        'user'       => $req['pm_approved_by_name'] ?? null,
        'role'       => $req['pm_approved_by_role'] ?? null,
        'department' => $req['pm_approved_by_dept'] ?? null,
        'at'         => $req['pm_approved_at'] ?? null,
        'amount'     => $req['total_amount'] ?? null,
        'note'       => $req['pm_note'] ?? null
    ],
    [
        'icon'       => 'fa-hammer',
        'title'      => 'Work Completed',
        'done'       => in_array($st, ['work_completed','payment_requested','qc_approved','pm_payment_approved','management_approved','partially_paid','paid','labor_disbursed']),
        'color'      => 'warning'
    ],
    [
        'icon'       => 'fa-hand-holding-dollar',
        'title'      => 'Payment Requested',
        'done'       => in_array($st, ['payment_requested','qc_approved','pm_payment_approved','management_approved','partially_paid','paid','labor_disbursed']),
        'color'      => 'orange',
        'at'         => $req['payment_requested_at'] ?? null,
        'amount'     => $req['total_amount'] ?? null
    ],
    [
        'icon'       => 'fa-magnifying-glass-check',
        'title'      => 'QC & Internal Audit Approved',
        'done'       => in_array($st, ['qc_approved','pm_payment_approved','management_approved','partially_paid','paid','labor_disbursed']),
        'color'      => 'teal',
        'user'       => $req['qc_approved_by_name'] ?? null,
        'role'       => $req['qc_approved_by_role'] ?? null,
        'department' => $req['qc_approved_by_dept'] ?? null,
        'at'         => $req['qc_approved_at'] ?? null,
        'amount'     => $req['qc_approved_amount'] ?? null,
        'note'       => $req['qc_note'] ?? null
    ],
    [
        'icon'       => 'fa-user-shield',
        'title'      => 'Admin & Operation Head Approved',
        'done'       => $isOpHeadApprovedTimeline,
        'color'      => 'indigo',
        'user'       => $isOpHeadApprovedTimeline ? ($ahInfo['op_head_name'] ?? 'Admin & Operation Head') : null,
        'role'       => $isOpHeadApprovedTimeline ? ($ahInfo['op_head_designation'] ?? 'Admin & Operation Head') : null,
        'department' => null,
        'at'         => $ahInfo['op_head_at'] ?? null,
        'amount'     => null,
        'note'       => $ahInfo['op_head_note'] ?? null
    ],
    [
        'icon'       => 'fa-stamp',
        'title'      => 'Accounts Head Approved',
        'done'       => $isAhApprovedTimeline,
        'color'      => 'primary',
        'user'       => $isAhApprovedTimeline ? ($ahInfo['approved_by_name'] ?? $ahInfo['designated_name'] ?? 'Accounts Head') : null,
        'role'       => $isAhApprovedTimeline ? ($ahInfo['approved_by_designation'] ?? 'Accounts Head') : null,
        'department' => null,
        'at'         => $ahInfo['approved_at'] ?? null,
        'amount'     => null,
        'note'       => $ahInfo['approval_note'] ?? null
    ],
];
if ($showLrMgmtStep) {
    $steps[] = [
        'icon'       => 'fa-building-circle-check',
        'title'      => 'Management Approved (> ৳10,000)',
        'done'       => in_array($st, ['management_approved','partially_paid','paid','labor_disbursed']),
        'color'      => 'success',
        'user'       => $req['management_approved_by_name'] ?? null,
        'role'       => $req['management_approved_by_role'] ?? null,
        'department' => $req['management_approved_by_dept'] ?? null,
        'at'         => $req['management_approved_at'] ?? null,
        'amount'     => $req['approved_amount'] ?? null,
        'note'       => $req['management_note'] ?? null
    ];
}
$steps[] = [
    'icon'       => 'fa-money-bill-wave',
    'title'      => 'Paid / Disbursed',
    'done'       => in_array($st, ['paid','labor_disbursed']),
    'color'      => 'success',
    'user'       => $req['paid_by_name'] ?? null,
    'role'       => $req['paid_by_role'] ?? null,
    'department' => $req['paid_by_dept'] ?? null,
    'at'         => in_array($st, ['paid','labor_disbursed']) ? ($req['updated_at'] ?? $req['payment_date'] ?? null) : null,
    'amount'     => $req['total_paid'] ?? $req['paid_amount'] ?? null,
    'note'       => $req['payment_note'] ?? null
];
?>

<style>
.timeline-step { position:relative; padding-left:44px; padding-bottom:20px; }
.timeline-step:not(:last-child)::before { content:''; position:absolute; left:15px; top:32px; width:2px; height:calc(100% - 8px); background:#e3e6f0; }
.timeline-step.done::before { background:var(--primary-color, #6c5ce7); }
.timeline-icon { position:absolute; left:0; top:0; width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:13px; background:#e3e6f0; color:#888; }
.timeline-step.done .timeline-icon { background:var(--step-color, #6c5ce7); color:#fff; }
.action-btn-group { display:flex; gap:8px; flex-wrap:wrap; }
.step-card { border:1.5px solid #e3e6f0; border-radius:10px; padding:14px 16px; background:#fff; }
.step-card.active-step { border-color:#6c5ce7; background:#f8f7ff; box-shadow:0 0 0 3px rgba(108,92,231,.12); }
</style>

<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h3 class="page-title fw-semibold fs-18 mb-0">
                <?php echo htmlspecialchars($req['requisition_no']); ?>
                <span class="badge <?php echo $statusBadge[$st] ?? 'bg-secondary'; ?> fs-11 ms-2 align-middle">
                    <?php echo $statusLabel[$st] ?? $st; ?>
                </span>
            </h3>
            <nav><ol class="breadcrumb mb-0 mt-1">
                <li class="breadcrumb-item"><a href="<?php echo $base; ?>/con-labor-requisitions">Labor Requisitions</a></li>
                <li class="breadcrumb-item active"><?php echo $req['requisition_no']; ?></li>
            </ol></nav>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <?php if (in_array($st, ['management_approved','partially_paid','paid','labor_disbursed'])): ?>
            <a href="<?php echo $base; ?>/print-debit-voucher?module=labor_requisition&id=<?php echo $req['id']; ?>" target="_blank" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-print me-1"></i> Debit Voucher
            </a>
            <?php endif; ?>
            <a href="<?php echo $base; ?>/con-labor-requisitions" class="btn btn-light btn-sm border">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if ($st === 'rejected'): ?>
        <div class="d-flex align-items-start gap-2.5 p-3 mb-4 rounded-3 border border-danger bg-danger-subtle text-dark lh-base">
            <i class="fa-solid fa-circle-xmark text-danger mt-1 flex-shrink-0"></i>
            <div><strong>Rejected:</strong> <?php echo htmlspecialchars($req['rejection_reason'] ?? 'No reason provided.'); ?></div>
        </div>
    <?php endif; ?>

    <!-- ACCOUNTS HEAD APPROVAL STATUS & ACTION CARD -->
    <?php 
    require_once __DIR__ . '/../../../models/ApprovalRequest.php';
    $ahInfo = ApprovalRequest::getAccountsHeadApprovalInfo($GLOBALS['db'] ?? null, 'con_labor_requisitions', (int)$req['id']);
    if (!empty($ahInfo['has_request'])) {
        $sourceTable = 'con_labor_requisitions';
        $sourceId = (int)$req['id'];
        include __DIR__ . '/../../layouts/accounts_head_approval_card.php';
    }
    ?>

    <div class="row g-4">

        <!-- LEFT: Details -->
        <div class="col-lg-4">

            <!-- Summary Card -->
            <div class="card custom-card mb-3">
                <div class="card-header py-3 px-4"><div class="card-title fs-13"><i class="fa-solid fa-hard-hat me-2 text-warning"></i> Requisition Info</div></div>
                <div class="card-body p-4">
                    <div class="row g-3 fs-13">
                        <div class="col-6">
                            <div class="text-muted fs-11 fw-semibold text-uppercase mb-1">Req. No</div>
                            <div class="fw-bold"><?php echo $req['requisition_no']; ?></div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted fs-11 fw-semibold text-uppercase mb-1">Date</div>
                            <div><?php echo date('d M Y', strtotime($req['requisition_date'])); ?></div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted fs-11 fw-semibold text-uppercase mb-1">Project</div>
                            <div class="fw-semibold"><?php echo htmlspecialchars($req['project_name'] ?? '---'); ?></div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted fs-11 fw-semibold text-uppercase mb-1">Phase</div>
                            <div><?php echo htmlspecialchars($req['phase_name'] ?? '---'); ?></div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted fs-11 fw-semibold text-uppercase mb-1">Requested By</div>
                            <div class="fw-semibold"><?php echo htmlspecialchars($req['requested_by_name'] ?? '---'); ?></div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted fs-11 fw-semibold text-uppercase mb-1">Labor Type</div>
                            <div><span class="badge bg-secondary-transparent text-dark"><?php echo $req['labor_type']; ?></span></div>
                        </div>

                        <?php if (!empty($req['work_date_from']) || !empty($req['work_date_to'])): ?>
                        <div class="col-12">
                            <div class="text-muted fs-11 fw-semibold text-uppercase mb-1">Work Period</div>
                            <div>
                                <?php echo !empty($req['work_date_from']) ? date('d M', strtotime($req['work_date_from'])) : '—'; ?>
                                —
                                <?php echo !empty($req['work_date_to']) ? date('d M Y', strtotime($req['work_date_to'])) : '—'; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="col-12"><hr class="my-1"></div>

                        <!-- Amounts -->
                        <div class="col-4 text-center">
                            <div class="text-muted fs-11 fw-semibold text-uppercase mb-1">Laborers</div>
                            <div class="fw-bold fs-18"><?php echo $req['labor_count']; ?></div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="text-muted fs-11 fw-semibold text-uppercase mb-1">Rate</div>
                            <div class="fw-bold fs-15">৳<?php echo number_format($req['per_labor_rate'], 0); ?></div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="text-muted fs-11 fw-semibold text-uppercase mb-1">Days</div>
                            <div class="fw-bold fs-18"><?php echo $req['working_days']; ?></div>
                        </div>

                        <div class="col-12">
                            <div class="p-3 rounded-2 text-center" style="background:linear-gradient(135deg,#e8f5e9,#f0f7ff)">
                                <div class="text-muted fs-11 fw-semibold text-uppercase mb-1">Total Requested</div>
                                <div class="fw-bold fs-20 text-success">৳ <?php echo number_format($req['total_amount'], 2); ?></div>
                            </div>
                        </div>

                        <?php if (!is_null($req['qc_approved_amount'])): ?>
                        <div class="col-12">
                            <div class="p-2 rounded text-center" style="background:rgba(32,201,151,.08);border:1px solid rgba(32,201,151,.25)">
                                <div class="fs-11 fw-semibold text-uppercase" style="color:#0f8964">QC Approved Amount</div>
                                <div class="fw-bold fs-18" style="color:#0f8964">৳ <?php echo number_format($req['qc_approved_amount'], 2); ?></div>
                                <?php if (!empty($req['qc_approved_by_name'])): ?>
                                    <div class="fs-10 text-muted">by <?= htmlspecialchars($req['qc_approved_by_name']) ?> <?= !empty($req['qc_approved_by_role']) ? '('.htmlspecialchars($req['qc_approved_by_role']).')' : '' ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (isset($req['pm_verified_amount']) && !is_null($req['pm_verified_amount'])): ?>
                        <div class="col-12">
                            <div class="p-2 rounded text-center" style="background:rgba(102,16,242,.08);border:1px solid rgba(102,16,242,.2)">
                                <div class="fs-11 fw-semibold text-uppercase text-indigo">PM Verified Amount</div>
                                <div class="fw-bold fs-18 text-indigo">৳ <?php echo number_format($req['pm_verified_amount'], 2); ?></div>
                                <?php if (!empty($req['pm_payment_approved_by_name'])): ?>
                                    <div class="fs-10 text-muted">by <?= htmlspecialchars($req['pm_payment_approved_by_name']) ?> <?= !empty($req['pm_payment_approved_by_role']) ? '('.htmlspecialchars($req['pm_payment_approved_by_role']).')' : '' ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!is_null($req['approved_amount'])): ?>
                        <div class="col-12">
                            <div class="p-2 rounded text-center bg-success bg-opacity-10 border border-success border-opacity-25">
                                <div class="text-success fs-11 fw-semibold text-uppercase">Management Approved Amount</div>
                                <div class="fw-bold fs-20 text-success">৳ <?php echo number_format($req['approved_amount'], 2); ?></div>
                                <?php if (!empty($req['management_approved_by_name'])): ?>
                                    <div class="fs-10 text-muted">by <?= htmlspecialchars($req['management_approved_by_name']) ?> <?= !empty($req['management_approved_by_role']) ? '('.htmlspecialchars($req['management_approved_by_role']).')' : '' ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php 
                        $showFinanceSummary = in_array($st, ['management_approved', 'partially_paid', 'paid', 'labor_disbursed']);
                        if ($showFinanceSummary):
                            $fApproved = (float)($req['approved_amount'] ?? $req['pm_verified_amount'] ?? $req['qc_approved_amount'] ?? $req['total_amount']);
                            $fPaid = (float)($req['total_paid'] ?? 0);
                            $fDue = max(0, $fApproved - $fPaid);
                        ?>
                        <div class="col-12">
                            <div class="p-2 rounded border" style="background:#fcfcfd">
                                <div class="d-flex justify-content-between align-items-center mb-1 fs-12">
                                    <span class="text-muted fw-semibold">Disbursed / Paid:</span>
                                    <span class="fw-bold text-primary">৳ <?= number_format($fPaid, 2) ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center fs-12">
                                    <span class="text-muted fw-semibold">Remaining Due:</span>
                                    <span class="fw-bold <?= $fDue > 0 ? 'text-danger' : 'text-success' ?>">৳ <?= number_format($fDue, 2) ?></span>
                                </div>
                                <?php if ($fDue <= 0 && ($fPaid > 0 || $st === 'paid')): ?>
                                    <div class="mt-1 text-center"><span class="badge bg-success-transparent text-success fs-10"><i class="fa-solid fa-circle-check me-1"></i>Fully Disbursed</span></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="col-12">
                            <div class="p-2.5 px-3 rounded-2 bg-light border text-muted fs-11 lh-base">
                                <strong class="text-dark">Amount in Words:</strong>
                                <div class="mt-0.5"><?= class_exists('FormatHelper') ? FormatHelper::amountInWords($req['approved_amount'] ?? $req['total_amount'] ?? 0) : (function_exists('amountInWords') ? amountInWords($req['approved_amount'] ?? $req['total_amount'] ?? 0) : '') ?></div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="text-muted fs-11 fw-semibold text-uppercase mb-1">Purpose</div>
                            <p class="mb-0 bg-light p-2.5 px-3 rounded-2 border fs-12 lh-base"><?php echo nl2br(htmlspecialchars($req['purpose'])); ?></p>
                        </div>
                        <?php if (!empty($req['remarks'])): ?>
                        <div class="col-12">
                            <div class="text-muted fs-11 fw-semibold text-uppercase mb-1">Remarks</div>
                            <p class="mb-0 fs-12 text-muted lh-base"><?php echo nl2br(htmlspecialchars($req['remarks'])); ?></p>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($req['attachment'])): ?>
                        <div class="col-12">
                            <a href="<?php echo $base . '/' . $req['attachment']; ?>" target="_blank" class="btn btn-sm btn-light border">
                                <i class="fa-solid fa-paperclip me-1"></i> View Attachment
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT: Timeline + Actions -->
        <div class="col-lg-8">

            <!-- Progress Timeline -->
            <div class="card custom-card mb-3">
                <div class="card-header py-3 px-4"><div class="card-title fs-14"><i class="fa-solid fa-timeline me-2 text-primary"></i> Approval Timeline</div></div>
                <div class="card-body p-4">
                    <div class="ps-1">
                        <?php foreach ($steps as $step): ?>
                        <div class="timeline-step <?php echo $step['done'] ? 'done' : ''; ?>">
                            <div class="timeline-icon" style="<?php echo $step['done'] ? 'background:#'.($step['color'] === 'primary' ? '6c5ce7' : ($step['color'] === 'info' ? '17a2b8' : ($step['color'] === 'success' ? '28a745' : ($step['color'] === 'warning' ? 'ffc107' : ($step['color'] === 'teal' ? '20c997' : ($step['color'] === 'indigo' ? '6610f2' : '6c757d')))))) . '!important' : ''; ?>">
                                <i class="fa-solid <?php echo $step['icon']; ?>"></i>
                            </div>
                            <div class="ms-1">
                                <div class="d-flex align-items-center flex-wrap gap-1 mb-1">
                                    <span class="fw-bold fs-13 <?php echo !$step['done'] ? 'text-muted' : 'text-dark'; ?>">
                                        <?php echo $step['title']; ?>
                                    </span>
                                    <?php if ($step['done'] && !empty($step['amount'])): ?>
                                        <span class="badge bg-success text-white fw-bold fs-11 ms-1">৳ <?= number_format((float)$step['amount'], 2) ?></span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($step['done'] && !empty($step['user'])): ?>
                                <div class="d-flex align-items-center flex-wrap gap-1 mb-1 fs-12">
                                    <span class="fw-semibold text-primary"><i class="fa-solid fa-user-check me-1"></i><?= htmlspecialchars($step['user']); ?></span>
                                    <?php if (!empty($step['department'])): ?>
                                        <span class="badge bg-primary-transparent text-primary fs-10"><i class="fa-solid fa-building me-1"></i><?= htmlspecialchars($step['department']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($step['role'])): ?>
                                        <span class="badge bg-light text-dark border fs-10"><i class="fa-solid fa-id-badge me-1"></i><?= htmlspecialchars($step['role']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($step['at'])): ?>
                                    <?php 
                                    $ts = strtotime($step['at']);
                                    $dateStr = (date('H:i:s', $ts) === '00:00:00') ? date('d M Y', $ts) : date('d M Y, h:i A', $ts);
                                    ?>
                                    <div class="fs-11 text-muted"><i class="fa-regular fa-clock me-1"></i><?php echo $dateStr; ?></div>
                                <?php endif; ?>
                                <?php if (!empty($step['note'])): ?>
                                    <div class="fs-11 text-dark fst-italic mt-1 p-2 bg-light rounded border"><i class="fa-solid fa-comment-dots me-1 text-muted"></i><?php echo htmlspecialchars($step['note']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- ====== ACTION PANELS ====== -->

            <!-- SE / Owner / Admin: Delete Requisition (status=pending or rejected) -->
            <?php if (($st === 'pending' || $st === 'rejected') && ($isOwner || ($_SESSION['user']['role_id'] ?? 0) == 1)): ?>
            <div class="card custom-card mb-3 border border-danger border-opacity-50">
                <div class="card-header bg-danger-transparent">
                    <div class="card-title fs-13 text-danger"><i class="fa-solid fa-trash me-2"></i> Delete Requisition</div>
                </div>
                <div class="card-body">
                    <p class="fs-13 text-muted mb-3">This requisition is not approved yet. You can delete it permanently.</p>
                    <form action="<?php echo $base; ?>/con-labor-requisition-delete/<?php echo $req['id']; ?>" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this requisition? This action cannot be undone.')">
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="fa-solid fa-trash me-1"></i> Delete Requisition
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- STEP 1: PM / PM Head Approve (status=pending) -->
            <?php if ($st === 'pending' && $isPM): ?>
            <div class="card custom-card mb-3 border border-info border-opacity-50">
                <div class="card-header bg-info-transparent">
                    <div class="card-title fs-13 text-info"><i class="fa-solid fa-stamp me-2"></i> Project Manager / PM Head Action</div>
                </div>
                <div class="card-body">
                    <p class="fs-13 text-muted mb-3">Review and approve or reject this labor requisition.</p>
                    <div class="action-btn-group">
                        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#pmApproveModal">
                            <i class="fa-solid fa-thumbs-up me-1"></i> Approve Requisition
                        </button>
                        <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#pmRejectModal">
                            <i class="fa-solid fa-thumbs-down me-1"></i> Reject
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- STEP 2: SE marks work complete (status=pm_approved, owner only) -->
            <?php if ($st === 'pm_approved' && ($isOwner || $isSE)): ?>
            <div class="card custom-card mb-3 border border-primary border-opacity-50">
                <div class="card-header bg-primary-transparent">
                    <div class="card-title fs-13 text-primary"><i class="fa-solid fa-hammer me-2"></i> Mark Work as Completed</div>
                </div>
                <div class="card-body">
                    <p class="fs-13 text-muted mb-3">Once all labor work is done at site, mark it as completed before requesting payment.</p>
                    <form action="<?php echo $base; ?>/con-labor-req-work-complete/<?php echo $req['id']; ?>" method="POST" onsubmit="return confirm('Confirm that work is completed?')">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-circle-check me-1"></i> Mark Work Complete
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- STEP 3: Request Payment (status=work_completed) -->
            <?php if ($st === 'work_completed' && ($isOwner || $isSE)): ?>
            <div class="card custom-card mb-3 border border-warning border-opacity-50">
                <div class="card-header bg-warning-transparent">
                    <div class="card-title fs-13 text-warning"><i class="fa-solid fa-hand-holding-dollar me-2"></i> Request Payment</div>
                </div>
                <div class="card-body">
                    <p class="fs-13 text-muted mb-3">Work is done. Submit a payment request to start the payment approval process.</p>
                    <form action="<?php echo $base; ?>/con-labor-req-payment-request/<?php echo $req['id']; ?>" method="POST" onsubmit="return confirm('Submit payment request?')">
                        <button type="submit" class="btn btn-warning btn-sm text-dark fw-semibold">
                            <i class="fa-solid fa-paper-plane me-1"></i> Request Payment
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- STEP 4: QC Approve Payment (status=payment_requested, strictly Role 24 & Admin) -->
            <?php if ($st === 'payment_requested' && $isQC): ?>
            <div class="card custom-card mb-3 border border-teal border-opacity-50" style="border-color:#20c997!important">
                <div class="card-header" style="background:rgba(32,201,151,.1)">
                    <div class="card-title fs-13" style="color:#20c997"><i class="fa-solid fa-magnifying-glass-check me-2"></i> QC & Internal Audit Head Review & Approval</div>
                </div>
                <div class="card-body">
                    <p class="fs-13 text-muted mb-2">QC & Internal Audit verification is required. You can enter or adjust the verified amount to forward to Project Manager / PM Head.</p>
                    <div class="p-2 rounded mb-3 bg-light border d-flex justify-content-between align-items-center">
                        <span class="text-muted fs-12 fw-semibold">Requested Amount:</span>
                        <span class="fs-16 fw-bold text-dark">৳ <?= number_format($req['total_amount'], 2) ?></span>
                    </div>
                    <div class="action-btn-group">
                        <button class="btn btn-sm fw-semibold text-white" style="background:#20c997" data-bs-toggle="modal" data-bs-target="#qcApproveModal">
                            <i class="fa-solid fa-check me-1"></i> QC Approve & Forward
                        </button>
                        <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#qcRejectModal">
                            <i class="fa-solid fa-xmark me-1"></i> Reject
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            
            <!-- STEP 5: Management & Role 20 Payment Approval (After QC Approval) -->
            <?php 
            $mgmtBaseAmount = (float)($req['qc_approved_amount'] ?? $req['pm_verified_amount'] ?? $req['total_amount']);
            $canApproveMgmt = !empty($canApproveManagement);
            $isOver1LacLabor = ($mgmtBaseAmount > 10000);
            $isOpHeadApprovedLabor = (!empty($ahInfo['is_op_head_approved']) || ($ahInfo['op_head_status'] ?? '') === 'approved');
            $isAhApprovedLabor = (!empty($ahInfo['is_accounts_head_approved']) || ($ahInfo['accounts_head_status'] ?? '') === 'approved');
            $roleNameLaborLc = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
            $isOpHeadUserLabor = (in_array((int)$userRole, [20, 32], true) || str_contains($roleNameLaborLc, 'operation head') || str_contains($roleNameLaborLc, 'admin & operation'));
            $isMgmtUserLabor = in_array((int)$userRole, [1, 3, 38], true) || in_array($roleNameLaborLc, ['super admin', 'admin', 'management', 'board of director', 'board of directors'], true) || str_contains($roleNameLaborLc, 'director') || str_contains($roleNameLaborLc, 'dceo');
            $isMgmtLockedLabor = false;
            $showMgmtCardLabor = ($isMgmtUserLabor && in_array($st, ['pending', 'pm_approved', 'work_completed', 'payment_requested', 'qc_approved', 'pm_payment_approved'], true))
                || (in_array($st, ['qc_approved', 'pm_payment_approved'], true) && $canApproveMgmt && (!$isOpHeadUserLabor || !$isOpHeadApprovedLabor));
            ?>
            <?php if ($showMgmtCardLabor): ?>
            <div class="card custom-card mb-3 border border-success border-opacity-50">
                <div class="card-header py-3 px-4 bg-success-transparent d-flex justify-content-between align-items-center">
                    <div class="card-title fs-13 text-success mb-0"><i class="fa-solid fa-building-circle-check me-2"></i> Management / Role 20 Approval</div>
                    <?php if ($st === 'qc_approved'): ?>
                        <span class="badge bg-teal text-white fs-11"><i class="fa-solid fa-shield-check me-1"></i> QC Verified</span>
                    <?php elseif ($st === 'pm_payment_approved'): ?>
                        <span class="badge bg-indigo text-white fs-11"><i class="fa-solid fa-user-check me-1"></i> PM Payment Verified</span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($req['qc_approved_by_name'])): ?>
                    <!-- QC Approval Details Highlight -->
                    <div class="p-3 mb-3 rounded border" style="background:#f4fbf7;border-color:#bbf7d0!important">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-success text-white fs-11 fw-semibold"><i class="fa-solid fa-shield-check me-1"></i> QC & Internal Audit Approved Information</span>
                            <span class="text-muted fs-11"><?= !empty($req['qc_approved_at']) ? date('d M Y, h:i A', strtotime($req['qc_approved_at'])) : '' ?></span>
                        </div>
                        <div class="row g-2">
                            <div class="col-sm-6">
                                <div class="text-muted fs-11 text-uppercase fw-semibold">Approved By (QC)</div>
                                <div class="fw-bold text-dark fs-13">
                                    <i class="fa-solid fa-user-check text-success me-1"></i>
                                    <?= htmlspecialchars($req['qc_approved_by_name']) ?>
                                    <?php if (!empty($req['qc_approved_by_dept'])): ?>
                                        <span class="badge bg-primary-transparent text-primary fs-10 ms-1"><?= htmlspecialchars($req['qc_approved_by_dept']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($req['qc_approved_by_role'])): ?>
                                        <span class="badge bg-light text-dark border fs-10 ms-1"><?= htmlspecialchars($req['qc_approved_by_role']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-muted fs-11 text-uppercase fw-semibold">QC Approved Amount</div>
                                <div class="fw-bold text-success fs-18">
                                    ৳ <?= number_format((float)($req['qc_approved_amount'] ?? $req['total_amount']), 2) ?>
                                </div>
                            </div>
                            <?php if (!empty($req['qc_note'])): ?>
                            <div class="col-12">
                                <div class="text-muted fs-11 text-uppercase fw-semibold">QC Remarks</div>
                                <div class="fs-12 text-dark bg-white p-2 rounded border mt-1 fst-italic">
                                    "<?= htmlspecialchars($req['qc_note']) ?>"
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Verification Breakdown -->
                    <div class="row g-2 mb-3">
                        <div class="col-6 text-center p-2 rounded bg-light border">
                            <div class="fs-11 text-muted fw-semibold">Requested Amount</div>
                            <div class="fw-bold fs-15 text-dark">৳ <?= number_format($req['total_amount'], 2) ?></div>
                        </div>
                        <div class="col-6 text-center p-2 rounded" style="background:rgba(32,201,151,.1)">
                            <div class="fs-11 text-muted fw-semibold"><?= !empty($req['qc_approved_amount']) ? 'QC Approved Amount' : 'Amount to Authorize' ?></div>
                            <div class="fw-bold fs-15 text-success">৳ <?= number_format($mgmtBaseAmount, 2) ?></div>
                        </div>
                    </div>

                    <?php if ($isMgmtLockedLabor): ?>
                        <div class="d-flex align-items-start gap-2.5 p-3 mb-3 rounded-2 border border-warning bg-warning-subtle text-dark fs-12 lh-base">
                            <i class="fa-solid fa-lock text-warning mt-0.5 flex-shrink-0"></i>
                            <div><strong>Management Approval Locked (&gt; ৳10,000):</strong> <?= $mgmtLockMsgLabor ?></div>
                        </div>
                    <?php else: ?>
                        <p class="fs-13 text-muted mb-3">Review and authorize payment disbursement for Accounts. You may adjust the approved amount if needed.</p>
                    <?php endif; ?>

                    <div class="row gx-2">
                        <div class="col-6">
                            <?php if ($isMgmtLockedLabor): ?>
                                <button type="button" class="btn btn-secondary w-100 fw-bold py-2 disabled" title="Awaiting Admin & Operation Head approval">
                                    <i class="fa-solid fa-lock me-1"></i> Locked (> ৳10,000)
                                </button>
                            <?php elseif ($isOver1LacLabor && !$isOpHeadApprovedLabor && $isOpHeadUserLabor): ?>
                                <button type="button" class="btn btn-success w-100 fw-bold py-2" data-bs-toggle="modal" data-bs-target="#mgmtApproveModal">
                                    <i class="fa-solid fa-check-double me-1"></i> Verify &amp; Approve (Op Head)
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-success w-100 fw-bold py-2" data-bs-toggle="modal" data-bs-target="#mgmtApproveModal">
                                    <i class="fa-solid fa-check-double me-1"></i> Approve Payment
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="col-6">
                            <button type="button" class="btn btn-danger w-100 fw-bold py-2" data-bs-toggle="modal" data-bs-target="#mgmtRejectModal">
                                <i class="fa-solid fa-ban me-1"></i> Reject
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- STEP 7a: Partially Paid — Management must approve remaining -->
            <?php if ($st === 'partially_paid' && $isManagement): ?>
            <?php
                $approvedAmt  = (float)($req['approved_amount'] ?? $req['pm_verified_amount'] ?? $req['qc_approved_amount'] ?? $req['total_amount']);
                $totalPaidAmt = (float)($req['total_paid'] ?? 0);
                $remainingAmt = max(0, $approvedAmt - $totalPaidAmt);
            ?>
            <div class="card custom-card mb-3" style="border:1.5px solid #fd7e14!important">
                <div class="card-header" style="background:rgba(253,126,20,.1)">
                    <div class="card-title fs-13" style="color:#fd7e14"><i class="fa-solid fa-clock-rotate-left me-2"></i> Partial Payment — Approve Remaining Balance</div>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-3">
                        <div class="col-4 text-center p-2 rounded" style="background:rgba(40,167,69,.1)">
                            <div class="fs-11 text-muted fw-semibold">Total Approved</div>
                            <div class="fw-bold fs-15 text-success">৳ <?= number_format($approvedAmt, 2) ?></div>
                        </div>
                        <div class="col-4 text-center p-2 rounded" style="background:rgba(13,110,253,.1)">
                            <div class="fs-11 text-muted fw-semibold">Already Paid</div>
                            <div class="fw-bold fs-15 text-primary">৳ <?= number_format($totalPaidAmt, 2) ?></div>
                        </div>
                        <div class="col-4 text-center p-2 rounded" style="background:rgba(253,126,20,.1)">
                            <div class="fs-11 text-muted fw-semibold">Remaining Due</div>
                            <div class="fw-bold fs-15" style="color:#fd7e14">৳ <?= number_format($remainingAmt, 2) ?></div>
                        </div>
                    </div>
                    <button class="btn btn-sm fw-semibold" style="background:#fd7e14;color:#fff" data-bs-toggle="modal" data-bs-target="#mgmtApproveRemainingModal">
                        <i class="fa-solid fa-check me-1"></i> Approve Remaining ৳ <?= number_format($remainingAmt, 2) ?>
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <!-- STEP 7b: Accounts Pay (management_approved OR partially_paid re-approved) -->
            <?php
            $approvedAmt  = (float)($req['approved_amount'] ?? $req['pm_verified_amount'] ?? $req['qc_approved_amount'] ?? $req['total_amount']);
            $totalPaidAmt = (float)($req['total_paid'] ?? 0);
            $remainingAmt = max(0, $approvedAmt - $totalPaidAmt);
            ?>
            <?php if (in_array($st, ['management_approved','partially_paid']) && $isAccounts): ?>
            <div class="card custom-card mb-3 border border-primary border-opacity-50">
                <div class="card-header bg-primary-transparent">
                    <div class="card-title fs-13 text-primary"><i class="fa-solid fa-money-bill-wave me-2"></i>
                        <?= $st === 'partially_paid' ? 'Accounts: Process Remaining Payment' : 'Accounts: Disburse Payment' ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-3">
                        <div class="col-4 text-center p-2 rounded" style="background:rgba(40,167,69,.1)">
                            <div class="fs-11 text-muted fw-semibold">Approved Amount</div>
                            <div class="fw-bold fs-15 text-success">৳ <?= number_format($approvedAmt, 2) ?></div>
                        </div>
                        <div class="col-4 text-center p-2 rounded" style="background:rgba(13,110,253,.1)">
                            <div class="fs-11 text-muted fw-semibold">Disbursed / Paid</div>
                            <div class="fw-bold fs-15 text-primary">৳ <?= number_format($totalPaidAmt, 2) ?></div>
                        </div>
                        <div class="col-4 text-center p-2 rounded" style="background:rgba(220,53,69,.1)">
                            <div class="fs-11 text-danger fw-semibold">Remaining Due</div>
                            <div class="fw-bold fs-15 text-danger">৳ <?= number_format($remainingAmt, 2) ?></div>
                        </div>
                    </div>
                    <?php if (!empty($ahInfo['is_accounts_head_approved'])): ?>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#payModal">
                        <i class="fa-solid fa-credit-card me-1"></i> Disburse Payment (Due: ৳ <?= number_format($remainingAmt, 2) ?>)
                    </button>
                    <?php else: ?>
                    <button class="btn btn-sm btn-light text-muted border" disabled title="Awaiting Accounts Head approval">
                        <i class="fa-solid fa-lock text-warning me-1"></i> Payment Locked (Awaiting Accounts Head Approval)
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Payment History (always visible if any payments made) -->
            <?php $paymentLogs = $paymentLogs ?? []; ?>
            <?php if (!empty($paymentLogs)): ?>
            <div class="card custom-card mb-3">
                <div class="card-header" style="background:rgba(40,167,69,.05);border-color:rgba(40,167,69,.2)">
                    <div class="card-title fs-13 text-success"><i class="fa-solid fa-receipt me-2"></i> Payment History</div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0 fs-12">
                        <thead class="table-light">
                            <tr><th>#</th><th>Date</th><th>Amount</th><th>Method</th><th>Reference</th><th>By</th><th>Note</th><th>Proof</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($paymentLogs as $li => $log): ?>
                        <tr>
                            <td class="text-muted"><?= $li + 1 ?></td>
                            <td><?= date('d M Y', strtotime($log['payment_date'])) ?></td>
                            <td class="fw-bold text-success">৳ <?= number_format($log['paid_amount'], 2) ?></td>
                            <td><span class="badge bg-secondary-transparent text-dark fs-10"><?= htmlspecialchars($log['payment_method']) ?></span></td>
                            <td class="text-muted"><?= htmlspecialchars($log['payment_reference'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($log['paid_by_name'] ?? '—') ?></td>
                            <td style="max-width:120px;white-space:normal"><?= htmlspecialchars($log['payment_note'] ?? '—') ?></td>
                            <td>
                                <?php if (!empty($log['proof_image'])): ?>
                                    <?php $ext = strtolower(pathinfo($log['proof_image'], PATHINFO_EXTENSION)); ?>
                                    <?php if (in_array($ext, ['jpg','jpeg','png','gif'])): ?>
                                        <a href="/<?= htmlspecialchars($log['proof_image']) ?>" target="_blank">
                                            <img src="/<?= htmlspecialchars($log['proof_image']) ?>" style="width:40px;height:40px;object-fit:cover;border-radius:4px;border:1px solid #ddd" alt="proof">
                                        </a>
                                    <?php else: ?>
                                        <a href="/<?= htmlspecialchars($log['proof_image']) ?>" target="_blank" class="btn btn-xs btn-light border fs-10">
                                            <i class="fa-solid fa-file-pdf text-danger"></i> View
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted fs-11">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="2" class="fw-bold text-end">Total Paid:</td>
                                <td class="fw-bold text-success">৳ <?= number_format(array_sum(array_column($paymentLogs, 'paid_amount')), 2) ?></td>
                                <td colspan="5"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- ✅ Full Payment Settled — visible to all esp. Site Engineer -->
            <?php if ($st === 'paid'): ?>
            <?php
                $approvedAmt  = (float)($req['approved_amount'] ?? $req['total_amount']);
                $totalPaidAmt = (float)($req['total_paid'] ?? 0);
            ?>
            <div class="card custom-card mb-3" style="border:2px solid #28a745!important;background:linear-gradient(135deg,rgba(40,167,69,.06),rgba(40,167,69,.02))">
                <div class="card-header" style="background:rgba(40,167,69,.12)">
                    <div class="card-title fs-14 text-success fw-bold">
                        <i class="fa-solid fa-circle-check me-2 fs-16"></i> Payment Fully Settled
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3 text-center">
                        <div class="col-4">
                            <div class="fs-11 text-muted fw-semibold">Total Approved</div>
                            <div class="fw-bold fs-16 text-success">৳ <?= number_format($approvedAmt, 2) ?></div>
                        </div>
                        <div class="col-4">
                            <div class="fs-11 text-muted fw-semibold">Total Paid</div>
                            <div class="fw-bold fs-16 text-success">৳ <?= number_format($totalPaidAmt, 2) ?></div>
                        </div>
                        <div class="col-4">
                            <div class="fs-11 text-muted fw-semibold">Paid By</div>
                            <div class="fw-semibold fs-13"><?= htmlspecialchars($req['paid_by_name'] ?? '—') ?></div>
                            <div class="fs-11 text-muted"><?= $req['payment_date'] ? date('d M Y', strtotime($req['payment_date'])) : '' ?></div>
                        </div>
                    </div>
                    <?php if (!empty($paymentLogs)): ?>
                    <div class="fs-12 text-muted mb-2 fw-semibold"><i class="fa-solid fa-paperclip me-1"></i>Payment Proofs:</div>
                    <div class="d-flex gap-2 flex-wrap">
                        <?php foreach ($paymentLogs as $log): ?>
                            <?php if (!empty($log['proof_image'])): ?>
                                <?php $ext = strtolower(pathinfo($log['proof_image'], PATHINFO_EXTENSION)); ?>
                                <?php if (in_array($ext, ['jpg','jpeg','png','gif'])): ?>
                                    <a href="/<?= htmlspecialchars($log['proof_image']) ?>" target="_blank" title="৳<?= number_format($log['paid_amount'],0) ?> — <?= date('d M', strtotime($log['payment_date'])) ?>">
                                        <img src="/<?= htmlspecialchars($log['proof_image']) ?>" style="width:64px;height:64px;object-fit:cover;border-radius:8px;border:2px solid #28a745" alt="proof">
                                    </a>
                                <?php else: ?>
                                    <a href="/<?= htmlspecialchars($log['proof_image']) ?>" target="_blank" class="btn btn-sm btn-outline-success">
                                        <i class="fa-solid fa-file-pdf me-1"></i> Receipt (৳<?= number_format($log['paid_amount'],0) ?>)
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- SE: Confirm Disbursement Button (only when status=paid) -->
                    <?php if ($isSE): ?>
                    <hr class="my-3">
                    <div class="d-flex align-items-center gap-3">
                        <div>
                            <div class="fs-12 text-muted">Accounts-এর কাছ থেকে টাকা পেয়েছেন?</div>
                            <div class="fs-11 text-muted">Labour-দের payment দিলে নিচের button click করুন এবং proof upload করুন।</div>
                        </div>
                        <button class="btn btn-sm fw-semibold text-nowrap" style="background:#0d6efd;color:#fff;min-width:180px" data-bs-toggle="modal" data-bs-target="#disbursementModal">
                            <i class="fa-solid fa-hand-holding-dollar me-1"></i> Confirm Labour Payment
                        </button>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
            <?php endif; ?>

            <!-- ✅ Labour Disbursed Confirmation Card -->
            <?php if ($st === 'labor_disbursed'): ?>
            <?php
                $logsSum = !empty($paymentLogs) ? array_sum(array_column($paymentLogs, 'paid_amount')) : (float)($req['total_paid'] ?? $req['approved_amount'] ?? 0);
            ?>
            <div class="card custom-card mb-3" style="border:2px solid #28a745!important;background:linear-gradient(135deg,rgba(40,167,69,.08),rgba(40,167,69,.02))">
                <div class="card-header" style="background:rgba(40,167,69,.15)">
                    <div class="card-title fs-14 text-success fw-bold">
                        <i class="fa-solid fa-users-check me-2 fs-16"></i> ✅ Labour Payment Fully Disbursed
                    </div>
                </div>
                <div class="card-body">

                    <!-- Section 1: Accounts Payment -->
                    <div class="mb-3 pb-3" style="border-bottom:1px dashed #dee2e6">
                        <div class="fs-12 fw-semibold text-muted mb-2"><i class="fa-solid fa-building me-1"></i> Accounts Payment</div>
                        <div class="row g-2 text-center">
                            <div class="col-4 p-2 rounded" style="background:rgba(40,167,69,.1)">
                                <div class="fs-11 text-muted fw-semibold">Total Paid</div>
                                <div class="fw-bold fs-15 text-success">৳ <?= number_format($logsSum, 2) ?></div>
                            </div>
                            <div class="col-4 p-2 rounded" style="background:rgba(13,110,253,.08)">
                                <div class="fs-11 text-muted fw-semibold">Paid By</div>
                                <div class="fw-semibold fs-12"><?= htmlspecialchars($req['paid_by_name'] ?? '—') ?></div>
                            </div>
                            <div class="col-4 p-2 rounded" style="background:rgba(13,110,253,.08)">
                                <div class="fs-11 text-muted fw-semibold">Payment Date</div>
                                <div class="fw-semibold fs-12"><?= !empty($req['payment_date']) ? date('d M Y', strtotime($req['payment_date'])) : '—' ?></div>
                            </div>
                        </div>
                        <?php if (!empty($paymentLogs)): ?>
                        <div class="d-flex gap-2 flex-wrap mt-2">
                            <?php foreach ($paymentLogs as $log): if (empty($log['proof_image'])) continue; ?>
                            <?php $pext = strtolower(pathinfo($log['proof_image'], PATHINFO_EXTENSION)); ?>
                            <?php if (in_array($pext, ['jpg','jpeg','png','gif'])): ?>
                                <a href="/<?= htmlspecialchars($log['proof_image']) ?>" target="_blank" title="৳<?= number_format($log['paid_amount'],0) ?>">
                                    <img src="/<?= htmlspecialchars($log['proof_image']) ?>" style="width:48px;height:48px;object-fit:cover;border-radius:6px;border:2px solid #28a745">
                                </a>
                            <?php else: ?>
                                <a href="/<?= htmlspecialchars($log['proof_image']) ?>" target="_blank" class="btn btn-sm btn-outline-success fs-11">
                                    <i class="fa-solid fa-file-pdf me-1"></i> Receipt
                                </a>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Section 2: SE Disbursement -->
                    <div>
                        <div class="fs-12 fw-semibold text-muted mb-2"><i class="fa-solid fa-user-hard-hat me-1"></i> Site Engineer Disbursement</div>
                        <div class="row g-2 text-center mb-2">
                            <div class="col-4 p-2 rounded" style="background:rgba(40,167,69,.1)">
                                <div class="fs-11 text-muted fw-semibold">Disbursed By (SE)</div>
                                <div class="fw-semibold fs-12"><?= htmlspecialchars($req['labor_disbursed_by_name'] ?? '—') ?></div>
                            </div>
                            <div class="col-4 p-2 rounded" style="background:rgba(40,167,69,.08)">
                                <div class="fs-11 text-muted fw-semibold">Disbursed On</div>
                                <div class="fw-semibold fs-12"><?= !empty($req['labor_disbursed_at']) ? date('d M Y', strtotime($req['labor_disbursed_at'])) : '—' ?></div>
                            </div>
                            <div class="col-4 p-2 rounded" style="background:rgba(40,167,69,.08)">
                                <div class="fs-11 text-muted fw-semibold">Labourers</div>
                                <div class="fw-bold fs-15 text-success"><?= $req['labor_count'] ?> জন</div>
                            </div>
                        </div>
                        <?php if (!empty($req['labor_disbursement_note'])): ?>
                        <div class="fs-12 text-muted mb-2"><i class="fa-solid fa-note-sticky me-1"></i> <strong>Note:</strong> <?= htmlspecialchars($req['labor_disbursement_note']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($req['labor_disbursement_proof'])): ?>
                        <?php $dext = strtolower(pathinfo($req['labor_disbursement_proof'], PATHINFO_EXTENSION)); ?>
                        <div class="fs-12 text-muted mb-2 fw-semibold"><i class="fa-solid fa-image me-1"></i>Disbursement Proof:</div>
                        <?php if (in_array($dext, ['jpg','jpeg','png','gif'])): ?>
                            <a href="/<?= htmlspecialchars($req['labor_disbursement_proof']) ?>" target="_blank">
                                <img src="/<?= htmlspecialchars($req['labor_disbursement_proof']) ?>" style="max-width:200px;max-height:150px;object-fit:cover;border-radius:8px;border:2px solid #28a745" alt="disbursement proof">
                            </a>
                        <?php else: ?>
                            <a href="/<?= htmlspecialchars($req['labor_disbursement_proof']) ?>" target="_blank" class="btn btn-sm btn-outline-success">
                                <i class="fa-solid fa-file-pdf me-1"></i> View Disbursement Proof
                            </a>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
            <?php endif; ?>

            <!-- ⚠️ Partially Paid notice — visible to SE -->
            <?php if ($st === 'partially_paid' && $isSE): ?>
            <?php
                $approvedAmt  = (float)($req['approved_amount'] ?? $req['total_amount']);
                $totalPaidAmt = (float)($req['total_paid'] ?? 0);
                $remainingAmt = $approvedAmt - $totalPaidAmt;
            ?>
            <div class="card custom-card mb-3" style="border:1.5px solid #fd7e14!important;background:rgba(253,126,20,.04)">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="fa-solid fa-triangle-exclamation text-warning fs-18"></i>
                        <span class="fw-bold fs-14" style="color:#fd7e14">Partial Payment Received</span>
                    </div>
                    <div class="row g-2 text-center">
                        <div class="col-4 p-2 rounded" style="background:rgba(40,167,69,.1)">
                            <div class="fs-11 text-muted">Total Approved</div>
                            <div class="fw-bold text-success">৳ <?= number_format($approvedAmt, 2) ?></div>
                        </div>
                        <div class="col-4 p-2 rounded" style="background:rgba(13,110,253,.1)">
                            <div class="fs-11 text-muted">Paid So Far</div>
                            <div class="fw-bold text-primary">৳ <?= number_format($totalPaidAmt, 2) ?></div>
                        </div>
                        <div class="col-4 p-2 rounded" style="background:rgba(253,126,20,.1)">
                            <div class="fs-11 text-muted">Remaining</div>
                            <div class="fw-bold" style="color:#fd7e14">৳ <?= number_format($remainingAmt, 2) ?></div>
                        </div>
                    </div>
                    <p class="fs-12 text-muted mt-2 mb-0">Management is reviewing the remaining balance. You will be notified once full payment is processed.</p>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- col-lg-8 -->
    </div><!-- row -->
</div>

<!-- =============================================================== -->
<!-- MODALS -->
<!-- =============================================================== -->

<!-- PM Approve -->
<?php if ($st === 'pending' && $isPM): ?>
<div class="modal fade" id="pmApproveModal" tabindex="-1">
    <div class="modal-dialog"><form action="<?php echo $base; ?>/con-labor-req-pm-approve/<?php echo $req['id']; ?>" method="POST">
        <div class="modal-content">
            <div class="modal-header bg-success bg-opacity-10"><h5 class="modal-title text-success"><i class="fa-solid fa-thumbs-up me-2"></i>PM / PM Head Approve</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="alert alert-info py-2 fs-12 mb-3">
                    Approving: <strong><?php echo $req['labor_count']; ?> laborers</strong> × ৳<?php echo number_format($req['per_labor_rate'],0); ?> × <?php echo $req['working_days']; ?> days = <strong>৳ <?php echo number_format($req['total_amount'],2); ?></strong>
                </div>
                <div class="mb-3"><label class="form-label">Note (Optional)</label><textarea name="pm_note" class="form-control" rows="2" placeholder="Any remarks..."></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success btn-sm">Approve</button></div>
        </div>
    </form></div>
</div>

<!-- PM Reject -->
<div class="modal fade" id="pmRejectModal" tabindex="-1">
    <div class="modal-dialog"><form action="<?php echo $base; ?>/con-labor-req-pm-reject/<?php echo $req['id']; ?>" method="POST">
        <div class="modal-content">
            <div class="modal-header bg-danger bg-opacity-10"><h5 class="modal-title text-danger"><i class="fa-solid fa-thumbs-down me-2"></i>Reject Requisition (PM / PM Head)</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Rejection Reason <span class="text-danger">*</span></label><textarea name="rejection_reason" class="form-control" rows="3" required></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger btn-sm">Reject</button></div>
        </div>
    </form></div>
</div>
<?php endif; ?>

<!-- QC Approve Modal (Strictly Role 24 & Super Admin) -->
<?php if ($st === 'payment_requested' && $isQC): ?>
<div class="modal fade" id="qcApproveModal" tabindex="-1">
    <div class="modal-dialog"><form action="<?php echo $base; ?>/con-labor-req-qc-approve/<?php echo $req['id']; ?>" method="POST">
        <div class="modal-content">
            <div class="modal-header" style="background:rgba(32,201,151,.1)"><h5 class="modal-title" style="color:#20c997"><i class="fa-solid fa-shield-check me-2"></i>QC & Internal Audit Head Approval</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="alert alert-info py-2 fs-12 mb-3">
                    Requested Amount: <strong>৳ <?= number_format($req['total_amount'], 2) ?></strong> (<?= $req['labor_count'] ?> laborers × ৳<?= number_format($req['per_labor_rate'], 0) ?> × <?= $req['working_days'] ?> days)
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">QC Approved / Forward Amount (৳) <span class="text-danger">*</span></label>
                    <input type="number" name="qc_approved_amount" class="form-control" step="0.01" min="0.01" value="<?php echo htmlspecialchars($req['qc_approved_amount'] ?? $req['total_amount']); ?>" required>
                    <div class="form-text text-muted">Type or adjust the amount to forward to Project Manager / PM Head.</div>
                </div>
                <div class="mb-3"><label class="form-label">QC Note / Observation (Optional)</label><textarea name="qc_note" class="form-control" rows="2" placeholder="QC verification notes..."></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-sm fw-semibold text-white" style="background:#20c997">Approve & Forward</button></div>
        </div>
    </form></div>
</div>
<div class="modal fade" id="qcRejectModal" tabindex="-1">
    <div class="modal-dialog"><form action="<?php echo $base; ?>/con-labor-req-qc-reject/<?php echo $req['id']; ?>" method="POST">
        <div class="modal-content">
            <div class="modal-header bg-danger bg-opacity-10"><h5 class="modal-title text-danger">QC & Internal Audit Rejection</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Reason <span class="text-danger">*</span></label><textarea name="rejection_reason" class="form-control" rows="3" required></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger btn-sm">Reject</button></div>
        </div>
    </form></div>
</div>
<?php endif; ?>

<!-- Management Approve Modal (After QC Approval or Management Direct Override) -->
<?php
$laborOverrideStage = '';
if (!empty($isMgmtUserLabor)) {
    if ($st === 'pending') {
        $laborOverrideStage = 'PM / Dept Head';
    } elseif (in_array($st, ['pm_approved', 'work_completed', 'payment_requested'], true)) {
        $laborOverrideStage = 'QC & Internal Audit';
    } elseif (!$isOpHeadApprovedLabor) {
        $laborOverrideStage = 'Head of Operation';
    } elseif (!$isAhApprovedLabor) {
        $laborOverrideStage = 'Accounts Head';
    }
}
if ((!empty($isMgmtUserLabor) && in_array($st, ['pending', 'pm_approved', 'work_completed', 'payment_requested', 'qc_approved', 'pm_payment_approved'], true)) || (in_array($st, ['qc_approved', 'pm_payment_approved']) && (!empty($canApproveManagement) || !empty($isManagement)))): ?>
<?php $mgmtDefaultAmount = (float)($req['qc_approved_amount'] ?? $req['pm_verified_amount'] ?? $req['total_amount']); ?>
<div class="modal fade" id="mgmtApproveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered"><form id="laborMgmtApproveForm" action="<?php echo $base; ?>/con-labor-req-mgmt-approve/<?php echo $req['id']; ?>" method="POST">
        <input type="hidden" name="approve_mode" id="laborApproveModeInput" value="override">
        <div class="modal-content" style="border-radius:16px;border:none;box-shadow:0 10px 40px rgba(0,0,0,.15)">
            <div class="modal-header border-0 pb-0"><h5 class="modal-title text-success fw-bold"><i class="fa-solid fa-building-circle-check me-2"></i>Management / Role 20 Payment Approval</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-2 mb-3 text-center">
                    <div class="col-6 p-2 rounded bg-light border">
                        <div class="fs-11 text-muted fw-semibold">Requested Amount</div>
                        <div class="fw-bold fs-14 text-dark">৳ <?= number_format($req['total_amount'], 2) ?></div>
                    </div>
                    <div class="col-6 p-2 rounded" style="background:rgba(32,201,151,.1)">
                        <div class="fs-11 text-muted fw-semibold"><?= !empty($req['qc_approved_amount']) ? 'QC Approved Amount' : 'Forwarded Amount' ?></div>
                        <div class="fw-bold fs-14 text-success">৳ <?= number_format($mgmtDefaultAmount, 2) ?></div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Approved Amount (৳) <span class="text-danger">*</span></label>
                    <input type="number" name="approved_amount" class="form-control" step="0.01" value="<?php echo $mgmtDefaultAmount; ?>" required>
                </div>
                <div class="mb-3"><label class="form-label fw-semibold">Management Note (Optional)</label><textarea name="management_note" class="form-control" rows="2" placeholder="Add approval remarks..."></textarea></div>
            </div>
            <div class="modal-footer border-0 pt-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:10px;font-weight:600">Cancel</button><button type="submit" class="btn btn-success" style="border-radius:10px;font-weight:600"><i class="fa-solid fa-check-double me-1"></i> Authorize Payment</button></div>
        </div>
    </form></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var laborForm = document.getElementById('laborMgmtApproveForm');
    if (laborForm) {
        laborForm.addEventListener('submit', function(e) {
            var overrideStage = <?php echo json_encode($laborOverrideStage ?? ''); ?>;
            if (overrideStage !== '' && !laborForm.getAttribute('data-confirmed-override')) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Pending ' + overrideStage + "'s Approval",
                        html: 'This labor requisition is pending for <strong>' + overrideStage + "'s</strong> approval. Choose whether to approve <strong>only " + overrideStage + '</strong> and forward to the next stage, or override all stages &amp; finalize now.',
                        icon: 'warning',
                        showCancelButton: true,
                        showDenyButton: true,
                        confirmButtonColor: '#0d6efd',
                        denyButtonColor: '#198754',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Approve Only ' + overrideStage,
                        denyButtonText: 'Approve All & Finalize'
                    }).then(function(result) {
                        var modeInput = document.getElementById('laborApproveModeInput');
                        if (result.isConfirmed) {
                            if (modeInput) modeInput.value = 'single';
                            laborForm.setAttribute('data-confirmed-override', '1');
                            laborForm.submit();
                        } else if (result.isDenied) {
                            if (modeInput) modeInput.value = 'override';
                            laborForm.setAttribute('data-confirmed-override', '1');
                            laborForm.submit();
                        }
                    });
                } else if (confirm("Pending " + overrideStage + "'s Approval. Are you ready to approve all bypassed stages and complete final Management approval?")) {
                    laborForm.setAttribute('data-confirmed-override', '1');
                    laborForm.submit();
                }
            }
        });
    }
});
</script>
<div class="modal fade" id="mgmtRejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered"><form action="<?php echo $base; ?>/con-labor-req-mgmt-reject/<?php echo $req['id']; ?>" method="POST">
        <div class="modal-content" style="border-radius:16px;border:none;box-shadow:0 10px 40px rgba(0,0,0,.15)">
            <div class="modal-header border-0 pb-0"><h5 class="modal-title text-danger fw-bold"><i class="fa-solid fa-ban me-2"></i>Reject Labor Requisition</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label fw-bold">Reason for Rejection <span class="text-danger">*</span></label><textarea name="rejection_reason" class="form-control" rows="3" placeholder="Explain why this labor requisition is being rejected..." required></textarea></div>
            </div>
            <div class="modal-footer border-0 pt-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:10px;font-weight:600">Cancel</button><button type="submit" class="btn btn-danger" style="border-radius:10px;font-weight:600"><i class="fa-solid fa-xmark me-1"></i> Confirm Rejection</button></div>
        </div>
    </form></div>
</div>
<?php endif; ?>

<!-- Accounts Pay Modal -->
<?php
$approvedAmt  = (float)($req['approved_amount'] ?? $req['pm_verified_amount'] ?? $req['qc_approved_amount'] ?? $req['total_amount']);
$totalPaidAmt = (float)($req['total_paid'] ?? 0);
$remainingAmt = max(0, $approvedAmt - $totalPaidAmt);
?>
<?php if (in_array($st, ['management_approved','partially_paid']) && $isAccounts): ?>
<div class="modal fade" id="payModal" tabindex="-1">
    <div class="modal-dialog"><form action="<?php echo $base; ?>/con-labor-req-mark-paid/<?php echo $req['id']; ?>" method="POST" enctype="multipart/form-data">
        <div class="modal-content">
            <div class="modal-header bg-primary bg-opacity-10"><h5 class="modal-title text-primary"><i class="fa-solid fa-credit-card me-2"></i>Process Payment Disbursement</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-2 mb-3 text-center">
                    <div class="col-4 p-2 rounded" style="background:rgba(40,167,69,.1)">
                        <div class="fs-11 text-muted">Approved Amount</div>
                        <div class="fw-bold text-success">৳ <?= number_format($approvedAmt, 2) ?></div>
                    </div>
                    <div class="col-4 p-2 rounded" style="background:rgba(13,110,253,.1)">
                        <div class="fs-11 text-muted">Disbursed / Paid</div>
                        <div class="fw-bold text-primary">৳ <?= number_format($totalPaidAmt, 2) ?></div>
                    </div>
                    <div class="col-4 p-2 rounded" style="background:rgba(220,53,69,.1)">
                        <div class="fs-11 text-danger fw-bold">Remaining Due</div>
                        <div class="fw-bold text-danger fs-15">৳ <?= number_format($remainingAmt, 2) ?></div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Disbursement / Paid Amount (৳) <span class="text-danger">*</span></label>
                    <input type="number" name="paid_amount" id="payAmountInput" class="form-control"
                           step="0.01" min="0.01" max="<?= $remainingAmt ?>"
                           value="<?= $remainingAmt ?>" required>
                    <div class="form-text text-danger fw-semibold">Due Amount: ৳ <?= number_format($remainingAmt, 2) ?> (Max payable)</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                    <input type="date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" class="form-select">
                        <option value="Cash">Cash</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Mobile Banking">Mobile Banking</option>
                        <option value="Cheque">Cheque</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Reference / Transaction No</label>
                    <input type="text" name="payment_reference" class="form-control" placeholder="Cheque no / bKash TxID...">
                </div>
                <div class="mb-3">
                    <label class="form-label">Payment Proof <span class="text-muted fs-11">(Image/PDF)</span></label>
                    <input type="file" name="proof_image" class="form-control" accept="image/*,.pdf">
                    <div class="form-text">Upload receipt, cheque scan, or bank transfer screenshot</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Accounts Note</label>
                    <textarea name="payment_note" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-check me-1"></i>Confirm Disbursement</button></div>
        </div>
    </form></div>
</div>
<?php endif; ?>

<!-- Management: Approve Remaining (partially_paid) -->
<?php if ($st === 'partially_paid' && $isManagement): ?>
<?php
$approvedAmt  = (float)($req['approved_amount'] ?? $req['total_amount']);
$totalPaidAmt = (float)($req['total_paid'] ?? 0);
$remainingAmt = max(0, $approvedAmt - $totalPaidAmt);
?>
<div class="modal fade" id="mgmtApproveRemainingModal" tabindex="-1">
    <div class="modal-dialog"><form action="<?php echo $base; ?>/con-labor-req-mgmt-approve-remaining/<?php echo $req['id']; ?>" method="POST">
        <div class="modal-content">
            <div class="modal-header" style="background:rgba(253,126,20,.1)"><h5 class="modal-title" style="color:#fd7e14"><i class="fa-solid fa-clock-rotate-left me-2"></i>Approve Remaining Balance</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-2 mb-3 text-center">
                    <div class="col-4 p-2 rounded" style="background:rgba(40,167,69,.1)">
                        <div class="fs-11 text-muted">Total Approved</div>
                        <div class="fw-bold text-success">৳ <?= number_format($approvedAmt, 2) ?></div>
                    </div>
                    <div class="col-4 p-2 rounded" style="background:rgba(13,110,253,.1)">
                        <div class="fs-11 text-muted">Already Paid</div>
                        <div class="fw-bold text-primary">৳ <?= number_format($totalPaidAmt, 2) ?></div>
                    </div>
                    <div class="col-4 p-2 rounded" style="background:rgba(253,126,20,.1)">
                        <div class="fs-11 text-muted">Remaining</div>
                        <div class="fw-bold" style="color:#fd7e14">৳ <?= number_format($remainingAmt, 2) ?></div>
                    </div>
                </div>
                <div class="alert alert-warning py-2 fs-12">
                    Approving <strong>৳ <?= number_format($remainingAmt, 2) ?></strong> remaining balance for payment by Accounts.
                </div>
                <div class="mb-3"><label class="form-label">Note (Optional)</label><textarea name="management_note" class="form-control" rows="2"></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-sm fw-semibold" style="background:#fd7e14;color:#fff">Approve Remaining</button></div>
        </div>
    </form></div>
</div>
<?php endif; ?>

<!-- SE: Labour Disbursement Confirmation Modal -->
<?php if ($st === 'paid' && $isSE): ?>
<div class="modal fade" id="disbursementModal" tabindex="-1">
    <div class="modal-dialog"><form action="<?php echo $base; ?>/con-labor-req-labor-disbursed/<?php echo $req['id']; ?>" method="POST" enctype="multipart/form-data">
        <div class="modal-content">
            <div class="modal-header bg-primary bg-opacity-10">
                <h5 class="modal-title text-primary"><i class="fa-solid fa-hand-holding-dollar me-2"></i>Confirm Labour Payment Disbursement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2 fs-12 mb-3">
                    <strong><?= $req['labor_count'] ?> জন Labour</strong>-কে মোট
                    <strong>৳ <?= number_format((float)($req['total_paid'] ?? $req['approved_amount'] ?? 0), 2) ?></strong>
                    payment দিয়েছেন বলে confirm করছেন।
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Payment Proof / Receipt <span class="text-danger">*</span></label>
                    <input type="file" name="disbursement_proof" class="form-control" accept="image/*,.pdf" required>
                    <div class="form-text">Labour payment-এর ছবি, receipt বা acknowledgment upload করুন</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Note (Optional)</label>
                    <textarea name="disbursement_note" class="form-control" rows="2" placeholder="e.g. Cash paid to all labourers on site..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm fw-semibold">
                    <i class="fa-solid fa-check me-1"></i> Confirm Disbursement
                </button>
            </div>
        </div>
    </form></div>
</div>
<?php endif; ?>

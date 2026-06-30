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
    'rejected'             => 'bg-danger',
];
$statusLabel = [
    'pending'              => 'Pending PM Approval',
    'pm_approved'          => 'PM Approved',
    'work_completed'       => 'Work Completed',
    'payment_requested'    => 'Payment Requested',
    'qc_approved'          => 'QC Approved',
    'pm_payment_approved'  => 'PM Payment Verified',
    'management_approved'  => 'Management Approved',
    'partially_paid'       => 'Partially Paid',
    'paid'                 => 'Paid',
    'rejected'             => 'Rejected',
];

$st = $req['status'];
$isOwner = ($req['requested_by'] == $userId);

// Timeline steps
$steps = [
    ['icon' => 'fa-file-pen',     'title' => 'Submitted',           'done' => true,                              'color' => 'primary'],
    ['icon' => 'fa-user-tie',     'title' => 'PM Approved',         'done' => !in_array($st, ['pending']),       'color' => 'info',    'user' => $req['pm_approved_by_name'], 'at' => $req['pm_approved_at'] ?? null, 'note' => $req['pm_note'] ?? null],
    ['icon' => 'fa-hammer',       'title' => 'Work Completed',      'done' => in_array($st, ['work_completed','payment_requested','qc_approved','pm_payment_approved','management_approved','paid']), 'color' => 'warning'],
    ['icon' => 'fa-hand-holding-dollar', 'title' => 'Payment Requested', 'done' => in_array($st, ['payment_requested','qc_approved','pm_payment_approved','management_approved','paid']), 'color' => 'orange'],
    ['icon' => 'fa-magnifying-glass-check', 'title' => 'QC Approved', 'done' => in_array($st, ['qc_approved','pm_payment_approved','management_approved','paid']), 'color' => 'teal', 'user' => $req['qc_approved_by_name'] ?? null, 'at' => $req['qc_approved_at'] ?? null, 'note' => $req['qc_note'] ?? null],
    ['icon' => 'fa-user-check',   'title' => 'PM Verified',         'done' => in_array($st, ['pm_payment_approved','management_approved','paid']), 'color' => 'indigo', 'user' => $req['pm_payment_approved_by_name'] ?? null, 'at' => $req['pm_payment_approved_at'] ?? null],
    ['icon' => 'fa-building',     'title' => 'Management Approved', 'done' => in_array($st, ['management_approved','paid']), 'color' => 'success', 'user' => $req['management_approved_by_name'] ?? null, 'at' => $req['management_approved_at'] ?? null, 'note' => $req['management_note'] ?? null],
    ['icon' => 'fa-money-bill-wave', 'title' => 'Paid',            'done' => $st === 'paid',                    'color' => 'success', 'user' => $req['paid_by_name'] ?? null, 'at' => $req['payment_date'] ?? null],
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
            <h1 class="page-title fw-semibold fs-18 mb-0">
                <?php echo htmlspecialchars($req['requisition_no']); ?>
                <span class="badge <?php echo $statusBadge[$st] ?? 'bg-secondary'; ?> fs-11 ms-2 align-middle">
                    <?php echo $statusLabel[$st] ?? $st; ?>
                </span>
            </h1>
            <nav><ol class="breadcrumb mb-0 mt-1">
                <li class="breadcrumb-item"><a href="<?php echo $base; ?>/con-labor-requisitions">Labor Requisitions</a></li>
                <li class="breadcrumb-item active"><?php echo $req['requisition_no']; ?></li>
            </ol></nav>
        </div>
        <a href="<?php echo $base; ?>/con-labor-requisitions" class="btn btn-light btn-sm border">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to List
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if ($st === 'rejected'): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-xmark me-2"></i>
            <strong>Rejected:</strong> <?php echo htmlspecialchars($req['rejection_reason'] ?? 'No reason provided.'); ?>
        </div>
    <?php endif; ?>

    <div class="row ">

        <!-- LEFT: Details -->
        <div class="col-lg-4">

            <!-- Summary Card -->
            <div class="card custom-card mb-3">
                <div class="card-header"><div class="card-title fs-13"><i class="fa-solid fa-hard-hat me-2 text-warning"></i> Requisition Info</div></div>
                <div class="card-body">
                    <div class="row g-2 fs-13">
                        <div class="col-6">
                            <div class="text-muted fs-11 fw-semibold text-uppercase">Req. No</div>
                            <div class="fw-bold"><?php echo $req['requisition_no']; ?></div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted fs-11 fw-semibold text-uppercase">Date</div>
                            <div><?php echo date('d M Y', strtotime($req['requisition_date'])); ?></div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted fs-11 fw-semibold text-uppercase">Project</div>
                            <div class="fw-semibold"><?php echo htmlspecialchars($req['project_name'] ?? '---'); ?></div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted fs-11 fw-semibold text-uppercase">Phase</div>
                            <div><?php echo htmlspecialchars($req['phase_name'] ?? '---'); ?></div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted fs-11 fw-semibold text-uppercase">Requested By</div>
                            <div class="fw-semibold"><?php echo htmlspecialchars($req['requested_by_name'] ?? '---'); ?></div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted fs-11 fw-semibold text-uppercase">Labor Type</div>
                            <div><span class="badge bg-secondary-transparent text-dark"><?php echo $req['labor_type']; ?></span></div>
                        </div>

                        <?php if (!empty($req['work_date_from']) || !empty($req['work_date_to'])): ?>
                        <div class="col-12">
                            <div class="text-muted fs-11 fw-semibold text-uppercase">Work Period</div>
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
                            <div class="text-muted fs-11 fw-semibold text-uppercase">Laborers</div>
                            <div class="fw-bold fs-18"><?php echo $req['labor_count']; ?></div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="text-muted fs-11 fw-semibold text-uppercase">Rate</div>
                            <div class="fw-bold fs-15">৳<?php echo number_format($req['per_labor_rate'], 0); ?></div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="text-muted fs-11 fw-semibold text-uppercase">Days</div>
                            <div class="fw-bold fs-18"><?php echo $req['working_days']; ?></div>
                        </div>

                        <div class="col-12">
                            <div class="p-2 rounded text-center" style="background:linear-gradient(135deg,#e8f5e9,#f0f7ff)">
                                <div class="text-muted fs-11 fw-semibold text-uppercase">Total Requested</div>
                                <div class="fw-bold fs-22 text-success">৳ <?php echo number_format($req['total_amount'], 2); ?></div>
                            </div>
                        </div>

                        <?php if (!is_null($req['approved_amount'])): ?>
                        <div class="col-12">
                            <div class="p-2 rounded text-center bg-success bg-opacity-10 border border-success border-opacity-25">
                                <div class="text-light fs-11 fw-semibold text-uppercase">Approved Amount</div>
                                <div class="fw-bold fs-20 text-light">৳ <?php echo number_format($req['approved_amount'], 2); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($st === 'paid' && !is_null($req['paid_amount'])): ?>
                        <div class="col-12">
                            <div class="p-2 rounded text-center bg-primary bg-opacity-10">
                                <div class="text-light fs-11 fw-semibold text-uppercase">Paid Amount</div>
                                <div class="fw-bold fs-20 text-light">৳ <?php echo number_format($req['paid_amount'], 2); ?></div>
                                <div class="fs-11 text-light mt-1">
                                    via <?php echo htmlspecialchars($req['payment_method'] ?? 'Cash'); ?>
                                    <?php if ($req['payment_reference']): ?>· Ref: <?php echo htmlspecialchars($req['payment_reference']); ?><?php endif; ?>
                                    <br>on <?php echo $req['payment_date'] ? date('d M Y', strtotime($req['payment_date'])) : '---'; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="col-12">
                            <div class="text-muted fs-11 fw-semibold text-uppercase">Purpose</div>
                            <p class="mb-0 bg-light p-2 rounded fs-12"><?php echo nl2br(htmlspecialchars($req['purpose'])); ?></p>
                        </div>
                        <?php if (!empty($req['remarks'])): ?>
                        <div class="col-12">
                            <div class="text-muted fs-11 fw-semibold text-uppercase">Remarks</div>
                            <p class="mb-0 fs-12 text-muted"><?php echo nl2br(htmlspecialchars($req['remarks'])); ?></p>
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
                <div class="card-header"><div class="card-title fs-14"><i class="fa-solid fa-timeline me-2 text-primary"></i> Approval Timeline</div></div>
                <div class="card-body">
                    <div class="ps-1">
                        <?php foreach ($steps as $step): ?>
                        <div class="timeline-step <?php echo $step['done'] ? 'done' : ''; ?>">
                            <div class="timeline-icon" style="<?php echo $step['done'] ? 'background:#'.($step['color'] === 'primary' ? '6c5ce7' : ($step['color'] === 'info' ? '17a2b8' : ($step['color'] === 'success' ? '28a745' : ($step['color'] === 'warning' ? 'ffc107' : ($step['color'] === 'teal' ? '20c997' : ($step['color'] === 'indigo' ? '6610f2' : '6c757d')))))) . '!important' : ''; ?>">
                                <i class="fa-solid <?php echo $step['icon']; ?>"></i>
                            </div>
                            <div class="ms-1">
                                <div class="fw-semibold fs-13 <?php echo !$step['done'] ? 'text-muted' : ''; ?>">
                                    <?php echo $step['title']; ?>
                                    <?php if ($step['done'] && !empty($step['user'])): ?>
                                        <span class="text-muted fw-normal fs-12">— <?php echo htmlspecialchars($step['user']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($step['at'])): ?>
                                    <div class="fs-11 text-muted"><?php echo date('d M Y, h:i A', strtotime($step['at'])); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($step['note'])): ?>
                                    <div class="fs-11 text-muted fst-italic mt-1 p-1 bg-light rounded"><?php echo htmlspecialchars($step['note']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- ====== ACTION PANELS ====== -->

            <!-- STEP 1: PM Approve (status=pending) -->
            <?php if ($st === 'pending' && $isPM): ?>
            <div class="card custom-card mb-3 border border-info border-opacity-50">
                <div class="card-header bg-info-transparent">
                    <div class="card-title fs-13 text-info"><i class="fa-solid fa-stamp me-2"></i> Project Manager Action</div>
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

            <!-- STEP 4: QC Approve Payment (status=payment_requested) -->
            <?php if ($st === 'payment_requested' && $isQC): ?>
            <div class="card custom-card mb-3 border border-teal border-opacity-50" style="border-color:#20c997!important">
                <div class="card-header" style="background:rgba(32,201,151,.1)">
                    <div class="card-title fs-13" style="color:#20c997"><i class="fa-solid fa-magnifying-glass-check me-2"></i> QC Review & Approval</div>
                </div>
                <div class="card-body">
                    <p class="fs-13 text-muted mb-3">QC verification required before PM approval for payment.</p>
                    <div class="action-btn-group">
                        <button class="btn btn-sm fw-semibold " style="background:#20c997" data-bs-toggle="modal" data-bs-target="#qcApproveModal">
                            <i class="fa-solid fa-check me-1"></i> QC Approve
                        </button>
                        <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#qcRejectModal">
                            <i class="fa-solid fa-xmark me-1"></i> Reject
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- STEP 5: PM Payment Approve (status=qc_approved) -->
            <?php if ($st === 'qc_approved' && $isPM): ?>
            <div class="card custom-card mb-3" style="border:1.5px solid #12b958!important">
                <div class="card-header" style="background:rgba(102,16,242,.08)">
                    <div class="card-title fs-13" style="color:#12b958"><i class="fa-solid fa-user-check me-2"></i> PM Payment Verification</div>
                </div>
                <div class="card-body">
                    <p class="fs-13 text-muted mb-3">Verify and forward to Management for final payment approval.</p>
                    <div class="action-btn-group">
                        <button class="btn btn-sm fw-semibold " style="background:#12b958" data-bs-toggle="modal" data-bs-target="#pmPayApproveModal">
                            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Approve & Forward to Management
                        </button>
                        <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#pmPayRejectModal">
                            <i class="fa-solid fa-xmark me-1"></i> Reject
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- STEP 6: Management Approve (status=pm_payment_approved) -->
            <!-- STEP 6: Management Approve (status=pm_payment_approved) -->
            <?php if ($st === 'pm_payment_approved' && $isManagement): ?>
            <div class="card custom-card mb-3 border border-success border-opacity-50">
                <div class="card-header bg-success-transparent">
                    <div class="card-title fs-13 text-success"><i class="fa-solid fa-building me-2"></i> Management Payment Approval</div>
                </div>
                <div class="card-body">
                    <p class="fs-13 text-muted mb-3">Review and approve the payment amount. You may adjust the amount if needed.</p>
                    <div class="action-btn-group">
                        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#mgmtApproveModal">
                            <i class="fa-solid fa-check-double me-1"></i> Approve Payment
                        </button>
                        <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#mgmtRejectModal">
                            <i class="fa-solid fa-ban me-1"></i> Reject
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- STEP 7a: Partially Paid — Management must approve remaining -->
            <?php if ($st === 'partially_paid' && $isManagement): ?>
            <?php
                $approvedAmt  = (float)($req['approved_amount'] ?? $req['total_amount']);
                $totalPaidAmt = (float)($req['total_paid'] ?? 0);
                $remainingAmt = $approvedAmt - $totalPaidAmt;
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
                            <div class="fs-11 text-muted fw-semibold">Remaining</div>
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
            $approvedAmt  = (float)($req['approved_amount'] ?? $req['total_amount']);
            $totalPaidAmt = (float)($req['total_paid'] ?? 0);
            $remainingAmt = max(0, $approvedAmt - $totalPaidAmt);
            ?>
            <?php if (in_array($st, ['management_approved','partially_paid']) && $isAccounts): ?>
            <div class="card custom-card mb-3 border border-primary border-opacity-50">
                <div class="card-header bg-primary-transparent">
                    <div class="card-title fs-13 text-primary"><i class="fa-solid fa-money-bill-wave me-2"></i>
                        <?= $st === 'partially_paid' ? 'Process Remaining Payment' : 'Process Payment' ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-3">
                        <div class="col-4 text-center p-2 rounded" style="background:rgba(40,167,69,.1)">
                            <div class="fs-11 text-muted fw-semibold">Approved</div>
                            <div class="fw-bold fs-15 text-success">৳ <?= number_format($approvedAmt, 2) ?></div>
                        </div>
                        <div class="col-4 text-center p-2 rounded" style="background:rgba(13,110,253,.1)">
                            <div class="fs-11 text-muted fw-semibold">Paid So Far</div>
                            <div class="fw-bold fs-15 text-primary">৳ <?= number_format($totalPaidAmt, 2) ?></div>
                        </div>
                        <div class="col-4 text-center p-2 rounded" style="background:rgba(253,126,20,.1)">
                            <div class="fs-11 text-muted fw-semibold">Remaining</div>
                            <div class="fw-bold fs-15" style="color:#fd7e14">৳ <?= number_format($remainingAmt, 2) ?></div>
                        </div>
                    </div>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#payModal">
                        <i class="fa-solid fa-credit-card me-1"></i> Pay Now (Max: ৳ <?= number_format($remainingAmt, 2) ?>)
                    </button>
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
                        <i class="fa-solid fa-circle-check me-2 fs-16"></i> ✅ Payment Fully Settled
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
            <div class="modal-header bg-success bg-opacity-10"><h5 class="modal-title text-success"><i class="fa-solid fa-thumbs-up me-2"></i>PM Approve</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
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
            <div class="modal-header bg-danger bg-opacity-10"><h5 class="modal-title text-danger"><i class="fa-solid fa-thumbs-down me-2"></i>Reject Requisition</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Rejection Reason <span class="text-danger">*</span></label><textarea name="rejection_reason" class="form-control" rows="3" required></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger btn-sm">Reject</button></div>
        </div>
    </form></div>
</div>
<?php endif; ?>

<!-- QC Approve -->
<?php if ($st === 'payment_requested' && $isQC): ?>
<div class="modal fade" id="qcApproveModal" tabindex="-1">
    <div class="modal-dialog"><form action="<?php echo $base; ?>/con-labor-req-qc-approve/<?php echo $req['id']; ?>" method="POST">
        <div class="modal-content">
            <div class="modal-header" style="background:rgba(32,201,151,.1)"><h5 class="modal-title" style="color:#20c997">QC Approval</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">QC Note (Optional)</label><textarea name="qc_note" class="form-control" rows="2"></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-sm fw-semibold " style="background:#20c997">QC Approve</button></div>
        </div>
    </form></div>
</div>
<div class="modal fade" id="qcRejectModal" tabindex="-1">
    <div class="modal-dialog"><form action="<?php echo $base; ?>/con-labor-req-qc-reject/<?php echo $req['id']; ?>" method="POST">
        <div class="modal-content">
            <div class="modal-header bg-danger bg-opacity-10"><h5 class="modal-title text-danger">QC Rejection</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Reason <span class="text-danger">*</span></label><textarea name="rejection_reason" class="form-control" rows="3" required></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger btn-sm">Reject</button></div>
        </div>
    </form></div>
</div>
<?php endif; ?>

<!-- PM Payment Approve -->
<?php if ($st === 'qc_approved' && $isPM): ?>
<div class="modal fade" id="pmPayApproveModal" tabindex="-1">
    <div class="modal-dialog"><form action="<?php echo $base; ?>/con-labor-req-pm-pay-approve/<?php echo $req['id']; ?>" method="POST">
        <div class="modal-content">
            <div class="modal-header" style="background:rgba(18,185,88,.1)"><h5 class="modal-title" style="color:#12b958">PM Payment Verification</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Note (Optional)</label><textarea name="pm_payment_note" class="form-control" rows="2"></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-sm fw-semibold " style="background:#12b958">Approve & Forward</button></div>
        </div>
    </form></div>
</div>
<div class="modal fade" id="pmPayRejectModal" tabindex="-1">
    <div class="modal-dialog"><form action="<?php echo $base; ?>/con-labor-req-pm-pay-reject/<?php echo $req['id']; ?>" method="POST">
        <div class="modal-content">
            <div class="modal-header bg-danger bg-opacity-10"><h5 class="modal-title text-danger">PM Reject Payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Reason <span class="text-danger">*</span></label><textarea name="rejection_reason" class="form-control" rows="3" required></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger btn-sm">Reject</button></div>
        </div>
    </form></div>
</div>
<?php endif; ?>

<!-- Management Approve -->
<?php if ($st === 'pm_payment_approved' && $isManagement): ?>
<div class="modal fade" id="mgmtApproveModal" tabindex="-1">
    <div class="modal-dialog"><form action="<?php echo $base; ?>/con-labor-req-mgmt-approve/<?php echo $req['id']; ?>" method="POST">
        <div class="modal-content">
            <div class="modal-header bg-success bg-opacity-10"><h5 class="modal-title text-success">Management Payment Approval</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Approved Amount (৳) <span class="text-danger">*</span></label>
                    <input type="number" name="approved_amount" class="form-control" step="0.01" value="<?php echo $req['total_amount']; ?>" required>
                    <div class="form-text">Requested: ৳ <?php echo number_format($req['total_amount'], 2); ?></div>
                </div>
                <div class="mb-3"><label class="form-label">Management Note</label><textarea name="management_note" class="form-control" rows="2"></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success btn-sm">Approve Payment</button></div>
        </div>
    </form></div>
</div>
<div class="modal fade" id="mgmtRejectModal" tabindex="-1">
    <div class="modal-dialog"><form action="<?php echo $base; ?>/con-labor-req-mgmt-reject/<?php echo $req['id']; ?>" method="POST">
        <div class="modal-content">
            <div class="modal-header bg-danger bg-opacity-10"><h5 class="modal-title text-danger">Management Reject</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Reason <span class="text-danger">*</span></label><textarea name="rejection_reason" class="form-control" rows="3" required></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger btn-sm">Reject</button></div>
        </div>
    </form></div>
</div>
<?php endif; ?>

<!-- Accounts Pay Modal -->
<?php
$approvedAmt  = (float)($req['approved_amount'] ?? $req['total_amount']);
$totalPaidAmt = (float)($req['total_paid'] ?? 0);
$remainingAmt = max(0, $approvedAmt - $totalPaidAmt);
?>
<?php if (in_array($st, ['management_approved','partially_paid']) && $isAccounts): ?>
<div class="modal fade" id="payModal" tabindex="-1">
    <div class="modal-dialog"><form action="<?php echo $base; ?>/con-labor-req-mark-paid/<?php echo $req['id']; ?>" method="POST" enctype="multipart/form-data">
        <div class="modal-content">
            <div class="modal-header bg-primary bg-opacity-10"><h5 class="modal-title text-primary"><i class="fa-solid fa-credit-card me-2"></i>Process Payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-2 mb-3 text-center">
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
                <div class="mb-3">
                    <label class="form-label fw-semibold">Paid Amount (৳) <span class="text-danger">*</span></label>
                    <input type="number" name="paid_amount" id="payAmountInput" class="form-control"
                           step="0.01" min="0.01" max="<?= $remainingAmt ?>"
                           value="<?= $remainingAmt ?>" required>
                    <div class="form-text">Max payable: ৳ <?= number_format($remainingAmt, 2) ?></div>
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
            <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-check me-1"></i>Confirm Payment</button></div>
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

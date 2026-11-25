<?php
$roleId = (int)($_SESSION['user']['role_id'] ?? 0);
$userId = (int)($_SESSION['user']['id'] ?? 0);

$reqTotal = (float)($req['total_price'] ?? 0);
$isOver10K = $reqTotal > 10000.01;
$isPaidOrDone = ($req['payment_status'] === 'paid' || $req['overall_status'] === 'paid' || $req['overall_status'] === 'purchased');

$overallBadges = [
    'pending' => 'warning', 'in_review' => 'info', 'approved' => 'success',
    'rejected' => 'danger', 'paid' => 'primary', 'purchased' => 'success'
];
$statusBadge = $overallBadges[$req['overall_status'] ?? 'pending'] ?? 'secondary';

// Timeline resolution
$dhStatus = $req['dept_head_status'] ?? 'not_required';
$itStatus = $req['it_head_status'] ?? 'not_required';
$opStatus = $req['operations_status'] ?? 'pending';
$phStatus = $req['purchase_head_status'] ?? 'pending';
$qcStatus = $req['qc_audit_status'] ?? 'pending';

$isOpHeadPostQcApproved = (!empty($req['op_head_status']) && strtolower($req['op_head_status']) === 'approved') || (!$isOver10K && ($req['mgmt_status'] ?? '') === 'approved') || $isPaidOrDone;
$isOpHeadPostQcRejected = (!empty($req['op_head_status']) && strtolower($req['op_head_status']) === 'rejected') || (!$isOver10K && ($req['mgmt_status'] ?? '') === 'rejected');
$opPostQcName = $req['op_head_by_name'] ?? (!$isOver10K ? ($req['mgmt_by_name'] ?? '') : '') ?? ($isOpHeadPostQcApproved ? 'Admin & Operation Head' : '');
$opPostQcAt = $req['op_head_at'] ?? (!$isOver10K ? ($req['mgmt_at'] ?? '') : '');
$opPostQcNote = $req['op_head_note'] ?? (!$isOver10K ? ($req['mgmt_note'] ?? '') : '');

$isAhApproved = (!empty($req['accounts_head_status']) && strtolower($req['accounts_head_status']) === 'approved') || $isPaidOrDone;
$isAhRejected = (!empty($req['accounts_head_status']) && strtolower($req['accounts_head_status']) === 'rejected');
$ahName = $req['accounts_head_by_name'] ?? ($isAhApproved ? 'Accounts Head' : '');
$ahAt = $req['accounts_head_at'] ?? '';
$ahNote = $req['accounts_head_note'] ?? '';

$showMgmtStep = $isOver10K || ($isOpHeadPostQcApproved && in_array($req['mgmt_status'] ?? '', ['approved', 'rejected'], true));
$isMgmtApproved = (($req['mgmt_status'] ?? '') === 'approved') || ($isOver10K && $isPaidOrDone);
$isMgmtRejected = (($req['mgmt_status'] ?? '') === 'rejected');

$stepTitles = [
    'dept_head'     => 'Department Head Approval',
    'it_head'       => 'IT Head Review',
    'operations'    => 'Admin & Operation Review',
    'purchase_head' => 'Purchase Head Quotation & Approval',
    'qc_audit'      => 'QC & Internal Audit Review',
    'mgmt'          => ($isOver10K ? 'DCEO / Management Final Approval' : 'Head of Operation Approval')
];
$curStepTitle = $stepTitles[$approvalStep] ?? 'Step Approval';
?>

<div class="container-fluid">
    <!-- Header & Breadcrumbs -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-3 page-header-breadcrumb">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/dashboard"><i class="fa-solid fa-house me-1"></i> Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/asset-extra-purchases">Office Procurement Requests</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Request #<?= $req['id'] ?></li>
                </ol>
            </nav>
            <h4 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                <span>Procurement Request #<?= $req['id'] ?></span>
                <span class="badge bg-<?= $statusBadge ?> fs-12 px-2.5 py-1 text-uppercase"><?= ucfirst($req['overall_status'] ?? 'pending') ?></span>
                <?php if ($req['payment_status'] === 'paid'): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle fs-12 px-2 py-1"><i class="fa-solid fa-check me-1"></i>Paid</span>
                <?php else: ?>
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle fs-12 px-2 py-1"><i class="fa-solid fa-clock me-1"></i>Unpaid</span>
                <?php endif; ?>
            </h4>
        </div>
        <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
            <a href="<?= $GLOBALS['base_url'] ?>/asset-extra-purchases" class="btn btn-outline-secondary btn-sm px-3">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to List
            </a>
            <?php if ($isPaidOrDone): ?>
                <a href="<?= $GLOBALS['base_url'] ?>/print-debit-voucher?module=project_extra_assets&id=<?= $req['id'] ?>" target="_blank" class="btn btn-primary btn-sm px-3 shadow-sm">
                    <i class="fa-solid fa-file-invoice-dollar me-1"></i> Debit Voucher
                </a>
                <a href="<?= $GLOBALS['base_url'] ?>/expense-adjustments/create?parent_type=extra_purchase&cashout_request_id=<?= $req['id'] ?>&project_id=<?= $req['project_id'] ?? '' ?>" class="btn btn-warning btn-sm px-3 shadow-sm">
                    <i class="fa-solid fa-sliders me-1"></i> Settlement / Adjustment
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i><?= $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i><?= $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Main Content Grid -->
    <div class="row ">
        <!-- LEFT COLUMN: Requisition Info & Audit Trail -->
        <div class="col-lg-7 col-xl-8">
            <!-- 4 Quick KPI Summary Cards -->
            <div class="row g-2 mb-3">
                <div class="col-sm-6 col-md-3">
                    <div class="card custom-card border-0 shadow-sm mb-0 p-3 bg-white" style="border-left: 4px solid #2563eb !important;">
                        <span class="text-muted fs-11 fw-semibold text-uppercase">Total Amount</span>
                        <h5 class="fw-bold text-primary mb-0 mt-1">৳ <?= number_format($reqTotal, 2) ?></h5>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="card custom-card border-0 shadow-sm mb-0 p-3 bg-white" style="border-left: 4px solid #16a34a !important;">
                        <span class="text-muted fs-11 fw-semibold text-uppercase">Quantity</span>
                        <h5 class="fw-bold text-success mb-0 mt-1"><?= (int)$req['quantity'] ?> <span class="fs-12 text-muted fw-normal">pcs</span></h5>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="card custom-card border-0 shadow-sm mb-0 p-3 bg-white" style="border-left: 4px solid #f59e0b !important;">
                        <span class="text-muted fs-11 fw-semibold text-uppercase">Payment Status</span>
                        <h6 class="fw-bold text-warning mb-0 mt-1"><?= ucfirst($req['payment_status'] ?? 'Unpaid') ?></h6>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="card custom-card border-0 shadow-sm mb-0 p-3 bg-white" style="border-left: 4px solid #0d9488 !important;">
                        <span class="text-muted fs-11 fw-semibold text-uppercase">Disbursed Paid</span>
                        <h6 class="fw-bold text-teal mb-0 mt-1">৳ <?= number_format((float)($req['paid_amount'] ?? 0), 2) ?></h6>
                    </div>
                </div>
            </div>

            <!-- Item & Procurement Details Card -->
            <div class="card custom-card border-0 shadow-sm mb-3">
                <div class="card-header bg-light py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
                    <h6 class="card-title mb-0 fw-bold fs-13 text-dark">
                        <i class="fa-solid fa-boxes-stacked text-primary me-1.5"></i> Procurement Item Details
                    </h6>
                    <span class="text-muted fs-11">Submitted: <?= !empty($req['created_at']) ? date('d M Y, h:i A', strtotime($req['created_at'])) : '—' ?></span>
                </div>
                <div class="card-body p-3">
                    <div class="row  fs-13">
                        <div class="col-md-6">
                            <span class="text-muted fs-11 d-block">Item Name</span>
                            <strong class="text-dark fs-14"><?= htmlspecialchars($req['item_name'] ?? '—') ?></strong>
                            <?php if (!empty($req['category_name'])): ?>
                                <span class="badge bg-light text-secondary border ms-1 fs-11"><?= htmlspecialchars($req['category_name']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted fs-11 d-block">Requested By</span>
                            <div class="d-flex align-items-center gap-1.5 mt-0.5">
                                <i class="fa-solid fa-user-circle text-primary"></i>
                                <strong class="text-dark"><?= htmlspecialchars($req['requested_by_name'] ?? 'System Admin') ?></strong>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <span class="text-muted fs-11 d-block">Project / Location</span>
                            <strong class="text-dark"><i class="fa-solid fa-building text-secondary me-1"></i><?= htmlspecialchars($req['project_name'] ?? 'General Corporate') ?></strong>
                            <div class="text-muted fs-11 mt-0.5"><i class="fa-solid fa-location-dot me-1 text-danger"></i><?= htmlspecialchars($req['branch_name'] ?? $req['workplace_name'] ?? $req['asset_location'] ?? 'Head Office') ?></div>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted fs-11 d-block">Assign To</span>
                            <strong class="text-dark"><i class="fa-solid fa-user-check text-success me-1"></i><?= htmlspecialchars($req['assigned_to_name'] ?? 'Unassigned') ?></strong>
                        </div>

                        <div class="col-md-6">
                            <span class="text-muted fs-11 d-block">Vendor</span>
                            <strong class="text-dark"><i class="fa-solid fa-store text-warning me-1"></i><?= htmlspecialchars($req['vendor_name'] ?? '—') ?></strong>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted fs-11 d-block">Payment Method & Estimated Price</span>
                            <span class="badge bg-light text-dark border me-2"><?= htmlspecialchars($req['payment_method'] ?? 'Cash') ?></span>
                            <span class="text-muted fs-11">Unit: ৳ <?= number_format((float)($req['unit_price'] ?? 0), 2) ?> &times; <?= (int)$req['quantity'] ?> pcs</span>
                        </div>

                        <div class="col-12">
                            <span class="text-muted fs-11 d-block mb-1">Justification / Reason</span>
                            <div class="p-2.5 rounded-2 bg-light border text-dark fs-12 lh-base">
                                <i class="fa-solid fa-quote-left text-muted me-1.5"></i><?= nl2br(htmlspecialchars($req['justification'] ?? 'No justification provided.')) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents & Quotations Card -->
            <?php if (!empty($req['attachment']) || !empty($req['purchase_head_document']) || !empty($req['payment_attachment']) || !empty($req['purchase_document']) || $isPaidOrDone): ?>
            <div class="card custom-card border-0 shadow-sm mb-3">
                <div class="card-header bg-light py-2.5 px-3 border-bottom">
                    <h6 class="card-title mb-0 fw-bold fs-13 text-dark">
                        <i class="fa-solid fa-paperclip text-primary me-1.5"></i> Submitted Documents & Vouchers
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex flex-wrap gap-2">
                        <?php if (!empty($req['attachment'])): ?>
                            <a href="<?= $GLOBALS['base_url'] ?>/<?= htmlspecialchars($req['attachment']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                <i class="fa-solid fa-file-pdf text-danger me-1"></i> Request Requisition Document
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($req['purchase_head_document'])): ?>
                            <a href="<?= $GLOBALS['base_url'] ?>/<?= htmlspecialchars($req['purchase_head_document']) ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                <i class="fa-solid fa-file-invoice text-info me-1"></i> Purchase Head Quotation Doc
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($req['payment_attachment'])): ?>
                            <a href="<?= $GLOBALS['base_url'] ?>/<?= htmlspecialchars($req['payment_attachment']) ?>" target="_blank" class="btn btn-sm btn-outline-success">
                                <i class="fa-solid fa-receipt text-success me-1"></i> Payment Receipt / Cheque
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($req['purchase_document'])): ?>
                            <a href="<?= $GLOBALS['base_url'] ?>/<?= htmlspecialchars($req['purchase_document']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fa-solid fa-file-invoice text-primary me-1"></i> Purchase Invoice Document
                            </a>
                        <?php endif; ?>
                        <?php if ($isPaidOrDone): ?>
                            <a href="<?= $GLOBALS['base_url'] ?>/print-debit-voucher?module=project_extra_assets&id=<?= $req['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fa-solid fa-print me-1"></i> Print Company Debit Voucher
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Approval Notes & Remarks Trail -->
            <?php if (!empty($req['dept_head_note']) || !empty($req['it_head_note']) || !empty($req['operations_note']) || !empty($req['purchase_head_note']) || !empty($req['qc_audit_note']) || !empty($opPostQcNote) || !empty($ahNote) || !empty($req['mgmt_note']) || !empty($req['purchase_note']) || !empty($req['return_note'])): ?>
            <div class="card custom-card border-0 shadow-sm mb-3">
                <div class="card-header bg-light py-2.5 px-3 border-bottom">
                    <h6 class="card-title mb-0 fw-bold fs-13 text-dark">
                        <i class="fa-solid fa-comment-dots text-primary me-1.5"></i> Approval Remarks & Notes History
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex flex-column gap-2">
                        <?php if (!empty($req['dept_head_note'])): ?>
                            <div class="p-2 rounded bg-light border-start border-3 border-info fs-12">
                                <strong class="text-info d-block">Department Head:</strong>
                                <span><?= htmlspecialchars($req['dept_head_note']) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($req['it_head_note'])): ?>
                            <div class="p-2 rounded bg-light border-start border-3 border-info fs-12">
                                <strong class="text-info d-block">IT Head:</strong>
                                <span><?= htmlspecialchars($req['it_head_note']) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($req['operations_note'])): ?>
                            <div class="p-2 rounded bg-light border-start border-3 border-secondary fs-12">
                                <strong class="text-secondary d-block">Admin & Operation (Initial Check):</strong>
                                <span><?= htmlspecialchars($req['operations_note']) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($req['purchase_head_note'])): ?>
                            <div class="p-2 rounded bg-light border-start border-3 border-primary fs-12">
                                <strong class="text-primary d-block">Purchase Head:</strong>
                                <span><?= htmlspecialchars($req['purchase_head_note']) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($req['qc_audit_note'])): ?>
                            <div class="p-2 rounded bg-light border-start border-3 border-info fs-12">
                                <strong class="text-info d-block">QC & Internal Audit:</strong>
                                <span><?= htmlspecialchars($req['qc_audit_note']) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($opPostQcNote)): ?>
                            <div class="p-2 rounded bg-light border-start border-3 border-primary fs-12">
                                <strong class="text-primary d-block">Head of Operation:</strong>
                                <span><?= htmlspecialchars($opPostQcNote) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($ahNote)): ?>
                            <div class="p-2 rounded bg-light border-start border-3 border-dark fs-12">
                                <strong class="text-dark d-block">Accounts Head:</strong>
                                <span><?= htmlspecialchars($ahNote) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($req['mgmt_note'])): ?>
                            <div class="p-2 rounded bg-light border-start border-3 border-warning fs-12">
                                <strong class="text-warning d-block">DCEO / Management:</strong>
                                <span><?= htmlspecialchars($req['mgmt_note']) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($req['purchase_note'])): ?>
                            <div class="p-2 rounded bg-light border-start border-3 border-success fs-12">
                                <strong class="text-success d-block">Operations / Purchaser Note:</strong>
                                <span><?= htmlspecialchars($req['purchase_note']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT COLUMN: Sequential Approval Timeline & Live Action Cards -->
        <div class="col-lg-5 col-xl-4">
            <!-- 1. Approval Action Card (For currently authorized approver) -->
            <?php if ($canApprove): ?>
            <div class="card custom-card border-0 shadow-sm mb-3" style="border-left: 4px solid #16a34a !important; background: #f0fdf4;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="mb-0 fw-bold fs-13 text-success">
                            <i class="fa-solid fa-shield-check me-1"></i> Ready for Approval
                        </h6>
                        <span class="badge bg-success text-white fs-10">Authorized Approver</span>
                    </div>
                    <div class="p-2 rounded bg-white border border-success-subtle fs-12 mb-3 text-secondary">
                        <strong class="text-dark d-block mb-0.5">Stage: <?= htmlspecialchars($curStepTitle) ?></strong>
                        <?php if (!empty($overrideWarningStage)): ?>
                            <span class="text-warning fw-semibold"><i class="fa-solid fa-triangle-exclamation me-1"></i>Notice:</span> Pending <?= htmlspecialchars($overrideWarningStage) ?>. Management can approve individually or override all prior stages.
                        <?php else: ?>
                            All previous stages are verified. Please review the procurement requisition and submit your approval.
                        <?php endif; ?>
                    </div>

                    <form id="viewInlineApprovalForm" action="<?= $GLOBALS['base_url'] ?>/asset-extra-purchases/approve/<?= $req['id'] ?>?redirect=view" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="step" value="<?= htmlspecialchars($approvalStep) ?>">
                        <input type="hidden" name="approval_status" id="viewApprovalStatus" value="approved">
                        <input type="hidden" name="redirect" value="view">

                        <?php if ($approvalStep === 'purchase_head'): ?>
                            <div class="mb-2.5">
                                <label class="form-label fs-11 fw-semibold text-dark mb-1">Upload Quotation / Comparative Statement (Optional)</label>
                                <input type="file" name="purchase_head_document" class="form-control form-control-sm">
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label fs-11 fw-semibold text-dark mb-1">Approval / Rejection Remarks (Optional)</label>
                            <textarea name="approval_note" class="form-control form-control-sm" rows="2" placeholder="Add approval remarks or notes..."></textarea>
                        </div>

                        <div class="d-flex justify-content-between gap-2 pt-1">
                            <button type="submit" class="btn btn-sm btn-outline-danger px-3 flex-fill" onclick="document.getElementById('viewApprovalStatus').value='rejected'; return confirm('Are you sure you want to REJECT this purchase request?');">
                                <i class="fa-solid fa-xmark me-1"></i> Reject
                            </button>
                            <button type="button" class="btn btn-sm btn-success px-3 flex-fill fw-bold shadow-sm" onclick="handleViewPageApproval('<?= htmlspecialchars($overrideWarningStage, ENT_QUOTES, 'UTF-8') ?>')">
                                <i class="fa-solid fa-check me-1"></i> Approve Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- 2. Accounts Head Approval Card -->
            <?php if ($canApproveAh && !empty($req['approval_request_id'])): ?>
            <div class="card custom-card border-0 shadow-sm mb-3" style="border-left: 4px solid #2563eb !important; background: #eff6ff;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="mb-0 fw-bold fs-13 text-primary">
                            <i class="fa-solid fa-stamp me-1"></i> Accounts Head Verification
                        </h6>
                        <span class="badge bg-primary text-white fs-10">Role 30 Action</span>
                    </div>
                    <p class="fs-12 text-secondary mb-3">
                        Operation Head has approved this procurement. Verify and approve as Accounts Head to <?= $isOver10K ? 'forward to Management for final authorization.' : 'authorize payment disbursement.' ?>
                    </p>
                    <button type="button" class="btn btn-primary btn-sm w-100 fw-bold shadow-sm py-2" onclick="triggerAhApprove(<?= (int)$req['approval_request_id'] ?>)">
                        <i class="fa-solid fa-stamp me-1"></i> Verify &amp; Approve (Accounts Head)
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <!-- 3. Accounts Payment Disbursement Card -->
            <?php if ($canPay): ?>
            <div class="card custom-card border-0 shadow-sm mb-3" style="border-left: 4px solid #16a34a !important; background: #f0fdf4;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="mb-0 fw-bold fs-13 text-success">
                            <i class="fa-solid fa-money-bill-wave me-1"></i> Ready for Payment Disbursement
                        </h6>
                        <span class="badge bg-success text-white fs-10">Accounts Action</span>
                    </div>
                    <p class="fs-12 text-secondary mb-3">
                        All multi-stage approvals are completed. Accounts can now process and disburse the fund (৳ <?= number_format($reqTotal, 2) ?>).
                    </p>
                    <button type="button" class="btn btn-success btn-sm w-100 fw-bold shadow-sm py-2" data-bs-toggle="modal" data-bs-target="#paymentModal">
                        <i class="fa-solid fa-money-bill-wave me-1"></i> Disburse / Process Payment
                    </button>
                </div>
            </div>
            <?php elseif ($isWaitingAccountsHead && !$canApproveAh && ($req['payment_status'] ?? '') === 'unpaid'): ?>
            <div class="card custom-card border-0 shadow-sm mb-3" style="border-left: 4px solid #f59e0b !important; background: #fffbeb;">
                <div class="card-body p-3">
                    <span class="fw-bold fs-13 text-dark d-block mb-1">
                        <i class="fa-solid fa-lock me-1 text-warning"></i> Payment Locked — Awaiting Accounts Head
                    </span>
                    <small class="text-muted fs-11">Verification from Accounts Head (Md. Mamunoor Rashid) is required before accounts can disburse payment.</small>
                </div>
            </div>
            <?php endif; ?>

            <!-- 4. Confirm Purchase & Add to Asset Tracker Card -->
            <?php if ($canConfirmPurchase): ?>
            <div class="card custom-card border-0 shadow-sm mb-3" style="border-left: 4px solid #0d9488 !important; background: #f0fdfa;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="mb-0 fw-bold fs-13 text-teal">
                            <i class="fa-solid fa-box-check me-1"></i> Confirm Purchase Received
                        </h6>
                        <span class="badge bg-teal text-white fs-10">Operations Action</span>
                    </div>
                    <p class="fs-12 text-secondary mb-3">
                        Payment has been disbursed. Operations team can confirm items are received to register into Product Master & Asset Tracker.
                    </p>
                    <button type="button" class="btn btn-teal btn-sm w-100 fw-bold shadow-sm py-2 text-white" data-bs-toggle="modal" data-bs-target="#confirmPurchaseModal">
                        <i class="fa-solid fa-box-check me-1"></i> Confirm &amp; Mark Purchased
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <!-- 5. Sequential Timeline Card -->
            <div class="card custom-card border-0 shadow-sm mb-3">
                <div class="card-header bg-light py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
                    <h6 class="card-title mb-0 fw-bold fs-13 text-dark">
                        <i class="fa-solid fa-timeline text-primary me-1.5"></i> Approval & Payment Flow
                    </h6>
                    <span class="badge bg-light text-secondary border fs-10">11 Steps</span>
                </div>
                <div class="card-body p-3">
                    <div class="timeline-workflow d-flex flex-column gap-3 fs-12">
                        <!-- Step 1: Submission -->
                        <div class="d-flex align-items-start gap-2.5">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 bg-success text-white" style="width: 24px; height: 24px; font-size: 11px;">
                                <i class="fa-solid fa-check"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between">
                                    <strong class="text-dark">Requisition Submitted</strong>
                                    <span class="badge bg-success-subtle text-success fs-10">Done</span>
                                </div>
                                <div class="text-muted fs-11 mt-0.5">
                                    By <strong><?= htmlspecialchars($req['requested_by_name'] ?? '—') ?></strong>
                                    <?= !empty($req['created_at']) ? ' &bull; ' . date('d M Y, h:i A', strtotime($req['created_at'])) : '' ?>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Department Head -->
                        <?php if ($dhStatus !== 'not_required'): 
                            $isDhDone = ($dhStatus === 'approved');
                            $isDhRej = ($dhStatus === 'rejected');
                        ?>
                        <div class="d-flex align-items-start gap-2.5">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 <?= $isDhDone ? 'bg-success text-white' : ($isDhRej ? 'bg-danger text-white' : ($dhStatus === 'pending' ? 'bg-warning text-dark' : 'bg-light text-muted border')) ?>" style="width: 24px; height: 24px; font-size: 11px;">
                                <i class="fa-solid <?= $isDhDone ? 'fa-check' : ($isDhRej ? 'fa-xmark' : 'fa-hourglass-half') ?>"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between">
                                    <strong class="text-dark">Department Head</strong>
                                    <span class="badge bg-<?= $isDhDone ? 'success-subtle text-success' : ($isDhRej ? 'danger-subtle text-danger' : 'warning-subtle text-warning') ?> fs-10"><?= ucfirst($dhStatus) ?></span>
                                </div>
                                <div class="text-muted fs-11 mt-0.5">
                                    <?= !empty($req['dept_head_by_name']) ? 'By <strong>' . htmlspecialchars($req['dept_head_by_name']) . '</strong>' : (!empty($req['reporting_boss_name']) ? 'Designated: <strong>' . htmlspecialchars($req['reporting_boss_name']) . '</strong>' : 'Awaiting Dept Head') ?>
                                    <?= !empty($req['dept_head_at']) ? ' &bull; ' . date('d M Y, h:i A', strtotime($req['dept_head_at'])) : '' ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Step 3: IT Head -->
                        <?php if ($itStatus !== 'not_required'): 
                            $isItDone = ($itStatus === 'approved');
                            $isItRej = ($itStatus === 'rejected');
                        ?>
                        <div class="d-flex align-items-start gap-2.5">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 <?= $isItDone ? 'bg-success text-white' : ($isItRej ? 'bg-danger text-white' : ($itStatus === 'pending' ? 'bg-warning text-dark' : 'bg-light text-muted border')) ?>" style="width: 24px; height: 24px; font-size: 11px;">
                                <i class="fa-solid <?= $isItDone ? 'fa-check' : ($isItRej ? 'fa-xmark' : 'fa-hourglass-half') ?>"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between">
                                    <strong class="text-dark">IT Head Review</strong>
                                    <span class="badge bg-<?= $isItDone ? 'success-subtle text-success' : ($isItRej ? 'danger-subtle text-danger' : 'warning-subtle text-warning') ?> fs-10"><?= ucfirst($itStatus) ?></span>
                                </div>
                                <div class="text-muted fs-11 mt-0.5">
                                    <?= !empty($req['it_head_by_name']) ? 'By <strong>' . htmlspecialchars($req['it_head_by_name']) . '</strong>' : 'Awaiting IT Head' ?>
                                    <?= !empty($req['it_head_at']) ? ' &bull; ' . date('d M Y, h:i A', strtotime($req['it_head_at'])) : '' ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Step 4: Admin & Operation Initial Review -->
                        <?php 
                        $isOpDone = ($opStatus === 'approved');
                        $isOpRej = ($opStatus === 'rejected');
                        ?>
                        <div class="d-flex align-items-start gap-2.5">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 <?= $isOpDone ? 'bg-success text-white' : ($isOpRej ? 'bg-danger text-white' : ($opStatus === 'pending' ? 'bg-warning text-dark' : 'bg-light text-muted border')) ?>" style="width: 24px; height: 24px; font-size: 11px;">
                                <i class="fa-solid <?= $isOpDone ? 'fa-check' : ($isOpRej ? 'fa-xmark' : 'fa-hourglass-half') ?>"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between">
                                    <strong class="text-dark">Admin & Operation (Initial)</strong>
                                    <span class="badge bg-<?= $isOpDone ? 'success-subtle text-success' : ($isOpRej ? 'danger-subtle text-danger' : 'warning-subtle text-warning') ?> fs-10"><?= ucfirst($opStatus) ?></span>
                                </div>
                                <div class="text-muted fs-11 mt-0.5">
                                    <?= !empty($req['operations_by_name']) ? 'By <strong>' . htmlspecialchars($req['operations_by_name']) . '</strong>' : 'Awaiting Operations' ?>
                                    <?= !empty($req['operations_at']) ? ' &bull; ' . date('d M Y, h:i A', strtotime($req['operations_at'])) : '' ?>
                                </div>
                            </div>
                        </div>

                        <!-- Step 5: Purchase Head Quotation -->
                        <?php 
                        $isPhDone = ($phStatus === 'approved');
                        $isPhRej = ($phStatus === 'rejected');
                        ?>
                        <div class="d-flex align-items-start gap-2.5">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 <?= $isPhDone ? 'bg-success text-white' : ($isPhRej ? 'bg-danger text-white' : ($phStatus === 'pending' ? 'bg-warning text-dark' : 'bg-light text-muted border')) ?>" style="width: 24px; height: 24px; font-size: 11px;">
                                <i class="fa-solid <?= $isPhDone ? 'fa-check' : ($isPhRej ? 'fa-xmark' : 'fa-hourglass-half') ?>"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between">
                                    <strong class="text-dark">Purchase Head</strong>
                                    <span class="badge bg-<?= $isPhDone ? 'success-subtle text-success' : ($isPhRej ? 'danger-subtle text-danger' : 'warning-subtle text-warning') ?> fs-10"><?= ucfirst($phStatus) ?></span>
                                </div>
                                <div class="text-muted fs-11 mt-0.5">
                                    <?= !empty($req['purchase_head_by_name']) ? 'By <strong>' . htmlspecialchars($req['purchase_head_by_name']) . '</strong>' : 'Awaiting Purchase Head' ?>
                                    <?= !empty($req['purchase_head_at']) ? ' &bull; ' . date('d M Y, h:i A', strtotime($req['purchase_head_at'])) : '' ?>
                                </div>
                            </div>
                        </div>

                        <!-- Step 6: QC & Internal Audit -->
                        <?php 
                        $isQcDone = ($qcStatus === 'approved');
                        $isQcRej = ($qcStatus === 'rejected');
                        ?>
                        <div class="d-flex align-items-start gap-2.5">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 <?= $isQcDone ? 'bg-success text-white' : ($isQcRej ? 'bg-danger text-white' : ($qcStatus === 'pending' ? 'bg-warning text-dark' : 'bg-light text-muted border')) ?>" style="width: 24px; height: 24px; font-size: 11px;">
                                <i class="fa-solid <?= $isQcDone ? 'fa-check' : ($isQcRej ? 'fa-xmark' : 'fa-hourglass-half') ?>"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between">
                                    <strong class="text-dark">QC & Internal Audit</strong>
                                    <span class="badge bg-<?= $isQcDone ? 'success-subtle text-success' : ($isQcRej ? 'danger-subtle text-danger' : 'warning-subtle text-warning') ?> fs-10"><?= ucfirst($qcStatus) ?></span>
                                </div>
                                <div class="text-muted fs-11 mt-0.5">
                                    <?= !empty($req['qc_audit_by_name']) ? 'By <strong>' . htmlspecialchars($req['qc_audit_by_name']) . '</strong>' : 'Awaiting QC' ?>
                                    <?= !empty($req['qc_audit_at']) ? ' &bull; ' . date('d M Y, h:i A', strtotime($req['qc_audit_at'])) : '' ?>
                                </div>
                            </div>
                        </div>

                        <!-- Step 7: Head of Operation Post-QC -->
                        <div class="d-flex align-items-start gap-2.5">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 <?= $isOpHeadPostQcApproved ? 'bg-success text-white' : ($isOpHeadPostQcRejected ? 'bg-danger text-white' : 'bg-warning text-dark') ?>" style="width: 24px; height: 24px; font-size: 11px;">
                                <i class="fa-solid <?= $isOpHeadPostQcApproved ? 'fa-check' : ($isOpHeadPostQcRejected ? 'fa-xmark' : 'fa-hourglass-half') ?>"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between">
                                    <strong class="text-dark">Head of Operation</strong>
                                    <span class="badge bg-<?= $isOpHeadPostQcApproved ? 'success-subtle text-success' : ($isOpHeadPostQcRejected ? 'danger-subtle text-danger' : 'warning-subtle text-warning') ?> fs-10"><?= $isOpHeadPostQcApproved ? 'Approved' : ($isOpHeadPostQcRejected ? 'Rejected' : 'Pending') ?></span>
                                </div>
                                <div class="text-muted fs-11 mt-0.5">
                                    <?= $isOpHeadPostQcApproved ? 'By <strong>' . htmlspecialchars($opPostQcName) . '</strong>' : 'Awaiting Operation Head' ?>
                                    <?= !empty($opPostQcAt) ? ' &bull; ' . date('d M Y, h:i A', strtotime($opPostQcAt)) : '' ?>
                                </div>
                            </div>
                        </div>

                        <!-- Step 8: Accounts Head Approval -->
                        <div class="d-flex align-items-start gap-2.5">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 <?= $isAhApproved ? 'bg-success text-white' : ($isAhRejected ? 'bg-danger text-white' : 'bg-warning text-dark') ?>" style="width: 24px; height: 24px; font-size: 11px;">
                                <i class="fa-solid <?= $isAhApproved ? 'fa-check' : ($isAhRejected ? 'fa-xmark' : 'fa-hourglass-half') ?>"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between">
                                    <strong class="text-dark">Accounts Head (Role 30)</strong>
                                    <span class="badge bg-<?= $isAhApproved ? 'success-subtle text-success' : ($isAhRejected ? 'danger-subtle text-danger' : 'warning-subtle text-warning') ?> fs-10"><?= $isAhApproved ? 'Approved' : ($isAhRejected ? 'Rejected' : 'Pending') ?></span>
                                </div>
                                <div class="text-muted fs-11 mt-0.5">
                                    <?= $isAhApproved ? 'By <strong>' . htmlspecialchars($ahName) . '</strong>' : 'Awaiting Accounts Head' ?>
                                    <?= !empty($ahAt) ? ' &bull; ' . date('d M Y, h:i A', strtotime($ahAt)) : '' ?>
                                </div>
                            </div>
                        </div>

                        <!-- Step 9: Management Final Approval (if applicable) -->
                        <?php if ($showMgmtStep): ?>
                        <div class="d-flex align-items-start gap-2.5">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 <?= $isMgmtApproved ? 'bg-success text-white' : ($isMgmtRejected ? 'bg-danger text-white' : 'bg-warning text-dark') ?>" style="width: 24px; height: 24px; font-size: 11px;">
                                <i class="fa-solid <?= $isMgmtApproved ? 'fa-check' : ($isMgmtRejected ? 'fa-xmark' : 'fa-hourglass-half') ?>"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between">
                                    <strong class="text-dark">DCEO / Management</strong>
                                    <span class="badge bg-<?= $isMgmtApproved ? 'success-subtle text-success' : ($isMgmtRejected ? 'danger-subtle text-danger' : 'warning-subtle text-warning') ?> fs-10"><?= $isMgmtApproved ? 'Approved' : ($isMgmtRejected ? 'Rejected' : 'Pending') ?></span>
                                </div>
                                <div class="text-muted fs-11 mt-0.5">
                                    <?= $isMgmtApproved ? 'By <strong>' . htmlspecialchars($req['mgmt_by_name'] ?? 'Management') . '</strong>' : 'Awaiting Management' ?>
                                    <?= !empty($req['mgmt_at']) ? ' &bull; ' . date('d M Y, h:i A', strtotime($req['mgmt_at'])) : '' ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Step 10: Accounts Payment Disbursement -->
                        <div class="d-flex align-items-start gap-2.5">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 <?= $isPaidOrDone ? 'bg-success text-white' : 'bg-light text-muted border' ?>" style="width: 24px; height: 24px; font-size: 11px;">
                                <i class="fa-solid <?= $isPaidOrDone ? 'fa-check' : 'fa-money-bill-wave' ?>"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between">
                                    <strong class="text-dark">Accounts Disbursement</strong>
                                    <span class="badge bg-<?= $isPaidOrDone ? 'success-subtle text-success' : 'warning-subtle text-warning' ?> fs-10"><?= $isPaidOrDone ? 'Paid' : 'Unpaid' ?></span>
                                </div>
                                <div class="text-muted fs-11 mt-0.5">
                                    <?= $isPaidOrDone ? 'Disbursed by <strong>' . htmlspecialchars($req['paid_by_name'] ?? 'Accounts') . '</strong>' : 'Awaiting Accounts Disbursement' ?>
                                    <?= !empty($req['paid_at']) ? ' &bull; ' . date('d M Y, h:i A', strtotime($req['paid_at'])) : '' ?>
                                </div>
                            </div>
                        </div>

                        <!-- Step 11: Asset Purchased & Tracker Registration -->
                        <?php $isPurchased = ($req['overall_status'] === 'purchased'); ?>
                        <div class="d-flex align-items-start gap-2.5">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 <?= $isPurchased ? 'bg-success text-white' : 'bg-light text-muted border' ?>" style="width: 24px; height: 24px; font-size: 11px;">
                                <i class="fa-solid <?= $isPurchased ? 'fa-check' : 'fa-boxes-stacked' ?>"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between">
                                    <strong class="text-dark">Asset Tracker Registered</strong>
                                    <span class="badge bg-<?= $isPurchased ? 'success-subtle text-success' : 'secondary-subtle text-secondary' ?> fs-10"><?= $isPurchased ? 'Registered' : 'Pending' ?></span>
                                </div>
                                <div class="text-muted fs-11 mt-0.5">
                                    <?= $isPurchased ? 'Confirmed by <strong>' . htmlspecialchars($req['purchased_by_name'] ?? 'Operations') . '</strong>' : 'Awaiting purchase confirmation' ?>
                                    <?= !empty($req['purchase_confirmed_at']) ? ' &bull; ' . date('d M Y, h:i A', strtotime($req['purchase_confirmed_at'])) : '' ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: PAYMENT DISBURSEMENT (Accounts) -->
<!-- ========================================================================= -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="paymentForm" action="<?= $GLOBALS['base_url'] ?>/asset-extra-purchases/purchased/<?= $req['id'] ?>?redirect=view" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="redirect" value="view">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-success-transparent py-2.5 px-3">
                    <h6 class="modal-title fw-semibold text-success"><i class="fa-solid fa-money-bill-wave me-1.5"></i>Disburse / Process Payment</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3 fs-13">
                    <div class="mb-3">
                        <label class="form-label fs-12 fw-semibold">Payment Amount (৳) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="paid_amount" class="form-control form-control-sm" required value="<?= number_format($reqTotal, 2, '.', '') ?>" max="<?= number_format($reqTotal, 2, '.', '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fs-12 fw-semibold">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select form-select-sm" required onchange="toggleBankSection(this.value)">
                            <option value="Cash" selected>Cash</option>
                            <option value="Bank">Bank Transfer</option>
                            <option value="Cheque">Cheque</option>
                        </select>
                    </div>

                    <div id="bankSection" class="mb-3" style="display: none;">
                        <label class="form-label fs-12 fw-semibold">Company Bank Account</label>
                        <select name="bank_account_id" class="form-select form-select-sm">
                            <option value="">Select Bank Account</option>
                            <?php foreach ($bankAccounts as $acc): ?>
                                <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['bank_name'] . ' (' . $acc['account_number'] . ')') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fs-12 fw-semibold">Payment Receipt / Voucher Attachment</label>
                        <input type="file" name="payment_attachment" class="form-control form-control-sm">
                    </div>

                    <div class="mb-2">
                        <label class="form-label fs-12 fw-semibold">Payment Remarks (Optional)</label>
                        <textarea name="purchase_note" class="form-control form-control-sm" rows="2" placeholder="Payment notes, voucher reference or cheque details..."></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm px-4 fw-bold">Confirm Disbursement</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: CONFIRM PURCHASE (Operations) -->
<!-- ========================================================================= -->
<div class="modal fade" id="confirmPurchaseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="confirmPurchaseForm" action="<?= $GLOBALS['base_url'] ?>/asset-extra-purchases/confirm/<?= $req['id'] ?>?redirect=view" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="redirect" value="view">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-teal-transparent py-2.5 px-3">
                    <h6 class="modal-title fw-semibold text-teal"><i class="fa-solid fa-box-check me-1.5"></i>Confirm Purchase Received</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3 fs-13">
                    <p class="text-muted fs-12 mb-3">Confirm that the purchased items have been received. This will automatically generate a Product Code, register the asset in the Asset Tracker, and capitalize it in Financial Hub Balance Sheet.</p>
                    
                    <div class="mb-3">
                        <label class="form-label fs-12 fw-semibold">Purchase Date <span class="text-danger">*</span></label>
                        <input type="date" name="purchase_date" class="form-control form-control-sm" required value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fs-12 fw-semibold">Upload Invoice / Vendor Bill Document</label>
                        <input type="file" name="purchase_document" class="form-control form-control-sm">
                    </div>

                    <div class="mb-2">
                        <label class="form-label fs-12 fw-semibold">Receiving Notes / Asset Serial (Optional)</label>
                        <textarea name="purchase_note" class="form-control form-control-sm" rows="2" placeholder="Item serial numbers, warranty, or receiver remarks..."></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-teal text-white btn-sm px-4 fw-bold">Confirm &amp; Register Asset</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function toggleBankSection(method) {
    var bankSection = document.getElementById('bankSection');
    if (bankSection) {
        bankSection.style.display = (method === 'Bank' || method === 'Cheque') ? 'block' : 'none';
    }
}

function handleViewPageApproval(overrideStage) {
    var form = document.getElementById('viewInlineApprovalForm');
    if (!form) return;
    document.getElementById('viewApprovalStatus').value = 'approved';

    if (overrideStage && overrideStage !== '') {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Pending ' + overrideStage + "'s Approval",
                html: 'This purchase request is pending for <strong>' + overrideStage + "'s</strong> approval. Choose whether to approve <strong>only " + overrideStage + '</strong> and forward to next stage, or override all stages &amp; finalize now.',
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
                    if (!inp) {
                        inp = document.createElement('input');
                        inp.type = 'hidden';
                        inp.name = 'approve_mode';
                        form.appendChild(inp);
                    }
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
</script>

<?php include __DIR__ . '/../../layouts/accounts_head_approval_card.php'; ?>

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
            <?php
            $latestRevision = $latestRevision ?? null;
            $isEpRevOpen    = ($latestRevision && ($latestRevision['status'] ?? '') === 'open');
            $isExtraReqUnapproved = (($req['overall_status'] ?? 'pending') === 'pending')
                && (($req['dept_head_status'] ?? '') !== 'approved') && empty($req['dept_head_by'])
                && (($req['it_head_status'] ?? '') !== 'approved') && empty($req['it_head_by'])
                && (($req['operations_status'] ?? '') !== 'approved') && empty($req['operations_by'])
                && (($req['purchase_head_status'] ?? '') !== 'approved') && empty($req['purchase_head_by'])
                && (($req['qc_audit_status'] ?? '') !== 'approved') && empty($req['qc_audit_by'])
                && (($req['mgmt_status'] ?? '') !== 'approved') && empty($req['mgmt_by']);
            $canEditExtraReq = ($isExtraReqUnapproved || $isEpRevOpen || ($req['overall_status'] ?? '') === 'rejected') && (((int)($req['requested_by'] ?? 0) === $userId) || in_array($roleId, [1, 3], true));
            if ($canEditExtraReq):
            ?>
                <button type="button" class="btn btn-outline-warning btn-sm px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#editExtraPurchaseModal">
                    <i class="fa-solid fa-pen-to-square me-1"></i> <?= $isEpRevOpen ? 'Edit &amp; Resubmit' : 'Edit Request' ?>
                </button>
            <?php endif; ?>
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
    <?php
    $epCanSendBack = !empty($canApprove) && !in_array($roleId, [33, 34, 35], true) && !in_array($req['overall_status'] ?? '', ['paid', 'purchased', 'rejected'], true);
    $revModuleTable      = 'project_extra_assets';
    $revSourceId         = (int)$req['id'];
    $revCreatorUserId    = (int)($req['requested_by'] ?? 0);
    $revCanSendBack      = $epCanSendBack;
    $revReturnStage      = !empty($approvalStep) ? (string)$approvalStep : 'approver';
    $revReturnStageLabel = !empty($curStepTitle) ? (string)$curStepTitle : 'Approver';
    $revRedirectUrl      = '/asset-extra-purchase/' . (int)$req['id'];
    $revEditModalTarget  = '#editExtraPurchaseModal';
    $revEditLinkUrl      = '';
    $revLatest           = $latestRevision ?? null;
    include __DIR__ . '/../../layouts/revision_workflow_panel.php';
    ?>
    <div class="row ">
        <!-- LEFT COLUMN: Requisition Info & Audit Trail -->
        <div class="col-lg-7 col-xl-8">
            <!-- 4 Quick KPI Summary Cards -->
            <div class="row g-2 mb-3">
                <div class="col-sm-6 col-md-3">
                    <div class="card custom-card border-0 shadow-sm mb-0 p-3 bg-white" style="border-left: 4px solid #2563eb !important;">
                        <span class="text-muted fs-11 fw-semibold text-uppercase">Total Amount</span>
                        <h5 class="fw-bold text-primary mb-0 mt-1">TK <?= number_format($reqTotal, 2) ?></h5>
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
                        <h6 class="fw-bold text-teal mb-0 mt-1">TK <?= number_format((float)($req['paid_amount'] ?? 0), 2) ?></h6>
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
                            <span class="text-muted fs-11">Unit: TK <?= number_format((float)($req['unit_price'] ?? 0), 2) ?> &times; <?= (int)$req['quantity'] ?> pcs</span>
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
            <!-- 0A. Linked OPO Banner (If OPO Generated) -->
            <?php if (!empty($opoData)): ?>
            <div class="card custom-card border-0 shadow-sm mb-3" style="border-left: 4px solid #2563eb !important; background: #eff6ff;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <h6 class="mb-0 fw-bold fs-13 text-primary">
                            <i class="fa-solid fa-file-invoice-dollar me-1"></i> <?= htmlspecialchars($opoData['opo_number']) ?>
                        </h6>
                        <span class="badge bg-primary text-white fs-10 text-uppercase"><?= htmlspecialchars(str_replace('_', ' ', $opoData['status'])) ?></span>
                    </div>
                    <p class="fs-12 text-secondary mb-2">
                        Multi-Vendor CST completed &amp; Office Procurement Order generated. View full CST matrix, OPO approvals, and handover actions.
                    </p>
                    <div class="d-flex gap-2">
                        <a href="<?= $GLOBALS['base_url'] ?>/office-procurement/order/<?= (int)$opoData['id'] ?>" class="btn btn-primary btn-sm flex-fill fw-bold shadow-sm">
                            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Open OPO Page
                        </a>
                        <?php if (($opoData['status'] ?? '') === 'revision_requested'): ?>
                            <a href="<?= $GLOBALS['base_url'] ?>/office-procurement/cst/<?= (int)$req['id'] ?>" class="btn btn-warning btn-sm fw-bold text-dark">
                                <i class="fa-solid fa-pen me-1"></i> Edit CST
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- 0B. Role 32 (Admin & Operation): Approve & Assign Employee for Multi-Vendor CST -->
            <?php if (!empty($canAssignCst)): ?>
            <div class="card custom-card border-0 shadow-sm mb-3" style="border-left: 4px solid #0284c7 !important; background: #f0f9ff;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="mb-0 fw-bold fs-13 text-primary">
                            <i class="fa-solid fa-user-tag me-1"></i> Admin &amp; Operation — Assign CST
                        </h6>
                        <span class="badge bg-info text-white fs-10">Admin &amp; Operation</span>
                    </div>
                    <p class="fs-12 text-secondary mb-2">
                        Approve requisition &amp; assign an employee from <strong>Admin &amp; Operation</strong>, <strong>Purchase</strong>, or <strong>IT</strong> department to collect multi-vendor quotations &amp; prepare CST.
                    </p>
                    <?php if (!empty($req['assigned_cst_name'])): ?>
                        <div class="alert alert-light border py-1.5 px-2 fs-11 mb-2">
                            Currently Assigned: <strong><?= htmlspecialchars($req['assigned_cst_name']) ?></strong>
                            <?php if (!empty($req['assigned_cst_at'])): ?>
                                <span class="text-muted"> &bull; <?= date('d M Y, h:i A', strtotime($req['assigned_cst_at'])) ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <form action="<?= $GLOBALS['base_url'] ?>/asset-extra-purchases/assign-cst/<?= (int)$req['id'] ?>" method="POST">
                        <div class="mb-2">
                            <label class="form-label fs-11 fw-semibold text-dark mb-1">Select Employee (Admin &amp; Ops / Purchase / IT) <span class="text-danger">*</span></label>
                            <select name="assigned_cst_user_id" id="viewAssignCstSelect" class="form-select form-select-sm select2" style="width:100%;" required>
                                <option value="">-- Search by Employee ID, Name or Dept --</option>
                                <?php foreach ($assignableCstEmployees ?? [] as $emp): ?>
                                    <option value="<?= $emp['id'] ?>" <?= ((int)($req['assigned_cst_user_id'] ?? 0) === (int)$emp['id']) ? 'selected' : '' ?>>
                                        [<?= htmlspecialchars($emp['emp_code'] ?? ('UID-' . $emp['id'])) ?>] <?= htmlspecialchars($emp['full_name']) ?> — (<?= htmlspecialchars($emp['dept_name']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <textarea name="assign_note" class="form-control form-control-sm" rows="2" placeholder="Optional instructions for CST assignee..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold shadow-sm">
                            <i class="fa-solid fa-paper-plane me-1"></i> Approve &amp; Assign for CST
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- 0C. CST Assignee Action Card: Prepare Multi-Vendor CST & Generate OPO -->
            <?php if (!empty($req['assigned_cst_user_id']) && empty($opoData) && ((int)$req['assigned_cst_user_id'] === $userId || in_array($roleId, [1, 3, 17, 20, 31, 32, 38], true))): ?>
            <div class="card custom-card border-0 shadow-sm mb-3" style="border-left: 4px solid #16a34a !important; background: #f0fdf4;">
                <div class="card-body p-3">
                    <h6 class="mb-1 fw-bold fs-13 text-success">
                        <i class="fa-solid fa-scale-balanced me-1"></i> Ready for Multi-Vendor CST
                    </h6>
                    <p class="fs-12 text-secondary mb-2">
                        Assigned to <strong><?= htmlspecialchars($req['assigned_cst_name'] ?? 'Employee') ?></strong>. Add multiple vendor quotations &amp; rates to generate the Office Procurement Order (OPO).
                    </p>
                    <a href="<?= $GLOBALS['base_url'] ?>/office-procurement/cst/<?= (int)$req['id'] ?>" class="btn btn-success btn-sm w-100 fw-bold shadow-sm">
                        <i class="fa-solid fa-plus-circle me-1"></i> Prepare Multi-Vendor CST &amp; Generate OPO
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- 1. Approval Action Card (Only for Dept Head step, or legacy non-OPO flow) -->
            <?php if ($canApprove && ($approvalStep === 'dept_head' || (empty($canAssignCst) && empty($opoData) && empty($req['assigned_cst_user_id'])))): ?>
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
                                <label class="form-label fs-11 fw-semibold text-dark mb-1"><i class="fa-solid fa-store me-1 text-primary"></i>Select / Finalize Vendor (Optional)</label>
                                <select name="vendor_id" class="form-select form-select-sm">
                                    <option value="">-- Keep Existing / No Vendor --</option>
                                    <?php foreach ($vendors ?? [] as $vnd): ?>
                                        <?php
                                        $vComp = trim($vnd['company_name'] ?? '');
                                        $vName = trim($vnd['name'] ?? '');
                                        $vLabel = (!empty($vComp) && !empty($vName) && strcasecmp($vComp, $vName) !== 0)
                                            ? ($vComp . ' (' . $vName . ')')
                                            : (!empty($vComp) ? $vComp : $vName);
                                        ?>
                                        <option value="<?= $vnd['id'] ?>" <?= ((int)($req['vendor_id'] ?? 0) === (int)$vnd['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($vLabel) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-2.5">
                                <label class="form-label fs-11 fw-semibold text-dark mb-1">Upload Quotation / Comparative Statement (Optional)</label>
                                <input type="file" name="purchase_head_document" class="form-control form-control-sm">
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label fs-11 fw-semibold text-dark mb-1">Approval / Rejection Remarks (Optional)</label>
                            <textarea name="approval_note" class="form-control form-control-sm" rows="2" placeholder="Add approval remarks or notes..."></textarea>
                        </div>

                        <div class="d-flex flex-wrap justify-content-between gap-2 pt-1">
                            <button type="button" class="btn btn-sm btn-success px-3 flex-fill fw-bold shadow-sm" onclick="handleViewPageApproval('<?= htmlspecialchars($overrideWarningStage, ENT_QUOTES, 'UTF-8') ?>')">
                                <i class="fa-solid fa-check me-1"></i> Approve Request
                            </button>
                            <?php if (!empty($revCanSendBack) && empty($revIsOpen)): ?>
                                <button type="button" class="btn btn-sm btn-warning text-dark px-3 flex-fill fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#sendBackReviewModal_project_extra_assets">
                                    <i class="fa-solid fa-rotate-left me-1"></i> Send Back for Review
                                </button>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger px-3 flex-fill" onclick="document.getElementById('viewApprovalStatus').value='rejected'; return confirm('Are you sure you want to REJECT this purchase request?');">
                                <i class="fa-solid fa-xmark me-1"></i> Reject
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
                        All multi-stage approvals are completed. Accounts can now process and disburse the fund (TK <?= number_format($reqTotal, 2) ?>).
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

            <!-- 5. Sequential Timeline Card (New Office Procurement & OPO Flow) -->
            <?php
            $hasCstAssigned = !empty($req['assigned_cst_user_id']);
            $hasOpo = !empty($opoData);
            $opoPhSt = $hasOpo ? ($opoData['purchase_head_status'] ?? 'pending') : ($req['purchase_head_status'] ?? 'pending');
            $opoQcSt = $hasOpo ? ($opoData['qc_status'] ?? 'pending') : ($req['qc_audit_status'] ?? 'pending');
            $opoOpSt = $hasOpo ? ($opoData['op_head_status'] ?? 'pending') : ($isOpHeadPostQcApproved ? 'approved' : ($isOpHeadPostQcRejected ? 'rejected' : 'pending'));
            $isPayReceivedStep = !empty($req['assignee_payment_received_at']) || in_array($req['overall_status'] ?? '', ['process_on_purchase', 'purchased', 'received'], true);
            $isPurchasedStep   = !empty($req['purchase_confirmed_at']) || in_array($req['overall_status'] ?? '', ['purchased', 'received'], true);
            $isAssetRcvStep    = !empty($req['asset_received_at']) || ($req['overall_status'] ?? '') === 'received';

            $flowSteps = [
                [
                    'title'  => '1. Requisition Submitted',
                    'state'  => 'done',
                    'badge'  => 'Done',
                    'detail' => 'By <strong>' . htmlspecialchars($req['requested_by_name'] ?? '—') . '</strong>' . (!empty($req['created_at']) ? ' &bull; ' . date('d M Y, h:i A', strtotime($req['created_at'])) : ''),
                ],
            ];
            if ($dhStatus !== 'not_required') {
                $flowSteps[] = [
                    'title'  => '2. Department Head Approval',
                    'state'  => $dhStatus === 'approved' ? 'done' : ($dhStatus === 'rejected' ? 'rejected' : 'pending'),
                    'badge'  => ucfirst($dhStatus),
                    'detail' => (!empty($req['dept_head_by_name']) ? 'By <strong>' . htmlspecialchars($req['dept_head_by_name']) . '</strong>' : (!empty($req['reporting_boss_name']) ? 'Designated: <strong>' . htmlspecialchars($req['reporting_boss_name']) . '</strong>' : 'Awaiting Department Head'))
                        . (!empty($req['dept_head_at']) ? ' &bull; ' . date('d M Y, h:i A', strtotime($req['dept_head_at'])) : ''),
                ];
            }
            $flowSteps[] = [
                'title'  => '3. Admin & Operation — Approve & Assign CST',
                'state'  => $hasCstAssigned ? 'done' : ($opStatus === 'rejected' ? 'rejected' : 'pending'),
                'badge'  => $hasCstAssigned ? 'Assigned' : ($opStatus === 'rejected' ? 'Rejected' : 'Pending'),
                'detail' => $hasCstAssigned
                    ? ('Assigned to <strong>' . htmlspecialchars($req['assigned_cst_name'] ?? 'Employee') . '</strong>' . (!empty($req['assigned_cst_by_name']) ? ' by ' . htmlspecialchars($req['assigned_cst_by_name']) : '') . (!empty($req['assigned_cst_at']) ? ' &bull; ' . date('d M Y, h:i A', strtotime($req['assigned_cst_at'])) : ''))
                    : 'Awaiting Admin & Operation review & CST assignment',
            ];
            $flowSteps[] = [
                'title'  => '4. Multi-Vendor CST & OPO Generated',
                'state'  => $hasOpo ? (($opoData['status'] ?? '') === 'revision_requested' ? 'revision' : 'done') : 'pending',
                'badge'  => $hasOpo ? (($opoData['status'] ?? '') === 'revision_requested' ? 'Revision Needed' : htmlspecialchars($opoData['opo_number'])) : 'Pending CST',
                'detail' => $hasOpo
                    ? ('<a href="' . $GLOBALS['base_url'] . '/office-procurement/order/' . (int)$opoData['id'] . '" class="fw-bold text-primary text-decoration-none">' . htmlspecialchars($opoData['opo_number']) . '</a> &bull; ' . htmlspecialchars($opoData['vendor_name'] ?? $req['vendor_name'] ?? '') . (!empty($opoData['created_at']) ? ' &bull; ' . date('d M Y, h:i A', strtotime($opoData['created_at'])) : ''))
                    : ($hasCstAssigned ? 'Awaiting CST submission by <strong>' . htmlspecialchars($req['assigned_cst_name'] ?? '') . '</strong>' : 'Waiting for CST assignment'),
            ];
            $flowSteps[] = [
                'title'  => '5. OPO — Purchase Department Head',
                'state'  => $opoPhSt === 'approved' ? 'done' : ($opoPhSt === 'rejected' ? 'rejected' : ($opoPhSt === 'revision_requested' ? 'revision' : 'pending')),
                'badge'  => ucfirst(str_replace('_', ' ', $opoPhSt)),
                'detail' => (!empty($opoData['purchase_head_by_name']) ? 'By <strong>' . htmlspecialchars($opoData['purchase_head_by_name']) . '</strong>' . (!empty($opoData['purchase_head_at']) ? ' &bull; ' . date('d M Y, h:i A', strtotime($opoData['purchase_head_at'])) : '') : 'Awaiting Purchase Department Head'),
            ];
            $flowSteps[] = [
                'title'  => '6. OPO — QC & Internal Audit Head',
                'state'  => $opoQcSt === 'approved' ? 'done' : ($opoQcSt === 'rejected' ? 'rejected' : ($opoQcSt === 'revision_requested' ? 'revision' : 'pending')),
                'badge'  => ucfirst(str_replace('_', ' ', $opoQcSt)),
                'detail' => (!empty($opoData['qc_by_name']) ? 'By <strong>' . htmlspecialchars($opoData['qc_by_name']) . '</strong>' . (!empty($opoData['qc_at']) ? ' &bull; ' . date('d M Y, h:i A', strtotime($opoData['qc_at'])) : '') : 'Awaiting QC & Internal Audit Head'),
            ];
            $flowSteps[] = [
                'title'  => '7. OPO — Admin & Operation Head',
                'state'  => $opoOpSt === 'approved' ? 'done' : ($opoOpSt === 'rejected' ? 'rejected' : ($opoOpSt === 'revision_requested' ? 'revision' : 'pending')),
                'badge'  => ucfirst(str_replace('_', ' ', $opoOpSt)),
                'detail' => (!empty($opoData['op_head_by_name']) ? 'By <strong>' . htmlspecialchars($opoData['op_head_by_name']) . '</strong>' . (!empty($opoData['op_head_at']) ? ' &bull; ' . date('d M Y, h:i A', strtotime($opoData['op_head_at'])) : '') : 'Awaiting Admin & Operation Head'),
            ];
            $flowSteps[] = [
                'title'  => '8. Accounts Head Approval',
                'state'  => $isAhApproved ? 'done' : ($isAhRejected ? 'rejected' : 'pending'),
                'badge'  => $isAhApproved ? 'Approved' : ($isAhRejected ? 'Rejected' : 'Pending'),
                'detail' => $isAhApproved ? ('By <strong>' . htmlspecialchars($ahName ?: 'Accounts Head') . '</strong>' . (!empty($ahAt) ? ' &bull; ' . date('d M Y, h:i A', strtotime($ahAt)) : '')) : 'Auto Payment Request after OPO approval',
            ];
            if ($showMgmtStep) {
                $flowSteps[] = [
                    'title'  => '9. Management Final Approval (> TK10,000)',
                    'state'  => $isMgmtApproved ? 'done' : ($isMgmtRejected ? 'rejected' : 'pending'),
                    'badge'  => $isMgmtApproved ? 'Approved' : ($isMgmtRejected ? 'Rejected' : 'Pending'),
                    'detail' => $isMgmtApproved ? ('By <strong>' . htmlspecialchars($req['mgmt_by_name'] ?? 'Management') . '</strong>' . (!empty($req['mgmt_at']) ? ' &bull; ' . date('d M Y, h:i A', strtotime($req['mgmt_at'])) : '')) : 'Required after Accounts Head for > TK10,000',
                ];
            }
            $flowSteps[] = [
                'title'  => 'Accounts Payment Disbursement',
                'state'  => $isPaidOrDone ? 'done' : 'pending',
                'badge'  => $isPaidOrDone ? 'Disbursed' : 'Unpaid',
                'detail' => $isPaidOrDone ? ('By <strong>' . htmlspecialchars($req['paid_by_name'] ?? 'Accounts') . '</strong>' . (!empty($req['paid_at']) ? ' &bull; ' . date('d M Y, h:i A', strtotime($req['paid_at'])) : '')) : 'Awaiting Accounts disbursement',
            ];
            $flowSteps[] = [
                'title'  => 'Assignee Received Payment (Process on Purchase)',
                'state'  => $isPayReceivedStep ? 'done' : 'pending',
                'badge'  => $isPayReceivedStep ? 'Process on Purchase' : 'Pending',
                'detail' => $isPayReceivedStep ? ('Confirmed by <strong>' . htmlspecialchars($req['payment_received_by_name'] ?? $req['assigned_cst_name'] ?? '') . '</strong>' . (!empty($req['assignee_payment_received_at']) ? ' &bull; ' . date('d M Y, h:i A', strtotime($req['assignee_payment_received_at'])) : '')) : 'Awaiting CST assignee payment confirmation',
            ];
            $flowSteps[] = [
                'title'  => 'Purchased & Sent to Requester',
                'state'  => $isPurchasedStep ? 'done' : 'pending',
                'badge'  => $isPurchasedStep ? 'Sent to Requester' : 'Pending',
                'detail' => $isPurchasedStep ? ('By <strong>' . htmlspecialchars($req['purchased_by_name'] ?? $req['assigned_cst_name'] ?? '') . '</strong>' . (!empty($req['purchase_confirmed_at']) ? ' &bull; ' . date('d M Y, h:i A', strtotime($req['purchase_confirmed_at'])) : '')) : 'Awaiting purchase completion & handover',
            ];
            $flowSteps[] = [
                'title'  => 'Requester Received Asset (Asset Tracker & Accounts)',
                'state'  => $isAssetRcvStep ? 'done' : 'pending',
                'badge'  => $isAssetRcvStep ? 'Received' : 'Pending',
                'detail' => $isAssetRcvStep ? ('Received by <strong>' . htmlspecialchars($req['asset_received_by_name'] ?? $req['requested_by_name'] ?? '') . '</strong>' . (!empty($req['asset_received_at']) ? ' &bull; ' . date('d M Y, h:i A', strtotime($req['asset_received_at'])) : '')) : 'Awaiting requester confirmation → Auto Asset Code & Accounts Sync',
            ];
            ?>
            <div class="card custom-card border-0 shadow-sm mb-3">
                <div class="card-header bg-light py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
                    <h6 class="card-title mb-0 fw-bold fs-13 text-dark">
                        <i class="fa-solid fa-timeline text-primary me-1.5"></i> Approval &amp; Procurement Flow
                    </h6>
                    <span class="badge bg-light text-secondary border fs-10"><?= count($flowSteps) ?> Stages</span>
                </div>
                <div class="card-body p-3">
                    <div class="timeline-workflow d-flex flex-column gap-3 fs-12">
                        <?php foreach ($flowSteps as $fs):
                            $dotCls = match($fs['state']) {
                                'done'     => 'bg-success text-white',
                                'rejected' => 'bg-danger text-white',
                                'revision' => 'bg-warning text-dark',
                                default    => 'bg-light text-muted border',
                            };
                            $iconCls = match($fs['state']) {
                                'done'     => 'fa-check',
                                'rejected' => 'fa-xmark',
                                'revision' => 'fa-rotate-left',
                                default    => 'fa-hourglass-half',
                            };
                            $badgeCls = match($fs['state']) {
                                'done'     => 'bg-success-subtle text-success',
                                'rejected' => 'bg-danger-subtle text-danger',
                                'revision' => 'bg-warning-subtle text-dark',
                                default    => 'bg-light text-secondary border',
                            };
                        ?>
                        <div class="d-flex align-items-start gap-2.5">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 <?= $dotCls ?>" style="width: 24px; height: 24px; font-size: 11px;">
                                <i class="fa-solid <?= $iconCls ?>"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between gap-1">
                                    <strong class="text-dark"><?= $fs['title'] ?></strong>
                                    <span class="badge <?= $badgeCls ?> fs-10"><?= $fs['badge'] ?></span>
                                </div>
                                <div class="text-muted fs-11 mt-0.5"><?= $fs['detail'] ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
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
                        <label class="form-label fs-12 fw-semibold">Payment Amount (TK) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="paid_amount" class="form-control form-control-sm" required value="<?= number_format($reqTotal, 2, '.', '') ?>" max="<?= number_format($reqTotal, 2, '.', '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fs-12 fw-semibold">Payment Account (Chart of Accounts) <span class="text-danger">*</span></label>
                        <select name="payment_account_id" class="form-select form-select-sm" required>
                            <option value="">-- Select Cash or Bank Account --</option>
                            <?php foreach ($bankAccounts ?? [] as $bAcc): ?>
                                <option value="<?= $bAcc['id'] ?>">
                                    <?= htmlspecialchars(($bAcc['account_code'] ?? '') . ' - ' . ($bAcc['account_name'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fs-12 fw-semibold">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select form-select-sm" required>
                            <option value="Cash" selected>Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Cheque">Cheque</option>
                            <option value="bKash">bKash</option>
                            <option value="Nagad">Nagad</option>
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

<?php if ($canEditExtraReq ?? false): ?>
<!-- Edit Office Procurement Request Modal (Before 1st Approval or Review Requested) -->
<div class="modal custom-modal fade" id="editExtraPurchaseModal" tabindex="-1" aria-labelledby="editExtraPurchaseModalLabel" aria-hidden="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header pb-3 mb-2 border-bottom">
                <h5 class="modal-title fw-bold text-dark" id="editExtraPurchaseModalLabel">
                    <i class="fa-solid fa-pen-to-square me-2 text-warning"></i> Edit Procurement Request #<?= (int)$req['id'] ?>
                </h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body p-0">
                <form method="POST" action="<?= $GLOBALS['base_url'] ?>/asset-extra-purchases/update/<?= (int)$req['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Item / Service Name <span class="text-danger">*</span></label>
                        <input type="text" name="item_name" class="form-control" value="<?= htmlspecialchars($req['item_name'] ?? '') ?>" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Quantity <span class="text-danger">*</span></label>
                            <input type="number" min="1" name="quantity" class="form-control" value="<?= (int)($req['quantity'] ?? 1) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Estimated Unit Price (৳) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="unit_price" class="form-control" value="<?= htmlspecialchars($req['unit_price'] ?? 0) ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Justification / Remarks</label>
                        <textarea name="justification" class="form-control" rows="3"><?= htmlspecialchars($req['justification'] ?? '') ?></textarea>
                    </div>
                    <?php if ($isEpRevOpen ?? false): ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Update / Response Note to <?= htmlspecialchars($latestRevision['return_stage_label'] ?? 'Approver') ?></label>
                        <textarea name="resubmitted_note" class="form-control" rows="2" placeholder="Briefly explain what you updated..."></textarea>
                    </div>
                    <?php endif; ?>
                    <div class="modal-footer px-0 pb-0 pt-3 border-top d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">
                            <i class="fa-solid fa-save me-1"></i> <?= ($isEpRevOpen ?? false) ? 'Save & Resubmit' : 'Save Changes' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
(function() {
    function initExtraPurchaseEditModal() {
        if (typeof jQuery === 'undefined' || typeof bootstrap === 'undefined') {
            setTimeout(initExtraPurchaseEditModal, 50);
            return;
        }
        jQuery(function($) {
            if ($('#editExtraPurchaseModal').length && $('#editExtraPurchaseModal').parent().prop('tagName') !== 'BODY') {
                $('#editExtraPurchaseModal').appendTo('body');
            }
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initExtraPurchaseEditModal);
    } else {
        initExtraPurchaseEditModal();
    }
})();
</script>
<?php endif; ?>

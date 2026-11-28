

<?php
if (!class_exists('ApprovalRequest')) {
    require_once __DIR__ . '/../../models/ApprovalRequest.php';
}
$ahInfo = ApprovalRequest::getAccountsHeadApprovalInfo($GLOBALS['db'] ?? ($this->db ?? null), 'project_other_costs', (int)$request['id']);
$isAhApproved = empty($ahInfo['has_request']) || !empty($ahInfo['is_accounts_head_approved']);
?>

<main id="main" class="main">
    <div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h1>Review Project Other Cost Request</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/">Home</a></li>
                    <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/project-other-costs">Project Other Costs</a></li>
                    <li class="breadcrumb-item active">Review</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <?php
            $latestRevision = $latestRevision ?? null;
            $isOcRevOpen    = ($latestRevision && ($latestRevision['status'] ?? '') === 'open');
            $isOtherCostUnapproved = (($request['status'] ?? '') === 'pending')
                && (($request['dept_head_status'] ?? '') !== 'approved')
                && empty($request['dept_head_approved_by'])
                && empty($request['qc_audit_approved_id'])
                && empty($request['management_apporved_by_id'])
                && empty($request['payment_by_id']);
            $canEditOtherCost = ($isOtherCostUnapproved || $isOcRevOpen) && (((int)($request['created_by_id'] ?? 0) === (int)($userId ?? 0)) || in_array((int)($roleId ?? 0), [1, 3], true));
            if ($canEditOtherCost):
            ?>
                <button type="button" class="btn btn-outline-warning btn-sm shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#editOtherCostModal">
                    <i class="fa-solid fa-pen-to-square me-1"></i> <?= $isOcRevOpen ? 'Edit &amp; Resubmit' : 'Edit Request' ?>
                </button>
            <?php endif; ?>
            <?php if ($request['status'] === 'paid'): ?>
                <a href="<?= $GLOBALS['base_url'] ?>/expense-adjustments/create?parent_type=other_cost&cashout_request_id=<?= $request['id'] ?>&project_id=<?= $request['project_id'] ?>" class="btn btn-warning btn-sm shadow-sm">
                    <i class="bi bi-sliders me-1"></i> Settlement / Adjustment
                </a>
                <a href="<?= $GLOBALS['base_url'] ?>/print-debit-voucher?module=project_other_costs&id=<?= $request['id'] ?>" target="_blank" class="btn btn-primary btn-sm">
                    <i class="bi bi-printer me-1"></i> Debit Voucher
                </a>
            <?php endif; ?>
            <a href="<?= $GLOBALS['base_url'] ?>/project-other-costs" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <section class="section">
        <?php
        $ocCanSendBack = false;
        $ocReturnStage = 'approver';
        $ocReturnStageLabel = 'Approver';
        $ocRoleId = (int)($roleId ?? 0);
        if (!in_array($ocRoleId, [33, 34, 35], true) && !in_array($request['status'] ?? '', ['paid', 'rejected'], true)) {
            $creatorBossId = (int)($request['creator_reporting_user_id'] ?? 0);
            if (($request['dept_head_status'] ?? '') === 'pending' && ($creatorBossId === (int)($userId ?? 0) || in_array($ocRoleId, [1, 3], true))) {
                $ocCanSendBack = true; $ocReturnStage = 'dept_head'; $ocReturnStageLabel = 'Department Head';
            } elseif (($request['status'] ?? '') === 'pending' && ($request['dept_head_status'] ?? '') !== 'pending' && in_array($ocRoleId, [13, 24, 25, 1, 3], true)) {
                $ocCanSendBack = true; $ocReturnStage = 'qc'; $ocReturnStageLabel = 'QC & Internal Audit';
            } elseif (($request['status'] ?? '') === 'qc_approved' && in_array($ocRoleId, [3, 15, 20, 22, 32, 38, 1], true)) {
                $ocCanSendBack = true;
                $ocReturnStage = in_array($ocRoleId, [20, 32], true) ? 'op_head' : 'mgmt';
                $ocReturnStageLabel = in_array($ocRoleId, [20, 32], true) ? 'Admin & Operation Head' : 'Management / PM Head';
            } elseif (($request['status'] ?? '') === 'management_approved' && !$isAhApproved && in_array($ocRoleId, [30, 1, 3], true)) {
                $ocCanSendBack = true; $ocReturnStage = 'accounts_head'; $ocReturnStageLabel = 'Accounts Head';
            }
        }
        $revModuleTable      = 'project_other_costs';
        $revSourceId         = (int)$request['id'];
        $revCreatorUserId    = (int)($request['created_by_id'] ?? 0);
        $revCanSendBack      = $ocCanSendBack;
        $revReturnStage      = $ocReturnStage;
        $revReturnStageLabel = $ocReturnStageLabel;
        $revRedirectUrl      = '/project-other-costs/review/' . (int)$request['id'];
        $revEditModalTarget  = '#editOtherCostModal';
        $revEditLinkUrl      = '';
        $revLatest           = $latestRevision ?? null;
        include __DIR__ . '/../layouts/revision_workflow_panel.php';
        ?>

        <?php 
        if (!empty($ahInfo['has_request'])) {
            $sourceTable = 'project_other_costs';
            $sourceId = (int)$request['id'];
            include __DIR__ . '/../layouts/accounts_head_approval_card.php';
        }
        ?>

        <?php if ($request['status'] === 'rejected'): ?>
            <?php
            $rejBy = 'Authority';
            $rejStep = 'Rejected';
            $rejAt = $request['updated_at'] ?? '';
            $rejNote = '';
            if (($request['dept_head_status'] ?? '') === 'rejected') {
                $rejStep = 'Department Head';
                $rejBy = $request['dh_name'] ?? 'Department Head';
                $rejAt = $request['dept_head_approved_at'] ?? '';
                $rejNote = $request['dept_head_approval_note'] ?? '';
            } elseif (!empty($request['management_apporved_by_id'])) {
                $rejStep = 'Management / Operation Head';
                $rejBy = $request['mgmt_name'] ?? 'Management';
                $rejAt = $request['management_apporved_at'] ?? '';
                $rejNote = $request['management_apporved_note'] ?? '';
            } elseif (!empty($request['qc_audit_approved_id'])) {
                $rejStep = 'QC & Internal Audit';
                $rejBy = $request['qc_name'] ?? 'QC Audit';
                $rejAt = $request['qc_audit_approved_at'] ?? '';
                $rejNote = $request['qc_audit_note'] ?? '';
            }
            ?>
            <div class="alert alert-danger border border-danger d-flex align-items-center justify-content-between p-3 mb-4 rounded-3 shadow-sm">
                <div class="d-flex align-items-center gap-3">
                    <i class="fa-solid fa-circle-xmark fs-2 text-danger flex-shrink-0"></i>
                    <div>
                        <h5 class="fw-bold mb-1 text-danger">Request Rejected</h5>
                        <div class="fs-13 text-dark">
                            Rejected by <strong><?= htmlspecialchars($rejBy) ?></strong> (<?= htmlspecialchars($rejStep) ?>)
                            <?php if (!empty($rejAt)): ?>
                                • <span class="text-muted"><?= date('d M Y, h:i A', strtotime($rejAt)) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($rejNote)): ?>
                            <div class="fs-12 text-danger mt-1 fst-italic">
                                <i class="fa-solid fa-quote-left me-1"></i>"<?= htmlspecialchars($rejNote) ?>"
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="badge bg-danger fs-12 px-3 py-2">Rejected</span>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-8">
                <!-- Request Details Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3 px-4 border-bottom">
                        <h5 class="card-title mb-0 p-0">Request Details</h5>
                    </div>
                    <div class="card-body p-4">
                        <?php 
                        $creatorAttachment = !empty($request['creator_attachment']) ? $request['creator_attachment'] : (!empty($request['attachment']) ? $request['attachment'] : null);
                        $creatorFileDetails = getProjectOtherCostFileDetails($creatorAttachment);
                        if (!empty($creatorAttachment)): 
                            $fileExists = $creatorFileDetails['exists'];
                            $downloadLink = $creatorFileDetails['url'];
                        ?>
                        <div class="alert <?= $fileExists ? 'alert-info' : 'alert-warning' ?> border d-flex flex-wrap align-items-center justify-content-between mb-3 p-3" style="background: <?= $fileExists ? 'rgba(13, 202, 240, 0.08)' : 'rgba(255, 193, 7, 0.08)' ?>; border-color: <?= $fileExists ? 'rgba(13, 202, 240, 0.3)' : 'rgba(255, 193, 7, 0.3)' ?> !important; border-radius: 10px; gap: 15px;">
                            <div class="d-flex align-items-center gap-3">
                                <i class="fa-solid fa-file-invoice fs-4 <?= $fileExists ? 'text-info' : 'text-warning' ?> flex-shrink-0"></i>
                                <div>
                                    <strong class="d-block text-dark">Attached Document / Requisition File:</strong>
                                    <span class="text-muted small"><?= htmlspecialchars($creatorFileDetails['filename']) ?></span>
                                </div>
                            </div>
                            <?php if ($fileExists): ?>
                                <a href="<?= $downloadLink ?>" target="_blank" class="btn btn-outline-info btn-sm flex-shrink-0">
                                    <i class="fa-solid fa-eye me-1"></i> View / Download
                                </a>
                            <?php else: ?>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-warning text-dark border border-warning"><i class="fa-solid fa-triangle-exclamation me-1"></i> File Missing</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <table class="table table-bordered align-middle mb-0">
                            <tbody>
                                <tr>
                                    <th class="bg-light" style="width: 30%;">Project Name</th>
                                    <td><?= htmlspecialchars($request['project_name']) ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Cost Type</th>
                                    <td><?= htmlspecialchars(ProjectOtherCost::getCostTypeLabel($request['rcost_type'])) ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Purpose</th>
                                    <td><?= nl2br(htmlspecialchars($request['purpose'])) ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Request Amount</th>
                                    <td class="text-primary fw-bold">৳ <?= number_format($request['request_amount'], 2) ?></td>
                                </tr>
                                <?php if(!empty($creatorAttachment)): ?>
                                <tr>
                                    <th class="bg-light">Attachment</th>
                                    <td>
                                        <?php if ($creatorFileDetails['exists']): ?>
                                            <a href="<?= $creatorFileDetails['url'] ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-paperclip"></i> View File</a>
                                            <span class="text-muted small ms-2"><?= htmlspecialchars($creatorFileDetails['filename']) ?></span>
                                        <?php else: ?>
                                            <div class="d-inline-flex align-items-center gap-2">
                                                <span class="badge bg-warning text-dark border border-warning">
                                                    <i class="fa-solid fa-triangle-exclamation me-1"></i> File Missing
                                                </span>
                                                <span class="text-muted small"><?= htmlspecialchars($creatorFileDetails['filename']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php if($request['approved_amount']): ?>
                                <tr>
                                    <th class="bg-light text-success">Approved Amount</th>
                                    <td class="text-success fw-bold">
                                        ৳ <?= number_format($request['approved_amount'], 2) ?>
                                        <div class="mt-1">
                                            <?php
                                            $approvedByText = 'N/A';
                                            if (!empty($request['management_apporved_by_id']) && stripos($request['mgmt_name'] ?? '', 'accounts') === false) {
                                                $approvedByText = $request['mgmt_name'];
                                            } elseif (!empty($ahInfo['op_head_name']) && !empty($ahInfo['is_op_head_approved'])) {
                                                $approvedByText = $ahInfo['op_head_name'] . (!empty($ahInfo['op_head_designation']) ? ' (' . $ahInfo['op_head_designation'] . ')' : '');
                                            } elseif (!empty($request['mgmt_name'])) {
                                                $approvedByText = $request['mgmt_name'];
                                            } elseif (!empty($request['qc_name'])) {
                                                $approvedByText = $request['qc_name'];
                                            }
                                            ?>
                                            <small class="text-muted fw-normal" style="font-size: 0.75rem;">
                                                <i class="bi bi-person me-1"></i>Approved by <?= htmlspecialchars($approvedByText) ?>
                                            </small>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php if($request['status'] === 'paid'): ?>
                                <tr>
                                    <th class="bg-light text-primary">Paid Amount</th>
                                    <td class="text-primary fw-bold">
                                        ৳ <?= number_format($request['payment_amount'] ?? $request['approved_amount'], 2) ?>
                                        <div class="mt-1">
                                            <small class="text-muted fw-normal" style="font-size: 0.75rem;">
                                                <i class="bi bi-cash me-1"></i>Disbursed by <?= htmlspecialchars($request['pay_name'] ?? 'Accounts') ?>
                                                <?php 
                                                $paymentAtDisplay = !empty($request['payment_at']) ? $request['payment_at'] : null;
                                                if ($paymentAtDisplay && date('H:i:s', strtotime($paymentAtDisplay)) === '00:00:00' && !empty($request['updated_at'])) {
                                                    $paymentAtDisplay = date('Y-m-d', strtotime($paymentAtDisplay)) . ' ' . date('H:i:s', strtotime($request['updated_at']));
                                                }
                                                if (!empty($paymentAtDisplay)): ?>
                                                    on <?= date('d M Y h:i A', strtotime($paymentAtDisplay)) ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <th class="bg-light">Status</th>
                                    <td>
                                        <span class="badge bg-<?= $request['status'] == 'pending' ? 'warning' : ($request['status'] == 'paid' ? 'success' : ($request['status'] == 'rejected' ? 'danger' : 'info')) ?>">
                                            <?= ucfirst(str_replace('_', ' ', $request['status'])) ?>
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Timeline / History Card -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3 px-4 border-bottom">
                        <h5 class="card-title mb-0 p-0">Approval History</h5>
                    </div>
                    <div class="card-body p-4">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item py-3 px-0 lh-base">
                                <strong>Created By:</strong> <?= htmlspecialchars($request['creator_name'] ?? 'N/A') ?><br>
                                <small class="text-muted"><?= date('d M Y h:i A', strtotime($request['created_at'])) ?></small>
                                <?php if (!empty($creatorAttachment)): ?>
                                    <br>
                                    <?php if ($creatorFileDetails['exists']): ?>
                                        <a href="<?= $creatorFileDetails['url'] ?>" target="_blank" class="badge bg-success text-white mt-1 text-decoration-none">
                                            <i class="bi bi-paperclip me-1"></i> View Attached File
                                        </a>
                                    <?php else: ?>
                                        <div class="mt-1 d-inline-flex align-items-center gap-1">
                                            <span class="badge bg-warning text-dark border border-warning"><i class="fa-solid fa-triangle-exclamation me-1"></i> File Missing</span>
                                            <span class="text-muted small ms-1"><?= htmlspecialchars($creatorFileDetails['filename']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </li>
                            <li class="list-group-item py-3 px-0 lh-base">
                                <strong>Dept Head Approval:</strong> 
                                <?php if($request['dept_head_status'] === 'not_required'): ?>
                                    <span class="text-muted"><i class="bi bi-dash-circle"></i> Not Required (No Dept Head Added)</span>
                                <?php elseif($request['dept_head_status'] === 'approved'): ?>
                                    <span class="text-success"><i class="bi bi-check-circle"></i> Approved</span> by <?= htmlspecialchars($request['dh_name'] ?? 'N/A') ?>
                                    <?php if ($request['status'] === 'pending' && empty($request['qc_audit_approved_id']) && ((int)($request['creator_reporting_user_id'] ?? 0) === (int)$userId || (int)($request['dept_head_approved_by'] ?? 0) === (int)$userId)): ?>
                                        <button type="button" class="btn btn-xs btn-outline-danger ms-2 py-0 px-2" data-bs-toggle="collapse" data-bs-target="#deptHeadRejectCollapse">
                                            <i class="bi bi-x-circle me-1"></i> Reject
                                        </button>
                                        <div class="collapse mt-2" id="deptHeadRejectCollapse">
                                            <form method="POST" action="<?= $GLOBALS['base_url'] ?>/project-other-costs/approve-dept-head/<?= $request['id'] ?>" class="border p-2 rounded bg-light">
                                                <div class="mb-2">
                                                    <textarea class="form-control form-control-sm" name="dept_head_approval_note" rows="2" placeholder="Reason for rejection..." required></textarea>
                                                </div>
                                                <button type="submit" name="dept_head_status" value="rejected" class="btn btn-sm btn-danger w-100" onclick="return confirm('Are you sure you want to reject this request?');">
                                                    <i class="bi bi-x-circle me-1"></i> Confirm Rejection
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?><br>
                                    <?php if(!empty($request['dept_head_approved_at'])): ?>
                                        <small class="text-muted"><?= date('d M Y h:i A', strtotime($request['dept_head_approved_at'])) ?></small><br>
                                    <?php endif; ?>
                                    <?php if($request['dept_head_approval_note']): ?><small>Note: <?= htmlspecialchars($request['dept_head_approval_note']) ?></small><?php endif; ?>
                                <?php elseif($request['dept_head_status'] === 'rejected'): ?>
                                    <span class="text-danger"><i class="bi bi-x-circle"></i> Rejected</span> by <?= htmlspecialchars($request['dh_name'] ?? 'N/A') ?><br>
                                    <?php if(!empty($request['dept_head_approved_at'])): ?>
                                        <small class="text-muted"><?= date('d M Y h:i A', strtotime($request['dept_head_approved_at'])) ?></small><br>
                                    <?php endif; ?>
                                    <?php if($request['dept_head_approval_note']): ?><small>Note: <?= htmlspecialchars($request['dept_head_approval_note']) ?></small><?php endif; ?>
                                <?php else: ?>
                                    <span class="text-warning"><i class="bi bi-clock"></i> Pending</span>
                                    <?php if (!empty($request['designated_dh_name'])): ?>
                                        from <strong><?= htmlspecialchars($request['designated_dh_name']) ?></strong>
                                        <?php if (!empty($request['designated_dh_designation'])): ?>
                                            <span class="text-muted small">(<?= htmlspecialchars($request['designated_dh_designation']) ?>)</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </li>
                            <li class="list-group-item py-3 px-0 lh-base">
                                <strong>QC Approval:</strong> 
                                <?php if($request['qc_audit_approved_id']): ?>
                                    <?php if($request['status'] === 'rejected' && empty($request['management_apporved_by_id'])): ?>
                                        <span class="text-danger"><i class="bi bi-x-circle"></i> Rejected</span> by <?= htmlspecialchars($request['qc_name'] ?? 'N/A') ?>
                                    <?php else: ?>
                                        <span class="text-success"><i class="bi bi-check-circle"></i> Approved</span> by <?= htmlspecialchars($request['qc_name'] ?? 'N/A') ?>
                                    <?php endif; ?>
                                    <?php 
                                    $isOpHeadDoneForQc = !empty($ahInfo['is_op_head_approved']) || (strtolower($ahInfo['op_head_status'] ?? '') === 'approved');
                                    if ($request['status'] === 'qc_approved' && !$isOpHeadDoneForQc && (in_array($roleId, [13, 24, 25]) || (int)($request['qc_audit_approved_id'] ?? 0) === (int)$userId)): ?>
                                        <button type="button" class="btn btn-xs btn-outline-danger ms-2 py-0 px-2" data-bs-toggle="collapse" data-bs-target="#qcRejectCollapse">
                                            <i class="bi bi-x-circle me-1"></i> Reject
                                        </button>
                                        <div class="collapse mt-2" id="qcRejectCollapse">
                                            <form method="POST" action="<?= $GLOBALS['base_url'] ?>/project-other-costs/approve-qc/<?= $request['id'] ?>" class="border p-2 rounded bg-light">
                                                <div class="mb-2">
                                                    <textarea class="form-control form-control-sm" name="qc_audit_note" rows="2" placeholder="Reason for QC rejection..." required></textarea>
                                                </div>
                                                <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger w-100" onclick="return confirm('Are you sure you want to reject this request?');">
                                                    <i class="bi bi-x-circle me-1"></i> Confirm QC Rejection
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?><br>
                                    <?php if(!empty($request['qc_audit_approved_at'])): ?>
                                        <small class="text-muted"><?= date('d M Y h:i A', strtotime($request['qc_audit_approved_at'])) ?></small><br>
                                    <?php endif; ?>
                                    <?php if($request['qc_audit_note']): ?><small>Note: <?= htmlspecialchars($request['qc_audit_note']) ?></small><?php endif; ?>
                                <?php elseif($request['dept_head_status'] === 'rejected'): ?>
                                    <span class="text-muted"><i class="bi bi-dash-circle"></i> Skipped (Rejected by Dept Head)</span>
                                <?php else: ?>
                                    <span class="text-warning"><i class="bi bi-clock"></i> Pending</span>
                                <?php endif; ?>
                            </li>
                            <!-- Admin & Operation Head Approval -->
                            <li class="list-group-item py-3 px-0 lh-base">
                                <strong>Admin &amp; Operation Head Approval:</strong>
                                <?php 
                                $isOpHeadApproved = !empty($ahInfo['is_op_head_approved']) || (strtolower($ahInfo['op_head_status'] ?? '') === 'approved');
                                ?>
                                <?php if ($isOpHeadApproved): ?>
                                    <span class="text-success"><i class="bi bi-check-circle"></i> Approved</span> by <?= htmlspecialchars($ahInfo['op_head_name'] ?? 'Admin & Operation Head') ?>
                                    <?php if (!empty($ahInfo['op_head_designation'])): ?>
                                        <span class="text-muted small">(<?= htmlspecialchars($ahInfo['op_head_designation']) ?>)</span>
                                    <?php endif; ?>
                                    <?php if (!empty($ahInfo['op_head_code'])): ?>
                                        <span class="badge bg-light text-secondary border ms-1">[<?= htmlspecialchars($ahInfo['op_head_code']) ?>]</span>
                                    <?php endif; ?>
                                    <?php if ($request['status'] === 'qc_approved' && empty($ahInfo['is_accounts_head_approved']) && (in_array($roleId, [20, 32]) || (int)($ahInfo['op_head_by'] ?? 0) === (int)$userId)): ?>
                                        <button type="button" class="btn btn-xs btn-outline-danger ms-2 py-0 px-2" data-bs-toggle="collapse" data-bs-target="#opHeadRejectCollapse">
                                            <i class="bi bi-x-circle me-1"></i> Reject
                                        </button>
                                        <div class="collapse mt-2" id="opHeadRejectCollapse">
                                            <form method="POST" action="<?= $GLOBALS['base_url'] ?>/project-other-costs/approve-management/<?= $request['id'] ?>" class="border p-2 rounded bg-light">
                                                <div class="mb-2">
                                                    <textarea class="form-control form-control-sm" name="management_apporved_note" rows="2" placeholder="Reason for rejection..." required></textarea>
                                                </div>
                                                <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger w-100" onclick="return confirm('Are you sure you want to reject this request?');">
                                                    <i class="bi bi-x-circle me-1"></i> Confirm Rejection
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?><br>
                                    <?php if (!empty($ahInfo['op_head_at'])): ?>
                                        <small class="text-muted"><?= date('d M Y h:i A', strtotime($ahInfo['op_head_at'])) ?></small><br>
                                    <?php endif; ?>
                                    <?php if (!empty($ahInfo['op_head_note'])): ?>
                                        <small>Note: <?= htmlspecialchars($ahInfo['op_head_note']) ?></small>
                                    <?php endif; ?>
                                <?php elseif ($request['status'] === 'rejected' && ($ahInfo['op_head_status'] ?? '') === 'rejected'): ?>
                                    <span class="text-danger"><i class="bi bi-x-circle"></i> Rejected</span> by <?= htmlspecialchars($ahInfo['op_head_name'] ?? 'Admin & Operation Head') ?><br>
                                    <?php if (!empty($ahInfo['op_head_at'])): ?>
                                        <small class="text-muted"><?= date('d M Y h:i A', strtotime($ahInfo['op_head_at'])) ?></small><br>
                                    <?php endif; ?>
                                    <?php if (!empty($ahInfo['op_head_note'])): ?><small>Note: <?= htmlspecialchars($ahInfo['op_head_note']) ?></small><?php endif; ?>
                                <?php elseif ($request['dept_head_status'] === 'rejected' || ($request['status'] === 'rejected' && empty($request['qc_audit_approved_id']))): ?>
                                    <span class="text-muted"><i class="bi bi-dash-circle"></i> Skipped (Rejected at Earlier Stage)</span>
                                <?php else: ?>
                                    <span class="text-warning"><i class="bi bi-clock"></i> Pending</span>
                                    <?php if (((float)($request['approved_amount'] ?? $request['request_amount'])) > 10000): ?>
                                        <span class="text-danger small fw-semibold ms-1">(Mandatory for &gt; ৳10,000)</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </li>

                            <!-- Accounts Head Approval -->
                            <li class="list-group-item py-3 px-0 lh-base">
                                <strong>Accounts Head Approval:</strong>
                                <?php if (!empty($ahInfo['is_accounts_head_approved'])): ?>
                                    <span class="text-success"><i class="bi bi-check-circle"></i> Approved</span> by <?= htmlspecialchars($ahInfo['approved_by_name'] ?? 'Accounts Head') ?>
                                    <?php if (!empty($ahInfo['approved_by_designation'])): ?>
                                        <span class="text-muted small">(<?= htmlspecialchars($ahInfo['approved_by_designation']) ?>)</span>
                                    <?php endif; ?>
                                    <?php if (!empty($ahInfo['approved_by_emp_id'])): ?>
                                        <span class="badge bg-light text-secondary border ms-1">[<?= htmlspecialchars($ahInfo['approved_by_emp_id']) ?>]</span>
                                    <?php endif; ?><br>
                                    <?php if (!empty($ahInfo['approved_at'])): ?>
                                        <small class="text-muted"><?= date('d M Y h:i A', strtotime($ahInfo['approved_at'])) ?></small><br>
                                    <?php endif; ?>
                                    <?php if (!empty($ahInfo['approval_note'])): ?>
                                        <small>Note: <?= htmlspecialchars($ahInfo['approval_note']) ?></small>
                                    <?php endif; ?>
                                <?php elseif (!empty($ahInfo['is_accounts_head_rejected'])): ?>
                                    <span class="text-danger"><i class="bi bi-x-circle"></i> Rejected</span> by <?= htmlspecialchars($ahInfo['approved_by_name'] ?? 'Accounts Head') ?><br>
                                    <?php if (!empty($ahInfo['approved_at'])): ?>
                                        <small class="text-muted"><?= date('d M Y h:i A', strtotime($ahInfo['approved_at'])) ?></small><br>
                                    <?php endif; ?>
                                    <?php if (!empty($ahInfo['approval_note'])): ?><small>Note: <?= htmlspecialchars($ahInfo['approval_note']) ?></small><?php endif; ?>
                                <?php elseif ($request['status'] === 'rejected'): ?>
                                    <span class="text-muted"><i class="bi bi-dash-circle"></i> Skipped (Rejected at Earlier Stage)</span>
                                <?php else: ?>
                                    <span class="text-warning"><i class="bi bi-clock"></i> Pending Accounts Head Verification & Approval</span>
                                <?php endif; ?>
                            </li>

                            <?php
                            $isUnder10k = ((float)($request['approved_amount'] ?? $request['request_amount']) <= 10000.01);
                            $isDistinctMgmtUser = !empty($request['management_apporved_by_id']) 
                                && stripos($request['mgmt_name'] ?? '', 'accounts') === false
                                && (empty($ahInfo['op_head_name']) || strtolower(trim($request['mgmt_name'] ?? '')) !== strtolower(trim($ahInfo['op_head_name'] ?? '')));
                            ?>
                            <?php if (!$isUnder10k || $isDistinctMgmtUser): ?>
                            <!-- Management Final Approval (> ৳10,000) -->
                            <li class="list-group-item py-3 px-0 lh-base">
                                <strong>Management Approval:</strong>
                                <?php if ($isDistinctMgmtUser): ?>
                                    <?php if ($request['status'] === 'rejected'): ?>
                                        <span class="text-danger"><i class="bi bi-x-circle"></i> Rejected</span> by <?= htmlspecialchars($request['mgmt_name'] ?? 'Management') ?>
                                    <?php else: ?>
                                        <span class="text-success"><i class="bi bi-check-circle"></i> Approved</span> by <?= htmlspecialchars($request['mgmt_name'] ?? 'Management') ?>
                                    <?php endif; ?>
                                    <?php if ($request['status'] !== 'paid' && $request['status'] !== 'rejected' && in_array($roleId, [1, 3, 38])): ?>
                                        <button type="button" class="btn btn-xs btn-outline-danger ms-2 py-0 px-2" data-bs-toggle="collapse" data-bs-target="#mgmtRejectCollapse">
                                            <i class="bi bi-x-circle me-1"></i> Reject
                                        </button>
                                        <div class="collapse mt-2" id="mgmtRejectCollapse">
                                            <form method="POST" action="<?= $GLOBALS['base_url'] ?>/project-other-costs/approve-management/<?= $request['id'] ?>" class="border p-2 rounded bg-light">
                                                <div class="mb-2">
                                                    <textarea class="form-control form-control-sm" name="management_apporved_note" rows="2" placeholder="Reason for management rejection..." required></textarea>
                                                </div>
                                                <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger w-100" onclick="return confirm('Are you sure you want to reject this request?');">
                                                    <i class="bi bi-x-circle me-1"></i> Confirm Management Rejection
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?><br>
                                    <?php if (!empty($request['management_apporved_at'])): ?>
                                        <small class="text-muted"><?= date('d M Y h:i A', strtotime($request['management_apporved_at'])) ?></small><br>
                                    <?php endif; ?>
                                    <?php if ($request['management_apporved_note']): ?><small>Note: <?= htmlspecialchars($request['management_apporved_note']) ?></small><?php endif; ?>
                                <?php elseif ($request['dept_head_status'] === 'rejected' || ($request['status'] === 'rejected' && empty($request['management_apporved_by_id']))): ?>
                                    <span class="text-muted"><i class="bi bi-dash-circle"></i> Skipped (Rejected at Earlier Stage)</span>
                                <?php else: ?>
                                    <span class="text-warning"><i class="bi bi-clock"></i> Pending</span>
                                    <span class="text-danger small fw-semibold ms-1">(Mandatory for &gt; ৳10,000)</span>
                                <?php endif; ?>
                            </li>
                            <?php endif; ?>
                            <li class="list-group-item py-3 px-0 lh-base">
                                <strong>Payment Status:</strong>
                                <?php if($request['payment_by_id']): ?>
                                    <span class="text-success"><i class="bi bi-check-circle"></i> Paid & Settled</span> by <?= htmlspecialchars($request['pay_name'] ?? 'N/A') ?><br>
                                    <small class="text-muted"><i class="bi bi-cash me-1"></i> Amount: ৳<?= number_format($request['payment_amount'] ?? $request['approved_amount'], 2) ?></small><br>
                                    <?php 
                                    $paymentAtDisplay = !empty($request['payment_at']) ? $request['payment_at'] : null;
                                    if ($paymentAtDisplay && date('H:i:s', strtotime($paymentAtDisplay)) === '00:00:00' && !empty($request['updated_at'])) {
                                        $paymentAtDisplay = date('Y-m-d', strtotime($paymentAtDisplay)) . ' ' . date('H:i:s', strtotime($request['updated_at']));
                                    }
                                    if (!empty($paymentAtDisplay)): ?>
                                        <small class="text-muted"><i class="bi bi-calendar me-1"></i> Date: <?= date('d M Y h:i A', strtotime($paymentAtDisplay)) ?></small>
                                    <?php endif; ?>
                                    <?php 
                                    $paymentAttachment = (!empty($request['attachment']) && $request['attachment'] !== ($request['creator_attachment'] ?? '')) ? $request['attachment'] : null;
                                    if(!empty($paymentAttachment)): 
                                        $paymentFileDetails = getProjectOtherCostFileDetails($paymentAttachment);
                                    ?>
                                        <br>
                                        <?php if ($paymentFileDetails['exists']): ?>
                                            <a href="<?= $paymentFileDetails['url'] ?>" target="_blank" class="badge bg-primary text-white mt-1 text-decoration-none">
                                                <i class="bi bi-paperclip me-1"></i> View Attachment
                                            </a>
                                        <?php else: ?>
                                            <div class="mt-1 d-inline-flex align-items-center gap-1">
                                                <span class="badge bg-warning text-dark border border-warning"><i class="fa-solid fa-triangle-exclamation me-1"></i> Receipt Missing</span>
                                                <span class="text-muted small ms-1"><?= htmlspecialchars($paymentFileDetails['filename']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php elseif($request['status'] === 'rejected'): ?>
                                    <span class="text-muted"><i class="bi bi-dash-circle"></i> Cancelled (Request Rejected)</span>
                                <?php else: ?>
                                    <span class="text-warning"><i class="bi bi-clock"></i> Pending Accounts Payout</span>
                                <?php endif; ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Action / Processing Card -->
            <div class="col-lg-4">
                <?php
                $userId = $_SESSION['user']['id'] ?? ($userId ?? null);
                $roleNameLc = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
                $creatorReportingUserId = (int)($request['creator_reporting_user_id'] ?? 0);
                $canDeptHeadApprove = ($request['dept_head_status'] === 'pending' && ($userId == $creatorReportingUserId));
                $canQCApprove = ($request['status'] == 'pending' && $request['dept_head_status'] !== 'pending' && in_array($roleId, [13, 24, 25]));
                $reqAmt = (float)($request['approved_amount'] ?? $request['request_amount'] ?? 0);
                $isOver1Lac = ($reqAmt > 10000);
                $isOpHeadUser = (in_array($roleId, [20, 32], true) || str_contains($roleNameLc, 'operation head') || str_contains($roleNameLc, 'admin & operation'));
                $isMgmtUser = in_array($roleId, [1, 3, 38], true) || in_array($roleNameLc, ['super admin', 'admin', 'management', 'board of director', 'board of directors'], true) || str_contains($roleNameLc, 'director') || str_contains($roleNameLc, 'dceo');
                $isOpHeadApproved = !empty($ahInfo['is_op_head_approved']);

                $isOpHeadAction = false;
                $isMgmtLocked = false;
                $canMgmtApprove = false;
                $pocOverrideStage = '';

                if (in_array($request['status'], ['pending', 'qc_approved', 'in_review'], true)) {
                    if ($isMgmtUser) {
                        // Management / DCEO / CEO / Director can always approve directly (auto-fills any pending prior steps with Management Override)
                        $canMgmtApprove = true;
                        if (($request['dept_head_status'] ?? 'not_required') === 'pending') {
                            $pocOverrideStage = 'Department Head';
                        } elseif ($request['status'] === 'pending') {
                            $pocOverrideStage = 'QC & Internal Audit';
                        } elseif (!$isOpHeadApproved) {
                            $pocOverrideStage = 'Head of Operation';
                        } elseif (!$isAhApproved) {
                            $pocOverrideStage = 'Accounts Head';
                        }
                    } elseif ($request['status'] == 'qc_approved' && !$isOpHeadApproved && $isOpHeadUser) {
                        $canMgmtApprove = true;
                        $isOpHeadAction = true;
                    }
                }
                $canPay = ($request['status'] == 'management_approved' && (in_array($roleId, [6, 30]) || $isMgmtUser) && $isAhApproved);
                $isWaitingAccountsHead = (in_array($request['status'], ['management_approved', 'in_review', 'qc_approved']) && $isOpHeadApproved && (in_array($roleId, [6, 30]) || $isMgmtUser) && !$isAhApproved);
                ?>

                <?php if ($canDeptHeadApprove || $canQCApprove || $canMgmtApprove || $isMgmtLocked || $canPay || $isWaitingAccountsHead): ?>
                    <div class="card shadow-sm border-0 border-top border-3 border-primary mb-4">
                        <div class="card-header bg-white py-3 px-4 border-bottom">
                            <h5 class="card-title text-primary mb-0 p-0"><i class="bi bi-pencil-square me-2"></i>Process Request</h5>
                        </div>
                        <div class="card-body p-4">
                            
                            <?php if ($canDeptHeadApprove): ?>
                                <form method="POST" action="<?= $GLOBALS['base_url'] ?>/project-other-costs/approve-dept-head/<?= $request['id'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Department Head Note (Optional)</label>
                                        <textarea class="form-control" name="dept_head_approval_note" rows="3" placeholder="Enter remarks..."></textarea>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="submit" name="dept_head_status" value="approved" class="btn btn-success flex-grow-1" onclick="return confirm('Are you sure you want to approve this request?');">
                                            <i class="bi bi-check-circle me-1"></i> Approve
                                        </button>
                                        <button type="submit" name="dept_head_status" value="rejected" class="btn btn-danger flex-grow-1" onclick="return confirm('Are you sure you want to reject this request?');">
                                            <i class="bi bi-x-circle me-1"></i> Reject
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>

                            <?php if ($canQCApprove): ?>
                                <form method="POST" action="<?= $GLOBALS['base_url'] ?>/project-other-costs/approve-qc/<?= $request['id'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Approved Amount (৳)</label>
                                        <input type="number" step="0.01" class="form-control form-control-lg" name="approved_amount" value="<?= $request['request_amount'] ?>" required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">QC Note (Optional)</label>
                                        <textarea class="form-control" name="qc_audit_note" rows="3" placeholder="Enter remarks..."></textarea>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="submit" name="action" value="approve" class="btn btn-success btn-lg flex-grow-1" onclick="return confirm('Are you sure you want to approve this request?');">
                                            <i class="bi bi-check-circle me-1"></i> Submit QC Approval
                                        </button>
                                        <button type="submit" name="action" value="reject" class="btn btn-danger btn-lg flex-grow-1" onclick="return confirm('Are you sure you want to reject this request?');">
                                            <i class="bi bi-x-circle me-1"></i> Reject
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>

                            <?php if ($isMgmtLocked): ?>
                                <div class="p-3 rounded-2 border border-warning bg-warning-subtle text-dark text-center mb-0 lh-base">
                                    <div class="fw-bold mb-1">
                                        <i class="fa-solid fa-lock text-warning me-1"></i>Management Approval Locked (&gt; ৳10,000)
                                    </div>
                                    <div class="fs-12">
                                        <?php if (!empty($mgmtLockReason) && $mgmtLockReason === 'accounts_head'): ?>
                                            Admin &amp; Operation Head has approved. Awaiting <strong>Accounts Head (Role 30)</strong> payment verification &amp; approval before Management can sign off.
                                        <?php else: ?>
                                            Requires <strong>Admin &amp; Operation Head (Role 20)</strong> verification and approval first.
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($canMgmtApprove): ?>
                                <form id="pocMgmtApproveForm" method="POST" action="<?= $GLOBALS['base_url'] ?>/project-other-costs/approve-management/<?= $request['id'] ?>">
                                    <input type="hidden" name="approve_mode" id="pocApproveModeInput" value="override">
                                    <input type="hidden" name="action" id="pocMgmtActionInput" value="approve">
                                    <?php if ($isOpHeadAction): ?>
                                        <div class="d-flex align-items-start gap-2 p-3 mb-3 rounded-2 border border-primary bg-primary-subtle text-dark small lh-base">
                                            <i class="bi bi-shield-check text-primary flex-shrink-0 mt-1"></i>
                                            <div>
                                                <strong>Admin &amp; Operation Head Approval<?= $isOver1Lac ? ' (Step 1 of 3)' : ' (≤ ৳10,000 Tier)' ?>:</strong> Your approval verifies and forwards this request to Accounts Head (Role 30).
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold"><?= $isOpHeadAction ? 'Verified Amount (৳)' : 'Final Approved Amount (৳)' ?></label>
                                        <input type="number" step="0.01" class="form-control form-control-lg" name="approved_amount" value="<?= $request['approved_amount'] ?? $request['request_amount'] ?>" required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold"><?= $isOpHeadAction ? 'Operation Head Note' : 'Management Note' ?> (Optional)</label>
                                        <textarea class="form-control" name="management_apporved_note" rows="3" placeholder="Enter remarks..."></textarea>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" id="btnPocMgmtApprove" class="btn <?= $isOpHeadAction ? 'btn-primary' : 'btn-success' ?> btn-lg flex-grow-1">
                                            <i class="bi bi-check-circle me-1"></i> <?= $isOpHeadAction ? 'Submit Op Head Approval' : 'Submit Mgmt Approval' ?>
                                        </button>
                                        <button type="submit" name="action" value="reject" class="btn btn-danger btn-lg flex-grow-1" onclick="document.getElementById('pocMgmtActionInput').value='reject'; return confirm('Are you sure you want to reject this request?');">
                                            <i class="bi bi-x-circle me-1"></i> Reject
                                        </button>
                                    </div>
                                </form>
                                <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    var btnPoc = document.getElementById('btnPocMgmtApprove');
                                    var formPoc = document.getElementById('pocMgmtApproveForm');
                                    if (btnPoc && formPoc) {
                                        btnPoc.addEventListener('click', function() {
                                            document.getElementById('pocMgmtActionInput').value = 'approve';
                                            var overrideStage = <?= json_encode($pocOverrideStage ?? '') ?>;
                                            var isOpHead = <?= !empty($isOpHeadAction) ? 'true' : 'false' ?>;
                                            var title = overrideStage !== ''
                                                ? 'Pending ' + overrideStage + "'s Approval"
                                                : (isOpHead ? 'Approve as Head of Operation?' : 'Final Management Approval?');
                                            var htmlMsg = overrideStage !== ''
                                                ? 'This request is pending for <strong>' + overrideStage + "'s</strong> approval. Choose whether to approve <strong>only " + overrideStage + '</strong> and forward to the next stage, or override all stages &amp; finalize now.'
                                                : (isOpHead
                                                    ? 'Confirm Head of Operation approval and forward to Accounts Head?'
                                                    : 'Confirm final Management approval for this request?');
                                            if (typeof Swal !== 'undefined') {
                                                Swal.fire({
                                                    title: title,
                                                    html: htmlMsg,
                                                    icon: overrideStage !== '' ? 'warning' : 'question',
                                                    showCancelButton: true,
                                                    showDenyButton: overrideStage !== '',
                                                    confirmButtonColor: overrideStage !== '' ? '#0d6efd' : (isOpHead ? '#0d6efd' : '#198754'),
                                                    denyButtonColor: '#198754',
                                                    cancelButtonColor: '#6c757d',
                                                    confirmButtonText: overrideStage !== '' ? 'Approve Only ' + overrideStage : 'Yes, Approve',
                                                    denyButtonText: 'Approve All & Finalize'
                                                }).then(function(result) {
                                                    var modeInput = document.getElementById('pocApproveModeInput');
                                                    if (result.isConfirmed) {
                                                        if (modeInput) modeInput.value = (overrideStage !== '') ? 'single' : 'override';
                                                        formPoc.submit();
                                                    } else if (result.isDenied && overrideStage !== '') {
                                                        if (modeInput) modeInput.value = 'override';
                                                        formPoc.submit();
                                                    }
                                                });
                                            } else if (confirm(title)) {
                                                formPoc.submit();
                                            }
                                        });
                                    }
                                });
                                </script>
                            <?php endif; ?>

                            <?php if ($canPay): ?>
                                <form method="POST" action="<?= $GLOBALS['base_url'] ?>/project-other-costs/pay/<?= $request['id'] ?>" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Amount to Pay (৳)</label>
                                        <?php $maxPay = (float)(!empty($request['approved_amount']) ? $request['approved_amount'] : $request['request_amount']); ?>
                                        <input type="number" step="0.01" min="0.01" max="<?= $maxPay ?>" class="form-control form-control-lg" name="payment_amount" value="<?= $maxPay ?>" required>
                                        <small class="text-muted d-block mt-1"><i class="bi bi-shield-check text-success me-1"></i>Approved limit: <strong>৳ <?= number_format($maxPay, 2) ?></strong> (cannot exceed this amount)</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Payment Date & Time</label>
                                        <input type="datetime-local" class="form-control" name="payment_at" value="<?= date('Y-m-d\TH:i') ?>" required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Attachment (Optional)</label>
                                        <input type="file" class="form-control" name="attachment" accept="image/*,.pdf,.doc,.docx">
                                        <small class="text-muted">Upload receipt or payment proof.</small>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-lg w-100" onclick="return confirm('Are you sure you want to disburse this payment?');">
                                        <i class="bi bi-cash me-1"></i> Disburse Payment
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if ($isWaitingAccountsHead): ?>
                                <div class="p-3 rounded-2 border border-warning bg-warning-subtle text-dark text-center mb-0 lh-base">
                                    <div class="fw-bold mb-1">
                                        <i class="fa-solid fa-lock text-warning me-1"></i>Payment Locked
                                    </div>
                                    <div class="fs-12">Pending Accounts Head approval.</div>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>
                <?php else: ?>
                    <div class="p-4 rounded-3 border bg-light text-center shadow-sm mb-4 lh-base">
                        <i class="bi bi-info-circle fs-4 d-block mb-2 text-muted"></i>
                        <div class="text-dark">
                            <?php if ($request['dept_head_status'] === 'pending'): ?>
                                Currently waiting for Department Head approval from <strong><?= htmlspecialchars($request['designated_dh_name'] ?? 'Reporting Boss') ?></strong>.
                            <?php elseif ($request['status'] === 'pending'): ?>
                                Currently waiting for QC &amp; Audit approval.
                            <?php elseif ($request['status'] === 'qc_approved'): ?>
                                Currently waiting for Management approval.
                            <?php elseif ($request['status'] === 'management_approved'): ?>
                                Currently waiting for Accounts Payout.
                            <?php elseif ($request['status'] === 'paid'): ?>
                                Request has been paid and completed.
                            <?php else: ?>
                                No actions required from your end at this stage.
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($request['status'] === 'paid'): ?>
                    <div class="card shadow-sm border-0 border-top border-3 border-warning mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title text-warning mb-0 fs-6">
                                <i class="bi bi-patch-check me-2"></i>Expense Settlement & Adjustments
                            </h5>
                            <a href="<?= $GLOBALS['base_url'] ?>/expense-adjustments/create?parent_type=other_cost&cashout_request_id=<?= $request['id'] ?>&project_id=<?= $request['project_id'] ?>" class="btn btn-warning btn-sm shadow-sm fw-semibold">
                                <i class="bi bi-file-earmark-plus me-1"></i> Submit Settlement
                            </a>
                        </div>
                        <div class="card-body mt-2">
                            <?php if (empty($adjustments)): ?>
                                <div class="text-center py-3 text-muted">
                                    <i class="bi bi-clipboard-x fs-2 d-block mb-1 text-secondary"></i>
                                    No settlements or adjustments submitted yet for this payment.<br>
                                    <small class="text-muted">You can return unused funds or request extra money for this expense.</small>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Type</th>
                                                <th class="text-end">Amount (৳)</th>
                                                <th class="text-center">Status</th>
                                                <th class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($adjustments as $adj):
                                                $typeBadge = ($adj['adjustment_type'] === 'return') ? 'info' : 'warning text-dark';
                                                $typeLabel = ($adj['adjustment_type'] === 'return') ? 'Return Unused' : 'Extra Money';
                                                $statusClass = match ($adj['status']) {
                                                    'pending' => 'warning text-dark',
                                                    'approved' => 'success',
                                                    'rejected' => 'danger',
                                                    'partially_paid' => 'info text-dark',
                                                    'fully_paid' => 'success',
                                                    default => 'secondary'
                                                };
                                            ?>
                                            <tr>
                                                <td class="fw-bold">REQ-<?= $adj['id'] ?></td>
                                                <td><span class="badge bg-<?= $typeBadge ?>"><?= $typeLabel ?></span></td>
                                                <td class="text-end fw-bold">৳ <?= number_format($adj['amount'], 2) ?></td>
                                                <td class="text-center"><span class="badge bg-<?= $statusClass ?>"><?= ucfirst(str_replace('_', ' ', $adj['status'])) ?></span></td>
                                                <td class="text-center">
                                                    <a href="<?= $GLOBALS['base_url'] ?>/expense-adjustments/show?id=<?= $adj['id'] ?>" class="btn btn-xs btn-outline-primary" style="font-size: 0.75rem;">
                                                        <i class="bi bi-eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </section>
</main>

<?php if ($canEditOtherCost ?? false): ?>
<!-- Edit Project Other Cost Modal (Before 1st Approval) -->
<div class="modal custom-modal fade" id="editOtherCostModal" tabindex="-1" aria-labelledby="editOtherCostModalLabel" aria-hidden="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header pb-3 mb-2 border-bottom">
                <h5 class="modal-title fw-bold text-dark" id="editOtherCostModalLabel">
                    <i class="fa-solid fa-pen-to-square me-2 text-warning"></i> Edit Other Cost Request #<?= (int)$request['id'] ?>
                </h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body p-0">
                <form method="POST" action="<?= $GLOBALS['base_url'] ?>/project-other-costs/update/<?= (int)$request['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Cost Type <span class="text-danger">*</span></label>
                        <select name="rcost_type" class="form-select" required>
                            <?php foreach (($costTypes ?? ['others' => 'Others']) as $ctKey => $ctLabel): ?>
                                <option value="<?= htmlspecialchars($ctKey) ?>" <?= (($request['rcost_type'] ?? '') === $ctKey) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(is_array($ctLabel) ? ($ctLabel['label'] ?? $ctKey) : $ctLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Requested Amount (৳) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="1" name="request_amount" class="form-control" value="<?= htmlspecialchars($request['request_amount'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Purpose / Description <span class="text-danger">*</span></label>
                        <textarea name="purpose" class="form-control" rows="3" required><?= htmlspecialchars($request['purpose'] ?? '') ?></textarea>
                    </div>
                    <?php if ($isOcRevOpen ?? false): ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Update / Response Note to <?= htmlspecialchars($latestRevision['return_stage_label'] ?? 'Approver') ?></label>
                        <textarea name="resubmitted_note" class="form-control" rows="2" placeholder="Briefly explain what you updated..."></textarea>
                    </div>
                    <?php endif; ?>
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
    function initOtherCostEditModal() {
        if (typeof jQuery === 'undefined' || typeof bootstrap === 'undefined') {
            setTimeout(initOtherCostEditModal, 50);
            return;
        }
        jQuery(function($) {
            if ($('#editOtherCostModal').length && $('#editOtherCostModal').parent().prop('tagName') !== 'BODY') {
                $('#editOtherCostModal').appendTo('body');
            }
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initOtherCostEditModal);
    } else {
        initOtherCostEditModal();
    }
})();
</script>
<?php endif; ?>



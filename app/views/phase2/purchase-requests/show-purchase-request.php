<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$sessionRole = (int)($_SESSION['user']['role_id'] ?? 0);
$isReadOnly = !in_array($sessionRole, [17, 23, 31]);
?>
<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-purchase-requests">Purchase Requests</a></li>
                <li class="breadcrumb-item active">PR-<?= $pr['id'] ?></li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $base_url ?>/con-stocks" target="_blank" class="btn btn-info btn-sm text-white"><i class="fal fa-boxes me-1"></i> Show Stock</a>
        <a href="<?= $base_url ?>/con-purchase-requests" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <?php
        $reqUserId = (int)($_SESSION['user']['id'] ?? 0);
        $reqUserRole = (int)($_SESSION['user']['role_id'] ?? 0);
        $isPRCreator = (int)($pr['created_by'] ?? 0) === $reqUserId;
        $isPRAdmin = in_array($reqUserRole, [1, 3]);
        $isPRDraft = ($pr['status'] ?? '') === 'Draft';
        $isPurchase = in_array($reqUserRole, [17, 23, 31]);
        $canDeletePR = ($isPRCreator || $isPRAdmin || $isPurchase);
        ?>
        <?php $canEditPR = ($isPRCreator || $isPRAdmin || $isPurchase); ?>
        <?php if ($canEditPR): ?>
            <a href="<?= $base_url ?>/con-purchase-request-edit/<?= $pr['id'] ?>" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-pencil me-1"></i> Edit Request
            </a>
        <?php endif; ?>
        <?php if ($canDeletePR): ?>
            <a href="<?= $base_url ?>/con-purchase-request-delete/<?= $pr['id'] ?>"
                class="btn btn-outline-danger btn-sm"
                onclick="return confirm('Are you sure you want to delete this purchase request?')">
                <i class="bi bi-trash me-1"></i> Delete Request
            </a>
        <?php endif; ?>
    </div>
</div>

<section class="section">
            <!-- ══════════ NEXT ACTION TRACKER ══════════ -->
            <?php if (!empty($nextStepInfo)): ?>
            <div class="card__wrapper shadow-sm mb-4" style="border: 1px solid #3b82f6; background: rgba(59, 130, 246, 0.03); border-radius: 14px;">
              <div class="card__body p-3" style="display: flex; align-items: flex-start; gap: 14px;">
                <div style="width: 32px; height: 32px; border-radius: 50%; background: #3b82f6; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #fff; box-shadow: 0 0 10px rgba(59, 130, 246, 0.4);">
                  <i class="fal fa-arrow-alt-right-to-line" style="font-size: 14px;"></i>
                </div>
                <div>
                  <div style="font-size: 11px; font-weight: 800; color: #067936; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 4px;">
                    <i class="fal fa-spinner-third fa-spin me-1"></i> Next Step / Stuck At
                  </div>
                  <div style="font-size: 15px; font-weight: 800; line-height: 1.4;">
                    <?= htmlspecialchars($nextStepInfo) ?>
                  </div>
                </div>
              </div>
            </div>
            <?php endif; ?>

            <div class="card__wrapper shadow-sm mb-4">
                <div class="card__body p-4">

                    <!-- Header Info -->
                    <?php $prType = $pr['pr_type'] ?? 'boq_linked'; ?>
                    <div class="row mb-4 g-3">
                        <div class="col-md-2">
                            <label class="small text-uppercase text-muted d-block">PR Number</label>
                            <span class="fw-bold fs-5">PR-<?= $pr['id'] ?></span>
                        </div>
                        <div class="col-md-3">
                            <label class="small text-uppercase text-muted d-block">Project</label>
                            <span class="fw-bold"><?= htmlspecialchars($pr['project_name']) ?></span>
                        </div>
                        <div class="col-md-2">
                            <label class="small text-uppercase text-muted d-block">Type</label>
                            <?php if ($prType === 'independent'): ?>
                                <span class="badge" style="background:rgba(245,158,11,.15);color:#b45309;border:1px solid rgba(245,158,11,.3);font-size:11px;font-weight:700;padding:5px 10px">
                                    ✏️ Not link with monthly target
                                </span>
                            <?php else: ?>
                                <span class="badge" style="background:rgba(37,99,235,.1);color:#1d4ed8;border:1px solid rgba(37,99,235,.2);font-size:11px;font-weight:700;padding:5px 10px">
                                    📋 Link with monthly target
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-2">
                            <label class="small text-uppercase text-muted d-block">Status</label>
                            <?php
                            $badge = match($pr['status']) {
                                'Approved'    => 'bg-success text-white',
                                'PO Issued'   => 'bg-primary text-white',
                                'PO Rejected' => 'bg-danger text-white',
                                'Rejected'    => 'bg-danger text-white',
                                'Submitted'   => 'bg-info text-dark',
                                default       => 'bg-warning text-dark',
                            };
                            ?>
                            <span class="badge <?= $badge ?> px-3 py-1">
                                <?php if ($pr['status'] === 'PO Rejected'): ?>
                                    <i class="bi bi-exclamation-octagon-fill me-1"></i> PO Rejected
                                <?php else: ?>
                                    <?= $pr['status'] ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="col-md-2">
                            <label class="small text-uppercase text-muted d-block">Created By</label>
                            <span class="fw-bold"><?= htmlspecialchars($pr['created_by_name'] ?? '—') ?></span>
                        </div>
                        <div class="col-md-1">
                            <label class="small text-uppercase text-muted d-block">Date</label>
                            <span class="fw-bold"><?= date('d M Y', strtotime($pr['created_at'])) ?></span>
                        </div>
                    </div>

                    <!-- ══════════ PO REJECTION ALERT CARD ══════════ -->
                    <?php if ($pr['status'] === 'PO Rejected'): ?>
                    <div class="alert alert-danger border-2 border-danger p-4 mb-4 shadow-sm" style="border-radius:14px; background:#fff5f5;">
                        <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                            <div class="d-flex align-items-start gap-3">
                                <div class="p-3 rounded-circle text-danger bg-white shadow-xs" style="border:2px solid #fca5a5; width:52px; height:52px; display:flex; align-items:center; justify-content:center;">
                                    <i class="bi bi-exclamation-octagon-fill fs-3"></i>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                        <span class="badge bg-danger fs-6 px-3 py-1">PO Rejected — Revision Required</span>
                                        <?php if (!empty($pr['revision_count']) && (int)$pr['revision_count'] > 0): ?>
                                            <span class="badge bg-warning text-dark">Revision #<?= $pr['revision_count'] ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <h5 class="fw-bold text-dark mb-1">
                                        Purchase Order was Rejected by Approver
                                    </h5>
                                    <p class="text-muted mb-2" style="font-size:13px;">
                                        This requisition's Purchase Order was rejected. Please review the note below, edit quantities or delete unneeded items, and re-run CST & PO generation.
                                    </p>
                                    <div class="p-3 rounded bg-white border border-danger-subtle mt-2" style="max-width:720px;">
                                        <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold" style="font-size:11px;">
                                                <i class="bi bi-chat-quote-fill me-1"></i> Approver Note / Instructions
                                            </span>
                                            <span class="small text-muted" style="font-size:11px;">
                                                <?php if (!empty($pr['po_rejected_by_name'])): ?>
                                                    By <strong><?= htmlspecialchars($pr['po_rejected_by_name']) ?></strong>
                                                    (<?= htmlspecialchars($pr['po_rejected_by_role'] ?? 'Management') ?>) &bull;
                                                <?php endif; ?>
                                                <?= !empty($pr['po_rejected_at']) ? date('d M Y, h:i A', strtotime($pr['po_rejected_at'])) : '' ?>
                                            </span>
                                        </div>
                                        <div class="text-danger fw-semibold" style="font-size:14px; white-space:pre-wrap; line-height:1.5;">
                                            <?= htmlspecialchars($pr['po_rejection_note'] ?? 'No specific remarks provided.') ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <?php if ($canEditPR): ?>
                                    <a href="<?= $base_url ?>/con-purchase-request-edit/<?= $pr['id'] ?>" class="btn btn-danger btn-lg shadow-sm px-4 fw-bold">
                                        <i class="bi bi-pencil-square me-2"></i> Edit & Revise Request
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Independent PR: Required Date + Purpose -->
                    <?php if (!empty($pr['required_date']) || !empty($pr['purpose']) || !empty($pr['notes'])): ?>
                    <div class="rounded p-3 mb-3" style="background:rgba(245,158,11,.05);border:1px solid rgba(245,158,11,.2)">
                        <div class="row g-3">
                            <?php if (!empty($pr['required_date'])): ?>
                            <div class="col-md-3">
                                <label class="small text-uppercase text-muted d-block">
                                    <i class="bi bi-calendar-event me-1 text-warning"></i> Required By
                                </label>
                                <span class="fw-bold text-warning"><?= date('d M Y', strtotime($pr['required_date'])) ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($pr['purpose']) || !empty($pr['notes'])): ?>
                            <div class="col-md-9">
                                <label class="small text-uppercase text-muted d-block">
                                    <i class="bi bi-justify-left me-1 text-info"></i> Purpose / Notes
                                </label>
                                <?php if (!empty($pr['purpose'])): ?>
                                    <div class="fw-bold mb-1"><?= nl2br(htmlspecialchars($pr['purpose'])) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($pr['notes'])): ?>
                                    <div class="<?= empty($pr['purpose']) ? 'fw-bold' : 'text-muted small' ?>">
                                        <?= empty($pr['purpose']) ? '' : '<i class="bi bi-sticky me-1"></i> Notes: ' ?>
                                        <?= nl2br(htmlspecialchars($pr['notes'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Approval Status Panel -->
                    <?php
                    $pmApproved   = !empty($pr['approved_by_id']);
                    $qcApproved   = !empty($pr['qc_approved_by_id']);
                    $mgmtApproved = !empty($pr['mgmt_approved_by_id']);
                    ?>
                    <div class="rounded p-3 mb-3" style="background:<?= in_array($pr['status'], ['Approved','PO Issued']) ? 'rgba(16,185,129,.06)' : 'rgba(245,158,11,.05)' ?>;border:1px solid <?= in_array($pr['status'], ['Approved','PO Issued']) ? 'rgba(16,185,129,.2)' : 'rgba(245,158,11,.18)' ?>">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-3">
                                <div style="width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;
                                    background:<?= in_array($pr['status'], ['Approved','PO Issued']) ? 'rgba(16,185,129,.15)' : 'rgba(245,158,11,.12)' ?>;
                                    color:<?= in_array($pr['status'], ['Approved','PO Issued']) ? '#059669' : '#d97706' ?>">
                                    <i class="fal <?= in_array($pr['status'], ['Approved','PO Issued']) ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                                </div>
                                <div style="display:flex; flex-direction:column; gap:6px;">
                                    <div style="font-size:12px;font-weight:700">Approval Workflow Status</div>
                                    
                                    <!-- PM Approval Status -->
                                    <div style="font-size:11.5px;">
                                        <strong>Project Manager Approval:</strong> 
                                        <?php if ($pmApproved || in_array($pr['status'], ['Approved', 'PO Issued'])): ?>
                                            <span style="color:#059669; font-weight:700;">✓ Approved</span>
                                            <?php if (!empty($pr['approved_by_name'])): ?>
                                                by <?= htmlspecialchars($pr['approved_by_name']) ?>
                                            <?php endif; ?>
                                            <?php if (!empty($pr['approved_at'])): ?>
                                                <span class="text-muted small">(<?= date('d M Y, h:i A', strtotime($pr['approved_at'])) ?>)</span>
                                            <?php endif; ?>
                                            <?php if (!empty($pr['management_note'])): ?>
                                                <br><span class="text-muted small">Note: <?= htmlspecialchars($pr['management_note']) ?></span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color:#d97706; font-weight:700;">Pending</span>
                                            <?php if (!empty($assignedPm)): ?>
                                                <span class="text-muted small"> (Required from: <strong><?= htmlspecialchars($assignedPm['full_name']) ?></strong> - <em><?= htmlspecialchars($assignedPm['designation'] ?? 'Project Manager') ?></em>)</span>
                                            <?php else: ?>
                                                <span class="text-danger small"> (No Project Manager assigned to this project)</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <span style="font-size:10px;font-weight:700;padding:4px 12px;border-radius:20px;
                                background:<?= in_array($pr['status'], ['Approved','PO Issued']) ? 'rgba(16,185,129,.12)' : ($pr['status'] === 'Rejected' ? 'rgba(239,68,68,.12)' : 'rgba(245,158,11,.1)') ?>;
                                color:<?= in_array($pr['status'], ['Approved','PO Issued']) ? '#059669' : ($pr['status'] === 'Rejected' ? '#dc2626' : '#d97706') ?>">
                                <?= in_array($pr['status'], ['Approved','PO Issued']) ? '✓ Approved' : ($pr['status'] === 'Rejected' ? '✗ Rejected' : 'Pending') ?>
                            </span>
                        </div>
                    </div>

                    <?php 
                    if (!empty($pr['attachment'])): 
                        $rootPath = dirname(dirname(dirname(dirname(__DIR__))));
                        $att = $pr['attachment'];
                        $cleanAtt = basename($att);
                        
                        $fileExists = false;
                        if (file_exists($rootPath . '/public/uploads/pr_attachments/' . $cleanAtt)) {
                            $downloadLink = $GLOBALS['base_url'] . '/public/uploads/pr_attachments/' . htmlspecialchars($cleanAtt);
                            $fileExists = true;
                        } elseif (file_exists($rootPath . '/public/uploads/purchase_requests/' . $cleanAtt)) {
                            $downloadLink = $GLOBALS['base_url'] . '/public/uploads/purchase_requests/' . htmlspecialchars($cleanAtt);
                            $fileExists = true;
                        } elseif (file_exists($rootPath . '/public/public/uploads/pr_attachments/' . $cleanAtt)) {
                            $downloadLink = $GLOBALS['base_url'] . '/public/public/uploads/pr_attachments/' . htmlspecialchars($cleanAtt);
                            $fileExists = true;
                        } else {
                            if (strpos($att, '/public/') === 0) {
                                $downloadLink = $GLOBALS['base_url'] . htmlspecialchars($att);
                            } else {
                                $downloadLink = $GLOBALS['base_url'] . '/public/uploads/pr_attachments/' . htmlspecialchars($att);
                            }
                        }
                    ?>
                    <div class="alert <?= $fileExists ? 'alert-info' : 'alert-warning' ?> border d-flex flex-wrap align-items-center justify-content-between mb-3 p-3" style="background: <?= $fileExists ? 'rgba(13, 202, 240, 0.08)' : 'rgba(255, 193, 7, 0.08)' ?>; border-color: <?= $fileExists ? 'rgba(13, 202, 240, 0.3)' : 'rgba(255, 193, 7, 0.3)' ?> !important; border-radius: 10px; gap: 15px;">
                        <div class="d-flex align-items-center gap-3">
                            <i class="fa-solid fa-file-invoice fs-4 <?= $fileExists ? 'text-info' : 'text-warning' ?> flex-shrink-0"></i>
                            <div>
                                <strong class="d-block text-dark">Attached Document / Requisition File:</strong>
                                <span class="text-muted small"><?= htmlspecialchars(basename($pr['attachment'])) ?></span>
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



                    <!-- Urgent Requisition Notification Banner -->
                    <?php if (!empty($pr['is_urgent'])): ?>
                    <div class="alert alert-danger d-flex align-items-start gap-3 p-3 mb-3 border-danger shadow-sm" style="border-radius:12px; background:linear-gradient(135deg, rgba(239,68,68,.12), rgba(220,38,38,.05)); border-left: 6px solid #dc2626 !important;">
                        <div style="">
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <h6 class="fw-bold text-danger mb-0">
                                 URGENT PURCHASE REQUEST — 30-Day Policy Exception
                                </h6>
                                <span class="badge bg-danger text-white px-2 py-1" style="font-size:11px">Urgent Fast-Track</span>
                            </div>
                            <div class="text-dark small mt-1">
                                <strong>Lead Time:</strong> 
                                <?php 
                                    $reqTs = !empty($pr['required_date']) ? strtotime($pr['required_date']) : null;
                                    $createdTs = strtotime($pr['created_at']);
                                    if ($reqTs) {
                                        $leadDays = ceil(($reqTs - $createdTs) / 86400);
                                        echo ($leadDays < 0 ? '0' : $leadDays) . ' days from creation (' . date('d M Y', $reqTs) . ')';
                                    } else {
                                        echo 'Under 30 days';
                                    }
                                ?>
                            </div>
                            <?php if (!empty($pr['urgent_reason'])): ?>
                            <div class="mt-2 p-2 rounded bg-white text-dark border border-danger-subtle small">
                                <strong class="text-danger">Urgent Justification / Reason:</strong>
                                <span class="text-dark fw-semibold"><?= nl2br(htmlspecialchars($pr['urgent_reason'])) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Items Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Material</th>
                                    <th>Unit</th>
                                    <th class="text-end">Current Stock (Site)</th>
                                    <th class="text-end">Requested Qty</th>
                                    <th class="text-end">Next 3-Months Req.</th>
                                    <th class="text-center">Required Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $i => $item): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td>
                                        <span class="fw-bold"><?= htmlspecialchars($item['material_name']) ?></span>
                                        <br><small class="text-muted">[Category: <?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?>]</small>
                                    </td>
                                    <td><?= htmlspecialchars($item['unit']) ?></td>
                                    <td class="text-end">
                                        <span class="fw-semibold text-secondary">
                                            <?= isset($item['current_stock']) ? number_format((float)$item['current_stock'], 3) : '0.000' ?>
                                        </span>
                                        <small class="text-muted"><?= htmlspecialchars($item['unit']) ?></small>
                                    </td>
                                    <td class="text-end fw-bold text-dark">
                                        <?= number_format($item['quantity'], 3) ?>
                                        <small class="text-muted fw-normal"><?= htmlspecialchars($item['unit']) ?></small>
                                    </td>
                                    <td class="text-end">
                                        <?php if (!empty($item['next_3_months_requirement']) && (float)$item['next_3_months_requirement'] > 0): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                                <?= number_format((float)$item['next_3_months_requirement'], 3) ?> <?= htmlspecialchars($item['unit']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                        $displayReqDate = !empty($item['item_required_date']) ? $item['item_required_date'] : (!empty($item['boq_required_date']) ? $item['boq_required_date'] : ($pr['required_date'] ?? null));
                                        if (!empty($displayReqDate)): ?>
                                            <span class="badge bg-light text-dark border border-secondary px-2 py-1">
                                                <i class="bi bi-calendar-event me-1 text-primary"></i>
                                                <?= date('d M Y', strtotime($displayReqDate)) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Status Actions -->
                    <?php
                    $sessionRole = (int)($_SESSION['user']['role_id'] ?? 0);
                    $isCreator   = ($pr['created_by'] == $_SESSION['user']['id']);
                    ?>
                    <?php if ($pr['status'] === 'Draft'): ?>
                        <div class="mt-4 p-3 bg-light rounded d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <span class="text-muted small">
                                <i class="bi bi-info-circle me-1"></i> This PR is currently in <strong>Draft</strong>. Click submit to send for PM review.
                            </span>
                            <?php if ($isCreator || in_array($sessionRole, [1, 3, 15, 22])): ?>
                            <div class="d-flex gap-2">
                                <form action="<?= $base_url ?>/con-purchase-request-approve/<?= $pr['id'] ?>" method="POST" class="d-inline">
                                    <input type="hidden" name="status" value="Submitted">
                                    <button type="submit" class="btn btn-primary btn-sm fw-bold">
                                        <i class="bi bi-send me-1"></i> Submit for PM Review
                                    </button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($pr['status'] === 'Submitted'): ?>
                        <div class="mt-4 p-3 bg-light rounded d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <span class="text-muted small w-100 mb-2">
                                <i class="bi bi-clock-history me-1"></i>
                                <strong>Project Manager Approval:</strong> Awaiting PM Approval.
                                <?php 
                                $assignedPmId = isset($assignedPm['id']) ? (int)$assignedPm['id'] : 0;
                                if (in_array($sessionRole, [1, 3, 15, 22])): 
                                ?>
                                    (Action required: Please review and Approve or Reject).
                                <?php endif; ?>
                            </span>

                            <?php 
                            $showForm = false;
                            $assignedPmId = isset($assignedPm['id']) ? (int)$assignedPm['id'] : 0;
                            if (in_array($sessionRole, [1, 3, 22]) || ($sessionRole === 15 && ($assignedPmId === 0 || $_SESSION['user']['id'] == $assignedPmId))) {
                                $showForm = true;
                            }
                            ?>

                            <?php if ($showForm): ?>
                            <form action="<?= $base_url ?>/con-purchase-request-approve/<?= $pr['id'] ?>" method="POST" class="d-inline w-100">
                                <input type="hidden" name="status" id="pr_status_input" value="">
                                <div class="mb-2 w-100">
                                    <textarea name="approval_note" class="form-control" rows="2" placeholder="Approval / Rejection Note"></textarea>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" onclick="document.getElementById('pr_status_input').value='Approved'; confirmAction(this, 'Approve Purchase Request?')" class="btn btn-success btn-sm">
                                        <i class="bi bi-check-circle me-1"></i> Approve PR
                                    </button>
                                    <button type="button" onclick="document.getElementById('pr_status_input').value='Rejected'; confirmAction(this, 'Reject this PR?')" class="btn btn-danger btn-sm">
                                        <i class="bi bi-x-circle me-1"></i> Reject PR
                                    </button>
                                </div>
                            </form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Linked Purchase Orders History -->
            <?php if (!empty($linkedPos)): ?>
            <div class="card__wrapper shadow-sm mb-4">
                <div class="card__header d-flex justify-content-between align-items-center">
                    <h6 class="card__title mb-0">
                        <i class="bi bi-file-earmark-text me-2 text-primary"></i>Purchase Orders History (<?= count($linkedPos) ?>)
                    </h6>
                    <?php if ($pr['status'] === 'PO Rejected'): ?>
                        <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>Last PO was Rejected</span>
                    <?php endif; ?>
                </div>
                <div class="card__body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>PO #</th>
                                    <th>Vendor</th>
                                    <th>Status</th>
                                    <th>Order Date</th>
                                    <th>Approver Remarks / Note</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($linkedPos as $lpo): ?>
                                <tr>
                                    <td class="fw-bold">
                                        <a href="<?= $base_url ?>/con-purchase-order/<?= $lpo['id'] ?>" class="text-primary text-decoration-none">
                                            PO-<?= $lpo['id'] ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($lpo['vendor_name'] ?? '—') ?></td>
                                    <td>
                                        <?php if ($lpo['status'] === 'Cancelled'): ?>
                                            <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i> Cancelled / Rejected</span>
                                        <?php elseif (in_array($lpo['status'], ['Approved', 'Fully Received'])): ?>
                                            <span class="badge bg-success"><?= $lpo['status'] ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-primary"><?= $lpo['status'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d M Y', strtotime($lpo['order_date'])) ?></td>
                                    <td style="max-width:320px;">
                                        <?php if (!empty($lpo['remarks'])): ?>
                                            <div class="small text-danger fw-semibold" title="<?= htmlspecialchars($lpo['remarks']) ?>">
                                                <i class="bi bi-chat-left-quote me-1"></i><?= htmlspecialchars(mb_strimwidth($lpo['remarks'], 0, 55, '...')) ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= $base_url ?>/con-purchase-order/<?= $lpo['id'] ?>" class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-eye me-1"></i> View PO
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Procurement Pipeline Actions -->
            <?php if (in_array($pr['status'], ['Approved', 'PO Issued', 'PO Rejected'])): ?>
            <div class="card__wrapper shadow-sm">
                <div class="card__header">
                    <h6 class="card__title mb-0">
                    <i class="bi bi-diagram-3 me-2"></i>Procurement Pipeline</h6>
                </div>
                <div class="card__body p-4">
                    <div class="row">
                        <!-- Step 1: Quotations -->
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center h-100">
                                <div class="mb-2">
                                    <i class="bi bi-receipt fs-3 text-info"></i>
                                </div>
                                <h6>Step 1: Vendor Quotations</h6>
                                <p class="small text-muted mb-2">
                                    <?= count($quotations) ?> quotation(s) collected
                                </p>
                                <?php if (!$isReadOnly): ?>
                                    <a href="<?= $base_url ?>/con-quotation-create/<?= $pr['id'] ?>"
                                       class="btn btn-outline-info btn-sm">
                                        <i class="bi bi-plus me-1"></i> Add / View Quotations
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Awaiting Quotations</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Step 2: CST Compare -->
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center h-100">
                                <div class="mb-2">
                                    <i class="bi bi-layout-three-columns fs-3 text-warning"></i>
                                </div>
                                <h6>Step 2: CST Comparison</h6>
                                <p class="small text-muted mb-2">
                                    <?= $hasCST ? 'Winner selected' : 'Compare & select lowest' ?>
                                </p>
                                <?php if (count($quotations) >= 2): ?>
                                    <?php if ($hasCST): ?>
                                        <a href="<?= $base_url ?>/con-quotation-compare/<?= $pr['id'] ?>"
                                           class="btn btn-outline-warning btn-sm">
                                            <i class="bi bi-eye me-1"></i> View / Re-select CST
                                        </a>
                                    <?php elseif (!$isReadOnly): ?>
                                        <a href="<?= $base_url ?>/con-quotation-compare/<?= $pr['id'] ?>"
                                           class="btn btn-outline-warning btn-sm">
                                            <i class="bi bi-eye me-1"></i> Compare Now
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Awaiting CST selection</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Need ≥ 2 quotes</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Step 3: Generate PO -->
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center h-100">
                                <div class="mb-2">
                                    <i class="bi bi-file-earmark-check fs-3 <?= ($pr['status'] === 'PO Rejected') ? 'text-danger' : 'text-success' ?>"></i>
                                </div>
                                <h6>Step 3: Purchase Order</h6>
                                <p class="small text-muted mb-2">
                                    <?php if ($pr['status'] === 'PO Rejected'): ?>
                                        <span class="text-danger fw-bold">PO Rejected (Revision Needed)</span>
                                    <?php elseif ($pr['status'] === 'PO Issued'): ?>
                                        PO Generated
                                    <?php else: ?>
                                        Auto-generate from CST
                                    <?php endif; ?>
                                </p>
                                <?php if ($pr['status'] === 'PO Rejected'): ?>
                                    <div class="d-flex flex-column gap-2 align-items-center">
                                        <?php if ($canEditPR): ?>
                                            <a href="<?= $base_url ?>/con-purchase-request-edit/<?= $pr['id'] ?>" class="btn btn-danger btn-sm text-white fw-bold">
                                                <i class="bi bi-pencil-square me-1"></i> Edit Request First
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($hasCST && !$isReadOnly): ?>
                                            <form action="<?= $base_url ?>/con-purchase-order-generate/<?= $pr['id'] ?>" method="POST" class="d-inline">
                                                <button type="button" onclick="confirmAction(this, 'Re-generate Purchase Order from CST selections?')" class="btn btn-outline-success btn-sm">
                                                    <i class="bi bi-arrow-repeat me-1"></i> Re-Generate PO
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif ($hasCST && $pr['status'] !== 'PO Issued'): ?>
                                    <?php if (!$isReadOnly): ?>
                                        <form action="<?= $base_url ?>/con-purchase-order-generate/<?= $pr['id'] ?>" method="POST" class="d-inline">
                                            <button type="button" onclick="confirmAction(this, 'Generate Purchase Order from CST selections?')" class="btn btn-outline-success btn-sm">
                                                <i class="bi bi-lightning me-1"></i> Generate PO
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Awaiting PO generation</span>
                                    <?php endif; ?>
                                <?php elseif ($pr['status'] === 'PO Issued'): ?>
                                    <span class="badge bg-success"><i class="bi bi-check"></i> PO Created</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Complete CST first</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmAction(btn, msg) {
    Swal.fire({
        title: 'Are you sure?',
        text: msg,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, proceed!'
    }).then((result) => {
        if (result.isConfirmed) {
            if (btn.name) {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = btn.name;
                hidden.value = btn.value;
                btn.form.appendChild(hidden);
            }
            btn.form.submit();
        }
    });
}
</script>

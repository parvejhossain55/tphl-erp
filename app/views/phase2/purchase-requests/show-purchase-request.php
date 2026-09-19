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
                                'Approved'  => 'bg-success',
                                'PO Issued' => 'bg-primary',
                                'Rejected'  => 'bg-danger',
                                'Submitted' => 'bg-info',
                                default     => 'bg-warning text-dark',
                            };
                            ?>
                            <span class="badge <?= $badge ?> px-3"><?= $pr['status'] ?></span>
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
                        
                        // Check various possible locations due to historical path issues
                        // Some paths contain the prefix already, some don't.
                        $cleanAtt = basename($att); // get just the filename
                        
                        if (file_exists($rootPath . '/public/uploads/pr_attachments/' . $cleanAtt)) {
                            $downloadLink = $GLOBALS['base_url'] . '/public/uploads/pr_attachments/' . htmlspecialchars($cleanAtt);
                        } elseif (file_exists($rootPath . '/public/uploads/purchase_requests/' . $cleanAtt)) {
                            $downloadLink = $GLOBALS['base_url'] . '/public/uploads/purchase_requests/' . htmlspecialchars($cleanAtt);
                        } elseif (file_exists($rootPath . '/public/public/uploads/pr_attachments/' . $cleanAtt)) {
                            $downloadLink = $GLOBALS['base_url'] . '/public/public/uploads/pr_attachments/' . htmlspecialchars($cleanAtt);
                        } else {
                            // Default fallback
                            // If it starts with /public, just prepend base_url
                            if (strpos($att, '/public/') === 0) {
                                $downloadLink = $GLOBALS['base_url'] . htmlspecialchars($att);
                            } else {
                                $downloadLink = $GLOBALS['base_url'] . '/public/uploads/pr_attachments/' . htmlspecialchars($att);
                            }
                        }
                    ?>
                    <div class="alert alert-info border d-flex align-items-center justify-content-between mb-3 p-3" style="background: rgba(13, 202, 240, 0.08); border-color: rgba(13, 202, 240, 0.3) !important; border-radius: 10px;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-file-earmark-text fs-4 text-info"></i>
                            <div>
                                <strong class="d-block text-dark">Attached Document / Requisition File:</strong>
                                <span class="text-muted small"><?= htmlspecialchars(basename($pr['attachment'])) ?></span>
                            </div>
                        </div>
                        <a href="<?= $downloadLink ?>" target="_blank" class="btn btn-outline-info btn-sm">
                            <i class="bi bi-eye me-1"></i> View / Download Document
                        </a>
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
                                    <th class="text-end">Requested Qty</th>
                                    <th class="text-center">Required Date (BOQ)</th>
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
                                    <td class="text-end"><?= number_format($item['quantity'], 3) ?></td>
                                    <td class="text-center">
                                        <?php if (!empty($item['boq_required_date'])): ?>
                                            <span class="badge bg-light text-dark border border-secondary px-2 py-1">
                                                <i class="bi bi-calendar-event me-1 text-primary"></i>
                                                <?= date('d M Y', strtotime($item['boq_required_date'])) ?>
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

            <!-- Procurement Pipeline Actions -->
            <?php if ($pr['status'] === 'Approved' || $pr['status'] === 'PO Issued'): ?>
            <div class="card__wrapper shadow-sm">
                <div class="card__header">
                    <h6 class="card__title mb-0"><i class="bi bi-diagram-3 me-2"></i>Procurement Pipeline</h6>
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
                                <?php if (in_array($pr['status'], ['Approved', 'PO Issued'])): ?>
                                    <?php if (!$isReadOnly): ?>
                                        <a href="<?= $base_url ?>/con-quotation-create/<?= $pr['id'] ?>"
                                           class="btn btn-outline-info btn-sm">
                                            <i class="bi bi-plus me-1"></i> Add / View Quotations
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Awaiting Quotations</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-success"><i class="bi bi-check"></i> Done</span>
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
                                            <i class="bi bi-eye me-1"></i> View CST
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
                                    <i class="bi bi-file-earmark-check fs-3 text-success"></i>
                                </div>
                                <h6>Step 3: Purchase Order</h6>
                                <p class="small text-muted mb-2">
                                    <?= ($pr['status'] === 'PO Issued') ? 'PO Generated' : 'Auto-generate from CST' ?>
                                </p>
                                <?php if ($hasCST && $pr['status'] !== 'PO Issued'): ?>
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

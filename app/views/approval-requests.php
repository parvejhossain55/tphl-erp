<?php require_once 'layouts/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-0">Pending Approval Requests</h4>
            <p class="text-muted small mb-0">List of all requests pending your approval</p>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card__wrapper">
                <div class="card__body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= $_SESSION['success']; ?>
                            <?php unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= $_SESSION['error']; ?>
                            <?php unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Department & Title</th>
                                    <th>Requested By & Date</th>
                                    <th>Details</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($pendingRequests)): ?>
                                    <?php foreach ($pendingRequests as $req): ?>
                                        <?php
                                        $targetUrl = null;
                                        if ($req['source_table'] === 'con_labor_requisitions') {
                                            $targetUrl = $GLOBALS['base_url'] . '/con-labor-requisition/' . $req['source_id'];
                                        } elseif ($req['source_table'] === 'contractor_bill_requests') {
                                            $targetUrl = $GLOBALS['base_url'] . '/con-contractor-bill/' . $req['source_id'];
                                        } elseif ($req['source_table'] === 'vendor_payment_requests') {
                                            $targetUrl = $GLOBALS['base_url'] . '/con-vendor-payment-show/' . $req['source_id'];
                                        } elseif ($req['source_table'] === 'project_other_costs') {
                                            $targetUrl = $GLOBALS['base_url'] . '/project-other-costs/review/' . $req['source_id'];
                                        } elseif ($req['source_table'] === 'con_work_orders') {
                                            $targetUrl = $GLOBALS['base_url'] . '/con-work-order/' . $req['source_id'];
                                        } elseif ($req['source_table'] === 'fund_requisitions') {
                                            $targetUrl = $GLOBALS['base_url'] . '/fund-requisitions/view/' . $req['source_id'];
                                        } elseif ($req['source_table'] === 'client_refund_requests') {
                                            $targetUrl = $GLOBALS['base_url'] . '/client-refund/' . $req['source_id'];
                                        }
                                        ?>
                                        <tr <?= !empty($targetUrl) ? 'data-href="' . htmlspecialchars($targetUrl) . '" class="clickable-approval-row" style="cursor: pointer;"' : '' ?>>
                                            <td>
                                                <h6 class="mb-1">
                                                    <?php if ($targetUrl): ?>
                                                        <a href="<?= $targetUrl ?>" class="text-primary text-decoration-none"><?= htmlspecialchars($req['department']) ?></a>
                                                    <?php else: ?>
                                                        <?= htmlspecialchars($req['department']) ?>
                                                    <?php endif; ?>
                                                </h6>
                                                <span class="text-muted small">
                                                    <?php if ($targetUrl): ?>
                                                        <a href="<?= $targetUrl ?>" class="text-dark text-decoration-none hover-primary"><?= htmlspecialchars($req['title']) ?></a>
                                                    <?php else: ?>
                                                        <?= htmlspecialchars($req['title']) ?>
                                                    <?php endif; ?>
                                                </span>
                                                <?php 
                                                    $attach = $req['attachment'] ?? $req['context']['attachment'] ?? null;
                                                    if (!empty($attach)): 
                                                ?>
                                                    <div class="mt-1">
                                                        <a href="<?= $GLOBALS['base_url'] ?>/<?= htmlspecialchars(ltrim($attach, '/')) ?>" target="_blank" class="badge bg-light text-primary border" onclick="event.stopPropagation();" title="View Attachment / Supporting Document">
                                                            <i class="fal fa-paperclip me-1"></i> Attachment
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($req['context']['requester'])): ?>
                                                    <span class="d-block fw-semibold"><?= htmlspecialchars($req['context']['requester']['user_name'] ?? '') ?></span>
                                                    <span class="small text-muted"><?= htmlspecialchars($req['context']['requester']['designation'] ?? '') ?></span>
                                                <?php else: ?>
                                                    <span class="d-block fw-semibold">System</span>
                                                <?php endif; ?>
                                                <div class="small text-muted mt-1"><i class="fal fa-calendar me-1"></i> <?= date('d M Y', strtotime($req['created_at'])) ?></div>
                                            </td>
                                            <td>
                                                <?php if ($req['source_table'] === 'employee_leaves'): 
                                                    $meta = json_decode($req['meta_data'], true);
                                                    $requested_days = $meta['requested_days'] ?? 0;
                                                ?>
                                                    <strong>Requested Days:</strong> <?= $requested_days ?>
                                                <?php elseif (!empty($req['request_amount'])): ?>
                                                    <strong>Requested Amount:</strong> ৳ <?= format_bdt($req['request_amount']) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning text-dark"><?= ucfirst($req['status']) ?></span>
                                            </td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#viewContextModal<?= $req['id'] ?>" title="View Details">
                                                    <i class="fal fa-eye"></i>
                                                </button>
                                                <?php if ($targetUrl): ?>
                                                    <a href="<?= $targetUrl ?>" class="btn btn-sm btn-outline-secondary" title="View Requisition Details">
                                                        <i class="fal fa-external-link"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php
                                                $userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
                                                $sessionRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
                                                $isChiefOfficer = ($userRoleId === 33 || $sessionRoleName === 'chief officer');
                                                $isMgmt = in_array($userRoleId, [1, 3], true) && !$isChiefOfficer;
                                                $isSiteOp = ($userRoleId === 20);
                                                $canAct = !$isChiefOfficer && ($isMgmt || ($isSiteOp && (float)($req['request_amount'] ?? 0) <= 10000) || !in_array(strtolower($req['approve_role'] ?? ''), ['management']));
                                                ?>
                                                <?php if ($canAct): ?>
                                                    <?php if ($req['request_type'] !== 'manpower_requisition'): ?>
                                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#partialApproveModal<?= $req['id'] ?>" title="Partial Approve">
                                                            <i class="fal fa-edit"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <form method="POST" action="<?= $GLOBALS['base_url'] ?>/approve-request/<?= $req['id'] ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to approve this request?');">
                                                        <button class="btn btn-sm btn-success" title="Full Approve">
                                                            <i class="fal fa-check"></i>
                                                        </button>
                                                    </form>
                                                    <form method="POST" action="<?= $GLOBALS['base_url'] ?>/reject-request/<?= $req['id'] ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to reject this request?');">
                                                        <button class="btn btn-sm btn-danger" title="Reject">
                                                            <i class="fal fa-times"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary-subtle text-muted border extra-small" title="Amount exceeds ৳10,000. Top Management approval required.">
                                                        <i class="fa-solid fa-lock text-warning me-1"></i>Management Only
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>

                                        <!-- Partial Modal -->
                                        <div class="modal fade" id="partialApproveModal<?= $req['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h6 class="modal-title">Partial Approval</h6>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body text-start">
                                                        <form method="POST" action="<?= $GLOBALS['base_url'] ?>/partial-approve-request/<?= $req['id'] ?>">
                                                            <?php if ($req['source_table'] === 'employee_leaves'): 
                                                                $meta = json_decode($req['meta_data'], true);
                                                                $requested_days = $meta['requested_days'] ?? 0;
                                                            ?>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Requested Days</label>
                                                                    <input type="number" class="form-control" value="<?= $requested_days ?>" disabled>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Approve Days</label>
                                                                    <input type="number" class="form-control" name="approved_days" placeholder="Enter approved days" required max="<?= $requested_days ?>">
                                                                </div>
                                                            <?php else: ?>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Requested Amount</label>
                                                                    <input type="text" class="form-control" value="৳ <?= number_format($req['request_amount'], 2) ?>" disabled>
                                                                </div>
                                                                <?php 
                                                                    $partialAttach = $req['attachment'] ?? $req['context']['attachment'] ?? null;
                                                                    if (!empty($partialAttach)): 
                                                                ?>
                                                                    <div class="mb-3 p-2 bg-light rounded border">
                                                                        <label class="form-label d-block text-muted small mb-1">Supporting Document / Attachment:</label>
                                                                        <a href="<?= $GLOBALS['base_url'] ?>/<?= htmlspecialchars(ltrim($partialAttach, '/')) ?>" target="_blank" class="btn btn-sm btn-outline-primary py-1 px-2">
                                                                            <i class="fal fa-paperclip me-1"></i> View Supporting Document
                                                                        </a>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Approve Amount</label>
                                                                    <input type="number" class="form-control" name="approved_amount" placeholder="Enter approved amount" required <?= ($isSiteOp ?? false) ? 'max="10000"' : '' ?>>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Note</label>
                                                                    <textarea class="form-control" name="approval_note" placeholder="Enter note here" rows="4"></textarea>
                                                                </div>
                                                            <?php endif; ?>

                                                            <div class="modal-footer px-0 pb-0">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-success">Approve</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- View Context Modal -->
                                        <div class="modal fade" id="viewContextModal<?= $req['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                                <div class="modal-content text-start">
                                                    <div class="modal-header">
                                                        <h6 class="modal-title">Request Details</h6>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body p-4 bg-light">
                                                        <?php renderApprovalContext($req); ?>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <?php if (!empty($targetUrl)): ?>
                                                            <a href="<?= $targetUrl ?>" class="btn btn-primary btn-sm">
                                                                <i class="fal fa-external-link me-1"></i> Open Requisition Details
                                                            </a>
                                                        <?php endif; ?>
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <i class="fal fa-folder-open fa-3x mb-3 d-block"></i>
                                            No pending requests available.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('tr.clickable-approval-row').forEach(function(row) {
        row.addEventListener('click', function(e) {
            if (e.target.closest('button, a, form, input, select, textarea, .modal')) return;
            var href = this.getAttribute('data-href');
            if (href) window.location.href = href;
        });
    });
});
</script>

<style>
.clickable-approval-row:hover td {
    background-color: rgba(var(--bs-primary-rgb), 0.05) !important;
}
.hover-primary:hover {
    color: var(--bs-primary) !important;
}
</style>

<?php require_once 'layouts/footer.php'; ?>

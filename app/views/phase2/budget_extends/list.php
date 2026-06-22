<?php
$base_url   = $GLOBALS['base_url'];
$page_title = $page_title ?? 'Budget Adjustments';
?>
<div class="container-fluid">
    <!-- Breadcrumb & Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h4 class="page-title fw-semibold fs-18 mb-0"><?= $page_title ?></h4>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Budget Adjustment Requests</li>
            </ol>
        </div>
        <div class="my-auto">
            <a href="<?= $base_url ?>/con-budget-extend-create" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> New Request
            </a>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card custom-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0"><i class="bi bi-patch-plus me-2 text-primary"></i> Budget Adjustment Requests</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="budgetExtendTable" class="table table-bordered table-hover text-nowrap align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Project</th>
                            <th>Title / Purpose</th>
                            <th class="text-end">Adjustment (৳)</th>
                            <th>Requested By</th>
                            <th>Status</th>
                            <th>Processed By / Remarks</th>
                            <th>Docs & Attachments</th>
                            <?php if (in_array($userRole, [1, 3, 15, 22])): ?>
                                <th class="text-center">Action</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($requests)): ?>
                            <?php foreach ($requests as $i => $r): ?>
                                <?php
                                $statusClass = match(strtolower($r['status'])) {
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                    default    => 'warning text-dark',
                                };
                                $amt = (float)$r['amount'];
                                ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td>
                                        <span class="fw-semibold text-primary"><?= htmlspecialchars($r['project_name'] ?? '-') ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($r['title'] ?? '-') ?></div>
                                        <small class="text-muted text-wrap d-block" style="max-width: 250px;">
                                            <?= htmlspecialchars($r['reason'] ?? '-') ?>
                                        </small>
                                    </td>
                                    <td class="text-end fw-bold">
                                        <?php if ($amt >= 0): ?>
                                            <span class="text-success">+ ৳ <?= number_format($amt, 2) ?></span>
                                        <?php else: ?>
                                            <span class="text-danger">- ৳ <?= number_format(abs($amt), 2) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($r['requester_name'] ?? '-') ?></td>
                                    <td>
                                        <span class="badge bg-<?= $statusClass ?>"><?= ucfirst($r['status']) ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($r['approved_by_name'])): ?>
                                            <div class="fw-semibold"><?= htmlspecialchars($r['approved_by_name']) ?></div>
                                            <?php if ($r['approved_at']): ?>
                                                <small class="text-muted"><?= date('d M Y, h:i A', strtotime($r['approved_at'])) ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($r['remarks'])): ?>
                                            <div class="mt-1 text-danger small text-wrap" style="max-width: 200px;">
                                                <strong>Remarks:</strong> <?= htmlspecialchars($r['remarks']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <?php if (!empty($r['attachment_file'])): ?>
                                                <a href="<?= getFileUrl('budget_extends', $r['attachment_file']) ?>" 
                                                   class="btn btn-sm btn-outline-secondary py-0 px-2 text-start" 
                                                   target="_blank">
                                                    <i class="bi bi-file-earmark-arrow-down me-1"></i> Attachment
                                                </a>
                                            <?php endif; ?>
                                            <?php if (!empty($r['docs_link'])): ?>
                                                <a href="<?= htmlspecialchars($r['docs_link']) ?>" 
                                                   class="btn btn-sm btn-outline-info py-0 px-2 text-start" 
                                                   target="_blank">
                                                    <i class="bi bi-link-45deg me-1"></i> Drive Link
                                                </a>
                                            <?php endif; ?>
                                            <?php if (empty($r['attachment_file']) && empty($r['docs_link'])): ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <?php if (in_array($userRole, [1, 3, 15, 22])): ?>
                                        <td class="text-center">
                                            <?php if (strtolower($r['status']) === 'pending'): ?>
                                                <div class="d-flex justify-content-center gap-1">
                                                    <a href="<?= $base_url ?>/con-budget-extend-approve/<?= $r['id'] ?>" 
                                                       class="btn btn-sm btn-success px-2 py-1" 
                                                       onclick="return confirm('Are you sure you want to approve this budget adjustment request? This will update the project budget.');">
                                                        <i class="bi bi-check-circle me-1"></i> Approve
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-danger px-2 py-1" 
                                                            onclick="openRejectModal(<?= $r['id'] ?>)">
                                                        <i class="bi bi-x-circle me-1"></i> Reject
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted small"><i class="bi bi-lock-fill"></i> Processed</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?= in_array($userRole, [1, 3, 15, 22]) ? 9 : 8 ?>" class="text-center text-muted py-4">
                                    No budget adjustment requests found. <a href="<?= $base_url ?>/con-budget-extend-create">Create a request</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="rejectForm" method="POST" action="">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel"><i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>Reject Budget Adjustment Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="remarks" class="form-label fw-semibold">Rejection Remarks / Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="remarks" id="remarks" rows="4" required placeholder="Please write a reason for rejecting this adjustment request..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="bi bi-x-circle me-1"></i> Submit Rejection
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function openRejectModal(id) {
    const form = document.getElementById('rejectForm');
    form.action = '<?= $base_url ?>/con-budget-extend-reject/' + id;
    const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
    modal.show();
}

$(document).ready(function () {
    if ($.fn.DataTable && $('#budgetExtendTable tbody tr:not(.text-center)').length > 0) {
        $('#budgetExtendTable').DataTable({
            responsive: true,
            pageLength: 25,
            order: [[0, 'asc']]
        });
    }
});
</script>

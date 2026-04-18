<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><?= htmlspecialchars($page_title) ?></h1>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Budget Extensions</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $base_url ?>/con-working-plans" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-calendar3 me-1"></i> Working Plans
        </a>
        <a href="<?= $base_url ?>/con-budget-extend-create" class="btn btn-warning btn-sm">
            <i class="bi bi-plus-circle me-1"></i> New Request
        </a>
    </div>
</div>

<section class="section">

    <!-- ================================================================ -->
    <!-- Report: Initial vs Revised vs Planned                             -->
    <!-- ================================================================ -->
    <?php if (!empty($report)): ?>
    <div class="card__wrapper shadow-sm mb-4">
        <div class="card__header">
            <h6 class="card__title mb-0">
                <i class="bi bi-bar-chart me-2"></i>Budget Status Report — Initial vs Revised vs Planned
            </h6>
        </div>
        <div class="card__body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Project</th>
                            <th>Budget Status</th>
                            <th class="text-end">Initial Budget (৳)</th>
                            <th class="text-end">Approved Extensions (৳)</th>
                            <th class="text-end">Revised Total (৳)</th>
                            <th class="text-end">Total Planned (৳)</th>
                            <th class="text-end">Remaining (৳)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report as $ri => $row): ?>
                        <?php
                        $remaining = (float)$row['remaining'];
                        $remainClass = $remaining < 0 ? 'text-danger fw-bold' : 'text-success fw-bold';
                        $statusBadge = match($row['budget_status']) {
                            'approved'  => 'bg-success',
                            'cancelled' => 'bg-danger',
                            default     => 'bg-warning text-dark',
                        };
                        ?>
                        <tr>
                            <td><?= $ri + 1 ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($row['project_name']) ?></td>
                            <td><span class="badge <?= $statusBadge ?>"><?= ucfirst($row['budget_status']) ?></span></td>
                            <td class="text-end"><?= number_format($row['initial_budget'], 2) ?></td>
                            <td class="text-end text-warning fw-bold">
                                <?= $row['approved_extensions'] > 0 ? '+' . number_format($row['approved_extensions'], 2) : '—' ?>
                            </td>
                            <td class="text-end fw-bold text-primary"><?= number_format($row['revised_total'], 2) ?></td>
                            <td class="text-end"><?= number_format($row['total_planned'], 2) ?></td>
                            <td class="text-end <?= $remainClass ?>"><?= number_format($remaining, 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ================================================================ -->
    <!-- Extension Requests List                                            -->
    <!-- ================================================================ -->
    <div class="card__wrapper shadow-sm">
        <div class="card__body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped custom-table mb-0 multiple-table">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Project / Subject</th>
                            <th>Budget Ref.</th>
                            <th class="text-end">Amount (৳)</th>
                            <th>Requested By</th>
                            <th>Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($extends)): ?>
                            <?php foreach ($extends as $i => $item): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="text-muted small">
                                    <?= date('d M Y', strtotime($item['created_at'])) ?>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($item['project_name']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($item['title']) ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        Budget #<?= $item['initial_budget_id'] ?>
                                    </span>
                                </td>
                                <td class="text-end fw-bold text-danger">
                                    +৳ <?= number_format($item['amount'], 2) ?>
                                </td>
                                <td><?= htmlspecialchars($item['requested_by_name']) ?></td>
                                <td>
                                    <?php
                                    $badge = match($item['status']) {
                                        'Approved' => 'bg-success',
                                        'Rejected' => 'bg-danger',
                                        default    => 'bg-warning text-dark',
                                    };
                                    ?>
                                    <span class="badge <?= $badge ?>"><?= $item['status'] ?></span>
                                    <?php if (!empty($item['approved_by_name'])): ?>
                                    <div class="small text-muted">
                                        by <?= htmlspecialchars($item['approved_by_name']) ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($item['status'] === 'Pending'): ?>
                                        <!-- Approve Button -->
                                        <button type="button"
                                                class="btn btn-sm btn-success"
                                                data-bs-toggle="modal"
                                                data-bs-target="#approveModal"
                                                data-id="<?= $item['id'] ?>"
                                                data-title="<?= htmlspecialchars($item['title']) ?>"
                                                title="Approve">
                                            <i class="bi bi-check-circle"></i>
                                        </button>
                                        <!-- Reject Button -->
                                        <button type="button"
                                                class="btn btn-sm btn-danger ms-1"
                                                data-bs-toggle="modal"
                                                data-bs-target="#rejectModal"
                                                data-id="<?= $item['id'] ?>"
                                                data-title="<?= htmlspecialchars($item['title']) ?>"
                                                title="Reject">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted small"><?= $item['status'] ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    No budget extension requests yet.
                                    <a href="<?= $base_url ?>/con-budget-extend-create" class="ms-2">Submit one →</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- ================================================================ -->
<!-- Approve Modal                                                      -->
<!-- ================================================================ -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="approveForm" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title text-success" id="approveModalLabel">
                        <i class="bi bi-check-circle me-2"></i>Approve Extension Request
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Approving: <strong id="approveTitleText"></strong></p>
                    <label class="form-label fw-bold">Remarks (optional)</label>
                    <textarea name="remarks" class="form-control" rows="3"
                              placeholder="Any note on the approval…"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4">
                        <i class="bi bi-check-circle me-1"></i> Confirm Approve
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================================================================ -->
<!-- Reject Modal                                                       -->
<!-- ================================================================ -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="rejectForm" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title text-danger" id="rejectModalLabel">
                        <i class="bi bi-x-circle me-2"></i>Reject Extension Request
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Rejecting: <strong id="rejectTitleText"></strong></p>
                    <label class="form-label fw-bold">
                        Reason for Rejection <span class="text-danger">*</span>
                    </label>
                    <textarea name="remarks" class="form-control" rows="3"
                              placeholder="Explain why this request is being rejected…" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4">
                        <i class="bi bi-x-circle me-1"></i> Confirm Reject
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var base = '<?= $base_url ?>';

    // Approve modal
    document.getElementById('approveModal').addEventListener('show.bs.modal', function (e) {
        var btn = e.relatedTarget;
        var id    = btn.getAttribute('data-id');
        var title = btn.getAttribute('data-title');
        document.getElementById('approveTitleText').textContent = title;
        document.getElementById('approveForm').action = base + '/con-budget-extend-approve/' + id;
    });

    // Reject modal
    document.getElementById('rejectModal').addEventListener('show.bs.modal', function (e) {
        var btn = e.relatedTarget;
        var id    = btn.getAttribute('data-id');
        var title = btn.getAttribute('data-title');
        document.getElementById('rejectTitleText').textContent = title;
        document.getElementById('rejectForm').action = base + '/con-budget-extend-reject/' + id;
    });
});
</script>

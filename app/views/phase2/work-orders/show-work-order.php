<?php
$base_url   = $GLOBALS['base_url'];
$page_title = $page_title ?? 'Work Order Detail';

$statusColors = [
    'draft'          => 'secondary',
    'approved'       => 'info',
    'active'         => 'primary',
    'work_completed' => 'warning',
    'qc_approved'    => 'success',
    'closed'         => 'dark',
    'rejected'       => 'danger',
];
$statusLabels = [
    'draft'          => 'Draft',
    'approved'       => 'Approved',
    'active'         => 'Active',
    'work_completed' => 'Work Completed — Awaiting QC',
    'qc_approved'    => 'QC Approved',
    'closed'         => 'Closed',
    'rejected'       => 'Rejected',
];

$status     = $wo['status'];
$statusCol  = $statusColors[$status] ?? 'secondary';
$statusLbl  = $statusLabels[$status] ?? ucfirst($status);
$totalAmt   = (float)($wo['total_amount'] ?? 0);
$advAmt     = (float)($wo['advance_amount'] ?? 0);
$retAmt     = (float)($wo['retention_amount'] ?? 0);
$runAmt     = $totalAmt - $advAmt - $retAmt;
?>

<div class="container-fluid">
    <!-- Breadcrumb -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h4 class="page-title fw-semibold text-muted fs-18 mb-0"><?= htmlspecialchars($wo['wo_number'] ?? 'Work Order') ?></h4>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-work-orders">Work Orders</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($wo['wo_number'] ?? '') ?></li>
            </ol>
        </div>
        <div class="d-flex gap-2 my-xl-auto right-content">
            <a href="<?= $base_url ?>/con-work-orders" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            <a href="<?= $base_url ?>/con-work-order-print/<?= $wo['id'] ?>" target="_blank"
               class="btn btn-outline-primary btn-sm">
                <i class="bi bi-printer me-1"></i> Print / PDF
            </a>
            <?php if ($status === 'draft' && $canApprove): ?>
                <a href="<?= $base_url ?>/con-work-order-edit/<?= $wo['id'] ?>" class="btn btn-outline-warning btn-sm">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Status Banner -->
    <div class="alert alert-<?= $statusCol ?> d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-info-circle-fill fs-18"></i>
        <div>
            <strong>Status:</strong> <?= $statusLbl ?>
            <?php if ($status === 'approved'): ?>
                — Approved by <strong><?= htmlspecialchars($wo['approved_by_name'] ?? '') ?></strong>
                on <?= date('d M Y H:i', strtotime($wo['approved_at'])) ?>
            <?php elseif ($status === 'closed'): ?>
                — QC cleared by <strong><?= htmlspecialchars($wo['qc_approved_by_name'] ?? '') ?></strong>
                on <?= date('d M Y H:i', strtotime($wo['qc_approved_at'])) ?>
            <?php elseif ($status === 'rejected'): ?>
                — Reason: <?= htmlspecialchars($wo['rejection_reason'] ?? '-') ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left Column -->
        <div class="col-xl-8">
            <!-- Work Order Details -->
            <div class="card custom-card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-file-earmark-ruled me-2 text-primary"></i>Work Order Details</h6>
                </div>
                <div class="card-body">
                    <div class="row ">
                        <div class="col-md-6">
                            <div class="text-muted fs-12">WO Number</div>
                            <div class="fw-semibold text-muted"><?= htmlspecialchars($wo['wo_number'] ?? '-') ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted fs-12">Status</div>
                            <div><span class="badge bg-<?= $statusCol ?>-transparent text-<?= $statusCol ?> fs-12"><?= $statusLbl ?></span></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted fs-12">Project</div>
                            <div class="fw-semibold text-muted"><?= htmlspecialchars($wo['project_name'] ?? '-') ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted fs-12">Work Design Type</div>
                            <div class="text-muted"><?= htmlspecialchars($wo['design_type_name'] ?? '-') ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted fs-12">Work Type</div>
                            <div class="text-muted"><?= htmlspecialchars($wo['work_type_name'] ?? '-') ?></div>
                        </div>
                        <div class="col-12">
                            <div class="text-muted fs-12">Title</div>
                            <div class="fw-semibold text-muted fs-15"><?= htmlspecialchars($wo['title'] ?? '-') ?></div>
                        </div>
                        <?php if (!empty($wo['description'])): ?>
                        <div class="col-12">
                            <div class="text-muted fs-12">Description</div>
                            <div class="p-3 bg-light rounded"><?= nl2br(htmlspecialchars($wo['description'])) ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($wo['scope_of_work'])): ?>
                        <div class="col-12">
                            <div class="text-muted fs-12">Scope of Work</div>
                            <div class="p-3 bg-light rounded"><?= nl2br(htmlspecialchars($wo['scope_of_work'])) ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($wo['notes'])): ?>
                        <div class="col-12">
                            <div class="text-muted fs-12">Notes</div>
                            <div class="p-2 border-start border-warning border-3 ps-3"><?= nl2br(htmlspecialchars($wo['notes'])) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Rate & Budget -->
            <div class="card custom-card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-calculator me-2 text-success"></i>Rate & Budget</h6>
                </div>
                <div class="card-body">
                    <div class="row ">
                        <div class="col-md-3">
                            <div class="text-muted fs-12">Unit</div>
                            <div class="fw-semibold text-muted"><?= strtoupper($wo['unit'] ?? '-') ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted fs-12">Unit Rate</div>
                            <div class="fw-semibold text-muted">৳<?= number_format($wo['unit_rate'] ?? 0, 2) ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted fs-12">Quantity</div>
                            <div class="fw-semibold text-muted"><?= number_format($wo['quantity'] ?? 0, 2) ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted fs-12">Total Amount</div>
                            <div class="fw-bold text-primary fs-16">৳<?= number_format($totalAmt, 2) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Timeline -->
            <div class="card custom-card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-calendar-range me-2 text-warning"></i>Timeline</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="text-muted fs-12">Start Date</div>
                            <div class="fw-semibold text-muted"><?= $wo['start_date'] ? date('d M Y', strtotime($wo['start_date'])) : '—' ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted fs-12">End Date (Deadline)</div>
                            <div class="fw-semibold text-muted <?= ($wo['end_date'] && strtotime($wo['end_date']) < time() && !in_array($status, ['closed','qc_approved'])) ? 'text-danger' : '' ?>">
                                <?= $wo['end_date'] ? date('d M Y', strtotime($wo['end_date'])) : '—' ?>
                                <?php if ($wo['end_date'] && strtotime($wo['end_date']) < time() && !in_array($status, ['closed','qc_approved','work_completed'])): ?>
                                    <span class="badge bg-danger ms-1">Overdue</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Working Plan Link -->
            <?php if (!empty($wo['work_plan_title'])): ?>
            <div class="card custom-card mb-4 border-warning">
                <div class="card-header bg-warning-transparent">
                    <h6 class="card-title mb-0"><i class="bi bi-bullseye me-2 text-warning"></i>Linked Working Plan</h6>
                </div>
                <div class="card-body">
                    <div class="row ">
                        <div class="col-md-12">
                            <div class="text-muted fs-12">Working Plan</div>
                            <div class="fw-semibold text-muted"><?= htmlspecialchars($wo['work_plan_title'] ?? '—') ?>
                                <small class="text-muted">(<?= $wo['plan_month'] ?? '' ?>/<?= $wo['plan_year'] ?? '' ?>)</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Linked Weekly Reports -->
            <?php if (!empty($linkedWeeklyReports)): ?>
            <div class="card custom-card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-graph-up-arrow me-2 text-success"></i>
                        Weekly Progress Reports
                        <span class="badge bg-success ms-2"><?= count($linkedWeeklyReports) ?></span>
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Report Date</th>
                                    <th>Engineer</th>
                                    <th>Work Done</th>
                                    <th>Progress</th>
                                    <th>Weather</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($linkedWeeklyReports as $wr): ?>
                                <tr>
                                    <td><?= date('d M Y', strtotime($wr['report_date'])) ?></td>
                                    <td><?= htmlspecialchars($wr['engineer_name'] ?? '—') ?></td>
                                    <td>
                                        <span title="<?= htmlspecialchars($wr['work_done']) ?>">
                                            <?= htmlspecialchars(mb_strimwidth($wr['work_done'], 0, 40, '...')) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <div class="progress flex-grow-1" style="height:6px;">
                                                <div class="progress-bar bg-success" style="width:<?= $wr['progress_percentage'] ?>%"></div>
                                            </div>
                                            <small><?= $wr['progress_percentage'] ?>%</small>
                                        </div>
                                    </td>
                                    <td><small><?= htmlspecialchars($wr['weather_condition'] ?? '—') ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Linked Bills -->
            <?php if (!empty($linkedBills)): ?>
            <div class="card custom-card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-receipt me-2 text-info"></i>Linked Contractor Bills</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Bill Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($linkedBills as $bill): ?>
                                <tr>
                                    <td><?= date('d M Y', strtotime($bill['bill_date'])) ?></td>
                                    <td>৳<?= number_format($bill['bill_amount'], 2) ?></td>
                                    <td><span class="badge bg-secondary"><?= ucfirst($bill['status']) ?></span></td>
                                    <td>
                                        <a href="<?= $base_url ?>/con-contractor-bill/<?= $bill['id'] ?>" class="btn btn-xs btn-outline-primary">View</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right Column -->
        <div class="col-xl-4">
            <!-- Contractor Info -->
            <div class="card custom-card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-person-badge me-2 text-primary"></i>Contractor</h6>
                </div>
                <div class="card-body">
                    <div class="fw-bold fs-15 mb-1 text-muted"><?= htmlspecialchars($wo['contractor_name'] ?? '-') ?></div>
                    <?php if (!empty($wo['contractor_trade'])): ?>
                        <div class="text-muted mb-1"><i class="bi bi-tools me-1"></i><?= htmlspecialchars($wo['contractor_trade']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($wo['contractor_phone'])): ?>
                        <div class="text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($wo['contractor_phone']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Payment Terms -->
            <div class="card custom-card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-wallet2 me-2 text-danger"></i>Payment Terms</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3 p-3 rounded bg-info-transparent">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Advance (<?= $wo['advance_percent'] ?? 0 ?>%)</span>
                            <strong class="text-info">৳<?= number_format($advAmt, 2) ?></strong>
                        </div>
                    </div>
                    <div class="mb-3 p-3 rounded bg-primary-transparent">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Running (<?= $wo['running_percent'] ?? 70 ?>%)</span>
                            <strong class="text-primary">৳<?= number_format($runAmt, 2) ?></strong>
                        </div>
                    </div>
                    <div class="p-3 rounded bg-danger-transparent">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Retention (<?= $wo['retention_percent'] ?? 10 ?>%)</span>
                            <strong class="text-danger">৳<?= number_format($retAmt, 2) ?></strong>
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="fw-semibold text-muted">Total</span>
                        <strong class="text-primary fs-15">৳<?= number_format($totalAmt, 2) ?></strong>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card custom-card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-lightning me-2 text-warning"></i>Actions</h6>
                </div>
                <div class="card-body d-grid gap-2">

                    <!-- Approve / Reject (draft → approved) -->
                    <?php if ($status === 'draft' && $canApprove): ?>
                        <form method="POST" action="<?= $base_url ?>/con-work-order-approve/<?= $wo['id'] ?>"
                              onsubmit="return confirm('Approve this Work Order?')">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-check-circle me-1"></i> Approve Work Order
                            </button>
                        </form>
                        <button class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="bi bi-x-circle me-1"></i> Reject
                        </button>
                    <?php endif; ?>

                    <!-- Mark Complete (approved/active → work_completed) -->
                    <?php if (in_array($status, ['approved','active']) && $canMarkComplete): ?>
                        <form method="POST" action="<?= $base_url ?>/con-work-order-complete/<?= $wo['id'] ?>"
                              onsubmit="return confirm('Mark this Work Order as Complete? QC review will be required.')">
                            <button type="submit" class="btn btn-warning w-100">
                                <i class="bi bi-check2-all me-1"></i> Mark Work Complete
                            </button>
                        </form>
                    <?php endif; ?>

                    <!-- QC Approve (work_completed → closed) -->
                    <?php if ($status === 'work_completed' && $canQcApprove): ?>
                        <form method="POST" action="<?= $base_url ?>/con-work-order-qc-approve/<?= $wo['id'] ?>"
                              onsubmit="return confirm('QC approve this work? This will CLOSE the Work Order and allow Contractor Bills.')">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-shield-check me-1"></i> QC Approve &amp; Close
                            </button>
                        </form>
                    <?php endif; ?>

                    <!-- Closed — Can now create bill -->
                    <?php if ($status === 'closed'): ?>
                        <a href="<?= $base_url ?>/con-contractor-bill-create" class="btn btn-outline-success w-100">
                            <i class="bi bi-receipt me-1"></i> Create Contractor Bill
                        </a>
                    <?php endif; ?>

                    <!-- Delete (draft only) -->
                    <?php if ($status === 'draft' && $canDelete): ?>
                        <form method="POST" action="<?= $base_url ?>/con-work-order-delete"
                              onsubmit="return confirm('Delete this Work Order permanently?')">
                            <input type="hidden" name="id" value="<?= $wo['id'] ?>">
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="bi bi-trash me-1"></i> Delete
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Meta Info -->
            <div class="card custom-card">
                <div class="card-body">
                    <div class="text-muted fs-12">Created By</div>
                    <div class="fw-semibold text-muted mb-2"><?= htmlspecialchars($wo['created_by_name'] ?? '-') ?></div>
                    <div class="text-muted fs-12">Created At</div>
                    <div class="mb-2 text-muted"><?= date('d M Y H:i', strtotime($wo['created_at'])) ?></div>
                    <?php if ($wo['completed_by_name']): ?>
                        <div class="text-muted fs-12">Completed By</div>
                        <div class="mb-2 text-muted"><?= htmlspecialchars($wo['completed_by_name']) ?> on <?= date('d M Y', strtotime($wo['completed_at'])) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= $base_url ?>/con-work-order-reject/<?= $wo['id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Work Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold text-muted">Rejection Reason</label>
                    <textarea name="rejection_reason" class="form-control" rows="3" required
                              placeholder="Explain why this Work Order is rejected..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Work Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$base_url = $GLOBALS['base_url'];
$page_title = $page_title ?? 'Work Orders';

$statusColors = [
    'draft' => 'secondary',
    'approved' => 'info',
    'active' => 'primary',
    'work_completed' => 'warning',
    'qc_approved' => 'success',
    'closed' => 'dark',
    'rejected' => 'danger',
];
$statusLabels = [
    'draft' => 'Draft',
    'approved' => 'Approved',
    'active' => 'Active',
    'work_completed' => 'Work Completed',
    'qc_approved' => 'QC Approved',
    'closed' => 'Closed',
    'rejected' => 'Rejected',
];
?>

<div class="container-fluid">
    <!-- Breadcrumb -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h4 class="page-title fw-semibold fs-18 mb-0"><?= $page_title ?></h4>
            <?php if ($canCreate): ?>
                <div class="d-flex my-xl-auto right-content align-items-center flex-wrap gap-2">
                    <a href="<?= $base_url ?>/con-work-order-create" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg me-1"></i> New Work Order
                    </a>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Stats Cards -->
    <?php
    $statusCount = $statusCounts ?? [];
    $total = $totalRecords ?? count($workOrders);
    ?>
    <div class="row  my-4">
        <div class="col-6 col-md-2">
            <div class="card custom-card text-center border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="fs-22 fw-bold text-primary"><?= $total ?></div>
                    <div class="text-muted fs-12">Total</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card custom-card text-center border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="fs-22 fw-bold text-secondary"><?= $statusCount['draft'] ?? 0 ?></div>
                    <div class="text-muted fs-12">Draft</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card custom-card text-center border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="fs-22 fw-bold text-info">
                        <?= ($statusCount['approved'] ?? 0) + ($statusCount['active'] ?? 0) ?></div>
                    <div class="text-muted fs-12">Active</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card custom-card text-center border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="fs-22 fw-bold text-warning"><?= $statusCount['work_completed'] ?? 0 ?></div>
                    <div class="text-muted fs-12">Pending QC</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card custom-card text-center border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="fs-22 fw-bold text-dark"><?= $statusCount['closed'] ?? 0 ?></div>
                    <div class="text-muted fs-12">Closed</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card custom-card text-center border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="fs-22 fw-bold text-danger"><?= $statusCount['rejected'] ?? 0 ?></div>
                    <div class="text-muted fs-12">Rejected</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card custom-card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h6 class="card-title mb-0">
                <i class="bi bi-file-earmark-ruled me-2 text-primary"></i> Work Orders List
            </h6>
            <span class="badge bg-primary-transparent text-primary"><?= $total ?> records</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover text-nowrap mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>WO Number</th>
                            <th>Project</th>
                            <th>Contractor</th>
                            <th>Work Type</th>
                            <th>Total Amount</th>
                            <th>Timeline</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($workOrders)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    No work orders found.
                                    <?php if ($canCreate): ?>
                                        <a href="<?= $base_url ?>/con-work-order-create">Create one</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($workOrders as $i => $wo): ?>
                                <tr>
                                    <td><?= ($offset ?? 0) + $i + 1 ?></td>
                                    <td>
                                        <a href="<?= $base_url ?>/con-work-order/<?= $wo['id'] ?>"
                                            class="fw-semibold text-primary">
                                            <?= htmlspecialchars($wo['wo_number'] ?? 'WO-') ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($wo['project_name'] ?? '-') ?></td>
                                    <td>
                                        <?= htmlspecialchars($wo['contractor_name'] ?? '-') ?>
                                        <?php if ($wo['contractor_trade']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($wo['contractor_trade']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($wo['work_type_name'] ?? '-') ?></td>
                                    <td>
                                        <strong>৳<?= number_format($wo['total_amount'] ?? 0, 2) ?></strong>
                                    </td>
                                    <td>
                                        <?php if ($wo['start_date']): ?>
                                            <small>
                                                <?= date('d M Y', strtotime($wo['start_date'])) ?>
                                                → <?= $wo['end_date'] ? date('d M Y', strtotime($wo['end_date'])) : '?' ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span
                                            class="badge bg-<?= $statusColors[$wo['status']] ?? 'secondary' ?>-transparent text-<?= $statusColors[$wo['status']] ?? 'secondary' ?> mb-1">
                                            <?= $statusLabels[$wo['status']] ?? ucfirst($wo['status']) ?>
                                        </span>
                                        <?php if (in_array($wo['status'], ['approved', 'active', 'work_completed', 'qc_approved', 'closed']) && !empty($wo['approved_by_name'])): ?>
                                            <div class="text-muted" style="font-size: 11px; line-height: 1.3;">
                                                <i class="bi bi-check-circle text-success me-1"></i>Approved by:
                                                <span class="fw-semibold text-dark"><?= htmlspecialchars($wo['approved_by_name']) ?></span>
                                                <?php if (!empty($wo['approved_at'])): ?>
                                                    <br><span class="text-muted" style="font-size: 10px; padding-left: 14px;"><?= date('d M Y h:i A', strtotime($wo['approved_at'])) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif (in_array($wo['status'], ['draft', 'rejected'])): ?>
                                            <?php 
                                            $pendingRole = $wo['pending_role_name'] ?? '';
                                            if ($pendingRole === 'super Admin') {
                                                $pendingRole = 'Management';
                                            }
                                            if (!empty($pendingRole)): 
                                            ?>
                                                <div class="text-warning-emphasis" style="font-size: 11px; line-height: 1.3;">
                                                    <i class="bi bi-hourglass-split text-warning me-1"></i>Pending:
                                                    <span class="fw-semibold text-warning-emphasis"><?= htmlspecialchars($pendingRole) ?></span>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= $base_url ?>/con-work-order/<?= $wo['id'] ?>"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($wo['status'] === 'draft' && $canCreate): ?>
                                            <a href="<?= $base_url ?>/con-work-order-edit/<?= $wo['id'] ?>"
                                                class="btn btn-sm btn-outline-warning">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div class="row my-3 px-3">
                <div class="col-12 d-flex justify-content-center">
                    <?= paginateLinksSecondary($base_url . "/con-work-orders", $currentPage, $totalPages); ?>
                </div>
            </div>
        </div>
    </div>
</div>
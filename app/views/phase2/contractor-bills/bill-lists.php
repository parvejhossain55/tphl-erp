<?php
$base_url   = $GLOBALS['base_url'];
$page_title = $page_title ?? 'Contractor Bill Requests';
?>
<div class="container-fluid">
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h4 class="page-title fw-semibold fs-18 mb-0"><?= $page_title ?></h4>
            <div class="d-flex">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item active">Contractor Bills</li>
                </ol>
            </div>
        </div>
        <div class="my-auto">
            <a href="<?= $base_url ?>/con-contractor-bill-create" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> New Bill
            </a>
        </div>
    </div>

    <div class="card custom-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0"><i class="bi bi-receipt me-2 text-primary"></i> All Contractor Bills</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="billTable" class="table table-bordered table-hover text-nowrap">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Project</th>
                            <th>Contractor</th>
                            <th>Bill Type</th>
                            <th>Bill Date</th>
                            <th class="text-end">Amount (৳)</th>
                            <th>Status</th>
                            <th>Submitted By</th>
                            <th>Approved By</th>
                            <th>Payment By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($bills)): ?>
                            <?php foreach ($bills as $i => $b): ?>
                                <?php
                                $statusMap = [
                                    'draft'          => 'warning',
                                    'submitted'      => 'info',
                                    'verified'       => 'primary',
                                    'qc_approved'    => 'secondary',
                                    'approved'       => 'success',
                                    'rejected'       => 'danger',
                                    'paid'           => 'secondary',
                                    'partially_paid' => 'info',
                                    'fully_paid'     => 'secondary',
                                ];
                                $badge = $statusMap[$b['status']] ?? 'secondary';
                                $statusLabel = str_replace('_', ' ', ucfirst($b['status']));
                                ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($b['project_name'] ?? '-') ?></td>
                                    <td>
                                        <span class="fw-semibold"><?= htmlspecialchars($b['operator_name'] ?? '-') ?></span><br>
                                        <small class="text-muted"><?= htmlspecialchars($b['operator_role'] ?? '') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= ($b['bill_type'] === 'Advance') ? 'warning' : (($b['bill_type'] === 'Retention') ? 'info' : 'primary') ?>-transparent">
                                            <?= htmlspecialchars($b['bill_type'] ?? 'Progress') ?>
                                        </span>
                                    </td>
                                    <td><?= date('d M Y', strtotime($b['bill_date'])) ?></td>
                                    <td class="fw-semibold text-end">৳ <?= number_format($b['bill_amount'], 2) ?></td>
                                    <td><span class="badge bg-<?= $badge ?>"><?= $statusLabel ?></span></td>
                                    <td><?= htmlspecialchars($b['created_by_name'] ?? '-') ?></td>
                                    <td>
                                        <?php if (!empty($b['approved_by_name'])): ?>
                                            <span class="text-success fw-semibold"><?= htmlspecialchars($b['approved_by_name']) ?></span>
                                            <?php if (!empty($b['approved_by_role'])): ?>
                                                <br><small class="text-muted fst-italic"><?= htmlspecialchars($b['approved_by_role']) ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($b['payment_by_name'])): ?>
                                            <span class="text-primary fw-semibold"><?= htmlspecialchars($b['payment_by_name']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= $base_url ?>/con-contractor-bill/<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="10" class="text-center text-muted py-4">No contractor bills found. <a href="<?= $base_url ?>/con-contractor-bill-create">Create one</a></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    $('#billTable').DataTable({ responsive: true, pageLength: 25, order: [[3, 'desc']] });
});
</script>

<?php
$base_url   = $GLOBALS['base_url'];
$page_title = $page_title ?? 'Contractor Bill Requests';
?>
<div class="container-fluid">
    <!-- Breadcrumb -->
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

    <!-- Card -->
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
                            <th>Bill Date</th>
                            <th>Amount (৳)</th>
                            <th>Status</th>
                            <th>Submitted By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($bills)): ?>
                            <?php foreach ($bills as $i => $b): ?>
                                <?php
                                $statusMap = [
                                    'submitted' => 'primary',
                                    'verified'  => 'info',
                                    'approved'  => 'success',
                                    'paid'      => 'dark',
                                    'rejected'  => 'danger',
                                ];
                                $badge = $statusMap[$b['status']] ?? 'secondary';
                                ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($b['project_name'] ?? '-') ?></td>
                                    <td>
                                        <span class="fw-semibold"><?= htmlspecialchars($b['operator_name'] ?? '-') ?></span><br>
                                        <small class="text-muted"><?= htmlspecialchars($b['operator_role'] ?? '') ?></small>
                                    </td>
                                    <td><?= date('d M Y', strtotime($b['bill_date'])) ?></td>
                                    <td class="fw-semibold text-end">৳ <?= number_format($b['bill_amount'], 2) ?></td>
                                    <td><span class="badge bg-<?= $badge ?>"><?= ucfirst($b['status']) ?></span></td>
                                    <td><?= htmlspecialchars($b['created_by_name'] ?? '-') ?></td>
                                    <td>
                                        <a href="<?= $base_url ?>/con-contractor-bill/<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">No contractor bills found. <a href="<?= $base_url ?>/con-contractor-bill-create">Create one</a></td></tr>
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

<?php
$base_url   = $GLOBALS['base_url'];
$page_title = $page_title ?? 'Pending Payments';
?>
<div class="container-fluid">
    <!-- Breadcrumb -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h4 class="page-title fw-semibold fs-18 mb-0"><?= $page_title ?></h4>
            <div class="d-flex">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-transactions">Transactions</a></li>
                    <li class="breadcrumb-item active">Pending Payments</li>
                </ol>
            </div>
        </div>
        <div class="my-auto">
            <span class="badge bg-warning fs-6 px-3 py-2">
                <i class="bi bi-clock me-1"></i> <?= count($pending ?? []) ?> Pending
            </span>
        </div>
    </div>

    <?php if (empty($pending)): ?>
    <div class="card custom-card">
        <div class="card-body text-center py-5">
            <i class="bi bi-check-circle-fill text-success fs-1 mb-3 d-block"></i>
            <h5 class="text-muted">No pending payments!</h5>
            <p class="text-muted small">All approved requests have been paid.</p>
            <a href="<?= $base_url ?>/con-transactions" class="btn btn-primary btn-sm">View All Transactions</a>
        </div>
    </div>
    <?php else: ?>
    <div class="card custom-card">
        <div class="card-header">
            <h6 class="card-title mb-0"><i class="bi bi-hourglass-split me-2 text-warning"></i> Approved — Awaiting Payment</h6>
        </div>
        <div class="card-body">
            <div class="alert alert-info border-0 py-2 mb-3 small">
                <i class="bi bi-info-circle me-1"></i>
                These records have been approved and are ready for payment processing by the Accounts team.
            </div>
            <div class="table-responsive">
                <table id="pendingTable" class="table table-bordered table-hover text-nowrap">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Type</th>
                            <th>Project</th>
                            <th>Description</th>
                            <th>Amount (৳)</th>
                            <th>Approved On</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending as $i => $p): ?>
                            <?php
                            $refBadge = $p['ref_type'] === 'contractor_bill' ? 'primary' : 'success';
                            $refLabel = $p['ref_type'] === 'contractor_bill' ? 'Contractor Bill' : 'Fund Request';
                            ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><span class="badge bg-<?= $refBadge ?>"><?= $refLabel ?></span></td>
                                <td><?= htmlspecialchars($p['project_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars(mb_strimwidth($p['description'], 0, 60, '...')) ?></td>
                                <td class="text-end fw-semibold text-success">৳ <?= number_format($p['amount'], 2) ?></td>
                                <td><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                                <td>
                                    <a href="<?= $base_url ?>/con-process-payment/<?= $p['ref_type'] ?>/<?= $p['ref_id'] ?>"
                                       class="btn btn-success btn-sm">
                                        <i class="bi bi-credit-card me-1"></i> Process Payment
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
</div>

<script>
$(document).ready(function () {
    $('#pendingTable').DataTable({ responsive: true, pageLength: 25 });
});
</script>

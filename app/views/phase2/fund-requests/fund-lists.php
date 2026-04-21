<?php
$base_url   = $GLOBALS['base_url'];
$page_title = $page_title ?? 'Operational Fund Requests';
?>
<div class="container-fluid">
    <!-- Breadcrumb -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h4 class="page-title fw-semibold fs-18 mb-0"><?= $page_title ?></h4>
            <div class="d-flex">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item active">Fund Requests</li>
                </ol>
            </div>
        </div>
        <div class="my-auto">
            <a href="<?= $base_url ?>/con-fund-request-create" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> New Request
            </a>
        </div>
    </div>

    <div class="card custom-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0"><i class="bi bi-cash-coin me-2 text-success"></i> All Fund Requests</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="fundTable" class="table table-bordered table-hover text-nowrap">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Project</th>
                            <th>Purpose</th>
                            <th>Amount (৳)</th>
                            <th>Fund Date</th>
                            <th>Status</th>
                            <th>Requested By</th>
                            <th>Approved By</th>
                            <th>Payment By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($requests)): ?>
                            <?php foreach ($requests as $i => $r): ?>
                                <?php
                                $statusMap = [
                                    'pending'        => 'warning',
                                    'approved'       => 'success',
                                    'rejected'       => 'danger',
                                    'paid'           => 'secondary',
                                    'partially_paid' => 'info',
                                    'fully_paid'     => 'secondary',
                                ];
                                $badge = $statusMap[$r['status']] ?? 'secondary';
                                $statusLabel = ucfirst(str_replace('_', ' ', $r['status']));
                                ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($r['project_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars(mb_strimwidth($r['purpose'] ?? '-', 0, 50, '...')) ?></td>
                                    <td class="text-end fw-semibold">৳ <?= number_format($r['amount'], 2) ?></td>
                                    <td><?= date('d M Y', strtotime($r['fund_date'])) ?></td>
                                    <td><span class="badge bg-<?= $badge ?>"><?= $statusLabel ?></span></td>
                                    <td><?= htmlspecialchars($r['requester_name'] ?? '-') ?></td>
                                    <td>
                                        <?php if (!empty($r['approved_by_name'])): ?>
                                            <span class="text-success fw-semibold"><?= htmlspecialchars($r['approved_by_name']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($r['payment_by_name'])): ?>
                                            <span class="text-primary fw-semibold"><?= htmlspecialchars($r['payment_by_name']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= $base_url ?>/con-fund-request/<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="10" class="text-center text-muted py-4">No fund requests found. <a href="<?= $base_url ?>/con-fund-request-create">Create one</a></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    $('#fundTable').DataTable({ responsive: true, pageLength: 25, order: [[4, 'desc']] });
});
</script>

<?php
$base_url = $GLOBALS['base_url'];
$refunds  = $refunds ?? [];

$statusMap = [
    'pending'  => ['label' => 'Pending',  'color' => 'warning'],
    'approved' => ['label' => 'Approved', 'color' => 'success'],
    'rejected' => ['label' => 'Rejected', 'color' => 'danger'],
    'refunded' => ['label' => 'Refunded', 'color' => 'secondary'],
];
?>

<!-- Header -->
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div class="my-auto">
        <h4 class="page-title fw-semibold fs-18 mb-0">Client Refund Requests</h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/accounts-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Client Refunds</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container-fluid">
    <div class="card custom-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0"><i class="bi bi-arrow-counterclockwise me-2"></i>All Refund Requests</h6>
        </div>
        <div class="card-body">
            <?php if (empty($refunds)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                    <p class="text-muted">No refund requests found.</p>
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm mb-0" id="refundTable">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Client</th>
                            <th>Project</th>
                            <th>Flat</th>
                            <th>Total Paid (৳)</th>
                            <th>Retention (৳)</th>
                            <th>Refund (৳)</th>
                            <th>Status</th>
                            <th>Requested By</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($refunds as $idx => $r):
                            $sInfo = $statusMap[$r['status']] ?? ['label' => ucfirst($r['status']), 'color' => 'secondary'];
                        ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td>
                                <strong><?= htmlspecialchars($r['client_name'] ?? '—') ?></strong>
                                <?php if (!empty($r['client_phone'])): ?>
                                    <br><small class="text-muted"><?= $r['client_phone'] ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($r['project_name'] ?? '—') ?></td>
                            <td>
                                <?= htmlspecialchars($r['flat_name'] ?? '—') ?>
                                <?php if (!empty($r['floor_no'])): ?>
                                    <br><small class="text-muted">Floor <?= $r['floor_no'] ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">৳ <?= number_format($r['total_paid'], 2) ?></td>
                            <td class="text-end text-danger">৳ <?= number_format($r['retention_amount'], 2) ?></td>
                            <td class="text-end fw-bold text-success">৳ <?= number_format($r['refund_amount'], 2) ?></td>
                            <td><span class="badge bg-<?= $sInfo['color'] ?>"><?= $sInfo['label'] ?></span></td>
                            <td><?= htmlspecialchars($r['requested_by_name'] ?? '—') ?></td>
                            <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                            <td>
                                <a href="<?= $base_url ?>/client-refund/<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

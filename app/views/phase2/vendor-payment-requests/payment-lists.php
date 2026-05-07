<?php
$base_url   = $GLOBALS['base_url'];
$page_title = $page_title ?? 'Vendor Payment Requests';
?>
<div class="container-fluid">
    <!-- Breadcrumb -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h3 class="page-title fw-semibold fs-18 mb-3"><?= $page_title ?></h4>
            <div class="d-flex">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item active">Vendor Payment Requests</li>
                </ol>
            </div>
        </div>
        <div class="my-auto">
            <a href="<?= $base_url ?>/con-vendor-payment-create" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> New Request
            </a>
        </div>
    </div>

    <div class="card custom-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0"><i class="bi bi-card-list me-2 text-primary"></i> Vendor Payment List</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="vprTable" class="table table-bordered table-hover text-nowrap">
                    <thead class="table-dark">
                        <tr>
                            <th>Req ID</th>
                            <th>Type</th>
                            <th>Created</th>
                            <th>Project</th>
                            <th>Vendor</th>
                            <th class="text-end">Invoice (৳)</th>
                            <th class="text-end">Net Payable</th>
                            <th class="text-end">Paid (৳)</th>
                            <th class="text-end">Due (৳)</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Approved By</th>
                            <th>Payment By</th>
                            <th class="text-center">Attachment</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($requests)): ?>
                            <?php foreach ($requests as $req): ?>
                                <?php
                                $pt = $req['payment_type'] ?? 'Final';
                                $ptClass = $pt === 'Advance' ? 'bg-primary' : ($pt === 'Partial' ? 'bg-warning text-dark' : 'bg-success');

                                $statusMap = [
                                    'Draft'          => 'warning',
                                    'Submitted'      => 'info',
                                    'Approved'       => 'success',
                                    'Rejected'       => 'danger',
                                    'Paid'           => 'secondary',
                                    'Partially_Paid' => 'info',
                                    'Fully_Paid'     => 'secondary',
                                ];
                                $badge = $statusMap[$req['status'] ?? ''] ?? 'secondary';
                                $statusLabel = str_replace('_', ' ', $req['status'] ?? 'Draft');
                                ?>
                                <tr>
                                    <td class="fw-bold">VPR-<?= $req['id'] ?></td>
                                    <td><span class="badge <?= $ptClass ?>"><?= $pt ?></span></td>
                                    <td><?= !empty($req['created_at']) ? date('d M Y', strtotime($req['created_at'])) : '—' ?></td>
                                    <td><?= htmlspecialchars($req['project_name'] ?? '—') ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($req['vendor_name'] ?? '—') ?></span></td>
                                    <td class="text-end"><?= number_format((float)($req['invoice_amount'] ?? 0), 2) ?></td>
                                    <td class="text-end fw-bold"><?= number_format((float)($req['net_payable'] ?? 0), 2) ?></td>
                                    <?php
                                        $alreadyPaid = (float)($req['already_paid'] ?? 0);
                                        $netPay      = (float)($req['net_payable'] ?? 0);
                                        $dueAmt      = $req['due_amount'] !== null
                                                        ? (float)$req['due_amount']
                                                        : max(0, $netPay - $alreadyPaid);
                                    ?>
                                    <td class="text-end fw-semibold <?= $alreadyPaid > 0 ? 'text-info' : 'text-muted' ?>">
                                        <?= $alreadyPaid > 0 ? '৳ ' . number_format($alreadyPaid, 2) : '—' ?>
                                    </td>
                                    <td class="text-end fw-bold <?= $dueAmt > 0 ? 'text-danger' : ($alreadyPaid > 0 ? 'text-success' : 'text-muted') ?>">
                                        <?php if ($alreadyPaid <= 0): ?>
                                            —
                                        <?php elseif ($dueAmt <= 0): ?>
                                            <i class="bi bi-check-circle-fill text-success"></i>
                                        <?php else: ?>
                                            ৳ <?= number_format($dueAmt, 2) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-<?= $badge ?>"><?= $statusLabel ?></span></td>
                                    <td><small><?= htmlspecialchars($req['created_by_name'] ?? '') ?></small></td>
                                    <td>
                                        <?php if (!empty($req['approved_by_name'])): ?>
                                            <span class="text-success fw-semibold"><?= htmlspecialchars($req['approved_by_name']) ?></span>
                                            <?php if (!empty($req['approved_by_role'])): ?>
                                                <br><small class="text-muted fst-italic"><?= htmlspecialchars($req['approved_by_role']) ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($req['payment_by_name'])): ?>
                                            <span class="text-primary fw-semibold"><?= htmlspecialchars($req['payment_by_name']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($req['payment_attachment'])): ?>
                                            <a href="<?= $base_url ?>/<?= htmlspecialchars($req['payment_attachment']) ?>" target="_blank"
                                               class="btn btn-sm btn-outline-secondary" title="View Attachment">
                                                <i class="bi bi-paperclip"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= $base_url ?>/con-vendor-payment-show/<?= $req['id'] ?>" class="btn btn-sm btn-outline-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="13" class="text-center text-muted py-4">No vendor payment requests found. <a href="<?= $base_url ?>/con-vendor-payment-create">Create one</a></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    $('#vprTable').DataTable({ responsive: true, pageLength: 25, order: [[2, 'desc']] });
});
</script>

<?php
$base_url   = $GLOBALS['base_url'];
$page_title = $page_title ?? 'Vendor Payment Requests';
$canProcessPayment = $canProcessPayment ?? false;
$canApprove = $canApprove ?? false;
?>
<div class="container-fluid">
    <!-- Breadcrumb -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h3 class="page-title fw-semibold fs-18 mb-1"><?= $page_title ?></h3>
            <div class="d-flex">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item active">Vendor Payment Requests</li>
                </ol>
            </div>
        </div>
        <div class="my-auto">
            <a href="<?= $base_url ?>/con-vendor-payment-create" class="btn btn-primary btn-sm shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> New Request
            </a>
        </div>
    </div>

    <!-- Vendor Payment Requests List -->
    <div class="card custom-card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-light">
            <div>
                <h6 class="card-title mb-0 fw-bold text-dark">
                    <i class="bi bi-card-list me-2 text-primary"></i> Vendor Payment Requests & Disbursal List
                </h6>
                <small class="text-muted">Track payment request approval workflow, management approvals, and accounts disbursement records.</small>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="vprTable" class="table table-bordered table-hover align-middle text-nowrap">
                    <thead class="table-dark">
                        <tr>
                            <th>Req ID</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Project & PO</th>
                            <th>Vendor</th>
                            <th class="text-end">Invoice (৳)</th>
                            <th class="text-end">Net Payable (৳)</th>
                            <th class="text-end">Paid (৳)</th>
                            <th class="text-end">Due (৳)</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Approved By</th>
                            <th>Payment By (Accounts)</th>
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
                                    'Draft'          => 'warning text-dark',
                                    'Submitted'      => 'info text-white',
                                    'QC Approved'    => 'primary',
                                    'Approved'       => 'success',
                                    'Rejected'       => 'danger',
                                    'Paid'           => 'secondary',
                                    'Partially_Paid' => 'info text-white',
                                    'Fully_Paid'     => 'success',
                                ];
                                $badge = $statusMap[$req['status'] ?? ''] ?? 'secondary';
                                $statusLabel = str_replace('_', ' ', $req['status'] ?? 'Draft');

                                $alreadyPaid = (float)($req['already_paid'] ?? 0);
                                $netPay      = (float)($req['net_payable'] ?? 0);
                                $dueAmt      = $req['due_amount'] !== null
                                                ? (float)$req['due_amount']
                                                : max(0, $netPay - $alreadyPaid);
                                $canPayThis  = in_array($req['status'], ['Approved', 'Partially_Paid']) && $dueAmt > 0;
                                ?>
                                <tr>
                                    <td class="fw-bold">
                                        <a href="<?= $base_url ?>/con-vendor-payment-show/<?= $req['id'] ?>" class="text-primary">
                                            VPR-<?= $req['id'] ?>
                                        </a>
                                    </td>
                                    <td><span class="badge <?= $ptClass ?>"><?= $pt ?></span></td>
                                    <td><?= !empty($req['created_at']) ? date('d M Y', strtotime($req['created_at'])) : '—' ?></td>
                                    <td>
                                        <span class="fw-semibold"><?= htmlspecialchars($req['project_name'] ?? '—') ?></span>
                                        <?php if (!empty($req['purchase_order_id'])): ?>
                                            <br>
                                            <a href="<?= $base_url ?>/con-purchase-order/<?= $req['purchase_order_id'] ?>" class="badge bg-light text-primary border" style="font-size: 10.5px;">
                                                <i class="bi bi-box-seam me-1"></i>PO-<?= $req['purchase_order_id'] ?>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($req['vendor_name'] ?? '—') ?></span></td>
                                    <td class="text-end">৳ <?= number_format((float)($req['invoice_amount'] ?? 0), 2) ?></td>
                                    <td class="text-end fw-bold">৳ <?= number_format((float)($req['net_payable'] ?? 0), 2) ?></td>
                                    <td class="text-end fw-bold <?= $alreadyPaid > 0 ? 'text-success' : 'text-muted' ?>">
                                        <?= $alreadyPaid > 0 ? '৳ ' . number_format($alreadyPaid, 2) : '—' ?>
                                    </td>
                                    <td class="text-end fw-bold <?= $dueAmt > 0 ? 'text-danger' : ($alreadyPaid > 0 ? 'text-success' : 'text-muted') ?>">
                                        <?php if ($alreadyPaid <= 0 && $dueAmt <= 0): ?>
                                            —
                                        <?php elseif ($dueAmt <= 0 && $alreadyPaid > 0): ?>
                                            <span class="text-success"><i class="bi bi-check-circle-fill"></i> Paid</span>
                                        <?php else: ?>
                                            ৳ <?= number_format($dueAmt, 2) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $badge ?>"><?= $statusLabel ?></span>
                                    </td>
                                    <td><small><?= htmlspecialchars($req['created_by_name'] ?? '—') ?></small></td>
                                    <td>
                                        <div class="small" style="font-size: 11px; line-height: 1.35;">
                                            <div>
                                                <strong>QC:</strong> 
                                                <?php if (!empty($req['qc_approved_by_name'])): ?>
                                                    <span class="text-success fw-semibold"><?= htmlspecialchars($req['qc_approved_by_name']) ?></span>
                                                    <?php if (isset($req['qc_approved_amount']) && (float)$req['qc_approved_amount'] > 0): ?>
                                                        <br><span class="badge bg-info text-white" style="font-size:9.5px">৳ <?= number_format((float)$req['qc_approved_amount'], 2) ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($req['qc_approved_at'])): ?>
                                                        <br><small class="text-muted" style="font-size:9.5px"><?= date('d M Y', strtotime($req['qc_approved_at'])) ?></small>
                                                    <?php endif; ?>
                                                <?php elseif ($req['status'] === 'Submitted'): ?>
                                                    <span class="badge bg-warning text-dark" style="font-size:9.5px">Pending QC</span>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="mt-1">
                                                <strong>Mgmt:</strong>
                                                <?php if (!empty($req['approved_by_name'])): ?>
                                                    <span class="text-success fw-semibold"><?= htmlspecialchars($req['approved_by_name']) ?></span>
                                                    <?php if (!empty($req['approved_by_role'])): ?>
                                                        <br><small class="text-muted fst-italic" style="font-size:9.5px"><?= htmlspecialchars($req['approved_by_role']) ?></small>
                                                    <?php endif; ?>
                                                <?php elseif (in_array($req['status'], ['Submitted', 'QC Approved'])): ?>
                                                    <span class="badge bg-info text-white" style="font-size:9.5px">Pending Mgmt</span>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($req['payment_by_name'])): ?>
                                            <span class="text-primary fw-semibold" style="font-size: 11.5px;"><?= htmlspecialchars($req['payment_by_name']) ?></span>
                                            <br><small class="text-success fw-bold" style="font-size: 10.5px">Paid: ৳ <?= number_format($alreadyPaid, 2) ?></small>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
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
                                        <div class="d-flex gap-1 justify-content-center">
                                            <?php if ($canPayThis && $canProcessPayment): ?>
                                                <a href="<?= $base_url ?>/con-vendor-payment-show/<?= $req['id'] ?>#disburse" 
                                                   class="btn btn-sm btn-success" title="Disburse Payment">
                                                    <i class="bi bi-wallet2 me-1"></i> Pay
                                                </a>
                                            <?php endif; ?>
                                            <a href="<?= $base_url ?>/con-vendor-payment-show/<?= $req['id'] ?>" 
                                               class="btn btn-sm btn-outline-primary" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="15" class="text-center text-muted py-4">No vendor payment requests found. <a href="<?= $base_url ?>/con-vendor-payment-create">Create one</a></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    $('#vprTable').DataTable({ responsive: true, pageLength: 25, order: [] });
});
</script>

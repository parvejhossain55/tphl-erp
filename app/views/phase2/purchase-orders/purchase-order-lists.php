<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Purchase Orders</li>
            </ol>
        </nav>
    </div>
    <div class="mb-4 d-flex gap-2 align-items-center">
        <?php if (!in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 15, 20, 22])): ?>
        <a href="<?= $base_url ?>/con-material-receiving-import"
           class="btn btn-success btn-sm fw-bold">
            <i class="bi bi-cloud-upload me-1"></i> Bulk CSV Import
        </a>
        <?php endif; ?>
    </div>
</div>

<style>
.payment-requested-row > td,
.payment-requested-row {
    background-color: #eef9f2 !important;
}
.payment-requested-row:hover > td,
.payment-requested-row:hover {
    background-color: #dff2e6 !important;
}
</style>

    <div class="card__wrapper shadow-sm">
        <div class="card__header d-flex justify-content-between align-items-center bg-white p-3 border-bottom">
            <h6 class="mb-0 d-flex align-items-center flex-wrap gap-2">
                <span><i class="fa-solid fa-filter me-2 text-primary"></i>Filter Orders</span>
                <span class="badge bg-success bg-opacity-10  border border-success border-opacity-25 fw-semibold" style="font-size:11px;">
                    <i class="fa-solid fa-square me-1" style="color:#28a745"></i> Light Green = Payment Requested
                </span>
            </h6>
            <form method="GET" class="d-flex gap-2 mb-0 flex-wrap">
                <select name="project_id" class="form-select form-select-sm fw-semibold" onchange="this.form.submit()" style="width: 220px; border-color: #dee2e6;">
                    <option value="">All Projects</option>
                    <?php if (!empty($projects)): ?>
                        <?php foreach ($projects as $proj): ?>
                            <option value="<?= $proj['id'] ?>" <?= (string)($currentProjectId ?? '') === (string)$proj['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($proj['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <select name="payment_filter" class="form-select form-select-sm fw-semibold" onchange="this.form.submit()" style="width: 210px; border-color: #dee2e6;">
                    <option value="all" <?= ($paymentFilter ?? 'all') === 'all' ? 'selected' : '' ?>>All Payment Status</option>
                    <option value="unrequested" <?= ($paymentFilter ?? '') === 'unrequested' ? 'selected' : '' ?>>Payment Not Requested</option>
                    <option value="requested" <?= ($paymentFilter ?? '') === 'requested' ? 'selected' : '' ?>>Payment Requested</option>
                    <option value="paid" <?= ($paymentFilter ?? '') === 'paid' ? 'selected' : '' ?>>Payment Completed</option>
                </select>
                <select name="filter" class="form-select form-select-sm fw-semibold" onchange="this.form.submit()" style="width: 180px; border-color: #dee2e6;">
                    <option value="pending_my_approval" <?= ($currentFilter ?? '') === 'pending_my_approval' ? 'selected' : '' ?>>Pending My Approval</option>
                    <option value="my_approved" <?= ($currentFilter ?? '') === 'my_approved' ? 'selected' : '' ?>>Approved By Me</option>
                    <option value="all" <?= ($currentFilter ?? '') === 'all' ? 'selected' : '' ?>>All Orders</option>
                </select>
            </form>
        </div>
        <div class="card__body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped custom-table mb-0 multiple-table">
                    <thead class="table-light">
                        <tr>
                            <th>PO #</th>
                            <th>Project</th>
                            <th class="text-end">Total Amount</th>
                            <th>Status</th>
                            <th class="text-center">Approvals</th>
                            <th class="text-center">Vendor Payment</th>
                            <th>Order Date</th>
                            <th>Created By</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($orders)): ?>
                            <?php foreach ($orders as $po): ?>
                            <?php $hasPaymentReq = !empty($po['vpr_id']); ?>
                            <tr class="<?= $hasPaymentReq ? 'payment-requested-row' : '' ?>">
                                <td><span class="fw-bold text-primary">PO-<?= $po['id'] ?></span></td>
                                <td class="fw-bold">
                                    <?= htmlspecialchars($po['project_name']) ?>
                                    <?php if (!empty($po['remarks'])): ?>
                                        <div class="text-muted small fw-normal mt-1" style="font-size:11px" title="Remarks">
                                            <i class="bi bi-chat-left-text me-1 text-primary"></i><?= htmlspecialchars($po['remarks']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($po['latest_note_author'])): ?>
                                        <div class="small fw-normal mt-1 p-1 rounded" style="font-size:10px; background:rgba(139,92,246,.08); color:#6d28d9; border: 1px solid rgba(139,92,246,.2);" title="<?= htmlspecialchars($po['latest_note_text']) ?>">
                                            <i class="fal fa-comment-dots me-1"></i><strong><?= htmlspecialchars($po['latest_note_author']) ?>:</strong> <?= htmlspecialchars(mb_strimwidth($po['latest_note_text'], 0, 30, '...')) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">৳ <?= number_format($po['total_amount'] ?? 0, 2) ?></td>
                                <td>
                                     <?php
                                     $badge = match($po['status']) {
                                         'Issued'             => 'bg-info',
                                         'Purchase Head Approved' => 'bg-warning text-dark',
                                         'QC Approved'        => 'bg-primary',
                                         'Approved'           => 'bg-success',
                                         'Partially Received' => 'bg-warning text-dark',
                                         'Fully Received'     => 'bg-success',
                                         'Cancelled'          => 'bg-danger',
                                         default              => 'bg-secondary',
                                     };
                                     ?>
                                     <span class="badge <?= $badge ?>"><?= $po['status'] ?></span>
                                     <?php
                                     $creatorRole = (int)($po['creator_role_id'] ?? 0);
                                     $phNeeded = in_array($creatorRole, [23, 31]);
                                     
                                     if ($po['status'] === 'Issued'): ?>
                                         <div class="mt-1 text-danger" style="font-size: 11px; font-weight: 600;">
                                             <i class="bi bi-hourglass-split"></i> Pending: <?= $phNeeded ? 'Purchase Head' : 'QC & Internal Audit' ?>
                                         </div>
                                     <?php elseif ($po['status'] === 'Purchase Head Approved'): ?>
                                         <div class="mt-1 text-danger" style="font-size: 11px; font-weight: 600;">
                                             <i class="bi bi-hourglass-split"></i> Pending: QC & Internal Audit
                                         </div>
                                     <?php elseif ($po['status'] === 'QC Approved'): ?>
                                         <div class="mt-1 text-danger" style="font-size: 11px; font-weight: 600;">
                                             <i class="bi bi-hourglass-split"></i> Pending: Operation Head
                                         </div>
                                     <?php endif; ?>
                                </td>
                                <td class="text-center">
                                     <?php
                                     $phOk = !empty($po['purchase_head_approved_by_id']);
                                     $qcOk = !empty($po['qc_approved_by_id']);
                                     $opOk = !empty($po['op_head_approved_by_id']);
                                     $creatorRole = (int)($po['creator_role_id'] ?? 0);
                                     $phNeeded = in_array($creatorRole, [23, 31]);
                                     $totalSteps = $phNeeded ? 3 : 2;
                                     $doneSteps = ($phNeeded && $phOk ? 1 : 0) + ($qcOk ? 1 : 0) + ($opOk ? 1 : 0);
                                     $apvColor = ($doneSteps == $totalSteps) ? '#059669' : ($doneSteps > 0 ? '#2563eb' : '#6b7280');
                                     ?>
                                     <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;
                                        background:rgba(0,0,0,.05);color:<?= $apvColor ?>">
                                        <?= $doneSteps ?>/<?= $totalSteps ?>
                                        <?= ($phNeeded && $phOk)?'<span title="Purchase Head">PH✓</span>':'' ?>
                                        <?= $qcOk?'<span title="QC & Internal Audit">QC✓</span>':'' ?>
                                        <?= $opOk?'<span title="Operation Head">OP✓</span>':'' ?>
                                     </span>
                                </td>
                                <td class="text-center" style="min-width: 170px;">
                                    <?php
                                    $hasVpr = !empty($po['vpr_id']);
                                    if (!$hasVpr):
                                        if (in_array($po['status'], ['Approved', 'Partially Received', 'Fully Received'])): ?>
                                            <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-50" style="font-size:11px; font-weight:600;">
                                                <i class="fa-solid fa-clock me-1 text-warning"></i>Not Requested
                                            </span>
                                            <div class="mt-1">
                                                <a href="<?= $base_url ?>/con-vendor-payment-create?po_id=<?= $po['id'] ?>&project_id=<?= $po['project_id'] ?? '' ?>&vendor_id=<?= $po['vendor_id'] ?? '' ?>" 
                                                   class="btn btn-xs btn-outline-primary" style="font-size:10px; padding:1px 8px;">
                                                    <i class="fa-solid fa-plus me-1"></i>Request Pay
                                                </a>
                                            </div>
                                        <?php elseif ($po['status'] === 'Cancelled'): ?>
                                            <span class="text-muted small">—</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border" style="font-size:10.5px;">
                                                <i class="fa-solid fa-hourglass-start me-1"></i>PO Pending Approval
                                            </span>
                                        <?php endif; ?>
                                    <?php else:
                                        $vprStatus = $po['vpr_status'] ?? 'Draft';
                                        $netPayable = (float)($po['vpr_net_payable'] ?? 0);
                                        $totalPaid  = (float)($po['vpr_total_paid'] ?? 0);
                                        $dueAmt     = (float)($po['vpr_due'] ?? 0);
                                        $isPaidFull = in_array($vprStatus, ['Fully_Paid', 'Paid']) || ($totalPaid > 0 && $dueAmt <= 0);

                                        if ($isPaidFull): ?>
                                            <span class="badge bg-success" style="font-size:11px; font-weight:600;">
                                                <i class="fa-solid fa-check-double me-1"></i>Payment Complete
                                            </span>
                                            <div class=" small fw-bold mt-1" style="font-size:10.5px;">
                                                Paid: ৳ <?= number_format($totalPaid, 2) ?>
                                            </div>
                                        <?php elseif ($vprStatus === 'Partially_Paid' || ($totalPaid > 0 && $dueAmt > 0)): ?>
                                            <span class="badge bg-info text-white" style="font-size:11px; font-weight:600;">
                                                <i class="fa-solid fa-coins me-1"></i>Partially Paid
                                            </span>
                                            <div class="mt-1" style="font-size:10.5px;">
                                                <span class=" fw-semibold">Paid: ৳ <?= number_format($totalPaid, 2) ?></span><br>
                                                <span class="text-danger fw-semibold">Due: ৳ <?= number_format($dueAmt, 2) ?></span>
                                            </div>
                                        <?php elseif ($vprStatus === 'Approved'): ?>
                                            <span class="badge bg-primary" style="font-size:11px; font-weight:600;">
                                                <i class="fa-solid fa-check me-1"></i>Approved
                                            </span>
                                            <div class="text-warning small fw-semibold mt-1" style="font-size:10.5px;">
                                                <i class="fa-solid fa-hourglass-half me-1"></i>Pending Accounts Pay
                                            </div>
                                            <div class="text-muted" style="font-size:10px;">
                                                Payable: ৳ <?= number_format($netPayable, 2) ?>
                                            </div>
                                        <?php elseif ($vprStatus === 'QC Approved'): ?>
                                            <span class="badge bg-primary-transparent text-primary border border-primary border-opacity-25" style="font-size:11px; font-weight:600;">
                                                <i class="fa-solid fa-check me-1"></i>QC Approved
                                            </span>
                                            <div class="text-danger small fw-semibold mt-1" style="font-size:10.5px;">
                                                <i class="fa-solid fa-hourglass-half me-1"></i>Pending Mgmt Approval
                                            </div>
                                            <div class="text-muted" style="font-size:10px;">
                                                Payable: ৳ <?= number_format($netPayable, 2) ?>
                                            </div>
                                        <?php elseif ($vprStatus === 'Submitted'): ?>
                                            <span class="badge bg-warning text-dark" style="font-size:11px; font-weight:600;">
                                                <i class="fa-solid fa-paper-plane me-1"></i>Submitted
                                            </span>
                                            <div class="text-danger small fw-semibold mt-1" style="font-size:10.5px;">
                                                <i class="fa-solid fa-hourglass-half me-1"></i>Pending QC Audit
                                            </div>
                                            <div class="text-muted" style="font-size:10px;">
                                                Payable: ৳ <?= number_format($netPayable, 2) ?>
                                            </div>
                                        <?php elseif ($vprStatus === 'Rejected'): ?>
                                            <span class="badge bg-danger" style="font-size:11px; font-weight:600;">
                                                <i class="fa-solid fa-ban me-1"></i>Rejected
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary" style="font-size:11px; font-weight:600;">
                                                <?= htmlspecialchars(str_replace('_', ' ', $vprStatus)) ?>
                                            </span>
                                        <?php endif; ?>
                                        <div class="mt-1">
                                            <a href="<?= $base_url ?>/con-vendor-payment-show/<?= $po['vpr_id'] ?>" 
                                               class="small text-primary text-decoration-none fw-semibold" style="font-size:10.5px;" title="View Vendor Payment Request">
                                                VPR-<?= $po['vpr_id'] ?> <i class="fa-solid fa-arrow-up-right-from-square ms-1" style="font-size:9px;"></i>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small"><?= date('d M Y', strtotime($po['order_date'])) ?></td>
                                <td><?= htmlspecialchars($po['created_by_name'] ?? '—') ?></td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="<?= $base_url ?>/con-purchase-order/<?= $po['id'] ?>"
                                           class="btn btn-sm btn-outline-info" title="View PO">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (!empty($po['vpr_id'])): ?>
                                        <a href="<?= $base_url ?>/con-vendor-payment-show/<?= $po['vpr_id'] ?>"
                                           class="btn btn-sm btn-outline-primary" title="View Vendor Payment Request">
                                            <i class="bi bi-credit-card"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="bi bi-clipboard-x me-2"></i>No purchase orders yet.
                                    POs are auto-generated from approved CST comparisons.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

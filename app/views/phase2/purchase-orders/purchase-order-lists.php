<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-0 fw-semibold"><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Purchase Orders</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2 align-items-center">
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

<!-- Filter Card Wrapper (Matching flat-lists & fund-requisitions) -->
<div class="card__wrapper mb-3">
    <form method="GET" action="<?= $base_url ?>/con-purchase-orders">
        <!-- Row 1: Filter inputs -->
        <div class="row g-2 align-items-center mb-2">
            <!-- Project Filter -->
            <div class="col-xl-3 col-md-3 col-sm-6">
                <select name="project_id" class="form-control form-control-sm select2" onchange="this.form.submit()">
                    <option value="">All Projects</option>
                    <?php if (!empty($projects)): ?>
                        <?php foreach ($projects as $proj): ?>
                            <option value="<?= $proj['id'] ?>" <?= (string)($currentProjectId ?? '') === (string)$proj['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($proj['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Material Category Filter -->
            <div class="col-xl-3 col-md-3 col-sm-6">
                <select name="category_id" id="category_filter" class="form-control form-control-sm select2" data-placeholder="All Material Categories">
                    <option value="">All Material Categories</option>
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= (string)($currentCategoryId ?? '') === (string)$cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Payment Filter -->
            <div class="col-xl-2 col-md-3 col-sm-6">
                <select name="payment_filter" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="all" <?= ($paymentFilter ?? 'all') === 'all' ? 'selected' : '' ?>>All Payment Status</option>
                    <option value="unrequested" <?= ($paymentFilter ?? '') === 'unrequested' ? 'selected' : '' ?>>Payment Not Requested</option>
                    <option value="requested" <?= ($paymentFilter ?? '') === 'requested' ? 'selected' : '' ?>>Payment Requested</option>
                    <option value="paid" <?= ($paymentFilter ?? '') === 'paid' ? 'selected' : '' ?>>Payment Completed</option>
                </select>
            </div>

            <!-- Approval Status Filter -->
            <div class="col-xl-2 col-md-3 col-sm-6">
                <select name="filter" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="pending_my_approval" <?= ($currentFilter ?? '') === 'pending_my_approval' ? 'selected' : '' ?>>Pending My Approval</option>
                    <option value="pending_approval" <?= ($currentFilter ?? '') === 'pending_approval' ? 'selected' : '' ?>>Pending Any Approval</option>
                    <option value="my_approved" <?= ($currentFilter ?? '') === 'my_approved' ? 'selected' : '' ?>>Approved By Me</option>
                    <option value="all" <?= ($currentFilter ?? '') === 'all' ? 'selected' : '' ?>>All Orders</option>
                </select>
            </div>

            <!-- Filter + Reset Buttons -->
            <div class="col-xl-2 col-md-12 col-sm-6">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-secondary btn-sm flex-fill">
                        <i class="fa-solid fa-filter me-1"></i> Filter
                    </button>
                    <a href="<?= $base_url ?>/con-purchase-orders" class="btn btn-success btn-sm flex-fill text-center">
                        <i class="fa-solid fa-rotate-right me-1"></i> Reset
                    </a>
                </div>
            </div>
        </div>

        <!-- Row 2: Status Legend -->
        <div class="row mt-2 align-items-center">
            <div class="col-12">
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <span class="badge bg-success bg-opacity-10  border border-success border-opacity-25 fw-semibold" style="font-size:11px;">
                        <i class="fa-solid fa-square me-1" style="color:#28a745"></i> Light Green = Payment Requested
                    </span>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Table Card Wrapper (Matching flat-lists & fund-requisitions) -->
<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table mb-20" id="purchaseOrdersTable">
                    <thead>
                        <tr class="table__title">
                            <th style="width:40px" class="text-center">#</th>
                            <th>PO #</th>
                            <th>Project</th>
                            <th class="text-end">Total Amount</th>
                            <th>Status</th>
                            <th class="text-center">Approvals</th>
                            <th class="text-center">Vendor Payment</th>
                            <th>Order Date</th>
                            <th>Created By</th>
                            <th class="text-center no-print" style="width:90px">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php if (!empty($orders)): ?>
                            <?php foreach ($orders as $index => $po): ?>
                            <?php $hasPaymentReq = !empty($po['vpr_id']); ?>
                            <tr class="<?= $hasPaymentReq ? 'payment-requested-row' : '' ?>">
                                <td class="text-center fw-bold text-muted"><?= $index + 1 ?></td>
                                <td>
                                    <span class="fw-bold text-primary">PO-<?= $po['id'] ?></span>
                                    <?php if (!empty($po['pr_is_urgent'])): ?>
                                        <div class="mt-1">
                                            <span class="badge bg-danger text-white rounded-pill px-2 py-1 shadow-sm" style="font-size:10px;" title="Urgent Requisition: <?= htmlspecialchars($po['pr_urgent_reason'] ?? 'Urgent requirement') ?>">
                                                <i class="fa-solid fa-bolt-lightning me-1"></i>URGENT
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold">
                                    <?= htmlspecialchars($po['project_name']) ?>
                                    <?php if (!empty($po['category_names'])): ?>
                                        <div class="mt-1" title="Material Category">
                                            <span class="badge bg-light text-dark border fw-normal" style="font-size:10.5px; border-color: #cbd5e1 !important;">
                                                <i class="fa-solid fa-tags me-1 text-primary"></i><?= htmlspecialchars($po['category_names']) ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
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
                                <td class="text-end fw-semibold">৳ <?= number_format($po['total_amount'] ?? 0, 2) ?></td>
                                <td>
                                     <?php
                                     $badge = match($po['status']) {
                                         'Issued'                 => 'bg-info',
                                         'Purchase Head Approved' => 'bg-warning text-dark',
                                         'QC Approved'            => 'bg-primary',
                                         'Approved'               => 'bg-success',
                                         'Partially Received'     => 'bg-warning text-dark',
                                         'Fully Received'         => 'bg-success',
                                         'Cancelled'              => 'bg-danger',
                                         default                  => 'bg-secondary',
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
                                <td class="text-center no-print">
                                    <div class="d-flex justify-content-center gap-1 flex-wrap">
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
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof jQuery !== 'undefined') {
        // Material category select change triggers filter
        $('#category_filter').on('change select2:select select2:clear', function () {
            $(this).closest('form').submit();
        });

        // Initialize DataTable with default 50 entries and 'All' option
        if (typeof jQuery.fn.DataTable !== 'undefined') {
            jQuery('#purchaseOrdersTable').DataTable({
                destroy: true,
                responsive: true,
                pageLength: 50,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                order: [], // Preserve controller's ranking & SQL order
                columnDefs: [
                    { orderable: false, searchable: false, targets: 0 },  // # serial column
                    { orderable: false, searchable: false, targets: -1 } // Actions column
                ],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Quick filter in table...",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    emptyTable: "No purchase orders found.",
                    paginate: {
                        first: '<i class="fa-solid fa-angles-left"></i>',
                        previous: '<i class="fa-solid fa-angle-left"></i>',
                        next: '<i class="fa-solid fa-angle-right"></i>',
                        last: '<i class="fa-solid fa-angles-right"></i>'
                    }
                },
                drawCallback: function(settings) {
                    var api = this.api();
                    var startIndex = api.context[0]._iDisplayStart;
                    api.column(0, {search:'applied', order:'applied'}).nodes().each(function(cell, i) {
                        cell.innerHTML = startIndex + i + 1;
                    });
                }
            });
        }
    }
});
</script>

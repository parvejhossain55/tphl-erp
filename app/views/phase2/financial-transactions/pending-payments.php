<?php
$base_url   = $GLOBALS['base_url'];
$page_title = $page_title ?? 'Pending Payments';
$canProcess = $canProcessPayment ?? false;
$canApproveVendor = $canApprove ?? false;
$userRole   = $userRole ?? null;

// Separate: vendor payments waiting approval vs all others ready for payment
$awaitingApproval = [];
$readyToPay       = [];

$roleNameLower = strtolower($_SESSION['user']['role_name'] ?? '');
$isChiefOfficer = ((int)$userRole === 33 || $roleNameLower === 'chief officer');
$isQc = !$isChiefOfficer && (in_array($userRole, [1, 24, 25]) || strpos($roleNameLower, 'qc') !== false || strpos($roleNameLower, 'audit') !== false);
$isMgmt = !$isChiefOfficer && (in_array($userRole, [1, 3, 22, 20, 32]) || strpos($roleNameLower, 'operation') !== false || strpos($roleNameLower, 'management') !== false || strpos($roleNameLower, 'admin') !== false);

foreach ($pending ?? [] as $p) {
    $refType = $p['ref_type'];
    $status = strtolower($p['vpr_status'] ?? '');
    
    $isAwaiting = false;
    $needsMyApproval = false;

    if ($refType === 'vendor_payment') {
        if (in_array($status, ['submitted', 'qc approved'])) {
            $isAwaiting = true;
            if ($isQc && $status === 'submitted' && !$isMgmt) {
                $needsMyApproval = true;
            } elseif ($isMgmt) {
                $needsMyApproval = true;
            }
        }
    } elseif ($refType === 'contractor_bill') {
        if (in_array($status, ['submitted', 'verified', 'qc_approved'])) {
            $isAwaiting = true;
            if ($isQc && in_array($status, ['submitted', 'verified'])) {
                $needsMyApproval = true;
            } elseif ($isMgmt && in_array($status, ['submitted', 'verified', 'qc_approved'])) {
                $needsMyApproval = true;
            }
        }
    }

    if ($isAwaiting) {
        $p['needs_my_approval'] = $needsMyApproval;
        $awaitingApproval[] = $p;
    } else {
        $readyToPay[] = $p;
    }
}

// Sort awaiting approval: items needing my approval first
usort($awaitingApproval, function($a, $b) {
    return ($b['needs_my_approval'] ? 1 : 0) - ($a['needs_my_approval'] ? 1 : 0);
});
?>

<div class="pagetitle d-flex justify-content-between align-items-center">
    <div class="mb-3">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-3">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-transactions">Transactions</a></li>
                <li class="breadcrumb-item active">Pending Payments</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex align-items-center gap-3">
        <!-- Date Filters -->
        <input type="date" id="fromDate" class="form-control form-control-sm shadow-sm" style="width: auto;" title="From Date">
        <span class="text-muted fw-bold">-</span>
        <input type="date" id="toDate" class="form-control form-control-sm shadow-sm" style="width: auto;" title="To Date">

        <!-- Type Filter -->
        <select id="typeFilter" class="form-select form-select-sm shadow-sm" style="width: auto;">
            <option value="">All Types</option>
            <option value="Vendor Payment">Vendor Payment</option>
            <option value="Contractor Bill">Contractor Bill</option>
            <option value="Fund Request">Fund Request</option>
        </select>
        
        <span class="badge bg-warning fs-6 px-3 py-2 shadow-sm ms-auto">
            <i class="bi bi-clock me-1"></i> <?= count($pending ?? []) ?> Pending
        </span>
    </div>
</div>

<section class="section">

    <?php if (empty($pending)): ?>
    <div class="card__wrapper shadow-sm">
        <div class="card__body text-center py-5 mb-3">
            <i class="bi bi-check-circle-fill text-success fs-1 mb-3 d-block"></i>
            <h5 class="text-muted">No pending payments!</h5>
            <p class="text-muted small">All approved requests have been paid.</p>
            <a href="<?= $base_url ?>/con-transactions" class="btn btn-primary btn-sm">View All Transactions</a>
        </div>
    </div>

    <?php else: ?>

    <!-- ================================================================
         SECTION 1: Vendor Payments Awaiting APPROVAL
    ================================================================ -->
    <?php if (!empty($awaitingApproval)): ?>
    <div class="card__wrapper shadow-sm mb-4 border-top border-warning border-3">
        <div class="mb-2 card__header d-flex justify-content-between align-items-center">
            <h6 class="card__title mb-0 text-warning">
                <i class="bi bi-hourglass-split me-2"></i>
                Requests — Awaiting Approval
                <span class="badge bg-warning text-dark ms-2"><?= count($awaitingApproval) ?></span>
            </h6>
        </div>
        <div class="card__body p-0">
            <div class="alert alert-warning border-0 rounded-0 py-2 mb-0 small">

                এই requests গুলো এখনো approve হয়নি। Approve করতে নিচের <strong>View</strong> button থেকে detail page এ যান।
            </div>
            <div class="table-responsive">
                <table id="awaitingApprovalTable" class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-warning">
                        <tr>
                            <th>#</th>
                            <th>Type</th>
                            <th>Project</th>
                            <th>PO #</th>
                            <th>Description</th>
                            <th class="text-end">Amount (৳)</th>
                            <th>Submitted On</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($awaitingApproval as $i => $p): ?>
                        <?php
                            $refType = $p['ref_type'];
                            $ptClass = ($p['payment_type'] ?? '') === 'Advance'
                                ? 'bg-primary'
                                : (($p['payment_type'] ?? '') === 'Partial' ? 'bg-orange text-dark' : 'bg-success');
                            $badgeColor = $refType === 'contractor_bill' ? 'primary' : 'info';
                            $refLabel = $refType === 'contractor_bill' ? 'Contractor Bill' : 'Vendor Payment';
                            $viewUrl = $refType === 'contractor_bill' ? $base_url . '/con-contractor-bill/' . $p['ref_id'] : $base_url . '/con-vendor-payment-show/' . $p['ref_id'];
                        ?>
                        <tr class="<?= $p['needs_my_approval'] ? 'table-warning shadow-sm position-relative' : '' ?>">
                            <td><?= $i + 1 ?>
                                <?php if ($p['needs_my_approval']): ?>
                                    <span class="position-absolute top-0 start-0 translate-middle p-1 bg-danger border border-light rounded-circle" title="Needs Your Action">
                                        <span class="visually-hidden">New alerts</span>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="type-cell">
                                <span class="badge bg-<?= $badgeColor ?>"><?= $refLabel ?></span><br>
                                <?php if ($refType === 'vendor_payment'): ?>
                                <span class="badge <?= $ptClass ?> mt-1"><?= htmlspecialchars($p['payment_type'] ?? '') ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($p['project_name'] ?? '—') ?></td>
                            <td><?= $refType === 'vendor_payment' && !empty($p['po_number']) ? '<span class="fw-bold text-success">PO-' . htmlspecialchars($p['po_number']) . '</span>' : '—' ?></td>
                            <td><?= htmlspecialchars(mb_strimwidth($p['description'] ?? '', 0, 55, '...')) ?></td>
                            <td class="text-end fw-bold text-warning">৳ <?= number_format((float)($p['amount'] ?? 0), 2) ?></td>
                            <td><small><?= !empty($p['created_at']) ? date('d M Y', strtotime($p['created_at'])) : '—' ?></small></td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="<?= $viewUrl ?>"
                                       class="btn btn-outline-info btn-sm" title="View Details">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    <?php if ($p['needs_my_approval']): ?>
                                    <a href="<?= $viewUrl ?>"
                                       class="btn btn-warning btn-sm fw-bold">
                                        <i class="bi bi-check-circle me-1"></i> Action
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ================================================================
         SECTION 2: All Approved — Ready for Payment Processing
    ================================================================ -->
    <?php if (!empty($readyToPay)): ?>
    <div class="card__wrapper shadow-sm border-top border-success border-3">
        <div class="card__header d-flex justify-content-between align-items-center">
            <h6 class="card__title mb-0 text-success mb-3">
                <i class="bi bi-credit-card me-2"></i>
                Approved — Ready for Payment Processing
                <span class="badge bg-success ms-2"><?= count($readyToPay) ?></span>
            </h6>
        </div>
        <div class="card__body p-0">
            <div class="alert alert-info border-0 rounded-0 py-2 mb-0 small">
                <i class="bi bi-info-circle me-1"></i>
                এই records গুলো approved — Accounts টিম এখন actual payment process করতে পারবে।
            </div>
            <div class="table-responsive">
                <table id="pendingTable" class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Type</th>
                            <th>Project</th>
                            <th>PO #</th>
                            <th>Description</th>
                            <th class="text-end">Payable (৳)</th>
                            <th class="text-end">Paid (৳)</th>
                            <th class="text-end">Due (৳)</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($readyToPay as $i => $p): ?>
                        <?php
                            $refType     = $p['ref_type'] ?? '';
                            $badgeColor  = match($refType) {
                                'contractor_bill'  => 'primary',
                                'operational_fund' => 'success',
                                'vendor_payment'   => 'info',
                                default            => 'secondary',
                            };
                            $refLabel    = match($refType) {
                                'contractor_bill'  => 'Contractor Bill',
                                'operational_fund' => 'Fund Request',
                                'vendor_payment'   => 'Vendor Payment',
                                default            => 'Other',
                            };
                            // Build the correct detail URL based on ref_type
                            $viewUrl = match($refType) {
                                'vendor_payment'   => $base_url . '/con-vendor-payment-show/' . $p['ref_id'],
                                'contractor_bill'  => $base_url . '/con-contractor-bill/' . $p['ref_id'],
                                'operational_fund' => $base_url . '/con-fund-request/' . $p['ref_id'],
                                default            => '#',
                            };
                            $sourceStatus = $p['source_status'] ?? '';
                            $isPartial    = in_array($sourceStatus, ['partially_paid', 'Partially_Paid']);
                            $payable      = (float)($p['amount']       ?? 0);
                            $alreadyPaid  = (float)($p['already_paid'] ?? 0);
                            $dueAmount    = $p['due_amount'] !== null
                                            ? (float)$p['due_amount']
                                            : max(0, $payable - $alreadyPaid);
                        ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td class="type-cell">
                                <span class="badge bg-<?= $badgeColor ?>"><?= $refLabel ?></span>
                                <?php if (!empty($p['payment_type']) && $refType === 'vendor_payment'): ?>
                                <br><span class="badge bg-secondary mt-1"><?= htmlspecialchars($p['payment_type']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($p['project_name'] ?? '—') ?></td>
                            <td><?= $refType === 'vendor_payment' && !empty($p['po_number']) ? '<span class="fw-bold text-success">PO-' . htmlspecialchars($p['po_number']) . '</span>' : '—' ?></td>
                            <td><?= htmlspecialchars(mb_strimwidth($p['description'] ?? '', 0, 50, '...')) ?></td>
                            <td class="text-end fw-semibold">৳ <?= number_format($payable, 2) ?></td>
                            <td class="text-end fw-semibold <?= $alreadyPaid > 0 ? 'text-info' : 'text-muted' ?>">
                                <?= $alreadyPaid > 0 ? '৳ ' . number_format($alreadyPaid, 2) : '—' ?>
                            </td>
                            <td class="text-end fw-bold <?= $dueAmount > 0 ? 'text-warning' : 'text-success' ?>">
                                <?php if ($dueAmount <= 0): ?>
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                <?php else: ?>
                                    ৳ <?= number_format($dueAmount, 2) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($isPartial): ?>
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-pie-chart-fill me-1"></i>Partial
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle me-1"></i>Approved
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><small><?= !empty($p['created_at']) ? date('d M Y', strtotime($p['created_at'])) : '—' ?></small></td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="<?= $viewUrl ?>"
                                       class="btn btn-outline-info btn-sm" title="View Details">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    <?php if ($canProcess): ?>
                                    <?php if ($refType === 'vendor_payment'): ?>
                                    <a href="<?= $base_url ?>/con-vendor-payment-show/<?= $p['ref_id'] ?>"
                                       class="btn btn-<?= $isPartial ? 'warning' : 'success' ?> btn-sm fw-bold">
                                        <i class="bi bi-credit-card me-1"></i>
                                        <?= $isPartial ? 'Continue' : 'Pay' ?>
                                    </a>
                                    <?php else: ?>
                                    <a href="<?= $base_url ?>/con-process-payment/<?= $p['ref_type'] ?>/<?= $p['ref_id'] ?>"
                                       class="btn btn-<?= $isPartial ? 'warning' : 'success' ?> btn-sm fw-bold">
                                        <i class="bi bi-credit-card me-1"></i>
                                        <?= $isPartial ? 'Continue' : 'Process' ?>
                                    </a>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</section>

<script>
$(document).ready(function () {
    let dtPending = null;
    let dtAwaiting = null;

    if ($('#pendingTable').length) {
        if ($.fn.dataTable.isDataTable('#pendingTable')) {
            dtPending = $('#pendingTable').DataTable();
        } else {
            dtPending = $('#pendingTable').DataTable({ responsive: true, pageLength: 25 });
        }
    }
    
    if ($('#awaitingApprovalTable').length) {
        if ($.fn.dataTable.isDataTable('#awaitingApprovalTable')) {
            dtAwaiting = $('#awaitingApprovalTable').DataTable();
        } else {
            dtAwaiting = $('#awaitingApprovalTable').DataTable({ responsive: true, pageLength: 25 });
        }
    }

    // Custom Date Range Search function for DataTables
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        let fromDateStr = $('#fromDate').val();
        let toDateStr = $('#toDate').val();
        
        let dateIndex = (settings.nTable.id === 'awaitingApprovalTable') ? 6 : 9;
        let rowDateStr = data[dateIndex] ? data[dateIndex].replace(/<[^>]+>/g, '').trim() : '';
        
        if (!rowDateStr || rowDateStr === '—') return true;

        let rowDate = new Date(rowDateStr);
        let fromDate = fromDateStr ? new Date(fromDateStr) : null;
        let toDate = toDateStr ? new Date(toDateStr) : null;
        
        if (fromDate) fromDate.setHours(0,0,0,0);
        if (toDate) toDate.setHours(23,59,59,999);
        
        if (fromDate && rowDate < fromDate) return false;
        if (toDate && rowDate > toDate) return false;
        
        return true;
    });
    
    // Trigger redraw on filters change
    $('#typeFilter, #fromDate, #toDate').on('change', function() {
        let filterValue = $('#typeFilter').val();
        
        if (dtAwaiting) {
            dtAwaiting.column(1).search(filterValue).draw();
        }
        
        if (dtPending) {
            dtPending.column(1).search(filterValue).draw();
        }
    });
});
</script>

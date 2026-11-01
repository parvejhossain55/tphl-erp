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

    <!-- Quick Status Filter Pills / KPI Badges -->
    <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
        <a href="<?= $base_url ?>/con-vendor-payment-requests" 
           class="btn btn-sm <?= empty($filters['status']) ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3 fw-semibold">
            All Requests <span class="badge bg-light text-dark ms-1"><?= number_format($filterCounts['total'] ?? 0) ?></span>
        </a>
        <a href="<?= $base_url ?>/con-vendor-payment-requests?status=pending_qc" 
           class="btn btn-sm <?= (($filters['status'] ?? '') === 'pending_qc') ? 'btn-warning text-dark' : 'btn-outline-warning text-dark' ?> rounded-pill px-3 fw-semibold">
            <i class="bi bi-hourglass-split me-1"></i> Pending QC 
            <span class="badge bg-warning text-dark ms-1"><?= number_format($filterCounts['pending_qc'] ?? 0) ?></span>
        </a>
        <a href="<?= $base_url ?>/con-vendor-payment-requests?status=pending_apex" 
           class="btn btn-sm <?= (($filters['status'] ?? '') === 'pending_apex') ? 'btn-info text-white' : 'btn-outline-info' ?> rounded-pill px-3 fw-semibold">
            <i class="bi bi-shield-check me-1"></i> Pending Apex/Mgmt 
            <span class="badge bg-info text-white ms-1"><?= number_format($filterCounts['pending_apex'] ?? 0) ?></span>
        </a>
        <a href="<?= $base_url ?>/con-vendor-payment-requests?status=apex_approved" 
           class="btn btn-sm <?= (in_array(($filters['status'] ?? ''), ['apex_approved', 'approved'])) ? 'btn-success' : 'btn-outline-success' ?> rounded-pill px-3 fw-semibold">
            <i class="bi bi-check-circle me-1"></i> Management Approved 
            <span class="badge bg-success text-white ms-1"><?= number_format($filterCounts['apex_approved'] ?? 0) ?></span>
        </a>
        <a href="<?= $base_url ?>/con-vendor-payment-requests?status=ready_to_pay" 
           class="btn btn-sm <?= (in_array(($filters['status'] ?? ''), ['ready_to_pay', 'partially_paid'])) ? 'btn-primary' : 'btn-outline-primary' ?> rounded-pill px-3 fw-semibold">
            <i class="bi bi-wallet2 me-1"></i> Ready to Pay (Partially Paid) 
            <span class="badge bg-primary text-white ms-1"><?= number_format($filterCounts['ready_to_pay'] ?? 0) ?></span>
        </a>
        <a href="<?= $base_url ?>/con-vendor-payment-requests?status=fully_paid" 
           class="btn btn-sm <?= (($filters['status'] ?? '') === 'fully_paid') ? 'btn-dark' : 'btn-outline-dark' ?> rounded-pill px-3 fw-semibold">
            <i class="bi bi-cash-stack me-1"></i> Fully Paid 
            <span class="badge bg-dark text-white ms-1"><?= number_format($filterCounts['fully_paid'] ?? 0) ?></span>
        </a>
    </div>

    <!-- Multi-Criteria Search & Filter Toolbar -->
    <div class="card custom-card shadow-sm border-0 mb-3 bg-white">
        <div class="card-body p-3">
            <form method="GET" action="<?= $base_url ?>/con-vendor-payment-requests" id="vprFilterForm">
                <div class="row g-2 align-items-end">
                    <!-- Project Filter -->
                    <div class="col-xl-3 col-lg-3 col-md-6">
                        <label class="form-label text-uppercase fw-bold text-secondary" style="font-size: 11px;">
                            Project Scope
                        </label>
                        <select name="project_id" class="form-control form-control-sm select2">
                            <option value="">All Projects</option>
                            <?php foreach ($projectsList ?? [] as $proj): ?>
                                <option value="<?= $proj['id'] ?>" <?= ((string)($filters['project_id'] ?? '') === (string)$proj['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($proj['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Material Category Filter -->
                    <div class="col-xl-2 col-lg-3 col-md-6">
                        <label class="form-label text-uppercase fw-bold text-secondary" style="font-size: 11px;">
                            Material Category
                        </label>
                        <select name="category_id" class="form-control form-control-sm select2">
                            <option value="">All Categories</option>
                            <?php foreach ($materialCategories ?? [] as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ((string)($filters['category_id'] ?? '') === (string)$cat['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Vendor Filter -->
                    <div class="col-xl-2 col-lg-3 col-md-6">
                        <label class="form-label text-uppercase fw-bold text-secondary" style="font-size: 11px;">
                            Vendor
                        </label>
                        <select name="vendor_id" class="form-control form-control-sm select2">
                            <option value="">All Vendors</option>
                            <?php foreach ($vendorsList ?? [] as $vnd): ?>
                                <option value="<?= $vnd['id'] ?>" <?= ((string)($filters['vendor_id'] ?? '') === (string)$vnd['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($vnd['name'] . (!empty($vnd['company_name']) ? ' (' . $vnd['company_name'] . ')' : '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Workflow & Payment Status Filter -->
                    <div class="col-xl-2 col-lg-3 col-md-6">
                        <label class="form-label text-uppercase fw-bold text-secondary" style="font-size: 11px;">
                            Status / Apex Payment
                        </label>
                        <select name="status" class="form-control form-control-sm select2">
                            <option value="">All Statuses</option>
                            <option value="ready_to_pay" <?= (in_array(($filters['status'] ?? ''), ['ready_to_pay', 'partially_paid'])) ? 'selected' : '' ?>>Ready to Pay (Partially Paid)</option>
                            <option value="apex_approved" <?= (in_array(($filters['status'] ?? ''), ['apex_approved', 'approved'])) ? 'selected' : '' ?>>Management / Apex Approved</option>
                            <option value="fully_paid" <?= (($filters['status'] ?? '') === 'fully_paid') ? 'selected' : '' ?>>Fully Paid</option>
                            <option value="pending" <?= (($filters['status'] ?? '') === 'pending' || ($filters['status'] ?? '') === 'all_pending') ? 'selected' : '' ?>>All Pending (QC & Apex)</option>
                            <option value="pending_qc" <?= (($filters['status'] ?? '') === 'pending_qc') ? 'selected' : '' ?>>Pending QC</option>
                            <option value="pending_apex" <?= (($filters['status'] ?? '') === 'pending_apex') ? 'selected' : '' ?>>Pending Apex / Mgmt</option>
                            <option value="rejected" <?= (($filters['status'] ?? '') === 'rejected') ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>

                    <!-- Search Input & Buttons -->
                    <div class="col-xl-3 col-lg-12 col-md-12">
                        <label class="form-label text-uppercase fw-bold text-secondary" style="font-size: 11px;">
                            Keyword Search
                        </label>
                        <div class="input-group input-group-sm" style="height: 36px;">
                            <input type="text" name="search" class="form-control" placeholder="Search Bill #, Invoice #, PO #, Vendor, Requester..." 
                                   value="<?= htmlspecialchars($filters['search'] ?? '') ?>" style="font-size: 12.5px;">
                            <button type="submit" class="btn btn-primary px-3 fw-semibold">
                                Filter
                            </button>
                            <?php if (!empty(array_filter($filters ?? []))): ?>
                                <a href="<?= $base_url ?>/con-vendor-payment-requests" class="btn btn-outline-danger" title="Clear all filters">
                                    Reset
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>
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
                            <th>Req ID &amp; Bill #</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Project, PO & Category</th>
                            <th>Vendor</th>
                            <th class="text-end">Invoice (৳)</th>
                            <th class="text-end">Net Payable (৳)</th>
                            <th class="text-end">Paid (৳)</th>
                            <th class="text-end">Due (৳)</th>
                            <th>Status</th>
                            <th>Submitted By</th>
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
                                        <a href="<?= $base_url ?>/con-vendor-payment-show/<?= $req['id'] ?>" class="text-primary fs-13">
                                            VPR-<?= $req['id'] ?>
                                        </a>
                                        <?php if (!empty($req['invoice_number'])): ?>
                                            <br>
                                            <span class="badge bg-light text-dark border extra-small mt-1" style="font-size: 10.5px;" title="Vendor Bill / Invoice #">
                                                <i class="bi bi-receipt me-1 text-primary"></i><?= htmlspecialchars($req['invoice_number']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge <?= $ptClass ?>"><?= $pt ?></span></td>
                                    <td><?= !empty($req['created_at']) ? date('d M Y', strtotime($req['created_at'])) : '—' ?></td>
                                    <td>
                                        <span class="fw-semibold text-dark"><?= htmlspecialchars($req['project_name'] ?? '—') ?></span>
                                        <?php if (!empty($req['purchase_order_id'])): ?>
                                            <br>
                                            <a href="<?= $base_url ?>/con-purchase-order/<?= $req['purchase_order_id'] ?>" class="badge bg-light text-primary border" style="font-size: 10.5px;">
                                                <i class="bi bi-box-seam me-1"></i>PO-<?= $req['purchase_order_id'] ?>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($req['category_names'])): ?>
                                            <br>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle mt-1" style="font-size: 10px;" title="Materials: <?= htmlspecialchars($req['material_names'] ?? '') ?>">
                                                <i class="bi bi-boxes me-1"></i><?= htmlspecialchars($req['category_names']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($req['vendor_name'] ?? '—') ?></span></td>
                                    <td class="text-end">
                                        <div class="fw-bold text-dark">৳ <?= number_format((float)($req['invoice_amount'] ?? 0), 2) ?></div>
                                        <?php if (!empty($req['invoice_number'])): ?>
                                            <small class="text-muted d-block" style="font-size: 10px;" title="Vendor Bill / Invoice #">
                                                Bill: <span class="fw-semibold text-secondary"><?= htmlspecialchars($req['invoice_number']) ?></span>
                                            </small>
                                        <?php endif; ?>
                                    </td>
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
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <i class="bi bi-person-circle text-secondary fs-13"></i>
                                            <span class="fw-semibold text-dark fs-12"><?= htmlspecialchars($req['created_by_name'] ?? '—') ?></span>
                                        </div>
                                        <?php if (!empty($req['created_at'])): ?>
                                            <div class="text-muted" style="font-size: 10px; margin-top: 2px;">
                                                <i class="bi bi-clock me-1"></i><?= date('d M Y, h:i A', strtotime($req['created_at'])) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
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
                                            <?php if ($alreadyPaid > 0): ?>
                                                <a href="<?= $base_url ?>/expense-adjustments/create?parent_type=vendor&cashout_request_id=<?= $req['id'] ?>&project_id=<?= $req['project_id'] ?>" 
                                                   class="btn btn-sm btn-outline-warning" title="Settlement / Adjustment">
                                                    <i class="bi bi-sliders"></i>
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
    if ($.fn.DataTable && !$.fn.DataTable.isDataTable('#vprTable')) {
        $('#vprTable').DataTable({ responsive: true, pageLength: 25, order: [] });
    }
});
</script>

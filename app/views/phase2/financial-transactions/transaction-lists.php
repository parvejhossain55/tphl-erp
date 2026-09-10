<?php
$base_url   = $GLOBALS['base_url'];
$page_title = $page_title ?? 'Financial Transactions Ledger';
$filters    = $filters ?? [];
$projects   = $projects ?? [];
$modulesList= $modulesList ?? [];
$moduleStats= $moduleStats ?? [];
$totalPaid  = (float)($totalPaid ?? 0);
$totalCount = (int)($totalCount ?? 0);
?>

<div class="container-fluid">
    <!-- Breadcrumb & Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h4 class="page-title fw-bold fs-18 mb-1">
                <i class="bi bi-journal-check text-primary me-2"></i><?= $page_title ?>
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard" class="text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="javascript:void(0);" class="text-muted">Finance & Accounts</a></li>
                    <li class="breadcrumb-item active text-primary" aria-current="page">Transactions Ledger</li>
                </ol>
            </nav>
        </div>
        <div class="my-auto d-flex align-items-center gap-2 mt-2 mt-md-0">
            <a href="<?= $base_url ?>/all-approval-requests" class="btn btn-outline-primary btn-sm">
                <i class="fa-solid fa-list-check me-1"></i> All Approval Requests
            </a>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row  mb-4">
        <!-- Total Disbursed -->
        <div class="col-xl-3 col-md-6 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-success">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Total Expense Disbursed</span>
                        <div class="avatar avatar-sm bg-success-subtle text-success rounded-2 p-2" style="background:#e8f5e9; color:#2e7d32;">
                            <i class="bi bi-cash-coin fs-16"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1 text-dark">
                        ৳ <?= number_format($totalPaid, 2) ?>
                    </h3>
                    <span class="text-muted extra-small" style="font-size:11px;">
                        Across <?= number_format($totalCount) ?> filtered transactions
                    </span>
                </div>
            </div>
        </div>

        <!-- Total Count -->
        <div class="col-xl-3 col-md-6 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-primary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Completed Transactions</span>
                        <div class="avatar avatar-sm bg-primary-subtle text-primary rounded-2 p-2" style="background:#e3f2fd; color:#1565c0;">
                            <i class="bi bi-check2-circle fs-16"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1 text-primary">
                        <?= number_format($totalCount) ?> <span class="fs-14 fw-normal text-muted">Paid Items</span>
                    </h3>
                    <span class="text-muted extra-small" style="font-size:11px;">
                        All paid & cleared expense records
                    </span>
                </div>
            </div>
        </div>

        <!-- Top Modules Quick Metric 1 -->
        <div class="col-xl-3 col-md-6 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-info">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Vendor Payments</span>
                        <div class="avatar avatar-sm bg-info-subtle text-info rounded-2 p-2" style="background:#e0f7fa; color:#00838f;">
                            <i class="bi bi-truck fs-16"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-1 text-dark">
                        ৳ <?= number_format($moduleStats['vendor_payment']['amount'] ?? 0, 0) ?>
                    </h4>
                    <span class="badge bg-info-subtle text-info extra-small rounded-pill" style="font-size:10px;">
                        <?= $moduleStats['vendor_payment']['count'] ?? 0 ?> Cleared Requests
                    </span>
                </div>
            </div>
        </div>

        <!-- Top Modules Quick Metric 2 (Office + Others) -->
        <div class="col-xl-3 col-md-6 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-warning">
                <div class="card-body p-3">
                    <?php 
                        $otherAmount = $totalPaid - ($moduleStats['vendor_payment']['amount'] ?? 0);
                        $otherCount = $totalCount - ($moduleStats['vendor_payment']['count'] ?? 0);
                    ?>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Other Expense Modules</span>
                        <div class="avatar avatar-sm bg-warning-subtle text-warning rounded-2 p-2" style="background:#fff8e1; color:#f57f17;">
                            <i class="bi bi-grid fs-16"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-1 text-warning">
                        ৳ <?= number_format($otherAmount, 0) ?>
                    </h4>
                    <span class="badge bg-warning-subtle text-warning extra-small rounded-pill" style="font-size:10px;">
                        <?= $otherCount ?> Fund, Office & Site Costs
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Card -->
    <div class="card custom-card border-0 shadow-sm mb-4">
        <div class="card-header bg-light py-2 px-3 d-flex align-items-center justify-content-between border-bottom">
            <span class="fw-bold fs-13 text-dark">
                <i class="bi bi-funnel me-1 text-primary"></i> Search & Filter Expense Transactions
            </span>
            <?php if (!empty($filters['module']) || !empty($filters['project_id']) || !empty($filters['start_date']) || !empty($filters['end_date']) || !empty($filters['payment_method']) || !empty($filters['search'])): ?>
                <a href="<?= $base_url ?>/con-transactions" class="badge bg-danger-subtle text-danger border border-danger-subtle text-decoration-none extra-small">
                    <i class="bi bi-x-circle me-1"></i> Clear Filters
                </a>
            <?php endif; ?>
        </div>
        <div class="card-body p-3">
            <form method="GET" action="<?= $base_url ?>/con-transactions" id="filterForm">
                <div class="row g-2">
                    <!-- Module Select -->
                    <div class="col-xl-3 col-md-4 col-sm-6">
                        <label class="form-label text-muted extra-small mb-1 fw-semibold">Expense Module</label>
                        <select name="module" class="form-control form-control-sm select2">
                            <option value="all"> All Expense Modules</option>
                            <?php foreach ($modulesList as $mKey => $mInfo): ?>
                                <option value="<?= htmlspecialchars($mKey) ?>" <?= ($filters['module'] ?? '') === $mKey ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($mInfo['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Project Select -->
                    <div class="col-xl-3 col-md-4 col-sm-6">
                        <label class="form-label text-muted extra-small mb-1 fw-semibold">Project Scope</label>
                        <select name="project_id" class="form-control form-control-sm select2">
                            <option value="all"> All Projects / Branches</option>
                            <?php foreach ($projects as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= ($filters['project_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Payment Method -->
                    <div class="col-xl-2 col-md-4 col-sm-6">
                        <label class="form-label text-muted extra-small mb-1 fw-semibold">Payment Method</label>
                        <select name="payment_method" class="form-control form-control-sm select2">
                            <option value="all">All Methods</option>
                            <option value="Cash" <?= ($filters['payment_method'] ?? '') === 'Cash' ? 'selected' : '' ?>>Cash</option>
                            <option value="Cheque" <?= ($filters['payment_method'] ?? '') === 'Cheque' ? 'selected' : '' ?>>Cheque</option>
                            <option value="Bank Transfer" <?= ($filters['payment_method'] ?? '') === 'Bank Transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                            <option value="Mobile Banking" <?= ($filters['payment_method'] ?? '') === 'Mobile Banking' ? 'selected' : '' ?>>Mobile Banking</option>
                        </select>
                    </div>

                    <!-- Date From -->
                    <div class="col-xl-2 col-md-3 col-sm-6">
                        <label class="form-label text-muted extra-small mb-1 fw-semibold">Paid Date (From)</label>
                        <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['start_date'] ?? '') ?>">
                    </div>

                    <!-- Date To -->
                    <div class="col-xl-2 col-md-3 col-sm-6">
                        <label class="form-label text-muted extra-small mb-1 fw-semibold">Paid Date (To)</label>
                        <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['end_date'] ?? '') ?>">
                    </div>

                    <!-- Keyword Search -->
                    <div class="col-xl-10 col-md-9 col-sm-8">
                        <label class="form-label text-muted extra-small mb-1 fw-semibold">Search Keywords</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by requester name, project, reference no, voucher, or notes..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Submit & Reset Buttons -->
                    <div class="col-xl-2 col-md-3 col-sm-4 d-flex align-items-end gap-1">
                        <button type="submit" class="btn btn-primary btn-sm flex-fill">
                            <i class="bi bi-funnel me-1"></i> Filter
                        </button>
                        <a href="<?= $base_url ?>/con-transactions" class="btn btn-light btn-sm border" title="Reset All Filters">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Transactions Master Table -->
    <div class="card custom-card border-0 shadow-sm">
        <div class="card-header py-3 px-3 d-flex align-items-center justify-content-between border-bottom">
            <div>
                <h6 class="card-title fw-bold mb-0 text-dark">
                    <i class="bi bi-list-columns-reverse me-2 text-primary"></i> All Expense Transactions History
                </h6>
                <span class="text-muted extra-small" style="font-size: 11px;">
                    Detailed payment disbursements across all expense departments
                </span>
            </div>
            <div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 fw-semibold">
                    <?= number_format($totalCount) ?> Records Found
                </span>
            </div>
        </div>
        <div class="card-body p-3">
            <div class="table-responsive">
                <table id="allExpenseTxnTable" class="table table-bordered table-hover align-middle text-nowrap w-100" style="font-size: 12.5px;">
                    <thead class="table-light">
                        <tr class="text-secondary fw-semibold">
                            <th style="width: 40px;" class="text-center">#</th>
                            <th>Expense Module</th>
                            <th>Project / Branch</th>
                            <th>Requested By (কে চেয়েছে)</th>
                            <th class="text-end">Paid Amount (কত টাকা)</th>
                            <th>Paid Date (কবে পেইড)</th>
                            <th>Method</th>
                            <th>Reference / Cheque No.</th>
                            <th>Disbursed By</th>
                            <th class="text-center" style="width: 60px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($transactions)): ?>
                            <?php foreach ($transactions as $i => $t): ?>
                                <?php
                                    $badge = $t['module_badge'] ?? 'secondary';
                                    $icon  = $t['module_icon']  ?? 'bi bi-cash';
                                    $method = $t['payment_method'] ?? 'Cash';
                                    $methodIcon = match(strtolower($method)) {
                                        'cash'           => 'bi bi-cash text-success',
                                        'cheque'         => 'bi bi-journal-text text-warning',
                                        'bank', 'bank transfer' => 'bi bi-bank text-primary',
                                        'mobile banking', 'mobilewallet' => 'bi bi-phone text-info',
                                        default          => 'bi bi-credit-card text-muted',
                                    };
                                ?>
                                <tr>
                                    <!-- Index -->
                                    <td class="text-center text-muted fw-semibold"><?= $i + 1 ?></td>

                                    <!-- Module Badge -->
                                    <td>
                                        <span class="badge bg-<?= $badge ?>-subtle text-<?= $badge ?> border border-<?= $badge ?>-subtle rounded-pill px-2 py-1" style="font-size: 11px;">
                                            <i class="<?= $icon ?> me-1"></i><?= htmlspecialchars($t['module_label']) ?>
                                        </span>
                                    </td>

                                    <!-- Project -->
                                    <td>
                                        <div class="fw-semibold text-dark text-truncate" style="max-width: 180px;" title="<?= htmlspecialchars($t['project_name']) ?>">
                                            <?= htmlspecialchars($t['project_name']) ?>
                                        </div>
                                    </td>

                                    <!-- Requested By (কে চেয়েছে) -->
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar avatar-xs rounded-circle bg-light border text-primary d-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 11px;">
                                                <i class="bi bi-person"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark text-truncate" style="max-width: 160px;" title="<?= htmlspecialchars($t['requested_by_name']) ?>">
                                                    <?= htmlspecialchars($t['requested_by_name']) ?>
                                                </div>
                                                <span class="text-muted extra-small d-block" style="font-size: 10px;">
                                                    <?= htmlspecialchars($t['requested_by_role']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Paid Amount (কত টাকা) -->
                                    <td class="text-end">
                                        <span class="fw-bold text-success fs-13">
                                            ৳ <?= number_format($t['amount_paid'] ?? 0, 2) ?>
                                        </span>
                                    </td>

                                    <!-- Paid Date (কবে পেইড) -->
                                    <td>
                                        <div class="text-nowrap fw-semibold text-dark">
                                            <i class="bi bi-calendar3 me-1 text-muted extra-small"></i>
                                            <?= !empty($t['payment_date']) ? date('d M Y', strtotime($t['payment_date'])) : '—' ?>
                                        </div>
                                    </td>

                                    <!-- Method -->
                                    <td>
                                        <span class="text-nowrap extra-small">
                                            <i class="<?= $methodIcon ?> me-1"></i>
                                            <?= htmlspecialchars($method) ?>
                                        </span>
                                    </td>

                                    <!-- Reference / Cheque No -->
                                    <td>
                                        <span class="badge bg-light text-dark border extra-small text-truncate" style="max-width: 140px; font-size: 10.5px;" title="<?= htmlspecialchars($t['reference_number']) ?>">
                                            <?= htmlspecialchars($t['reference_number']) ?>
                                        </span>
                                    </td>

                                    <!-- Disbursed By -->
                                    <td>
                                        <span class="text-muted extra-small">
                                            <i class="bi bi-person-check me-1 text-success"></i>
                                            <?= htmlspecialchars($t['processed_by_name'] ?? 'Accounts') ?>
                                        </span>
                                    </td>

                                    <!-- Action -->
                                    <td class="text-center">
                                        <?php if (!empty($t['view_url'])): ?>
                                            <a href="<?= $base_url ?>/<?= $t['view_url'] ?>" target="_blank" class="btn btn-sm btn-outline-primary py-1 px-2" title="View Details / Receipt">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox fs-32 text-secondary d-block mb-2"></i>
                                        <p class="mb-1 fw-semibold">No Expense Transactions Found</p>
                                        <span class="extra-small text-muted">Try adjusting your filters or search keywords.</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.dataTables_wrapper .dataTables_length {
    margin-bottom: 0.5rem;
    font-size: 13px;
}
.dataTables_wrapper .dataTables_length select {
    display: inline-block;
    width: auto;
    padding: 0.25rem 1.75rem 0.25rem 0.75rem;
    font-size: 13px;
    font-weight: 500;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    margin: 0 0.4rem;
}
.dataTables_wrapper .dataTables_filter {
    margin-bottom: 0.5rem;
}
.dataTables_wrapper .dataTables_filter input {
    display: inline-block;
    width: auto;
    padding: 0.25rem 0.75rem;
    font-size: 13px;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    margin-left: 0.4rem;
}
.dataTables_wrapper .dataTables_info {
    padding-top: 0.5rem;
    font-size: 12.5px;
    color: #6c757d;
}
.dataTables_wrapper .dataTables_paginate {
    padding-top: 0.5rem;
}
.dataTables_wrapper .dataTables_paginate .pagination {
    margin-bottom: 0;
    gap: 2px;
}
.dataTables_wrapper .dataTables_paginate .page-item .page-link {
    border-radius: 4px;
    font-size: 12.5px;
    padding: 0.35rem 0.75rem;
}
.dataTables_wrapper .dataTables_paginate .page-item.active .page-link {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: #fff;
    font-weight: 600;
}
</style>

<?php
$page_script = <<<'JS'
(function initTxnDataTable() {
    function setup() {
        if (typeof jQuery === 'undefined' || typeof $.fn.DataTable === 'undefined') {
            setTimeout(setup, 150);
            return;
        }

        var $table = $('#allExpenseTxnTable');
        if (!$table.length) return;

        if ($.fn.DataTable.isDataTable('#allExpenseTxnTable')) {
            $table.DataTable().destroy();
        }

        $table.DataTable({
            responsive: true,
            pageLength: 50,
            lengthMenu: [
                [25, 50, 100, 200, -1],
                [25, 50, 100, 200, "All (সবগুলো)"]
            ],
            order: [[5, 'desc']], // Paid Date desc
            dom: "<'row align-items-center mb-3'<'col-sm-12 col-md-6 d-flex align-items-center'l><'col-sm-12 col-md-6 d-flex justify-content-md-end'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row align-items-center mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-md-end'p>>",
            language: {
                lengthMenu: "Show _MENU_ entries per page",
                search: "_INPUT_",
                searchPlaceholder: "Instant table search...",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)",
                paginate: {
                    first: "«",
                    previous: "‹ Prev",
                    next: "Next ›",
                    last: "»"
                }
            }
        });

        if ($.fn.select2) {
            $('.select2').select2({ width: '100%' });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setup);
    } else {
        setup();
    }
})();
JS;
?>

<script>
// Fallback direct execution
if (typeof initTxnDataTable === 'function') {
    initTxnDataTable();
}
</script>


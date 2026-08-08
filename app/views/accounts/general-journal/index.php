<?php
$base_url = $GLOBALS['base_url'];
$sessionUser = $_SESSION['user'] ?? [];
$role_id = $sessionUser['role_id'] ?? 0;
$canEdit = in_array($role_id, [1, 3, 6, 30]);

$moduleLabels = [
    '' => 'All Modules & Source Categories',
    'fund_requisition' => 'Fund Requisitions',
    'vendor_payment'   => 'Project Vendor Payments',
    'contractor_bill'  => 'Contractor Bills & Payments',
    'material_purchase' => 'Material Purchase & Procurement',
    'office_assets'    => 'Company Fixed Assets (Register / Opening)',
    'asset_procurement' => 'Asset Procurement Requisitions (New Purchases)',
    'payroll'          => 'Payroll & Employee Salaries',
    'management_cashin' => 'Management & Directors Investment',
    'land_acquisition' => 'Land Acquisition Payments',
    'expense_voucher'  => 'Accounts Expense Vouchers',
    'sales_payment'     => 'Client Receipts & Revenue'
];

$expenseTypeLabels = [
    '' => 'All Expense & Receipt Types',
    'vendor_material' => 'Vendor & Material Payment',
    'contractor'      => 'Contractor Bill Payment'
];
?>

<div class="container-fluid py-3">
    <!-- PAGE HEADER -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 page-header-breadcrumb">
        <div>
            <h4 class="fw-bold mb-1 d-flex align-items-center gap-2 text-dark">
                <i class="fa-light fa-book-journal-whills text-primary"></i> General Journal & Transaction Explorer
            </h4>
            <div class="fs-12 text-muted">
                Search, filter, and track all journal entries across modules, projects, and expense categories.
            </div>
        </div>
        <div class="d-flex gap-2">
            <?php if ($canEdit): ?>
                <a href="<?= $base_url ?>/general-journal/create" class="btn btn-primary btn-sm fw-semibold d-inline-flex align-items-center gap-1 shadow-sm">
                    <i class="fa-light fa-plus"></i> New Journal Entry
                </a>
            <?php endif; ?>
            <a href="<?= $base_url ?>/financial-dashboard" class="btn btn-outline-secondary btn-sm fw-semibold">
                <i class="fa-light fa-arrow-left me-1"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- SUMMARY KPI STATS -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card custom-card border-0 shadow-sm overflow-hidden h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar avatar-md bg-primary-transparent text-primary rounded-circle">
                            <i class="fa-light fa-list-check fs-5"></i>
                        </div>
                        <div>
                            <span class="fs-11 text-muted text-uppercase fw-semibold">Total Journal Entries</span>
                            <h4 class="fw-bold mb-0 text-dark"><?= number_format($journal_count) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card custom-card border-0 shadow-sm overflow-hidden h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar avatar-md bg-success-transparent text-success rounded-circle">
                            <i class="fa-light fa-money-bill-transfer fs-5"></i>
                        </div>
                        <div>
                            <span class="fs-11 text-muted text-uppercase fw-semibold">Total Debit Volume</span>
                            <h4 class="fw-bold mb-0 text-success">৳ <?= number_format($total_debit, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card custom-card border-0 shadow-sm overflow-hidden h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar avatar-md bg-info-transparent text-info rounded-circle">
                            <i class="fa-light fa-receipt fs-5"></i>
                        </div>
                        <div>
                            <span class="fs-11 text-muted text-uppercase fw-semibold">Total Credit Volume</span>
                            <h4 class="fw-bold mb-0 text-info">৳ <?= number_format($total_credit, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card custom-card border-0 shadow-sm overflow-hidden h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar avatar-md bg-warning-transparent text-warning rounded-circle">
                            <i class="fa-light fa-city fs-5"></i>
                        </div>
                        <div>
                            <span class="fs-11 text-muted text-uppercase fw-semibold">Active Projects Filtered</span>
                            <h4 class="fw-bold mb-0 text-dark"><?= count($projects) ?> Projects</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ADVANCED FILTER BAR -->
    <div class="card custom-card border-0 shadow-sm mb-4">
        <div class="card-header bg-light py-3 border-bottom d-flex align-items-center justify-content-between">
            <h6 class="card-title mb-0 fw-bold fs-14">
                <i class="fa-light fa-filter me-2 text-primary"></i> Advanced Journal Search & Filters
            </h6>
            <?php if (!empty(array_filter($filters))): ?>
                <a href="<?= $base_url ?>/general-journal" class="btn btn-outline-danger btn-sm fs-11 fw-semibold py-1">
                    <i class="fa-light fa-rotate-right me-1"></i> Clear Filters
                </a>
            <?php endif; ?>
        </div>
        <div class="card-body p-3">
            <form method="GET" action="<?= $base_url ?>/general-journal" class="row g-3 align-items-end">
                <!-- Search Keyword -->
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label fs-12 fw-semibold text-muted">Keyword Search</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fa-light fa-magnifying-glass"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Voucher #, Narration, Code, Party..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                    </div>
                </div>

                <!-- Module / Source Category Filter -->
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label fs-12 fw-semibold text-muted">Module / Category Source</label>
                    <select name="module" class="form-select form-select-sm select2-filter">
                        <?php foreach ($moduleLabels as $val => $lbl): ?>
                            <option value="<?= $val ?>" <?= ($filters['module'] ?? '') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Project-wise Search (Only Projects with Journals) -->
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label fs-12 fw-semibold text-muted">Project-Wise Filter</label>
                    <select name="project_id" class="form-select form-select-sm select2-filter">
                        <option value="">All Projects & Head Office</option>
                        <option value="head_office" <?= ($filters['project_id'] ?? '') === 'head_office' ? 'selected' : '' ?>>Head Office / General</option>
                        <?php foreach ($projects as $proj): ?>
                            <option value="<?= $proj['id'] ?>" <?= ($filters['project_id'] ?? '') == $proj['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($proj['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Expense Sub-Type Filter -->
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label fs-12 fw-semibold text-muted">Project Expense Category</label>
                    <select name="expense_type" class="form-select form-select-sm select2-filter">
                        <?php foreach ($expenseTypeLabels as $val => $lbl): ?>
                            <option value="<?= $val ?>" <?= ($filters['expense_type'] ?? '') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Date From -->
                <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                    <label class="form-label fs-12 fw-semibold text-muted">From Date</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
                </div>

                <!-- Date To -->
                <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                    <label class="form-label fs-12 fw-semibold text-muted">To Date</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
                </div>

                <!-- Filter Actions -->
                <div class="col-xl-2 col-lg-3 col-md-4 col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill fw-semibold">
                        <i class="fa-light fa-filter me-1"></i> Filter
                    </button>
                    <a href="<?= $base_url ?>/general-journal" class="btn btn-light btn-sm fw-semibold text-muted border">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- JOURNALS LIST TABLE -->
    <div class="card custom-card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <h6 class="card-title mb-0 fw-bold">
                <i class="fa-light fa-list me-2 text-primary"></i> Journal Voucher Entries (<?= count($journals) ?>)
            </h6>
            <button class="btn btn-outline-secondary btn-sm no-print" onclick="window.print()">
                <i class="fa-light fa-print me-1"></i> Print Table
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0" id="journalExplorerTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 130px;">Voucher No</th>
                            <th style="width: 110px;">Date</th>
                            <th style="width: 130px;">Voucher Type</th>
                            <th style="width: 180px;">Project</th>
                            <th>Particulars / Narration</th>
                            <th class="text-end" style="width: 130px;">Debit (BDT)</th>
                            <th class="text-end" style="width: 130px;">Credit (BDT)</th>
                            <th class="text-center" style="width: 110px;">Status</th>
                            <th class="text-center pe-3 no-print" style="width: 100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($journals)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted fs-13">
                                    <i class="fa-light fa-folder-open fs-2 d-block mb-2 text-secondary"></i>
                                    No journal entries found matching your search filters.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($journals as $j): ?>
                                <?php
                                $status = strtolower($j['status'] ?? 'approved');
                                $statusBadge = match ($status) {
                                    'approved', 'posted' => 'bg-success-transparent text-success border border-success',
                                    'pending'  => 'bg-warning-transparent text-warning border border-warning',
                                    'rejected' => 'bg-danger-transparent text-danger border border-danger',
                                    default    => 'bg-secondary-transparent text-secondary'
                                };

                                $voucherBadge = match ($j['voucher_type'] ?? '') {
                                    'Receipt Voucher' => 'bg-info-transparent text-info border border-info',
                                    'Payment Voucher' => 'bg-danger-transparent text-danger border border-danger',
                                    'Journal Voucher' => 'bg-primary-transparent text-primary border border-primary',
                                    default           => 'bg-secondary-transparent text-secondary'
                                };
                                ?>
                                <tr>
                                    <td class="ps-3 fw-bold font-monospace text-primary">
                                        <a href="<?= $base_url ?>/general-journal/view/<?= $j['id'] ?>" class="text-primary text-decoration-none">
                                            <?= htmlspecialchars($j['journal_number']) ?>
                                        </a>
                                    </td>
                                    <td class="fs-12"><?= date('d M Y', strtotime($j['date'])) ?></td>
                                    <td>
                                        <span class="badge <?= $voucherBadge ?> fs-11">
                                            <?= htmlspecialchars($j['voucher_type'] ?? 'Journal') ?>
                                        </span>
                                    </td>
                                    <td class="fs-12">
                                        <?php if (!empty($j['project_name'])): ?>
                                            <span class="fw-semibold text-dark"><i class="fa-light fa-building me-1 text-primary"></i> <?= htmlspecialchars($j['project_name']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted fs-11"><i class="fa-light fa-city me-1"></i> Head Office / General</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fs-12 text-wrap" style="max-width: 320px;">
                                        <div class="fw-medium text-dark"><?= htmlspecialchars($j['narration'] ?? 'Journal Entry') ?></div>
                                        <?php if (!empty($j['created_by_name'])): ?>
                                            <div class="fs-10 text-muted">Created by: <?= htmlspecialchars($j['created_by_name']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end fw-bold text-success font-monospace">
                                        ৳ <?= number_format((float)$j['total_debit'], 2) ?>
                                    </td>
                                    <td class="text-end fw-bold text-info font-monospace">
                                        ৳ <?= number_format((float)$j['total_credit'], 2) ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?= $statusBadge ?> fs-10 text-uppercase px-2 py-1">
                                            <?= htmlspecialchars($j['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center pe-3 no-print">
                                        <div class="btn-list">
                                            <a href="<?= $base_url ?>/general-journal/view/<?= $j['id'] ?>" class="btn btn-icon btn-sm btn-info-light border" title="View Journal Details">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            <?php if ($canEdit): ?>
                                                <a href="<?= $base_url ?>/general-journal/edit/<?= $j['id'] ?>" class="btn btn-icon btn-sm btn-primary-light border" title="Edit Journal Entry">
                                                    <i class="fa-solid fa-pen-to-square"></i>
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
document.addEventListener('DOMContentLoaded', function() {
    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
        jQuery('.select2-filter').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    }
});
</script>

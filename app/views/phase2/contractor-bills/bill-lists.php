<?php
$base_url   = $GLOBALS['base_url'];
$page_title = $page_title ?? 'Contractor Bill Requests';

$countAll        = count($bills ?? []);
$countDraft      = 0;
$countSubmitted  = 0;
$countVerified   = 0;
$countQcApproved = 0;
$countApproved   = 0;
$countRejected   = 0;
$countPaid       = 0;

foreach (($bills ?? []) as $b) {
    match ($b['status'] ?? '') {
        'draft'          => $countDraft++,
        'submitted'      => $countSubmitted++,
        'verified'       => $countVerified++,
        'qc_approved'    => $countQcApproved++,
        'approved'       => $countApproved++,
        'rejected'       => $countRejected++,
        'paid', 'partially_paid', 'fully_paid' => $countPaid++,
        default          => null,
    };
}
?>
<div class="container-fluid">
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="d-flex align-items-center gap-2">
            <h4 class="page-title fw-semibold fs-18 mb-0"><?= $page_title ?></h4>
            <?php if (hasPermission('Contractor Bill') || in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33, 17, 23, 31, 20, 32, 12])): ?>
                <a href="<?= $base_url ?>/con-contractor-bill-create" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i> New Bill
                </a>
            <?php endif; ?>
        </div>
      
    </div>

    <!-- Filter Toolbar: Tabs / Pills + Status Dropdown -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary bill-filter-pill active" data-status="All" onclick="filterBillTable('All', this)">
                <i class="bi bi-grid-fill me-1"></i> All <span class="badge bg-secondary ms-1 rounded-pill"><?= $countAll ?></span>
            </button>

            <button type="button" class="btn btn-sm btn-outline-warning bill-filter-pill" data-status="Draft" onclick="filterBillTable('Draft', this)">
                <i class="bi bi-pencil-fill me-1"></i> Draft 
                <span class="badge bg-warning text-dark ms-1 rounded-pill"><?= $countDraft ?></span>
            </button>

            <button type="button" class="btn btn-sm btn-outline-info bill-filter-pill" data-status="Submitted" onclick="filterBillTable('Submitted', this)">
                <i class="bi bi-hourglass-split me-1"></i> Submitted 
                <span class="badge bg-info text-dark ms-1 rounded-pill"><?= $countSubmitted ?></span>
            </button>

            <button type="button" class="btn btn-sm btn-outline-primary bill-filter-pill" data-status="Verified" onclick="filterBillTable('Verified', this)">
                <i class="bi bi-check-circle me-1"></i> PM Verified 
                <span class="badge bg-primary ms-1 rounded-pill"><?= $countVerified ?></span>
            </button>

            <button type="button" class="btn btn-sm btn-outline-secondary bill-filter-pill" data-status="Qc approved" onclick="filterBillTable('Qc approved', this)">
                <i class="bi bi-shield-check me-1"></i> QC Approved 
                <span class="badge bg-secondary ms-1 rounded-pill"><?= $countQcApproved ?></span>
            </button>

            <button type="button" class="btn btn-sm btn-outline-success bill-filter-pill" data-status="Approved" onclick="filterBillTable('Approved', this)">
                <i class="bi bi-check-circle-fill me-1"></i> Approved 
                <span class="badge bg-success ms-1 rounded-pill"><?= $countApproved ?></span>
            </button>

            <button type="button" class="btn btn-sm btn-outline-danger bill-filter-pill" data-status="Rejected" onclick="filterBillTable('Rejected', this)">
                <i class="bi bi-x-circle-fill me-1"></i> Rejected 
                <span class="badge bg-danger ms-1 rounded-pill"><?= $countRejected ?></span>
            </button>

            <button type="button" class="btn btn-sm btn-outline-dark bill-filter-pill" data-status="Paid" onclick="filterBillTable('Paid', this)">
                <i class="bi bi-cash me-1"></i> Paid 
                <span class="badge bg-dark ms-1 rounded-pill"><?= $countPaid ?></span>
            </button>
        </div>

        <div class="d-flex align-items-center gap-2">
            <label for="billStatusFilterSelect" class="small text-muted fw-bold mb-0 text-nowrap">
                <i class="bi bi-funnel-fill me-1"></i>Status Filter:
            </label>
            <select id="billStatusFilterSelect" class="form-select form-select-sm shadow-xs" style="min-width: 175px; font-weight: 600;" onchange="onStatusSelectChange(this.value)">
                <option value="All">All Statuses (<?= $countAll ?>)</option>
                <option value="Draft">Draft (<?= $countDraft ?>)</option>
                <option value="Submitted">Submitted (<?= $countSubmitted ?>)</option>
                <option value="Verified">PM Verified (<?= $countVerified ?>)</option>
                <option value="Qc approved">QC Approved (<?= $countQcApproved ?>)</option>
                <option value="Approved">Approved (<?= $countApproved ?>)</option>
                <option value="Rejected">Rejected (<?= $countRejected ?>)</option>
                <option value="Paid">Paid (<?= $countPaid ?>)</option>
            </select>
        </div>
    </div>

    <div class="card custom-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="card-title mb-0"><i class="bi bi-receipt me-2 text-primary"></i> All Contractor Bills</h6>

            <!-- Search by Contractor & Trade / Expertise Filter -->
            <form method="GET" action="<?= $base_url ?>/con-contractor-bills" class="d-flex align-items-center gap-2 mb-0 flex-wrap" id="contractorFilterForm">
                <div class="d-flex align-items-center gap-2" style="min-width: 320px;">
                    <label for="contractor_id" class="form-label mb-0 fw-semibold text-muted text-nowrap" style="font-size: 13px;">
                        <i class="bi bi-funnel text-primary me-1"></i> Contractor / Trade:
                    </label>
                    <div style="min-width: 250px;">
                        <select name="contractor_id" id="contractor_id" class="form-select form-select-sm select2" style="width: 100%;">
                            <option value="">All Contractors & Trades</option>
                            <?php if (!empty($contractors)): ?>
                                <?php foreach ($contractors as $c): ?>
                                    <?php
                                        $tradeLabel = !empty($c['trade']) ? ucwords(str_replace('_', ' ', $c['trade'])) : (!empty($c['role']) ? $c['role'] : '');
                                        $displayTxt = htmlspecialchars($c['name']) . (!empty($tradeLabel) ? ' (' . htmlspecialchars($tradeLabel) . ')' : '');
                                        $isSelected = (!empty($selectedContractorId) && (int)$selectedContractorId === (int)$c['id']);
                                    ?>
                                    <option value="<?= $c['id'] ?>" <?= $isSelected ? 'selected' : '' ?>>
                                        <?= $displayTxt ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-sm btn-primary d-flex align-items-center gap-1">
                    <i class="bi bi-search"></i> Search
                </button>

                <?php if (!empty($selectedContractorId)): ?>
                    <a href="<?= $base_url ?>/con-contractor-bills" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1" title="Clear Filter">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="billTable" class="table table-bordered table-hover text-nowrap">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Project</th>
                            <th>Contractor</th>
                            <th>Bill Type</th>
                            <th>Bill Date</th>
                            <th class="text-end">Amount (৳)</th>
                            <th>Status</th>
                            <th>Submitted By</th>
                            <th>Approved By</th>
                            <th>Payment By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($bills)): ?>
                            <?php foreach ($bills as $i => $b): ?>
                                <?php
                                $statusMap = [
                                    'draft'          => 'warning',
                                    'submitted'      => 'info',
                                    'verified'       => 'primary',
                                    'qc_approved'    => 'secondary',
                                    'approved'       => 'success',
                                    'rejected'       => 'danger',
                                    'paid'           => 'secondary',
                                    'partially_paid' => 'info',
                                    'fully_paid'     => 'secondary',
                                ];
                                $badge = $statusMap[$b['status']] ?? 'secondary';
                                $statusLabel = str_replace('_', ' ', ucfirst($b['status']));
                                ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td>
                                        <?= htmlspecialchars($b['project_name'] ?? '-') ?>
                                        <?php if (!empty($b['target_id'])): ?>
                                            <br>
                                            <small class="text-success fw-semibold">
                                                <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($b['target_phase_name'] ?? '') ?> 
                                                <span class="text-muted">(Week <?= htmlspecialchars($b['target_week_number'] ?? '') ?>)</span>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-dark"><?= htmlspecialchars($b['operator_name'] ?? '-') ?></span><br>
                                        <?php if (!empty($b['operator_trade'])): ?>
                                            <span class="badge bg-light text-primary border border-primary-subtle mt-1" style="font-size: 11px;">
                                                <i class="bi bi-tools me-1"></i><?= htmlspecialchars(ucwords(str_replace('_', ' ', $b['operator_trade']))) ?>
                                            </span>
                                        <?php elseif (!empty($b['operator_role'])): ?>
                                            <small class="text-muted"><?= htmlspecialchars($b['operator_role']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= ($b['bill_type'] === 'Advance') ? 'warning' : (($b['bill_type'] === 'Retention') ? 'info' : 'primary') ?>-transparent">
                                            <?= htmlspecialchars($b['bill_type'] ?? 'Progress') ?>
                                        </span>
                                    </td>
                                    <td><?= date('d M Y', strtotime($b['bill_date'])) ?></td>
                                    <td class="fw-semibold text-end">৳ <?= number_format($b['bill_amount'], 2) ?></td>
                                    <td><span class="badge bg-<?= $badge ?>"><?= $statusLabel ?></span></td>
                                    <td><?= htmlspecialchars($b['created_by_name'] ?? '-') ?></td>
                                    <td>
                                        <?php
                                        $hasApprover = false;
                                        ?>
                                        <div class="d-flex flex-column gap-1" style="font-size: 11px; min-width: 140px;">
                                            <?php if (!empty($b['verified_by_name'])): 
                                                $hasApprover = true;
                                                $pmDate = !empty($b['verified_at']) ? date('d M Y', strtotime($b['verified_at'])) : '';
                                            ?>
                                                <div class="p-1 rounded bg-light border border-info-subtle mb-1">
                                                    <div class="d-flex align-items-center justify-content-between gap-1">
                                                        <span class="badge bg-info text-white px-1" style="font-size: 8.5px; font-weight: 600;">PM</span>
                                                        <span class="fw-semibold text-dark text-truncate" title="<?= htmlspecialchars($b['verified_by_name']) ?>"><?= htmlspecialchars($b['verified_by_name']) ?></span>
                                                    </div>
                                                    <div class="d-flex align-items-center justify-content-between text-muted mt-1" style="font-size: 9.5px;">
                                                        <span class="text-truncate"><?= htmlspecialchars($b['verified_by_role'] ?? 'PM Head') ?></span>
                                                        <?php if ($pmDate): ?>
                                                            <span class="text-nowrap ms-1 text-primary fw-medium"><?= $pmDate ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($b['qc_approved_by_name'])): 
                                                $hasApprover = true;
                                                $qcDate = !empty($b['qc_approved_at']) ? date('d M Y', strtotime($b['qc_approved_at'])) : '';
                                            ?>
                                                <div class="p-1 rounded bg-light border border-primary-subtle mb-1">
                                                    <div class="d-flex align-items-center justify-content-between gap-1">
                                                        <span class="badge bg-primary text-white px-1" style="font-size: 8.5px; font-weight: 600;">QC</span>
                                                        <span class="fw-semibold text-dark text-truncate" title="<?= htmlspecialchars($b['qc_approved_by_name']) ?>"><?= htmlspecialchars($b['qc_approved_by_name']) ?></span>
                                                    </div>
                                                    <div class="d-flex align-items-center justify-content-between text-muted mt-1" style="font-size: 9.5px;">
                                                        <span class="text-truncate"><?= htmlspecialchars($b['qc_approved_by_role'] ?? 'QC & Audit') ?></span>
                                                        <?php if ($qcDate): ?>
                                                            <span class="text-nowrap ms-1 text-primary fw-medium"><?= $qcDate ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($b['approved_by_name'])): 
                                                $hasApprover = true;
                                                $mgmtDate = !empty($b['approved_at']) ? date('d M Y', strtotime($b['approved_at'])) : (!empty($b['updated_at']) && in_array($b['status'], ['approved', 'paid', 'partially_paid', 'fully_paid']) ? date('d M Y', strtotime($b['updated_at'])) : '');
                                            ?>
                                                <div class="p-1 rounded bg-light border border-success-subtle">
                                                    <div class="d-flex align-items-center justify-content-between gap-1">
                                                        <span class="badge bg-success text-white px-1" style="font-size: 8.5px; font-weight: 600;">Mgmt</span>
                                                        <span class="fw-semibold text-success text-truncate" title="<?= htmlspecialchars($b['approved_by_name']) ?>"><?= htmlspecialchars($b['approved_by_name']) ?></span>
                                                    </div>
                                                    <div class="d-flex align-items-center justify-content-between text-muted mt-1" style="font-size: 9.5px;">
                                                        <span class="text-truncate"><?= htmlspecialchars($b['approved_by_role'] ?? 'Management') ?></span>
                                                        <?php if ($mgmtDate): ?>
                                                            <span class="text-nowrap ms-1 text-success fw-medium"><?= $mgmtDate ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!$hasApprover): ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($b['payment_by_name'])): ?>
                                            <span class="text-primary fw-semibold"><?= htmlspecialchars($b['payment_by_name']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= $base_url ?>/con-contractor-bill/<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (in_array($b['status'], ['paid', 'partially_paid', 'fully_paid']) || !empty($b['payment_date']) || !empty($b['payment_by_name'])): ?>
                                            <a href="<?= $base_url ?>/expense-adjustments/create?parent_type=contractor&cashout_request_id=<?= $b['id'] ?>&project_id=<?= $b['project_id'] ?>" class="btn btn-sm btn-outline-warning" title="Settlement / Adjustment">
                                                <i class="bi bi-sliders"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!in_array($b['status'], ['approved', 'paid', 'partially_paid', 'fully_paid'])): ?>
                                            <?php if (hasPermission('Contractor Bill') || in_array($userRole ?? ($_SESSION['user']['role_id'] ?? 0), [1, 3, 33, 17, 23, 31])): ?>
                                                <a href="<?= $base_url ?>/con-contractor-bill-edit/<?= $b['id'] ?>" class="btn btn-sm btn-outline-info" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="<?= $base_url ?>/con-contractor-bill-delete/<?= $b['id'] ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this bill?');">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" class="text-center text-muted py-4">
                                    No contractor bills found.
                                    <?php if (hasPermission('Contractor Bill') || in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33, 17, 23, 31, 20, 32, 12])): ?>
                                        <a href="<?= $base_url ?>/con-contractor-bill-create">Create one</a>
                                    <?php endif; ?>
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
.custom-card .card-header .select2-container--default .select2-selection--single {
    height: 31px;
    border-color: #dee2e6;
    display: flex;
    align-items: center;
}
.custom-card .card-header .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 31px;
    font-size: 13px;
    padding-left: 8px;
    color: #333;
}
.custom-card .card-header .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 31px;
}
</style>

<script>
window.filterBillTable = function (status, btnElement) {
    document.querySelectorAll('.bill-filter-pill').forEach(function (pill) {
        pill.classList.remove('active');
        if (pill.classList.contains('btn-secondary')) {
            pill.classList.replace('btn-secondary', 'btn-outline-secondary');
        } else if (pill.classList.contains('btn-danger')) {
            pill.classList.replace('btn-danger', 'btn-outline-danger');
        } else if (pill.classList.contains('btn-success')) {
            pill.classList.replace('btn-success', 'btn-outline-success');
        } else if (pill.classList.contains('btn-primary')) {
            pill.classList.replace('btn-primary', 'btn-outline-primary');
        } else if (pill.classList.contains('btn-info')) {
            pill.classList.replace('btn-info', 'btn-outline-info');
        } else if (pill.classList.contains('btn-dark')) {
            pill.classList.replace('btn-dark', 'btn-outline-dark');
        } else if (pill.classList.contains('btn-warning')) {
            pill.classList.replace('btn-warning', 'btn-outline-warning');
        }
    });

    if (btnElement) {
        btnElement.classList.add('active');
        if (btnElement.classList.contains('btn-outline-secondary')) {
            btnElement.classList.replace('btn-outline-secondary', 'btn-secondary');
        } else if (btnElement.classList.contains('btn-outline-danger')) {
            btnElement.classList.replace('btn-outline-danger', 'btn-danger');
        } else if (btnElement.classList.contains('btn-outline-success')) {
            btnElement.classList.replace('btn-outline-success', 'btn-success');
        } else if (btnElement.classList.contains('btn-outline-primary')) {
            btnElement.classList.replace('btn-outline-primary', 'btn-primary');
        } else if (btnElement.classList.contains('btn-outline-info')) {
            btnElement.classList.replace('btn-outline-info', 'btn-info');
        } else if (btnElement.classList.contains('btn-outline-dark')) {
            btnElement.classList.replace('btn-outline-dark', 'btn-dark');
        } else if (btnElement.classList.contains('btn-outline-warning')) {
            btnElement.classList.replace('btn-outline-warning', 'btn-warning');
        }
    }

    // Keep dropdown in sync
    var sel = document.getElementById('billStatusFilterSelect');
    if (sel && sel.value !== status) {
        sel.value = status;
    }

    if (typeof jQuery !== 'undefined' && typeof jQuery.fn.DataTable !== 'undefined') {
        var dt = jQuery('#billTable').DataTable();
        if (!status || status === 'All') {
            dt.column(6).search('').draw();
        } else {
            if (status === 'Paid') {
                dt.column(6).search('Paid', true, false).draw();
            } else {
                var escaped = jQuery.fn.dataTable.util.escapeRegex(status);
                dt.column(6).search('^' + escaped + '$', true, false).draw();
            }
        }
    }
};

window.onStatusSelectChange = function (val) {
    var matchingBtn = document.querySelector('.bill-filter-pill[data-status="' + val + '"]');
    filterBillTable(val, matchingBtn);
};

$(document).ready(function () {
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('#contractor_id').select2({
            placeholder: "Search by Contractor Name or Trade / Expertise...",
            allowClear: true,
            width: '100%'
        });
        $('#contractor_id').on('change select2:select select2:clear', function () {
            $('#contractorFilterForm').submit();
        });
    }

    if (typeof jQuery !== 'undefined' && typeof jQuery.fn.DataTable !== 'undefined') {
        var table = $('#billTable').DataTable({ 
            destroy: true,
            responsive: true, 
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            order: [[4, 'desc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Quick filter in table...",
                paginate: {
                    first: '<i class="fa-solid fa-angles-left"></i>',
                    previous: '<i class="fa-solid fa-angle-left"></i>',
                    next: '<i class="fa-solid fa-angle-right"></i>',
                    last: '<i class="fa-solid fa-angles-right"></i>'
                }
            }
        });
    }
});
</script>

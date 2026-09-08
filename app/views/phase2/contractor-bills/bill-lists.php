<?php
$base_url   = $GLOBALS['base_url'];
$page_title = $page_title ?? 'Contractor Bill Requests';
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
                                        <?php if (!empty($b['approved_by_name'])): ?>
                                            <span class="text-success fw-semibold"><?= htmlspecialchars($b['approved_by_name']) ?></span>
                                            <?php if (!empty($b['approved_by_role'])): ?>
                                                <br><small class="text-muted fst-italic"><?= htmlspecialchars($b['approved_by_role']) ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
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

    $('#billTable').DataTable({ 
        responsive: true, 
        pageLength: 25, 
        order: [[4, 'desc']] 
    });
});
</script>

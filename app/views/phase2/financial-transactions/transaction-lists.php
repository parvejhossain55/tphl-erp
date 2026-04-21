<?php
$base_url   = $GLOBALS['base_url'];
$page_title = $page_title ?? 'Financial Transactions Ledger';
?>
<div class="container-fluid">
    <!-- Breadcrumb -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h4 class="page-title fw-semibold fs-18 mb-0"><?= $page_title ?></h4>
            <div class="d-flex">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item active">Transactions Ledger</li>
                </ol>
            </div>
        </div>
        <div class="my-auto">
            <a href="<?= $base_url ?>/con-pending-payments" class="btn btn-warning btn-sm">
                <i class="bi bi-clock me-1"></i> Pending Payments
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <?php
    $totalPaid  = array_sum(array_column($transactions ?? [], 'amount'));
    $totalCount = count($transactions ?? []);
    $refTypes   = array_count_values(array_column($transactions ?? [], 'ref_type'));
    ?>
    <div class="row  mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card custom-card border-0 bg-primary bg-opacity-10">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar avatar-md bg-primary rounded-circle">
                            <i class="bi bi-currency-exchange text-white fs-5"></i>
                        </div>
                        <div>
                            <p class="mb-0 text-muted small">Total Transactions</p>
                            <h5 class="mb-0 fw-bold"><?= number_format($totalCount) ?></h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card custom-card border-0 bg-success bg-opacity-10">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar avatar-md bg-success rounded-circle">
                            <i class="bi bi-cash-stack text-white fs-5"></i>
                        </div>
                        <div>
                            <p class="mb-0 text-muted small">Total Paid</p>
                            <h5 class="mb-0 fw-bold">৳ <?= number_format($totalPaid, 0) ?></h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card custom-card border-0 bg-info bg-opacity-10">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar avatar-md bg-info rounded-circle">
                            <i class="bi bi-receipt text-white fs-5"></i>
                        </div>
                        <div>
                            <p class="mb-0 text-muted small">Contractor Bills Paid</p>
                            <h5 class="mb-0 fw-bold"><?= $refTypes['contractor_bill'] ?? 0 ?></h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card custom-card border-0 bg-warning bg-opacity-10">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar avatar-md bg-warning rounded-circle">
                            <i class="bi bi-cash-coin text-white fs-5"></i>
                        </div>
                        <div>
                            <p class="mb-0 text-muted small">Fund Requests Paid</p>
                            <h5 class="mb-0 fw-bold"><?= $refTypes['operational_fund'] ?? 0 ?></h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="card custom-card">
        <div class="card-header">
            <h6 class="card-title mb-0"><i class="bi bi-journal-check me-2 text-primary"></i> Transaction Ledger</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="txnTable" class="table table-bordered table-hover text-nowrap">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Ref Type</th>
                            <th>Project</th>
                            <th>Amount (৳)</th>
                            <th>Payment Method</th>
                            <th>Payment Date</th>
                            <th>Reference No.</th>
                            <th>Processed By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($transactions)): ?>
                            <?php foreach ($transactions as $i => $t): ?>
                                <?php
                                $refBadge = [
                                    'contractor_bill'  => 'primary',
                                    'operational_fund' => 'success',
                                    'vendor_payment'   => 'info',
                                ][$t['ref_type']] ?? 'secondary';
                                $refLabel = str_replace('_', ' ', ucwords($t['ref_type'], '_'));
                                ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><span class="badge bg-<?= $refBadge ?>"><?= $refLabel ?></span></td>
                                    <td><?= htmlspecialchars($t['project_name'] ?? '-') ?></td>
                                    <td class="text-end fw-semibold text-success">৳ <?= number_format($t['amount'], 2) ?></td>
                                    <td>
                                        <i class="bi bi-<?= $t['payment_method'] === 'Cash' ? 'cash' : ($t['payment_method'] === 'Cheque' ? 'journal-text' : 'bank') ?> me-1"></i>
                                        <?= htmlspecialchars($t['payment_method']) ?>
                                    </td>
                                    <td><?= date('d M Y', strtotime($t['payment_date'])) ?></td>
                                    <td><?= htmlspecialchars($t['reference_no'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($t['processed_by_name'] ?? '-') ?></td>
                                    <td>
                                        <a href="<?= $base_url ?>/con-transaction/<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="9" class="text-center text-muted py-4">No transactions yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    $('#txnTable').DataTable({ responsive: true, pageLength: 25, order: [[5, 'desc']] });
});
</script>

<?php
$base_url   = $GLOBALS['base_url'];
$page_title = $page_title ?? 'Pending Payments';

// Separate: vendor payments waiting approval vs all others ready for payment
$awaitingApproval = [];
$readyToPay       = [];

foreach ($pending ?? [] as $p) {
    if ($p['ref_type'] === 'vendor_payment' && ($p['vpr_status'] ?? '') === 'Submitted') {
        $awaitingApproval[] = $p;
    } else {
        $readyToPay[] = $p;
    }
}
?>

<div class="pagetitle d-flex justify-content-between align-items-center">
    <div>
        <h1><?= htmlspecialchars($page_title) ?></h1>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-transactions">Transactions</a></li>
                <li class="breadcrumb-item active">Pending Payments</li>
            </ol>
        </nav>
    </div>
    <span class="badge bg-warning fs-6 px-3 py-2">
        <i class="bi bi-clock me-1"></i> <?= count($pending ?? []) ?> Pending
    </span>
</div>

<section class="section">

    <?php if (empty($pending)): ?>
    <div class="card__wrapper shadow-sm">
        <div class="card__body text-center py-5">
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
        <div class="card__header d-flex justify-content-between align-items-center">
            <h6 class="card__title mb-0 text-warning">
                <i class="bi bi-hourglass-split me-2"></i>
                Vendor Payments — Awaiting Approval
                <span class="badge bg-warning text-dark ms-2"><?= count($awaitingApproval) ?></span>
            </h6>
        </div>
        <div class="card__body p-0">
            <div class="alert alert-warning border-0 rounded-0 py-2 mb-0 small">
                <i class="bi bi-info-circle me-1"></i>
                এই requests গুলো এখনো approve হয়নি। Approve করতে নিচের <strong>View</strong> button থেকে detail page এ যান।
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-warning">
                        <tr>
                            <th>#</th>
                            <th>Type</th>
                            <th>Project</th>
                            <th>Description</th>
                            <th class="text-end">Amount (৳)</th>
                            <th>Submitted On</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($awaitingApproval as $i => $p): ?>
                        <?php
                            $ptClass = ($p['payment_type'] ?? '') === 'Advance'
                                ? 'bg-primary'
                                : (($p['payment_type'] ?? '') === 'Partial' ? 'bg-orange text-dark' : 'bg-success');
                        ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>
                                <span class="badge bg-secondary">Vendor Payment</span><br>
                                <span class="badge <?= $ptClass ?> mt-1"><?= htmlspecialchars($p['payment_type'] ?? '') ?></span>
                            </td>
                            <td><?= htmlspecialchars($p['project_name'] ?? '—') ?></td>
                            <td><?= htmlspecialchars(mb_strimwidth($p['description'] ?? '', 0, 55, '...')) ?></td>
                            <td class="text-end fw-bold text-warning">৳ <?= number_format((float)($p['amount'] ?? 0), 2) ?></td>
                            <td><small><?= !empty($p['created_at']) ? date('d M Y', strtotime($p['created_at'])) : '—' ?></small></td>
                            <td class="text-center">
                                <a href="<?= $base_url ?>/con-vendor-payment-show/<?= $p['ref_id'] ?>"
                                   class="btn btn-warning btn-sm fw-bold">
                                    <i class="bi bi-eye me-1"></i> View & Approve
                                </a>
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
            <h6 class="card__title mb-0 text-success">
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
                            <th>Description</th>
                            <th class="text-end">Amount (৳)</th>
                            <th>Approved On</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($readyToPay as $i => $p): ?>
                        <?php
                            $refType  = $p['ref_type'] ?? '';
                            $badgeColor = match($refType) {
                                'contractor_bill'  => 'primary',
                                'operational_fund' => 'success',
                                'vendor_payment'   => 'info',
                                default            => 'secondary',
                            };
                            $refLabel = match($refType) {
                                'contractor_bill'  => 'Contractor Bill',
                                'operational_fund' => 'Fund Request',
                                'vendor_payment'   => 'Vendor Payment',
                                default            => 'Other',
                            };
                        ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>
                                <span class="badge bg-<?= $badgeColor ?>"><?= $refLabel ?></span>
                                <?php if (!empty($p['payment_type']) && $refType === 'vendor_payment'): ?>
                                <br><span class="badge bg-secondary mt-1"><?= htmlspecialchars($p['payment_type']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($p['project_name'] ?? '—') ?></td>
                            <td><?= htmlspecialchars(mb_strimwidth($p['description'] ?? '', 0, 55, '...')) ?></td>
                            <td class="text-end fw-semibold text-success">৳ <?= number_format((float)($p['amount'] ?? 0), 2) ?></td>
                            <td><small><?= !empty($p['created_at']) ? date('d M Y', strtotime($p['created_at'])) : '—' ?></small></td>
                            <td class="text-center">
                                <a href="<?= $base_url ?>/con-process-payment/<?= $p['ref_type'] ?>/<?= $p['ref_id'] ?>"
                                   class="btn btn-success btn-sm fw-bold">
                                    <i class="bi bi-credit-card me-1"></i> Process Payment
                                </a>
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
    if ($('#pendingTable').length) {
        $('#pendingTable').DataTable({ responsive: true, pageLength: 25 });
    }
});
</script>

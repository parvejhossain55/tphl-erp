<?php
$base_url      = $GLOBALS['base_url'];
$page_title    = $page_title ?? 'Transaction Receipt';
$sourceDetail  = $sourceDetail ?? null;
$allPayments   = $allPayments  ?? [];
$totalPaid     = (float)($totalPaid     ?? 0);
$originalAmount= (float)($originalAmount ?? 0);
$dueAmount     = (float)($dueAmount     ?? 0);

$refType = $txn['ref_type'] ?? '';

// Labels
$refLabel = [
    'contractor_bill'  => 'Contractor Bill',
    'operational_fund' => 'Operational Fund',
    'vendor_payment'   => 'Vendor Payment',
][$refType] ?? ucwords(str_replace('_', ' ', $refType));

// Method icons
$methodIcon = [
    'Cash'         => 'cash',
    'Cheque'       => 'journal-text',
    'BankTransfer' => 'bank',
    'MobileWallet' => 'phone',
][$txn['payment_method'] ?? ''] ?? 'credit-card';

// Payment type badge
$payBadge = match($txn['payment_type'] ?? '') {
    'Advance' => ['bg-primary',   'Advance'],
    'Partial' => ['bg-warning text-dark', 'Partial'],
    'Refund'  => ['bg-danger',    'Refund'],
    default   => ['bg-success',   'Full'],
};

// Payable percentage
$paidPct = $originalAmount > 0 ? min(100, round(($totalPaid / $originalAmount) * 100)) : 0;

// Specific source info
$vendorName     = '';
$sourceRef      = '';
$requestAmount  = 0;
$approvedAmount = 0;

if ($refType === 'vendor_payment' && $sourceDetail) {
    $vendorName     = $sourceDetail['vendor_name']   ?? '—';
    $sourceRef      = 'VPR-' . $sourceDetail['id'];
    $requestAmount  = (float)($sourceDetail['net_payable']      ?? 0);
    $approvedAmount = (float)($sourceDetail['approved_amount']  ?? $requestAmount);
} elseif ($refType === 'contractor_bill' && $sourceDetail) {
    $vendorName     = $sourceDetail['contractor_name'] ?? '—';
    $sourceRef      = 'CBR-' . $sourceDetail['id'];
    $requestAmount  = (float)($sourceDetail['bill_amount']      ?? 0);
    $approvedAmount = (float)($sourceDetail['approved_amount']  ?? $requestAmount);
} elseif ($refType === 'operational_fund' && $sourceDetail) {
    $vendorName     = 'Internal Fund';
    $sourceRef      = 'OFR-' . $sourceDetail['id'];
    $requestAmount  = (float)($sourceDetail['amount']           ?? 0);
    $approvedAmount = (float)($sourceDetail['approved_amount']  ?? $requestAmount);
}
?>

<div class="container-fluid">
    <!-- Breadcrumb -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h4 class="page-title fw-semibold fs-18 mb-0"><?= $page_title ?></h4>
            <ol class="breadcrumb mb-0 mt-1">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-transactions">Transactions</a></li>
                <li class="breadcrumb-item active">TXN #<?= $txn['id'] ?></li>
            </ol>
        </div>
        <div class="my-auto d-flex gap-2 flex-wrap">
            <?php 
                $voucherModule = match($refType) {
                    'vendor_payment' => 'vendor_payment',
                    'contractor_bill' => 'contractor_bill',
                    'operational_fund' => 'operational_fund',
                    default => $refType
                };
                $voucherRefId = $txn['ref_id'] ?? ($sourceDetail['id'] ?? 0);
            ?>
            <?php if (!empty($voucherRefId) && in_array($refType, ['vendor_payment', 'contractor_bill', 'operational_fund'])): ?>
            <a href="<?= $base_url ?>/print-debit-voucher?module=<?= urlencode($voucherModule) ?>&id=<?= (int)$voucherRefId ?>" target="_blank" class="btn btn-primary btn-sm">
                <i class="bi bi-printer me-1"></i> Print Debit Voucher
            </a>
            <?php endif; ?>
            <?php if ($refType === 'vendor_payment' && !empty($sourceDetail['id'])): ?>
            <a href="<?= $base_url ?>/con-vendor-payment-show/<?= $sourceDetail['id'] ?>" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-eye me-1"></i> View Request
            </a>
            <?php elseif ($refType === 'contractor_bill' && !empty($sourceDetail['id'])): ?>
            <a href="<?= $base_url ?>/con-contractor-bill-show/<?= $sourceDetail['id'] ?>" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-eye me-1"></i> View Bill
            </a>
            <?php endif; ?>
            <a href="<?= $base_url ?>/con-pending-payments" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Pending Payments
            </a>
            <a href="<?= $base_url ?>/con-transactions" class="btn btn-secondary btn-sm">
                <i class="bi bi-list me-1"></i> All Transactions
            </a>
        </div>
    </div>

    <div class="row ">

        <!-- ══ LEFT COLUMN: Transaction Receipt + Payment History ══════════════ -->
        <div class="col-xl-8">

            <!-- Transaction Receipt Card -->
            <div class="card custom-card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center py-3"
                     style="background: linear-gradient(135deg,#1a6e40 0%,#198754 100%);">
                    <div>
                        <h6 class="text-white mb-0 fw-bold">
                            <i class="bi bi-receipt me-2"></i>Transaction Receipt
                        </h6>
                        <small class="text-white-50">TXN #<?= $txn['id'] ?> &nbsp;·&nbsp; <?= $refLabel ?></small>
                    </div>
                    <span class="badge <?= $payBadge[0] ?> fs-6"><?= $payBadge[1] ?> Payment</span>
                </div>

                <div class="card-body">

                    <!-- Amount Hero -->
                    <div class="text-center py-3 mb-3 rounded-3" style="background:#f0faf4;">
                        <small class="text-muted d-block">Amount Paid (This Transaction)</small>
                        <span class="fw-bold text-success" style="font-size:2.4rem;">
                            ৳ <?= number_format((float)($txn['amount_paid'] ?? 0), 2) ?>
                        </span>
                        <div class="mt-2 pt-2 border-top border-success border-opacity-25" style="max-width: 600px; margin: 0 auto;">
                            <span class="text-uppercase text-muted fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">Amount in Words</span>
                            <div class="fw-bold text-success fs-14 mt-1">
                                <i class="bi bi-chat-quote me-1"></i><?= class_exists('FormatHelper') ? FormatHelper::amountInWords($txn['amount_paid'] ?? 0) : (function_exists('amountInWords') ? amountInWords($txn['amount_paid'] ?? 0) : '') ?>
                            </div>
                        </div>
                    </div>

                    <div class="row ">
                        <!-- Payment Method -->
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Payment Method</small>
                            <strong>
                                <i class="bi bi-<?= $methodIcon ?> me-1 text-primary"></i>
                                <?= str_replace('BankTransfer', 'Bank Transfer', $txn['payment_method'] ?? '—') ?>
                            </strong>
                        </div>
                        <!-- Payment Date -->
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Payment Date</small>
                            <strong>
                                <?php
                                $pDate = $txn['transaction_date_str'] ?? $txn['transaction_date'] ?? $txn['created_at'] ?? null;
                                echo $pDate ? date('d M Y', strtotime($pDate)) : '—';
                                ?>
                            </strong>
                        </div>
                        <!-- Project -->
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Project</small>
                            <strong><?= htmlspecialchars($txn['project_name'] ?? $sourceDetail['project_name'] ?? '—') ?></strong>
                        </div>
                        <!-- Reference No -->
                        <?php if (!empty($txn['reference_number'])): ?>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Reference No.</small>
                            <strong><?= htmlspecialchars($txn['reference_number']) ?></strong>
                        </div>
                        <?php endif; ?>
                        <!-- Processed By -->
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Processed By</small>
                            <strong><?= htmlspecialchars($txn['processed_by_name'] ?? '—') ?></strong>
                        </div>
                        <!-- Recorded At -->
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Recorded At</small>
                            <strong><?= date('d M Y h:i A', strtotime($txn['created_at'])) ?></strong>
                        </div>
                        <!-- Notes -->
                        <?php if (!empty($txn['notes'])): ?>
                        <div class="col-sm-12">
                            <small class="text-muted d-block">Notes</small>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($txn['notes'])) ?></p>
                        </div>
                        <?php endif; ?>
                        <!-- Disbursement Proof -->
                        <?php
                        $proofFiles = [];
                        if (!empty($txn['disbursement_proof'])) {
                            $proofFiles = is_array($txn['disbursement_proof'])
                                ? $txn['disbursement_proof']
                                : (json_decode($txn['disbursement_proof'], true) ?? []);
                        }
                        ?>
                        <?php if (!empty($proofFiles)): ?>
                        <div class="col-sm-12">
                            <small class="text-muted d-block"><i class="bi bi-file-earmark-check me-1"></i>Disbursement Proof</small>
                            <div class="d-flex flex-wrap gap-2 mt-1">
                                <?php foreach ($proofFiles as $idx => $fp): ?>
                                <a href="<?= $base_url ?>/public/<?= htmlspecialchars($fp) ?>"
                                   target="_blank" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-file-earmark me-1"></i>Document <?= $idx + 1 ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- All Payments History for this source -->
            <?php if (count($allPayments) > 0): ?>
            <div class="card custom-card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-clock-history me-2"></i>
                        All Payments for <?= $sourceRef ?> (<?= count($allPayments) ?> transaction<?= count($allPayments) > 1 ? 's' : '' ?>)
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>TXN ID</th>
                                    <th>Date</th>
                                    <th class="text-end">Amount Paid (৳)</th>
                                    <th>Type</th>
                                    <th>Method</th>
                                    <th>Processed By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allPayments as $i => $p):
                                    $isCurrent = ($p['id'] == $txn['id']);
                                ?>
                                <tr <?= $isCurrent ? 'class="table-success fw-bold"' : '' ?>>
                                    <td><?= $i + 1 ?></td>
                                    <td>
                                        <a href="<?= $base_url ?>/con-transaction/<?= $p['id'] ?>"
                                           class="text-decoration-none <?= $isCurrent ? 'text-success' : '' ?>">
                                            #<?= $p['id'] ?>
                                            <?php if ($isCurrent): ?><span class="badge bg-success ms-1">Current</span><?php endif; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php
                                        $d = $p['transaction_date_str'] ?? $p['transaction_date'] ?? $p['created_at'] ?? null;
                                        echo $d ? date('d M Y', strtotime($d)) : '—';
                                        ?>
                                    </td>
                                    <td class="text-end fw-semibold text-success">৳ <?= number_format((float)($p['amount_paid'] ?? 0), 2) ?></td>
                                    <td>
                                        <span class="badge <?= match($p['payment_type'] ?? '') {
                                            'Advance' => 'bg-primary',
                                            'Partial' => 'bg-warning text-dark',
                                            'Refund'  => 'bg-danger',
                                            default   => 'bg-success'
                                        } ?>">
                                            <?= htmlspecialchars($p['payment_type'] ?? 'Full') ?>
                                        </span>
                                    </td>
                                    <td><?= str_replace('BankTransfer', 'Bank Transfer', $p['payment_method'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($p['processed_by_name'] ?? '—') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-dark">
                                <tr>
                                    <td colspan="3" class="fw-bold">Total Paid</td>
                                    <td class="text-end fw-bold text-success fs-6">৳ <?= number_format($totalPaid, 2) ?></td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ══ RIGHT COLUMN: Source / Vendor Info ══════════════════════════════ -->
        <div class="col-xl-4">

            <!-- Payment Summary Card -->
            <div class="card custom-card mb-3 border-success">
                <div class="card-header bg-success bg-opacity-10">
                    <h6 class="card-title mb-0 text-success">
                        <i class="bi bi-bar-chart-line me-2"></i>Payment Summary
                    </h6>
                </div>
                <div class="card-body">
                    <!-- Progress Bar -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Payment Progress</small>
                            <small class="fw-bold text-success"><?= $paidPct ?>%</small>
                        </div>
                        <div class="progress" style="height:10px;">
                            <div class="progress-bar bg-success" style="width:<?= $paidPct ?>%"></div>
                        </div>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Original Amount</span>
                        <strong>৳ <?= number_format($originalAmount, 2) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Paid</span>
                        <strong class="text-success">৳ <?= number_format($totalPaid, 2) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-0 pt-2 border-top">
                        <span class="fw-bold">Due Remaining</span>
                        <strong class="<?= $dueAmount > 0 ? 'text-danger' : 'text-success' ?> fs-6">
                            <?php if ($dueAmount > 0): ?>
                                ৳ <?= number_format($dueAmount, 2) ?>
                            <?php else: ?>
                                <i class="bi bi-check-circle-fill me-1"></i>Fully Paid
                            <?php endif; ?>
                        </strong>
                    </div>
                </div>
            </div>

            <!-- Source / Vendor Info Card -->
            <?php if ($sourceDetail): ?>
            <div class="card custom-card mb-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-building me-2"></i>
                        <?= $refType === 'vendor_payment' ? 'Vendor Information' : 'Request Details' ?>
                    </h6>
                </div>
                <div class="card-body">
                    <?php if ($refType === 'vendor_payment'): ?>
                    <!-- Vendor Info -->
                    <div class="mb-3 d-flex align-items-center gap-2">
                        <div class="avatar avatar-md bg-success bg-opacity-15 text-success rounded-circle fw-bold d-flex align-items-center justify-content-center" style="width:44px;height:44px;font-size:1.2rem;">
                            <?= mb_strtoupper(mb_substr($vendorName, 0, 1)) ?>
                        </div>
                        <div>
                            <strong class="d-block"><?= htmlspecialchars($vendorName) ?></strong>
                            <?php if (!empty($sourceDetail['vendor_phone'])): ?>
                            <small class="text-muted">
                                <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($sourceDetail['vendor_phone']) ?>
                            </small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <hr>
                    <div class="mb-2">
                        <small class="text-muted d-block">Request Ref</small>
                        <strong><?= $sourceRef ?></strong>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Payment Type</small>
                        <strong><?= htmlspecialchars($sourceDetail['payment_type'] ?? '—') ?></strong>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Net Payable (Requested)</small>
                        <strong>৳ <?= number_format((float)($sourceDetail['net_payable'] ?? 0), 2) ?></strong>
                    </div>
                    <?php if (!empty($sourceDetail['approved_amount'])): ?>
                    <div class="mb-2">
                        <small class="text-muted d-block">Approved Amount</small>
                        <strong class="text-success">৳ <?= number_format((float)$sourceDetail['approved_amount'], 2) ?></strong>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($sourceDetail['approved_by_name'])): ?>
                    <div class="mb-2">
                        <small class="text-muted d-block">Approved By</small>
                        <strong><?= htmlspecialchars($sourceDetail['approved_by_name']) ?></strong>
                    </div>
                    <?php endif; ?>
                    <div class="mb-0">
                        <small class="text-muted d-block">Current Status</small>
                        <span class="badge <?= match($sourceDetail['status'] ?? '') {
                            'Approved'      => 'bg-success',
                            'Fully_Paid'    => 'bg-primary',
                            'Partially_Paid'=> 'bg-warning text-dark',
                            'Submitted'     => 'bg-info',
                            'Rejected'      => 'bg-danger',
                            default         => 'bg-secondary'
                        } ?>"><?= htmlspecialchars($sourceDetail['status'] ?? '—') ?></span>
                    </div>

                    <?php elseif ($refType === 'contractor_bill'): ?>
                    <div class="mb-2">
                        <small class="text-muted d-block">Contractor</small>
                        <strong><?= htmlspecialchars($vendorName) ?></strong>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Bill Ref</small>
                        <strong><?= $sourceRef ?></strong>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Bill Amount</small>
                        <strong>৳ <?= number_format((float)($sourceDetail['bill_amount'] ?? 0), 2) ?></strong>
                    </div>
                    <?php if (!empty($sourceDetail['approved_amount'])): ?>
                    <div class="mb-2">
                        <small class="text-muted d-block">Approved Amount</small>
                        <strong class="text-success">৳ <?= number_format((float)$sourceDetail['approved_amount'], 2) ?></strong>
                    </div>
                    <?php endif; ?>

                    <?php elseif ($refType === 'operational_fund'): ?>
                    <div class="mb-2">
                        <small class="text-muted d-block">Fund Request Ref</small>
                        <strong><?= $sourceRef ?></strong>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Purpose</small>
                        <strong><?= htmlspecialchars($sourceDetail['purpose'] ?? '—') ?></strong>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Requested Amount</small>
                        <strong>৳ <?= number_format((float)($sourceDetail['amount'] ?? 0), 2) ?></strong>
                    </div>
                    <?php if (!empty($sourceDetail['approved_amount'])): ?>
                    <div class="mb-2">
                        <small class="text-muted d-block">Approved Amount</small>
                        <strong class="text-success">৳ <?= number_format((float)$sourceDetail['approved_amount'], 2) ?></strong>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>

                    <!-- Project -->
                    <hr>
                    <div class="mb-0">
                        <small class="text-muted d-block"><i class="bi bi-building-fill me-1"></i>Project</small>
                        <strong><?= htmlspecialchars($sourceDetail['project_name'] ?? '—') ?></strong>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /right col -->
    </div>
</div>

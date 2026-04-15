<?php
$page_title = "Clients Cash in History";
$page = 'Clients';
$addPage = 'add transaction';
$addPage = true;
$show_breadcrumb = true;
?>

<div class="row">
    <!-- Client Header Section -->
    <div class="col-xxl-12 mb-4">
        <div class="card__wrapper">
            <div class="card__body">
                <h3 class="mb-2 card__icon">
                    <i class="fa-light fa-user-circle me-2"></i>
                    <?= htmlspecialchars($clientDetails['client_name'] ?? 'N/A') ?>
                </h3>
                <div class="d-flex flex-wrap gap-3">
                    <span class="text-muted">
                        <i class="fa-light fa-building mx-2"></i>
                        <strong>Project:</strong> <?= htmlspecialchars($clientDetails['project_name'] ?? 'N/A') ?>
                    </span>
                    <span class="text-muted">
                        <i class="fa-light fa-home mx-2"></i>
                        <strong>Flat:</strong> UNIT <?= htmlspecialchars($clientDetails['unit_name'] ?? '') ?> - FLOOR <?= htmlspecialchars($clientDetails['flat_no'] ?? 'N/A') ?>
                    </span>
                    <span class="text-muted">
                        <i class="fa-light fa-phone mx-2"></i>
                        <strong>Phone:</strong> <?= htmlspecialchars($clientDetails['phone'] ?? 'N/A') ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row">

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span><i class="fa-light fa-money-bill"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Total Price</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['total_price'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Total Land Share Card -->
<div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
    <div class="card__wrapper">
        <div class="d-flex align-items-center gap-1">
            <div class="card__icon" >
                <span><i class="fa-light fa-landmark"></i></span>
            </div>
            <div class="card__title-wrap">
                <h6 class="card__sub-title mb-10">Total Land Share</h6>
                <div class="d-flex flex-wrap align-items-end gap-10">
                    <h4 class="card__title">TK.<?= number_format($paymentSummary['total_land_share'] ?? 0, 2) ?></h4>
                </div>
            </div>
        </div>
    </div>
</div>
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span><i class="fa-light fa-hand-holding-usd"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Booking Money</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['booking_money'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<!-- Paid Land Share Card -->
<div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
    <div class="card__wrapper">
        <div class="d-flex align-items-center gap-1">
            <div class="card__icon" >
                <span><i class="fa-light fa-hand-holding-usd"></i></span>
            </div>
            <div class="card__title-wrap">
                <h6 class="card__sub-title mb-10"> Land Share Paid</h6>
                <div class="d-flex flex-wrap align-items-end gap-10">
                    <h4 class="card__title">TK.<?= number_format($paymentSummary['total_land_share_paid'] ?? 0, 2) ?></h4>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Remaining Land Share Card -->
<div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
    <div class="card__wrapper">
        <div class="d-flex align-items-center gap-1">
            <div class="card__icon" >
                <span><i class="fa-light fa-scale-balanced"></i></span>
            </div>
            <div class="card__title-wrap">
                <h6 class="card__sub-title mb-10">Remaining Land Share</h6>
                <div class="d-flex flex-wrap align-items-end gap-10">
                    <h4 class="card__title">TK.<?= number_format($paymentSummary['remaining_land_share'] ?? 0, 2) ?></h4>
                </div>


            </div>
        </div>
    </div>
</div>
        <!-- NEW: DISCOUNT CARD -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span><i class="fa-light fa-tag"></i></span> <!-- Changed icon -->
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Discount Amount</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title text-success">TK.<?= number_format($paymentSummary['discount_amount'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span><i class="fa-light fa-file-certificate"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Registration Money</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['registration_money'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span><i class="fa-light fa-tools"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Development Charges</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['development_charge_paid'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span><i class="fa-light fa-building"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Total Construction Fee</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['construction_fee'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span><i class="fa-light fa-money-check-dollar"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Construction Fee Paid</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['construction_fee_paid_after_service'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span><i class="fa-light fa-hand-holding-dollar"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Service Fee (10%)</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['total_service_fee'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span><i class="fa-light fa-money-check-dollar"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Remaining Construction Fee</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['remaining_construction_fee'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- REMOVED: Average EMI Amount Card -->
        <!--
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-calculator"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Average EMI Amount</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title">TK.<?= number_format($paymentSummary['avg_emi_amount'] ?? 0, 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    -->

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span><i class="fa-light fa-list-ol"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Total EMI</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title"><?= $paymentSummary['total_emi'] ?? 0 ?> </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span><i class="fa-light fa-check-circle"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Completed EMI</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title"><?= $paymentSummary['completed_emi'] ?? 0 ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span><i class="fa-light fa-clock"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Pending EMI</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title"><?= $paymentSummary['pending_emi'] ?? 0 ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span><i class="fa-light fa-calendar-check"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Last Payment</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">
                                <?php if ($paymentSummary['last_payment_date']): ?>
                                    <?= date('d M, Y', strtotime($paymentSummary['last_payment_date'])) ?>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction History Table -->
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="card__header">
                <h5 class="card__title">Transaction History</h5>
            </div>
            <div class="col-xxl-12 mb-4">
                <div class="mt-3 d-flex gap-2 position ">
                    <a href="<?= $GLOBALS['base_url'] ?>/client-cash-in-form?booking_id=<?= $clientDetails['booking_id'] ?>" class="btn btn-primary mb-3">
                        <i class="fa-light fa-plus me-2"></i> Add Transaction
                    </a>
                    <a href="<?= $base_url ?>/client-statement/<?= $clientDetails['booking_id'] ?>" target="_blank" class="btn btn-primary mb-3">
                        <i class="fa-light fa-file-invoice me-2"></i> Get Statement
                    </a>
                </div>
                <div class="table__wrapper table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Payment Type</th>
                                <th>Details</th>
                                <th>Amount</th>
                                <th>Due Amount</th> 
                                <th>Land Share</th>
                                <th>Service Fee</th>
                                <th>Method</th>
                                <th>Trx ID</th>
                                <th>Status</th>
                                <th>Verification</th>
                                <th>Verified By</th>
                                <th>Document</th>
                                <th>Added By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?php echo $transaction['formatted_date']; ?></td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo $transaction['installment_label']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $transaction['payment_details']; ?></td>
                                    <td>
                                        <?php
                                            $isEmi       = ($transaction['payment_type'] === 'emi');
                                            $grossAmount = (float)($transaction['amount'] ?? 0);
                                            $serviceFee  = (float)($transaction['service_fee'] ?? 0);
                                            $netAmount   = $isEmi ? ($grossAmount - $serviceFee) : $grossAmount;
                                        ?>
                                        TK.<?= number_format($netAmount, 2) ?>
                                        <?php if ($isEmi && $serviceFee > 0): ?>
                                            <br><span class="text-muted" style="font-size:11px;">
                                                Gross: TK.<?= number_format($grossAmount, 2) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <!-- ADD THIS -->
                                     <td>
                                         <?php if (!empty($transaction['due_emi_amount']) && $transaction['due_emi_amount'] > 0): ?>
                                             TK.<?php echo number_format($transaction['due_emi_amount'], 2); ?>
                                         <?php else: ?>
                                             <span class="text-muted">-</span>
                                         <?php endif; ?>
                                     </td>
                                    <td>
                                        <?php if ($transaction['land_share_amount'] > 0): ?>
                                            TK.<?php echo number_format($transaction['land_share_amount'], 2); ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($transaction['service_fee'] > 0): ?>
                                            TK.<?php echo number_format($transaction['service_fee'], 2); ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $transaction['payment_method'] ?? 'N/A'))); ?>
                                    </td>
                                    <td>
                                        <?php echo !empty($transaction['transaction_ref']) ? htmlspecialchars($transaction['transaction_ref']) : '<span class="text-muted">-</span>'; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $transaction['status_class']; ?>">
                                            <?php echo $transaction['status_text']; ?>
                                        </span>
                                    </td>
                                    <!-- VERIFICATION CONTROL -->
                                    <td class="ver-cell" id="ver-cell-<?= $transaction['id'] ?>">
                                        <?php
                                            $vs = $transaction['verification_status'] ?? 'pending';
                                            if ($vs === 'verified'):
                                        ?>
                                            <span class="badge bg-success ver-badge">
                                                <i class="fa-light fa-check-circle me-1"></i>Verified
                                            </span>
                                        <?php elseif ($vs === 'rejected'): ?>
                                            <span class="badge bg-danger ver-badge">
                                                <i class="fa-light fa-times-circle me-1"></i>Rejected
                                            </span>
                                        <?php else: /* pending */ ?>
                                            <?php
                                                // ── ACCOUNTS-ONLY: show action buttons only to Accounts/Admin ──
                                                $canVerify = in_array((int)($_SESSION['user']['role_id'] ?? 0), [1, 3, 6]);
                                            ?>
                                            <?php if ($canVerify): ?>
                                            <div class="d-flex gap-1">
                                                <button type="button"
                                                    class="btn btn-sm btn-verify"
                                                    onclick="verifyPayment(<?= $transaction['id'] ?>, 'verify')"
                                                    title="Verify this payment">
                                                    <i class="fa-light fa-check"></i> Verify
                                                </button>
                                                <button type="button"
                                                    class="btn btn-sm btn-reject"
                                                    onclick="openRejectModal(<?= $transaction['id'] ?>)"
                                                    title="Reject this payment">
                                                    <i class="fa-light fa-times"></i> Reject
                                                </button>
                                            </div>
                                            <?php else: ?>
                                            <span class="badge bg-warning text-dark">
                                                <i class="fa-light fa-clock me-1"></i>Pending
                                            </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <!-- VERIFIED BY -->
                                    <td id="verified-by-<?= $transaction['id'] ?>">
                                        <?php if (!empty($transaction['verified_by_name'])): ?>
                                            <?= htmlspecialchars($transaction['verified_by_name']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <!-- DOCUMENT COLUMN — visible regardless of verification_status -->
                                    <td>
                                        <?php
                                            $docFiles = [];
                                            if (!empty($transaction['attachment_paths'])) {
                                                $decoded = json_decode($transaction['attachment_paths'], true);
                                                if (is_array($decoded)) $docFiles = $decoded;
                                            }
                                            if (!empty($transaction['attachment_image'])) {
                                                $legacyPath = 'uploads/payments/' . $transaction['attachment_image'];
                                                if (!in_array($legacyPath, $docFiles)) $docFiles[] = $legacyPath;
                                            }
                                            $docCount = count($docFiles);
                                        ?>
                                        <?php if ($docCount > 0 && is_numeric($transaction['id'])): ?>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-primary"
                                                onclick="openDocumentModal(<?= (int)$transaction['id'] ?>)"
                                                title="View <?= $docCount ?> document(s)">
                                                <i class="fa-light fa-file-image me-1"></i>
                                                <?= $docCount ?> File<?= $docCount > 1 ? 's' : '' ?>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo !empty($transaction['created_by_name']) ? htmlspecialchars($transaction['created_by_name']) : '<span class="text-muted">System</span>'; ?>
                                    </td>
                                    <td>
                                        
                                        <div class="d-flex align-items-center justify-content-start gap-10">
                                            <?php if (!empty($transaction['id']) && strpos($transaction['id'], 'booking-') === false): ?>
                                                <?php $isVerified = ($transaction['verification_status'] ?? 'pending') === 'verified'; ?>
                                                <!-- INVOICE BUTTON — disabled when not verified -->
                                                <?php if ($isVerified): ?>
                                                <a href="<?= $GLOBALS['base_url'] ?>/payment-invoice?payment_id=<?= $transaction['id'] ?>"
                                                    class="table__icon invoice"
                                                    style="color: #fff; background-color: #6c5ffc;"
                                                    title="View Invoice"
                                                    target="_blank">
                                                    <i class="fa-light fa-file-invoice"></i>
                                                </a>
                                                <?php else: ?>
                                                <span class="table__icon disabled"
                                                    title="Invoice disabled — payment not yet verified"
                                                    style="background-color: #fef3cd; color: #856404; cursor: not-allowed;">
                                                    <i class="fa-light fa-file-invoice"></i>
                                                </span>
                                                <?php endif; ?>
                                                <!-- EDIT BUTTON -->
                                                <?php if ($transaction['payment_type'] !== 'booking_money'): ?>
                                                <a href="<?= $GLOBALS['base_url'] ?>/edit-client-cash-in/<?= $transaction['id'] ?>"
                                                    class="table__icon edit"
                                                    title="Edit">
                                                    <i class="fa-sharp fa-light fa-pen"></i>
                                                </a>
                                                <?php else: ?>
                                                <span class="table__icon disabled" title="Booking Money Cannot Be Edited" style="background-color: #f3f6f9; color: #a5aeb7;">
                                                    <i class="fa-regular fa-lock"></i>
                                                </span>
                                                <?php endif; ?>
                                                <!-- DELETE BUTTON -->
                                                <a href="#" class="removeBtn table__icon delete"
                                                    onclick="deleteTransaction(<?= $transaction['id'] ?>); return false;"
                                                    title="Delete">
                                                    <i class="fa-regular fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="table__icon disabled" title="System Generated">
                                                    <i class="fa-regular fa-lock"></i>
                                                </span>
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
    </div>
<!-- ===== REJECTION REASON MODAL ===== -->
<div class="modal fade" id="rejectReasonModal" tabindex="-1" aria-labelledby="rejectReasonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="border-bottom: 2px solid #fee2e2;">
                <h5 class="modal-title" id="rejectReasonModalLabel" style="color:#b91c1c;">
                    <i class="fa-light fa-times-circle me-2"></i>Reject Payment
                </h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">
                    <i class="fa-light fa-info-circle me-1"></i>
                    Please provide a reason for rejecting this payment. This will be recorded for audit purposes.
                </p>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Rejection Reason <span class="text-danger">*</span></label>
                    <textarea id="rejectReasonInput"
                        class="form-control"
                        rows="3"
                        placeholder="e.g. Cheque bounced, account number mismatch, insufficient proof of payment…"
                        style="resize: vertical;"></textarea>
                    <div id="rejectReasonError" class="text-danger small mt-1" style="display:none;">
                        <i class="fa-light fa-exclamation-circle me-1"></i>Rejection reason is required.
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f1f5f9;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa-light fa-arrow-left me-1"></i> Cancel
                </button>
                <button type="button" id="confirmRejectBtn" class="btn"
                    style="background:#dc2626; color:#fff; border:none;">
                    <i class="fa-light fa-times-circle me-1"></i> Confirm Rejection
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     Document Preview Modal  (Accounts view)
     ═══════════════════════════════════════════════════════ -->
<div class="modal fade" id="accDocumentModal" tabindex="-1" aria-labelledby="accDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="accDocumentModalLabel">
                    <i class="fa-light fa-file-image me-2"></i>Transaction Documents
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="accDocumentModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

    <script>
        const BASE_URL = '<?= $GLOBALS['base_url'] ?>';

        // ─── Document Modal ───────────────────────────────────────────────────
        function openDocumentModal(paymentId) {
            const modal = new bootstrap.Modal(document.getElementById('accDocumentModal'));
            const body  = document.getElementById('accDocumentModalBody');

            body.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Loading documents…</p>
                </div>`;
            modal.show();

            fetch(BASE_URL + '/get-transaction-attachments?payment_id=' + paymentId)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        body.innerHTML = `<div class="alert alert-warning">${data.message}</div>`;
                        return;
                    }
                    const vsBadges = {
                        'verified': '<span class="badge bg-success"><i class="fa-light fa-check-circle me-1"></i>Verified</span>',
                        'rejected': '<span class="badge bg-danger"><i class="fa-light fa-times-circle me-1"></i>Rejected</span>',
                        'pending':  '<span class="badge bg-warning text-dark"><i class="fa-light fa-clock me-1"></i>Pending</span>',
                    };
                    const vsBadge = vsBadges[data.verification_status] || vsBadges['pending'];

                    let html = `
                        <div class="d-flex flex-wrap gap-3 mb-3 align-items-center">
                            <div><strong>Payment ID:</strong> #${data.payment_id}</div>
                            <div><strong>Type:</strong> ${(data.payment_type||'').replace(/_/g,' ').toUpperCase()}</div>
                            <div><strong>Amount:</strong> TK.${parseFloat(data.amount||0).toLocaleString('en-BD',{minimumFractionDigits:2})}</div>
                            <div><strong>Date:</strong> ${data.payment_date||'-'}</div>
                            <div><strong>Status:</strong> ${vsBadge}</div>
                            ${data.verified_by_name ? '<div><strong>Verified By:</strong> '+data.verified_by_name+'</div>' : ''}
                            ${data.rejection_reason ? '<div class="w-100"><div class="alert alert-danger py-2 mb-0"><strong>Rejection Reason:</strong> '+data.rejection_reason+'</div></div>' : ''}
                        </div><hr>`;

                    if (!data.files || data.files.length === 0) {
                        html += `<div class="alert alert-info"><i class="fa-light fa-info-circle me-1"></i>No documents attached to this transaction.</div>`;
                    } else {
                        html += `<div class="row g-3">`;
                        data.files.forEach((filePath, idx) => {
                            const fullUrl  = BASE_URL + '/public/' + filePath;
                            const ext      = filePath.split('.').pop().toLowerCase();
                            const isImage  = ['jpg','jpeg','png','gif','webp'].includes(ext);
                            const isPDF    = ext === 'pdf';
                            const fileName = filePath.split('/').pop();
                            if (isImage) {
                                html += `
                                <div class="col-md-4 col-sm-6">
                                    <div class="card h-100">
                                        <a href="${fullUrl}" target="_blank">
                                            <img src="${fullUrl}" class="card-img-top"
                                                 style="height:200px;object-fit:contain;background:#f8f9fa;padding:8px;"
                                                 alt="Document ${idx+1}"
                                                 onerror="this.src='${BASE_URL}/public/assets/img/no-image.png'">
                                        </a>
                                        <div class="card-body p-2 d-flex justify-content-between align-items-center">
                                            <small class="text-muted text-truncate" style="max-width:70%" title="${fileName}">${fileName}</small>
                                            <a href="${fullUrl}" target="_blank" download class="btn btn-sm btn-outline-secondary">
                                                <i class="fa-light fa-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>`;
                            } else if (isPDF) {
                                html += `
                                <div class="col-md-6">
                                    <div class="card h-100 text-center p-3">
                                        <i class="fa-light fa-file-pdf" style="font-size:3rem;color:#dc3545"></i>
                                        <p class="mt-2 mb-2 text-truncate" title="${fileName}"><strong>${fileName}</strong></p>
                                        <div class="d-flex gap-2 justify-content-center">
                                            <a href="${fullUrl}" target="_blank" class="btn btn-sm btn-outline-danger">
                                                <i class="fa-light fa-eye me-1"></i>View PDF
                                            </a>
                                            <a href="${fullUrl}" download class="btn btn-sm btn-outline-secondary">
                                                <i class="fa-light fa-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>`;
                            } else {
                                html += `
                                <div class="col-md-4 col-sm-6">
                                    <div class="card h-100 text-center p-3">
                                        <i class="fa-light fa-file" style="font-size:3rem;color:#6c757d"></i>
                                        <p class="mt-2 mb-2 text-truncate" title="${fileName}">${fileName}</p>
                                        <a href="${fullUrl}" download class="btn btn-sm btn-outline-secondary">
                                            <i class="fa-light fa-download me-1"></i>Download
                                        </a>
                                    </div>
                                </div>`;
                            }
                        });
                        html += `</div>`;
                    }
                    body.innerHTML = html;
                })
                .catch(() => {
                    body.innerHTML = `<div class="alert alert-danger">Failed to load documents. Please try again.</div>`;
                });
        }

        // ─── Delete ──────────────────────────────────────
        // ─── Delete (SweetAlert confirm) ──────────────────────────────────────────
        function deleteTransaction(transactionId) {
            const currentBookingId = <?= $clientDetails['booking_id'] ?? 'null' ?>;
            Swal.fire({
                title: 'Delete Transaction?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fa-light fa-trash me-1"></i> Yes, Delete',
                cancelButtonText: 'Cancel',
            }).then(result => {
                if (result.isConfirmed) {
                    if (currentBookingId) {
                        window.location.href = BASE_URL + '/delete-transaction?payment_id=' + transactionId + '&booking_id=' + currentBookingId;
                    } else {
                        Swal.fire('Error', 'Missing booking information.', 'error');
                    }
                }
            });
        }

        // ─── Rejection modal ─────────────────────────────
        let _rejectPaymentId = null;
        function openRejectModal(paymentId) {
            _rejectPaymentId = paymentId;
            document.getElementById('rejectReasonInput').value = '';
            document.getElementById('rejectReasonError').style.display = 'none';
            new bootstrap.Modal(document.getElementById('rejectReasonModal')).show();
        }

        document.addEventListener('DOMContentLoaded', function () {
            const confirmRejectBtn = document.getElementById('confirmRejectBtn');
            if (confirmRejectBtn) {
                confirmRejectBtn.addEventListener('click', function () {
                    const reason = document.getElementById('rejectReasonInput').value.trim();
                    if (!reason) {
                        document.getElementById('rejectReasonError').style.display = 'block';
                        return;
                    }
                    verifyPayment(_rejectPaymentId, 'reject', reason);
                    bootstrap.Modal.getInstance(document.getElementById('rejectReasonModal')).hide();
                });
            }
        });

        // ─── Core verify/reject AJAX (with SweetAlert2) ────────────────────
        function verifyPayment(paymentId, action, rejectionReason = '') {
            // ── Pre-confirm for Verify action only
            if (action === 'verify') {
                Swal.fire({
                    title: 'Verify Payment?',
                    text: 'This will mark the payment as verified. This action cannot be undone.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#16a34a',
                    cancelButtonColor:  '#6c757d',
                    confirmButtonText:  '<i class="fa-light fa-check me-1"></i> Yes, Verify',
                    cancelButtonText:   'Cancel',
                }).then(result => {
                    if (result.isConfirmed) {
                        _doVerifyRequest(paymentId, action, rejectionReason);
                    }
                });
            } else {
                // Reject flows through the rejection-reason modal first, then calls here directly
                _doVerifyRequest(paymentId, action, rejectionReason);
            }
        }

        function _doVerifyRequest(paymentId, action, rejectionReason) {
            const cell = document.getElementById('ver-cell-' + paymentId);
            if (cell) cell.innerHTML = '<span class="text-muted small"><i class="fa-light fa-spinner fa-spin"></i> Processing…</span>';

            const body = new URLSearchParams({ payment_id: paymentId, action, rejection_reason: rejectionReason });

            fetch(BASE_URL + '/verify-payment', { method: 'POST', body })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        // Update verification badge live
                        if (cell) {
                            if (res.status === 'verified') {
                                cell.innerHTML = '<span class="badge bg-success ver-badge"><i class="fa-light fa-check-circle me-1"></i>Verified</span>';
                            } else {
                                cell.innerHTML = '<span class="badge bg-danger ver-badge"><i class="fa-light fa-times-circle me-1"></i>Rejected</span>';
                            }
                        }
                        // Update "Verified By" cell
                        const vbCell = document.getElementById('verified-by-' + paymentId);
                        if (vbCell && res.verified_by_name) {
                            vbCell.textContent = res.verified_by_name;
                        }
                        // Update status badge to Paid when verified
                        if (res.status === 'verified') {
                            const row = cell ? cell.closest('tr') : null;
                            if (row) {
                                const statusCell = row.querySelector('td:nth-child(10)');
                                if (statusCell) {
                                    statusCell.innerHTML = '<span class="badge bg-success">Paid</span>';
                                }
                            }
                        }
                        // SweetAlert2 success popup
                        Swal.fire({
                            icon: res.status === 'verified' ? 'success' : 'info',
                            title: res.status === 'verified' ? 'Payment Verified!' : 'Payment Rejected',
                            text:  res.message,
                            timer: 2500,
                            timerProgressBar: true,
                            showConfirmButton: false,
                        });
                    } else {
                        if (cell) cell.innerHTML = '<span class="badge bg-warning text-dark"><i class="fa-light fa-clock me-1"></i>Pending</span>';
                        Swal.fire({
                            icon: 'error',
                            title: 'Action Failed',
                            text:  res.message || 'Something went wrong. Please try again.',
                        });
                    }
                })
                .catch(() => {
                    if (cell) cell.innerHTML = '<span class="badge bg-warning text-dark"><i class="fa-light fa-clock me-1"></i>Pending</span>';
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text:  'Could not reach the server. Please check your connection and try again.',
                    });
                });
        }

        // ─── SweetAlert2 helper (replaces old toast) ───────────────────────
        function showVerificationToast(msg, type) {
            toast.style.cssText = 'top:20px;right:20px;z-index:9999;min-width:280px;';
            toast.innerHTML = '<i class="fa-light fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + ' me-2"></i>' + msg;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
    </script>

    <style>
        .card__icon span {
            color: var(--clr-theme-primary);
            background: rgba(108, 95, 252, 0.1);
            width: 40px; height: 40px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; font-size: 16px;
        }
        .card__title { font-size: 1.25rem; }

        /* Verify / Reject inline buttons */
        .btn-verify {
            background: #dcfce7; color: #15803d;
            border: 1px solid #86efac;
            font-size: 11px; padding: 3px 8px; border-radius: 6px;
            font-weight: 600; white-space: nowrap;
            transition: background .15s;
        }
        .btn-verify:hover { background: #16a34a; color: #fff; }

        .btn-reject {
            background: #fee2e2; color: #b91c1c;
            border: 1px solid #fca5a5;
            font-size: 11px; padding: 3px 8px; border-radius: 6px;
            font-weight: 600; white-space: nowrap;
            transition: background .15s;
        }
        .btn-reject:hover { background: #dc2626; color: #fff; }

        .ver-toast { animation: slideInRight .3s ease; }
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(40px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        @media (min-width: 975px) {
            .dataTables_wrapper .dataTables_filter input[type=search] {
                border: 1px solid var(--clr-border-1); color: var(--clr-text-body);
                padding: 0 10px; margin-inline-start: 10px;
                height: 36px; border-radius: 6px; width: 380px;
            }
        }
        @media (max-width: 975px) {
            .card__wrapper .card__body h3 { font-size: 1.3rem; }
            .card__title { font-size: 1rem; }
        }
        @media (max-width: 768px) { .btn { font-size: 14px; padding: 6px 24px; } }
        @media (min-width: 575px) { .position { justify-content: end !important; } }
        @media (max-width: 480px) {
            .btn { font-size: 16px; padding: 6px 21px; }
            .position { justify-content: start !important; }
        }
        @media (max-width: 400px) { .btn { font-size: 14px; padding: 6px 14px; } }
    </style>
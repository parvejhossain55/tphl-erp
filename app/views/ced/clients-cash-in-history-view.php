<?php
$page_title = "Clients Cash in History";
$page = 'Clients';
$show_breadcrumb = true;

// Check if there is an error message
if (isset($error_message) && !empty($error_message)):
?>
    <div class="row">
        <div class="col-xxl-12 mb-4">
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="fa-light fa-exclamation-triangle fa-2x me-3"></i>
                <div>
                    <h4 class="alert-heading mb-1">Error Loading Transaction History</h4>
                    <p class="mb-0"><?= htmlspecialchars($error_message) ?></p>
                </div>
            </div>
            <a href="<?= $GLOBALS['base_url'] ?>/clients-cashin-view" class="btn btn-primary">
                <i class="fa-light fa-arrow-left me-2"></i> Back to Clients
            </a>
        </div>
    </div>
<?php else: ?>

<?php
// Check if land share is completed (enables Add Transaction for dev charges & EMI)
$accountModel = new AccountClient();
$isLandShareCompleted = $paymentSummary['is_land_share_completed'] ?? false;
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
                
                <div class="mt-3 d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#transferNoteModal">
                        <i class="fa-light fa-exchange-alt me-1"></i> Transfer Note
                    </button>

                    <?php if (isset($clientDetails['transfer_status']) && $clientDetails['transfer_status'] == 1): ?>
                        <span class="badge bg-warning text-dark">Transferred</span>
                    <?php else: ?>
                        <span class="badge bg-success">Current</span>
                    <?php endif; ?>
                    
                    <?php if (isset($refundDetails) && $refundDetails['status'] === 'approved'): ?>
                        <span class="badge bg-danger"><i class="fa-light fa-undo me-1"></i>Refund Approved</span>
                        <?php if ($refundPaidAmount > 0): ?>
                            <span class="badge" style="background:#2d8cf0; color:#fff;"><i class="fa-light fa-money-bill-transfer me-1"></i>Refunded Amount: TK.<?= number_format($refundPaidAmount, 2) ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <?php
        $devTarget = (float)($paymentSummary['flat_development_charges'] ?? 0);
        $devPaid   = (float)($paymentSummary['development_charge_paid']  ?? 0);
        $devRemain = max(0, $devTarget - $devPaid);
    ?>
    <div class="row">

        <!-- ROW 1: Agreement Overview -->
        <!-- 1. Total Value -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-money-bill"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Total Value</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['total_price'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 2. Booking Money -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-hand-holding-usd"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Booking Money</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['booking_money'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 3. Discount Amount -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-tag"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Discount Amount</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title text-success">TK.<?= number_format($paymentSummary['discount_amount'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 4. Registration Money -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-file-certificate"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Registration Money</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['registration_paid'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROW 2: Land Share & Dev Charges -->
        <!-- 5. Total Land Share -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-landmark"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Total Land Share</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['total_land_share'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 6. Land Share Paid -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-hand-holding-usd"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Land Share Paid</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['total_land_share_paid'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 7. Remaining Land Share -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-scale-balanced"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Remaining Land Share</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['remaining_land_share'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 8. Development Charges -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-wrench"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Development Charges</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($devTarget, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROW 3: Dev Status & Construction -->
        <!-- 9. Dev Charges Paid -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-hammer"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Dev. Charges Paid</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($devPaid, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 10. Dev Charges Remaining -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span style="color:<?= $devRemain > 0 ? '#e74c3c' : '#27ae60' ?>;"><i class="fa-light fa-hourglass-end"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Dev. Charges Remaining</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title" style="color:<?= $devRemain > 0 ? '#e74c3c' : '#27ae60' ?>;">
                                <?php if ($devRemain <= 0): ?>
                                    Fully Paid ✓
                                <?php else: ?>
                                    TK.<?= number_format($devRemain, 2) ?>
                                <?php endif; ?>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 11. Total Construction Fee -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-building"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Total Construction Fee</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['construction_fee'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 12. Construction Fee Paid -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-money-check-dollar"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Construction Fee Paid</h6>
                        <div class="d-flex flex-column align-items-start">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['construction_fee_paid_after_service'] ?? 0, 2) ?></h4>
                            <?php if (!empty($paymentSummary['unverified_emi_amount']) && $paymentSummary['unverified_emi_amount'] > 0): ?>
                                <div style="margin-top: 4px;">
                                    <span class="badge bg-warning text-dark" style="font-size:11px; font-weight:600; padding:4px 8px;" title="Uploaded EMI payments awaiting verification">
                                        <i class="fa-light fa-clock"></i> +TK.<?= number_format($paymentSummary['unverified_emi_amount'], 2) ?> Pending Verification
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROW 4: CF Remaining & EMI Status -->
        <!-- 13. Remaining Construction Fee -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span style="color:#e74c3c;"><i class="fa-light fa-hourglass-half"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Remaining Construction Fee</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title" style="color:<?= ($paymentSummary['remaining_construction_fee'] ?? 0) <= 0 ? '#27ae60' : '#e74c3c' ?>;">
                                <?php if (($paymentSummary['remaining_construction_fee'] ?? 0) <= 0): ?>
                                    Fully Paid ✓
                                <?php else: ?>
                                    TK.<?= number_format($paymentSummary['remaining_construction_fee'], 2) ?>
                                <?php endif; ?>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 14. Per EMI Amount -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-calculator"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Per EMI Amount</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['per_emi_amount'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 15. Completed EMI -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-check-circle"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Completed EMI</h6>
                        <div class="d-flex flex-column align-items-start">
                            <h4 class="card__title"><?= $paymentSummary['completed_emi'] ?? 0 ?> / <?= $paymentSummary['total_emi'] ?? 0 ?></h4>
                            <?php if (!empty($paymentSummary['unverified_emi_count']) && $paymentSummary['unverified_emi_count'] > 0): ?>
                                <div style="margin-top: 4px;">
                                    <span class="badge bg-warning text-dark" style="font-size:11px; font-weight:600; padding:4px 8px;" title="EMI payments awaiting verification">
                                        <i class="fa-light fa-clock"></i> +<?= $paymentSummary['unverified_emi_count'] ?> EMI Pending Verification
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 16. Service Charge Rate -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span style="color:#e67e22;"><i class="fa-light fa-percent"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Service Charge Rate</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <?php $svcRate = (float)($paymentSummary['service_charge_per_emi'] ?? 0); ?>
                            <h4 class="card__title" style="color:#e67e22;"><?= number_format($svcRate, 0) ?>% <small style="font-size:13px;color:#999;">per EMI</small></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROW 5: Revenue & Meta -->
        <!-- 17. Total Service Fee Collected -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span style="color:#8e44ad;"><i class="fa-light fa-hand-holding-dollar"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Total Service Fee Collected</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title" style="color:#8e44ad;">TK.<?= number_format($paymentSummary['total_service_fee'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 18. Net Received -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-sack-dollar"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Net Received</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['net_received'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 19. Total Due -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span style="color:#e74c3c;"><i class="fa-light fa-file-invoice-dollar"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Total Due</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title" style="color:<?= ($paymentSummary['total_due'] ?? 0) <= 0 ? '#27ae60' : '#e74c3c' ?>;">
                                <?php if (($paymentSummary['total_due'] ?? 0) <= 0): ?>
                                    All Clear ✓
                                <?php else: ?>
                                    TK.<?= number_format($paymentSummary['total_due'], 2) ?>
                                <?php endif; ?>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 20. Last Payment -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-calendar-check"></i></span></div>
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

        <?php if (($paymentSummary['total_bulk_emi_discount'] ?? 0) > 0): ?>
        <!-- 21. Bulk EMI Discount (conditional) -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span style="color:#6c5ffc;"><i class="fa-light fa-tags"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Bulk EMI Discount</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title" style="color:#6c5ffc;">
                                TK.<?= number_format($paymentSummary['total_bulk_emi_discount'], 2) ?>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>


    <!-- Outstanding EMI Due Alert -->
    <?php
        $totalEmiDue = $paymentSummary['total_emi_due'] ?? 0;
        $outstandingDues = $accountModel->getOutstandingEmiDues($clientDetails['booking_id'] ?? 0);
    ?>
    <?php if ($totalEmiDue > 0): ?>
    <div class="col-xxl-12 mb-3">
        <div class="d-flex align-items-center justify-content-between mb-0 p-3"
             style="border-radius:10px;background:rgba(108,95,252,0.1);border:1px solid rgba(108,95,252,0.25);box-shadow:0 2px 10px rgba(108,95,252,0.1);">
            <div>
                <i class="fa-light fa-info-circle me-2" style="color:#6c5ffc;"></i>
                <strong style="color:#6c5ffc;">Outstanding EMI Due: TK.<?= number_format($totalEmiDue, 2) ?></strong>
                &mdash; <?= count($outstandingDues) ?> EMI payment(s) have unpaid balances.
                <small class="d-block mt-1 text-muted">This will be <strong>automatically cleared</strong> from the next EMI payment (oldest due first).</small>
            </div>
            <a href="<?= $GLOBALS['base_url'] ?>/ced-add-transaction-form?booking_id=<?= $clientDetails['booking_id'] ?>"
               class="btn btn-sm text-white" style="background:#6c5ffc;border-color:#6c5ffc;">
                <i class="fa-light fa-plus me-1"></i> Add EMI Payment
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Transaction History Table -->
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="card__header">
                <h5 class="card__title mb-2">Transaction History</h5>
            </div>
            <div class="col-xxl-12 mb-4">
                    <?php
                        $db = $accountModel->getDb();
                        $stmt = $db->prepare("SELECT id FROM client_refund_requests WHERE booking_id = ? AND status != 'rejected'");
                        $bId = $clientDetails['booking_id'];
                        $stmt->bind_param('i', $bId);
                        $stmt->execute();
                        $hasRefund = $stmt->get_result()->num_rows > 0;
                    ?>
                    <?php if ($hasRefund): ?>
                    <div class="mt-2 alert alert-warning d-flex align-items-center mb-3 border-warning" style="background-color: #fff3cd; color: #856404; width: 100%;" role="alert">
                        <div>
                            <strong>Refund in Progress:</strong> A refund has been initiated for this booking. As a result, the system is no longer accepting new cash-in transactions.
                        </div>
                    </div>
                    <?php endif; ?>

                <div class="mt-3 d-flex gap-2 justify-content-end">
                    <?php if ($hasRefund): ?>
                        <button class="btn btn-secondary mb-3" disabled title="A refund has been requested for this booking">
                            <i class="fa-light fa-plus me-2"></i> Add Transaction
                        </button>
                    <?php elseif ($isLandShareCompleted): ?>
                        <a href="<?= $GLOBALS['base_url'] ?>/ced-add-transaction-form?booking_id=<?= $clientDetails['booking_id'] ?>" class="btn btn-primary mb-3">
                            <i class="fa-light fa-plus me-2"></i> Add Transaction
                        </a>
                    <?php else: ?>
                        <button class="btn btn-secondary mb-3" disabled title="Land share must be completed before adding transactions">
                            <i class="fa-light fa-lock me-2"></i> Add Transaction (Requires Land Share)
                        </button>
                    <?php endif; ?>
                    <a href="<?= $GLOBALS['base_url'] ?>/client-statement/<?= $clientDetails['booking_id'] ?>" target="_blank" class="btn btn-primary mb-3">
                        <i class="fa-light fa-file-invoice me-2"></i> Get Statement
                    </a>
                    <a href="<?= $GLOBALS['base_url'] ?>/discount-statement/<?= $clientDetails['booking_id'] ?>" target="_blank" class="btn mb-3" style="background: linear-gradient(135deg, #6c5ffc, #8b5cf6); color:#fff; border:none;">
                        <i class="fa-light fa-tags me-2"></i> Discount Statement
                    </a>
                    <?php
                        $canRefund = in_array((int)($_SESSION['user']['role_id'] ?? 0), [1, 3, 6, 8]);
                        $bookingIsActive = in_array($clientDetails['booking_status'] ?? '', ['booked', 'completed']);
                    ?>
                    <?php if ($canRefund && $bookingIsActive && !$hasRefund): ?>
                    <button type="button" class="btn btn-danger mb-3" data-bs-toggle="modal" data-bs-target="#refundRequestModal">
                        <i class="fa-light fa-rotate-left me-2"></i> Refund
                    </button>
                    <?php endif; ?>
                </div>

                <!-- Payment Type Filter -->
                <div class="mb-3 d-flex flex-wrap align-items-center gap-2">
                    <span class="text-muted me-1" style="font-size:13px;font-weight:600;">Filter:</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary payment-filter active" data-filter="all">All</button>
                    <button type="button" class="btn btn-sm btn-outline-primary payment-filter" data-filter="booking_money">Booking Money</button>
                    <button type="button" class="btn btn-sm btn-outline-info payment-filter" data-filter="registration">Registration</button>
                    <button type="button" class="btn btn-sm btn-outline-success payment-filter" data-filter="land_share">Land Share</button>
                    <button type="button" class="btn btn-sm btn-outline-warning payment-filter" data-filter="development_charge">Dev. Charge</button>
                    <button type="button" class="btn btn-sm btn-outline-danger payment-filter" data-filter="emi">EMI</button>
                    <button type="button" class="btn btn-sm btn-outline-dark payment-filter" data-filter="emi_due_settlement">EMI Settlement</button>
                </div>

                <div class="table__wrapper table-responsive">
                    <table class="table table-hover" id="cedTransactionTable">
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
                                <th>Bank</th>
                                <th>Trx ID</th>
                                <th>Status</th>
                                <th>Verification</th>
                                <th>Verified By</th>
                                <th>Document</th>
                                <th>Added By</th>
                                <th style="white-space:nowrap;">Invoice</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr data-payment-type="<?= htmlspecialchars($transaction['payment_type'] ?? '') ?>">
                                    <td><?php echo $transaction['formatted_date']; ?></td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo $transaction['installment_label']; ?>
                                        </span>
                                        <?php if (!empty($transaction['bulk_emi_discount']) && $transaction['bulk_emi_discount'] > 0): ?>
                                            <br><span class="badge mt-1" style="background:#6c5ffc;font-size:10px;">
                                                <i class="fa-light fa-tags me-1"></i>Discount: TK.<?= number_format($transaction['bulk_emi_discount'], 2) ?>
                                            </span>
                                            <?php if (!empty($transaction['bulk_pay_amount']) && $transaction['bulk_pay_amount'] > 0): ?>
                                                <br><span class="badge mt-1" style="background:#2d8cf0;font-size:10px;">
                                                    <i class="fa-light fa-layer-group me-1"></i>Against bulk pay: TK.<?= number_format($transaction['bulk_pay_amount'], 2) ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($transaction['payment_details'] ?? '-'); ?></td>
                                    <td>TK.<?php echo number_format($transaction['amount'], 2); ?></td>
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
                                        <?php if (!empty($transaction['bank_display_name'])): ?>
                                            <span class="text-primary" style="font-size:12px;">
                                                <i class="fa-light fa-building-columns me-1"></i><?= htmlspecialchars($transaction['bank_display_name']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo !empty($transaction['transaction_ref']) ? htmlspecialchars($transaction['transaction_ref']) : '<span class="text-muted">-</span>'; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $transaction['status_class']; ?>">
                                            <?php echo $transaction['status_text']; ?>
                                        </span>
                                    </td>
                                    <!-- VERIFICATION STATUS -->
                                    <td>
                                        <?php
                                            $vs = $transaction['verification_status'] ?? 'pending';
                                            if ($vs === 'verified'):
                                        ?>
                                            <span class="badge bg-success">
                                                <i class="fa-light fa-check-circle me-1"></i>Verified
                                            </span>
                                        <?php elseif ($vs === 'rejected'): ?>
                                            <span class="badge bg-danger">
                                                <i class="fa-light fa-times-circle me-1"></i>Rejected
                                            </span>
                                            <?php if (!empty($transaction['rejection_reason'])): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger ms-1 py-0 px-1"
                                                onclick="showRejectionReason('<?= htmlspecialchars(addslashes($transaction['rejection_reason']), ENT_QUOTES) ?>')"
                                                title="View rejection reason" style="font-size:11px;">
                                                <i class="fa-light fa-comment-exclamation"></i>
                                            </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">
                                                <i class="fa-light fa-clock me-1"></i>Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <!-- VERIFIED BY -->
                                    <td>
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
                                                class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1"
                                                style="white-space:nowrap;"
                                                onclick="openDocumentModal(<?= (int)$transaction['id'] ?>)"
                                                title="View <?= $docCount ?> document(s)">
                                                <i class="fa-light fa-file-image"></i>
                                                <span class="badge bg-primary rounded-pill" style="font-size:11px;"><?= $docCount ?></span>
                                                <?= $docCount > 1 ? 'Files' : 'File' ?>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>

                                        <?php if (!empty($transaction['penalty_paid']) && $transaction['penalty_paid'] > 0): ?>
                                            <div class="mt-1">
                                                <span class="badge bg-danger-subtle text-danger border border-danger fs-11" title="Late Penalty Paid">
                                                    <i class="fa-light fa-clock-rotate-left me-1"></i>Penalty: TK.<?= number_format($transaction['penalty_paid'], 2) ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($transaction['penalty_waived']) && $transaction['penalty_waived'] > 0): ?>
                                            <div class="mt-1">
                                                <span class="badge bg-warning-subtle text-dark border border-warning fs-11" title="Reason: <?= htmlspecialchars($transaction['waiver_reason'] ?? 'N/A') ?>">
                                                    <i class="fa-light fa-file-signature me-1"></i>Waived: TK.<?= number_format($transaction['penalty_waived'], 2) ?> (<?= ucfirst($transaction['waiver_status'] ?? 'pending') ?>)
                                                </span>
                                                <?php if (!empty($transaction['waiver_attachment'])): ?>
                                                    <a href="<?= $GLOBALS['base_url'] . '/' . htmlspecialchars($transaction['waiver_attachment']) ?>" target="_blank" class="btn btn-link btn-sm p-0 fs-11 ms-1 text-primary" title="View Waiver Proof">
                                                        <i class="fa-light fa-paperclip"></i> Proof
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                     <td>
                                         <?php echo !empty($transaction['created_by_name']) ? htmlspecialchars($transaction['created_by_name']) : '<span class="text-muted">System</span>'; ?>
                                         <?php 
                                             $isCsv = !empty($transaction['csv_import_log_id']) || in_array($transaction['payment_type'] ?? '', ['emi', 'emi_due_settlement', 'development_charge']);
                                         ?>
                                         <?php if ($isCsv): ?>
                                             <br><span class="badge bg-info-subtle text-info border border-info" style="font-size:10px; font-weight:600; margin-top:3px;" title="Uploaded via CSV Bulk Import">
                                                 <i class="fa-light fa-file-csv me-1"></i>CSV Import
                                             </span>
                                         <?php endif; ?>
                                     </td>
                                    
                                    <td>
                                        <?php
                                            $hasId = (!empty($transaction['id']) && strpos($transaction['id'], 'booking-') === false);
                                            $isVerified = (($transaction['verification_status'] ?? 'pending') === 'verified');
                                        ?>
                                        <?php if ($hasId && $isVerified): ?>
                                            <a href="<?= $GLOBALS['base_url'] ?>/payment-invoice?payment_id=<?= $transaction['id'] ?>"
                                               class="table__icon invoice"
                                               style="color:#fff;background-color:#6c5ffc;"
                                               title="View Invoice" target="_blank">
                                                <i class="fa-light fa-file-invoice"></i>
                                            </a>
                                        <?php elseif ($hasId): ?>
                                            <span class="table__icon disabled"
                                                  title="Invoice available after verification"
                                                  style="background-color:#fef3cd;color:#856404;cursor:not-allowed;">
                                                <i class="fa-light fa-file-invoice"></i>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- VIRTUAL EMI SCHEDULE (Yield to Maturity) -->
                <?php if (!empty($virtualEmiSchedule)): ?>
                <div class="mt-10" id="emi-schedule-section">
                    <h5 class="mb-3"><i class="fa-light fa-calendar-check me-2"></i>EMI Maturity Schedule</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" style="font-size: 14px;">
                            <thead style="background-color: #f8f9fa;">
                                <tr>
                                    <th>EMI No</th>
                                    <th>Projected/Actual Due Date</th>
                                    <th>Amount</th>
                                    <th>Paid Amount</th>
                                    <th>Due Amount</th>
                                    <th>Payment Status</th>
                                    <th>Maturity Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($virtualEmiSchedule as $emi): 
                                    $maturityBadge = '';
                                    switch($emi['maturity_status']) {
                                        case 'paid': $maturityBadge = '<span class="badge bg-success">Paid</span>'; break;
                                        case 'matured': $maturityBadge = '<span class="badge bg-info text-dark" style="background-color: #0dcaf0; color: #000;">Matured</span>'; break;
                                        case 'pending': $maturityBadge = '<span class="badge bg-warning text-dark">Pending (Current Month)</span>'; break;
                                        case 'due': $maturityBadge = '<span class="badge bg-danger">Due (Overdue)</span>'; break;
                                        case 'future': $maturityBadge = '<span class="badge bg-secondary">Future</span>'; break;
                                    }
                                    
                                    $statusBadge = '';
                                    if ($emi['status'] === 'paid') $statusBadge = '<span class="badge bg-success">Paid</span>';
                                    elseif ($emi['status'] === 'partially_paid') $statusBadge = '<span class="badge bg-warning text-dark">Partially Paid</span>';
                                    else $statusBadge = '<span class="text-muted">Unpaid</span>';
                                ?>
                                <tr>
                                    <td><strong>#<?= $emi['emi_no'] ?></strong> <?= $emi['is_virtual'] ? '<small class="text-muted">(Proj.)</small>' : '' ?></td>
                                    <td><?= date('d M, Y', strtotime($emi['due_date'])) ?></td>
                                    <td>TK. <?= number_format($emi['amount'], 2) ?></td>
                                    <td>TK. <?= number_format($emi['paid_amount'], 2) ?></td>
                                    <td>TK. <?= number_format($emi['due_amount'], 2) ?></td>
                                    <td><?= $statusBadge ?></td>
                                    <td><?= $maturityBadge ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

<!-- ═══════════════════════════════════════════════════════
     Document Preview Modal  (CED — Read-Only)
     ═══════════════════════════════════════════════════════ -->
<div class="modal fade" id="cedDocumentModal" tabindex="-1" aria-labelledby="cedDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cedDocumentModalLabel">
                    <i class="fa-light fa-file-image me-2"></i>Transaction Documents
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="cedDocumentModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <span class="text-muted small">
                    <i class="fa-light fa-lock me-1"></i>
                    Read-only view. Verification is managed by the Accounts module.
                </span>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== REJECTION REASON VIEW MODAL ===== -->
<div class="modal fade" id="viewRejectionReasonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:12px; overflow:hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #dc2626, #b91c1c); color:#fff; border:0;">
                <h5 class="modal-title">
                    <i class="fa-light fa-comment-exclamation me-2"></i>Rejection Reason
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger mb-0" style="border-radius:8px;">
                    <i class="fa-light fa-quote-left me-1 text-danger"></i>
                    <span id="rejectionReasonText"></span>
                    <i class="fa-light fa-quote-right ms-1 text-danger"></i>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f0f0f0;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

    <script>
        const BASE_URL_CED = '<?= $GLOBALS['base_url'] ?>';

        // ─── Show Rejection Reason Modal ───────────────────────────────────
        function showRejectionReason(reason) {
            document.getElementById('rejectionReasonText').textContent = reason;
            new bootstrap.Modal(document.getElementById('viewRejectionReasonModal')).show();
        }
        // ─── Document Modal ───────────────────────────────────────────────────
        function openDocumentModal(paymentId) {
            const modal = new bootstrap.Modal(document.getElementById('cedDocumentModal'));
            const body  = document.getElementById('cedDocumentModalBody');

            body.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Loading documents…</p>
                </div>`;
            modal.show();

            fetch(BASE_URL_CED + '/get-transaction-attachments?payment_id=' + paymentId)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        body.innerHTML = `<div class="alert alert-warning">${data.message}</div>`;
                        return;
                    }

                    const vsBadges = {
                        'verified': '<span class="badge bg-success"><i class="fa-light fa-check-circle me-1"></i>Verified</span>',
                        'rejected': '<span class="badge bg-danger"><i class="fa-light fa-times-circle me-1"></i>Rejected</span>',
                        'pending':  '<span class="badge bg-warning text-dark"><i class="fa-light fa-clock me-1"></i>Pending Verification</span>',
                    };
                    const vsBadge = vsBadges[data.verification_status] || vsBadges['pending'];

                    let html = `
                        <div class="d-flex flex-wrap gap-3 mb-3 align-items-center">
                            <div><strong>Payment ID:</strong> #${data.payment_id}</div>
                            <div><strong>Type:</strong> ${(data.payment_type || '').replace(/_/g,' ').toUpperCase()}</div>
                            <div><strong>Amount:</strong> TK.${parseFloat(data.amount || 0).toLocaleString('en-BD', {minimumFractionDigits:2})}</div>
                            <div><strong>Date:</strong> ${data.payment_date || '-'}</div>
                            <div><strong>Status:</strong> ${vsBadge}</div>
                            ${data.verified_by_name ? '<div><strong>Verified By:</strong> ' + data.verified_by_name + '</div>' : ''}
                            ${data.rejection_reason ? '<div class="w-100"><div class="alert alert-danger py-2 mb-0"><strong>Rejection Reason:</strong> ' + data.rejection_reason + '</div></div>' : ''}
                        </div><hr>`;

                    if (!data.files || data.files.length === 0) {
                        html += `<div class="alert alert-info"><i class="fa-light fa-info-circle me-1"></i>No documents attached to this transaction.</div>`;
                    } else {
                        html += `<div class="row g-3">`;
                        data.files.forEach((filePath, idx) => {
                            const fullUrl  = BASE_URL_CED + '/public/' + filePath;
                            const ext      = filePath.split('.').pop().toLowerCase();
                            const isImage  = ['jpg','jpeg','png','gif','webp'].includes(ext);
                            const isPDF    = ext === 'pdf';
                            const fileName = filePath.split('/').pop();

                            if (isImage) {
                                html += `
                                <div class="col-md-4 col-sm-6">
                                    <div class="card h-100">
                                        <a href="${fullUrl}" target="_blank" title="Open full size">
                                            <img src="${fullUrl}"
                                                 class="card-img-top"
                                                 style="height:200px;object-fit:contain;background:#f8f9fa;padding:8px;"
                                                 alt="Document ${idx+1}"
                                                 onerror="this.src='${BASE_URL_CED}/public/assets/img/no-image.png'">
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
                    body.innerHTML = `<div class="alert alert-danger">Failed to load documents. Check your connection and try again.</div>`;
                });
        }
    </script>

<!-- ===== REFUND REQUEST MODAL ===== -->
<div class="modal fade" id="refundRequestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= $GLOBALS['base_url'] ?>/client-refund-store" method="POST" enctype="multipart/form-data">
                <div class="modal-header" style="background: linear-gradient(135deg, #dc2626, #b91c1c); color:#fff; border:0;">
                    <h5 class="modal-title">
                        <i class="fa-light fa-rotate-left me-2"></i>Request Refund
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="booking_id" value="<?= $clientDetails['booking_id'] ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Refund Amount (৳) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="1" name="refund_amount" class="form-control" required placeholder="Enter refund amount">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required placeholder="Provide reason for refund..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Upload Document <span class="text-muted">(Optional)</span></label>
                        <input type="file" name="document" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                        <small class="text-muted">Allowed types: jpg, png, pdf, doc, docx. Max size: 5MB.</small>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #f0f0f0;">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

    <style>
        .card__icon span {
            color: var(--clr-theme-primary);
            background: rgba(108, 95, 252, 0.1);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            text-align: center;
            justify-content: center;
            align-items: center;
            font-size: 16px;
        }

        .card__title {
            font-size: 1.25rem;
        }

        @media (min-width: 975px) {
            .dataTables_wrapper .dataTables_filter input[type=search] {
                border: 1px solid var(--clr-border-1);
                color: var(--clr-text-body);
                padding: 0 10px;
                margin-inline-start: 10px;
                height: 36px;
                border-radius: 6px;
                width: 380px;
            }
        }

        @media (max-width: 975px) {
            .card__wrapper .card__body h3 {
                font-size: 1.3rem;
            }

            .card__title {
                font-size: 1rem;
            }
        }

        @media (max-width: 768px) {
            .btn {
                font-size: 14px;
                padding: 6px 24px;
            }
        }

        @media (min-width: 575px) {
            .position {
                justify-content: end !important;
            }
        }

        @media (max-width: 480px) {
            .btn {
                font-size: 16px;
                padding: 6px 21px;
            }

            .position {
                justify-content: start !important;
            }
        }

        @media (max-width: 400px) {
            .btn {
                font-size: 14px;
                padding: 6px 14px;
            }
        }

        /* Filter button active states */
        .payment-filter.active { font-weight: 600; }
        .payment-filter[data-filter="all"].active { background:#6c757d; color:#fff; border-color:#6c757d; }
        .payment-filter[data-filter="booking_money"].active { background:#0d6efd; color:#fff; border-color:#0d6efd; }
        .payment-filter[data-filter="registration"].active { background:#0dcaf0; color:#fff; border-color:#0dcaf0; }
        .payment-filter[data-filter="land_share"].active { background:#198754; color:#fff; border-color:#198754; }
        .payment-filter[data-filter="development_charge"].active { background:#ffc107; color:#000; border-color:#ffc107; }
        .payment-filter[data-filter="emi"].active { background:#dc3545; color:#fff; border-color:#dc3545; }
        .payment-filter[data-filter="emi_due_settlement"].active { background:#212529; color:#fff; border-color:#212529; }
        
        /* Fix SweetAlert z-index behind bootstrap modals */
        .swal2-container { z-index: 99999 !important; }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var filters = document.querySelectorAll('.payment-filter');
        var rows = document.querySelectorAll('#cedTransactionTable tbody tr[data-payment-type]');

        filters.forEach(function(btn) {
            btn.addEventListener('click', function() {
                filters.forEach(function(b) { b.classList.remove('active'); });
                btn.classList.add('active');

                var filter = btn.getAttribute('data-filter');
                rows.forEach(function(row) {
                    if (filter === 'all' || row.getAttribute('data-payment-type') === filter) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
    });
    
    function openEmiFollowupModal(bookingId, emiNo) {
        $('#followupBookingId').val(bookingId);
        $('#followupEmiNo').val(emiNo);
        $('#followupNoteInput').val('');
        $('#followupHistoryContainer').html('<div class="text-center text-muted py-4">Loading history...</div>');
        
        var modal = new bootstrap.Modal(document.getElementById('emiFollowupModal'));
        modal.show();
        
        loadEmiFollowups(bookingId, emiNo);
    }
    
    function loadEmiFollowups(bookingId, emiNo) {
        $.ajax({
            url: '<?= $GLOBALS['base_url'] ?>/account-client/getEmiFollowups',
            type: 'GET',
            data: { booking_id: bookingId, emi_no: emiNo },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var html = '';
                    if (response.data.length === 0) {
                        html = '<div class="text-center text-muted py-4">No follow up notes yet.</div>';
                    } else {
                        response.data.forEach(function(note) {
                            html += '<div class="card mb-2 shadow-sm border-0 bg-light">';
                            html += '<div class="card-body p-3">';
                            html += '<div class="mb-2" style="font-size:12px; color:#475569;">';
                            if (note.activity_date) html += '<span><i class="fa-light fa-calendar me-1"></i>' + note.activity_date + '</span>';
                            if (note.activity_mode) html += '<span class="ms-3"><i class="fa-light fa-phone-volume me-1"></i>' + note.activity_mode + '</span>';
                            if (note.activity_reason) html += '<span class="ms-3"><i class="fa-light fa-bullseye me-1"></i>' + note.activity_reason + '</span>';
                            html += '</div>';
                            html += '<p class="mb-2" style="font-size: 14px;">' + (note.note || '') + '</p>';
                            html += '<div class="d-flex justify-content-between text-muted" style="font-size: 12px;">';
                            html += '<span><i class="fa-light fa-user me-1"></i> ' + (note.author_name || 'System') + '</span>';
                            html += '<span><i class="fa-light fa-clock me-1"></i> ' + (note.created_at_formatted || '') + '</span>';
                            html += '</div></div></div>';
                        });
                    }
                    $('#followupHistoryContainer').html(html);
                } else {
                    $('#followupHistoryContainer').html('<div class="text-danger p-3">Failed to load history.</div>');
                }
            },
            error: function() {
                $('#followupHistoryContainer').html('<div class="text-danger p-3">Error connecting to server.</div>');
            }
        });
    }
    
    function saveEmiFollowup() {
        var bookingId = $('#followupBookingId').val();
        var emiNo = $('#followupEmiNo').val();
        var note = $('#followupNoteInput').val().trim();
        var activityDate = $('#followupActivityDate').val();
        var activityMode = $('#followupActivityMode').val();
        var activityReason = $('#followupActivityReason').val();
        
        if (!note || !activityDate || !activityMode || !activityReason) {
            Swal.fire({
                icon: 'error',
                title: 'Fields Required',
                text: 'Please fill in all the required fields (*).',
            });
            return;
        }
        
        $.ajax({
            url: '<?= $GLOBALS['base_url'] ?>/account-client/saveEmiFollowup',
            type: 'POST',
            data: { 
                booking_id: bookingId, 
                emi_no: emiNo, 
                note: note,
                activity_date: activityDate,
                activity_mode: activityMode,
                activity_reason: activityReason
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Follow up note saved successfully.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    $('#followupNoteInput').val('');
                    loadEmiFollowups(bookingId, emiNo);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Failed',
                        text: response.message || "Failed to save note."
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Server Error',
                    text: 'Please try again later.'
                });
            }
        });
    }
    </script>

<?php endif; ?>

<div class="modal fade" id="transferNoteModal" tabindex="-1" aria-labelledby="transferNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="<?= $GLOBALS['base_url'] ?>/save-transfer-note">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="transferNoteModalLabel">Transfer Note</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="booking_id" value="<?= $clientDetails['booking_id'] ?? '' ?>">
                    <input type="hidden" name="source_page" value="ced">
                    <input type="hidden" name="transfer_status" value="<?= htmlspecialchars($clientDetails['transfer_status'] ?? 0) ?>">
                    
                    <div class="mb-3">
                        <label for="transfer_date" class="form-label">Transfer Date</label>
                        <input type="date" class="form-control" id="transfer_date" name="transfer_date" value="<?= htmlspecialchars($clientDetails['transfer_date'] ?? '') ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="transfer_note" class="form-label">Transfer Note</label>
                        <textarea class="form-control" id="transfer_note" name="transfer_note" rows="4"><?= htmlspecialchars($clientDetails['transfer_note'] ?? '') ?></textarea>
                    </div>
                    
                    <?php if (!empty($clientDetails['transfer_updated_by_name'])): ?>
                    <div class="text-muted small mt-2">
                        Last updated by: <strong><?= htmlspecialchars($clientDetails['transfer_updated_by_name']) ?></strong>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Note</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ===== EMI FOLLOW UP MODAL ===== -->
<div class="modal fade" id="emiFollowupModal" tabindex="-1" aria-labelledby="emiFollowupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emiFollowupModalLabel">EMI Follow Up Notes</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal"><i class="fa-solid fa-xmark-large"></i></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-5 border-end">
                        <h6 class="mb-3">Add New Note</h6>
                        <input type="hidden" id="followupBookingId">
                        <input type="hidden" id="followupEmiNo">
                        <div class="mb-3">
                            <label class="form-label fs-13 font-semibold text-muted">Date of Activity <span class="text-danger">*</span></label>
                            <input type="date" id="followupActivityDate" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fs-13 font-semibold text-muted">Mode of Activity <span class="text-danger">*</span></label>
                            <select id="followupActivityMode" class="form-select form-select-sm">
                                <option value="">Select Mode</option>
                                <option value="Phone Call">Phone Call</option>
                                <option value="WhatsApp">WhatsApp</option>
                                <option value="SMS">SMS</option>
                                <option value="Email">Email</option>
                                <option value="Meeting">Meeting</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fs-13 font-semibold text-muted">Reason for Activity <span class="text-danger">*</span></label>
                            <select id="followupActivityReason" class="form-select form-select-sm">
                                <option value="">Select Reason</option>
                                <option value="EMI Payment Follow-up">EMI Payment Follow-up</option>
                                <option value="Payment Reminder (General)">Payment Reminder (General)</option>
                                <option value="Development Charge Follow-up">Development Charge Follow-up</option>
                                <option value="Overdue Payment Follow-up">Overdue Payment Follow-up</option>
                                <option value="Handover / Registration Discussion">Handover / Registration Discussion</option>
                                <option value="Document Collection">Document Collection</option>
                                <option value="Client Query / Issue Resolution">Client Query / Issue Resolution</option>
                                <option value="Commitment Date Extension">Commitment Date Extension</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fs-13 font-semibold text-muted">Note <span class="text-danger">*</span></label>
                            <textarea id="followupNoteInput" class="form-control" rows="3" placeholder="Type your follow up note here..."></textarea>
                        </div>
                        <button type="button" class="btn btn-primary w-100" onclick="saveEmiFollowup()">Save Note</button>
                    </div>
                    <div class="col-md-7">
                        <h6 class="mb-3">Follow Up History</h6>
                        <div id="followupHistoryContainer" style="max-height: 300px; overflow-y: auto;">
                            <div class="text-center text-muted py-4">Loading history...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
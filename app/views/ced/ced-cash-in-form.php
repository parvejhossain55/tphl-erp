<?php
$page_title = "Add Transaction (CED)";
$page = 'Clients';
$addPage = false;
$show_breadcrumb = true;

// Get booking ID from URL or session
$bookingId = $_GET['booking_id'] ?? $clientDetails['id'] ?? null;

// Avoid instantiating the model directly here since it's passed from the controller
// Use variables passed from controller ($paymentSummary, $outstandingDues, $isShop)
if (!isset($paymentSummary)) {
    // Fallback if accessed directly (should not happen)
    $accountModel = new AccountClient();
    $paymentSummary = $accountModel->getPaymentSummaryByBookingId($bookingId);
    $outstandingDues = $accountModel->getOutstandingEmiDues($bookingId);
    $isShop = false;
}

$isLandShareCompleted  = $paymentSummary['is_land_share_completed'] ?? false;
$allBangladeshBanks    = function_exists('getBangladeshBanks') ? getBangladeshBanks() : [];
// Calculate ordinal suffix for EMI numbers
$getOrdinal = function($num) {
    if (!is_numeric($num) || $num <= 0) return '';
    if (!in_array(($num % 100), [11, 12, 13])) {
        switch ($num % 10) {
            case 1:  return $num . 'st';
            case 2:  return $num . 'nd';
            case 3:  return $num . 'rd';
        }
    }
    return $num . 'th';
};

$nextEmiNum = (int)($paymentSummary['next_emi_no'] ?? 0);
if ($nextEmiNum <= 0) {
    $nextEmiNum = ($paymentSummary['completed_emi'] ?? 0) + 1;
}
$nextEmiStr = $getOrdinal($nextEmiNum);

$dueEmiStr = '';
$dueNos = [];
if (!empty($outstandingDues)) {
    $dueNos = array_filter(array_column($outstandingDues, 'emi_no'));
    if (!empty($dueNos)) {
        $dueEmiStr = " (Due for " . implode(', ', array_map($getOrdinal, $dueNos)) . ")";
    }
}

$isRegistrationPaid    = $paymentSummary['is_registration_paid'] ?? false;
$devChargePaid         = $isShop ? ($paymentSummary['shop_service_charge_paid_total'] ?? 0) : ($paymentSummary['development_charge_paid_total'] ?? 0);
$flatDevCharges        = (float)($isShop ? ($paymentSummary['shop_service_charge_target'] ?? 0) : ($paymentSummary['flat_development_charges'] ?? 0));
$isDevChargeCompleted  = $isShop ? !empty($paymentSummary['is_shop_service_charge_completed']) : !empty($paymentSummary['is_dev_charge_completed']);
$devChargeLabel        = $isShop ? "Shop Service Charge" : "Development Charges";
$devChargeValue        = $isShop ? "shop_service_charge" : "development_charge";
// Shops can also have a Development Charge (flats.development_charges) — paid before Shop Service Charge
$shopDevTarget         = $isShop ? (float)($paymentSummary['flat_development_charges'] ?? 0) : 0;
$shopDevPaid           = $isShop ? (float)($paymentSummary['development_charge_paid_total'] ?? 0) : 0;
$shopDevRemaining      = max(0, $shopDevTarget - $shopDevPaid);
$perEmiAmount          = (float)($paymentSummary['per_emi_amount'] ?? 0);
$serviceChargeRate     = (float)($paymentSummary['service_charge_per_emi'] ?? 0);
$totalEmiDue           = (float)($paymentSummary['total_emi_due'] ?? 0);
if ($totalEmiDue <= 0 && !empty($outstandingDues)) {
    $totalEmiDue = (float)array_sum(array_column($outstandingDues, 'due_emi_amount'));
}

$hasOutstandingDues    = count($outstandingDues) > 0;
$totalBulkDiscount     = (float)($paymentSummary['total_bulk_emi_discount'] ?? 0);
$constructionFee       = (float)($paymentSummary['construction_fee'] ?? 0);
$emiPaidSoFar          = (float)($paymentSummary['emi_paid_total'] ?? 0);
$remainingCF           = max(0, $constructionFee - $emiPaidSoFar - $totalBulkDiscount);

// Booking Money & Registration / Land Share status
$agreementValue             = (float)($paymentSummary['agreement_value'] ?? 0);
$bookingMoneyPaid           = (float)($paymentSummary['booking_money_paid'] ?? 0); // verified only
$bookingMoneyTotal          = (float)($paymentSummary['booking_money_total'] ?? $bookingMoneyPaid); // all non-rejected entries
$effectiveBookingTarget     = ($agreementValue > 0) ? $agreementValue : 300000;
$formRemainingBooking       = isset($formRemainingBooking) ? (float)$formRemainingBooking : max(0, $effectiveBookingTarget - $bookingMoneyTotal);
$formRemainingRegistration  = isset($formRemainingRegistration) ? (float)$formRemainingRegistration : 0;
$allRegistrationAndLandshare = isset($allRegistrationAndLandshare) ? (float)$allRegistrationAndLandshare : 0;

$isBookingFullyPaid         = ($formRemainingBooking <= 0 && $bookingMoneyTotal > 0);
$remainingLandShare         = (float)($paymentSummary['remaining_land_share'] ?? 0);
$isLandShareCompleted       = $isShop
    ? (bool)($paymentSummary['is_land_share_completed'] ?? ($remainingLandShare <= 0))
    : ($formRemainingRegistration <= 0 && $isBookingFullyPaid);

$virtualEmiSchedule    = $virtualEmiSchedule ?? [];
$overduePenaltySum     = 0;
$overdueEmiCount       = 0;
$overdueEmiList        = [];
if (!empty($virtualEmiSchedule)) {
    foreach ($virtualEmiSchedule as $sch) {
        if (($sch['maturity_status'] ?? '') === 'due') {
            $overdueEmiCount++;
            $overduePenaltySum += (float)($sch['penalty_amount'] ?? 0);
            $overdueEmiList[] = [
                'emi_no'         => (int)($sch['emi_no'] ?? 0),
                'due_date'       => $sch['due_date'] ?? null,
                'amount'         => (float)($sch['amount'] ?? 0),
                'due_amount'     => (float)($sch['due_amount'] ?? 0),
                'overdue_days'   => (int)($sch['overdue_days'] ?? 0),
                'months_overdue' => (int)($sch['months_overdue'] ?? 0),
                'penalty_amount' => (float)($sch['penalty_amount'] ?? 0),
            ];
        }
    }
}
?>

<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="card__header mb-30">
                <h3 class="card__title">Add Transaction (CED) for Client:
                    <?= htmlspecialchars($clientDetails['client_name'] ?? '') ?></h3>
                <p class="text-muted mt-1 mb-0">
                    <i class="fa-light fa-building me-1"></i>
                    <?= htmlspecialchars($clientDetails['project_name'] ?? '') ?> —
                    UNIT <?= htmlspecialchars($clientDetails['unit_name'] ?? '') ?> - FLOOR <?= htmlspecialchars($clientDetails['flat_no'] ?? '') ?>
                </p>
            </div>

            <div class="card__body">

                <!-- Info Alert -->
                <div class="alert alert-info alert-dismissible fade show mb-4 d-flex align-items-center" role="alert">
                    <div>
                        <strong>CED Payment Entry</strong>
                        Transactions submitted here will be marked as <em>Pending Verification</em>.
                        The Accounts team must verify them before they appear in fiscal reports.
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>

                <?php if (!$isBookingFullyPaid && $bookingMoneyTotal <= 0): ?>
                    <div class="alert alert-warning alert-dismissible fade show mb-4 d-flex align-items-center" role="alert">
                        <div>
                            <strong>Booking Money Pending:</strong>
                            Booking Money of <strong>TK.<?= number_format($effectiveBookingTarget, 2) ?></strong> has not been recorded yet.
                            Select <em>"Booking Money"</em> below to record the initial booking payment.
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php elseif (!$isBookingFullyPaid && $bookingMoneyTotal > 0): ?>
                    <div class="alert alert-warning alert-dismissible fade show mb-4 d-flex align-items-center" role="alert">
                        <div>
                            <strong>Booking Money Partially Paid:</strong>
                            Paid <strong>TK.<?= number_format($bookingMoneyTotal, 2) ?></strong> of <strong>TK.<?= number_format($effectiveBookingTarget, 2) ?></strong>.
                            Remaining balance: <strong class="text-danger">TK.<?= number_format($formRemainingBooking, 2) ?></strong>.
                            Select <em>"Booking Money"</em> below to pay the remaining balance.
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($isLandShareCompleted): ?>
                    <div class="alert alert-success alert-dismissible fade show mb-4 d-flex align-items-center" role="alert">
                        <div>
                            <strong>Land Share &amp; Registration Completed!</strong>
                            Total Land Share Paid: <strong>TK.<?= number_format($paymentSummary['total_land_share_paid'] ?? 0, 2) ?></strong>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($hasOutstandingDues): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-4 d-flex align-items-center" role="alert">
                      
                        <div>
                            <strong>Outstanding EMI Due: TK.<?= number_format($totalEmiDue, 2) ?></strong>
                            — Regular EMI payment will automatically settle dues and fill installments in order. Or select <em>"Advanced: Settle EMI Due"</em> to pay a specific due.
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <!-- Transaction Form -->
                <form action="<?= $GLOBALS['base_url'] ?>/ced-add-transaction" method="post" id="transactionForm" enctype="multipart/form-data">
                    <input type="hidden" name="booking_id" value="<?= $bookingId ?>">

                    <div class="row gy-20">
                        <!-- Payment Type -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Type: <span class="text-danger">*</span></label>
                            <select name="payment_type" class="form-control" id="paymentType" required>
                                <option value="">-- Select Payment Type --</option>

                                <!-- 1. Booking Money -->
                                <?php if ($isShop): ?>
                                    <?php if (!$isBookingFullyPaid && $formRemainingBooking > 0): ?>
                                        <option value="booking_money" data-details="false">
                                            Booking Money<?= $bookingMoneyTotal > 0 ? ' (Remaining: TK.' . number_format($formRemainingBooking, 2) . ' of TK.' . number_format($effectiveBookingTarget, 2) . ')' : ' (Target: TK.' . number_format($effectiveBookingTarget, 2) . ')' ?>
                                        </option>
                                    <?php else: ?>
                                        <option value="booking_money" disabled style="color:#6c757d;">
                                            Booking Money (Fully Paid — TK.<?= number_format($bookingMoneyTotal, 2) ?> / Target: TK.<?= number_format($effectiveBookingTarget, 2) ?>)
                                        </option>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if ($formRemainingBooking > 0 || !$isBookingFullyPaid): ?>
                                        <option value="booking_money" data-details="false">
                                            Booking Money<?= $bookingMoneyTotal > 0 ? ' (Remaining: TK.' . number_format($formRemainingBooking, 2) . ')' : '' ?>
                                        </option>
                                    <?php else: ?>
                                        <option value="booking_money" disabled style="color:#6c757d;">
                                            Booking Money (Fully Paid — TK.<?= number_format($bookingMoneyTotal, 2) ?>)
                                        </option>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <!-- 2. Registration & Mutation Cost -->
                                <?php if ($isShop): ?>
                                    <?php if (!$isLandShareCompleted && $remainingLandShare > 0): ?>
                                        <option value="registration" data-details="false">
                                            Registration (Remaining: TK.<?= number_format($remainingLandShare, 2) ?>)
                                        </option>
                                    <?php else: ?>
                                        <option value="registration" disabled style="color:#6c757d;">
                                            Registration (Completed ✓ — TK.0.00)
                                        </option>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if ($formRemainingRegistration > 0): ?>
                                        <option value="registration" data-details="false">
                                            Registration (Remaining: TK.<?= number_format($formRemainingRegistration, 2) ?>)
                                        </option>
                                        <option value="mutation_cost" data-details="false">
                                            Mutation Cost
                                        </option>
                                    <?php else: ?>
                                        <option value="registration" disabled style="color:#6c757d;">
                                            Registration (Completed ✓)
                                        </option>
                                        <option value="mutation_cost" disabled style="color:#6c757d;">
                                            Mutation Cost (Completed ✓)
                                        </option>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <!-- 3. Shop: Development Charge (only if set on the shop unit) -->
                                <?php if ($isShop && $shopDevTarget > 0): ?>
                                    <?php if ($shopDevRemaining > 0): ?>
                                        <option value="development_charge" data-details="false">
                                            Development Charge (Remaining: TK.<?= number_format($shopDevRemaining, 2) ?>)
                                        </option>
                                    <?php else: ?>
                                        <option value="development_charge" disabled style="color:#6c757d;">
                                            Development Charge — Fully Paid ✓ (TK.<?= number_format($shopDevPaid, 2) ?>)
                                        </option>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <!-- 4. Development Charges or Shop Service Charge -->
                                <?php if (!$isDevChargeCompleted): ?>
                                    <option value="<?= $devChargeValue ?>" data-details="false">
                                        <?= $devChargeLabel ?> (Remaining: TK.<?= number_format(max(0, $flatDevCharges - $devChargePaid), 2) ?>)
                                    </option>
                                <?php else: ?>
                                    <option value="<?= $devChargeValue ?>" disabled style="color:#6c757d;">
                                        <?= $devChargeLabel ?> — Fully Paid ✓ (TK.<?= number_format($devChargePaid, 2) ?>)
                                    </option>
                                <?php endif; ?>

                                <!-- 5. EMI -->
                                <?php if ($remainingCF > 0): ?>
                                    <option value="emi" data-details="true">
                                        <?= $nextEmiStr ?> EMI (Construction Fee)<?php if ($perEmiAmount > 0): ?> — Suggested: TK.<?= number_format($perEmiAmount, 2) ?><?php endif; ?><?php if ($totalEmiDue > 0): ?> — Outstanding Due: TK.<?= number_format($totalEmiDue, 2) ?><?= $dueEmiStr ?><?php endif; ?>
                                    </option>
                                <?php else: ?>
                                    <option value="emi" disabled style="color:#6c757d;">
                                        EMI (Construction Fee) — Fully Paid ✓ (TK.<?= number_format($constructionFee, 2) ?>)
                                    </option>
                                <?php endif; ?>

                                <!-- 6. Settle EMI Due — Advanced fallback (only shown when outstanding dues exist) -->
                                <?php if ($hasOutstandingDues): ?>
                                    <option value="emi_due_settlement" data-details="false">
                                        ⚙ Advanced: Settle <?= !empty($dueNos) ? implode(', ', array_map($getOrdinal, $dueNos)) . ' ' : '' ?>EMI Due (Outstanding: TK.<?= number_format($totalEmiDue, 2) ?>)
                                    </option>
                                <?php endif; ?>

                                <!-- 7. Others (Extra / Incidental Charges — outside flat/shop price) -->
                                <option value="others" data-details="false" style="color: #0d6efd; font-weight: 600;">
                                    Others (Extra / Incidental Charges — Excluded from <?= $isShop ? 'Shop' : 'Flat' ?> Price)
                                </option>

                            </select>

                            <!-- Help text -->
                            <small class="text-muted mt-2 d-block">
                                <?php if (!$isBookingFullyPaid): ?>
                                    <span class="text-danger">
                                        <i class="fa-light fa-circle-exclamation"></i>
                                        Booking Money balance due: <strong>TK.<?= number_format($formRemainingBooking, 2) ?></strong>
                                    </span>
                                <?php elseif (!$isLandShareCompleted): ?>
                                    <span class="text-warning">
                                        <i class="fa-light fa-triangle-exclamation"></i>
                                        Registration balance due: TK.<?= number_format($isShop ? $remainingLandShare : $formRemainingRegistration, 2) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-success">
                                        <i class="fa-light fa-circle-check"></i>
                                        Ready for <?= strtolower($devChargeLabel) ?> and construction fee payments
                                    </span>
                                <?php endif; ?>
                            </small>
                        </div>

                        <!-- Other Purpose (shown only when Others is selected) -->
                        <div class="col-md-6" id="otherPurposeContainer" style="display: none;">
                            <div class="from__input-box">
                                <label class="form__input-title">Payment Purpose / Description <span>*</span></label>
                                <input type="text" name="other_purpose" id="otherPurpose" class="form-control"
                                    placeholder="e.g. Utility Connection, Modification, Extra Work">
                                <small class="text-muted">Specify the purpose for this incidental payment (shows in ledger &amp; receipt, not deducted from <?= $isShop ? 'shop' : 'flat' ?> price)</small>
                            </div>
                        </div>

                        <?php if (!$isShop): ?>
                        <!-- Total Booking Money (Shown only for Booking Money & Registration on Flats) -->
                        <div class="col-md-6" id="totalBookingMoneyContainer" style="display: none;">
                            <div class="from__input-box">
                                <label class="form__input-title">Total Booking Money (Target) <span>*</span></label>
                                <input type="number" name="total_booking_money" id="totalBookingMoneyField" class="form-control" step="1" min="0" value="<?= ($agreementValue > 0) ? $agreementValue : 300000 ?>">
                                <small class="text-muted">Default is 3,00,000. Updating this adjusts the booking money &amp; registration balances.</small>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Amount -->
                        <div class="col-md-6">
                            <div class="from__input-box">
                                <label class="form__input-title">Paid Amount <span>*</span></label>
                                <input type="number" name="amount" id="amount" class="form-control" step="1"
                                    min="1" required>
                                <small id="amountHelp" class="text-muted"></small>
                            </div>
                        </div>

                        <!-- EMI Info Panel (shown when EMI selected) -->
                        <div class="col-md-12 emi-fields" style="display: none;">
                            <div class="from__input-box p-3" style="background:#f8f9fa;border-radius:8px;border:1px solid #dee2e6;">
                                <label class="form__input-title">EMI Payment Info <small class="text-muted">(Smart Allocation)</small></label>
                                <div class="w-100 mt-2">
                                    <?php if ($totalEmiDue > 0): ?>
                                        <div class="d-block w-100 mb-2 p-2 bg-warning bg-opacity-10 border border-warning rounded">
                                            <div class="d-flex align-items-center mb-1">
                                                <span class="badge bg-warning text-dark"><i class="fa-light fa-exclamation-triangle me-1"></i>Outstanding Due</span>
                                                <strong class="text-danger ms-2">TK.<?= number_format($totalEmiDue, 2) ?></strong>
                                            </div>
                                            <div class="d-block text-muted small" style="line-height: 1.4;">
                                                — will be auto-cleared first from your payment (oldest due first)
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-block w-100 text-muted small p-2 bg-light border rounded">
                                        <?php if ($perEmiAmount > 0): ?>
                                            <div class="mb-1">Suggested EMI: <strong>TK.<?= number_format($perEmiAmount, 2) ?></strong>.</div>
                                            <?php if ($serviceChargeRate > 0): ?>
                                                <div class="mb-1">Service charge of <strong><?= number_format($serviceChargeRate, 2) ?>%</strong> applies on total amount.</div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <div class="mb-1">Enter any amount — the system will:</div>
                                        <ol class="mb-0 ps-3" style="line-height: 1.4;">
                                            <li>Clear outstanding dues (oldest first)</li>
                                            <li>Cover current installment</li>
                                            <li>Carry surplus to next installment(s)</li>
                                        </ol>
                                    </div>
                                </div>
                                <input type="hidden" name="due_emi_amount" id="dueEmiAmount" value="0">

                                <!-- EMI Discount Toggle -->
                                <div class="mt-3 p-3" style="background:rgba(108,95,252,0.07);border-radius:8px;border:1px solid rgba(108,95,252,0.2);">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="applyEmiDiscount" name="apply_emi_discount" value="1">
                                        <label class="form-check-label" for="applyEmiDiscount" style="color:#6c5ffc; font-weight:600;">
                                            <i class="fa-light fa-tags me-1"></i>Apply EMI Discount?
                                        </label>
                                    </div>
                                    <div id="emiDiscountSection" style="display:none;">
                                        <div class="row align-items-end">
                                            <div class="col-md-6">
                                                <input type="number" name="bulk_emi_discount" id="bulkEmiDiscount"
                                                       class="form-control" step="1" min="0" value="0"
                                                       placeholder="Enter discount amount">
                                            </div>
                                            <div class="col-md-6">
                                                <small class="text-muted">
                                                    Remaining CF after payment: <strong id="remainingCFAfterPayment">—</strong><br>
                                                    Max discount: <strong id="maxBulkDiscount">—</strong>
                                                </small>
                                            </div>
                                        </div>
                                        <small class="text-muted d-block mt-1">
                                            <i class="fa-light fa-info-circle me-1"></i>
                                            This discount will be recorded as a virtual payment (Discount EMI) and will not affect the cash balance.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Late Payment Penalty & Waiver Section (shown when overdue installments exist and EMI or Settle EMI Due selected) -->
                        <div class="col-md-12" id="penaltyWaiverWrapper" style="display: none;">
                            <div id="penaltyWaiverSection" class="p-3" style="background:rgba(220,53,69,0.05);border-radius:8px;border:1px solid rgba(220,53,69,0.2);">
                                <div class="d-block w-100 mb-2">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <label class="form__input-title mb-0 text-danger" style="line-height:1.4;">
                                            <i class="fa-light fa-clock-rotate-left me-1"></i>Late Payment Penalty<br>
                                            <small class="text-muted" style="font-size:0.85em;">(5% Compounded Monthly on Overdue EMIs)</small>
                                        </label>
                                        <span id="penaltyBadge" class="badge bg-danger mt-1" style="display:none; white-space:nowrap;">Overdue</span>
                                    </div>
                                </div>
                                
                                <div class="row align-items-center mt-2">
                                    <div class="col-12 col-md-7">
                                        <div class="d-block w-100 mb-2">
                                            <div class="d-block mb-1">
                                                <span class="text-muted small">Calculated Penalty:</span>
                                                <strong id="calculatedPenaltyText" class="text-danger fs-15 ms-2">TK. 0.00</strong>
                                            </div>
                                            <div id="penaltyTotalBreakdown" class="d-block fs-13 mt-2" style="display:none;">
                                                <div class="d-block mb-1">
                                                    <span class="text-dark" id="totalPayableLabel">Suggested Total:</span>
                                                    <strong id="totalPayableWithPenaltyText" class="text-primary fs-15 ms-1">TK. 0.00</strong>
                                                </div>
                                                <div class="d-block mt-2">
                                                    <button type="button" id="btnFillTotalWithPenalty" class="btn btn-sm btn-outline-primary py-1 px-2" style="font-size:12px;">
                                                        <i class="fa-light fa-plus-circle me-1"></i>Auto Fill Amount
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <input type="hidden" name="penalty_amount" id="penaltyAmount" value="0">
                                        <input type="hidden" name="penalty_paid" id="penaltyPaid" value="0">
                                    </div>
                                    <div class="col-12 col-md-5 mt-3 mt-md-0">
                                        <div class="d-block w-100">
                                            <div class="d-flex flex-wrap align-items-center justify-content-start justify-content-md-end gap-3 mb-2">
                                                <div class="form-check form-switch m-0">
                                                    <input class="form-check-input" type="checkbox" id="toggleNoPenalty" name="no_penalty" value="1">
                                                    <label class="form-check-label text-success fw-bold" for="toggleNoPenalty">Penalty Not Required</label>
                                                </div>
                                            </div>
                                            <div class="d-flex flex-wrap align-items-center justify-content-start justify-content-md-end gap-3">
                                                <div class="form-check form-switch m-0">
                                                    <input class="form-check-input" type="checkbox" id="toggleWaiver" name="apply_waiver" value="1">
                                                    <label class="form-check-label text-dark fw-bold" for="toggleWaiver">Request Penalty Waiver</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!empty($overdueEmiList)): ?>
                                <div class="mt-3 p-2 bg-white rounded border border-danger-subtle">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fs-12 text-muted fw-bold text-uppercase"><i class="fa-light fa-list-check me-1"></i>Overdue Breakdown (Oldest Due First):</span>
                                        <span class="badge bg-danger-subtle text-danger border border-danger fs-11"><?= count($overdueEmiList) ?> Overdue EMI(s)</span>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-borderless align-middle mb-0 fs-12">
                                            <thead>
                                                <tr class="text-muted border-bottom" style="font-size:11px;">
                                                    <th>Installment</th>
                                                    <th>Due Date</th>
                                                    <th>Remaining Due</th>
                                                    <th>Overdue Delay</th>
                                                    <th>Compounded Formula (5%)</th>
                                                    <th class="text-end">Late Penalty</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($overdueEmiList as $oItem): 
                                                    $oBase = (float)($oItem['amount'] > 0 ? $oItem['amount'] : $perEmiAmount);
                                                    $oMonths = (int)$oItem['months_overdue'];
                                                    $oFactor = pow(1.05, $oMonths) - 1;
                                                    $oPct = round($oFactor * 100, 4);
                                                ?>
                                                <tr>
                                                    <td class="fw-semibold text-dark">EMI #<?= $oItem['emi_no'] ?></td>
                                                    <td class="text-muted"><?= !empty($oItem['due_date']) ? date('d M Y', strtotime($oItem['due_date'])) : '—' ?></td>
                                                    <td class="text-danger fw-semibold">TK.<?= number_format($oItem['due_amount'], 2) ?></td>
                                                    <td>
                                                        <span class="badge bg-warning bg-opacity-20 text-warning-emphasis">
                                                            <?= $oItem['overdue_days'] ?> days (<?= $oMonths ?> mo)
                                                        </span>
                                                    </td>
                                                    <td class="font-monospace text-muted fs-11">
                                                        TK.<?= number_format($oBase, 0) ?> &times; [(1.05)<sup><?= $oMonths ?></sup> &minus; 1] = <?= $oPct ?>%
                                                    </td>
                                                    <td class="text-end text-danger fw-bold">TK.<?= number_format($oItem['penalty_amount'], 2) ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr class="border-top fw-bold">
                                                    <td colspan="5" class="text-end text-dark">Total Overdue Penalty:</td>
                                                    <td class="text-end text-danger fs-13">TK.<?= number_format($overduePenaltySum, 2) ?></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>

                                    <!-- Explanatory Note on Calculation -->
                                    <div class="mt-2 p-2 rounded bg-light border fs-12 text-muted">
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="fa-light fa-calculator text-primary me-2"></i>
                                            <strong class="text-dark">How Penalty is Calculated (5% Compounded Monthly):</strong>
                                        </div>
                                        <div class="ps-3" style="line-height: 1.5;">
                                            <div>&bull; <strong>Formula:</strong> <code>Base EMI &times; [(1 + 0.05)<sup>Months</sup> &minus; 1]</code> (Compounded monthly on EMI Base).</div>
                                            <div>&bull; <strong>Overdue Delay:</strong> Any day past a monthly maturity cycle enters the next month (e.g. 63 days = 2 months + 3 days &rarr; <strong>3 months</strong>; 49 days = 1 month + 19 days &rarr; <strong>2 months</strong>).</div>
                                            <div>&bull; <strong>Total Overdue Penalty:</strong> <?= implode(' + ', array_map(function($i){ return 'TK. ' . number_format($i['penalty_amount'], 2) . ' (EMI #' . $i['emi_no'] . ')'; }, $overdueEmiList)) ?> = <strong class="text-danger">TK. <?= number_format($overduePenaltySum, 2) ?></strong>.</div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Waiver Input Box -->
                                <div id="waiverFields" class="mt-3 p-3 bg-white" style="display:none;border-radius:6px;border:1px solid #ced4da;">
                                    <h6 class="text-primary fs-14 mb-2"><i class="fa-light fa-file-signature me-1"></i>Penalty Waiver Details (Requires Management Approval)</h6>
                                    <div class="row ">
                                        <div class="col-md-6">
                                            <label class="form-label fs-13 font-semibold">Waived Penalty Amount (TK.) <span class="text-danger">*</span></label>
                                            <input type="number" name="penalty_waived" id="penaltyWaived" class="form-control form-control-sm" step="0.01" min="0" value="0">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fs-13 font-semibold">Waiver Reason <span class="text-danger">*</span></label>
                                            <textarea name="waiver_reason" id="waiverReason" class="form-control form-control-sm" rows="2" placeholder="State valid business/client reason for waiver..."></textarea>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label fs-13 font-semibold">Approval Document / Attachment Reference <span class="text-danger">*</span></label>
                                            <input type="file" name="waiver_file" id="waiverFile" class="form-control form-control-sm" accept="image/*,application/pdf">
                                            <small class="text-muted fs-12">Attach approved paper, application letter or reference document (PDF, PNG, JPG)</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Settle EMI Due — parent payment selector (shown when emi_due_settlement selected) -->
                        <div class="col-md-12 settle-due-fields" style="display:none;">
                            <div class="from__input-box p-3" style="background:#fff3cd;border-radius:8px;border:1px solid #ffc107;">
                                <label class="form__input-title">Select EMI to Settle <span>*</span></label>
                                <select name="parent_payment_id" id="parentPaymentId" class="form-control">
                                    <option value="">-- Select Outstanding EMI --</option>
                                    <?php foreach ($outstandingDues as $due): ?>
                                        <option value="<?= $due['id'] ?>"
                                            data-due="<?= $due['due_emi_amount'] ?>">
                                            EMI #<?= $due['emi_no'] > 0 ? $due['emi_no'] : '?' ?>
                                            — Paid: TK.<?= number_format($due['amount'], 2) ?>
                                            — Due: TK.<?= number_format($due['due_emi_amount'], 2) ?>
                                            (<?= date('d M Y', strtotime($due['payment_date'])) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="settlement_type" value="emi_due_settlement">
                                <small class="text-muted d-block mt-1">Amount field will be auto-capped to the selected EMI's remaining due. Service charge applies on settled amount.</small>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="col-md-6">
                            <div class="from__input-box">
                                <label class="form__input-title">Payment Method <span>*</span></label>
                                <select name="payment_method" id="paymentMethod" class="form-select" required>
                                    <option value="cash">Cash</option>
                                    <option value="bank">Bank Transfer</option>
                                    <option value="online">Online Payment</option>
                                    <option value="cheque">Cheque</option>
                                </select>
                            </div>
                        </div>

                        <!-- Payment Date -->
                        <div class="col-md-6">
                            <div class="from__input-box">
                                <label class="form__input-title">Payment Date <span>*</span></label>
                                <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>"
                                    required>
                            </div>
                        </div>

                        <!-- Bank Fields (Visible only for bank/cheque) -->
                        <div class="col-md-6 bank-fields" style="display: none;">
                            <div class="from__input-box">
                                <label class="form__input-title">Transfer To / Company Bank <span>*</span></label>
                                <div class="input-group">
                                    <select name="bank_id" id="bankSelect" class="form-select">
                                        <option value="">-- Loading banks... --</option>
                                    </select>
                                    <button type="button" class="btn btn-outline-primary" id="addCompanyBankBtn" title="Add Company Bank"><i class="fa-light fa-plus"></i></button>
                                    <button type="button" class="btn btn-outline-secondary" id="editCompanyBankBtn" title="Edit Selected Bank" style="display:none;"><i class="fa-light fa-pen-to-square"></i></button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 bank-fields" style="display: none;">
                            <div class="from__input-box">
                                <label class="form__input-title">Transfer From / Client Bank <span>*</span></label>
                                <select name="bank_name" id="clientBankSelect" class="form-select" style="width: 100%;">
                                    <option value="">-- Select Client Bank --</option>
                                    <?php foreach ($allBangladeshBanks as $bName): ?>
                                        <option value="<?= htmlspecialchars($bName) ?>"><?= htmlspecialchars($bName) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6 bank-fields" id="otherBankField" style="display: none;">
                            <div class="from__input-box">
                                <label class="form__input-title" id="customBankLabel">Specify Foreign / Other Bank Name <span>*</span></label>
                                <input type="text" name="custom_bank_name" id="customBankInput" class="form-control"
                                    placeholder="e.g. Emirates NBD, Chase Bank, Al Rajhi, etc.">
                            </div>
                        </div>

                        <div class="col-md-6 bank-fields" style="display: none;">
                            <div class="from__input-box">
                                <label class="form__input-title">Client Bank A/C No. (Optional)</label>
                                <input type="text" name="bank_account_no" class="form-control"
                                    placeholder="Account Number">
                            </div>
                        </div>

                        <!-- Cheque Details (Visible only for cheque) -->
                        <div class="col-md-6 cheque-fields" style="display: none;">
                            <div class="from__input-box">
                                <label class="form__input-title">Cheque Number <span>*</span></label>
                                <input type="text" name="cheque_no" class="form-control" placeholder="Cheque Number">
                            </div>
                        </div>

                        <!-- Online Details (Visible only for online) -->
                        <div class="col-md-6 online-fields" style="display: none;">
                            <div class="from__input-box">
                                <label class="form__input-title">Online Type <span>*</span></label>
                                <select name="online_type" class="form-select">
                                    <option value="bKash">bKash</option>
                                    <option value="Nagad">Nagad</option>
                                    <option value="Rocket">Rocket</option>
                                    <option value="Bank Card">Bank Card</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>

                        <!-- Transaction Reference -->
                        <div class="col-md-6">
                            <div class="from__input-box">
                                <label class="form__input-title">Transaction Reference</label>
                                <input type="text" name="transaction_ref" class="form-control"
                                    placeholder="Transaction ID/Reference">
                            </div>
                        </div>

                        <!-- Payment Status -->
                        <div class="col-md-6">
                            <div class="from__input-box">
                                <label class="form__input-title">Payment Status <span>*</span></label>
                                <select name="payment_status" class="form-select" required>
                                    <option value="paid">Paid</option>
                                    <option value="partially_paid" id="partiallyPaidOption">Partially Paid</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </div>
                        </div>

                        <!-- Handover Section -->
                        <div class="col-md-12 mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="enableHandover" value="1">
                                <label class="form-check-label fw-bold" for="enableHandover">
                                    Initiate Handover Approval?
                                </label>
                            </div>
                            <div class="from__input-box d-none" id="handoverWrapper">
                                <label class="form__input-title">Handover To <span class="text-danger">*</span></label>
                                <select name="handover_to" id="handoverTo" class="form-control select2">
                                    <option value="">-- Select Employee --</option>
                                    <?php if (!empty($managementUsers)): ?>
                                        <?php foreach ($managementUsers as $user): ?>
                                            <option value="<?= $user['id'] ?>">
                                                <?= htmlspecialchars($user['full_name']) ?> 
                                                (<?= htmlspecialchars($user['email'] ?? 'No Email') ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Proof of Payment Documents (multi-file upload) -->
                        <div class="col-md-12">
                            <div class="from__input-box">
                                <label class="form__input-title">
                                    <i class="fa-light fa-paperclip me-1"></i>Proof of Payment
                                    <span class="text-muted small ms-1">(JPG, PNG, WEBP, PDF — max 20 MB each)</span>
                                </label>
                                <input type="file" name="proof_files[]" id="proofFiles"
                                    class="form-control" multiple accept="image/*,application/pdf">
                                <div id="filePreview" class="d-flex flex-wrap gap-2 mt-2"></div>
                            </div>
                        </div>

                        <!-- Testing Mode Switch & Phone Alert -->
                        <div class="col-md-12">
                            <?php 
                                $clientPhone = trim($clientDetails['phone'] ?? ($clientDetails['phone_number'] ?? ''));
                                $isPhoneValid = (strlen($clientPhone) === 11 && preg_match('/^01[3-9]\d{8}$/', $clientPhone));
                            ?>
                            <?php if (!$isPhoneValid): ?>
                                <div class="alert alert-warning d-flex align-items-center justify-content-between p-2 mb-2 mt-2">
                                    <div class="small">
                                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                        <strong>Invalid Client Phone:</strong> Client phone (<?= htmlspecialchars($clientPhone ?: 'Empty') ?>) is missing or not a valid 11-digit mobile number.
                                    </div>
                                    <span class="badge bg-danger">SMS Notification Blocked</span>
                                </div>
                            <?php endif; ?>

                            <div class="form-check form-switch p-2 bg-light border rounded mt-2">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="send_sms" id="sendSms" value="true" checked>
                                <label class="form-check-label fw-semibold text-success" for="sendSms">
                                    <i class="fa-solid fa-envelope me-1"></i> Send SMS Notification
                                </label>
                            </div>
                            
                            <div class="form-check form-switch p-2 bg-light border rounded mt-2">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="is_testing_mode" id="isTestingMode" value="1">
                                <label class="form-check-label fw-semibold text-warning" for="isTestingMode">
                                    <i class="fa-solid fa-vial me-1"></i> Testing Mode (Skip SMS & Bypass Phone Validation)
                                </label>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="col-md-12">
                            <div class="submit__btn text-center mt-4 d-grid gap-2 d-md-block">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-save me-1"></i>Save Transaction
                                </button>
                                <a href="<?= $GLOBALS['base_url'] ?>/clients-cash-in-history-view/<?= $bookingId ?>"
                                    class="btn btn-danger">
                                    <i class="fa-solid fa-times me-1"></i>Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const paymentType = document.getElementById('paymentType');
        const paymentMethod = document.getElementById('paymentMethod');
        const dueEmiAmountField = document.getElementById('dueEmiAmount');
        const paymentStatusSelect = document.querySelector('select[name="payment_status"]');
        const amountField = document.getElementById('amount');
        const amountHelp = document.getElementById('amountHelp');
        const partiallyPaidOption = document.getElementById('partiallyPaidOption');

        const emiFields = document.querySelectorAll('.emi-fields');
        const bankFields = document.querySelectorAll('.bank-fields');
        const chequeFields = document.querySelectorAll('.cheque-fields');
        const onlineFields = document.querySelectorAll('.online-fields');

        // Bulk discount elements
        const applyEmiDiscount     = document.getElementById('applyEmiDiscount');
        const emiDiscountSection   = document.getElementById('emiDiscountSection');
        const bulkDiscountInput    = document.getElementById('bulkEmiDiscount');
        const remainingCFDisplay   = document.getElementById('remainingCFAfterPayment');
        const maxDiscountDisplay   = document.getElementById('maxBulkDiscount');
        const perEmiAmountVal      = <?= json_encode((float)$perEmiAmount) ?>;
        const constructionFee      = <?= json_encode((float)$constructionFee) ?>;
        const emiPaidSoFar         = <?= json_encode((float)$emiPaidSoFar) ?>;
        const existingBulkDiscount = <?= json_encode((float)$totalBulkDiscount) ?>;
        const effectiveCF          = Math.max(0, constructionFee - existingBulkDiscount);

        const settleDueFields       = document.querySelectorAll('.settle-due-fields');
        const parentPaymentIdSelect = document.getElementById('parentPaymentId');
        const totalBookingMoneyContainer = document.getElementById('totalBookingMoneyContainer');
        const totalBookingMoneyField     = document.getElementById('totalBookingMoneyField');
        const otherPurposeContainer      = document.getElementById('otherPurposeContainer');
        const otherPurposeInput          = document.getElementById('otherPurpose');

        const enableHandover  = document.getElementById('enableHandover');
        const handoverWrapper = document.getElementById('handoverWrapper');
        const handoverTo      = document.getElementById('handoverTo');

        if (enableHandover && handoverWrapper && handoverTo) {
            enableHandover.addEventListener('change', function() {
                if (this.checked) {
                    handoverWrapper.classList.remove('d-none');
                    handoverTo.setAttribute('required', 'required');
                } else {
                    handoverWrapper.classList.add('d-none');
                    handoverTo.removeAttribute('required');
                    handoverTo.value = '';
                }
            });
        }

        // PHP values for dynamic balance calculation
        const isShop = <?= json_encode((bool)$isShop) ?>;
        const totalLandShare = <?= json_encode((float)($paymentSummary['total_land_share'] ?? 0)) ?>;
        const discountAmount = <?= json_encode((float)($paymentSummary['discount_amount'] ?? 0)) ?>;
        const bookingMoneyTotal = <?= json_encode((float)$bookingMoneyTotal) ?>;
        const allRegistrationAndLandshare = <?= json_encode((float)$allRegistrationAndLandshare) ?>;
        const shopRemainingLandShare = <?= json_encode((float)$remainingLandShare) ?>;

        function getDynamicBalances() {
            if (isShop) {
                const target = <?= json_encode((float)$effectiveBookingTarget) ?>;
                const remBooking = Math.max(0, target - bookingMoneyTotal);
                const remReg = Math.max(0, shopRemainingLandShare);
                return { remBooking, remReg, target };
            }

            const target = totalBookingMoneyField ? (parseFloat(totalBookingMoneyField.value) || 0) : <?= json_encode((float)$effectiveBookingTarget) ?>;
            const remBooking = Math.max(0, target - bookingMoneyTotal);
            const remReg = Math.max(0, totalLandShare - discountAmount - target - allRegistrationAndLandshare);

            const bmOption = paymentType ? paymentType.querySelector('option[value="booking_money"]') : null;
            if (bmOption) {
                if (remBooking > 0) {
                    bmOption.disabled = false;
                    bmOption.style.color = '';
                    bmOption.textContent = 'Booking Money' + (bookingMoneyTotal > 0 ? ' (Remaining: TK.' + remBooking.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2}) + ')' : '');
                } else {
                    bmOption.disabled = true;
                    bmOption.style.color = '#6c757d';
                    bmOption.textContent = 'Booking Money (Fully Paid — TK.' + bookingMoneyTotal.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2}) + ')';
                }
            }

            const regOption = paymentType ? paymentType.querySelector('option[value="registration"]') : null;
            if (regOption) {
                if (remReg > 0) {
                    regOption.disabled = false;
                    regOption.style.color = '';
                    regOption.textContent = 'Registration (Remaining: TK.' + remReg.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2}) + ')';
                } else {
                    regOption.disabled = true;
                    regOption.style.color = '#6c757d';
                    regOption.textContent = 'Registration (Completed ✓)';
                }
            }

            return { remBooking, remReg, target };
        }

        if (totalBookingMoneyField) {
            totalBookingMoneyField.addEventListener('input', function() {
                updateAmountField();
            });
        }

        // Auto-calculate due_emi_amount from entered amount vs suggested per-EMI
        function recalcEmiDue() {
            if (!dueEmiAmountField) return;
            if (!paymentType || paymentType.value !== 'emi') {
                dueEmiAmountField.value = '0';
                controlPartiallyPaidStatus();
                return;
            }
            const entered = parseFloat(amountField?.value) || 0;
            const remainingAfterThis = Math.max(0, effectiveCF - emiPaidSoFar - entered);
            let due = 0;
            if (remainingAfterThis > 0) {
                const maxAvailableCF = Math.max(0, effectiveCF - emiPaidSoFar);
                const expectedForThis = perEmiAmountVal > 0 ? Math.min(perEmiAmountVal, maxAvailableCF) : 0;
                if (expectedForThis > 0 && entered > 0 && entered < expectedForThis) {
                    due = expectedForThis - entered;
                }
            }
            dueEmiAmountField.value = due.toFixed(2);
            controlPartiallyPaidStatus();
        }

        // Function to update amount field help text
        function updateAmountField() {
            if (!paymentType) return;
            const paymentTypeValue = paymentType.value;
            let helpText = '';
            const balances = getDynamicBalances();

            // Toggle Other Purpose input
            if (otherPurposeContainer) {
                if (paymentTypeValue === 'others') {
                    otherPurposeContainer.style.display = 'block';
                    if (otherPurposeInput) otherPurposeInput.setAttribute('required', 'required');
                } else {
                    otherPurposeContainer.style.display = 'none';
                    if (otherPurposeInput) {
                        otherPurposeInput.removeAttribute('required');
                        otherPurposeInput.value = '';
                    }
                }
            }

            // Toggle Total Booking Money container (for non-shop booking_money & registration)
            if (totalBookingMoneyContainer) {
                if (paymentTypeValue === 'booking_money' || paymentTypeValue === 'registration') {
                    totalBookingMoneyContainer.style.display = 'block';
                } else {
                    totalBookingMoneyContainer.style.display = 'none';
                }
            }

            switch (paymentTypeValue) {
                case 'booking_money':
                    var remBooking = balances.remBooking;
                    if (remBooking > 0) {
                        helpText = 'Booking Money balance: TK.' + remBooking.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2}) + '. Any surplus amount will automatically cascade to Registration -> Dev Charge -> EMI.';
                        amountField.setAttribute('placeholder', 'Suggested: TK.' + remBooking.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' (Surplus cascades)');
                        if (document.activeElement !== totalBookingMoneyField) {
                            amountField.value = remBooking.toFixed(2);
                        }
                        amountField.removeAttribute('max');
                        amountField.removeAttribute('disabled');
                    } else {
                        helpText = 'Booking Money is already fully paid.';
                        amountField.value = '';
                        amountField.setAttribute('disabled', 'disabled');
                    }
                    break;
                case 'registration':
                    var remaining = balances.remReg;
                    helpText = 'Remaining Registration amount: TK.' + remaining.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
                    amountField.setAttribute('placeholder', 'Max: TK.' + remaining.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2}));
                    amountField.setAttribute('max', remaining);
                    amountField.removeAttribute('disabled');
                    break;
                case 'mutation_cost':
                    helpText = 'Enter mutation cost amount';
                    amountField.setAttribute('placeholder', 'Enter mutation cost amount');
                    amountField.removeAttribute('max');
                    amountField.removeAttribute('disabled');
                    break;
                case 'others':
                    helpText = 'Incidental / extra payment outside <?= $isShop ? 'shop' : 'flat' ?> price. Will be recorded in ledger & receipt without reducing unit due.';
                    amountField.setAttribute('placeholder', 'Enter amount');
                    amountField.removeAttribute('max');
                    amountField.removeAttribute('disabled');
                    break;
                case 'emi':
                    var perEmi = <?= json_encode($perEmiAmount) ?>;
                    var cedRemainingCF = <?= json_encode((float)$remainingCF) ?>;
                    var totalDue = <?= json_encode($totalEmiDue) ?>;
                    var dueNote = totalDue > 0 ? ' Outstanding due: TK.' + totalDue.toLocaleString('en-IN', {minimumFractionDigits:2}) + ' (auto-cleared first).' : '';
                    if (cedRemainingCF <= 0) {
                        helpText = 'Construction fee fully paid — no more EMI needed.';
                        amountField.setAttribute('disabled', 'disabled');
                    } else if (perEmi > 0 && cedRemainingCF < perEmi) {
                        helpText = 'Remaining CF: TK.' + cedRemainingCF.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' (less than per EMI).' + dueNote;
                        amountField.setAttribute('placeholder', 'Remaining CF: TK.' + cedRemainingCF.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2}));
                        amountField.removeAttribute('max');
                        amountField.removeAttribute('disabled');
                    } else {
                        helpText = perEmi > 0 ? 'Suggested: TK.' + perEmi.toLocaleString('en-IN', {minimumFractionDigits:2}) + '.' + dueNote + ' Enter any amount — system allocates automatically.' : 'Enter EMI amount';
                        amountField.setAttribute('placeholder', perEmi > 0 ? 'Suggested: TK.' + perEmi.toLocaleString('en-IN', {minimumFractionDigits:2}) : 'Enter EMI amount');
                        amountField.removeAttribute('max');
                        amountField.removeAttribute('disabled');
                    }
                    break;
                case 'development_charge':
                case 'shop_service_charge':
                    var cedRemainingDev = <?= json_encode(max(0, (float)$flatDevCharges - (float)$devChargePaid)) ?>;
                    var cedDevLabel = '<?= addslashes($devChargeLabel) ?>';
                    <?php if ($isShop): ?>
                    if (paymentTypeValue === 'development_charge') {
                        cedRemainingDev = <?= json_encode($shopDevRemaining) ?>;
                        cedDevLabel = 'Development Charge';
                    }
                    <?php endif; ?>
                    helpText = cedDevLabel + ' payment. Remaining: TK.' + cedRemainingDev.toLocaleString('en-IN', {minimumFractionDigits:2}) + '. Any surplus amount will automatically cascade to the next stage (<?= $isShop ? 'Shop Service Charge → EMI' : 'EMI' ?>).';
                    amountField.setAttribute('placeholder', 'Enter amount (Surplus cascades to EMI)');
                    amountField.removeAttribute('max');
                    amountField.removeAttribute('disabled');
                    break;
                case 'emi_due_settlement':
                    if (totalEmiDueVal > 0) {
                        helpText = 'Outstanding EMI Due: TK. ' + totalEmiDueVal.toLocaleString('en-US', {minimumFractionDigits: 2}) + '. Payment will settle oldest dues first (waterfall).';
                        amountField.setAttribute('placeholder', 'Suggested: TK. ' + totalEmiDueVal.toLocaleString('en-US', {minimumFractionDigits: 2}));
                    } else {
                        helpText = 'Enter payment amount (will auto-settle booking, land share, dev charges & EMI dues sequentially)';
                        amountField.setAttribute('placeholder', 'Enter payment amount');
                    }
                    amountField.removeAttribute('max');
                    amountField.removeAttribute('disabled');
                    break;
                default:
                    helpText = 'Enter payment amount';
                    amountField.setAttribute('placeholder', 'Enter payment amount');
                    amountField.removeAttribute('max');
                    amountField.removeAttribute('disabled');
            }

            if (amountHelp) amountHelp.textContent = helpText;
        }

        // Function to control Partially Paid status
        function controlPartiallyPaidStatus() {
            if (!paymentType || !partiallyPaidOption) return;
            const paymentTypeValue = paymentType.value;
            const dueAmount = parseFloat(dueEmiAmountField?.value) || 0;

            if (paymentTypeValue === 'emi') {
                if (dueAmount > 0) {
                    partiallyPaidOption.disabled = false;
                    partiallyPaidOption.textContent = 'Partially Paid';
                } else {
                    partiallyPaidOption.disabled = true;
                    partiallyPaidOption.textContent = 'Partially Paid (Due amount required)';
                    if (paymentStatusSelect && paymentStatusSelect.value === 'partially_paid') {
                        paymentStatusSelect.value = 'paid';
                    }
                }
            } else {
                partiallyPaidOption.disabled = true;
                partiallyPaidOption.textContent = 'Partially Paid (Only for EMI)';
                if (paymentStatusSelect && paymentStatusSelect.value === 'partially_paid') {
                    paymentStatusSelect.value = 'paid';
                }
            }
        }

        if (applyEmiDiscount && emiDiscountSection) {
            applyEmiDiscount.addEventListener('change', function() {
                emiDiscountSection.style.display = this.checked ? 'block' : 'none';
                if (!this.checked && bulkDiscountInput) bulkDiscountInput.value = '0';
                
                // Allow 0 amount if applying EMI discount (e.g., adding only a discount retrospectively)
                if (this.checked) {
                    if (amountField) {
                        amountField.min = "0";
                        amountField.required = false;
                        if (!amountField.value) amountField.value = "0";
                    }
                    // Update label to clarify it won't consume the next EMI slot if just a discount
                    const emiOption = document.querySelector('#paymentType option[value="emi"]');
                    if (emiOption) {
                        if (!emiOption.dataset.originalText) emiOption.dataset.originalText = emiOption.text;
                        emiOption.text = "EMI / Past EMI Discount (Cons. Fee)";
                    }
                } else {
                    if (amountField) {
                        amountField.min = "1";
                        amountField.required = true;
                        if (parseFloat(amountField.value) === 0) amountField.value = "";
                    }
                    // Restore label
                    const emiOption = document.querySelector('#paymentType option[value="emi"]');
                    if (emiOption && emiOption.dataset.originalText) {
                        emiOption.text = emiOption.dataset.originalText;
                    }
                }
            });
        }

        // Update bulk discount section visibility and limits
        function updateBulkDiscount() {
            if (!applyEmiDiscount) return;
            const amount = parseFloat(amountField?.value) || 0;
            const isEmi = paymentType && paymentType.value === 'emi';

            if (isEmi) {
                applyEmiDiscount.closest('.mt-3').style.display = 'block';
                const remainingAfter = Math.max(0, effectiveCF - emiPaidSoFar - amount);
                const maxDiscount = remainingAfter;
                if (remainingCFDisplay) remainingCFDisplay.textContent = 'TK.' + remainingAfter.toLocaleString('en-IN', {minimumFractionDigits:2});
                if (maxDiscountDisplay) maxDiscountDisplay.textContent = 'TK.' + maxDiscount.toLocaleString('en-IN', {minimumFractionDigits:2});
                if (bulkDiscountInput) {
                    bulkDiscountInput.setAttribute('max', maxDiscount.toFixed(2));
                    if (parseFloat(bulkDiscountInput.value) > maxDiscount) {
                        bulkDiscountInput.value = maxDiscount.toFixed(2);
                    }
                }
            } else {
                applyEmiDiscount.closest('.mt-3').style.display = 'none';
                applyEmiDiscount.checked = false;
                if (emiDiscountSection) emiDiscountSection.style.display = 'none';
                if (bulkDiscountInput) bulkDiscountInput.value = '0';
            }
        }

        // Show/hide EMI / Settle-Due fields when payment type changes
        if (paymentType) {
            paymentType.addEventListener('change', function () {
                const val = this.value;
                const isEMI       = val === 'emi';
                const isSettleDue = val === 'emi_due_settlement';

                // Show/hide EMI due panel
                emiFields.forEach(f => f.style.display = isEMI ? 'block' : 'none');

                // Show/hide settle-due parent selector
                // Do not show yellow box — handled automatically by unified waterfall
                settleDueFields.forEach(f => f.style.display = 'none');



                // Reset due fields when not EMI
                if (!isEMI) {
                    if (dueEmiAmountField) dueEmiAmountField.value = '0';
                }

                updateAmountField();
                recalcEmiDue();
                controlPartiallyPaidStatus();
                updateBulkDiscount();
                updatePenaltyDisplay();
            });
        }

        // Cap amount when a parent EMI is selected for settlement
        if (parentPaymentIdSelect) {
            parentPaymentIdSelect.addEventListener('change', function () {
                const selectedOption = this.options[this.selectedIndex];
                const maxDue = parseFloat(selectedOption.dataset.due) || 0;
                if (maxDue > 0) {
                    // amountField.removeAttribute('max'); // uncapped for waterfall
                    // amountField.value preserved
                    if (amountHelp) amountHelp.textContent = 'Max: TK.' + maxDue.toFixed(2) + ' (remaining due for this EMI)';
                } else {
                    amountField.removeAttribute('max');
                }
            });
        }

        // Live amount input: recalculate EMI due and update bulk discount visibility
        if (amountField) {
            amountField.addEventListener('input', function () {
                recalcEmiDue();
                updateBulkDiscount();
            });
        }

        // Listen to due amount field changes
        if (dueEmiAmountField) {
            dueEmiAmountField.addEventListener('input', controlPartiallyPaidStatus);
            dueEmiAmountField.addEventListener('change', controlPartiallyPaidStatus);
        }

        // Show/hide payment method specific fields
        if (paymentMethod) {
            paymentMethod.addEventListener('change', function () {
                const method = this.value;

                bankFields.forEach(field => field.style.display = 'none');
                chequeFields.forEach(field => field.style.display = 'none');
                onlineFields.forEach(field => field.style.display = 'none');

                bankFields.forEach(field => {
                    field.querySelectorAll('input, select').forEach(el => el.removeAttribute('required'));
                });
                chequeFields.forEach(field => field.querySelector('input')?.removeAttribute('required'));
                onlineFields.forEach(field => field.querySelector('select')?.removeAttribute('required'));

                const otherBankField = document.getElementById('otherBankField');
                if (otherBankField) otherBankField.style.display = 'none';
                const customBankInput = document.getElementById('customBankInput');
                if (customBankInput) {
                    customBankInput.removeAttribute('required');
                    customBankInput.value = '';
                }

                if (method === 'bank' || method === 'cheque') {
                    bankFields.forEach(field => {
                        if (field.id !== 'otherBankField') {
                            field.style.display = 'block';
                        }
                    });
                    document.getElementById('bankSelect')?.setAttribute('required', 'required');
                    document.getElementById('clientBankSelect')?.setAttribute('required', 'required');

                    if (method === 'cheque') {
                        chequeFields.forEach(field => {
                            field.style.display = 'block';
                            field.querySelector('input')?.setAttribute('required', 'required');
                        });
                    }

                    if (window.jQuery && $.fn.select2) {
                        const $cBank = $('#clientBankSelect');
                        if (!$cBank.hasClass('select2-hidden-accessible')) {
                            $cBank.select2({
                                width: '100%',
                                placeholder: '-- Select Client Bank --',
                                tags: true,
                                createTag: function (params) {
                                    var term = $.trim(params.term);
                                    if (term === '') return null;
                                    return {
                                        id: term,
                                        text: term + ' (Foreign / Custom Bank)',
                                        newTag: true
                                    };
                                }
                            });
                        }
                    }
                } else if (method === 'online') {
                    onlineFields.forEach(field => {
                        field.style.display = 'block';
                        field.querySelector('select')?.setAttribute('required', 'required');
                    });
                }
            });
        }

        // Handle "Other Country / Foreign Bank" & "Other Bank" selection
        if (window.jQuery) {
            $(document).on('change', '#clientBankSelect', function() {
                const val = this.value;
                if (val === 'Other Bank' || val === 'Other Country / Foreign Bank') {
                    if (val === 'Other Country / Foreign Bank') {
                        $('#customBankLabel').html('Specify Foreign / Other Country Bank Name <span>*</span>');
                        $('#customBankInput').attr('placeholder', 'e.g. Emirates NBD, Chase Bank, Al Rajhi, etc.');
                    } else {
                        $('#customBankLabel').html('Specify Client Bank Name <span>*</span>');
                        $('#customBankInput').attr('placeholder', 'Enter bank name');
                    }
                    $('#otherBankField').show();
                    $('#customBankInput').attr('required', 'required');
                } else {
                    $('#otherBankField').hide();
                    $('#customBankInput').removeAttr('required').val('');
                }
            });
        }

        // Penalty calculation constants from PHP
        const overduePenaltySum = <?= (float)$overduePenaltySum ?>;
        const overdueEmiCount   = <?= (int)$overdueEmiCount ?>;
        const totalEmiDueVal    = <?= (float)$totalEmiDue ?>;

        function updatePenaltyDisplay() {
            const penaltyWrapper        = document.getElementById('penaltyWaiverWrapper');
            const penaltySection        = document.getElementById('penaltyWaiverSection');
            const penaltyBadge          = document.getElementById('penaltyBadge');
            const calculatedPenaltyText = document.getElementById('calculatedPenaltyText');
            const penaltyAmountInput    = document.getElementById('penaltyAmount');
            const penaltyPaidInput      = document.getElementById('penaltyPaid');
            const penaltyWaivedInput    = document.getElementById('penaltyWaived');
            const toggleWaiver          = document.getElementById('toggleWaiver');
            const toggleNoPenalty       = document.getElementById('toggleNoPenalty');

            const pTypeValue = paymentType ? paymentType.value : '';
            const isEligibleForPenalty = (pTypeValue === 'emi' || pTypeValue === 'emi_due_settlement');

            if (!isEligibleForPenalty || overdueEmiCount <= 0 || overduePenaltySum <= 0) {
                if (penaltyWrapper) penaltyWrapper.style.display = 'none';
                if (penaltySection) penaltySection.style.display = 'none';
                if (penaltyBadge) penaltyBadge.style.display = 'none';
                if (penaltyAmountInput) penaltyAmountInput.value = '0';
                if (penaltyPaidInput) penaltyPaidInput.value = '0';
                return;
            }

            if (penaltyWrapper) penaltyWrapper.style.display = 'block';
            if (penaltySection) penaltySection.style.display = 'block';
            if (penaltyBadge) {
                penaltyBadge.style.display = 'inline-block';
                penaltyBadge.textContent = overdueEmiCount + ' Overdue Installment(s)';
            }
            if (calculatedPenaltyText) {
                calculatedPenaltyText.textContent = 'TK. ' + overduePenaltySum.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }
            if (penaltyAmountInput) penaltyAmountInput.value = overduePenaltySum.toFixed(2);

            let effectivePenaltyPaid = overduePenaltySum;
            if (toggleNoPenalty && toggleNoPenalty.checked) {
                effectivePenaltyPaid = 0;
                if (penaltyPaidInput) penaltyPaidInput.value = '0';
                if (penaltyWaivedInput) penaltyWaivedInput.value = '0';
                if (toggleWaiver) {
                    toggleWaiver.checked = false;
                    toggleWaiver.disabled = true;
                }
                const waiverFields = document.getElementById('waiverFields');
                const waiverReason = document.getElementById('waiverReason');
                const waiverFile   = document.getElementById('waiverFile');
                if (waiverFields) waiverFields.style.display = 'none';
                if (waiverReason) waiverReason.removeAttribute('required');
                if (waiverFile) waiverFile.removeAttribute('required');
            } else {
                if (toggleWaiver) toggleWaiver.disabled = false;
                
                if (toggleWaiver && toggleWaiver.checked) {
                    const waivedVal = parseFloat(penaltyWaivedInput?.value) || overduePenaltySum;
                    effectivePenaltyPaid = Math.max(0, overduePenaltySum - waivedVal);
                    if (penaltyPaidInput) penaltyPaidInput.value = effectivePenaltyPaid.toFixed(2);
                    if (penaltyWaivedInput && (!penaltyWaivedInput.value || parseFloat(penaltyWaivedInput.value) <= 0)) {
                        penaltyWaivedInput.value = overduePenaltySum.toFixed(2);
                    }
                } else {
                    if (penaltyPaidInput) penaltyPaidInput.value = overduePenaltySum.toFixed(2);
                    if (penaltyWaivedInput) penaltyWaivedInput.value = '0';
                }
            }

            // Total Payable
            const penaltyTotalBreakdown    = document.getElementById('penaltyTotalBreakdown');
            const totalPayableLabel        = document.getElementById('totalPayableLabel');
            const totalPayableText         = document.getElementById('totalPayableWithPenaltyText');
            const btnFillTotalWithPenalty  = document.getElementById('btnFillTotalWithPenalty');

            let baseAmount = 0;
            if (pTypeValue === 'emi_due_settlement') {
                baseAmount = totalEmiDueVal > 0 ? totalEmiDueVal : 0;
                if (totalPayableLabel) totalPayableLabel.textContent = 'Suggested Total (Dues + Penalty):';
            } else {
                baseAmount = perEmiAmountVal > 0 ? perEmiAmountVal : 0;
                if (totalPayableLabel) totalPayableLabel.textContent = 'Suggested Total (1 EMI + Penalty):';
            }

            const suggestedTotalPayable = baseAmount + effectivePenaltyPaid;
            if (penaltyTotalBreakdown) penaltyTotalBreakdown.style.display = 'block';
            if (totalPayableText) {
                totalPayableText.textContent = 'TK. ' + suggestedTotalPayable.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }

            if (btnFillTotalWithPenalty) {
                btnFillTotalWithPenalty.onclick = function() {
                    if (amountField) {
                        amountField.value = Math.round(suggestedTotalPayable);
                        amountField.dispatchEvent(new Event('input'));
                    }
                };
            }
        }

        // Toggle Penalty Waiver fields
        const toggleWaiver = document.getElementById('toggleWaiver');
        const waiverFields = document.getElementById('waiverFields');
        const waiverReason = document.getElementById('waiverReason');
        const waiverFile   = document.getElementById('waiverFile');

        if (toggleWaiver && waiverFields) {
            toggleWaiver.addEventListener('change', function() {
                if (this.checked) {
                    waiverFields.style.display = 'block';
                    if (waiverReason) waiverReason.setAttribute('required', 'required');
                    if (waiverFile) waiverFile.setAttribute('required', 'required');
                } else {
                    waiverFields.style.display = 'none';
                    if (waiverReason) waiverReason.removeAttribute('required');
                    if (waiverFile) waiverFile.removeAttribute('required');
                }
                updatePenaltyDisplay();
            });
        }

        const toggleNoPenalty = document.getElementById('toggleNoPenalty');
        if (toggleNoPenalty) {
            toggleNoPenalty.addEventListener('change', function() {
                updatePenaltyDisplay();
            });
        }

        if (paymentType) {
            paymentType.addEventListener('change', function() {
                updatePenaltyDisplay();
            });
        }

        updatePenaltyDisplay();

        // Form validation
        const transactionForm = document.getElementById('transactionForm');
        if (transactionForm) {
            transactionForm.addEventListener('submit', function (e) {
                if (toggleWaiver && toggleWaiver.checked) {
                    const waivedAmt = parseFloat(document.getElementById('penaltyWaived')?.value) || 0;
                    const reasonTxt = waiverReason?.value.trim() || '';
                    const fileVal   = waiverFile?.value || '';

                    if (waivedAmt <= 0) {
                        e.preventDefault();
                        alert('Please specify the waived penalty amount.');
                        document.getElementById('penaltyWaived')?.focus();
                        return false;
                    }
                    if (!reasonTxt) {
                        e.preventDefault();
                        alert('Waiver reason is required.');
                        waiverReason?.focus();
                        return false;
                    }
                    if (!fileVal) {
                        e.preventDefault();
                        alert('Attachment document for penalty waiver approval is required.');
                        waiverFile?.focus();
                        return false;
                    }
                }
                const paymentTypeValue = paymentType ? paymentType.value : '';
                const amount = parseFloat(amountField?.value) || 0;
                const dueAmount = parseFloat(dueEmiAmountField?.value) || 0;
                const paymentStatus = paymentStatusSelect ? paymentStatusSelect.value : 'paid';

                // emi_due_settlement is handled by unified waterfall without manual parent requirement

                // Others purpose validation
                if (paymentTypeValue === 'others') {
                    const purposeVal = otherPurposeInput ? otherPurposeInput.value.trim() : '';
                    if (!purposeVal) {
                        e.preventDefault();
                        alert("Please specify the purpose/description for the 'Others' payment.");
                        if (otherPurposeInput) otherPurposeInput.focus();
                        return false;
                    }
                }

                // Registration cap validation
                if (paymentTypeValue === 'registration') {
                    const balances = getDynamicBalances();
                    const remaining = balances.remReg;
                    if (amount > remaining + 0.01) {
                        e.preventDefault();
                        alert('Registration amount cannot exceed remaining balance: TK.' + remaining.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2}));
                        if (amountField) amountField.focus();
                        return false;
                    }
                }

                // EMI partially_paid validation
                if (paymentTypeValue === 'emi' && paymentStatus === 'partially_paid' && dueAmount <= 0) {
                    e.preventDefault();
                    alert('Partially Paid status requires a due amount greater than 0.');
                    if (dueEmiAmountField) dueEmiAmountField.focus();
                    return false;
                }

                const isDiscountOnly = (paymentTypeValue === 'emi' && applyEmiDiscount && applyEmiDiscount.checked && (parseFloat(bulkDiscountInput?.value) || 0) > 0);
                if (amount <= 0 && !isDiscountOnly) {
                    e.preventDefault();
                    alert('Please enter a valid amount.');
                    if (amountField) amountField.focus();
                    return false;
                }

                return true;
            });
        }

        // Initialize
        updateAmountField();
        controlPartiallyPaidStatus();

        if (paymentMethod) {
            paymentMethod.dispatchEvent(new Event('change'));
        }

        // Load banks
        fetch('<?= $GLOBALS['base_url'] ?>/get-company-banks')
            .then(r => r.json())
            .then(data => {
                window.companyBanksList = data.banks || [];
                const sel = document.getElementById('bankSelect');
                if (!sel) return;
                sel.innerHTML = '<option value="">-- Select Transfer To / Company Bank --</option>';
                window.companyBanksList.forEach(b => {
                    const opt = document.createElement('option');
                    opt.value = b.id;
                    opt.textContent = b.bank_name + ' - ' + b.account_name + (b.account_number ? ' (' + b.account_number + ')' : '');
                    sel.appendChild(opt);
                });
            })
            .catch(() => {
                const sel = document.getElementById('bankSelect');
                if (sel) sel.innerHTML = '<option value="">Bank Registry unavailable</option>';
            });

        // File preview thumbnails
        const proofFilesInput = document.getElementById('proofFiles');
        const filePreview = document.getElementById('filePreview');
        if (proofFilesInput && filePreview) {
            proofFilesInput.addEventListener('change', function() {
                filePreview.innerHTML = '';
                Array.from(this.files).forEach(file => {
                    const thumb = document.createElement('div');
                    thumb.className = 'border rounded p-1 text-center';
                    thumb.style.cssText = 'width:80px;font-size:11px;overflow:hidden;';
                    if (file.type.startsWith('image/')) {
                        const img = document.createElement('img');
                        img.style.cssText = 'width:68px;height:52px;object-fit:cover;border-radius:4px;';
                        img.src = URL.createObjectURL(file);
                        thumb.appendChild(img);
                    } else {
                        thumb.innerHTML = '<i class="fa-light fa-file-pdf fa-2x text-danger"></i>';
                    }
                    const name = document.createElement('div');
                    name.textContent = file.name.length > 10 ? file.name.substr(0,10)+'…' : file.name;
                    thumb.appendChild(name);
                    filePreview.appendChild(thumb);
                });
            });
        }

        // Company Bank Add/Edit Logic
        const bankSelect = document.getElementById('bankSelect');
        const editCompanyBankBtn = document.getElementById('editCompanyBankBtn');
        if (bankSelect && editCompanyBankBtn) {
            bankSelect.addEventListener('change', function() {
                editCompanyBankBtn.style.display = this.value ? 'inline-block' : 'none';
            });
        }

        document.getElementById('addCompanyBankBtn')?.addEventListener('click', function() {
            document.getElementById('companyBankModalTitle').textContent = 'Add Company Bank';
            document.getElementById('companyBankForm').reset();
            document.getElementById('cb_id').value = '';
            new bootstrap.Modal(document.getElementById('companyBankModal')).show();
        });

        document.getElementById('editCompanyBankBtn')?.addEventListener('click', function() {
            const bankId = document.getElementById('bankSelect').value;
            if (!bankId) return;
            const bank = (window.companyBanksList || []).find(b => b.id == bankId);
            if (bank) {
                document.getElementById('companyBankModalTitle').textContent = 'Edit Company Bank';
                document.getElementById('cb_id').value = bank.id;
                document.getElementById('cb_bank_name').value = bank.bank_name || '';
                document.getElementById('cb_account_name').value = bank.account_name || '';
                document.getElementById('cb_account_number').value = bank.account_number || '';
                new bootstrap.Modal(document.getElementById('companyBankModal')).show();
            }
        });

        document.getElementById('saveCompanyBankBtn')?.addEventListener('click', function() {
            const form = document.getElementById('companyBankForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            const btn = this;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-light fa-spinner fa-spin me-1"></i>Saving...';
            btn.disabled = true;

            const formData = new FormData(form);
            const isEdit = formData.get('id') !== '';
            const endpoint = isEdit ? '/update-company-bank-ajax' : '/store-company-bank-ajax';

            fetch('<?= $GLOBALS['base_url'] ?>' + endpoint, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    // Reload banks and select the saved one
                    fetch('<?= $GLOBALS['base_url'] ?>' + '/get-company-banks')
                        .then(r => r.json())
                        .then(data => {
                            window.companyBanksList = data.banks || [];
                            const sel = document.getElementById('bankSelect');
                            if (sel) {
                                sel.innerHTML = '<option value="">-- Select Transfer To / Company Bank --</option>';
                                window.companyBanksList.forEach(b => {
                                    const opt = document.createElement('option');
                                    opt.value = b.id;
                                    opt.textContent = b.bank_name + ' - ' + b.account_name + (b.account_number ? ' (' + b.account_number + ')' : '');
                                    sel.appendChild(opt);
                                });
                                // Keep selection
                                const savedId = isEdit ? formData.get('id') : (res.id || '');
                                if (savedId) {
                                    sel.value = savedId;
                                    document.getElementById('editCompanyBankBtn').style.display = 'inline-block';
                                }
                            }
                        });
                    
                    bootstrap.Modal.getInstance(document.getElementById('companyBankModal')).hide();
                    if(typeof toastr !== 'undefined') toastr.success(res.message || 'Bank saved successfully');
                } else {
                    if(typeof toastr !== 'undefined') toastr.error(res.message || 'Failed to save bank');
                }
            })
            .catch(err => {
                if(typeof toastr !== 'undefined') toastr.error('An error occurred');
            })
            .finally(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        });
    });
</script>

<!-- Company Bank Add/Edit Modal -->
<div class="modal fade" id="companyBankModal" tabindex="-1" aria-labelledby="companyBankModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header bg-primary text-white border-0 py-3">
                <h5 class="modal-title fw-bold text-white" id="companyBankModalLabel">
                    <i class="fa-light fa-building-columns me-2"></i><span id="companyBankModalTitle">Add Company Bank</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <form id="companyBankForm">
                    <input type="hidden" name="id" id="cb_id" value="">
                    <div class="row ">
                        <div class="col-12">
                            <label class="form-label fs-14 fw-semibold text-dark">Bank Name <span class="text-danger">*</span></label>
                            <input type="text" name="bank_name" id="cb_bank_name" class="form-control" required placeholder="e.g. Dutch Bangla Bank">
                        </div>
                        <div class="col-12">
                            <label class="form-label fs-14 fw-semibold text-dark">Account Name</label>
                            <input type="text" name="account_name" id="cb_account_name" class="form-control" placeholder="e.g. TPHL">
                        </div>
                        <div class="col-12">
                            <label class="form-label fs-14 fw-semibold text-dark">Account Number</label>
                            <input type="text" name="account_number" id="cb_account_number" class="form-control" placeholder="e.g. 101.XXX.XXXXX">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0 pb-4 px-4 bg-light">
                <button type="button" class="btn btn-light border shadow-sm px-4 rounded-pill" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="saveCompanyBankBtn" class="btn btn-primary px-4 rounded-pill shadow-sm"><i class="fa-light fa-save me-1"></i>Save Bank</button>
            </div>
        </div>
    </div>
</div>

<style>
    @media(max-width: 575px) {
        .card__title {
            font-size: 24px;
            line-height: 1.4;
        }
        .mt-30 { margin-top: 10px; }
        .btn { padding: 6px 12px; }
    }

    select option:disabled {
        color: #999;
        background-color: #f8f9fa;
    }

    .alert {
        border-radius: 10px;
        border: none;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .is-invalid {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }

    .invalid-feedback {
        display: block;
        margin-top: 5px;
        font-size: 12px;
    }
</style>

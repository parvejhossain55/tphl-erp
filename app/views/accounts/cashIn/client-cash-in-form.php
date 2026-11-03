<?php
$page_title = "Add Transaction";
$page = 'Clients';
$addPage = false;
$show_breadcrumb = true;

// Get booking ID from URL or session
$bookingId = $_GET['booking_id'] ?? $clientDetails['id'] ?? null;

// Get payment summary to check all gating flags
$accountModel     = new AccountClient();
$paymentSummary   = $accountModel->getPaymentSummaryByBookingId($bookingId);
$outstandingDues  = $accountModel->getOutstandingEmiDues($bookingId);

$remainingLandShare      = $paymentSummary['remaining_land_share']       ?? 0;
$allBangladeshBanks       = function_exists('getBangladeshBanks') ? getBangladeshBanks() : [];
$isLandShareCompleted    = $paymentSummary['is_land_share_completed']    ?? false;
$isDevChargeCompleted    = $paymentSummary['is_dev_charge_completed']    ?? false;
$isBookingFullyPaid      = $paymentSummary['is_booking_fully_paid']      ?? false;
$agreementValue          = $paymentSummary['agreement_value']            ?? 0;
$effectiveBookingTarget  = $paymentSummary['effective_booking_target']   ?? $agreementValue;
$bookingMoneyTotal       = $paymentSummary['booking_money_total']        ?? 0;
$bookingMoneyPaid        = $paymentSummary['booking_money_paid']         ?? 0;
$bookingBalance          = max(0, $effectiveBookingTarget - $bookingMoneyTotal);
$perEmiAmount         = $paymentSummary['per_emi_amount']          ?? 0;
$serviceChargeRate    = $paymentSummary['service_charge_per_emi']  ?? 0;
$flatDevCharges       = $paymentSummary['flat_development_charges'] ?? 0;
$devChargePaid        = $paymentSummary['development_charge_paid_total'] ?? 0;
$totalEmiDue          = (float)($paymentSummary['total_emi_due'] ?? 0);
if ($totalEmiDue <= 0 && !empty($outstandingDues)) {
    $totalEmiDue = (float)array_sum(array_column($outstandingDues, 'due_emi_amount'));
}
$hasOutstandingDues   = count($outstandingDues) > 0;
// isRegistrationPaid is now tied to is_land_share_completed (same gate)
$isRegistrationPaid   = $paymentSummary['is_registration_paid']   ?? false;
$totalBulkDiscount    = (float)($paymentSummary['total_bulk_emi_discount'] ?? 0);
$constructionFee      = (float)($paymentSummary['construction_fee'] ?? 0);
$emiPaidSoFar         = (float)($paymentSummary['emi_paid_total'] ?? 0);
$remainingCF          = max(0, $constructionFee - $emiPaidSoFar - $totalBulkDiscount);

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
?>

<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="card__header mb-30">
                <h3 class="card__title">Add Transaction for Client:
                    <?= htmlspecialchars($clientDetails['client_name'] ?? '') ?></h3>
            </div>

            <div class="card__body">

                <?php if (($paymentSummary['booking_money_paid'] ?? 0) <= 0 && ($paymentSummary['booking_money'] ?? 0) > 0): ?>
                    <div class="alert alert-warning alert-dismissible fade show mb-4 d-flex align-items-center" role="alert">
                        <i class=""></i>
                        <div>
                            <strong>Booking Money Pending!</strong>
                            Agreed booking amount of <strong>TK.<?= number_format($paymentSummary['booking_money'], 2) ?></strong>
                            has not been received yet. Accounts can record it using the <em>Booking Money</em> option below.
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!$isBookingFullyPaid && $bookingMoneyPaid > 0): ?>
                    <div class="alert alert-warning alert-dismissible fade show mb-4 d-flex align-items-center" role="alert">
                        <i class=""></i>
                        <div>
                            <strong>Booking Money Partially Paid!</strong>
                            TK.<?= number_format($bookingMoneyPaid, 2) ?> received out of agreed
                            TK.<?= number_format($agreementValue, 2) ?>.
                            <strong>Outstanding balance: TK.<?= number_format($bookingBalance, 2) ?></strong>.
                            Land Share payments are <strong>locked</strong> until the full booking amount is settled.
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($isLandShareCompleted): ?>
                    <div class="alert alert-success alert-dismissible fade show mb-4 d-flex align-items-center" role="alert">
                    
                        <div>
                            <strong>Land Share &amp; Registration Completed!</strong>
                            All land share and registration payments are done.
                            <?php if (!$isDevChargeCompleted && $flatDevCharges > 0): ?>
                                Development Charge is next (<?= number_format($devChargePaid, 2) ?> / <?= number_format($flatDevCharges, 2) ?> paid).
                            <?php elseif ($isDevChargeCompleted): ?>
                                EMI payments are now available.
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if ($hasOutstandingDues && $remainingCF > 0): ?>
                    <div class="alert alert-warning alert-dismissible fade show mb-4 d-flex align-items-center" role="alert">
                        <i class=""></i>
                        <div>
                            <strong>Outstanding EMI Due: TK.<?= number_format($totalEmiDue, 2) ?></strong>
                            — Regular EMI payment will automatically settle dues and fill installments in order. Or select <em>"Advanced: Settle EMI Due"</em> to pay a specific due.
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Transaction Form -->
                <form action="<?= $GLOBALS['base_url'] ?>/add-transaction" method="post" id="transactionForm" enctype="multipart/form-data">
                    <input type="hidden" name="booking_id" value="<?= $bookingId ?>">

                    <div class="row gy-20">
                        <!-- Payment Type -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Type: <span class="text-danger">*</span></label>
                            <select name="payment_type" class="form-control" id="paymentType" required>
                                <option value="">-- Select Payment Type --</option>

                                <!-- Booking Money -->
                                <?php if ($formRemainingBooking > 0 || !$isBookingFullyPaid): ?>
                                    <option value="booking_money">
                                        Booking Money (Remaining: TK.<?= number_format($formRemainingBooking, 2) ?>)
                                    </option>
                                <?php else: ?>
                                    <option value="booking_money" disabled>
                                        Booking Money (Fully Paid — TK.<?= number_format($bookingMoneyPaid, 2) ?>)
                                    </option>
                                <?php endif; ?>

                                <!-- Registration -->
                                <?php if ($formRemainingRegistration > 0): ?>
                                    <option value="registration">
                                        Registration (Remaining: TK.<?= number_format($formRemainingRegistration, 2) ?>)
                                    </option>
                                    <option value="mutation_cost">
                                        Mutation Cost
                                    </option>
                                <?php else: ?>
                                    <option value="registration" disabled>Registration (Completed ✓)</option>
                                    <option value="mutation_cost" disabled>Mutation Cost (Completed ✓)</option>
                                <?php endif; ?>

                                <!-- Development Charge -->
                                <?php if (!$isDevChargeCompleted): ?>
                                    <?php if ($devChargePaid >= $flatDevCharges && $flatDevCharges > 0): ?>
                                        <option value="development_charge" disabled>
                                            Development Charges (Pending Verification — TK.<?= number_format($devChargePaid, 2) ?>)
                                        </option>
                                    <?php else: ?>
                                        <option value="development_charge">
                                            Development Charges (Remaining: TK.<?= number_format(max(0, $flatDevCharges - $devChargePaid), 2) ?>)
                                        </option>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <option value="development_charge" disabled>
                                        Development Charges (Fully Paid ✓ — TK.<?= number_format($devChargePaid, 2) ?>)
                                    </option>
                                <?php endif; ?>

                                <!-- EMI -->
                                <?php if ($remainingCF > 0): ?>
                                    <option value="emi" data-details="true">
                                        <?= !empty($nextEmiStr) ? $nextEmiStr . " " : "" ?>EMI (Construction Fee)<?php if ($perEmiAmount > 0): ?> — Suggested: TK.<?= number_format($perEmiAmount, 2) ?><?php endif; ?><?php if ($totalEmiDue > 0): ?> — Outstanding Due: TK.<?= number_format($totalEmiDue, 2) ?><?= $dueEmiStr ?><?php endif; ?>
                                    </option>
                                <?php else: ?>
                                    <option value="emi" disabled>
                                        EMI (Construction Fee) — Fully Paid ✓ (TK.<?= number_format($constructionFee, 2) ?>)
                                    </option>
                                <?php endif; ?>

                                <!-- Settle EMI Due — Advanced fallback (only shown when outstanding dues exist) -->
                                <?php if ($hasOutstandingDues && $remainingCF > 0): ?>
                                    <option value="emi_due_settlement" data-details="false">
                                        ⚙ Advanced: Settle <?= !empty($dueNos) ? implode(', ', array_map($getOrdinal, $dueNos)) . ' ' : 'Specific ' ?>EMI Due (Outstanding: TK.<?= number_format($totalEmiDue, 2) ?>)
                                    </option>
                                <?php endif; ?>

                                <!-- Others (Extra / Incidental Charges) -->
                                <option value="others" style="font-weight:600; color:#0d6efd;">
                                    Others (Extra / Incidental Charges — Excluded from Flat Price)
                                </option>

                            </select>

                            <!-- Help text -->
                            <small class="text-muted mt-2 d-block">
                                <?php if (!$isDevChargeCompleted && $flatDevCharges > 0): ?>
                                    <span class="text-warning">
                                        <i class="fa-light fa-exclamation-triangle"></i>
                                        Dev Charge paid: TK.<?= number_format($devChargePaid, 2) ?> / TK.<?= number_format($flatDevCharges, 2) ?> — EMI unlocks after this is complete
                                    </span>
                                <?php else: ?>
                                    <span class="text-success">
                                        <i class="fa-light fa-circle-check"></i>
                                        All prerequisites met. EMI payments available.
                                    </span>
                                <?php endif; ?>
                            </small>
                        </div>

                        <!-- Total Booking Money (shown for Booking Money or Registration) -->
                        <div class="col-md-6" id="totalBookingMoneyContainer" style="display:none;">
                            <div class="from__input-box">
                                <label class="form__input-title">Total Booking Money Target <span>*</span></label>
                                <input type="number" name="total_booking_money" id="totalBookingMoney" class="form-control" step="1"
                                    min="0" placeholder="Enter target amount" value="<?= $effectiveBookingTarget ?>" required>
                                <small class="text-muted">Remaining amounts will auto-calculate based on this</small>
                            </div>
                        </div>

                        <!-- Other Purpose (shown when Others is selected) -->
                        <div class="col-md-6" id="otherPurposeContainer" style="display:none;">
                            <div class="from__input-box">
                                <label class="form__input-title">Payment Purpose / Description <span>*</span></label>
                                <input type="text" name="other_purpose" id="otherPurpose" class="form-control"
                                    placeholder="e.g. Utility Connection, Modification, Mutation, Extra Work">
                                <small class="text-muted">Specify the purpose for this incidental payment (shows in ledger &amp; receipt, not deducted from flat price)</small>
                            </div>
                        </div>

                        <!-- Amount -->
                        <div class="col-md-6">
                            <div class="from__input-box">
                                <label class="form__input-title">Paid Amount <span>*</span></label>
                                        <input type="number" name="amount" id="amount" class="form-control" step="1"
                                    min="1" required placeholder="Enter payment amount">
                                <small id="amountHelp" class="text-muted"></small>
                            </div>
                        </div>

                        <!-- EMI Info Panel (shown when EMI selected) -->
                        <div class="col-md-12 emi-fields" style="display:none;">
                            <div class="from__input-box p-3" style="background:#f8f9fa;border-radius:8px;border:1px solid #dee2e6;">
                                <label class="form__input-title">EMI Payment Info <small class="text-muted">(Smart Allocation)</small></label>
                                <div class="d-flex flex-column gap-2">
                                    <?php if ($totalEmiDue > 0): ?>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-warning text-dark"><i class="fa-light fa-exclamation-triangle me-1"></i>Outstanding Due</span>
                                            <strong class="text-danger">TK.<?= number_format($totalEmiDue, 2) ?></strong>
                                            <small class="text-muted">— will be auto-cleared first from your payment (oldest due first)</small>
                                        </div>
                                    <?php endif; ?>
                                    <small class="text-muted">
                                        <?php if ($perEmiAmount > 0): ?>
                                            Suggested EMI: <strong>TK.<?= number_format($perEmiAmount, 2) ?></strong>.
                                            <?php if ($serviceChargeRate > 0): ?>
                                                Service charge of <strong><?= number_format($serviceChargeRate, 2) ?>%</strong> applies on total amount.
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        Enter any amount — the system will: ① clear outstanding dues (oldest first) → ② cover current installment → ③ carry surplus to next installment(s).
                                    </small>
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
                                <select name="bank_id" id="bankSelect" class="form-select">
                                    <option value="">-- Loading company banks... --</option>
                                </select>
                                <small class="text-muted">Managed in <a href="<?= $GLOBALS['base_url'] ?>/company-bank-accounts" target="_blank">Company Bank Manage</a></small>
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
                                    <option value="failed">Failed</option>
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
                                                <?= !empty($user['email']) ? '('.htmlspecialchars($user['email']).')' : '' ?>
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
                                    <span class="text-muted small ms-1">(JPG, PNG, WEBP, PDF — max 10 MB each)</span>
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
                                    <div class="small d-flex align-items-center">
                                    
                                        <div>
                                            <strong>Invalid Client Phone:</strong> Client phone (<?= htmlspecialchars($clientPhone ?: 'Empty') ?>) is missing or not a valid 11-digit mobile number.
                                        </div>
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
                            <div class="submit__btn text-center mt-30">
                                <button type="submit" class="btn btn-primary me-3">
                                    <i class="fa-solid fa-save me-2"></i>Save Transaction
                                </button>
                                <a href="<?= $GLOBALS['base_url'] ?>/clients-cash-in-history?booking_id=<?= $bookingId ?>"
                                    class="btn btn-secondary">
                                    <i class="fa-solid fa-times me-2"></i>Cancel
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
        const enableHandover = document.getElementById('enableHandover');
        const handoverWrapper = document.getElementById('handoverWrapper');
        const handoverTo = document.getElementById('handoverTo');
        
        if (enableHandover) {
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

        const emiFields = document.querySelectorAll('.emi-fields');
        const bankFields = document.querySelectorAll('.bank-fields');
        const chequeFields = document.querySelectorAll('.cheque-fields');
        const onlineFields = document.querySelectorAll('.online-fields');

        const remainingLandShare    = <?= json_encode($remainingLandShare) ?>;
        const isLandShareCompleted   = <?= json_encode($isLandShareCompleted) ?>;
        const isDevChargeCompleted   = <?= json_encode($isDevChargeCompleted) ?>;
        const isBookingFullyPaid     = <?= json_encode($isBookingFullyPaid) ?>;
        const bookingBalance         = <?= json_encode((float)$bookingBalance) ?>;
        const effectiveBookingTarget = <?= json_encode((float)$effectiveBookingTarget) ?>;
        const perEmiAmount           = <?= json_encode((float)$perEmiAmount) ?>;
        const serviceChargeRate      = <?= json_encode((float)$serviceChargeRate) ?>;
        const settleDueFields        = document.querySelectorAll('.settle-due-fields');
        const parentPaymentIdSelect  = document.getElementById('parentPaymentId');
        const dueEmiHidden           = document.getElementById('dueEmiAmount');
        const dueEmiDisplay          = document.getElementById('dueEmiDisplay');
        const remainingCF            = <?= json_encode((float)$remainingCF) ?>;
        // Bulk discount elements
        const applyEmiDiscount       = document.getElementById('applyEmiDiscount');
        const emiDiscountSection     = document.getElementById('emiDiscountSection');
        const bulkDiscountInput      = document.getElementById('bulkEmiDiscount');
        const remainingCFDisplay     = document.getElementById('remainingCFAfterPayment');
        const maxDiscountDisplay     = document.getElementById('maxBulkDiscount');
        const constructionFee        = <?= json_encode((float)$constructionFee) ?>;
        const emiPaidSoFar           = <?= json_encode((float)$emiPaidSoFar) ?>;
        const existingBulkDiscount   = <?= json_encode((float)$totalBulkDiscount) ?>;
        const effectiveCF            = Math.max(0, constructionFee - existingBulkDiscount);
        const totalLandShare         = <?= json_encode((float)($clientDetails['total_land_share'] ?? 0)) ?>;
        const discountAmount         = <?= json_encode((float)($clientDetails['discount_amount'] ?? 0)) ?>;
        const allRegistrationAndLandshare = <?= json_encode((float)($allRegistrationAndLandshare ?? 0)) ?>;
        const bookingMoneyTotalPaid  = <?= json_encode((float)($clientDetails['booking_money_total'] ?? 0)) ?>;
        const formRemainingRegistrationPHP = <?= json_encode((float)($formRemainingRegistration ?? 0)) ?>;

        const totalBookingMoneyInput = document.getElementById('totalBookingMoney');
        const totalBookingMoneyContainer = document.getElementById('totalBookingMoneyContainer');

        // Function to calculate dynamic remaining amounts
        function getDynamicBalances() {
            const totalBM = parseFloat(totalBookingMoneyInput.value) || 0;
            const remainingBooking = Math.max(0, totalBM - bookingMoneyTotalPaid);
            // Registration Target = Total Land Share - Discount - Total Booking Money Target
            // Then subtract all registration/land_share payments (pending + verified)
            const remainingReg = Math.max(0, totalLandShare - discountAmount - totalBM - allRegistrationAndLandshare);
            return { remainingBooking, remainingReg };
        }

        // Function to update amount field help text and EMI due calculation
        function updateAmountField() {
            const paymentTypeValue = paymentType.value;
            let helpText = '';
            
            const { remainingBooking, remainingReg } = getDynamicBalances();

            // Show Total Booking Money input for Booking Money and Registration
            if (paymentTypeValue === 'booking_money' || paymentTypeValue === 'registration') {
                totalBookingMoneyContainer.style.display = 'block';
            } else {
                totalBookingMoneyContainer.style.display = 'none';
            }

            // Show Other Purpose input when Others is selected
            const otherPurposeContainer = document.getElementById('otherPurposeContainer');
            const otherPurposeInput = document.getElementById('otherPurpose');
            if (otherPurposeContainer) {
                if (paymentTypeValue === 'others') {
                    otherPurposeContainer.style.display = 'block';
                    if (otherPurposeInput) otherPurposeInput.setAttribute('required', 'required');
                } else {
                    otherPurposeContainer.style.display = 'none';
                    if (otherPurposeInput) otherPurposeInput.removeAttribute('required');
                }
            }

            switch (paymentTypeValue) {
                case 'booking_money':
                    if (remainingBooking > 0) {
                        helpText = 'Next payable amount: TK.' + remainingBooking.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
                        amountField.setAttribute('placeholder', 'Next payable: TK.' + remainingBooking.toLocaleString('en-IN', {minimumFractionDigits:2}));
                        amountField.removeAttribute('disabled');
                    } else {
                        helpText = 'Booking already fully paid';
                        amountField.setAttribute('disabled', 'disabled');
                        amountField.setAttribute('placeholder', 'Fully paid');
                    }
                    break;

                case 'emi':
                    if (remainingCF <= 0) {
                        helpText = 'Construction fee fully paid — no more EMI needed.';
                        amountField.setAttribute('disabled', 'disabled');
                    } else if (perEmiAmount > 0) {
                        const svcPct = serviceChargeRate > 0 ? ' + ' + serviceChargeRate + '% service charge included' : '';
                        const totalDue = <?= json_encode((float)$totalEmiDue) ?>;
                        let dueNote = totalDue > 0 ? ' Outstanding due: TK.' + totalDue.toLocaleString('en-IN', {minimumFractionDigits:2}) + ' (auto-cleared first).' : '';
                        const effectiveMax = Math.min(perEmiAmount, remainingCF);
                        if (remainingCF < perEmiAmount) {
                            helpText = 'Remaining CF: TK.' + remainingCF.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' (less than per EMI).' + svcPct + dueNote;
                            amountField.setAttribute('placeholder', 'Next payable: TK.' + remainingCF.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2}));
                            // amountField.setAttribute('max', remainingCF.toFixed(2));
                        } else {
                            helpText = 'Suggested: TK.' + perEmiAmount.toLocaleString('en-IN', {minimumFractionDigits:2}) + svcPct + '.' + dueNote + ' Enter any amount — system allocates automatically.';
                            amountField.setAttribute('placeholder', 'Suggested EMI: TK.' + perEmiAmount.toLocaleString('en-IN', {minimumFractionDigits:2}));
                            // amountField.setAttribute('max', remainingCF.toFixed(2));
                        }
                        amountField.removeAttribute('disabled');
                    } else {
                        helpText = 'Enter EMI amount';
                        amountField.setAttribute('placeholder', 'Enter EMI amount');
                    }
                    break;

                case 'registration':
                    if (remainingReg > 0) {
                        helpText = 'Registration fee (Next payable: TK.' + remainingReg.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2}) + ')';
                        amountField.removeAttribute('disabled');
                        amountField.setAttribute('placeholder', 'Enter payment amount');
                    } else {
                        helpText = 'Land share fully paid — registration not needed.';
                        amountField.setAttribute('disabled', 'disabled');
                        amountField.setAttribute('placeholder', 'Fully paid');
                    }
                    break;

                case 'development_charge':
                    helpText = 'Development charge payment';
                    amountField.removeAttribute('max');
                    amountField.removeAttribute('disabled');
                    break;

                case 'emi_due_settlement':
                    helpText = 'Enter payment amount (will auto-settle booking, land share, dev charges & EMI dues sequentially)';
                    amountField.removeAttribute('max');
                    amountField.removeAttribute('disabled');
                    break;

                case 'others':
                    helpText = 'Others: Extra / incidental charges. This will show in ledger & total paid, but will NOT deduct from flat price.';
                    amountField.removeAttribute('max');
                    amountField.removeAttribute('disabled');
                    amountField.setAttribute('placeholder', 'Enter others payment amount');
                    break;

                default:
                    helpText = 'Enter payment amount';
                    amountField.setAttribute('placeholder', 'Enter payment amount');
                    amountField.removeAttribute('max');
                    amountField.removeAttribute('disabled');
            }

            // Update dropdown text dynamically
            const bmOption = document.querySelector('#paymentType option[value="booking_money"]');
            if (bmOption) {
                if (remainingBooking > 0) {
                    bmOption.textContent = 'Booking Money (Remaining: TK.' + remainingBooking.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2}) + ')';
                } else {
                    bmOption.textContent = 'Booking Money (Fully Paid — TK.' + bookingMoneyTotalPaid.toLocaleString('en-IN', {minimumFractionDigits:2}) + ')';
                }
            }

            const regOption = document.querySelector('#paymentType option[value="registration"]');
            if (regOption) {
                if (remainingReg > 0) {
                    regOption.textContent = 'Registration (Remaining: TK.' + remainingReg.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2}) + ')';
                } else {
                    regOption.textContent = 'Registration (Completed ✓)';
                }
            }

            if (amountHelp) amountHelp.textContent = helpText;

            // Clear previous validation
            amountField.classList.remove('is-invalid');
            const invalidFeedback = amountField.nextElementSibling;
            if (invalidFeedback && invalidFeedback.classList.contains('invalid-feedback')) {
                invalidFeedback.remove();
            }
        }

        // Recalculate EMI due based on entered amount vs suggested
        function recalcEmiDue() {
            if (paymentType.value !== 'emi') {
                if (dueEmiHidden) dueEmiHidden.value = '0';
                if (dueEmiDisplay) dueEmiDisplay.value = '';
                return;
            }
            const entered = parseFloat(amountField?.value) || 0;
            const cfRemainAfter = Math.max(0, remainingCF - entered);
            let due = 0;
            if (cfRemainAfter > 0) {
                // Expected for this installment is capped at actual remaining CF
                const expectedForThis = perEmiAmount > 0 ? Math.min(perEmiAmount, remainingCF) : 0;
                due = (expectedForThis > 0 && entered > 0 && entered < expectedForThis) ? (expectedForThis - entered) : 0;
            }
            if (dueEmiHidden)  dueEmiHidden.value  = due.toFixed(2);
            if (dueEmiDisplay) dueEmiDisplay.value  = due > 0 ? 'TK. ' + due.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : 'TK. 0.00';
        }

        if (applyEmiDiscount && emiDiscountSection) {
            applyEmiDiscount.addEventListener('change', function() {
                emiDiscountSection.style.display = this.checked ? 'block' : 'none';
                if (!this.checked && bulkDiscountInput) bulkDiscountInput.value = '0';
                
                // Allow 0 amount if applying EMI discount
                if (this.checked) {
                    if (amountField) {
                        amountField.min = "0";
                        amountField.required = false;
                        if (!amountField.value) amountField.value = "0";
                    }
                } else {
                    if (amountField) {
                        amountField.min = "1";
                        amountField.required = true;
                        if (parseFloat(amountField.value) === 0) amountField.value = "";
                    }
                }
            });
        }

        // Update bulk discount section visibility and limits
        function updateBulkDiscount() {
            if (!applyEmiDiscount) return;
            const amount = parseFloat(amountField?.value) || 0;
            const isEmi = paymentType.value === 'emi';

            if (isEmi) {
                // Show toggle if EMI
                applyEmiDiscount.closest('.mt-3').style.display = 'block';
                const remainingAfter = Math.max(0, effectiveCF - emiPaidSoFar - amount);
                const maxDiscount = remainingAfter;
                if (remainingCFDisplay) remainingCFDisplay.textContent = 'TK.' + remainingAfter.toLocaleString('en-IN', {minimumFractionDigits:2});
                if (maxDiscountDisplay) maxDiscountDisplay.textContent = 'TK.' + maxDiscount.toLocaleString('en-IN', {minimumFractionDigits:2});
                if (bulkDiscountInput) {
                    bulkDiscountInput.setAttribute('max', maxDiscount.toFixed(2));
                    // Cap current value
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

        // Function to control Partially Paid status
        function controlPartiallyPaidStatus() {
            const paymentTypeValue = paymentType.value;
            const dueAmount = parseFloat(dueEmiAmountField?.value) || 0;

            if (paymentTypeValue === 'emi') {
                if (dueAmount > 0) {
                    // Enable Partially Paid option
                    partiallyPaidOption.disabled = false;
                    partiallyPaidOption.textContent = 'Partially Paid';
                } else {
                    // Disable Partially Paid option
                    partiallyPaidOption.disabled = true;
                    partiallyPaidOption.textContent = 'Partially Paid (Due amount required)';

                    // If currently selected, change to 'paid'
                    if (paymentStatusSelect.value === 'partially_paid') {
                        paymentStatusSelect.value = 'paid';
                    }
                }
            } else {
                // For non-EMI payments, always disable partially_paid
                partiallyPaidOption.disabled = true;
                partiallyPaidOption.textContent = 'Partially Paid (Only for EMI)';

                // If currently selected, change to 'paid'
                if (paymentStatusSelect.value === 'partially_paid') {
                    paymentStatusSelect.value = 'paid';
                }
            }
        }

        // Show/hide EMI / Settle-Due fields when payment type changes
        if (paymentType) {
            paymentType.addEventListener('change', function () {
                const val = this.value;
                const isEMI        = val === 'emi';
                const isSettleDue  = val === 'emi_due_settlement';

                // Show/hide EMI due panel
                emiFields.forEach(f => f.style.display = isEMI ? 'block' : 'none');

                // Show/hide settle-due parent selector
                // Do not show yellow box — handled automatically by unified waterfall
                settleDueFields.forEach(f => f.style.display = 'none');



                // Reset due fields when not EMI
                if (!isEMI) {
                    if (dueEmiHidden)  dueEmiHidden.value  = '0';
                    if (dueEmiDisplay) dueEmiDisplay.value = '';
                }

                // Update help text
                updateAmountField();
                controlPartiallyPaidStatus();
                updateBulkDiscount();
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

        // Live recalculate EMI due when amount changes
        if (amountField) {
            amountField.addEventListener('input', function () {
                const paymentTypeValue = paymentType.value;
                const amount = parseFloat(this.value) || 0;

                if (paymentTypeValue === 'emi') {
                    recalcEmiDue();
                    // Auto-set payment_status
                    const due = parseFloat(dueEmiHidden?.value) || 0;
                    if (paymentStatusSelect) {
                        paymentStatusSelect.value = due > 0 ? 'partially_paid' : 'paid';
                    }
                    if (partiallyPaidOption) {
                        partiallyPaidOption.disabled = false;
                    }
                }

                if (paymentTypeValue === 'land_share') {
                    if (amount > remainingLandShare) {
                        this.classList.add('is-invalid');
                    } else {
                        this.classList.remove('is-invalid');
                    }
                }

                // Update bulk discount visibility
                updateBulkDiscount();
            });
        }

        // Show/hide payment method specific fields
        if (paymentMethod) {
            paymentMethod.addEventListener('change', function () {
                const method = this.value;

                // Hide all fields first
                bankFields.forEach(field => field.style.display = 'none');
                chequeFields.forEach(field => field.style.display = 'none');
                onlineFields.forEach(field => field.style.display = 'none');

                // Remove required attributes
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

                // Show relevant fields
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

        // Form validation
        const transactionForm = document.getElementById('transactionForm');
        if (transactionForm) {
            transactionForm.addEventListener('submit', function (e) {
                const paymentTypeValue = paymentType.value;
                const amount = parseFloat(amountField?.value) || 0;
                const dueAmount = parseFloat(dueEmiAmountField?.value) || 0;
                const paymentStatus = paymentStatusSelect.value;

                // Reset any previous validation
                amountField.classList.remove('is-invalid');
                const existingError = amountField.nextElementSibling;
                if (existingError && existingError.classList.contains('invalid-feedback')) {
                    existingError.remove();
                }

                // emi_due_settlement is handled by unified waterfall without manual parent requirement

                // Validation for EMI
                if (paymentTypeValue === 'emi' && amount <= 0) {
                    e.preventDefault();
                    alert('Please enter a valid EMI amount.');
                    amountField.focus();
                    return false;
                }

                // Validation for registration (which now includes land share remaining check)
                if (paymentTypeValue === 'registration') {
                    const { remainingReg } = getDynamicBalances();
                    if (amount > remainingReg + 0.01) {
                        e.preventDefault();
                        alert('Registration amount cannot exceed remaining: TK.' + remainingReg.toFixed(2));
                        amountField.focus();
                        return false;
                    }
                }

                // Validation for others
                if (paymentTypeValue === 'others') {
                    const purposeInput = document.getElementById('otherPurpose');
                    if (!purposeInput || !purposeInput.value.trim()) {
                        e.preventDefault();
                        alert('Please specify the payment purpose for "Others" (e.g. Utility Connection, Extra Work).');
                        if (purposeInput) purposeInput.focus();
                        return false;
                    }
                }

                // General
                if (amount <= 0) {
                    e.preventDefault();
                    alert('Please enter a valid amount.');
                    amountField.focus();
                    return false;
                }

                return true;
            });
        }

        // Initialize on page load
        if (totalBookingMoneyInput) {
            totalBookingMoneyInput.addEventListener('input', function() {
                updateAmountField();
            });
        }
        updateAmountField();
        controlPartiallyPaidStatus();


        // Trigger payment method change to set initial state
        if (paymentMethod) {
            paymentMethod.dispatchEvent(new Event('change'));
        }

        // Load company banks for AJAX dropdown
        fetch('<?= $GLOBALS['base_url'] ?>/get-company-banks')
            .then(r => r.json())
            .then(data => {
                const sel = document.getElementById('bankSelect');
                if (!sel) return;
                sel.innerHTML = '<option value="">-- Select Transfer To / Company Bank --</option>';
                (data.banks || []).forEach(b => {
                    const opt = document.createElement('option');
                    opt.value = b.id;
                    opt.textContent = b.bank_name + ' — ' + b.account_name + (b.account_number ? ' (' + b.account_number + ')' : '');
                    sel.appendChild(opt);
                });
            })
            .catch(() => {
                const sel = document.getElementById('bankSelect');
                if (sel) sel.innerHTML = '<option value="">Company Bank Accounts unavailable</option>';
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
    });
</script>

<style>
    @media(max-width: 575px) {
        .card__title {
            font-size: 24px;
            line-height: 1.4;
        }

        .mt-30 {
            margin-top: 10px;
        }

        .btn {
            padding: 6px 12px;
        }
    }

    /* Styling for disabled options */
    select option:disabled {
        color: #999;
        background-color: #f8f9fa;
    }

    /* Alert styling */
    .alert {
        border-radius: 10px;
        border: none;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    /* Form validation */
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
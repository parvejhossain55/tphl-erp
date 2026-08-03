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
$isRegistrationPaid    = $paymentSummary['is_registration_paid'] ?? false;
$devChargePaid         = $isShop ? ($paymentSummary['shop_service_charge_paid'] ?? 0) : ($paymentSummary['development_charge_paid'] ?? 0);
$flatDevCharges        = (float)($isShop ? ($paymentSummary['total_shop_service_charge'] ?? 0) : ($paymentSummary['flat_development_charges'] ?? 0));
$isDevChargeCompleted  = $isShop ? !empty($paymentSummary['is_shop_service_charge_completed']) : !empty($paymentSummary['is_dev_charge_completed']);
$devChargeLabel        = $isShop ? "Shop Service Charge" : "Development Charges";
$devChargeValue        = $isShop ? "shop_service_charge" : "development_charge";
$perEmiAmount          = (float)($paymentSummary['per_emi_amount'] ?? 0);
$serviceChargeRate     = (float)($paymentSummary['service_charge_per_emi'] ?? 0);
$totalEmiDue           = (float)($paymentSummary['total_emi_due'] ?? 0);

$hasOutstandingDues    = count($outstandingDues) > 0;
$remainingCF           = (float)($paymentSummary['remaining_construction_fee'] ?? 0);
$totalBulkDiscount     = (float)($paymentSummary['total_bulk_emi_discount'] ?? 0);
$constructionFee       = (float)($paymentSummary['construction_fee'] ?? 0);
$emiPaidSoFar          = (float)($paymentSummary['construction_fee_paid'] ?? 0);
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

                <?php if (!$isLandShareCompleted && !$isShop): ?>
                    <div class="alert alert-danger mb-4 d-flex align-items-center">
                        <div>
                            <strong>Land Share Required!</strong>
                            Land share payment must be completed before adding any transactions from CED.
                        </div>
                    </div>
                <?php else: ?>

                <?php if ($hasOutstandingDues): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-4 d-flex align-items-center" role="alert">
                      
                        <div>
                            <strong>Outstanding EMI Due: TK.<?= number_format($totalEmiDue, 2) ?></strong>
                            — Select <em>"Settle EMI Due"</em> below to clear the balance.
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <!-- Transaction Form -->
                <form action="<?= $GLOBALS['base_url'] ?>/ced-add-transaction" method="post" id="transactionForm" enctype="multipart/form-data">
                    <input type="hidden" name="booking_id" value="<?= $bookingId ?>">

                    <div class="row gy-20">
                        <!-- Payment Type (CED: dev charge + EMI only) -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Type: <span class="text-danger">*</span></label>
                            <select name="payment_type" class="form-control" id="paymentType" required>
                                <option value="">-- Select Payment Type --</option>

                                <!-- Development Charges or Shop Service Charge — requires landshare completed for flats -->
                                <?php if (($isLandShareCompleted || $isShop) && !$isDevChargeCompleted): ?>
                                    <option value="<?= $devChargeValue ?>" data-details="false">
                                        <?= $devChargeLabel ?> (Remaining: TK.<?= number_format(max(0, $flatDevCharges - $devChargePaid), 2) ?>)
                                    </option>
                                <?php elseif ($isDevChargeCompleted): ?>
                                    <option value="<?= $devChargeValue ?>" disabled>
                                        <?= $devChargeLabel ?> — Fully Paid ✓ (TK.<?= number_format($devChargePaid, 2) ?>)
                                    </option>
                                <?php else: ?>
                                    <option value="<?= $devChargeValue ?>" disabled>
                                        <?= $devChargeLabel ?> (Locked — complete land share first)
                                    </option>
                                <?php endif; ?>

                                <!-- EMI - Only if land share is completed -->
                                <?php if (($isLandShareCompleted || $isShop) && $isDevChargeCompleted && $remainingCF > 0): ?>
                                    <option value="emi" data-details="true">
                                        EMI (Construction Fee)<?php if ($perEmiAmount > 0): ?> — Suggested: TK.<?= number_format($perEmiAmount, 2) ?><?php endif; ?><?php if ($totalEmiDue > 0): ?> — Outstanding Due: TK.<?= number_format($totalEmiDue, 2) ?><?php endif; ?>
                                    </option>
                                <?php elseif (($isLandShareCompleted || $isShop) && $isDevChargeCompleted && $remainingCF <= 0): ?>
                                    <option value="emi" disabled>
                                        EMI (Construction Fee) — Fully Paid ✓ (TK.<?= number_format($constructionFee, 2) ?>)
                                    </option>
                                <?php elseif ($isLandShareCompleted || $isShop): ?>
                                    <option value="emi" disabled>
                                        EMI (Locked — pay development charge first)
                                    </option>
                                <?php else: ?>
                                    <option value="emi" disabled>
                                        EMI (Requires: Land Share Completed)
                                    </option>
                                <?php endif; ?>

                                <!-- Settle EMI Due — Advanced fallback (only shown when outstanding dues exist) -->
                                <?php if ($hasOutstandingDues): ?>
                                    <option value="emi_due_settlement" data-details="false">
                                        ⚙ Advanced: Settle Specific EMI Due (Outstanding: TK.<?= number_format($totalEmiDue, 2) ?>)
                                    </option>
                                <?php endif; ?>

                            </select>

                            <!-- Help text -->
                            <small class="text-muted mt-2 d-block">
                                <?php if (!$isLandShareCompleted && !$isShop): ?>
                                    <span class="text-warning">
                                        <i class="fa-light fa-exclamation-triangle"></i>
                                        Land share must be completed before EMI payments
                                    </span>
                                <?php else: ?>
                                    <span class="text-success">
                                        <i class="fa-light fa-circle-check"></i>
                                        Ready for <?= strtolower($devChargeLabel) ?> and construction fee payments
                                    </span>
                                <?php endif; ?>
                            </small>
                        </div>

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

                                <!-- Bulk EMI Discount (shown when >= 2 EMIs) -->
                                <div id="bulkDiscountSection" class="mt-3 p-3" style="display:none;background:rgba(108,95,252,0.07);border-radius:8px;border:1px solid rgba(108,95,252,0.2);">
                                    <label class="form__input-title" style="color:#6c5ffc;">
                                        <i class="fa-light fa-tags me-1"></i>Bulk EMI Discount (Optional)
                                    </label>
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
                                        This discount reduces the <strong>total construction fee</strong> (separate from booking discount). Applied after verification.
                                    </small>
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
                        <div class="col-md-12 bank-fields" style="display: none;">
                            <div class="from__input-box">
                                <label class="form__input-title">Bank / Branch <span>*</span></label>
                                <select name="bank_id" id="bankSelect" class="form-select">
                                    <option value="">-- Loading banks... --</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6 bank-fields" style="display: none;">
                            <div class="from__input-box">
                                <label class="form__input-title">Bank Account No. <span>*</span></label>
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

                        <!-- Proof of Payment Documents (multi-file upload) -->
                        <div class="col-md-12">
                            <div class="from__input-box">
                                <label class="form__input-title">
                                    <i class="fa-light fa-paperclip me-1"></i>Proof of Payment
                                    <span class="text-muted small ms-1">(JPG, PNG, PDF — max 5 MB each)</span>
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
                                <a href="<?= $GLOBALS['base_url'] ?>/clients-cash-in-history-view/<?= $bookingId ?>"
                                    class="btn btn-secondary">
                                    <i class="fa-solid fa-times me-2"></i>Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
                <?php endif; ?>
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
        const bulkDiscountSection  = document.getElementById('bulkDiscountSection');
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

        // Function to update amount field help text
        function updateAmountField() {
            if (!paymentType) return;
            const paymentTypeValue = paymentType.value;
            let helpText = '';

            switch (paymentTypeValue) {
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
                        amountField.setAttribute('placeholder', 'Max: TK.' + cedRemainingCF.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2}));
                        amountField.setAttribute('max', cedRemainingCF.toFixed(2));
                        amountField.removeAttribute('disabled');
                    } else {
                        helpText = perEmi > 0 ? 'Suggested: TK.' + perEmi.toLocaleString('en-IN', {minimumFractionDigits:2}) + '.' + dueNote + ' Enter any amount — system allocates automatically.' : 'Enter EMI amount';
                        amountField.setAttribute('max', cedRemainingCF.toFixed(2));
                        amountField.removeAttribute('disabled');
                    }
                    break;
                case 'development_charge':
                    helpText = 'Development charge payment';
                    amountField.removeAttribute('max');
                    amountField.removeAttribute('disabled');
                    break;
                case 'emi_due_settlement':
                    helpText = 'Select an EMI above, then enter the amount to settle (≤ remaining due).';
                    amountField.removeAttribute('disabled');
                    break;
                default:
                    helpText = 'Enter payment amount';
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

        // Update bulk discount section visibility and limits
        function updateBulkDiscount() {
            if (!bulkDiscountSection) return;
            const amount = parseFloat(amountField?.value) || 0;
            const isEmi = paymentType && paymentType.value === 'emi';
            const coversMultiple = perEmiAmountVal > 0 && amount >= (perEmiAmountVal * 2);

            if (isEmi && coversMultiple) {
                bulkDiscountSection.style.display = 'block';
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
                bulkDiscountSection.style.display = 'none';
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
                settleDueFields.forEach(f => f.style.display = isSettleDue ? 'block' : 'none');

                // Reset due fields when not EMI
                if (!isEMI) {
                    if (dueEmiAmountField) dueEmiAmountField.value = '0';
                }

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
                    amountField.setAttribute('max', maxDue);
                    amountField.value = maxDue.toFixed(2);
                    if (amountHelp) amountHelp.textContent = 'Max: TK.' + maxDue.toFixed(2) + ' (remaining due for this EMI)';
                } else {
                    amountField.removeAttribute('max');
                }
            });
        }

        // Live amount input: update bulk discount visibility
        if (amountField) {
            amountField.addEventListener('input', function () {
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

                bankFields.forEach(field => field.querySelector('input')?.removeAttribute('required'));
                chequeFields.forEach(field => field.querySelector('input')?.removeAttribute('required'));
                onlineFields.forEach(field => field.querySelector('select')?.removeAttribute('required'));

                if (method === 'bank') {
                    bankFields.forEach(field => {
                        field.style.display = 'block';
                        field.querySelector('input')?.setAttribute('required', 'required');
                    });
                } else if (method === 'cheque') {
                    bankFields.forEach(field => {
                        field.style.display = 'block';
                        field.querySelector('input')?.setAttribute('required', 'required');
                    });
                    chequeFields.forEach(field => {
                        field.style.display = 'block';
                        field.querySelector('input')?.setAttribute('required', 'required');
                    });
                } else if (method === 'online') {
                    onlineFields.forEach(field => {
                        field.style.display = 'block';
                        field.querySelector('select')?.setAttribute('required', 'required');
                    });
                }
            });
        }

        // Form validation
        const transactionForm = document.getElementById('transactionForm');
        if (transactionForm) {
            transactionForm.addEventListener('submit', function (e) {
                const paymentTypeValue = paymentType ? paymentType.value : '';
                const amount = parseFloat(amountField?.value) || 0;
                const dueAmount = parseFloat(dueEmiAmountField?.value) || 0;
                const paymentStatus = paymentStatusSelect ? paymentStatusSelect.value : 'paid';

                // Validation for EMI due settlement
                if (paymentTypeValue === 'emi_due_settlement') {
                    if (!parentPaymentIdSelect || !parentPaymentIdSelect.value) {
                        e.preventDefault();
                        alert('Please select the EMI you want to settle.');
                        return false;
                    }
                    const maxDue = parseFloat(parentPaymentIdSelect.options[parentPaymentIdSelect.selectedIndex]?.dataset?.due) || 0;
                    if (amount <= 0) {
                        e.preventDefault();
                        alert('Please enter a valid settlement amount.');
                        amountField.focus();
                        return false;
                    }
                    if (amount > maxDue + 0.01) {
                        e.preventDefault();
                        alert('Settlement amount (TK.' + amount.toFixed(2) + ') cannot exceed remaining due (TK.' + maxDue.toFixed(2) + ').');
                        amountField.focus();
                        return false;
                    }
                    return true;
                }

                // EMI partially_paid validation
                if (paymentTypeValue === 'emi' && paymentStatus === 'partially_paid' && dueAmount <= 0) {
                    e.preventDefault();
                    alert('Partially Paid status requires a due amount greater than 0.');
                    if (dueEmiAmountField) dueEmiAmountField.focus();
                    return false;
                }

                if (amount <= 0) {
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
        fetch('<?= $GLOBALS['base_url'] ?>/get-banks')
            .then(r => r.json())
            .then(data => {
                const sel = document.getElementById('bankSelect');
                if (!sel) return;
                sel.innerHTML = '<option value="">-- Select Bank / Branch --</option>';
                (data.banks || []).forEach(b => {
                    const opt = document.createElement('option');
                    opt.value = b.id;
                    opt.textContent = b.bank_name + ' — ' + b.branch_name;
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
    });
</script>

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

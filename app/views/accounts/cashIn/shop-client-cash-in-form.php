<?php
$page_title = "Add Transaction";
$page = 'Clients';
$addPage = false;
$show_breadcrumb = true;

// Get booking ID from URL or session
$bookingId = $_GET['booking_id'] ?? $clientDetails['id'] ?? null;

// Get payment summary to check all gating flags
$accountModel     = new AccountShopClient();
$paymentSummary   = $accountModel->getPaymentSummaryByBookingId($bookingId);
$outstandingDues  = $accountModel->getOutstandingEmiDues($bookingId);

$remainingLandShare      = $paymentSummary['remaining_land_share']       ?? 0;
$isLandShareCompleted    = $paymentSummary['is_land_share_completed']    ?? false;
$isShopServiceChargeCompleted = $paymentSummary['is_shop_service_charge_completed'] ?? false;
$isBookingFullyPaid      = $paymentSummary['is_booking_fully_paid']      ?? false;
$agreementValue          = $paymentSummary['agreement_value']            ?? 0;
$effectiveBookingTarget  = $paymentSummary['effective_booking_target']   ?? $agreementValue;
$bookingMoneyTotal       = $paymentSummary['booking_money_total']        ?? 0;
$bookingMoneyPaid        = $paymentSummary['booking_money_paid']         ?? 0;
$bookingBalance          = max(0, $effectiveBookingTarget - $bookingMoneyTotal);
$perEmiAmount         = $paymentSummary['per_emi_amount']          ?? 0;
$serviceChargeRate    = $paymentSummary['service_charge_per_emi']  ?? 0;
$shopServiceChargeTarget = $paymentSummary['shop_service_charge_target'] ?? 0;
$shopServiceChargePaid   = $paymentSummary['shop_service_charge_paid_total'] ?? 0;
$totalEmiDue          = $paymentSummary['total_emi_due']           ?? 0;
$hasOutstandingDues   = count($outstandingDues) > 0;
// isRegistrationPaid is now tied to is_land_share_completed (same gate)
$isRegistrationPaid   = $paymentSummary['is_registration_paid']   ?? false;
$remainingCF          = (float)($paymentSummary['remaining_construction_fee'] ?? 0);
$totalBulkDiscount    = (float)($paymentSummary['total_bulk_emi_discount'] ?? 0);
$constructionFee      = (float)($paymentSummary['construction_fee'] ?? 0);
$emiPaidSoFar         = (float)($paymentSummary['construction_fee_paid'] ?? 0);
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
                        <i class="fa-light fa-triangle-exclamation fs-4 me-2"></i>
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
                        <i class="fa-light fa-triangle-exclamation fs-4 me-2"></i>
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
                            <?php if (!$isShopServiceChargeCompleted && $shopServiceChargeTarget > 0): ?>
                                Shop Service Charge is next (<?= number_format($shopServiceChargePaid, 2) ?> / <?= number_format($shopServiceChargeTarget, 2) ?> paid).
                            <?php elseif ($isShopServiceChargeCompleted): ?>
                                EMI payments are now available.
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

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
                <form action="<?= $GLOBALS['base_url'] ?>/add-shop-transaction" method="post" id="transactionForm" enctype="multipart/form-data">
                    <input type="hidden" name="booking_id" value="<?= $bookingId ?>">

                    <div class="row gy-20">
                        <!-- Payment Type -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Type: <span class="text-danger">*</span></label>
                            <select name="payment_type" class="form-control" id="paymentType" required>
                                <option value="">-- Select Payment Type --</option>

                                <!-- Booking Money -->
                                <?php if (!$isBookingFullyPaid): ?>
                                    <option value="booking_money">
                                        Booking Money<?php if ($bookingBalance > 0): ?> (Remaining: TK.<?= number_format($bookingBalance, 2) ?>)<?php elseif ($effectiveBookingTarget > 0): ?> (Agreed: TK.<?= number_format($effectiveBookingTarget, 2) ?>)<?php endif; ?>
                                    </option>
                                <?php else: ?>
                                    <option value="booking_money" disabled>
                                        Booking Money (Fully Paid — TK.<?= number_format($bookingMoneyPaid, 2) ?> / Target: TK.<?= number_format($effectiveBookingTarget, 2) ?>)
                                    </option>
                                <?php endif; ?>

                                <!-- Registration unlock after booking paid, lock when landshare target reached -->
                                <?php if ($isBookingFullyPaid && !$isLandShareCompleted): ?>
                                    <option value="registration">
                                        Registration (Remaining: TK.<?= number_format($remainingLandShare, 2) ?>)
                                    </option>
                                <?php elseif ($isLandShareCompleted): ?>
                                    <option value="registration" disabled>Registration (Completed)</option>
                                <?php else: ?>
                                    <option value="registration" disabled>Registration (Locked — complete booking first)</option>
                                <?php endif; ?>

                                <!-- Shop Service Charge — requires registration completed -->
                                <?php if ($isLandShareCompleted && !$isShopServiceChargeCompleted): ?>
                                    <option value="shop_service_charge">
                                        Shop Service Charge (Remaining: TK.<?= number_format(max(0, $shopServiceChargeTarget - $shopServiceChargePaid), 2) ?>)
                                    </option>
                                <?php elseif ($isShopServiceChargeCompleted): ?>
                                    <option value="shop_service_charge" disabled>
                                        Shop Service Charge (Fully Paid — TK.<?= number_format($shopServiceChargePaid, 2) ?>)
                                    </option>
                                <?php else: ?>
                                    <option value="shop_service_charge" disabled>
                                        Shop Service Charge (Locked — complete registration first)
                                    </option>
                                <?php endif; ?>

                                <!-- EMI — requires dev charge and land share completed -->
                                <?php if ($isLandShareCompleted && $isShopServiceChargeCompleted && $remainingCF > 0): ?>
                                    <option value="emi" data-details="true">
                                        EMI (Construction Fee)<?php if ($perEmiAmount > 0): ?> — Suggested: TK.<?= number_format($perEmiAmount, 2) ?><?php endif; ?><?php if ($totalEmiDue > 0): ?> — Outstanding Due: TK.<?= number_format($totalEmiDue, 2) ?><?php endif; ?>
                                    </option>
                                <?php elseif ($isLandShareCompleted && $isShopServiceChargeCompleted && $remainingCF <= 0): ?>
                                    <option value="emi" disabled>
                                        EMI (Construction Fee) — Fully Paid ✓ (TK.<?= number_format($constructionFee, 2) ?>)
                                    </option>
                                <?php elseif ($isLandShareCompleted): ?>
                                    <option value="emi" disabled>
                                        EMI (Locked — pay development charge first)
                                    </option>
                                <?php else: ?>
                                    <option value="emi" disabled>
                                        EMI (Locked — complete registration &amp; shop service charge first)
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
                                <?php if (!$isLandShareCompleted): ?>
                                    <span class="text-warning">
                                        <i class="fa-light fa-exclamation-triangle"></i>
                                        Remaining Registration: TK.<?= number_format($remainingLandShare, 2) ?>
                                    </span>
                                <?php elseif (!$isShopServiceChargeCompleted && $shopServiceChargeTarget > 0): ?>
                                    <span class="text-warning">
                                        <i class="fa-light fa-exclamation-triangle"></i>
                                        Shop Svc Charge paid: TK.<?= number_format($shopServiceChargePaid, 2) ?> / TK.<?= number_format($shopServiceChargeTarget, 2) ?> — EMI unlocks after this is complete
                                    </span>
                                <?php else: ?>
                                    <span class="text-success">
                                        <i class="fa-light fa-circle-check"></i>
                                        All prerequisites met. EMI payments available.
                                    </span>
                                <?php endif; ?>
                            </small>
                        </div>

                        <!-- Amount -->
                        <div class="col-md-6">
                            <div class="from__input-box">
                                <label class="form__input-title">Paid Amount <span>*</span></label>
                                        <input type="number" name="amount" id="amount" class="form-control" step="0.01"
                                    min="0.01" required placeholder="Enter payment amount">
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
                                <small class="text-muted">Managed in <a href="<?= $GLOBALS['base_url'] ?>/bank-registry" target="_blank">Bank Registry</a></small>
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
                                    <option value="failed">Failed</option>
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

        const emiFields = document.querySelectorAll('.emi-fields');
        const bankFields = document.querySelectorAll('.bank-fields');
        const chequeFields = document.querySelectorAll('.cheque-fields');
        const onlineFields = document.querySelectorAll('.online-fields');

        const remainingLandShare    = <?= json_encode($remainingLandShare) ?>;
        const isLandShareCompleted   = <?= json_encode($isLandShareCompleted) ?>;
        const isDevChargeCompleted   = <?= json_encode($isShopServiceChargeCompleted) ?>;
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
        const bulkDiscountSection    = document.getElementById('bulkDiscountSection');
        const bulkDiscountInput      = document.getElementById('bulkEmiDiscount');
        const remainingCFDisplay     = document.getElementById('remainingCFAfterPayment');
        const maxDiscountDisplay     = document.getElementById('maxBulkDiscount');
        const constructionFee        = <?= json_encode((float)$constructionFee) ?>;
        const emiPaidSoFar           = <?= json_encode((float)$emiPaidSoFar) ?>;
        const existingBulkDiscount   = <?= json_encode((float)$totalBulkDiscount) ?>;
        const effectiveCF            = Math.max(0, constructionFee - existingBulkDiscount);

        // Function to update amount field help text and EMI due calculation
        function updateAmountField() {
            const paymentTypeValue = paymentType.value;
            let helpText = '';

            switch (paymentTypeValue) {
                case 'booking_money':
                    if (bookingBalance > 0) {
                        helpText = 'Maximum payable: TK.' + bookingBalance.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' (agreement minus discount)';
                        amountField.setAttribute('max', bookingBalance.toFixed(2));
                        amountField.setAttribute('placeholder', 'Max: TK.' + bookingBalance.toLocaleString('en-IN', {minimumFractionDigits:2}));
                    } else {
                        helpText = 'Booking already fully paid';
                        amountField.setAttribute('disabled', 'disabled');
                    }
                    amountField.removeAttribute('disabled');
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
                            amountField.setAttribute('placeholder', 'Max: TK.' + remainingCF.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2}));
                            amountField.setAttribute('max', remainingCF.toFixed(2));
                        } else {
                            helpText = 'Suggested: TK.' + perEmiAmount.toLocaleString('en-IN', {minimumFractionDigits:2}) + svcPct + '.' + dueNote + ' Enter any amount — system allocates automatically.';
                            amountField.setAttribute('placeholder', 'Suggested EMI: TK.' + perEmiAmount.toLocaleString('en-IN', {minimumFractionDigits:2}));
                            amountField.setAttribute('max', remainingCF.toFixed(2));
                        }
                        amountField.removeAttribute('disabled');
                    } else {
                        helpText = 'Enter EMI amount';
                        amountField.setAttribute('placeholder', 'Enter EMI amount');
                    }
                    break;

                case 'registration':
                    if (remainingLandShare > 0) {
                        helpText = 'Registration fee (Max: TK.' + remainingLandShare.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2}) + ')';
                        amountField.setAttribute('max', remainingLandShare.toFixed(2));
                    } else {
                        helpText = 'Land share fully paid — registration not needed.';
                        amountField.setAttribute('disabled', 'disabled');
                    }
                    amountField.removeAttribute('disabled');
                    break;

                case 'shop_service_charge':
                    helpText = 'Shop service charge payment';
                    amountField.removeAttribute('max');
                    amountField.removeAttribute('disabled');
                    break;

                case 'emi_due_settlement':
                    helpText = 'Select an EMI above, then enter the amount to settle (≤ remaining due).';
                    amountField.removeAttribute('disabled');
                    break;

                default:
                    helpText = 'Enter payment amount';
                    amountField.setAttribute('placeholder', 'Enter payment amount');
                    amountField.removeAttribute('max');
                    amountField.removeAttribute('disabled');
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
                due = expectedForThis > 0 && entered < expectedForThis ? (expectedForThis - entered).toFixed(2) : 0;
            }
            if (dueEmiHidden)  dueEmiHidden.value  = due.toFixed(2);
            if (dueEmiDisplay) dueEmiDisplay.value  = due > 0 ? 'TK. ' + due.toLocaleString('en-IN') : 'TK. 0.00';
        }

        // Update bulk discount section visibility and limits
        function updateBulkDiscount() {
            if (!bulkDiscountSection) return;
            const amount = parseFloat(amountField?.value) || 0;
            const isEmi = paymentType.value === 'emi';
            const coversMultiple = perEmiAmount > 0 && amount >= (perEmiAmount * 2);

            if (isEmi && coversMultiple) {
                bulkDiscountSection.style.display = 'block';
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
                bulkDiscountSection.style.display = 'none';
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
                settleDueFields.forEach(f => f.style.display = isSettleDue ? 'block' : 'none');

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
                    amountField.setAttribute('max', maxDue);
                    amountField.value = maxDue.toFixed(2);
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
                bankFields.forEach(field => field.querySelector('input')?.removeAttribute('required'));
                chequeFields.forEach(field => field.querySelector('input')?.removeAttribute('required'));
                onlineFields.forEach(field => field.querySelector('select')?.removeAttribute('required'));

                // Show relevant fields
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

                // Validation for EMI
                if (paymentTypeValue === 'emi' && amount <= 0) {
                    e.preventDefault();
                    alert('Please enter a valid EMI amount.');
                    amountField.focus();
                    return false;
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
        updateAmountField();
        controlPartiallyPaidStatus();

        // Trigger payment method change to set initial state
        if (paymentMethod) {
            paymentMethod.dispatchEvent(new Event('change'));
        }

        // Load banks from Bank Registry for AJAX dropdown
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
                    opt.dataset.routing = b.routing_number || '';
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
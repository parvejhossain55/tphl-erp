<?php
$page_title = "Add Transaction";
$page = 'Clients';
$addPage = false;
$show_breadcrumb = true;

// Get booking ID from URL or session
$bookingId = $_GET['booking_id'] ?? $clientDetails['id'] ?? null;

// Get payment summary to check land share status
$accountModel = new AccountClient();
$paymentSummary = $accountModel->getPaymentSummaryByBookingId($bookingId);

$remainingLandShare = $paymentSummary['remaining_land_share'] ?? 0;
$isLandShareCompleted = $paymentSummary['is_land_share_completed'] ?? false;
$isRegistrationPaid = $accountModel->isRegistrationPaid($bookingId);
?>

<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="card__header mb-30">
                <h3 class="card__title">Add Transaction for Client:
                    <?= htmlspecialchars($clientDetails['client_name'] ?? '') ?></h3>
            </div>

            <div class="card__body">

                <!-- Land Share Status Alert -->
                <?php if ($remainingLandShare <= 0): ?>
                    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                        <strong>Land Share Completed!</strong> All land share payments have been made. You can now add
                        construction fee/EMI payments.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Registration Required Alert -->
                <?php if (!$isRegistrationPaid): ?>
                    <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
                        <strong>Registration Required!</strong> Registration payment must be completed before adding EMI
                        payments.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Transaction Form -->
                <form action="<?= $GLOBALS['base_url'] ?>/add-transaction" method="post" id="transactionForm">
                    <input type="hidden" name="booking_id" value="<?= $bookingId ?>">

                    <div class="row gy-20">
                        <!-- Payment Type -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Type: <span class="text-danger">*</span></label>
                            <select name="payment_type" class="form-control" id="paymentType" required>
                                <option value="">-- Select Payment Type --</option>

                                <!-- Registration - Always available -->
                                <option value="registration" data-details="false">
                                    Registration
                                </option>

                                <!-- Land Share - Only if not completed -->
                                <?php if (!$isLandShareCompleted): ?>
                                    <option value="land_share" data-details="false">
                                        Land Share
                                        <?php echo $remainingLandShare > 0 ? '(Remaining: ৳' . number_format($remainingLandShare, 2) . ')' : ''; ?>
                                    </option>
                                <?php else: ?>
                                    <option value="land_share" disabled>
                                        Land Share (Completed)
                                    </option>
                                <?php endif; ?>

                                <!-- EMI - Only if registration is paid AND land share is completed -->
                                <?php if ($isRegistrationPaid && $isLandShareCompleted): ?>
                                    <option value="emi" data-details="true">
                                        EMI (Construction Fee)
                                    </option>
                                <?php else: ?>
                                    <option value="emi" disabled>
                                        EMI (Requires: Registration
                                        Paid<?php echo !$isLandShareCompleted ? ' + Land Share Completed' : ''; ?>)
                                    </option>
                                <?php endif; ?>
                            </select>

                            <!-- Help text with status -->
                            <small class="text-muted mt-2 d-block">
                                <?php if (!$isRegistrationPaid): ?>
                                    <span class="text-warning">
                                        <i class="fa-light fa-exclamation-triangle"></i>
                                        Registration must be paid first
                                    </span>
                                <?php elseif (!$isLandShareCompleted): ?>
                                    <span class="text-warning">
                                        <i class="fa-light fa-exclamation-triangle"></i>
                                        Remaining Land Share: ৳<?= number_format($remainingLandShare, 2) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-success">
                                        <i class="fa-light fa-circle-check"></i>
                                        Ready for construction fee payments
                                    </span>
                                <?php endif; ?>
                            </small>
                        </div>

                        <!-- Amount -->
                        <div class="col-md-6">
                            <div class="from__input-box">
                                <label class="form__input-title">Paid Amount <span>*</span></label>
                                <input type="number" name="amount" id="amount" class="form-control" step="0.01"
                                    min="0.01" required>
                                <small id="amountHelp" class="text-muted"></small>
                            </div>
                        </div>

                        <!-- EMI Fields (hidden by default) -->
                        <div class="col-md-12 emi-fields" style="display: none;">
                            <div class="from__input-box">
                                <label class="form__input-title">Due Amount</label>
                                <input type="number" name="due_emi_amount" id="dueEmiAmount" class="form-control"
                                    step="0.01" min="0" placeholder="Enter due amount for this EMI">
                                <small class="text-muted">This will be added to EMI schedule (optional)</small>
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

                        <!-- Bank Details (Visible only for bank/cheque) -->
                        <div class="col-md-6 bank-fields" style="display: none;">
                            <div class="from__input-box">
                                <label class="form__input-title">Bank Name <span>*</span></label>
                                <input type="text" name="bank_name" class="form-control" placeholder="Bank Name">
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

        const remainingLandShare = <?= json_encode($remainingLandShare) ?>;
        const isLandShareCompleted = <?= json_encode($isLandShareCompleted) ?>;

        // Function to update amount field help text and validation
        function updateAmountField() {
            const paymentTypeValue = paymentType.value;
            let helpText = '';
            let maxAmount = null;
            let minAmount = 0.01;

            switch (paymentTypeValue) {
                case 'land_share':
                    if (remainingLandShare > 0) {
                        helpText = 'Maximum: ৳' + remainingLandShare.toLocaleString('en-IN', { minimumFractionDigits: 2 });
                        maxAmount = remainingLandShare;
                        amountField.setAttribute('max', maxAmount);
                    } else {
                        helpText = 'Land share already completed';
                        amountField.setAttribute('disabled', 'disabled');
                    }
                    break;

                case 'emi':
                    helpText = 'EMI amount (10% service fee will be calculated)';
                    break;

                case 'registration':
                    helpText = 'Registration fee amount';
                    break;

                case 'construction_fee':
                    helpText = 'Construction fee payment';
                    break;

                default:
                    helpText = 'Enter payment amount';
            }

            amountHelp.textContent = helpText;

            // Enable field if it was disabled
            if (paymentTypeValue !== 'land_share' || remainingLandShare > 0) {
                amountField.removeAttribute('disabled');
            }

            // Remove any previous validation messages
            amountField.classList.remove('is-invalid');
            const invalidFeedback = amountField.nextElementSibling;
            if (invalidFeedback && invalidFeedback.classList.contains('invalid-feedback')) {
                invalidFeedback.remove();
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

        // Show/hide EMI fields when payment type changes
        if (paymentType) {
            paymentType.addEventListener('change', function () {
                const isEMI = this.value === 'emi';

                if (isEMI) {
                    emiFields.forEach(field => field.style.display = 'block');
                } else {
                    emiFields.forEach(field => field.style.display = 'none');
                    if (dueEmiAmountField) {
                        dueEmiAmountField.value = '';
                    }
                }

                // Update amount field help text
                updateAmountField();

                // Control partially paid status when payment type changes
                controlPartiallyPaidStatus();
            });
        }

        // Validate amount on input
        if (amountField) {
            amountField.addEventListener('input', function () {
                const paymentTypeValue = paymentType.value;
                const amount = parseFloat(this.value) || 0;

                if (paymentTypeValue === 'land_share') {
                    if (amount > remainingLandShare) {
                        this.classList.add('is-invalid');
                        const errorMsg = document.createElement('div');
                        errorMsg.className = 'invalid-feedback';
                        errorMsg.textContent = 'Amount cannot exceed remaining land share (৳' +
                            remainingLandShare.toLocaleString('en-IN', { minimumFractionDigits: 2 }) + ')';

                        // Remove previous error message
                        const existingError = this.nextElementSibling;
                        if (existingError && existingError.classList.contains('invalid-feedback')) {
                            existingError.remove();
                        }

                        // Insert after input field
                        this.parentNode.insertBefore(errorMsg, this.nextElementSibling);
                    } else {
                        this.classList.remove('is-invalid');
                        const invalidFeedback = this.nextElementSibling;
                        if (invalidFeedback && invalidFeedback.classList.contains('invalid-feedback')) {
                            invalidFeedback.remove();
                        }
                    }
                }
            });
        }

        // Listen to due amount field changes
        if (dueEmiAmountField) {
            dueEmiAmountField.addEventListener('input', function () {
                controlPartiallyPaidStatus();
            });

            dueEmiAmountField.addEventListener('change', function () {
                controlPartiallyPaidStatus();
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

                // Validation for land share payments
                if (paymentTypeValue === 'land_share') {
                    if (remainingLandShare <= 0) {
                        e.preventDefault();
                        alert('Land share payment is already completed!');
                        return false;
                    }

                    if (amount > remainingLandShare) {
                        e.preventDefault();
                        alert('Land share amount cannot exceed remaining: ৳' +
                            remainingLandShare.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
                        amountField.focus();
                        return false;
                    }

                    if (amount <= 0) {
                        e.preventDefault();
                        alert('Please enter a valid land share amount.');
                        amountField.focus();
                        return false;
                    }
                }

                // Validation for EMI payments
                if (paymentTypeValue === 'emi') {
                    // Only validate due amount if payment status is 'partially_paid'
                    if (paymentStatus === 'partially_paid' && (!dueAmount || dueAmount <= 0)) {
                        e.preventDefault();
                        alert('Partially Paid status requires a due amount greater than 0.');
                        if (dueEmiAmountField) {
                            dueEmiAmountField.focus();
                        }
                        return false;
                    }

                    // Check if amount is valid
                    if (amount <= 0) {
                        e.preventDefault();
                        alert('Please enter a valid EMI amount.');
                        amountField.focus();
                        return false;
                    }
                }

                // General amount validation
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
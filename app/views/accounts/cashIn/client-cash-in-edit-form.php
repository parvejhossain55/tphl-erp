<?php
$page_title = "Edit Transaction";
$page = 'Clients';
$addPage = false;
$show_breadcrumb = true;

// Get transaction details
$transaction = $transactionDetails ?? []; // This now matches the controller variable
$bookingId = $transaction['booking_id'] ?? $_GET['booking_id'] ?? null;

// Get EMI schedule if exists
$emiSchedule = $emiSchedule ?? null;

// DEBUG: Check if data is present
error_log("View - Transaction Data: " . print_r($transaction, true));
error_log("View - EMI Schedule: " . print_r($emiSchedule, true));
?>
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
</style>
<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="card__header mb-30">
                <h3 class="card__title">Edit Transaction for Client: <?= htmlspecialchars($clientDetails['client_name'] ?? '') ?></h3>
                <?php if (empty($transaction)): ?>
                    <div class="alert alert-danger">ERROR: No transaction data found!</div>
                <?php endif; ?>
            </div>

            <div class="card__body">
  

                <!-- Transaction Form -->
                <form action="<?= $GLOBALS['base_url'] ?>/update-transaction" method="post" id="transactionForm">
                    <input type="hidden" name="payment_id" value="<?= $transaction['id'] ?? '' ?>">
                    <input type="hidden" name="booking_id" value="<?= $bookingId ?>">

                    <div class="row gy-20">
                        <!-- Payment Type -->
                        <div class="col-md-6">
                            <div class="from__input-box">
                                <label class="form__input-title">Payment Type</label>
                                <?php if (($transaction['payment_type'] ?? '') === 'emi'): ?>
                                    <!-- Read-only for EMI transactions -->
                                    <input type="text" class="form-control" value="EMI" readonly>
                                    <input type="hidden" name="payment_type" value="emi">
                                    <?php if (!empty($emiSchedule['emi_no'])): ?>
                                        <small class="text-muted">EMI #<?= $emiSchedule['emi_no'] ?> - Cannot be changed</small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <!-- Editable for other payment types -->
                                    <select name="payment_type" id="paymentType" class="form-select" required>
                                        <option value="">Select Payment Type</option>
                                        <option value="booking_money" <?= ($transaction['payment_type'] ?? '') === 'booking_money' ? 'selected' : '' ?>>Booking Money</option>
                                        <option value="land_share" <?= ($transaction['payment_type'] ?? '') === 'land_share' ? 'selected' : '' ?>>Land Share</option>
                                        <option value="registration" <?= ($transaction['payment_type'] ?? '') === 'registration' ? 'selected' : '' ?>>Registration</option>
                                        <option value="emi" <?= ($transaction['payment_type'] ?? '') === 'emi' ? 'selected' : '' ?>>EMI</option>
                                        <option value="down_payment" <?= ($transaction['payment_type'] ?? '') === 'down_payment' ? 'selected' : '' ?>>Down Payment</option>
                                    </select>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Amount -->
                        <div class="col-md-6">
                            <div class="from__input-box">
                                <label class="form__input-title">Paid Amount <span>*</span></label>
                                <input type="number" name="amount" id="amountField" class="form-control" step="0.01" min="0" value="<?= $transaction['amount'] ?? '' ?>" required>
                            </div>
                        </div>

                        <!-- EMI Details (Visible only for EMI type or when changing to EMI) -->
                        <div class="col-md-12 emi-fields" style="display: <?= (($transaction['payment_type'] ?? '') === 'emi') ? 'block' : 'none' ?>;">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="from__input-box">
                                        <label class="form__input-title">Due Amount</label>
                                        <input type="number" name="due_emi_amount" id="dueEmiAmount" class="form-control"
                                            step="0.01" min="0"
                                            value="<?= $emiSchedule['due_emi_amount'] ?? ($transaction['schedule_due_emi_amount'] ?? 0) ?>"
                                            placeholder="Enter due amount for this EMI">

                                            <small class="text-muted">This amount is recorded in EMI schedule</small>
                                    </div>
                                </div>

                                <?php if (($transaction['payment_type'] ?? '') === 'emi' && !empty($emiSchedule['emi_no'])): ?>
                                    <div class="col-md-6">
                                        <div class="from__input-box">
                                            <label class="form__input-title">EMI Number</label>
                                            <input type="text" class="form-control" value="EMI #<?= $emiSchedule['emi_no'] ?? $transaction['emi_no'] ?? 'N/A' ?>" readonly>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="col-md-6">
                            <div class="from__input-box">
                                <label class="form__input-title">Payment Method <span>*</span></label>
                                <select name="payment_method" id="paymentMethod" class="form-select" required>
                                    <option value="cash" <?= ($transaction['payment_method'] ?? '') === 'cash' ? 'selected' : '' ?>>Cash</option>
                                    <option value="bank" <?= ($transaction['payment_method'] ?? '') === 'bank' ? 'selected' : '' ?>>Bank Transfer</option>
                                    <option value="online" <?= ($transaction['payment_method'] ?? '') === 'online' ? 'selected' : '' ?>>Online Payment</option>
                                    <option value="cheque" <?= ($transaction['payment_method'] ?? '') === 'cheque' ? 'selected' : '' ?>>Cheque</option>
                                </select>
                            </div>
                        </div>

                        <!-- Payment Date -->
                        <div class="col-md-6">
                            <div class="from__input-box">
                                <label class="form__input-title">Payment Date <span>*</span></label>
                                <input type="date" name="payment_date" class="form-control" value="<?= $transaction['payment_date'] ?? '' ?>" required>
                            </div>
                        </div>

                        <!-- Bank Details -->
                        <div class="col-md-6 bank-fields" style="display: <?= in_array($transaction['payment_method'] ?? '', ['bank', 'cheque']) ? 'block' : 'none' ?>;">
                            <div class="from__input-box">
                                <label class="form__input-title">Bank Name</label>
                                <input type="text" name="bank_name" class="form-control" placeholder="Bank Name" value="<?= $transaction['bank_name'] ?? '' ?>">
                            </div>
                        </div>

                        <div class="col-md-6 bank-fields" style="display: <?= in_array($transaction['payment_method'] ?? '', ['bank', 'cheque']) ? 'block' : 'none' ?>;">
                            <div class="from__input-box">
                                <label class="form__input-title">Bank Account No.</label>
                                <input type="text" name="bank_account_no" class="form-control" placeholder="Account Number" value="<?= $transaction['bank_account_no'] ?? '' ?>">
                            </div>
                        </div>

                        <!-- Cheque Details -->
                        <div class="col-md-6 cheque-fields" style="display: <?= ($transaction['payment_method'] ?? '') === 'cheque' ? 'block' : 'none' ?>;">
                            <div class="from__input-box">
                                <label class="form__input-title">Cheque Number</label>
                                <input type="text" name="cheque_no" class="form-control" placeholder="Cheque Number" value="<?= $transaction['cheque_no'] ?? '' ?>">
                            </div>
                        </div>

                        <!-- Online Details -->
                        <div class="col-md-6 online-fields" style="display: <?= ($transaction['payment_method'] ?? '') === 'online' ? 'block' : 'none' ?>;">
                            <div class="from__input-box">
                                <label class="form__input-title">Online Type</label>
                                <select name="online_type" class="form-select">
                                    <option value="bKash" <?= ($transaction['online_type'] ?? '') === 'bKash' ? 'selected' : '' ?>>bKash</option>
                                    <option value="Nagad" <?= ($transaction['online_type'] ?? '') === 'Nagad' ? 'selected' : '' ?>>Nagad</option>
                                    <option value="Rocket" <?= ($transaction['online_type'] ?? '') === 'Rocket' ? 'selected' : '' ?>>Rocket</option>
                                    <option value="Bank Card" <?= ($transaction['online_type'] ?? '') === 'Bank Card' ? 'selected' : '' ?>>Bank Card</option>
                                    <option value="Other" <?= ($transaction['online_type'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                        </div>

                        <!-- Transaction Reference -->
                        <div class="col-md-6">
                            <div class="from__input-box">
                                <label class="form__input-title">Transaction Reference</label>
                                <input type="text" name="transaction_ref" class="form-control" placeholder="Transaction ID/Reference" value="<?= $transaction['transaction_ref'] ?? '' ?>">
                            </div>
                        </div>

                        <!-- Payment Status -->
                        <div class="col-md-6">
                            <div class="from__input-box">
                                <label class="form__input-title">Payment Status <span>*</span></label>
                                <select name="payment_status" class="form-select" required>
                                    <option value="paid" <?= ($transaction['payment_status'] ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
                                    <option value="partially_paid" <?= ($transaction['payment_status'] ?? '') === 'partially_paid' ? 'selected' : '' ?>>Partially Paid</option>
                                    <option value="pending" <?= ($transaction['payment_status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="failed" <?= ($transaction['payment_status'] ?? '') === 'failed' ? 'selected' : '' ?>>Failed</option>
                                </select>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="col-md-12">
                            <div class="submit__btn text-center mt-30">
                                <button type="submit" class="btn btn-primary me-3">
                                    <i class="fa-solid fa-save me-2"></i>Update Transaction
                                </button>
                                <a href="<?= $GLOBALS['base_url'] ?>/clients-cash-in-history?booking_id=<?= $bookingId ?>" class="btn btn-secondary">
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
    document.addEventListener('DOMContentLoaded', function() {
        const paymentType = document.getElementById('paymentType');
        const paymentMethod = document.getElementById('paymentMethod');
        const emiFields = document.querySelectorAll('.emi-fields');
        const bankFields = document.querySelectorAll('.bank-fields');
        const chequeFields = document.querySelectorAll('.cheque-fields');
        const onlineFields = document.querySelectorAll('.online-fields');

        // Show/hide EMI fields when payment type changes
        if (paymentType) {
            paymentType.addEventListener('change', function() {
                const isEMI = this.value === 'emi';

                emiFields.forEach(field => field.style.display = isEMI ? 'block' : 'none');

                const dueAmountField = document.getElementById('dueEmiAmount');
                if (isEMI) {
                    dueAmountField.setAttribute('', '');
                } else {
                    dueAmountField.removeAttribute('');
                }
            });
        }

        // Show/hide payment method specific fields
        paymentMethod.addEventListener('change', function() {
            const method = this.value;

            // Hide all fields first
            bankFields.forEach(field => field.style.display = 'none');
            chequeFields.forEach(field => field.style.display = 'none');
            onlineFields.forEach(field => field.style.display = 'none');

            // Show relevant fields
            if (method === 'bank') {
                bankFields.forEach(field => field.style.display = 'block');
            } else if (method === 'cheque') {
                bankFields.forEach(field => field.style.display = 'block');
                chequeFields.forEach(field => field.style.display = 'block');
            } else if (method === 'online') {
                onlineFields.forEach(field => field.style.display = 'block');
            }
        });

document.getElementById('transactionForm').addEventListener('submit', function(e) {
    return true;
});

    });

//Partial status 

document.addEventListener('DOMContentLoaded', function() {
    const paymentType = document.getElementById('paymentType');
    const paymentMethod = document.getElementById('paymentMethod');
    const dueEmiAmountField = document.getElementById('dueEmiAmount');
    const paymentStatusSelect = document.querySelector('select[name="payment_status"]');
    const amountField = document.getElementById('amount') || document.getElementById('amountField');
    
    const emiFields = document.querySelectorAll('.emi-fields');
    const bankFields = document.querySelectorAll('.bank-fields');
    const chequeFields = document.querySelectorAll('.cheque-fields');
    const onlineFields = document.querySelectorAll('.online-fields');
    
    // Function to control Partially Paid status
    function controlPartiallyPaidStatus() {
        const paymentTypeValue = paymentType ? paymentType.value : document.querySelector('input[name="payment_type"]')?.value;
        const dueAmount = parseFloat(dueEmiAmountField?.value) || 0;
        const partiallyPaidOption = paymentStatusSelect.querySelector('option[value="partially_paid"]');
        
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
        paymentType.addEventListener('change', function() {
            const isEMI = this.value === 'emi';
            
            emiFields.forEach(field => field.style.display = isEMI ? 'block' : 'none');
            
            if (dueEmiAmountField) {
                if (isEMI) {
                    dueEmiAmountField.setAttribute('required', 'required');
                } else {
                    dueEmiAmountField.removeAttribute('required');
                    dueEmiAmountField.value = ''; // Clear the value
                }
            }
            
            // Control partially paid status when payment type changes
            controlPartiallyPaidStatus();
        });
    }
    
    // Listen to due amount field changes
    if (dueEmiAmountField) {
        dueEmiAmountField.addEventListener('input', function() {
            controlPartiallyPaidStatus();
        });
        
        dueEmiAmountField.addEventListener('change', function() {
            controlPartiallyPaidStatus();
        });
    }
    
    // Show/hide payment method specific fields
    if (paymentMethod) {
        paymentMethod.addEventListener('change', function() {
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
        transactionForm.addEventListener('submit', function(e) {
            const paymentTypeValue = paymentType ? paymentType.value : document.querySelector('input[name="payment_type"]')?.value;
            const amount = parseFloat(amountField?.value) || 0;
            const dueAmount = parseFloat(dueEmiAmountField?.value) || 0;
            const paymentStatus = paymentStatusSelect.value;
            
            // Validation for EMI payments
            if (paymentTypeValue === 'emi') {
                // Only validate due amount if payment status is 'partially_paid'
                if (paymentStatus === 'partially_paid' && (!dueAmount || dueAmount <= 0)) {
                    e.preventDefault();
                    alert('Partially Paid status requires a due amount greater than 0.');
                    dueEmiAmountField?.focus();
                    return false;
                }
                
                // For paid status, due amount can be 0 or null
                // No validation needed
            }
            
            return true;
        });
    }
    
    // Initial call to set the correct state on page load
    controlPartiallyPaidStatus();
});
</script>
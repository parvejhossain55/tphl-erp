<?php
$page_title = "Add Transaction";
$page = 'Clients';
$addPage = false;
$show_breadcrumb = true;

// Get booking ID from URL or session
$bookingId = $_GET['booking_id'] ?? $clientDetails['id'] ?? null;
?>

<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="card__header mb-30">
                <h3 class="card__title">Add Transaction for Client: <?= htmlspecialchars($clientDetails['client_name'] ?? '') ?></h3>
            </div>
            
            <div class="card__body">
                <!-- Client Summary -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <div class="row">
                                <div class="col-md-3">
                                    <strong>Client:</strong> <br><?= htmlspecialchars($clientDetails['client_name'] ?? '') ?>
                                </div>
                                <div class="col-md-3">
                                    <strong>Agreement Value:</strong> <br>৳<?= number_format($clientDetails['agreement_value'] ?? 0, 2) ?>
                                </div>
                                <div class="col-md-3">
                                    <strong>Total Paid:</strong> <br>৳<?= number_format($clientDetails['total_paid'] ?? 0, 2) ?>
                                </div>
                                <div class="col-md-3">
                                    <strong>Due Amount:</strong> <br>৳<?= number_format(($clientDetails['agreement_value'] ?? 0) - ($clientDetails['total_paid'] ?? 0), 2) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transaction Form -->
                <form action="<?= $GLOBALS['base_url'] ?>/add-transaction" method="post" id="transactionForm">
                    <input type="hidden" name="booking_id" value="<?= $bookingId ?>">
                    
                    <div class="row gy-20">
                        <!-- Payment Type -->
<!-- Payment Type Field -->
<div class="col-md-6 mb-3">
    <label class="form-label">Payment Type: <span class="text-danger">*</span></label>
    <select name="payment_type" class="form-control" id="paymentType" required>
        <option value="">-- Select Payment Type --</option>
        
        <!-- Registration - Always available -->
        <option value="registration" 
                data-details="false"
                <?= (isset($_POST['payment_type']) && $_POST['payment_type'] == 'registration') ? 'selected' : '' ?>>
            Registration
        </option>
                
        <!-- EMI - Show only if registration is paid -->
        <?php if ($registrationPaid): ?>
            <option value="emi" 
                    data-details="true"
                    <?= (isset($_POST['payment_type']) && $_POST['payment_type'] == 'emi') ? 'selected' : '' ?>>
                EMI
            </option>
        <?php else: ?>
            <!-- Disabled option with message -->
            <option value="emi" disabled>
                EMI (Registration Required)
            </option>
        <?php endif; ?>
    </select>
    
    <!-- Simple help text -->
    <small class="text-muted">
        <?php if (!$registrationPaid): ?>
            <span class="text-warning">
                <i class="fa-light fa-exclamation-triangle"></i> 
                EMI option will be available after registration payment is made.
            </span>
        <?php endif; ?>
    </small>
</div>

<!-- EMI Details Section -->
<div class="col-md-6 mb-3" id="emiDetailsSection" style="display: none;">
    <label class="form-label">Due EMI Amount: <span class="text-danger">*</span></label>
    <input type="number" name="due_emi_amount" class="form-control" step="0.01" min="0.01" 
           value="<?= $_POST['due_emi_amount'] ?? '' ?>">
    <small class="text-muted">Enter the due amount for this EMI installment</small>
</div>

                        <!-- Amount -->
                        <div class="col-md-6">
                            <div class="from__input-box">
                                <label class="form__input-title">Amount <span>*</span></label>
                                <input type="number" name="amount" id="amount" class="form-control" step="0.01" min="0" required>
                            </div>
                        </div>

                        <!-- EMI Details (Visible only for EMI type) -->
                        <div class="col-md-12 emi-fields" style="display: none;">
                            <div class="from__input-box">
                                <label class="form__input-title">Due Amount <span>*</span></label>
                                <input type="number" name="due_emi_amount" id="dueEmiAmount" class="form-control" step="0.01" min="0" placeholder="Enter due amount for this EMI">
                                <small class="text-muted">This will be added to EMI schedule</small>
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
                                <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
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
                                <input type="text" name="bank_account_no" class="form-control" placeholder="Account Number">
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
                                <input type="text" name="transaction_ref" class="form-control" placeholder="Transaction ID/Reference">
                            </div>
                        </div>

                        <!-- Payment Status -->
                        <div class="col-md-6">
                            <div class="from__input-box">
                                <label class="form__input-title">Payment Status <span>*</span></label>
                                <select name="payment_status" class="form-select" required>
                                    <option value="paid">Paid</option>
                                    <option value="partially_paid">Partially Paid</option>
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
    
    // Show/hide EMI fields
    paymentType.addEventListener('change', function() {
        if (this.value === 'emi') {
            emiFields.forEach(field => field.style.display = 'block');
            document.getElementById('dueEmiAmount').removeAttribute('required', 'required');
        } else {
            emiFields.forEach(field => field.style.display = 'none');
            document.getElementById('dueEmiAmount').removeAttribute('required');
        }
    });
    
    // Show/hide payment method specific fields
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

    // Form validation
    document.getElementById('transactionForm').addEventListener('submit', function(e) {
        const paymentType = document.getElementById('paymentType').value;
        const amount = parseFloat(document.getElementById('amount').value) || 0;
        const dueAmount = parseFloat(document.getElementById('dueEmiAmount').value) || '';
        

    });
});


</script>
<?php
$page_title = "Add Transaction (CED)";
$page = 'Clients';
$addPage = false;
$show_breadcrumb = true;

// Get booking ID from URL or session
$bookingId = $_GET['booking_id'] ?? $clientDetails['id'] ?? null;

// Get payment summary
$accountModel = new AccountClient();
$paymentSummary = $accountModel->getPaymentSummaryByBookingId($bookingId);

$isLandShareCompleted  = $paymentSummary['is_land_share_completed'] ?? false;
$isRegistrationPaid    = $accountModel->isRegistrationPaid($bookingId);
$devChargePaid         = $paymentSummary['development_charge_paid'] ?? 0;
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
                <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
                    <strong><i class="fa-light fa-info-circle me-1"></i>CED Payment Entry</strong>
                    Transactions submitted here will be marked as <em>Pending Verification</em>.
                    The Accounts team must verify them before they appear in fiscal reports.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>

                <?php if (!$isRegistrationPaid): ?>
                    <div class="alert alert-danger mb-4">
                        <strong><i class="fa-light fa-lock me-1"></i>Registration Required!</strong>
                        Registration payment must be completed before adding any transactions from CED.
                    </div>
                <?php else: ?>
                <!-- Transaction Form -->
                <form action="<?= $GLOBALS['base_url'] ?>/ced-add-transaction" method="post" id="transactionForm" enctype="multipart/form-data">
                    <input type="hidden" name="booking_id" value="<?= $bookingId ?>">

                    <div class="row gy-20">
                        <!-- Payment Type (CED: dev charge + EMI only) -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Type: <span class="text-danger">*</span></label>
                            <select name="payment_type" class="form-control" id="paymentType" required>
                                <option value="">-- Select Payment Type --</option>

                                <!-- Development Charges - always available when registration paid -->
                                <option value="development_charge" data-details="false">
                                    Development Charges
                                </option>

                                <!-- EMI - Only if land share is completed -->
                                <?php if ($isLandShareCompleted): ?>
                                    <option value="emi" data-details="true">
                                        EMI (Construction Fee)
                                    </option>
                                <?php else: ?>
                                    <option value="emi" disabled>
                                        EMI (Requires: Land Share Completed)
                                    </option>
                                <?php endif; ?>
                            </select>

                            <!-- Help text -->
                            <small class="text-muted mt-2 d-block">
                                <?php if (!$isLandShareCompleted): ?>
                                    <span class="text-warning">
                                        <i class="fa-light fa-exclamation-triangle"></i>
                                        Land share must be completed before EMI payments
                                    </span>
                                <?php else: ?>
                                    <span class="text-success">
                                        <i class="fa-light fa-circle-check"></i>
                                        Ready for development charges and construction fee payments
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

        // Function to update amount field help text
        function updateAmountField() {
            if (!paymentType) return;
            const paymentTypeValue = paymentType.value;
            let helpText = '';

            switch (paymentTypeValue) {
                case 'emi':
                    helpText = 'EMI amount (10% service fee will be calculated)';
                    break;
                case 'development_charge':
                    helpText = 'Development charge payment';
                    break;
                default:
                    helpText = 'Enter payment amount';
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

        // Show/hide EMI fields when payment type changes
        if (paymentType) {
            paymentType.addEventListener('change', function () {
                const isEMI = this.value === 'emi';

                if (isEMI) {
                    emiFields.forEach(field => field.style.display = 'block');
                } else {
                    emiFields.forEach(field => field.style.display = 'none');
                    if (dueEmiAmountField) dueEmiAmountField.value = '';
                }

                updateAmountField();
                controlPartiallyPaidStatus();
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

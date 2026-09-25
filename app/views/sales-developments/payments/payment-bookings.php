<?php
$page_title = "Add Payment";
$page = 'Payments';
$show_breadcrumb = true;

if (session_status() === PHP_SESSION_NONE) session_start();

$allBangladeshBanks = function_exists('getBangladeshBanks') ? getBangladeshBanks() : [];

$finalAmount = !empty($booking['approved_amount'])
    ? $booking['agreement_value'] - $booking['approved_amount']
    : $booking['agreement_value'];

if ($finalAmount <= 0) {
    $finalAmount = $booking['agreement_value'];
}

$booking_paid = 0;
$booking_pending_and_paid = 0;
$registration_pending_and_paid = 0;
$has_registration = false;
$existing_emi_no = null;

foreach ($payments as $p) {
    if (!empty($p['total_emi_no'])) {
        $existing_emi_no = $p['total_emi_no'];
    }
    
    if ($p['payment_type'] === 'booking_money') {
        if ($p['verification_status'] == 'verified') {
            $booking_paid += $p['amount'];
        }
        if (in_array($p['verification_status'], ['verified', 'pending'])) {
            $booking_pending_and_paid += $p['amount'];
        }
    } elseif ($p['payment_type'] === 'registration') {
        if (in_array($p['verification_status'], ['verified', 'pending'])) {
            $registration_pending_and_paid += $p['amount'];
            $has_registration = true;
        }
    }
}

$remaining = $finalAmount - $booking_pending_and_paid;
$is_booking_fully_paid = ($remaining <= 0);

$totalRegistrationAmount = abs((float)($booking['land_share_value'] ?? 0) - (float)($booking['agreement_value'] ?? 0));
$registrationRemaining = $totalRegistrationAmount - $registration_pending_and_paid;
$is_registration_fully_paid = ($registrationRemaining <= 0);

$showBookingForm = false;
$form_mode = 'booking_money';

if (!$is_booking_fully_paid) {
    $showBookingForm = true;
    $form_mode = 'booking_money';
} elseif (!$is_registration_fully_paid) {
    $showBookingForm = true;
    $form_mode = 'registration';
}

$userRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
$isSalesHead = ((strpos($userRoleName, 'sales') !== false && strpos($userRoleName, 'head') !== false) || strpos($userRoleName, 'admin') !== false || $_SESSION['user']['role_id'] == 1);
?>



<!-- booking_money type select kore rakhte hobe   -->
<?php if ($showBookingForm): ?>

    <div class="row mb-4" id="bookingPayment">
        <div class="col-xxl-12">
            <div class="card__wrapper p-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h5 class="mb-0"><?= $form_mode === 'registration' ? 'Add Registration Payment for' : 'Add Booking Payment for' ?> <?= htmlspecialchars($booking['full_name'] ?? 'Lead') ?></h5>
                    <?php if (empty($booking['verified_payment_count']) || (int)$booking['verified_payment_count'] === 0): ?>
                        <div class="d-flex gap-2">
                            <a href="<?= $GLOBALS['base_url'] ?>/booking-leads/<?= $booking['lead_id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fa-solid fa-pen-to-square me-1"></i> Edit Booking
                            </a>
                            <a href="<?= $GLOBALS['base_url'] ?>/booking-delete/<?= $booking['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this entire booking? All unverified payment records will be removed and the flat will be reset to available status.');">
                                <i class="fa-solid fa-trash me-1"></i> Delete Booking
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <form action="<?= $GLOBALS['base_url'] ?>/payment-booking-store" method="POST" enctype="multipart/form-data">
                    <div class="row gy-3">

                        <!-- Hidden Inputs -->
                        <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                        <input type="hidden" name="created_by" value="<?= $_SESSION['user']['id'] ?>">

                        <!-- Lead Info (Readonly) -->
                        <div class="col-lg-6">
                            <div class="form__input-title">

                                <label class="form__input-title">Lead Name</label>
                            </div>
                            <input type="text" class="form-control" value="<?= $booking['full_name'] ?>" readonly>
                        </div>

                        <div class="col-lg-6">
                            <div class="form__input-title">

                                <label class="form__input-title">Project</label>
                            </div>
                            <input type="text" class="form-control" value="<?= $booking['project_name'] ?>" readonly>
                        </div>
                        <div class="col-lg-6">
                            <div class="form__input-title">

                                <label class="form__input-title">Number Of Share</label>
                            </div>
                            <input type="text" class="form-control" value="<?= $booking['total_share'] ?>" readonly>
                        </div>

                        <div class="col-lg-6">
                            <div class="form__input-title">

                                <label class="form__input-title">Flat / Unit</label>
                            </div>
                            <input type="text" class="form-control" value="<?= $booking['unit_name'] ?> (Floor: <?= $booking['floor_no'] ?>)" readonly>
                        </div>
                        <div class="col-lg-6">
                            <div class="form__input-title">

                                <label class="form__input-title">Flat Size</label>
                            </div>
                            <input type="text" class="form-control" value="<?= $booking['sqft'] ?>" readonly>
                        </div>
                        <?php if ($form_mode !== 'registration'): ?>
                            <div class="col-lg-6">
                                <div class="form__input-title">
                                    <label class="form__input-title">Project Share Value</label>
                                </div>
                                <input type="text" class="form-control" value="<?= number_format($booking['price_value'], 2) ?>" readonly>
                            </div>
                            <div class="col-lg-6">
                                <div class="form__input-title">
                                    <label class="form__input-title">Agreement Value</label>
                                </div>
                                <input type="text" class="form-control" value="<?= number_format($booking['agreement_value'], 2) ?>" readonly>
                            </div>
                            <?php if ($booking['approved_amount'] > 0): ?>
                                <div class="col-lg-6">
                                    <div class="form__input-title">
                                        <label class="form__input-title">Discount Value</label>
                                    </div>
                                    <input type="text" class="form-control" value="<?= number_format($booking['approved_amount'] ?? 0, 2) ?>" readonly>
                                </div>
                            <?php endif; ?>
                            <div class="col-lg-6">
                                <div class="form__input-title">
                                    <label class="form__input-title">Total EMI Number</label>
                                </div>
                                <input type="number" name="total_emi_no" class="form-control" value="<?= htmlspecialchars($existing_emi_no ?? '') ?>" <?= !empty($existing_emi_no) ? 'readonly' : '' ?>>
                            </div>
                        <?php endif; ?>

                        <!-- Payment Type -->
                        <div class="col-lg-6">
                            <div class="form__input-title">
                                <label class="form__input-title">Payment Type *</label>
                            </div>
                            <?php if ($form_mode === 'registration'): ?>
                                <select name="payment_type_view" class="form-select" disabled>
                                    <option value="registration" selected>Registration</option>
                                </select>
                                <input type="hidden" name="payment_type" value="registration">
                            <?php else: ?>
                                <select name="payment_type" class="form-select" required>
                                    <option value="booking_money" selected>Booking</option>
                                </select>
                            <?php endif; ?>
                        </div>

                        <!-- Payment Method -->
                        <div class="col-lg-6">
                            <div class="form__input-title">

                                <label class="form__input-title">Payment Method *</label>
                            </div>
                            <select name="payment_method" class="form-select payment-method" required>
                                <option value="">Select Method</option>
                                <option value="cash">Cash</option>
                                <option value="bank">Bank</option>
                                <option value="online">Online</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>

                        <!-- Company Bank Details -->
                        <div class="col-lg-6 extra-input" id="companyBankFields" style="display:none;">
                            <div class="form-group mb-3">
                                <label class="form__input-title">Transfer To / Company Bank *</label>
                                <div class="input-group">
                                    <select name="bank_id" id="bankSelect" class="form-select">
                                        <option value="">-- Loading banks... --</option>
                                    </select>
                                    <button type="button" class="btn btn-outline-primary" id="addCompanyBankBtn" title="Add Company Bank"><i class="fa-light fa-plus"></i></button>
                                    <button type="button" class="btn btn-outline-secondary" id="editCompanyBankBtn" title="Edit Selected Bank" style="display:none;"><i class="fa-light fa-pen-to-square"></i></button>
                                </div>
                            </div>
                        </div>

                        <!-- Bank Details -->
                        <div class="col-lg-6 extra-input" id="bankFields" style="display:none;">
                            <div class="form__input-title">
                                <label class="form__input-title">Transfer From / Client Bank *</label>
                            </div>
                            <select name="bank_name" id="clientBankSelect" class="form-select" style="width: 100%;">
                                <option value="">-- Select Client Bank --</option>
                                <?php foreach ($allBangladeshBanks as $bName): ?>
                                    <option value="<?= htmlspecialchars($bName) ?>"><?= htmlspecialchars($bName) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-lg-6 extra-input" id="otherBankField" style="display:none;">
                            <div class="form__input-title">
                                <label class="form__input-title" id="customBankLabel">Specify Foreign / Other Bank Name *</label>
                            </div>
                            <input type="text" name="custom_bank_name" id="customBankInput" class="form-control" placeholder="e.g. Emirates NBD, Chase Bank, Al Rajhi, etc.">
                        </div>

                        <div class="col-lg-6 extra-input" id="bankAccountFields" style="display:none;">
                            <div class="form__input-title">

                                <label class="form__input-title">Client Bank A/C No. (Optional)</label>
                            </div>
                            <input type="text" name="bank_account_no" class="form-control" placeholder="Account Number">
                        </div>

                        <!-- Cheque Details -->
                        <div class="col-lg-6 extra-input" id="chequeFields" style="display:none;">
                            <div class="form__input-title">

                                <label class="form__input-title">Cheque No *</label>
                            </div>
                            <input type="text" name="cheque_no" class="form-control" placeholder="Enter Cheque Number">
                        </div>

                        <!-- Online Details -->
                        <div class="col-lg-6 extra-input" id="onlineFields" style="display:none;">
                            <label class="form__input-title">Online Type *</label>
                            <input type="text" name="online_type" class="form-control" placeholder="Enter Online Type (Bkash, Nagad, etc)">
                        </div>
                       
                        <?php
                        $registrationAmountValue = '';
                        if ($form_mode === 'registration') {
                            $placeholderText = "Remaining: " . number_format($registrationRemaining, 2);
                            $registrationAmountValue = $registrationRemaining;
                        } else {
                            if ($booking_pending_and_paid == 0) {
                                $placeholderText = ($booking['approved_amount'] > 0)
                                
                                    ? "After Discount: " . number_format($finalAmount, 2)
                                    : "Final Amount: " . number_format($finalAmount, 2);
                            } else {
                                $placeholderText = "Remaining: " . number_format($remaining, 2);
                            }
                        }
                        ?>

                        <!-- Amount -->
                        <div class="col-lg-6">
                            <div class="form__input-title">
                                <label class="form__input-title"><?= $form_mode === 'registration' ? 'Pay Registration Money' : 'Pay Booking Money' ?> *</label>
                                <?php if ($form_mode === 'registration'): ?>
                                    <small class="text-muted d-block" style="font-size: 0.8rem; margin-top: -5px; margin-bottom: 5px;">Remaining: <?= number_format($registrationRemaining, 2) ?></small>
                                <?php endif; ?>
                            </div>
                            <input type="number" step="1" name="amount"
                                class="form-control"
                                placeholder="<?= $placeholderText ?>"
                                <?= $form_mode === 'booking_money' ? 'max="' . $remaining . '"' : '' ?>
                                <?= $form_mode === 'registration' ? 'max="' . $registrationRemaining . '" value="' . $registrationAmountValue . '"' : '' ?>
                                required>
                        </div>

                        <!-- Payment Date -->
                        <div class="col-lg-6">
                            <div class="form__input-title">

                                <label class="form__input-title">Payment Date *</label>
                            </div>
                            <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" <?= $isSalesHead ? '' : 'readonly style="pointer-events:none; background-color:#e9ecef;" title="Only Sales Head can edit the payment date"' ?> required>
                        </div>

                        <!-- Transaction Reference -->
                        <div class="col-lg-6">
                            <div class="form__input-title">
                                <label class="form__input-title">Transaction Reference</label>
                            </div>
                            <input type="text" name="transaction_ref" class="form-control" placeholder="Required reference number" required>
                        </div>

                        <!-- Attachment Image -->
                        <div class="col-lg-6">
                            <div class="form__input-title">
                                <label class="form__input-title">Attachment Image</label>
                            </div>
                            <input type="file" name="attachment_image" class="form-control" accept="image/*,application/pdf">
                        </div>

                        <!-- Status -->
                        <div class="col-lg-6">
                            <div class="form__input-title">

                                <label class="form__input-title">Payment Status</label>
                            </div>
                            <select name="payment_status" class="form-select">
                                <!-- <option value="paid">Paid</option> -->
                                <option value="pending">Pending</option>
                                <!-- <option value="failed">Failed</option> -->
                            </select>
                        <!-- Testing Mode Switch & Phone Alert -->
                        <div class="col-lg-12">
                            <?php 
                                $clientPhone = trim($booking['phone'] ?? ($booking['phone_number'] ?? ''));
                                $isPhoneValid = (strlen($clientPhone) === 11 && preg_match('/^01[3-9]\d{8}$/', $clientPhone));
                            ?>
                            <?php if (!$isPhoneValid): ?>
                                <div class="alert alert-warning d-flex align-items-center justify-content-between p-2 mb-2">
                                    <div class="small">

                                        <strong>Invalid Client Phone:</strong> Client phone (<?= htmlspecialchars($clientPhone ?: 'Empty') ?>) is missing or not a valid 11-digit mobile number.
                                    </div>
                                    <span class="badge bg-danger">SMS Notification Blocked</span>
                                </div>
                            <?php endif; ?>
                            <div class="col-lg-12">
                                <div class="form-check form-switch p-2 bg-light border rounded mb-2">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="send_sms" id="sendSmsToggle" value="1">
                                    <label class="form-check-label fw-semibold text-primary" for="sendSmsToggle">
                                        <i class="fa-solid fa-envelope me-1"></i> Send SMS Notification
                                    </label>
                                </div>
                                <div class="form-check form-switch p-2 bg-light border rounded">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="is_testing_mode" id="isTestingMode" value="1">
                                    <label class="form-check-label fw-semibold text-warning" for="isTestingMode">
                                        <i class="fa-solid fa-vial me-1"></i> Testing Mode (Skip SMS & Bypass Phone Validation)
                                    </label>
                                </div>
                            </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-success">Add Payment</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
<?php endif ?>

<!-- Payment Table -->
<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th># Payment Date</th>
                            <th>Type</th>
                            <th>Total Amount</th>
                            <th>Paid Amount</th>
                            <th>Remaining Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Payment Date</th>
                            <th>Transaction Ref</th>
                            <th>Created By</th>
                            <th>Verified By</th>
                            <th>Verification Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($payments)): ?>

                            <?php
                            // Step 1: calculate final agreement value
                            $finalAmount = !empty($booking['approved_amount'])
                                ? $booking['agreement_value'] - $booking['approved_amount']
                                : $booking['agreement_value'];

                            if ($finalAmount <= 0) {
                                $finalAmount = $booking['agreement_value'];
                            }

                            // Step 2: running total for ledger style remaining
                            $running_paid = 0;
                            $registration_running_paid = 0;
                            $totalRegistrationAmount = abs((float)($booking['land_share_value'] ?? 0) - (float)($booking['agreement_value'] ?? 0));
                            ?>

                            <?php foreach ($payments as $index => $p): ?>

                                <?php
                                // Only verified payments affect balance
                                if (
                                    $p['payment_type'] === 'booking_money' &&
                                    $p['verification_status'] === 'verified'
                                ) {
                                    $running_paid += $p['amount'];
                                } elseif (
                                    $p['payment_type'] === 'registration' &&
                                    $p['verification_status'] === 'verified'
                                ) {
                                    $registration_running_paid += $p['amount'];
                                }

                                $row_remaining = $finalAmount - $running_paid;
                                $reg_row_remaining = $totalRegistrationAmount - $registration_running_paid;
                                ?>
                        
                                <tr>
                                    <td>
                                        (<?= $index + 1 ?>) <?= date('d M Y', strtotime($p['payment_date'])) ?>
                                    </td>

                                    <td><?= ucfirst($p['payment_type']) ?></td>

                                    <td>
                                        <?php if ($p['payment_type'] === 'booking_money'): ?>
                                            <?= number_format($p['agreement_value'], 2) ?>
                                        <?php elseif ($p['payment_type'] === 'registration'): ?>
                                            <?= number_format($totalRegistrationAmount, 2) ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>

                                    <td><?= number_format($p['amount'], 2) ?></td>

                                    <!--  Correct Remaining (ledger style) -->
                                    <td>
                                        <?php if ($p['payment_type'] === 'booking_money'): ?>
                                            <?= number_format($row_remaining, 2) ?>
                                        <?php elseif ($p['payment_type'] === 'registration'): ?>
                                            <?= number_format($reg_row_remaining, 2) ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <strong><?= ucfirst($p['payment_method']) ?></strong>
                                        <?php if (!empty($p['company_bank_name'])): ?>
                                            <br><small class="text-success"><i class="fa-solid fa-building-columns me-1"></i>To: <?= htmlspecialchars($p['company_bank_name']) ?><?= !empty($p['company_account_number']) ? ' (' . htmlspecialchars($p['company_account_number']) . ')' : '' ?></small>
                                        <?php endif; ?>
                                        <?php if (!empty($p['bank_name'])): ?>
                                            <br><small class="text-muted"><i class="fa-solid fa-user me-1"></i>From: <?= htmlspecialchars($p['bank_name']) ?></small>
                                        <?php endif; ?>
                                    </td>

                                    <td><?= ucfirst($p['payment_status']) ?></td>

                                    <td><?= date('d M Y', strtotime($p['payment_date'])) ?></td>

                                    <td><?= $p['transaction_ref'] ?? '-' ?></td>

                                    <td><?= $p['created_by_name'] ?? '-' ?></td>

                                    <td><?= $p['verified_by_name'] ?? '-' ?></td>

                                    <td>
                                        <?php if ($p['verification_status'] == 'verified'): ?>
                                            <span class="badge bg-success">Verified</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php endif; ?>
                                    </td>

                                    <td style="white-space: nowrap;">
                                        <div class="d-flex align-items-center gap-1">
                                            <!-- Print Invoice (Always enabled so money receipt can be generated even if verification is pending) -->
                                            <a target="_blank"
                                                href="<?= $GLOBALS['base_url'] ?>/payment-invoice?payment_id=<?= $p['id'] ?>"
                                                class="btn btn-success btn-sm"
                                                title="Print Money Receipt / Invoice">
                                                <i class="fa-solid fa-receipt me-1"></i> <?= $p['payment_type'] === 'registration' ? 'Registration Invoice' : ($p['payment_type'] === 'land_share' ? 'Land Share Invoice' : 'Booking Invoice') ?>
                                            </a>

                                            <!-- Edit Payment -->
                                            <?php if ($p['verification_status'] != 'verified' || $isSalesHead): ?>
                                                <button type="button" 
                                                    class="btn btn-outline-primary btn-sm editPaymentBtn"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editPaymentModal"
                                                    data-id="<?= $p['id'] ?>"
                                                    data-booking-id="<?= $booking['id'] ?>"
                                                    data-amount="<?= $p['amount'] ?>"
                                                    data-method="<?= $p['payment_method'] ?>"
                                                    data-date="<?= $p['payment_date'] ?>"
                                                    data-ref="<?= htmlspecialchars($p['transaction_ref'] ?? '') ?>"
                                                    data-bank-id="<?= $p['bank_id'] ?? '' ?>"
                                                    data-bank-name="<?= htmlspecialchars($p['bank_name'] ?? '') ?>"
                                                    data-account-no="<?= htmlspecialchars($p['bank_account_no'] ?? '') ?>"
                                                    data-cheque="<?= htmlspecialchars($p['cheque_no'] ?? '') ?>"
                                                    data-online="<?= htmlspecialchars($p['online_type'] ?? '') ?>"
                                                    data-verified="<?= $p['verification_status'] === 'verified' ? 'true' : 'false' ?>"
                                                    title="Edit Payment Record">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                            <?php endif; ?>

                                            <!-- Delete only for unverified payments -->
                                            <?php if ($p['verification_status'] != 'verified'): ?>
                                                <a href="<?= $GLOBALS['base_url'] ?>/payment-booking-delete/<?= $p['id'] ?>"
                                                    class="btn btn-outline-danger btn-sm"
                                                    onclick="return confirm('Are you sure you want to delete this payment record?');"
                                                    title="Delete Payment">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>
                            <tr>
                                <td colspan="13" class="text-center">No payments found for this onboarding.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>



<?php if (!$showBookingForm): ?>
    <script>
        document.querySelector('#bookingPayment').style.display = 'none';
    </script>
<?php endif; ?>
<script>
    document.querySelectorAll('.payment-method').forEach(select => {
        select.addEventListener('change', function() {
            const method = this.value;

            // Hide all extra input fields
            document.querySelectorAll('.extra-input').forEach(div => {
                div.style.display = 'none';
                div.querySelectorAll('input, select').forEach(el => el.removeAttribute('required'));
            });
            const otherBankField = document.querySelector('#otherBankField');
            if (otherBankField) otherBankField.style.display = 'none';
            const customBankInput = document.querySelector('#customBankInput');
            if (customBankInput) {
                customBankInput.removeAttribute('required');
                customBankInput.value = '';
            }

            // Show relevant fields
            if (method === 'bank') {
                document.querySelector('#companyBankFields').style.display = 'block';
                document.querySelector('#bankSelect')?.setAttribute('required', 'required');
                document.querySelector('#bankFields').style.display = 'block';
                document.querySelector('#clientBankSelect')?.setAttribute('required', 'required');

                document.querySelector('#bankAccountFields').style.display = 'block';
                document.querySelector('#bankAccountFields input')?.setAttribute('required', 'required');
            } else if (method === 'cheque') {
                document.querySelector('#companyBankFields').style.display = 'block';
                document.querySelector('#bankSelect')?.setAttribute('required', 'required');
                document.querySelector('#bankFields').style.display = 'block';
                document.querySelector('#clientBankSelect')?.setAttribute('required', 'required');
                document.querySelector('#chequeFields').style.display = 'block';
                document.querySelector('#chequeFields input')?.setAttribute('required', 'required');
            } else if (method === 'online') {
                document.querySelector('#onlineFields').style.display = 'block';
                document.querySelector('#onlineFields input')?.setAttribute('required', 'required');
            }

            // Initialize or re-init Select2 on clientBankSelect if jQuery and select2 available
            if (method === 'bank' || method === 'cheque') {
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
            }
        });
    });

    // Handle "Other Country / Foreign Bank" & "Other Bank" selection in main form
    document.addEventListener('DOMContentLoaded', function() {
        if (window.jQuery) {
            $(document).on('change', '#clientBankSelect', function() {
                const val = this.value;
                if (val === 'Other Bank' || val === 'Other Country / Foreign Bank') {
                    if (val === 'Other Country / Foreign Bank') {
                        $('#customBankLabel').text('Specify Foreign / Other Country Bank Name *');
                        $('#customBankInput').attr('placeholder', 'e.g. Emirates NBD, Chase Bank, Al Rajhi, etc.');
                    } else {
                        $('#customBankLabel').text('Specify Client Bank Name *');
                        $('#customBankInput').attr('placeholder', 'Enter bank name');
                    }
                    $('#otherBankField').show();
                    $('#customBankInput').attr('required', 'required');
                } else {
                    $('#otherBankField').hide();
                    $('#customBankInput').removeAttr('required').val('');
                }
            });
        } else {
            const clientBankSelect = document.getElementById('clientBankSelect');
            if (clientBankSelect) {
                clientBankSelect.addEventListener('change', function() {
                    const val = this.value;
                    const otherBankField = document.getElementById('otherBankField');
                    const customBankInput = document.getElementById('customBankInput');
                    const customBankLabel = document.getElementById('customBankLabel');
                    if (val === 'Other Bank' || val === 'Other Country / Foreign Bank') {
                        if (val === 'Other Country / Foreign Bank' && customBankLabel) {
                            customBankLabel.textContent = 'Specify Foreign / Other Country Bank Name *';
                            if (customBankInput) customBankInput.placeholder = 'e.g. Emirates NBD, Chase Bank, Al Rajhi, etc.';
                        } else if (customBankLabel) {
                            customBankLabel.textContent = 'Specify Client Bank Name *';
                            if (customBankInput) customBankInput.placeholder = 'Enter bank name';
                        }
                        if (otherBankField) otherBankField.style.display = 'block';
                        if (customBankInput) customBankInput.setAttribute('required', 'required');
                    } else {
                        if (otherBankField) otherBankField.style.display = 'none';
                        if (customBankInput) {
                            customBankInput.removeAttribute('required');
                            customBankInput.value = '';
                        }
                    }
                });
            }
        }
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
                    <div class="row g-3">
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

<script>
    // Load banks on page load
    fetch('<?= $GLOBALS['base_url'] ?>/get-company-banks')
        .then(r => r.json())
        .then(data => {
            window.companyBanksList = data.banks || [];
            const sel = document.getElementById('bankSelect');
            const editSel = document.getElementById('edit_p_bank_id');
            if (sel) {
                sel.innerHTML = '<option value="">-- Select Transfer To / Company Bank --</option>';
                window.companyBanksList.forEach(b => {
                    const opt = document.createElement('option');
                    opt.value = b.id;
                    opt.textContent = b.bank_name + ' - ' + b.account_name + (b.account_number ? ' (' + b.account_number + ')' : '');
                    sel.appendChild(opt);
                });
            }
            if (editSel) {
                editSel.innerHTML = '<option value="">-- Select Transfer To / Company Bank --</option>';
                window.companyBanksList.forEach(b => {
                    const opt = document.createElement('option');
                    opt.value = b.id;
                    opt.textContent = b.bank_name + ' - ' + b.account_name + (b.account_number ? ' (' + b.account_number + ')' : '');
                    editSel.appendChild(opt);
                });
            }
        })
        .catch(() => {
            const sel = document.getElementById('bankSelect');
            if (sel) sel.innerHTML = '<option value="">Bank Registry unavailable</option>';
        });

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
                fetch('<?= $GLOBALS['base_url'] ?>' + '/get-company-banks')
                    .then(r => r.json())
                    .then(data => {
                        window.companyBanksList = data.banks || [];
                        const sel = document.getElementById('bankSelect');
                        const editSel = document.getElementById('edit_p_bank_id');
                        [sel, editSel].forEach(s => {
                            if (!s) return;
                            s.innerHTML = '<option value="">-- Select Transfer To / Company Bank --</option>';
                            window.companyBanksList.forEach(b => {
                                const opt = document.createElement('option');
                                opt.value = b.id;
                                opt.textContent = b.bank_name + ' - ' + b.account_name + (b.account_number ? ' (' + b.account_number + ')' : '');
                                s.appendChild(opt);
                            });
                        });
                        const savedId = isEdit ? formData.get('id') : (res.id || '');
                        if (savedId && sel) {
                            sel.value = savedId;
                            sel.dispatchEvent(new Event('change'));
                        }
                    });
                bootstrap.Modal.getInstance(document.getElementById('companyBankModal')).hide();
                if (typeof toastr !== 'undefined') {
                    toastr.success(res.message || 'Bank saved successfully');
                } else {
                    alert(res.message || 'Bank saved successfully');
                }
            } else {
                if (typeof toastr !== 'undefined') {
                    toastr.error(res.message || 'Error saving bank');
                } else {
                    alert(res.message || 'Error saving bank');
                }
            }
        })
        .catch(err => {
            if (typeof toastr !== 'undefined') {
                toastr.error('Network error. Please try again.');
            } else {
                alert('Network error. Please try again.');
            }
        })
        .finally(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    });

    // Edit Payment Modal logic
    document.querySelectorAll('.editPaymentBtn').forEach(btn => {
        btn.addEventListener('click', function() {
            const pId = this.dataset.id;
            const amount = this.dataset.amount;
            const method = this.dataset.method;
            const date = this.dataset.date;
            const ref = this.dataset.ref;
            const bankId = this.dataset.bankId;
            const bankName = this.dataset.bankName;
            const accountNo = this.dataset.accountNo;
            const cheque = this.dataset.cheque;
            const online = this.dataset.online;
            const isVerified = this.dataset.verified === 'true';

            document.getElementById('edit_p_id').value = pId;
            document.getElementById('edit_p_amount').value = amount;
            document.getElementById('edit_p_date').value = date;
            document.getElementById('edit_p_ref').value = ref || '';
            document.getElementById('edit_p_method').value = method;

            const formFields = [
                'edit_p_amount', 'edit_p_method', 'edit_p_ref',
                'edit_p_bank_id', 'edit_p_bank_name', 'edit_p_account_no',
                'edit_p_cheque', 'edit_p_online'
            ];
            
            if (isVerified) {
                formFields.forEach(id => {
                    const el = document.getElementById(id);
                    if (el) {
                        el.style.pointerEvents = 'none';
                        el.style.backgroundColor = '#e9ecef';
                        if (el.tagName === 'INPUT') el.readOnly = true;
                    }
                });
            } else {
                formFields.forEach(id => {
                    const el = document.getElementById(id);
                    if (el) {
                        el.style.pointerEvents = 'auto';
                        el.style.backgroundColor = '';
                        if (el.tagName === 'INPUT') el.readOnly = false;
                    }
                });
            }

            const editCompanyBankSelect = document.getElementById('edit_p_bank_id');
            if (editCompanyBankSelect) {
                editCompanyBankSelect.value = bankId || '';
            }

            const editBankSelect = document.getElementById('edit_p_bank_name');
            if (editBankSelect) {
                if (bankName && !Array.from(editBankSelect.options).some(opt => opt.value === bankName)) {
                    const customOpt = new Option(bankName, bankName, true, true);
                    editBankSelect.add(customOpt);
                }
                editBankSelect.value = bankName || '';
                if (window.jQuery && $(editBankSelect).hasClass('select2-hidden-accessible')) {
                    $(editBankSelect).trigger('change');
                }
            }

            document.getElementById('edit_p_account_no').value = accountNo || '';
            document.getElementById('edit_p_cheque').value = cheque || '';
            document.getElementById('edit_p_online').value = online || '';

            toggleEditMethodFields(method);
        });
    });

    function toggleEditMethodFields(method) {
        document.querySelectorAll('.edit-bank-fields').forEach(el => el.style.display = (method === 'bank') ? 'block' : 'none');
        document.querySelectorAll('.edit-cheque-fields').forEach(el => el.style.display = (method === 'cheque') ? 'block' : 'none');
        document.querySelectorAll('.edit-online-fields').forEach(el => el.style.display = (method === 'online') ? 'block' : 'none');

        if ((method === 'bank' || method === 'cheque') && window.jQuery && $.fn.select2) {
            const $editBank = $('#edit_p_bank_name');
            if ($editBank.length && !$editBank.hasClass('select2-hidden-accessible')) {
                $editBank.select2({
                    dropdownParent: $('#editPaymentModal'),
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
    }

    document.getElementById('edit_p_method')?.addEventListener('change', function() {
        toggleEditMethodFields(this.value);
    });

    if (window.jQuery) {
        $('#editPaymentModal').on('shown.bs.modal', function () {
            if ($('#edit_p_bank_name').is(':visible') && $.fn.select2) {
                if (!$('#edit_p_bank_name').hasClass('select2-hidden-accessible')) {
                    $('#edit_p_bank_name').select2({
                        dropdownParent: $('#editPaymentModal'),
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
        });
    }
</script>

<!-- Edit Payment Modal -->
<div id="editPaymentModal" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Payment Record</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/payment-booking-update" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="payment_id" id="edit_p_id">
                    <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">

                    <div class="row gy-3">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Amount *</label>
                            <input type="number" step="any" name="amount" id="edit_p_amount" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Method *</label>
                            <select name="payment_method" id="edit_p_method" class="form-select" required>
                                <option value="cash">Cash</option>
                                <option value="bank">Bank Transfer</option>
                                <option value="cheque">Cheque</option>
                                <option value="online">Online</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Date *</label>
                            <input type="date" name="payment_date" id="edit_p_date" class="form-control" <?= $isSalesHead ? '' : 'readonly style="pointer-events:none; background-color:#e9ecef;" title="Only Sales Head can edit the payment date"' ?> required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Transaction Reference</label>
                            <input type="text" name="transaction_ref" id="edit_p_ref" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3 edit-bank-fields" style="display:none;">
                            <label class="form-label">Transfer To / Company Bank</label>
                            <select name="bank_id" id="edit_p_bank_id" class="form-select">
                                <option value="">-- Select Company Bank --</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3 edit-bank-fields" style="display:none;">
                            <label class="form-label">Transfer From / Client Bank</label>
                            <select name="bank_name" id="edit_p_bank_name" class="form-select" style="width: 100%;">
                                <option value="">-- Select Client Bank --</option>
                                <?php foreach ($allBangladeshBanks as $bName): ?>
                                    <option value="<?= htmlspecialchars($bName) ?>"><?= htmlspecialchars($bName) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3 edit-bank-fields" style="display:none;">
                            <label class="form-label">Bank Account No</label>
                            <input type="text" name="bank_account_no" id="edit_p_account_no" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3 edit-cheque-fields" style="display:none;">
                            <label class="form-label">Cheque No</label>
                            <input type="text" name="cheque_no" id="edit_p_cheque" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3 edit-online-fields" style="display:none;">
                            <label class="form-label">Online Type</label>
                            <input type="text" name="online_type" id="edit_p_online" class="form-control" placeholder="bKash / Nagad / Rocket / etc.">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Update Attachment Image (Optional)</label>
                            <input type="file" name="attachment_image" class="form-control" accept="image/*,application/pdf">
                        </div>
                    </div>
                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Update Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
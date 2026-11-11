<?php
$page_title = "Add Payment";
$page = 'Payments';
$show_breadcrumb = true;

if (session_status() === PHP_SESSION_NONE) session_start();

$allBangladeshBanks = function_exists('getBangladeshBanks') ? getBangladeshBanks() : [];

// Calculate discount and effective targets
$discountAmount = 0;
if (!empty($booking['approved_amount'])) {
    $discountAmount = (float)$booking['approved_amount'];
} elseif (!empty($booking['discount_amount']) && in_array(strtolower($booking['approval_status'] ?? ''), ['approved', 'not_required', 'pending'])) {
    $discountAmount = (float)$booking['discount_amount'];
}

$effectiveLandShareTarget = max(0, (float)($booking['land_share_value'] ?? 0) - $discountAmount);
$agreementValue = (float)($booking['agreement_value'] ?? 0);
$finalAmount = $agreementValue;

$booking_paid = 0;
$booking_pending_and_paid = 0;
$registration_paid = 0;
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
    } elseif (in_array($p['payment_type'], ['registration', 'land_share'])) {
        if ($p['verification_status'] == 'verified') {
            $registration_paid += $p['amount'];
        }
        if (in_array($p['verification_status'], ['verified', 'pending'])) {
            $registration_pending_and_paid += $p['amount'];
            $has_registration = true;
        }
    }
}

$remaining = max(0, $finalAmount - $booking_pending_and_paid);
$is_booking_fully_paid = ($booking_pending_and_paid >= $finalAmount);

$totalRegistrationAmount = max(0, $effectiveLandShareTarget - $finalAmount);
$registrationRemaining = max(0, $totalRegistrationAmount - $registration_pending_and_paid);
$is_registration_fully_paid = ($registration_pending_and_paid >= $totalRegistrationAmount);

$showBookingForm = false;
$form_mode = 'booking_money';

if (!$is_booking_fully_paid) {
    $showBookingForm = true;
    $form_mode = 'booking_money';
} elseif (!$is_registration_fully_paid) {
    $showBookingForm = true;
    $form_mode = 'registration';
}

$currentUserId = (int)($_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0);
$currentUserRoleId = (int)($_SESSION['user']['role_id'] ?? $_SESSION['user']['primary_role_id'] ?? 0);
$secondaryRoleId = (int)($_SESSION['user']['secondary_role_id'] ?? 0);
$userRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
$userName = strtolower(trim($_SESSION['user']['full_name'] ?? $_SESSION['user']['user_name'] ?? ''));

$isManagement = in_array($currentUserRoleId, [1, 3, 20, 32, 33, 35]) ||
                in_array($secondaryRoleId, [1, 3, 20, 32, 33, 35]) ||
                (strpos($userRoleName, 'admin') !== false) ||
                (strpos($userRoleName, 'management') !== false) ||
                (strpos($userRoleName, 'ceo') !== false) ||
                (strpos($userRoleName, 'chief') !== false) ||
                (strpos($userName, 'management') !== false);

$isSalesHead = in_array($currentUserRoleId, [5, 9]) ||
               in_array($secondaryRoleId, [5, 9]) ||
               (strpos($userRoleName, 'sales') !== false && (strpos($userRoleName, 'head') !== false || strpos($userRoleName, 'manager') !== false));

$isAssignedSalesPerson = (
    (!empty($booking['sales_by_id']) && (int)$booking['sales_by_id'] === $currentUserId) ||
    (!empty($booking['assigned_id']) && (int)$booking['assigned_id'] === $currentUserId)
);
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
                            <input type="text" class="form-control" value="<?= format_sqft($booking['sqft'] ?? '') ?>" readonly>
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
                                    <label class="form__input-title">Land Share Value</label>
                                </div>
                                <input type="text" class="form-control" value="<?= number_format($booking['land_share_value'] ?? 0, 2) ?>" readonly>
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
                            <label class="form__input-title">Online Type</label>
                            <input type="text" name="online_type" class="form-control" placeholder="Enter Online Type (remittance, Bkash, Nagad, etc)">
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
                                <?= $form_mode === 'registration' ? 'value="' . $registrationAmountValue . '"' : '' ?>
                                required>
                        </div>

                        <!-- Payment Date -->
                        <div class="col-lg-6">
                            <div class="form__input-title">

                                <label class="form__input-title">Payment Date *</label>
                            </div>
                            <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <!-- Transaction Reference -->
                        <div class="col-lg-6">
                            <div class="form__input-title">
                                <label class="form__input-title">Transaction Reference</label>
                            </div>
                            <input type="text" name="transaction_ref" class="form-control" placeholder="Required reference number" required>
                        </div>

                        <!-- Attachment Image 1 -->
                        <div class="col-lg-6">
                            <div class="form__input-title">
                                <label class="form__input-title">Attachment 1 (JPG, PNG, PDF)</label>
                            </div>
                            <input type="file" name="attachment_image" class="form-control" accept="image/*,application/pdf">
                        </div>

                        <!-- Attachment Image 2 -->
                        <div class="col-lg-6">
                            <div class="form__input-title">
                                <label class="form__input-title">Attachment 2 (Optional)</label>
                            </div>
                            <input type="file" name="attachment_image2" class="form-control" accept="image/*,application/pdf">
                        </div>

                        <!-- Attachment Image 3 -->
                        <div class="col-lg-6" id="container_attachment_image3" style="display: none;">
                            <div class="form__input-title">
                                <label class="form__input-title">Attachment 3 (Optional)</label>
                            </div>
                            <input type="file" name="attachment_image3" class="form-control" accept="image/*,application/pdf">
                        </div>

                        <!-- Attachment Image 4 -->
                        <div class="col-lg-6" id="container_attachment_image4" style="display: none;">
                            <div class="form__input-title">
                                <label class="form__input-title">Attachment 4 (Optional)</label>
                            </div>
                            <input type="file" name="attachment_image4" class="form-control" accept="image/*,application/pdf">
                        </div>

                        <!-- Add More Image / Attachment Button -->
                        <div class="col-lg-12 mb-2" id="container_add_more_btn">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="btn_add_more_attachments">
                                <i class="fa-solid fa-plus me-1"></i> Add More Image / Document
                            </button>
                            <small class="text-muted ms-2">(Add 3rd &amp; 4th attachment; JPG, PNG, WEBP, PDF accepted)</small>
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
                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="send_sms" id="sendSmsToggle" value="1" checked>
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
<?php
// Find transaction refs that have more than 1 payment
$multi_refs = [];
$ref_counts = [];
foreach ($payments as $p) {
    if (!empty($p['transaction_ref']) && empty($p['batch_id'])) {
        $ref_counts[$p['transaction_ref']] = ($ref_counts[$p['transaction_ref']] ?? 0) + 1;
    }
}
foreach ($ref_counts as $ref => $count) {
    if ($count > 1) {
        $multi_refs[] = $ref;
    }
}
?>

<?php if (!empty($multi_refs)): ?>
<div class="mb-3 d-flex align-items-center gap-2 bg-light p-2 rounded border">
    <label class="mb-0 fw-bold">Combine Old Receipts:</label>
    <select id="combine_ref_select" class="form-select form-select-sm w-auto">
        <option value="">Select Reference No.</option>
        <?php foreach ($multi_refs as $ref): ?>
            <option value="<?= htmlspecialchars($ref) ?>"><?= htmlspecialchars($ref) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="button" class="btn btn-warning btn-sm" onclick="generateCombinedReceipt()">
        <i class="fa-solid fa-object-group me-1"></i> Generate Combined Receipt
    </button>
</div>
<script>
function generateCombinedReceipt() {
    const ref = document.getElementById('combine_ref_select').value;
    if (!ref) {
        alert('Please select a reference number first.');
        return;
    }
    if (confirm('Are you sure you want to combine the receipts for reference: ' + ref + '?')) {
        window.location.href = '<?= $GLOBALS["base_url"] ?>/payment-booking-combine?booking_id=<?= $booking["id"] ?>&ref=' + encodeURIComponent(ref);
    }
}
</script>
<?php endif; ?>

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
                        <?php 
                            // Group payments by batch_id to enable combined invoice printing
                            $batch_payment_ids = [];
                            if (!empty($payments)) {
                                foreach ($payments as $bp) {
                                    if (!empty($bp['batch_id'])) {
                                        $batch_payment_ids[$bp['batch_id']][] = $bp['id'];
                                    }
                                }
                            }
                        ?>
                        <?php if (!empty($payments)): ?>

                            <?php
                            // Step 2: running total for ledger style remaining
                            $running_paid = 0;
                            $registration_running_paid = 0;
                            ?>

                            <?php foreach ($payments as $index => $p): ?>

                                <?php
                                if ($p['payment_type'] === 'booking_money') {
                                    $running_paid += $p['amount'];
                                    $row_remaining = max(0, $finalAmount - $running_paid);
                                } elseif (in_array($p['payment_type'], ['registration', 'land_share'])) {
                                    $registration_running_paid += $p['amount'];
                                    $reg_row_remaining = max(0, $totalRegistrationAmount - $registration_running_paid);
                                } else {
                                    $row_remaining = 0;
                                    $reg_row_remaining = 0;
                                }
                                ?>
                        
                                <tr>
                                    <td>
                                        (<?= $index + 1 ?>) <?= date('d M Y', strtotime($p['payment_date'])) ?>
                                    </td>

                                    <td><?= ucfirst(str_replace('_', ' ', $p['payment_type'])) ?></td>

                                    <td>
                                        <?php if ($p['payment_type'] === 'booking_money'): ?>
                                            <?= number_format($finalAmount, 2) ?>
                                        <?php elseif (in_array($p['payment_type'], ['registration', 'land_share'])): ?>
                                            <?= number_format($totalRegistrationAmount, 2) ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>

                                    <td><?= number_format($p['amount'], 2) ?></td>

                                    <!--  Correct Remaining (ledger style) -->
                                    <td>
                                        <?php if ($p['payment_type'] === 'booking_money'): ?>
                                            <?= number_format($row_remaining, 2) ?><?= $p['verification_status'] === 'pending' ? ' <small class="text-warning fw-semibold">(Pending)</small>' : '' ?>
                                        <?php elseif (in_array($p['payment_type'], ['registration', 'land_share'])): ?>
                                            <?= number_format($reg_row_remaining, 2) ?><?= $p['verification_status'] === 'pending' ? ' <small class="text-warning fw-semibold">(Pending)</small>' : '' ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <strong><?= ucfirst($p['payment_method']) ?></strong>
                                        <?php if (!empty($p['online_type'])): ?>
                                            (<?= htmlspecialchars($p['online_type']) ?>)
                                        <?php endif; ?>
                                        <?php if (!empty($p['company_bank_name'])): ?>
                                            <br><small class="text-success"><i class="fa-solid fa-building-columns me-1"></i>To: <?= htmlspecialchars($p['company_bank_name']) ?><?= !empty($p['company_account_number']) ? ' (' . htmlspecialchars($p['company_account_number']) . ')' : '' ?></small>
                                        <?php endif; ?>
                                        <?php if (strtolower($p['payment_method']) !== 'online' && !empty($p['bank_name'])): ?>
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
                                            <?php 
                                                $isBatched = !empty($p['batch_id']) && isset($batch_payment_ids[$p['batch_id']]) && count($batch_payment_ids[$p['batch_id']]) > 1;
                                            ?>
                                            
                                            <?php if ($isBatched): ?>
                                                <a target="_blank"
                                                    href="<?= $GLOBALS['base_url'] ?>/payment-invoice?payment_id=<?= implode(',', $batch_payment_ids[$p['batch_id']]) ?>"
                                                    class="btn btn-warning btn-sm"
                                                    title="Print Combined Money Receipt">
                                                    <i class="fa-solid fa-object-group me-1"></i> Combined Invoice
                                                </a>
                                            <?php endif; ?>

                                            <!-- Individual Invoice -->
                                            <a target="_blank"
                                                href="<?= $GLOBALS['base_url'] ?>/payment-invoice?payment_id=<?= $p['id'] ?>"
                                                class="d-none btn btn-success btn-sm"
                                                title="Print Individual Money Receipt">
                                                <i class="fa-solid fa-receipt me-1"></i> <?= $p['payment_type'] === 'registration' ? 'Registration Invoice' : ($p['payment_type'] === 'land_share' ? 'Land Share Invoice' : 'Booking Invoice') ?>
                                            </a>
                                            
                                            <?php if (strtolower($p['payment_type']) === 'booking_money'): ?>
                                                <a target="_blank"
                                                    href="<?= $GLOBALS['base_url'] ?>/payment-booking-invoice/<?= $p['id'] ?>"
                                                    class="btn btn-primary btn-sm"
                                                    title="Print Booking Form">
                                                    <i class="fa-solid fa-file-signature me-1"></i> Booking Form
                                                </a>
                                            <?php endif; ?>

                                            <!-- Attachments (1 to 4) -->
                                            <?php 
                                            for ($attIdx = 1; $attIdx <= 4; $attIdx++):
                                                $attCol = ($attIdx === 1) ? 'attachment_image' : 'attachment_image' . $attIdx;
                                                $attFile = $p[$attCol] ?? '';
                                                if (!empty($attFile)):
                                                    $isPdf = strtolower(pathinfo($attFile, PATHINFO_EXTENSION)) === 'pdf';
                                            ?>
                                                <a href="<?= $GLOBALS['base_url'] ?>/public/uploads/payments/<?= htmlspecialchars($attFile) ?>" 
                                                   target="_blank" 
                                                   class="btn btn-outline-info btn-sm"
                                                   style="padding: 2px 7px; font-size: 11px;"
                                                   title="View Attachment <?= $attIdx ?> <?= $isPdf ? '(PDF)' : '' ?>">
                                                    <i class="fa-solid <?= $isPdf ? 'fa-file-pdf text-danger' : 'fa-paperclip' ?>"></i> <?= $attIdx ?>
                                                </a>
                                            <?php 
                                                endif;
                                            endfor; 
                                            ?>

                                            <!-- Edit Payment -->
                                            <?php 
                                                $pIsVerified = ($p['verification_status'] === 'verified');
                                                $pCreatedBy = (int)($p['created_by'] ?? 0);

                                                $canEditThisPayment = false;
                                                if ($pIsVerified) {
                                                    // Verified: ONLY Management or Sales Head can edit
                                                    $canEditThisPayment = ($isManagement || $isSalesHead);
                                                } else {
                                                    // Unverified: Management, Sales Head, Assigned Sales Person, or payment creator can edit
                                                    $canEditThisPayment = ($isManagement || $isSalesHead || $isAssignedSalesPerson || ($pCreatedBy === $currentUserId));
                                                }

                                                $canDeleteThisPayment = false;
                                                if (!$pIsVerified) {
                                                    $canDeleteThisPayment = ($isManagement || $isSalesHead || $isAssignedSalesPerson || ($pCreatedBy === $currentUserId));
                                                }
                                            ?>
                                            <?php if ($canEditThisPayment): ?>
                                                <button type="button" 
                                                    class="btn btn-outline-primary btn-sm editPaymentBtn"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editPaymentModal"
                                                    onclick="openEditPaymentModal(this)"
                                                    data-id="<?= $p['id'] ?>"
                                                    data-booking-id="<?= $booking['id'] ?>"
                                                    data-type="<?= htmlspecialchars($p['payment_type'] ?? 'booking_money') ?>"
                                                    data-amount="<?= $p['amount'] ?>"
                                                    data-method="<?= htmlspecialchars($p['payment_method'] ?? 'cash') ?>"
                                                    data-status="<?= htmlspecialchars($p['payment_status'] ?? 'pending') ?>"
                                                    data-date="<?= $p['payment_date'] ?>"
                                                    data-emi-no="<?= htmlspecialchars($p['total_emi_no'] ?? $booking['total_emi_no'] ?? '') ?>"
                                                    data-ref="<?= htmlspecialchars($p['transaction_ref'] ?? '') ?>"
                                                    data-bank-id="<?= $p['bank_id'] ?? '' ?>"
                                                    data-bank-name="<?= htmlspecialchars($p['bank_name'] ?? '') ?>"
                                                    data-account-no="<?= htmlspecialchars($p['bank_account_no'] ?? '') ?>"
                                                    data-cheque="<?= htmlspecialchars($p['cheque_no'] ?? '') ?>"
                                                    data-online="<?= htmlspecialchars($p['online_type'] ?? '') ?>"
                                                    data-image1="<?= htmlspecialchars($p['attachment_image'] ?? '') ?>"
                                                    data-image2="<?= htmlspecialchars($p['attachment_image2'] ?? '') ?>"
                                                    data-image3="<?= htmlspecialchars($p['attachment_image3'] ?? '') ?>"
                                                    data-image4="<?= htmlspecialchars($p['attachment_image4'] ?? '') ?>"
                                                    data-verified="<?= $pIsVerified ? 'true' : 'false' ?>"
                                                    title="Edit Payment Record">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                            <?php endif; ?>

                                            <!-- Delete only for unverified payments -->
                                            <?php if ($canDeleteThisPayment): ?>
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
                document.querySelector('#companyBankFields').style.display = 'block';
                document.querySelector('#bankSelect')?.setAttribute('required', 'required');
                
                document.querySelector('#onlineFields').style.display = 'block';
                // Online type is optional now
            }

            // Initialize or re-init Select2 on clientBankSelect if jQuery and select2 available
            if (method === 'bank' || method === 'cheque') {
                if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
                    const $cBank = window.jQuery('#clientBankSelect');
                    if (!$cBank.hasClass('select2-hidden-accessible')) {
                        $cBank.select2({
                            width: '100%',
                            placeholder: '-- Select Client Bank --',
                            tags: true,
                            createTag: function (params) {
                                var term = (params.term || '').trim();
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
            window.jQuery(document).on('change', '#clientBankSelect', function() {
                const val = this.value;
                if (val === 'Other Bank' || val === 'Other Country / Foreign Bank') {
                    if (val === 'Other Country / Foreign Bank') {
                        window.jQuery('#customBankLabel').text('Specify Foreign / Other Country Bank Name *');
                        window.jQuery('#customBankInput').attr('placeholder', 'e.g. Emirates NBD, Chase Bank, Al Rajhi, etc.');
                    } else {
                        window.jQuery('#customBankLabel').text('Specify Client Bank Name *');
                        window.jQuery('#customBankInput').attr('placeholder', 'Enter bank name');
                    }
                    window.jQuery('#otherBankField').show();
                    window.jQuery('#customBankInput').attr('required', 'required');
                } else {
                    window.jQuery('#otherBankField').hide();
                    window.jQuery('#customBankInput').removeAttr('required').val('');
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

<style>
    #editBankFields .select2-container {
        width: 100% !important;
    }
</style>

<!-- Edit Payment Modal -->
<div id="editPaymentModal" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Payment Record <span id="edit_payment_badge"></span></h5>
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
                            <label class="form-label">Payment Type *</label>
                            <select name="payment_type" id="edit_p_type" class="form-select" required>
                                <option value="booking_money">Booking Money</option>
                                <option value="registration">Registration</option>
                                <option value="emi">EMI / Installment</option>
                                <option value="land_share">Land Share</option>
                                <option value="development_charge">Development Charge</option>
                                <option value="others">Others</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Total EMI Number</label>
                            <input type="number" name="total_emi_no" id="edit_p_total_emi" class="form-control" placeholder="e.g. 42">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Amount *</label>
                            <input type="number" step="any" name="amount" id="edit_p_amount" class="form-control" required>
                            <small id="edit_p_amount_note" class="text-danger mt-1" style="display:none; font-size:12px;"></small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Method *</label>
                            <select name="payment_method" id="edit_p_method" class="form-select" onchange="toggleEditMethodFields(this.value)" required>
                                <option value="cash">Cash</option>
                                <option value="bank">Bank</option>
                                <option value="online">Online</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>

                        <!-- Transfer To / Company Bank (Bank, Cheque, Online) -->
                        <div class="col-md-6 mb-3 edit-company-bank-fields" id="editCompanyBankFields" style="display:none;">
                            <label class="form-label">Transfer To / Company Bank *</label>
                            <div class="input-group">
                                <select name="bank_id" id="edit_p_bank_id" class="form-select">
                                    <option value="">-- Select Transfer To / Company Bank --</option>
                                    <?php if (!empty($companyBanks)): ?>
                                        <?php foreach ($companyBanks as $cb): ?>
                                            <option value="<?= $cb['id'] ?>">
                                                <?= htmlspecialchars($cb['bank_name']) ?> - <?= htmlspecialchars($cb['account_name'] ?? '') ?><?= !empty($cb['account_number']) ? ' (' . htmlspecialchars($cb['account_number']) . ')' : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <button type="button" class="btn btn-outline-primary" id="editAddCompanyBankBtn" title="Add Company Bank"><i class="fa-light fa-plus"></i></button>
                            </div>
                        </div>

                        <!-- Transfer From / Client Bank (Bank, Cheque) -->
                        <div class="col-md-6 mb-3 edit-bank-fields" id="editBankFields" style="display:none;">
                            <label class="form-label">Transfer From / Client Bank *</label>
                            <select name="bank_name" id="edit_p_bank_name" class="form-select" style="width: 100%;" onchange="handleEditClientBankChange(this.value)">
                                <option value="">-- Select Client Bank --</option>
                                <option value="Other Country / Foreign Bank">Other Country / Foreign Bank</option>
                                <option value="Other Bank">Other Bank</option>
                                <?php foreach ($allBangladeshBanks as $bName): ?>
                                    <option value="<?= htmlspecialchars($bName) ?>"><?= htmlspecialchars($bName) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Foreign / Other Bank Custom Name -->
                        <div class="col-md-6 mb-3 edit-custom-bank-field" id="editOtherBankField" style="display:none;">
                            <label class="form-label" id="edit_customBankLabel">Specify Foreign / Other Country Bank Name *</label>
                            <input type="text" name="custom_bank_name" id="edit_customBankInput" class="form-control" placeholder="e.g. Emirates NBD, Chase Bank, Al Rajhi, etc.">
                        </div>

                        <!-- Client Bank Account No (Bank only) -->
                        <div class="col-md-6 mb-3 edit-bank-account-fields" id="editBankAccountFields" style="display:none;">
                            <label class="form-label">Client Bank A/C No. (Optional)</label>
                            <input type="text" name="bank_account_no" id="edit_p_account_no" class="form-control" placeholder="Account Number">
                        </div>

                        <!-- Cheque No (Cheque only) -->
                        <div class="col-md-6 mb-3 edit-cheque-fields" id="editChequeFields" style="display:none;">
                            <label class="form-label">Cheque No *</label>
                            <input type="text" name="cheque_no" id="edit_p_cheque" class="form-control" placeholder="Enter Cheque Number">
                        </div>

                        <!-- Online Type (Online only) -->
                        <div class="col-md-6 mb-3 edit-online-fields" id="editOnlineFields" style="display:none;">
                            <label class="form-label">Online Type</label>
                            <input type="text" name="online_type" id="edit_p_online" class="form-control" placeholder="remittance, bKash, Nagad, Rocket, etc.">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Date *</label>
                            <input type="date" name="payment_date" id="edit_p_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Transaction Reference</label>
                            <input type="text" name="transaction_ref" id="edit_p_ref" class="form-control" placeholder="Required reference number">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Status</label>
                            <select name="payment_status" id="edit_p_status" class="form-select">
                                <option value="pending">Pending</option>
                                <option value="partially_paid">Partially Paid</option>
                                <option value="paid">Paid</option>
                                <option value="failed">Failed</option>
                            </select>
                            <small id="edit_p_status_note" class="text-muted mt-1 d-block" style="font-size:11px;"></small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Update Attachment 1 (Optional)</label>
                            <input type="file" name="attachment_image" class="form-control" accept="image/*,application/pdf">
                            <div id="edit_p_image1_preview" class="mt-1 small"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Update Attachment 2 (Optional)</label>
                            <input type="file" name="attachment_image2" class="form-control" accept="image/*,application/pdf">
                            <div id="edit_p_image2_preview" class="mt-1 small"></div>
                        </div>
                        <div class="col-md-6 mb-3" id="edit_container_attachment3" style="display:none;">
                            <label class="form-label">Update Attachment 3 (Optional)</label>
                            <input type="file" name="attachment_image3" class="form-control" accept="image/*,application/pdf">
                            <div id="edit_p_image3_preview" class="mt-1 small"></div>
                        </div>
                        <div class="col-md-6 mb-3" id="edit_container_attachment4" style="display:none;">
                            <label class="form-label">Update Attachment 4 (Optional)</label>
                            <input type="file" name="attachment_image4" class="form-control" accept="image/*,application/pdf">
                            <div id="edit_p_image4_preview" class="mt-1 small"></div>
                        </div>
                        <div class="col-md-12 mb-2" id="edit_container_add_more_btn">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="btn_edit_add_more_attachments">
                                <i class="fa-solid fa-plus me-1"></i> Add More Image / Document
                            </button>
                            <small class="text-muted ms-2">(Add 3rd &amp; 4th attachment; JPG, PNG, WEBP, PDF accepted)</small>
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

<script>
    // Load banks on page load
    fetch('<?= $GLOBALS['base_url'] ?>/get-company-banks')
        .then(r => r.json())
        .then(data => {
            window.companyBanksList = data.banks || [];
            const sel = document.getElementById('bankSelect');
            const editSel = document.getElementById('edit_p_bank_id');
            if (sel) {
                const curSelVal = sel.value;
                sel.innerHTML = '<option value="">-- Select Transfer To / Company Bank --</option>';
                window.companyBanksList.forEach(b => {
                    const opt = document.createElement('option');
                    opt.value = b.id;
                    opt.textContent = b.bank_name + ' - ' + b.account_name + (b.account_number ? ' (' + b.account_number + ')' : '');
                    sel.appendChild(opt);
                });
                if (curSelVal) sel.value = curSelVal;
            }
            if (editSel) {
                const curEditVal = editSel.value;
                editSel.innerHTML = '<option value="">-- Select Transfer To / Company Bank --</option>';
                window.companyBanksList.forEach(b => {
                    const opt = document.createElement('option');
                    opt.value = b.id;
                    opt.textContent = b.bank_name + ' - ' + b.account_name + (b.account_number ? ' (' + b.account_number + ')' : '');
                    editSel.appendChild(opt);
                });
                if (curEditVal) editSel.value = curEditVal;
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

    document.getElementById('editAddCompanyBankBtn')?.addEventListener('click', function() {
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
                        if (savedId) {
                            if (sel) {
                                sel.value = savedId;
                                sel.dispatchEvent(new Event('change'));
                            }
                            if (editSel) {
                                editSel.value = savedId;
                                editSel.dispatchEvent(new Event('change'));
                            }
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

    const currentUserRoleType = '<?= $isManagement ? "management" : ($isSalesHead ? "sales_head" : "sales_person") ?>';

    // Global function to toggle edit modal method fields in pure vanilla JS
    function toggleEditMethodFields(method) {
        const isBank = (method === 'bank');
        const isCheque = (method === 'cheque');
        const isOnline = (method === 'online');

        const compBankDiv = document.getElementById('editCompanyBankFields');
        const clientBankDiv = document.getElementById('editBankFields');
        const clientAccDiv = document.getElementById('editBankAccountFields');
        const chequeDiv = document.getElementById('editChequeFields');
        const onlineDiv = document.getElementById('editOnlineFields');
        const otherBankDiv = document.getElementById('editOtherBankField');

        if (compBankDiv) compBankDiv.style.display = (isBank || isCheque || isOnline) ? 'block' : 'none';
        if (clientBankDiv) clientBankDiv.style.display = (isBank || isCheque) ? 'block' : 'none';
        if (clientAccDiv) clientAccDiv.style.display = isBank ? 'block' : 'none';
        if (chequeDiv) chequeDiv.style.display = isCheque ? 'block' : 'none';
        if (onlineDiv) onlineDiv.style.display = isOnline ? 'block' : 'none';

        if (otherBankDiv) {
            if (!isBank && !isCheque) {
                otherBankDiv.style.display = 'none';
            } else {
                const bVal = document.getElementById('edit_p_bank_name') ? document.getElementById('edit_p_bank_name').value : '';
                const customLabel = document.getElementById('edit_customBankLabel');
                if (bVal === 'Other Bank' || bVal === 'Other Country / Foreign Bank') {
                    if (customLabel) {
                        customLabel.textContent = (bVal === 'Other Country / Foreign Bank') ? 'Specify Foreign / Other Country Bank Name *' : 'Specify Client Bank Name *';
                    }
                    otherBankDiv.style.display = 'block';
                } else {
                    otherBankDiv.style.display = 'none';
                }
            }
        }

        // Initialize Select2 on Client Bank inside modal if available
        if ((isBank || isCheque) && window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
            const $editBank = window.jQuery('#edit_p_bank_name');
            if ($editBank.length && !$editBank.hasClass('select2-hidden-accessible')) {
                $editBank.select2({
                    dropdownParent: window.jQuery('#editPaymentModal'),
                    width: '100%',
                    placeholder: '-- Select Client Bank --',
                    tags: true,
                    createTag: function (params) {
                        var term = (params.term || '').trim();
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

    window.toggleEditMethodFields = toggleEditMethodFields;

    // Client Bank change handler
    function handleEditClientBankChange(val) {
        const otherDiv = document.getElementById('editOtherBankField');
        const customInput = document.getElementById('edit_customBankInput');
        const customLabel = document.getElementById('edit_customBankLabel');
        if (val === 'Other Bank' || val === 'Other Country / Foreign Bank') {
            if (customLabel) {
                customLabel.textContent = (val === 'Other Country / Foreign Bank') ? 'Specify Foreign / Other Country Bank Name *' : 'Specify Client Bank Name *';
            }
            if (otherDiv) otherDiv.style.display = 'block';
        } else {
            if (otherDiv) otherDiv.style.display = 'none';
            if (customInput) customInput.value = '';
        }
    }
    window.handleEditClientBankChange = handleEditClientBankChange;

    // Pure Vanilla JS Event Delegation: When Payment Method changes inside Edit Modal
    document.addEventListener('change', function(e) {
        if (e.target && e.target.id === 'edit_p_method') {
            toggleEditMethodFields(e.target.value);
        }
    });

    // Pure Vanilla JS Event Delegation: When Client Bank changes inside Edit Modal
    document.addEventListener('change', function(e) {
        if (e.target && e.target.id === 'edit_p_bank_name') {
            handleEditClientBankChange(e.target.value);
        }
    });

    // Main function to open and populate Edit Payment Modal
    function openEditPaymentModal(btn) {
        if (!btn) return;

        const pId = btn.dataset.id || btn.getAttribute('data-id') || '';
        const type = btn.dataset.type || btn.getAttribute('data-type') || 'booking_money';
        const amount = btn.dataset.amount || btn.getAttribute('data-amount') || '0';
        const method = btn.dataset.method || btn.getAttribute('data-method') || 'cash';
        const status = btn.dataset.status || btn.getAttribute('data-status') || 'pending';
        const date = btn.dataset.date || btn.getAttribute('data-date') || '';
        const emiNo = btn.dataset.emiNo || btn.getAttribute('data-emi-no') || '';
        const ref = btn.dataset.ref || btn.getAttribute('data-ref') || '';
        const bankId = btn.dataset.bankId || btn.getAttribute('data-bank-id') || '';
        const bankName = btn.dataset.bankName || btn.getAttribute('data-bank-name') || '';
        const accountNo = btn.dataset.accountNo || btn.getAttribute('data-account-no') || '';
        const cheque = btn.dataset.cheque || btn.getAttribute('data-cheque') || '';
        const online = btn.dataset.online || btn.getAttribute('data-online') || '';
        const img1 = btn.dataset.image1 || btn.getAttribute('data-image1') || '';
        const img2 = btn.dataset.image2 || btn.getAttribute('data-image2') || '';
        const img3 = btn.dataset.image3 || btn.getAttribute('data-image3') || '';
        const img4 = btn.dataset.image4 || btn.getAttribute('data-image4') || '';
        const isVerified = (btn.dataset.verified === 'true' || btn.getAttribute('data-verified') === 'true');

        const elId = document.getElementById('edit_p_id');
        const elType = document.getElementById('edit_p_type');
        const elEmi = document.getElementById('edit_p_total_emi');
        const elAmount = document.getElementById('edit_p_amount');
        const elMethod = document.getElementById('edit_p_method');
        const elStatus = document.getElementById('edit_p_status');
        const elDate = document.getElementById('edit_p_date');
        const elRef = document.getElementById('edit_p_ref');
        const elBankId = document.getElementById('edit_p_bank_id');
        const elAccountNo = document.getElementById('edit_p_account_no');
        const elCheque = document.getElementById('edit_p_cheque');
        const elOnline = document.getElementById('edit_p_online');

        if (elId) elId.value = pId;
        if (elType) elType.value = type;
        if (elEmi) elEmi.value = emiNo;
        if (elAmount) elAmount.value = amount;
        if (elMethod) elMethod.value = method;
        if (elStatus) elStatus.value = status;
        if (elDate) elDate.value = date;
        if (elRef) elRef.value = ref;
        if (elBankId) elBankId.value = bankId;
        if (elAccountNo) elAccountNo.value = accountNo;
        if (elCheque) elCheque.value = cheque;
        if (elOnline) elOnline.value = online;

        // Attachment previews (1 to 4)
        function formatPreviewLink(fileName, num) {
            if (!fileName) return `<span class="text-muted">No attachment ${num} uploaded</span>`;
            const isPdf = fileName.toLowerCase().endsWith('.pdf');
            const icon = isPdf ? 'fa-file-pdf text-danger' : 'fa-paperclip';
            return `<a href="<?= $GLOBALS['base_url'] ?>/public/uploads/payments/${encodeURIComponent(fileName)}" target="_blank" class="text-primary"><i class="fa-solid ${icon} me-1"></i>View Current Attachment ${num} ${isPdf ? '(PDF)' : ''}</a>`;
        }

        const p1 = document.getElementById('edit_p_image1_preview');
        if (p1) p1.innerHTML = formatPreviewLink(img1, 1);
        const p2 = document.getElementById('edit_p_image2_preview');
        if (p2) p2.innerHTML = formatPreviewLink(img2, 2);
        const p3 = document.getElementById('edit_p_image3_preview');
        if (p3) p3.innerHTML = formatPreviewLink(img3, 3);
        const p4 = document.getElementById('edit_p_image4_preview');
        if (p4) p4.innerHTML = formatPreviewLink(img4, 4);

        const editC3 = document.getElementById('edit_container_attachment3');
        const editC4 = document.getElementById('edit_container_attachment4');
        const editBtnMore = document.getElementById('btn_edit_add_more_attachments');

        if (editC3) editC3.style.display = img3 ? 'block' : 'none';
        if (editC4) editC4.style.display = img4 ? 'block' : 'none';
        if (editBtnMore) editBtnMore.style.display = (img3 && img4) ? 'none' : 'inline-block';

        // Client Bank setup
        const editBankSelect = document.getElementById('edit_p_bank_name');
        const otherBankDiv = document.getElementById('editOtherBankField');
        const customBankInput = document.getElementById('edit_customBankInput');
        if (editBankSelect) {
            if (bankName) {
                let found = false;
                for (let i = 0; i < editBankSelect.options.length; i++) {
                    if (editBankSelect.options[i].value === bankName) {
                        found = true;
                        break;
                    }
                }
                if (found) {
                    editBankSelect.value = bankName;
                    if (bankName === 'Other Bank' || bankName === 'Other Country / Foreign Bank') {
                        if (otherBankDiv) otherBankDiv.style.display = 'block';
                    } else {
                        if (otherBankDiv) otherBankDiv.style.display = 'none';
                        if (customBankInput) customBankInput.value = '';
                    }
                } else {
                    const newOpt = new Option(bankName, bankName, true, true);
                    editBankSelect.add(newOpt);
                    editBankSelect.value = bankName;
                    if (otherBankDiv) otherBankDiv.style.display = 'none';
                }
                if (window.jQuery && window.jQuery(editBankSelect).hasClass('select2-hidden-accessible')) {
                    window.jQuery(editBankSelect).trigger('change');
                }
            } else {
                editBankSelect.value = '';
                if (otherBankDiv) otherBankDiv.style.display = 'none';
                if (customBankInput) customBankInput.value = '';
                if (window.jQuery && window.jQuery(editBankSelect).hasClass('select2-hidden-accessible')) {
                    window.jQuery(editBankSelect).trigger('change');
                }
            }
        }

        // Role permissions
        const badgeEl = document.getElementById('edit_payment_badge');
        const amountInput = document.getElementById('edit_p_amount');
        const amountNote = document.getElementById('edit_p_amount_note');
        if (amountNote) amountNote.style.display = 'none';

        if (isVerified) {
            if (currentUserRoleType === 'management') {
                if (badgeEl) {
                    badgeEl.className = 'badge bg-success ms-2';
                    badgeEl.textContent = 'Verified (Management: Full Edit Access)';
                }
                if (amountInput) {
                    amountInput.readOnly = false;
                    amountInput.style.pointerEvents = 'auto';
                    amountInput.style.backgroundColor = '';
                    amountInput.removeAttribute('title');
                }
                [elType, elStatus].forEach(el => {
                    if (el) {
                        el.style.pointerEvents = 'auto';
                        el.style.backgroundColor = '';
                        el.disabled = false;
                    }
                });
                if (elEmi) {
                    elEmi.readOnly = false;
                    elEmi.style.pointerEvents = 'auto';
                    elEmi.style.backgroundColor = '';
                }
            } else if (currentUserRoleType === 'sales_head') {
                if (badgeEl) {
                    badgeEl.className = 'badge bg-warning text-dark ms-2';
                    badgeEl.textContent = 'Verified (Sales Head: Method/Date/Bank/Cheque Edit Access)';
                }
                if (amountInput) {
                    amountInput.readOnly = true;
                    amountInput.style.pointerEvents = 'none';
                    amountInput.style.backgroundColor = '#e9ecef';
                    amountInput.setAttribute('title', 'Amount cannot be modified for verified payment by Sales Head.');
                }
                [elType, elStatus].forEach(el => {
                    if (el) {
                        el.style.pointerEvents = 'none';
                        el.style.backgroundColor = '#e9ecef';
                    }
                });
                if (elEmi) {
                    elEmi.readOnly = true;
                    elEmi.style.pointerEvents = 'none';
                    elEmi.style.backgroundColor = '#e9ecef';
                }
                if (amountNote) {
                    amountNote.style.display = 'block';
                    amountNote.textContent = 'Amount and core billing types are locked by Accounts. Only Management can edit verified amounts.';
                }
            }
        } else {
            if (badgeEl) {
                badgeEl.className = 'badge bg-secondary ms-2';
                badgeEl.textContent = 'Unverified Payment';
            }
            if (amountInput) {
                amountInput.readOnly = false;
                amountInput.style.pointerEvents = 'auto';
                amountInput.style.backgroundColor = '';
                amountInput.removeAttribute('title');
            }
            [elType, elStatus].forEach(el => {
                if (el) {
                    el.style.pointerEvents = 'auto';
                    el.style.backgroundColor = '';
                    el.disabled = false;
                }
            });
            if (elEmi) {
                elEmi.readOnly = false;
                elEmi.style.pointerEvents = 'auto';
                elEmi.style.backgroundColor = '';
            }
        }

        // Toggle relevant fields based on current method
        toggleEditMethodFields(method);
    }
    window.openEditPaymentModal = openEditPaymentModal;

    // Pure Vanilla JS Event Delegation: When Edit Payment button is clicked
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.editPaymentBtn');
        if (btn) openEditPaymentModal(btn);
    });

    // Ensure Select2 and layout refresh when modal fully opens
    const editModalEl = document.getElementById('editPaymentModal');
    if (editModalEl) {
        editModalEl.addEventListener('shown.bs.modal', function () {
            const elMethod = document.getElementById('edit_p_method');
            const curMethod = elMethod ? elMethod.value : 'cash';
            toggleEditMethodFields(curMethod);
        });
    }

    // Dynamic Add More Attachment in Add Payment Form
    const btnAddMore = document.getElementById('btn_add_more_attachments');
    const c3 = document.getElementById('container_attachment_image3');
    const c4 = document.getElementById('container_attachment_image4');
    if (btnAddMore) {
        btnAddMore.addEventListener('click', function() {
            if (c3 && c3.style.display === 'none') {
                c3.style.display = 'block';
            } else if (c4 && c4.style.display === 'none') {
                c4.style.display = 'block';
                btnAddMore.style.display = 'none';
            }
        });
    }

    // Dynamic Add More Attachment in Edit Payment Modal
    const btnEditAddMore = document.getElementById('btn_edit_add_more_attachments');
    if (btnEditAddMore) {
        btnEditAddMore.addEventListener('click', function() {
            const editC3 = document.getElementById('edit_container_attachment3');
            const editC4 = document.getElementById('edit_container_attachment4');
            if (editC3 && editC3.style.display === 'none') {
                editC3.style.display = 'block';
            } else if (editC4 && editC4.style.display === 'none') {
                editC4.style.display = 'block';
                btnEditAddMore.style.display = 'none';
            }
        });
    }
</script>

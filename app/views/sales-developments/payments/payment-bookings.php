<?php
$page_title = "Add Payment";
$page = 'Payments';
$show_breadcrumb = true;

if (session_status() === PHP_SESSION_NONE) session_start();

$finalAmount = !empty($booking['approved_amount'])
    ? $booking['agreement_value'] - $booking['approved_amount']
    : $booking['agreement_value'];

$booking_paid = 0;
$booking_pending_and_paid = 0;
$has_registration = false;

foreach ($payments as $p) {
    if ($p['payment_type'] === 'booking_money') {
        if ($p['verification_status'] == 'verified') {
            $booking_paid += $p['amount'];
        }
        if (in_array($p['verification_status'], ['verified', 'pending'])) {
            $booking_pending_and_paid += $p['amount'];
        }
    } elseif ($p['payment_type'] === 'registration') {
        if (in_array($p['verification_status'], ['verified', 'pending'])) {
            $has_registration = true;
        }
    }
}

$remaining = $finalAmount - $booking_pending_and_paid;
$is_booking_fully_paid = ($remaining <= 0);

$showBookingForm = false;
$form_mode = 'booking_money';

if (!$is_booking_fully_paid) {
    $showBookingForm = true;
    $form_mode = 'booking_money';
} elseif (!$has_registration) {
    $showBookingForm = true;
    $form_mode = 'registration';
}
?>



<!-- booking_money type select kore rakhte hobe   -->
<?php if ($showBookingForm): ?>

    <div class="row mb-4" id="bookingPayment">
        <div class="col-xxl-12">
            <div class="card__wrapper p-3">
                <h5><?= $form_mode === 'registration' ? 'Add Registration Payment for' : 'Add Booking Payment for' ?> <?= $booking['full_name'] ?? 'Lead' ?></h5>

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
                                <input type="number" name="total_emi_no" class="form-control" value="">
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

                        <!-- Bank Details -->
                        <div class="col-lg-6 extra-input" id="bankFields" style="display:none;">
                            <div class="form__input-title">

                                <label class="form__input-title">Bank Name - Branch*</label>
                            </div>
                            <input type="text" name="bank_name" class="form-control" placeholder="Enter Bank Name">
                        </div>

                        <div class="col-lg-6 extra-input" id="bankAccountFields" style="display:none;">
                            <div class="form__input-title">

                                <label class="form__input-title">Bank Account No *</label>
                            </div>
                            <input type="text" name="bank_account_no" class="form-control" placeholder="Enter Account Number">
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
                        if ($form_mode === 'registration') {
                            $placeholderText = "Enter Registration Amount";
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
                                <label class="form__input-title">Pay Amount *</label>
                            </div>
                            <input type="number" step="0.01" name="amount"
                                class="form-control"
                                placeholder="<?= $placeholderText ?>"
                                <?= $form_mode === 'booking_money' ? 'max="' . $remaining . '"' : '' ?>
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
                            <th>Total Booking Money</th>
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

                            // Step 2: running total for ledger style remaining
                            $running_paid = 0;
                            ?>

                            <?php foreach ($payments as $index => $p): ?>

                                <?php
                                // Only verified payments affect balance
                                if (
                                    $p['payment_type'] === 'booking_money' &&
                                    $p['verification_status'] === 'verified'
                                ) {
                                    $running_paid += $p['amount'];
                                }

                                $row_remaining = $finalAmount - $running_paid;
                                ?>

                                <tr>
                                    <td>
                                        (<?= $index + 1 ?>) <?= date('d M Y', strtotime($p['payment_date'])) ?>
                                    </td>

                                    <td><?= ucfirst($p['payment_type']) ?></td>

                                    <td><?= number_format($p['agreement_value'], 2) ?></td>

                                    <td><?= number_format($p['amount'], 2) ?></td>

                                    <!--  Correct Remaining (ledger style) -->
                                    <td>
                                        <?= ($p['payment_type'] === 'booking_money')
                                            ? number_format($row_remaining, 2)
                                            : '-' ?>
                                    </td>

                                    <td><?= ucfirst($p['payment_method']) ?></td>

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

                                    <td>
                                        <?php if ($p['verification_status'] == 'verified'): ?>
                                            <a target="_blank"
                                                href="<?= $GLOBALS['base_url'] ?>/payment-booking-invoice/<?= $p['id'] ?>"
                                                class="btn btn-success btn-sm">
                                                Print Booking Invoice
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-secondary btn-sm" disabled>
                                                Print Booking Invoice
                                            </button>
                                        <?php endif; ?>
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
                div.querySelector('input')?.removeAttribute('required');
            });

            // Show relevant fields
            if (method === 'bank') {
                document.querySelector('#bankFields').style.display = 'block';
                document.querySelector('#bankFields input').setAttribute('required', 'required');

                document.querySelector('#bankAccountFields').style.display = 'block';
                document.querySelector('#bankAccountFields input').setAttribute('required', 'required');
            } else if (method === 'cheque') {
                document.querySelector('#bankFields').style.display = 'block';
                document.querySelector('#bankFields input').setAttribute('required', 'required');
                document.querySelector('#chequeFields').style.display = 'block';
                document.querySelector('#chequeFields input').setAttribute('required', 'required');
            } else if (method === 'online') {
                document.querySelector('#onlineFields').style.display = 'block';
                document.querySelector('#onlineFields input').setAttribute('required', 'required');
            }
        });
    });
</script>
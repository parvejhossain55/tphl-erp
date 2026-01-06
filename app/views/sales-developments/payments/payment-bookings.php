<?php
$page_title = "Add Payment";
$page = 'Payments';
$show_breadcrumb = true;

if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!-- booking_money type select kore rakhte hobe   -->
<?php if ($showBookingForm): ?>

    <div class="row mb-4" id="bookingPayment">
        <div class="col-xxl-12">
            <div class="card__wrapper p-3">
                <h5>Add Payment for <?= $booking['lead_name'] ?? 'Lead' ?></h5>

                <form action="<?= $GLOBALS['base_url'] ?>/payment-booking-store" method="POST">
                    <div class="row gy-3">

                        <!-- Hidden Inputs -->
                        <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                        <input type="hidden" name="created_by" value="<?= $_SESSION['user']['id'] ?>">

                        <!-- Lead Info (Readonly) -->
                        <div class="col-lg-6">
                            <label class="form__input-title">Lead Name</label>
                            <input type="text" class="form-control" value="<?= $booking['lead_name'] ?>" readonly>
                        </div>

                        <div class="col-lg-6">
                            <label class="form__input-title">Project</label>
                            <input type="text" class="form-control" value="<?= $booking['project_name'] ?>" readonly>
                        </div>
                        <div class="col-lg-6">
                            <label class="form__input-title">Number Of Share</label>
                            <input type="text" class="form-control" value="<?= $booking['total_share'] ?>" readonly>
                        </div>

                        <div class="col-lg-6">
                            <label class="form__input-title">Flat / Unit</label>
                            <input type="text" class="form-control" value="<?= $booking['unit_name'] ?> (Floor: <?= $booking['floor_no'] ?>)" readonly>
                        </div>
                        <div class="col-lg-6">
                            <label class="form__input-title">Flat Size</label>
                            <input type="text" class="form-control" value="<?= $booking['sqft'] ?>" readonly>
                        </div>
                        <div class="col-lg-6">
                            <label class="form__input-title">Land Share Value</label>
                            <input type="text" class="form-control" value="<?= number_format($booking['price_value'], 2) ?>" readonly>
                        </div>
                        <div class="col-lg-6">
                            <label class="form__input-title">Agreement Value</label>
                            <input type="text" class="form-control" value="<?= number_format($booking['agreement_value'], 2) ?>" readonly>
                        </div>
                        <div class="col-lg-6">
                            <label class="form__input-title">Discount Value</label>
                            <input type="text" class="form-control"  value="<?= number_format($booking['discount_amount'] ?? 0, 2) ?>"  readonly>
                        </div>
                         <div class="col-lg-6">
                            <label class="form__input-title">Total EMI Number</label>
                            <input type="number" name="total_emi_no" class="form-control" value="">
                        </div>

                        <!-- Payment Type -->
                        <div class="col-lg-6">
                            <label class="form__input-title">Payment Type *</label>
                            <select name="payment_type" class="form-select" required>
                                <option value="">Select Type</option>
                                <option value="booking_money">Booking</option>
                                <option value="registration">Registration</option>
                                <option value="emi">EMI</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <!-- Payment Method -->
                        <div class="col-lg-6">
                            <label class="form__input-title">Payment Method *</label>
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
                            <label class="form__input-title">Bank Name - Branch*</label>
                            <input type="text" name="bank_name" class="form-control" placeholder="Enter Bank Name">
                        </div>

                        <div class="col-lg-6 extra-input" id="bankAccountFields" style="display:none;">
                            <label class="form__input-title">Bank Account No *</label>
                            <input type="text" name="bank_account_no" class="form-control" placeholder="Enter Account Number">
                        </div>

                        <!-- Cheque Details -->
                        <div class="col-lg-6 extra-input" id="chequeFields" style="display:none;">
                            <label class="form__input-title">Cheque No *</label>
                            <input type="text" name="cheque_no" class="form-control" placeholder="Enter Cheque Number">
                        </div>

                        <!-- Online Details -->
                        <div class="col-lg-6 extra-input" id="onlineFields" style="display:none;">
                            <label class="form__input-title">Online Type *</label>
                            <input type="text" name="online_type" class="form-control" placeholder="Enter Online Type (Bkash, Nagad, etc)">
                        </div>


                        <!-- Amount -->
                        <div class="col-lg-6">
                            <label class="form__input-title">Pay Amount *</label>
                            <input type="number" step="0.01" name="amount" class="form-control" placeholder="e.g. 5000000" required>
                        </div>

                        <!-- Payment Date -->
                        <div class="col-lg-6">
                            <label class="form__input-title">Payment Date *</label>
                            <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <!-- Transaction Reference -->
                        <div class="col-lg-6">
                            <label class="form__input-title">Transaction Reference</label>
                            <input type="text" name="transaction_ref" class="form-control" placeholder="Optional reference number">
                        </div>

                        <!-- Status -->
                        <div class="col-lg-6">
                            <label class="form__input-title">Payment Status</label>
                            <select name="payment_status" class="form-select">
                                <option value="paid">Paid</option>
                                <option value="pending">Pending</option>
                                <option value="failed">Failed</option>
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
                            <th>#</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Payment Date</th>
                            <th>Transaction Ref</th>
                            <th>Recevied By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($payments)): ?>
                            <?php foreach ($payments as $index => $p): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= ucfirst($p['payment_type']) ?></td>
                                    <td><?= number_format($p['amount'], 2) ?></td>
                                    <td><?= ucfirst($p['payment_method']) ?></td>
                                    <td><?= ucfirst($p['payment_status']) ?></td>
                                    <td><?= date('d M Y', strtotime($p['payment_date'])) ?></td>
                                    <td><?= $p['transaction_ref'] ?? '-' ?></td>
                                    <td><?= $p['created_by_name'] ?? '-' ?></td>
                                    <td>
                                        <a target="_blank"
                                            href="<?= $GLOBALS['base_url'] ?>/payment-booking-invoice/<?= $p['id'] ?>"
                                            class="btn btn-success btn-sm">
                                            Print Invoice
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">No payments found for this onboarding.</td>
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



















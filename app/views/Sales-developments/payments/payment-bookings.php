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
                <h5>Add Payment for <?= $onboard['lead_name'] ?? 'Lead' ?></h5>

                <form action="<?= $GLOBALS['base_url'] ?>/payment-booking-store" method="POST">
                    <div class="row gy-3">

                        <!-- Hidden Inputs -->
                        <input type="hidden" name="booking_id" value="<?= $onboard['id'] ?>">
                        <input type="hidden" name="created_by" value="<?= $_SESSION['user']['id'] ?>">

                        <!-- Lead Info (Readonly) -->
                        <div class="col-lg-6">
                            <label class="form-label">Lead Name</label>
                            <input type="text" class="form-control" value="<?= $onboard['lead_name'] ?>" readonly>
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label">Project</label>
                            <input type="text" class="form-control" value="<?= $onboard['project_name'] ?>" readonly>
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label">Flat / Unit</label>
                            <input type="text" class="form-control" value="<?= $onboard['unit_name'] ?> (Floor: <?= $onboard['floor_no'] ?>)" readonly>
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label">Agreement Value</label>
                            <input type="text" class="form-control" value="<?= number_format($onboard['agreement_value'], 2) ?>" readonly>
                        </div>

                        <!-- Payment Type -->
                        <div class="col-lg-6">
                            <label class="form-label">Payment Type *</label>
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
                            <label class="form-label">Payment Method *</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="">Select Method</option>
                                <option value="cash">Cash</option>
                                <option value="bank">Bank</option>
                                <option value="online">Online</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>

                        <!-- Amount -->
                        <div class="col-lg-6">
                            <label class="form-label">Pay Amount *</label>
                            <input type="number" step="0.01" name="amount" class="form-control" placeholder="e.g. 5000000" required>
                        </div>

                        <!-- Payment Date -->
                        <div class="col-lg-6">
                            <label class="form-label">Payment Date *</label>
                            <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <!-- Transaction Reference -->
                        <div class="col-lg-6">
                            <label class="form-label">Transaction Reference</label>
                            <input type="text" name="transaction_ref" class="form-control" placeholder="Optional reference number">
                        </div>

                        <!-- Status -->
                        <div class="col-lg-6">
                            <label class="form-label">Payment Status</label>
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
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Payment Date</th>
                            <th></th>
                            <th>Transaction Ref</th>
                            <th>Created By</th>
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
                                    <td><?= $p['payment_date'] ?></td>
                                    <td><?= $p['transaction_ref'] ?? '-' ?></td>
                                    <td><?= $p['created_by_name'] ?? '-' ?> </td>
                                    <td><a target="_blank"
                                        href="<?= $GLOBALS['base_url'] ?>/payment-invoice/<?= $p['id'] ?>"
                                        class="btn btn-success btn-sm">
                                        Print Invoice
                                    </a></td>

                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No payments found for this onboarding.</td>
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
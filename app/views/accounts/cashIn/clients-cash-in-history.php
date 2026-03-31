<?php
$page_title = "Clients Cash in History";
$page = 'Clients';
$addPage = 'add transaction';
$addPage = true;
$show_breadcrumb = true;
?>

<div class="row">
    <!-- Client Header Section -->
    <div class="col-xxl-12 mb-4">
        <div class="card__wrapper">
            <div class="card__body">
                <h3 class="mb-2 card__icon">
                    <i class="fa-light fa-user-circle me-2"></i>
                    <?= htmlspecialchars($clientDetails['client_name'] ?? 'N/A') ?>
                </h3>
                <div class="d-flex flex-wrap gap-3">
                    <span class="text-muted">
                        <i class="fa-light fa-building mx-2"></i>
                        <strong>Project:</strong> <?= htmlspecialchars($clientDetails['project_name'] ?? 'N/A') ?>
                    </span>
                    <span class="text-muted">
                        <i class="fa-light fa-home mx-2"></i>
                        <strong>Flat:</strong> UNIT <?= htmlspecialchars($clientDetails['unit_name'] ?? '') ?> - FLOOR <?= htmlspecialchars($clientDetails['flat_no'] ?? 'N/A') ?>
                    </span>
                    <span class="text-muted">
                        <i class="fa-light fa-phone mx-2"></i>
                        <strong>Phone:</strong> <?= htmlspecialchars($clientDetails['phone'] ?? 'N/A') ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row">

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span><i class="fa-light fa-money-bill"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Total Price</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['total_price'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Total Land Share Card -->
<div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
    <div class="card__wrapper">
        <div class="d-flex align-items-center gap-1">
            <div class="card__icon" >
                <span><i class="fa-light fa-landmark"></i></span>
            </div>
            <div class="card__title-wrap">
                <h6 class="card__sub-title mb-10">Total Land Share</h6>
                <div class="d-flex flex-wrap align-items-end gap-10">
                    <h4 class="card__title">TK.<?= number_format($paymentSummary['total_land_share'] ?? 0, 2) ?></h4>
                </div>
            </div>
        </div>
    </div>
</div>
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span><i class="fa-light fa-hand-holding-usd"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Booking Money</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['booking_money'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<!-- Paid Land Share Card -->
<div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
    <div class="card__wrapper">
        <div class="d-flex align-items-center gap-1">
            <div class="card__icon" >
                <span><i class="fa-light fa-hand-holding-usd"></i></span>
            </div>
            <div class="card__title-wrap">
                <h6 class="card__sub-title mb-10"> Land Share Paid</h6>
                <div class="d-flex flex-wrap align-items-end gap-10">
                    <h4 class="card__title">TK.<?= number_format($paymentSummary['total_land_share_paid'] ?? 0, 2) ?></h4>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Remaining Land Share Card -->
<div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
    <div class="card__wrapper">
        <div class="d-flex align-items-center gap-1">
            <div class="card__icon" >
                <span><i class="fa-light fa-scale-balanced"></i></span>
            </div>
            <div class="card__title-wrap">
                <h6 class="card__sub-title mb-10">Remaining Land Share</h6>
                <div class="d-flex flex-wrap align-items-end gap-10">
                    <h4 class="card__title">TK.<?= number_format($paymentSummary['remaining_land_share'] ?? 0, 2) ?></h4>
                </div>


            </div>
        </div>
    </div>
</div>
        <!-- NEW: DISCOUNT CARD -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span><i class="fa-light fa-tag"></i></span> <!-- Changed icon -->
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Discount Amount</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title text-success">TK.<?= number_format($paymentSummary['discount_amount'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span><i class="fa-light fa-file-certificate"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Registration Money</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['registration_money'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span><i class="fa-light fa-building"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Total Construction Fee</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['construction_fee'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span><i class="fa-light fa-money-check-dollar"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Construction Fee Paid</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['construction_fee_paid_after_service'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span><i class="fa-light fa-hand-holding-dollar"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Service Fee (10%)</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['total_service_fee'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span><i class="fa-light fa-money-check-dollar"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Remaining Construction Fee</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['remaining_construction_fee'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- REMOVED: Average EMI Amount Card -->
        <!--
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-calculator"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Average EMI Amount</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title">TK.<?= number_format($paymentSummary['avg_emi_amount'] ?? 0, 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    -->

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span><i class="fa-light fa-list-ol"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Total EMI</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title"><?= $paymentSummary['total_emi'] ?? 0 ?> </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span><i class="fa-light fa-check-circle"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Completed EMI</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title"><?= $paymentSummary['completed_emi'] ?? 0 ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span><i class="fa-light fa-clock"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Pending EMI</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title"><?= $paymentSummary['pending_emi'] ?? 0 ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span><i class="fa-light fa-calendar-check"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Last Payment</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">
                                <?php if ($paymentSummary['last_payment_date']): ?>
                                    <?= date('d M, Y', strtotime($paymentSummary['last_payment_date'])) ?>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction History Table -->
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="card__header">
                <h5 class="card__title">Transaction History</h5>
            </div>
            <div class="col-xxl-12 mb-4">
                <div class="mt-3 d-flex gap-2 position ">
                    <a href="<?= $GLOBALS['base_url'] ?>/client-cash-in-form?booking_id=<?= $clientDetails['booking_id'] ?>" class="btn btn-primary mb-3">
                        <i class="fa-light fa-plus me-2"></i> Add Transaction
                    </a>
                    <a href="<?= $base_url ?>/client-statement/<?= $clientDetails['booking_id'] ?>" target="_blank" class="btn btn-primary mb-3">
                        <i class="fa-light fa-file-invoice me-2"></i> Get Statement
                    </a>
                </div>
                <div class="table__wrapper table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Payment Type</th>
                                <th>Details</th>
                                <th>Amount</th>
                                <th>Due Amount</th> 
                                <th>Land Share</th>
                                <th>Service Fee</th>
                                <th>Status</th>
                                <th>Added By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?php echo $transaction['formatted_date']; ?></td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo $transaction['installment_label']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $transaction['payment_details']; ?></td>
                                    <td>TK.<?php echo number_format($transaction['amount'], 2); ?></td>
                                    <!-- ADD THIS -->
                                     <td>
                                         <?php if (!empty($transaction['due_emi_amount']) && $transaction['due_emi_amount'] > 0): ?>
                                             TK.<?php echo number_format($transaction['due_emi_amount'], 2); ?>
                                         <?php else: ?>
                                             <span class="text-muted">-</span>
                                         <?php endif; ?>
                                     </td>
                                    <td>
                                        <?php if ($transaction['land_share_amount'] > 0): ?>
                                            TK.<?php echo number_format($transaction['land_share_amount'], 2); ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($transaction['service_fee'] > 0): ?>
                                            TK.<?php echo number_format($transaction['service_fee'], 2); ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $transaction['status_class']; ?>">
                                            <?php echo $transaction['status_text']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo !empty($transaction['created_by_name']) ? htmlspecialchars($transaction['created_by_name']) : '<span class="text-muted">System</span>'; ?>
                                    </td>
                                    <td>
                                        
                                        <div class="d-flex align-items-center justify-content-start gap-10">
                                            <?php if (!empty($transaction['id']) && strpos($transaction['id'], 'booking-') === false): ?>
                                                <!-- INVOICE BUTTON -->
                                                <a href="<?= $GLOBALS['base_url'] ?>/payment-invoice?payment_id=<?= $transaction['id'] ?>"
                                                    class="table__icon invoice"
                                                    style="color: #fff; background-color: #6c5ffc;"
                                                    title="View Invoice"
                                                    target="_blank">
                                                    <i class="fa-light fa-file-invoice"></i>
                                                </a>
                                                <!-- EDIT BUTTON -->
                                                <?php if ($transaction['payment_type'] !== 'booking_money'): ?>
                                                <a href="<?= $GLOBALS['base_url'] ?>/edit-client-cash-in/<?= $transaction['id'] ?>"
                                                    class="table__icon edit"
                                                    title="Edit">
                                                    <i class="fa-sharp fa-light fa-pen"></i>
                                                </a>
                                                <?php else: ?>
                                                <span class="table__icon disabled" title="Booking Money Cannot Be Edited" style="background-color: #f3f6f9; color: #a5aeb7;">
                                                    <i class="fa-regular fa-lock"></i>
                                                </span>
                                                <?php endif; ?>
                                                <!-- DELETE BUTTON - FIXED -->
                                                <a href="#" class="removeBtn table__icon delete"
                                                    onclick="deleteTransaction(<?= $transaction['id'] ?>); return false;"
                                                    title="Delete">
                                                    <i class="fa-regular fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="table__icon disabled" title="System Generated">
                                                    <i class="fa-regular fa-lock"></i>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        //  FIXED: Changed from $clientDetails['id'] to $clientDetails['booking_id']
        function deleteTransaction(transactionId) {
            if (confirm('Are you sure you want to delete this transaction? This action cannot be undone.')) {
                let currentBookingId = <?= $clientDetails['booking_id'] ?? 'null' ?>;

                if (currentBookingId) {
                    window.location.href = '<?= $GLOBALS['base_url'] ?>/delete-transaction?payment_id=' + transactionId + '&booking_id=' + currentBookingId;
                } else {
                    alert('Cannot delete: Missing booking information');
                }
            }
        }
    </script>

    <style>
        .card__icon span {
            color: var(--clr-theme-primary);
            background: rgba(108, 95, 252, 0.1);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            text-align: center;
            justify-content: center;
            align-items: center;
            font-size: 16px;
        }

        .card__title {
            font-size: 1.25rem;
        }

        @media (min-width: 975px) {
            .dataTables_wrapper .dataTables_filter input[type=search] {
                border: 1px solid var(--clr-border-1);
                color: var(--clr-text-body);
                padding: 0 10px;
                margin-inline-start: 10px;
                height: 36px;
                border-radius: 6px;
                width: 380px;
            }
        }

        @media (max-width: 975px) {
            .card__wrapper .card__body h3 {
                font-size: 1.3rem;
            }

            .card__title {
                font-size: 1rem;
            }
        }

        @media (max-width: 768px) {
            .btn {
                font-size: 14px;
                padding: 6px 24px;
            }
        }

        @media (min-width: 575px) {
            .position {
                justify-content: end !important;
            }
        }

        @media (max-width: 480px) {
            .btn {
                font-size: 16px;
                padding: 6px 21px;
            }

            .position {
                justify-content: start !important;
            }
        }

        @media (max-width: 400px) {
            .btn {
                font-size: 14px;
                padding: 6px 14px;
            }
        }
    </style>
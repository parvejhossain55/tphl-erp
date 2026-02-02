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
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-money-bill"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Price</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title">৳<?= number_format($paymentSummary['total_price'] ?? 0, 2) ?></h4>
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
                        <h4 class="card__title">৳<?= number_format($paymentSummary['booking_money'] ?? 0, 2) ?></h4>
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
                        <h4 class="card__title">৳<?= number_format($paymentSummary['registration_money'] ?? 0, 2) ?></h4>
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
                        <h4 class="card__title">৳<?= number_format($paymentSummary['construction_fee'] ?? 0, 2) ?></h4>
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
                        <h4 class="card__title">৳<?= number_format($paymentSummary['construction_fee_paid_after_service'] ?? 0, 2) ?></h4>
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
                        <h4 class="card__title">৳<?= number_format($paymentSummary['total_service_fee'] ?? 0, 2) ?></h4>
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
                        <h4 class="card__title">৳<?= number_format($paymentSummary['remaining_construction_fee'] ?? 0, 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-calculator"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Average EMI Amount</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title">৳<?= number_format($paymentSummary['avg_emi_amount'] ?? 0, 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                    <table class="table mb-20 multiple_tables">
                        <thead>
                            <tr class="table__title table__sort">
                                <th>Date</th>
                                <th>Installment</th>
                                <th>Paid Amount</th>
                                <th>Service Fee (10%)</th>
                                <th>Net Paid EMI</th>
                                <th>Due Amount</th>
                                <th>Status</th>
                                <th>Method</th>
                                <th>Payment Details</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody class="table__body">
                            <?php if (!empty($transactions)): ?>
                                <?php foreach ($transactions as $transaction): ?>
                                    <?php
                                    // Get service fee for this transaction from emi_schedules
                                    $serviceFee = 0;
                                    $netPaidEmi = 0;
                                    
                                    if ($transaction['payment_type'] === 'emi' && isset($transaction['id'])) {
                                        // Fetch service fee from emi_schedules
                                        $serviceFeeStmt = $this->db->prepare("
                                            SELECT service_fee 
                                            FROM emi_schedules 
                                            WHERE lead_payment_id = ?
                                        ");
                                        if ($serviceFeeStmt) {
                                            $serviceFeeStmt->bind_param("i", $transaction['id']);
                                            $serviceFeeStmt->execute();
                                            $serviceFeeResult = $serviceFeeStmt->get_result();
                                            $serviceFeeData = $serviceFeeResult ? $serviceFeeResult->fetch_assoc() : null;
                                            $serviceFee = $serviceFeeData['service_fee'] ?? 0;
                                        }
                                        
                                        // Calculate Net Paid EMI = Paid Amount - Service Fee
                                        $netPaidEmi = ($transaction['amount'] ?? 0) - $serviceFee;
                                    }
                                    ?>
                                    <tr>
                                        <td>
                                            <?php
                                            if (isset($transaction['payment_date'])) {
                                                echo date('d M, Y', strtotime($transaction['payment_date']));
                                            } elseif (isset($transaction['booking_date'])) {
                                                echo date('d M, Y', strtotime($transaction['booking_date']));
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($transaction['payment_type'] === 'emi'): ?>
                                                <span class="bd-badge bg-primary">
                                                    <?= $transaction['installment_label'] ?? 'EMI' ?>
                                                </span>
                                            <?php elseif ($transaction['payment_type'] === 'booking_money'): ?>
                                                <span class="bd-badge bg-secondary">
                                                    BOOKING
                                                </span>
                                            <?php else: ?>
                                                <span class="bd-badge bg-info">
                                                    <?= ucfirst(str_replace('_', ' ', $transaction['payment_type'])) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong>৳<?= number_format($transaction['amount'] ?? 0, 2) ?></strong>
                                        </td>
                                        <!-- Service Fee Column -->
                                        <td>
                                            <?php if ($serviceFee > 0): ?>
                                                <div class="d-flex align-items-center gap-1">
                                                    <strong style="color: #e67e22;">৳<?= number_format($serviceFee, 2) ?></strong>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted" style="font-size: 18px;">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <!-- NEW: Net Paid EMI Column -->
                                        <td>
                                            <?php if ($transaction['payment_type'] === 'emi' && $netPaidEmi > 0): ?>
                                                <div class="d-flex align-items-center gap-1">
                                                    <strong style="color: #27ae60;">৳<?= number_format($netPaidEmi, 2) ?></strong>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted" style="font-size: 18px;">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <!-- Due Amount Column -->
                                        <td>
                                            <?php 
                                            $dueAmount = 0;
                                            
                                            // For EMI transactions, get due_emi_amount from emi_schedules
                                            if ($transaction['payment_type'] === 'emi' && isset($transaction['due_emi_amount'])) {
                                                $dueAmount = floatval($transaction['due_emi_amount']);
                                            }
                                            
                                            // Display due amount with styling
                                            if ($dueAmount > 0): 
                                            ?>
                                                <div class="d-flex align-items-center gap-1">
                                                    <strong style="color: #e74c3c;">৳<?= number_format($dueAmount, 2) ?></strong>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted" style="font-size: 18px;">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (isset($transaction['status_class']) && isset($transaction['status_text'])): ?>
                                                <span class="bd-badge <?= $transaction['status_class'] ?>">
                                                    <?= $transaction['status_text'] ?>
                                                </span>
                                            <?php else: ?>
                                                <?php if (($transaction['payment_status'] ?? 'paid') == 'paid'): ?>
                                                    <span class="bd-badge bg-success">Paid</span>
                                                <?php elseif (($transaction['payment_status'] ?? '') == 'partially_paid'): ?>
                                                    <span class="bd-badge bg-info">Partially Paid</span>
                                                <?php elseif (($transaction['payment_status'] ?? '') == 'pending'): ?>
                                                    <span class="bd-badge bg-warning">Pending</span>
                                                <?php else: ?>
                                                    <span class="bd-badge bg-danger">Failed</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="bd-badge bg-light text-dark">
                                                <?= ucfirst($transaction['payment_method'] ?? 'agreement') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small>
                                                <?php if (($transaction['payment_method'] ?? '') === 'bank'): ?>
                                                    <?= $transaction['bank_name'] ? htmlspecialchars($transaction['bank_name']) : '' ?>
                                                    <?= $transaction['bank_account_no'] ? ' (' . htmlspecialchars($transaction['bank_account_no']) . ')' : '' ?>
                                                <?php elseif (($transaction['payment_method'] ?? '') === 'cheque'): ?>
                                                    Cheque No: <?= $transaction['cheque_no'] ? htmlspecialchars($transaction['cheque_no']) : 'N/A' ?>
                                                    <?= $transaction['bank_name'] ? ' - ' . htmlspecialchars($transaction['bank_name']) : '' ?>
                                                <?php elseif (($transaction['payment_method'] ?? '') === 'online'): ?>
                                                    <?= $transaction['online_type'] ? htmlspecialchars($transaction['online_type']) : 'Online' ?>
                                                <?php elseif (($transaction['payment_method'] ?? '') === 'agreement'): ?>
                                                    Land Share Agreement
                                                <?php else: ?>
                                                    Cash Payment
                                                <?php endif; ?>
                                            </small>
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
                                                    <a href="<?= $GLOBALS['base_url'] ?>/edit-client-cash-in/<?= $transaction['id'] ?>"
                                                        class="table__icon edit"
                                                        title="Edit">
                                                        <i class="fa-sharp fa-light fa-pen"></i>
                                                    </a>
                                                    <!-- DELETE BUTTON -->
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
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fa-light fa-receipt fa-2x mb-3"></i><br>
                                            No transactions found
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function deleteTransaction(transactionId, bookingId) {
            if (confirm('Are you sure you want to delete this transaction? This action cannot be undone.')) {
                let currentBookingId = <?= $clientDetails['id'] ?? 'null' ?>;

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
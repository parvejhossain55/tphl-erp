<?php
$page_title = "Clients Cash in History";
$page = 'Clients';
$addPage = 'add transaction';
$addPage = true;
$show_breadcrumb = true;
?>
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
</style>
<div class="row">
    <!-- Client Header Section -->
    <div class="col-xxl-12 mb-4">
        
        <div class="card__wrapper">
            <div class="card__body">
                <h3 class="mb-2">
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
                        <h4 class="card__title">৳<?= number_format($paymentSummary['construction_fee_paid'] ?? 0, 2) ?></h4>
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
                        <h4 class="card__title"><?= $paymentSummary['total_emi'] ?? 0 ?></h4>
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
                        <div class="d-flex flex-wrap justify-content-end gap-2">
             <a href="<?= $GLOBALS['base_url'] ?>/client-cash-in-form?booking_id=<?= $clientDetails['id'] ?>" class="btn btn-primary mb-3">
                        <i class="fa-light fa-plus me-2"></i> Add Transaction
                    </a>
                </div>
            <div class="table__wrapper table-responsive">
                <table class="table mb-20 multiple_tables">
                    <thead>
                        <tr class="table__title table__sort">
                            <th>Date</th>
                            <th>Installment</th>
                            <th>Type</th>
                            <th>Paid Amount</th>
                            <th>Status</th>
                            <th>Method</th>
                            <th>Payment Details</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php if (!empty($transactions)): ?>
                            <?php foreach ($transactions as $transaction): ?>
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
                                        <?= strtoupper(str_replace('_', ' ', $transaction['payment_type'])) ?>
                                    </td>
                                    <td>
                                        <strong>৳<?= number_format($transaction['amount'] ?? 0, 2) ?></strong>
                                    </td>
                                    <td>
                                        <?php if (($transaction['payment_status'] ?? 'paid') == 'paid'): ?>
                                            <span class="bd-badge bg-success">Paid</span>
                                        <?php elseif (($transaction['payment_status'] ?? '') == 'pending'): ?>
                                            <span class="bd-badge bg-warning">Pending</span>
                                        <?php else: ?>
                                            <span class="bd-badge bg-danger">Failed</span>
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
                                                Booking Agreement
                                            <?php else: ?>
                                                Cash Payment
                                            <?php endif; ?>
                                        </small>
                                    </td>

                                    <td>
                                        <div class="d-flex align-items-center justify-content-start gap-10">
                                            <?php if (!empty($transaction['id']) && strpos($transaction['id'], 'booking-') === false): ?>
                                                <a href="<?= $GLOBALS['base_url'] ?>/edit-client-cash-in/<?= $transaction['id'] ?>"
                                                    class="table__icon edit"
                                                    title="Edit">
                                                    <i class="fa-sharp fa-light fa-pen"></i>
                                                </a>
                                                <a href="#" class="removeBtn table__icon delete"
                                                    onclick="deleteTransaction(<?= $transaction['id'] ?>)"
                                                    title="Delete">
                                                    <i class="fa-regular fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <!-- For booking money placeholder, show disabled or different actions -->
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
                                <td colspan="8" class="text-center py-4">
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

<!-- View Transaction Modal -->
<div class="modal fade" id="viewTransactionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Transaction Details</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body" id="transactionDetails">
                <!-- Details will be loaded here via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    function editTransaction(transactionId) {
        // This is now handled by the link
    }

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

    function viewTransaction(transactionId) {
        fetch('<?= $GLOBALS['base_url'] ?>/get-transaction/' + transactionId)
            .then(response => response.json())
            .then(data => {
                if (data) {
                    let detailsHtml = `
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Client:</strong> ${data.client_name}</p>
                            <p><strong>Project:</strong> ${data.project_name}</p>
                            <p><strong>Flat No:</strong> ${data.flat_no}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Date:</strong> ${new Date(data.payment_date).toLocaleDateString()}</p>
                            <p><strong>Status:</strong> <span class="badge ${data.payment_status === 'paid' ? 'bg-success' : data.payment_status === 'pending' ? 'bg-warning' : 'bg-danger'}">${data.payment_status.toUpperCase()}</span></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Payment Type:</strong> ${data.payment_type.toUpperCase().replace('_', ' ')}</p>
                            <p><strong>Amount:</strong> ৳${parseFloat(data.amount).toLocaleString('en-BD', {minimumFractionDigits: 2})}</p>
                            <p><strong>Method:</strong> ${data.payment_method.toUpperCase()}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Reference:</strong> ${data.transaction_ref || 'N/A'}</p>
                            <p><strong>Created By:</strong> ${data.created_by_name || 'N/A'}</p>
                            <p><strong>Created At:</strong> ${new Date(data.created_at).toLocaleString()}</p>
                        </div>
                    </div>`;

                    if (data.payment_type === 'emi') {
                        detailsHtml += `
                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                <h6>EMI Details</h6>
                                <p><strong>Installment:</strong> ${data.emi_no} of ${data.emi_total}</p>
                            </div>
                        </div>`;
                    }

                    if (data.bank_name || data.bank_account_no || data.cheque_no || data.online_type) {
                        detailsHtml += `
                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                <h6>Payment Method Details</h6>
                                ${data.bank_name ? `<p><strong>Bank:</strong> ${data.bank_name}</p>` : ''}
                                ${data.bank_account_no ? `<p><strong>Account No:</strong> ${data.bank_account_no}</p>` : ''}
                                ${data.cheque_no ? `<p><strong>Cheque No:</strong> ${data.cheque_no}</p>` : ''}
                                ${data.online_type ? `<p><strong>Online Type:</strong> ${data.online_type}</p>` : ''}
                            </div>
                        </div>`;
                    }

                    document.getElementById('transactionDetails').innerHTML = detailsHtml;
                    var modal = new bootstrap.Modal(document.getElementById('viewTransactionModal'));
                    modal.show();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while loading transaction details.');
            });
    }

    function deleteBookingMoney(bookingId) {
        if (confirm('Are you sure you want to delete this booking? This will delete ALL related transactions and cannot be undone.')) {
            window.location.href = '<?= $GLOBALS['base_url'] ?>/delete-booking-money?booking_id=' + bookingId;
        }
    }
</script>
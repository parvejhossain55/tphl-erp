<?php
$page_title = "Clients Cash in History";
$page = 'Clients';
$addPage = 'add transaction';
$addPage = true;
$show_breadcrumb = true;
?>

<div class="row">
    <!-- Summary Cards -->
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
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

    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
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

    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-light fa-badge-check"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Paid</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title">৳<?= number_format($paymentSummary['total_paid'] ?? 0, 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-light fa-exclamation-circle"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Current Due</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title">৳<?= number_format($paymentSummary['due_amount'] ?? 0, 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
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

    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
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

    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
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

    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
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
                <div class="ms-auto">
                    <a href="<?= $GLOBALS['base_url'] ?>/client-cash-in-form?booking_id=<?= $clientDetails['id'] ?? '' ?>"
                        class="btn btn-success my-2">
                        <i class="fa-solid fa-plus me-2"></i>Add Transaction
                    </a>
                </div>
            </div>

            <div class="table__wrapper ">
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
                            <th class="d-none">Action</th>
                        </tr>
                    </thead>
<tbody class="table__body">
    <?php if (!empty($transactions)): ?>
        <?php foreach ($transactions as $transaction): ?>
            <tr>
                <td>
                    <?= $transaction['payment_date'] ? date('d M, Y', strtotime($transaction['payment_date'])) : '' ?>
                </td>
                <td>
                    <?php if ($transaction['payment_type'] === 'emi'): ?>
                        <span class="bd-badge bg-primary">
                            <?= $transaction['installment_label'] ?? 'EMI' ?>
                        </span>
                    <?php else: ?>
                        <span class="bd-badge bg-secondary">
                            <?= $transaction['payment_type'] === 'booking_money' ? 'BOOKING' : ucfirst(str_replace('_', ' ', $transaction['payment_type'])) ?>
                        </span>
                    <?php endif; ?>
                </td>
                <td>
                    <?= strtoupper(str_replace('_', ' ', $transaction['payment_type'])) ?>
                </td>
                <td>
                    <strong>৳<?= number_format($transaction['amount'], 2) ?></strong>
                </td>
                <td>
                    <?php if ($transaction['payment_status'] == 'paid'): ?>
                        <span class="bd-badge bg-success">Paid</span>
                    <?php elseif ($transaction['payment_status'] == 'pending'): ?>
                        <span class="bd-badge bg-warning">Pending</span>
                    <?php else: ?>
                        <span class="bd-badge bg-danger">Failed</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="bd-badge bg-light text-dark">
                        <?= ucfirst($transaction['payment_method']) ?>
                    </span>
                </td>
                <td>
                    <small>
                        <?php if ($transaction['payment_method'] === 'bank'): ?>
                            <?= $transaction['bank_name'] ? htmlspecialchars($transaction['bank_name']) : '' ?>
                            <?= $transaction['bank_account_no'] ? ' (' . htmlspecialchars($transaction['bank_account_no']) . ')' : '' ?>
                        <?php elseif ($transaction['payment_method'] === 'cheque'): ?>
                            Cheque No: <?= $transaction['cheque_no'] ? htmlspecialchars($transaction['cheque_no']) : 'N/A' ?>
                            <?= $transaction['bank_name'] ? ' - ' . htmlspecialchars($transaction['bank_name']) : '' ?>
                        <?php elseif ($transaction['payment_method'] === 'online'): ?>
                            <?= $transaction['online_type'] ? htmlspecialchars($transaction['online_type']) : 'Online' ?>
                        <?php else: ?>
                            Cash Payment
                        <?php endif; ?>
                    </small>
                </td>

                <td class="d-none">
                    <div class="d-flex align-items-center justify-content-start gap-10">
                        <a href="#" class="table__icon view"
                            onclick="viewTransaction(<?= $transaction['id'] ?>)"
                            title="View Details">
                            <i class="fa-regular fa-eye"></i>
                        </a>
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
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="9" class="text-center py-4">
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
        // Get booking ID from URL or use a data attribute
        let currentBookingId = <?= $clientDetails['id'] ?? 'null' ?>;
        
        // Show loading or disable button
        if (currentBookingId) {
            window.location.href = '<?= $GLOBALS['base_url'] ?>/delete-transaction?payment_id=' + transactionId + '&booking_id=' + currentBookingId;
        } else {
            alert('Cannot delete: Missing booking information');
        }
    }
}

    function viewTransaction(transactionId) {
        // Fetch transaction details
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

function viewTransaction(transactionId) {
    // Check if it's booking money (id = -1)
    if (transactionId == -1) {
        // Show booking money details
        let detailsHtml = `
        <div class="row">
            <div class="col-md-6">
                <p><strong>Client:</strong> <?= htmlspecialchars($clientDetails['client_name'] ?? '') ?></p>
                <p><strong>Project:</strong> <?= htmlspecialchars($clientDetails['project_name'] ?? '') ?></p>
                <p><strong>Flat No:</strong> <?= $clientDetails['flat_no'] ?? '' ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Date:</strong> <?= $clientDetails['booking_date'] ? date('d M, Y', strtotime($clientDetails['booking_date'])) : 'N/A' ?></p>
                <p><strong>Status:</strong> <span class="badge bg-success">BOOKED</span></p>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-md-6">
                <p><strong>Payment Type:</strong> BOOKING MONEY</p>
                <p><strong>Amount:</strong> ৳<?= number_format($clientDetails['agreement_value'] ?? 0, 2) ?></p>
                <p><strong>Method:</strong> Agreement</p>
            </div>
            <div class="col-md-6">
                <p><strong>Reference:</strong> BOOKING-<?= $clientDetails['id'] ?? '' ?></p>
                <p><strong>Created At:</strong> <?= $clientDetails['created_at'] ? date('d M, Y H:i', strtotime($clientDetails['created_at'])) : 'N/A' ?></p>
            </div>
        </div>`;
        
        document.getElementById('transactionDetails').innerHTML = detailsHtml;
        var modal = new bootstrap.Modal(document.getElementById('viewTransactionModal'));
        modal.show();
        return;
    }
    
    // Rest of the existing viewTransaction code for regular payments...
    // [Keep your existing viewTransaction code here]
}
</script>
<?php
$page_title = "Management Cash In";
$page = 'Management';
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

    .action-transaction-buttons {
        display: flex;
        flex-wrap: wrap;
        justify-content: end;
    }

    @media (max-width: 640px) {
        .card__wrapper {
            padding: 26px 15px;
        }

        .card__header {
            margin-top: 5px;
            flex-direction: column;
        }

        .action-transaction-buttons {
            margin-top: 10px;
            flex-wrap: nowrap
        }

        .btn {
            font-size: 16px;
            border-radius: 6px;
            padding: 8px;
            display: inline-block;
            margin-top: 6px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            font-size: 12px;
            padding: 6px 8px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            font-size: 12px;
            padding: 6px 16px;
        }
    }

    @media (max-width: 575px) {
        .card__wrapper {
            padding: 26px 15px;
        }

        .card__header {
            margin-top: 5px;
            flex-direction: column;
        }

        .action-transaction-buttons {
            margin-top: 10px;
            flex-wrap: nowrap
        }

        .btn {
            font-size: 12px;
            border-radius: 6px;
            padding: 5px;
            display: inline-block;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            font-size: 12px;
            padding: 6px 8px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            font-size: 12px;
            padding: 6px 16px;
        }
    }
</style>
<div class="row">
    <!-- Summary Cards -->
    <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-list"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Transactions</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title fs-6"><?= $statistics['total_transactions'] ?? 0 ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-money-bill"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title  mb-10">Total Amount</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title fs-6">৳<?= number_format($statistics['total_amount'] ?? 0, 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-badge-check"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Paid Amount</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title fs-6">৳<?= number_format($statistics['paid_amount'] ?? 0, 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-clock"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Pending Amount</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title fs-6">৳<?= number_format($statistics['pending_amount'] ?? 0, 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction Table -->
    <div class="col-xxl-12 mb-4">
        <div class="card__wrapper">
            <div class="card__header d-flex justify-content-between align-items-center mb-3">
                <h5 class="card__title" style="margin-top: -13px;">Management Transactions</h5>
                <div class="action-transaction-buttons gap-2">
                    <a href="<?= $GLOBALS['base_url'] ?>/management-cash-in-statement" target="_blank" class="btn btn-primary">
                        <i class="fa-light fa-file-lines me-2"></i> Get Statement
                    </a>
                    <a href="<?= $GLOBALS['base_url'] ?>/management-roles" class="btn btn-info">
                        <i class="fa-light fa-users-gear me-2"></i> Manage Roles
                    </a>
                    <a href="<?= $GLOBALS['base_url'] ?>/add-management-cash-in" class="btn btn-success">
                        <i class="fa-light fa-plus me-2"></i> Add Transaction
                    </a>
                </div>
            </div>
            <div class="card__body">
                <?php if (!empty($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= $_SESSION['success']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <?php unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= $_SESSION['error']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <?php unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <div class="table__wrapper table-responsive">
                    <table class="table mb-20 multiple_tables">
                        <thead>
                            <tr class="table__title table__sort">
                                <th>ID</th>
                                <th>Date</th>
                                <th>Role</th>
                                <th>Person Name</th>
                                <th>Source</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Method</th>
                                <th>Payment Details</th>
                                <th>Created By</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody class="table__body">
                            <?php if (!empty($transactions)): ?>
                                <?php foreach ($transactions as $transaction): ?>
                                    <tr>
                                        <td>#<?= $transaction['id'] ?></td>
                                        <td><?= $transaction['transaction_date'] ? date('d M, Y', strtotime($transaction['transaction_date'])) : '' ?></td>
                                        <td>
                                            <span class="bd-badge bg-primary"><?= htmlspecialchars($transaction['role_name'] ?? 'N/A') ?></span>
                                        </td>
                                        <td><strong><?= htmlspecialchars($transaction['person_name'] ?? 'N/A') ?></strong></td>
                                        <td><?= htmlspecialchars($transaction['source'] ?? '') ?></td>
                                        <td>
                                            <small>
                                                <?= htmlspecialchars(substr($transaction['description'] ?? '', 0, 50)) ?>
                                                <?php if (!empty($transaction['description']) && strlen($transaction['description']) > 50): ?>
                                                    ...<br><a href="#" onclick="showFullDescription(<?= $transaction['id'] ?>); return false;">View More</a>
                                                <?php endif; ?>
                                            </small>
                                        </td>
                                        <td><strong>৳<?= number_format($transaction['amount'], 2) ?></strong></td>
                                        <td>
                                            <?php if ($transaction['payment_status'] == 'paid'): ?>
                                                <span class="bd-badge bg-success">Paid</span>
                                            <?php elseif ($transaction['payment_status'] == 'pending'): ?>
                                                <span class="bd-badge bg-warning">Pending</span>
                                            <?php else: ?>
                                                <span class="bd-badge bg-danger">Failed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="bd-badge bg-light text-dark"><?= ucfirst($transaction['payment_method']) ?></span></td>
                                        <td>
                                            <small>
                                                <?php if ($transaction['payment_method'] === 'bank'): ?>
                                                    <?= $transaction['bank_name'] ?? '' ?>
                                                    <?= $transaction['bank_account_no'] ? ' (' . $transaction['bank_account_no'] . ')' : '' ?>
                                                <?php elseif ($transaction['payment_method'] === 'cheque'): ?>
                                                    Cheque No: <?= $transaction['cheque_no'] ?? 'N/A' ?>
                                                    <?= $transaction['bank_name'] ? ' - ' . $transaction['bank_name'] : '' ?>
                                                <?php elseif ($transaction['payment_method'] === 'online'): ?>
                                                    <?= $transaction['online_type'] ?? 'Online' ?>
                                                <?php else: ?>
                                                    Cash Payment
                                                <?php endif; ?>
                                                <?= $transaction['transaction_ref'] ? '<br>Ref: ' . $transaction['transaction_ref'] : '' ?>
                                            </small>
                                        </td>
                                        <td><?= htmlspecialchars($transaction['created_by_name'] ?? 'N/A') ?></td>
                                        <td>
                                            <small>
                                                <?= $transaction['created_at'] ? date('d M, Y', strtotime($transaction['created_at'])) : '' ?>
                                                <br>
                                                <?= $transaction['created_at'] ? date('h:i A', strtotime($transaction['created_at'])) : '' ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center justify-content-start gap-10">
                                                <!-- INVOICE BUTTON -->
                                                <?php if (($transaction['payment_status'] ?? 'paid') === 'paid'): ?>
                                                    <a href="<?= $GLOBALS['base_url'] ?>/management-cash-in-invoice?transaction_id=<?= $transaction['id'] ?>"
                                                        class="table__icon invoice"
                                                        style="color: #fff; background-color: #6c5ffc;"
                                                        title="View Invoice"
                                                        target="_blank">
                                                        <i class="fa-light fa-file-invoice"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="table__icon invoice"
                                                        style="color: #999; background-color: #e9ecef; cursor: not-allowed;"
                                                        title="Invoice not available for <?= htmlspecialchars($transaction['payment_status'] ?? 'pending') ?> transactions">
                                                        <i class="fa-light fa-file-invoice"></i>
                                                    </span>
                                                <?php endif; ?>
                                                <!-- EDIT BUTTON -->
                                                <a href="<?= $GLOBALS['base_url'] ?>/edit-management-cash-in?id=<?= $transaction['id'] ?>" class="table__icon edit" title="Edit">
                                                    <i class="fa-sharp fa-light fa-pen"></i>
                                                </a>
                                                <!-- DELETE BUTTON -->
                                                <a href="#" class="removeBtn table__icon delete" onclick="deleteTransaction(<?= $transaction['id'] ?>); return false;" title="Delete">
                                                    <i class="fa-regular fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="13" class="text-center py-4">
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
</div>

<!-- Modal for full description -->
<div class="modal fade" id="descriptionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Transaction Description</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body" id="descriptionContent">
                Loading...
            </div>
        </div>
    </div>
</div>

<script>
    function deleteTransaction(transactionId) {
        if (confirm('Are you sure you want to delete this transaction?')) {
            window.location.href = '<?= $GLOBALS['base_url'] ?>/delete-management-cash-in?id=' + transactionId;
        }
    }

    function showFullDescription(transactionId) {
        fetch('<?= $GLOBALS['base_url'] ?>/get-transaction-details?id=' + transactionId)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    document.getElementById('descriptionContent').innerHTML = data.error;
                } else {
                    document.getElementById('descriptionContent').innerHTML =
                        data.description ? data.description.replace(/\n/g, '<br>') : 'No description provided.';
                }
                new bootstrap.Modal(document.getElementById('descriptionModal')).show();
            })
            .catch(error => {
                document.getElementById('descriptionContent').innerHTML = 'Error loading description.';
                new bootstrap.Modal(document.getElementById('descriptionModal')).show();
            });
    }
</script>
<?php
$page_title = "Management Cash Out";
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

    @media(max-width:768px) {
        .action-transaction-buttons {
            margin-top: 10px;
        }
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
                    <span><i class="fa-light fa-money-bill-transfer"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title  mb-10">Total Amount</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title fs-6">TK.<?= number_format($statistics['total_amount'] ?? 0, 2) ?></h4>
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
                        <h4 class="card__title fs-6">TK.<?= number_format($statistics['paid_amount'] ?? 0, 2) ?></h4>
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
                        <h4 class="card__title fs-6">TK.<?= number_format($statistics['pending_amount'] ?? 0, 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction Table -->
    <div class="col-xxl-12 mb-4">
        <div class="card__wrapper">
            <div class="card__header mb-3">
                <h5 class="card__title" style="margin-top: -13px;">Cash Out Transactions</h5>
                <div class="action-transaction-buttons gap-2">
                    <!-- In the action buttons section, add this after "Manage Categories" button -->
                    <a href="<?= $GLOBALS['base_url'] ?>/management-cash-out-statement" target="_blank" class="btn btn-primary">
                        <i class="fa-light fa-file-invoice me-2"></i> Get Statement
                    </a>
                    <a href="<?= $GLOBALS['base_url'] ?>/management-categories" class="btn btn-info">
                        <i class="fa-light fa-layer-group me-2"></i> Manage Categories
                    </a>

                    <a href="<?= $GLOBALS['base_url'] ?>/add-management-cash-out" class="btn btn-success">
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
                                <th>Category</th>
                                <th>Paid To</th>
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
                                        <td><span class="bd-badge bg-primary"><?= htmlspecialchars($transaction['category_name'] ?? 'N/A') ?></span></td>
                                        <td><strong><?= htmlspecialchars($transaction['paid_to'] ?? '') ?></strong></td>
                                        <td>
                                            <small>
                                                <?= htmlspecialchars($transaction['description'] ?? '') ?>
                                                <?php if (!empty($transaction['description']) && strlen($transaction['description']) > 50): ?>
                                                    <br><a href="#" onclick="showFullDescription(<?= $transaction['id'] ?>)">View More</a>
                                                <?php endif; ?>
                                            </small>
                                        </td>
                                        <td><strong>TK.<?= number_format($transaction['amount'], 2) ?></strong></td>
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
                                                <a href="<?= $GLOBALS['base_url'] ?>/management-cash-out-invoice?id=<?= $transaction['id'] ?>"
                                                    class="table__icon view"
                                                    title="View Invoice"
                                                    target="_blank">
                                                    <i class="fa-light fa-file-invoice" style="background:#6c5ffc; color: white; padding:7px 9px; border-radius: 5px;"></i>
                                                </a>
                                                <a href="<?= $GLOBALS['base_url'] ?>/edit-management-cash-out?id=<?= $transaction['id'] ?>" class="table__icon edit" title="Edit">
                                                    <i class="fa-sharp fa-light fa-pen"></i>
                                                </a>
                                                <a href="#" class="removeBtn table__icon delete" onclick="deleteTransaction(<?= $transaction['id'] ?>)" title="Delete">
                                                    <i class="fa-regular fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="12" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fa-light fa-receipt fa-2x mb-3"></i><br>
                                            No cash out transactions found
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

<!-- =====================================================================
     FUND REQUISITIONS PANEL (for Accounts)
     ===================================================================== -->
<div class="container-fluid mt-4">
    <div class="card custom-card">
        <div class="card-header justify-content-between align-items-center">
            <div class="card-title fs-14 fw-bold">
                <i class="fa-solid fa-file-invoice-dollar me-2 text-primary"></i>Fund Requisition Requests
            </div>
            <a href="<?= $GLOBALS['base_url'] ?>/fund-requisitions" class="btn btn-sm btn-light border fs-11">
                View All <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table text-nowrap table-hover mb-0 fs-12">
                    <thead class="table-light">
                        <tr>
                            <th>REQ ID</th>
                            <th>Date</th>
                            <th>Requester</th>
                            <th>Dept / Project</th>
                            <th>Type</th>
                            <th class="text-end">Amount</th>
                            <th>Purpose</th>
                            <th>Status</th>
                            <th>Settlement</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($fundRequisitions)): ?>
                        <tr><td colspan="10" class="text-center text-muted py-4">No fund requisitions found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($fundRequisitions as $fr):
                            $frStatusColors = ['pending'=>'bg-warning text-dark','approved'=>'bg-info','paid'=>'bg-success','rejected'=>'bg-danger'];
                            $frSClass = $frStatusColors[$fr['status']] ?? 'bg-secondary';
                            $settleMap = ['pending'=>'bg-secondary','settled'=>'bg-success','extra_requested'=>'bg-danger'];
                            $settleSt  = $settleMap[$fr['settlement_status'] ?? 'pending'] ?? 'bg-secondary';
                        ?>
                        <tr>
                            <td class="fw-semibold">REQ-<?= $fr['id'] ?></td>
                            <td><?= date('d M Y', strtotime($fr['created_at'])) ?></td>
                            <td><?= htmlspecialchars($fr['requester_name'] ?? '---') ?></td>
                            <td>
                                <div><?= htmlspecialchars($fr['department_name'] ?? '---') ?></div>
                                <small class="text-muted"><?= $fr['project_name'] ? htmlspecialchars($fr['project_name']) : 'Office' ?></small>
                            </td>
                            <td>
                                <?php if ($fr['requisition_type'] === 'petty_cash'): ?>
                                    <span class="badge bg-warning fs-10">Petty Cash</span>
                                <?php else: ?>
                                    <span class="badge bg-info fs-10">General</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end fw-bold">&#2547; <?= number_format($fr['amount'], 2) ?></td>
                            <td class="text-wrap" style="max-width:160px"><?= htmlspecialchars(mb_substr($fr['purpose'], 0, 60)) ?>...</td>
                            <td><span class="badge <?= $frSClass ?> fs-10 text-capitalize"><?= htmlspecialchars($fr['status']) ?></span></td>
                            <td>
                                <?php if ($fr['status'] === 'paid'): ?>
                                    <span class="badge <?= $settleSt ?> fs-10 text-capitalize"><?= $fr['settlement_status'] ?? 'pending' ?></span>
                                <?php else: echo '---'; endif; ?>
                            </td>
                            <td>
                                <a href="<?= $GLOBALS['base_url'] ?>/fund-requisitions/view/<?= $fr['id'] ?>"
                                   class="btn btn-icon btn-sm btn-info border" title="View Details">
                                    <i class="fa-solid fa-eye fs-11"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
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
        if (confirm('Are you sure you want to delete this cash out transaction?')) {
            window.location.href = '<?= $GLOBALS['base_url'] ?>/delete-management-cash-out?id=' + transactionId;
        }
    }

    function showFullDescription(transactionId) {
        fetch('<?= $GLOBALS['base_url'] ?>/get-cashout-transaction-details?id=' + transactionId)
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
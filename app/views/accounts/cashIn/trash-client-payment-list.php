<?php
$roleId = (int)($_SESSION['user']['role_id'] ?? 0);
$roleName = strtolower($_SESSION['user']['role'] ?? '');
$isManagementUser = !empty($isManagementUser) || ($roleId === 1 || $roleId === 3 || strpos($roleName, 'management') !== false || strpos($roleName, 'admin') !== false);
?>
<style>
.table th, .table td { vertical-align: middle; }
</style>
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0">Trash Client Payment</h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Manage Cash In</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Trash Client Payment</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                <div class="card-title">Trashed Transactions</div>
                <?php if (!$isManagementUser): ?>
                    <span class="badge bg-secondary-subtle text-secondary border fs-12 fw-normal"><i class="fa-solid fa-shield-halved me-1"></i>Permanent deletion restricted to Management</span>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped text-nowrap mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Sl</th>
                                <th>Client Name</th>
                                <th>Project & Flat</th>
                                <th>Payment Type</th>
                                <th>Amount</th>
                                <th>Trashed By</th>
                                <th>Trashed At</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($trashedTransactions)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No trashed transactions found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($trashedTransactions as $index => $transaction): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars($transaction['client_name']) ?></div>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($transaction['project_name']) ?><br>
                                            <small class="text-muted"><?= htmlspecialchars($transaction['unit_name']) ?></small>
                                        </td>
                                        <td>
                                            <?php
                                            $type = str_replace('_', ' ', $transaction['payment_type']);
                                            echo ucwords($type);
                                            ?>
                                        </td>
                                        <td class="fw-bold">
                                            <?= number_format($transaction['amount'], 2) ?>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark"><i class="fa-solid fa-user-xmark text-danger me-1"></i><?= htmlspecialchars($transaction['trashed_by_name'] ?? 'System') ?></div>
                                            <?php if (!empty($transaction['trashed_by_role'])): ?>
                                                <span class="badge bg-secondary-subtle text-secondary border py-0 px-1" style="font-size:10px;"><?= htmlspecialchars(trim($transaction['trashed_by_role'])) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-danger"><i class="fa-regular fa-clock me-1"></i><?= !empty($transaction['trashed_at']) ? date('d M, Y', strtotime($transaction['trashed_at'])) : '—' ?></div>
                                            <small class="text-muted"><?= !empty($transaction['trashed_at']) ? date('h:i A', strtotime($transaction['trashed_at'])) : '' ?></small>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-success" onclick="restoreTransaction(<?= $transaction['id'] ?>, <?= $transaction['booking_id'] ?>)" title="Restore">
                                                <i class="fa-solid fa-rotate-left"></i> Restore
                                            </button>
                                            <?php if ($isManagementUser): ?>
                                                <button type="button" class="btn btn-sm btn-danger ms-1" onclick="deleteTransactionPermanently(<?= $transaction['id'] ?>, <?= $transaction['booking_id'] ?>)" title="Delete Permanently (Management Only)">
                                                    <i class="fa-solid fa-trash"></i> Delete
                                                </button>
                                            <?php endif; ?>
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
</div>

<script>
function restoreTransaction(paymentId, bookingId) {
    Swal.fire({
        title: 'Restore Transaction?',
        text: "This transaction will be moved back to the client's history.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Restore'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `<?= $GLOBALS['base_url'] ?>/restore-client-payment?payment_id=${paymentId}&booking_id=${bookingId}`;
        }
    });
}

function deleteTransactionPermanently(paymentId, bookingId) {
    Swal.fire({
        title: 'Permanently Delete?',
        text: "This action cannot be undone. Are you sure you want to permanently delete this transaction?",
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Delete Permanently!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `<?= $GLOBALS['base_url'] ?>/delete-transaction?payment_id=${paymentId}&booking_id=${bookingId}&from_trash=1`;
        }
    });
}
</script>

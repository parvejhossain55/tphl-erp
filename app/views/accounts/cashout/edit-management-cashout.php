<?php
$page_title = "Edit Cash Out Transaction";
$page = 'Management';
$addPage = true;
$show_breadcrumb = true;

// Get transaction data
$transaction = $transaction ?? [];
// Get old form data if validation failed
$old_data = $_SESSION['old_form_data'] ?? $transaction;
unset($_SESSION['old_form_data']);
?>
<style>
    @media(max-width: 575px) {
        .card__title {
            font-size: 20px;
            line-height: 1.4;
        }

        .card__wrapper {
            padding: 31px 15px;
        }

        .mt-30 {
            margin-top: 10px;
        }

        .btn {

            padding: 6px 12px;
        }
    }
</style>
<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="card__header d-flex justify-content-between align-items-center mb-3">
                <h5 class="card__title" style="margin-top: -13px;">Edit Cash Out Transaction #<?= $transaction['id'] ?? '' ?></h5>
                <a href="<?= $GLOBALS['base_url'] ?>/management-cash-out" class="btn btn-secondary">
                    <i class="fa-light fa-arrow-left me-2"></i> Back
                </a>
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

                <form action="<?= $GLOBALS['base_url'] ?>/update-management-cash-out" method="POST">
                    <input type="hidden" name="transaction_id" value="<?= $transaction['id'] ?? '' ?>">

                    <div class="row">
                        <!-- Category Selection -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Select Category: <span class="text-danger">*</span></label>
                            <select name="category_id" id="category_id" class="form-control" required>
                                <option value="">-- Select Category --</option>
                                <?php if (!empty($categories) && is_array($categories)): ?>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= htmlspecialchars($category['id'] ?? '') ?>"
                                            <?= (!empty($old_data['category_id']) && $old_data['category_id'] == $category['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['category_name'] ?? 'Unknown') ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>No categories available</option>
                                <?php endif; ?>
                            </select>
                            <small class="text-muted">
                                Don't see the category? <a href="<?= $GLOBALS['base_url'] ?>/management-categories" target="_blank">Add a new category here</a>
                            </small>
                        </div>

                        <!-- Transaction Date -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Transaction Date: <span class="text-danger">*</span></label>
                            <input type="date" name="transaction_date" class="form-control"
                                value="<?= !empty($old_data['transaction_date']) ? htmlspecialchars($old_data['transaction_date']) : date('Y-m-d') ?>" required>
                        </div>

                        <!-- Paid To -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Paid To: <span class="text-danger">*</span></label>
                            <input type="text" name="paid_to" class="form-control"
                                placeholder="e.g., Contractor, Supplier, Employee, etc."
                                value="<?= !empty($old_data['paid_to']) ? htmlspecialchars($old_data['paid_to']) : '' ?>" required>
                        </div>

                        <!-- Amount -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Amount (৳): <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control"
                                placeholder="0.00" step="0.01" min="0.01"
                                value="<?= !empty($old_data['amount']) ? htmlspecialchars($old_data['amount']) : '' ?>" required>
                        </div>

                        <!-- Description -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Description:</label>
                            <textarea name="description" class="form-control" rows="3"
                                placeholder="Enter transaction description..."><?= !empty($old_data['description']) ? htmlspecialchars($old_data['description']) : '' ?></textarea>
                        </div>

                        <!-- Payment Method -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Method: <span class="text-danger">*</span></label>
                            <select name="payment_method" id="payment_method" class="form-control" required onchange="togglePaymentFields()">
                                <option value="cash" <?= (!empty($old_data['payment_method']) && $old_data['payment_method'] == 'cash') ? 'selected' : '' ?>>Cash</option>
                                <option value="bank" <?= (!empty($old_data['payment_method']) && $old_data['payment_method'] == 'bank') ? 'selected' : '' ?>>Bank Transfer</option>
                                <option value="cheque" <?= (!empty($old_data['payment_method']) && $old_data['payment_method'] == 'cheque') ? 'selected' : '' ?>>Cheque</option>
                                <option value="online" <?= (!empty($old_data['payment_method']) && $old_data['payment_method'] == 'online') ? 'selected' : '' ?>>Online Payment</option>
                            </select>
                        </div>

                        <!-- Payment Status -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Status: <span class="text-danger">*</span></label>
                            <select name="payment_status" class="form-control" required>
                                <option value="paid" <?= (!empty($old_data['payment_status']) && $old_data['payment_status'] == 'paid') ? 'selected' : '' ?>>Paid</option>
                                <option value="pending" <?= (!empty($old_data['payment_status']) && $old_data['payment_status'] == 'pending') ? 'selected' : '' ?>>Pending</option>
                                <option value="failed" <?= (!empty($old_data['payment_status']) && $old_data['payment_status'] == 'failed') ? 'selected' : '' ?>>Failed</option>
                            </select>
                        </div>

                        <!-- Bank Fields -->
                        <div id="bank_fields" style="display: none;">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Bank Name:</label>
                                    <input type="text" name="bank_name" class="form-control"
                                        placeholder="e.g., DBBL, Brac Bank, City Bank, etc."
                                        value="<?= !empty($old_data['bank_name']) ? htmlspecialchars($old_data['bank_name']) : '' ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Bank Account No:</label>
                                    <input type="text" name="bank_account_no" class="form-control"
                                        placeholder="Enter account number"
                                        value="<?= !empty($old_data['bank_account_no']) ? htmlspecialchars($old_data['bank_account_no']) : '' ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Cheque Fields -->
                        <div id="cheque_fields" style="display: none;">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Cheque No:</label>
                                    <input type="text" name="cheque_no" class="form-control"
                                        placeholder="Enter cheque number"
                                        value="<?= !empty($old_data['cheque_no']) ? htmlspecialchars($old_data['cheque_no']) : '' ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Online Fields -->
                        <div id="online_fields" style="display: none;">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Online Payment Type:</label>
                                    <select name="online_type" class="form-control">
                                        <option value="bKash" <?= (!empty($old_data['online_type']) && $old_data['online_type'] == 'bKash') ? 'selected' : '' ?>>bKash</option>
                                        <option value="Nagad" <?= (!empty($old_data['online_type']) && $old_data['online_type'] == 'Nagad') ? 'selected' : '' ?>>Nagad</option>
                                        <option value="Rocket" <?= (!empty($old_data['online_type']) && $old_data['online_type'] == 'Rocket') ? 'selected' : '' ?>>Rocket</option>
                                        <option value="Upay" <?= (!empty($old_data['online_type']) && $old_data['online_type'] == 'Upay') ? 'selected' : '' ?>>Upay</option>
                                        <option value="Bank Online" <?= (!empty($old_data['online_type']) && $old_data['online_type'] == 'Bank Online') ? 'selected' : '' ?>>Bank Online</option>
                                        <option value="Other" <?= (!empty($old_data['online_type']) && $old_data['online_type'] == 'Other') ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Transaction Reference -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Transaction Reference/Note:</label>
                            <input type="text" name="transaction_ref" class="form-control"
                                placeholder="e.g., Transaction ID, Reference No, Note, etc."
                                value="<?= !empty($old_data['transaction_ref']) ? htmlspecialchars($old_data['transaction_ref']) : '' ?>">
                        </div>

                        <!-- Submit Buttons -->
                        <div class="col-md-12 mt-4">
                            <div class="d-flex justify-content-end gap-10">
                                <button type="submit" class="btn btn-success">
                                    <i class="fa-light fa-check me-2"></i> Update Transaction
                                </button>
                                <a href="<?= $GLOBALS['base_url'] ?>/management-cash-out" class="btn btn-secondary">
                                    <i class="fa-light fa-times me-2"></i> Cancel
                                </a>

                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function togglePaymentFields() {
        var paymentMethod = document.getElementById('payment_method').value;

        document.getElementById('bank_fields').style.display = 'none';
        document.getElementById('cheque_fields').style.display = 'none';
        document.getElementById('online_fields').style.display = 'none';

        if (paymentMethod === 'bank') {
            document.getElementById('bank_fields').style.display = 'block';
        } else if (paymentMethod === 'cheque') {
            document.getElementById('bank_fields').style.display = 'block';
            document.getElementById('cheque_fields').style.display = 'block';
        } else if (paymentMethod === 'online') {
            document.getElementById('online_fields').style.display = 'block';
        }
    }

    // Initialize fields on page load
    document.addEventListener('DOMContentLoaded', function() {
        togglePaymentFields();
    });
</script>
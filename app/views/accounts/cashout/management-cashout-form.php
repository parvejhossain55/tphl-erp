<?php
$page_title = "Add Cash Out Transaction";
$page = 'Management';
$addPage = true;
$show_breadcrumb = true;

// Get old form data if validation failed
$old_data = $_SESSION['old_form_data'] ?? [];
unset($_SESSION['old_form_data']);
?>

<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="card__header d-flex justify-content-between align-items-center mb-3">
                <h5 class="card__title" style="margin-top: -13px;">Add Cash Out Transaction</h5>
                <a href="<?= $GLOBALS['base_url'] ?>/management-cash-out" class="btn btn-secondary">
                    <i class="fa-light fa-arrow-left me-2"></i> Back to List
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

                <form action="<?= $GLOBALS['base_url'] ?>/store-management-cash-out" method="POST">
                    <div class="row">
                        <!-- Role Selection -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Select Role: <span class="text-danger">*</span></label>
                            <select name="role_id" id="role_id" class="form-control" required onchange="updatePersonName()">
                                <option value="">-- Select Role --</option>
                                <?php if (!empty($roles) && is_array($roles)): ?>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= htmlspecialchars($role['id'] ?? '') ?>"
                                            data-person="<?= htmlspecialchars($role['person_name'] ?? '') ?>"
                                            <?= (!empty($old_data['role_id']) && $old_data['role_id'] == $role['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($role['role_name'] ?? 'Unknown') ?> - <?= htmlspecialchars($role['person_name'] ?? 'Unknown') ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>No roles available</option>
                                <?php endif; ?>
                            </select>
                            <small class="text-muted">
                                Don't see the role? <a href="<?= $GLOBALS['base_url'] ?>/management-roles" target="_blank">Add a new role here</a>
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
                                <option value="cash" <?= (!empty($old_data['payment_method']) && $old_data['payment_method'] == 'cash') ? 'selected' : 'selected' ?>>Cash</option>
                                <option value="bank" <?= (!empty($old_data['payment_method']) && $old_data['payment_method'] == 'bank') ? 'selected' : '' ?>>Bank Transfer</option>
                                <option value="cheque" <?= (!empty($old_data['payment_method']) && $old_data['payment_method'] == 'cheque') ? 'selected' : '' ?>>Cheque</option>
                                <option value="online" <?= (!empty($old_data['payment_method']) && $old_data['payment_method'] == 'online') ? 'selected' : '' ?>>Online Payment</option>
                            </select>
                        </div>

                        <!-- Payment Status -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Status: <span class="text-danger">*</span></label>
                            <select name="payment_status" class="form-control" required>
                                <option value="paid" <?= (!empty($old_data['payment_status']) && $old_data['payment_status'] == 'paid') ? 'selected' : 'selected' ?>>Paid</option>
                                <option value="pending" <?= (!empty($old_data['payment_status']) && $old_data['payment_status'] == 'pending') ? 'selected' : '' ?>>Pending</option>
                                <option value="failed" <?= (!empty($old_data['payment_status']) && $old_data['payment_status'] == 'failed') ? 'selected' : '' ?>>Failed</option>
                            </select>
                        </div>

                        <!-- Bank Fields (shown when bank or cheque selected) -->
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

                        <!-- Cheque Fields (shown when cheque selected) -->
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

                        <!-- Online Fields (shown when online selected) -->
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

                        <!-- Hidden fields for role_name and person_name -->
                        <input type="hidden" name="role_name" id="role_name" value="<?= !empty($old_data['role_name']) ? htmlspecialchars($old_data['role_name']) : '' ?>">
                        <input type="hidden" name="person_name" id="person_name" value="<?= !empty($old_data['person_name']) ? htmlspecialchars($old_data['person_name']) : '' ?>">

                        <!-- Created by (hidden, will be set in backend) -->
                        <input type="hidden" name="created_by" value="<?= $_SESSION['user_id'] ?? '' ?>">

                        <!-- Submit Buttons -->
                        <div class="col-md-12 mt-4">
                            <div class="d-flex justify-content-end gap-10">
                                <a href="<?= $GLOBALS['base_url'] ?>/management-cash-out" class="btn btn-secondary">
                                    <i class="fa-light fa-times me-2"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-success">
                                    <i class="fa-light fa-check me-2"></i> Add Transaction
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function updatePersonName() {
        var roleSelect = document.getElementById('role_id');
        var selectedOption = roleSelect.options[roleSelect.selectedIndex];

        if (selectedOption && selectedOption.value) {
            var roleName = selectedOption.text.split(' - ')[0];
            var personName = selectedOption.getAttribute('data-person') || '';

            // Update hidden fields
            document.getElementById('role_name').value = roleName;
            document.getElementById('person_name').value = personName;
        } else {
            document.getElementById('role_name').value = '';
            document.getElementById('person_name').value = '';
        }
    }

    function togglePaymentFields() {
        var paymentMethod = document.getElementById('payment_method').value;

        // Hide all fields first
        document.getElementById('bank_fields').style.display = 'none';
        document.getElementById('cheque_fields').style.display = 'none';
        document.getElementById('online_fields').style.display = 'none';

        // Show relevant fields based on payment method
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
        updatePersonName();
        togglePaymentFields();

        // If old data exists, ensure correct fields are shown
        <?php if (!empty($old_data['payment_method'])): ?>
            togglePaymentFields();
        <?php endif; ?>
    });
</script>
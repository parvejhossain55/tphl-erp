<?php
$page_title = "Add Management Transaction";
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
                <h5 class="card__title" style="margin-top: -13px;">Add Management Transaction</h5>
                <a href="<?= $GLOBALS['base_url'] ?>/management-cash-in" class="btn btn-secondary">
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

                <form action="<?= $GLOBALS['base_url'] ?>/store-management-cash-in" method="POST">
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

                        <!-- Source -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Source: <span class="text-danger">*</span></label>
                            <input type="text" name="source" class="form-control" 
                                   placeholder="e.g., Investment, Loan, etc." 
                                   value="<?= !empty($old_data['source']) ? htmlspecialchars($old_data['source']) : '' ?>" required>
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

                        <!-- Bank Fields (shown when bank or cheque is selected) -->
                        <div class="col-md-6 mb-3 bank-fields" style="display: none;">
                            <label class="form-label">Bank Name: <span class="text-danger bank-required">*</span></label>
                            <input type="text" name="bank_name" id="bank_name" class="form-control" 
                                   placeholder="e.g., Dutch Bangla Bank" 
                                   value="<?= !empty($old_data['bank_name']) ? htmlspecialchars($old_data['bank_name']) : '' ?>">
                        </div>

                        <div class="col-md-6 mb-3 bank-fields" style="display: none;">
                            <label class="form-label">Bank Account No:</label>
                            <input type="text" name="bank_account_no" class="form-control" 
                                   placeholder="Account Number" 
                                   value="<?= !empty($old_data['bank_account_no']) ? htmlspecialchars($old_data['bank_account_no']) : '' ?>">
                        </div>

                        <!-- Cheque Fields (shown when cheque is selected) -->
                        <div class="col-md-6 mb-3 cheque-fields" style="display: none;">
                            <label class="form-label">Cheque Number: <span class="text-danger cheque-required">*</span></label>
                            <input type="text" name="cheque_no" id="cheque_no" class="form-control" 
                                   placeholder="Cheque Number" 
                                   value="<?= !empty($old_data['cheque_no']) ? htmlspecialchars($old_data['cheque_no']) : '' ?>">
                        </div>

                        <!-- Online Payment Fields (shown when online is selected) -->
                        <div class="col-md-6 mb-3 online-fields" style="display: none;">
                            <label class="form-label">Online Payment Type:</label>
                            <select name="online_type" class="form-control">
                                <option value="">-- Select Type --</option>
                                <option value="bKash" <?= (!empty($old_data['online_type']) && $old_data['online_type'] == 'bKash') ? 'selected' : '' ?>>bKash</option>
                                <option value="Nagad" <?= (!empty($old_data['online_type']) && $old_data['online_type'] == 'Nagad') ? 'selected' : '' ?>>Nagad</option>
                                <option value="Rocket" <?= (!empty($old_data['online_type']) && $old_data['online_type'] == 'Rocket') ? 'selected' : '' ?>>Rocket</option>
                                <option value="Upay" <?= (!empty($old_data['online_type']) && $old_data['online_type'] == 'Upay') ? 'selected' : '' ?>>Upay</option>
                                <option value="Card" <?= (!empty($old_data['online_type']) && $old_data['online_type'] == 'Card') ? 'selected' : '' ?>>Credit/Debit Card</option>
                                <option value="Other" <?= (!empty($old_data['online_type']) && $old_data['online_type'] == 'Other') ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>

                        <!-- Transaction Reference -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Transaction Reference:</label>
                            <input type="text" name="transaction_ref" class="form-control" 
                                   placeholder="Reference number or ID" 
                                   value="<?= !empty($old_data['transaction_ref']) ? htmlspecialchars($old_data['transaction_ref']) : '' ?>">
                        </div>

                        <!-- Submit Buttons -->
                        <div class="col-md-12 mt-3">
                            <button type="submit" class="btn btn-success me-2">
                                <i class="fa-light fa-check me-2"></i> Save Transaction
                            </button>
                            <a href="<?= $GLOBALS['base_url'] ?>/management-cash-in" class="btn btn-secondary">
                                <i class="fa-light fa-times me-2"></i> Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle payment method fields
function togglePaymentFields() {
    const paymentMethod = document.getElementById('payment_method').value;
    
    // Hide all conditional fields
    document.querySelectorAll('.bank-fields, .cheque-fields, .online-fields').forEach(field => {
        field.style.display = 'none';
    });
    
    // Remove required attribute from all conditional fields
    const bankNameField = document.getElementById('bank_name');
    const chequeNoField = document.getElementById('cheque_no');
    
    if (bankNameField) bankNameField.removeAttribute('required');
    if (chequeNoField) chequeNoField.removeAttribute('required');
    
    // Show relevant fields based on payment method
    if (paymentMethod === 'bank') {
        document.querySelectorAll('.bank-fields').forEach(field => {
            field.style.display = 'block';
        });
        if (bankNameField) bankNameField.setAttribute('required', 'required');
    } else if (paymentMethod === 'cheque') {
        document.querySelectorAll('.bank-fields, .cheque-fields').forEach(field => {
            field.style.display = 'block';
        });
        if (bankNameField) bankNameField.setAttribute('required', 'required');
        if (chequeNoField) chequeNoField.setAttribute('required', 'required');
    } else if (paymentMethod === 'online') {
        document.querySelectorAll('.online-fields').forEach(field => {
            field.style.display = 'block';
        });
    }
}

// Update person name when role is selected
function updatePersonName() {
    const roleSelect = document.getElementById('role_id');
    const selectedOption = roleSelect.options[roleSelect.selectedIndex];
    const personName = selectedOption.getAttribute('data-person');
    
    // You can display the person name if needed
    console.log('Selected person:', personName);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Show fields based on current payment method selection
    togglePaymentFields();
});
</script>
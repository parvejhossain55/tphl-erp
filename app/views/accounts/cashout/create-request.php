<?php
$page_title = "Request Cashout";
$page = 'Cashout';
$show_breadcrumb = true;
?>

<style>
    .card-body {
    background: var(--clr-bg-card);
    padding: 25px 25px;
    margin-bottom: 0;
    position: relative;
    border-radius: 0;
    box-shadow: 0 6px 30px rgba(182, 186, 203, 0.3);
}
</style>
<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="card__header d-flex justify-content-between align-items-center mb-3">
                <h5 class="card__title">Request Cashout</h5>
                <a href="<?= $GLOBALS['base_url'] ?>/cashout-requests" class="btn btn-secondary">
                    <i class="fa-light fa-arrow-left me-2"></i> Back
                </a>
            </div>
            <div class="card__body">
                <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= $_SESSION['error']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <?php unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <form action="<?= $GLOBALS['base_url'] ?>/store-cashout-request" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <!-- Purpose / Paid To -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Purpose / Paid To: <span class="text-danger">*</span></label>
                            <input type="text" name="purpose_paid_to" class="form-control" 
                                placeholder="e.g., Vendor payment, Office supplies, Travel expenses, etc."
                                value="<?= htmlspecialchars($_POST['purpose_paid_to'] ?? '') ?>"
                                required>
                        </div>

                        <!-- Amount -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Amount (৳): <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control" 
                                placeholder="0.00" step="0.01" min="0.01"
                                value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>"
                                required>
                        </div>

                        <!-- Project Selection (Optional) -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Project (Optional)</label>
                            <select class="form-control" id="project_id" name="project_id">
                                <option value="">Select Project (Optional)</option>
                                <?php if (!empty($projects)): ?>
                                    <?php foreach ($projects as $project): ?>
                                        <option value="<?= $project['id'] ?>" 
                                            <?= (isset($_POST['project_id']) && $_POST['project_id'] == $project['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($project['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">No projects available</option>
                                <?php endif; ?>
                            </select>
                            <small class="text-muted">Select a project if this cashout is project-related</small>
                        </div>
                        
                        <!-- Office Selection (Optional) -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Office (Optional)</label>
                            <select class="form-control" id="office" name="office">
                                <?php if (!empty($officeOptions)): ?>
                                    <?php foreach ($officeOptions as $value => $label): ?>
                                        <option value="<?= $value ?>" 
                                            <?= (isset($_POST['office']) && $_POST['office'] == $value) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">No office options available</option>
                                <?php endif; ?>
                            </select>
                            <small class="text-muted">Select an office if this cashout is office-related</small>
                        </div>

                        <!-- Description -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Description:</label>
                            <textarea name="description" class="form-control" rows="3" 
                                placeholder="Detailed explanation of the cashout request..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        </div>

                        <!-- Payment Method -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Preferred Payment Method:</label>
                            <select name="payment_method" class="form-control">
                                <option value="cash" <?= (isset($_POST['payment_method']) && $_POST['payment_method'] == 'cash') ? 'selected' : '' ?>>Cash</option>
                                <option value="bank" <?= (isset($_POST['payment_method']) && $_POST['payment_method'] == 'bank') ? 'selected' : '' ?>>Bank Transfer</option>
                                <option value="cheque" <?= (isset($_POST['payment_method']) && $_POST['payment_method'] == 'cheque') ? 'selected' : '' ?>>Cheque</option>
                                <option value="online" <?= (isset($_POST['payment_method']) && $_POST['payment_method'] == 'online') ? 'selected' : '' ?>>Online Payment</option>
                                <option value="other" <?= (isset($_POST['payment_method']) && $_POST['payment_method'] == 'other') ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>

                        <!-- Transaction Reference -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Transaction Reference (Optional):</label>
                            <input type="text" name="transaction_ref" class="form-control" 
                                placeholder="e.g., Invoice #, PO #, etc."
                                value="<?= htmlspecialchars($_POST['transaction_ref'] ?? '') ?>">
                        </div>

                        <!-- Attachment -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Attachment (Optional):</label>
                            <input type="file" name="attachment" class="form-control" 
                                accept=".doc,.docx,.pdf,.jpg,.jpeg,.png">
                            <small class="text-muted">Max file size: 5MB. Allowed: DOC, DOCX, PDF, JPG, PNG</small>
                        </div>

                        <!-- Additional Information -->
                        <div class="col-md-12 mb-3">
                            <div class="card bg-light">
                                <div class="card-body p-3">
                                    <small class="text-muted">
                                        <i class="fa-light fa-info-circle me-1"></i> 
                                        Note: All cashout requests require management approval. 
                                        The request will be reviewed before processing.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="col-md-12 mt-4">
                            <div class="d-flex justify-content-end gap-10">
                                <button type="submit" class="btn btn-success">
                                    <i class="fa-light fa-paper-plane me-2"></i> Submit Request
                                </button>
                                <a href="<?= $GLOBALS['base_url'] ?>/cashout-requests" class="btn btn-secondary">
                                    Cancel
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
// Optional: Add dynamic behavior if needed
document.addEventListener('DOMContentLoaded', function() {
    // Example: Show/hide additional fields based on payment method
    const paymentMethodSelect = document.querySelector('select[name="payment_method"]');
    const transactionRefInput = document.querySelector('input[name="transaction_ref"]');
    
    if (paymentMethodSelect && transactionRefInput) {
        paymentMethodSelect.addEventListener('change', function() {
            const selectedMethod = this.value;
            
            // Update placeholder based on payment method
            switch(selectedMethod) {
                case 'bank':
                    transactionRefInput.placeholder = 'e.g., Bank Transaction ID, Account No.';
                    break;
                case 'cheque':
                    transactionRefInput.placeholder = 'e.g., Cheque No.';
                    break;
                case 'online':
                    transactionRefInput.placeholder = 'e.g., Payment Gateway Reference, Transaction ID';
                    break;
                default:
                    transactionRefInput.placeholder = 'e.g., Invoice #, PO #, etc.';
            }
        });
        
        // Trigger change event on page load
        paymentMethodSelect.dispatchEvent(new Event('change'));
    }
});
</script>
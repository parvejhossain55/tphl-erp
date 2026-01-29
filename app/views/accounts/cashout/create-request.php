<?php
$page_title = "Request Cashout";
$page = 'Cashout';
$show_breadcrumb = true;
?>
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
                                required>
                        </div>

                        <!-- Amount -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Amount (৳): <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control" 
                                placeholder="0.00" step="0.01" min="0.01"
                                required>
                        </div>

                        <!-- Description -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Description:</label>
                            <textarea name="description" class="form-control" rows="3" 
                                placeholder="Detailed explanation of the cashout request..."></textarea>
                        </div>

                        <!-- Payment Method -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Preferred Payment Method:</label>
                            <select name="payment_method" class="form-control">
                                <option value="cash">Cash</option>
                                <option value="bank">Bank Transfer</option>
                                <option value="cheque">Cheque</option>
                                <option value="online">Online Payment</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <!-- Transaction Reference -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Transaction Reference (Optional):</label>
                            <input type="text" name="transaction_ref" class="form-control" 
                                placeholder="e.g., Invoice #, PO #, etc.">
                        </div>

                        <!-- Attachment -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Attachment (Optional):</label>
                            <input type="file" name="attachment" class="form-control" 
                                accept=".doc,.docx,.pdf,.jpg,.jpeg,.png">
                            <small class="text-muted">Max file size: 5MB. Allowed: DOC, DOCX, PDF, JPG, PNG</small>
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
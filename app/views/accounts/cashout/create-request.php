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
            <div class="px-4 pt-3">
                <?php include __DIR__ . '/../../layouts/management_approval_notice.php'; ?>
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
                            <label class="form-label">Amount (TK.): <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control" 
                                placeholder="0.00" step="0.01" min="0.01"
                                value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>"
                                required>
                        </div>

                        <!-- Project Selection (Optional) -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="project_id">Project(s) (Optional)</label>
                            <select class="form-control select2" data-placeholder="Select Project(s)" id="project_id" name="project_id[]" multiple>
                                <?php if (!empty($projects)): ?>
                                    <?php foreach ($projects as $project): ?>
                                        <option value="<?= $project['id'] ?>" 
                                            <?= (!empty($_POST['project_id']) && in_array($project['id'], (array)$_POST['project_id'])) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($project['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">No projects available</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <!-- Office Selection (Optional) -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="office">Office(s) (Optional)</label>
                            <select class="form-control select2" data-placeholder="Select Office(s)" id="office" name="office[]" multiple>
                                <?php if (!empty($officeOptions)): ?>
                                    <?php foreach ($officeOptions as $value => $label): ?>
                                        <?php if ($value !== ''): ?>
                                        <option value="<?= htmlspecialchars($value) ?>" 
                                            <?= (!empty($_POST['office']) && in_array($value, (array)$_POST['office'])) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($label) ?>
                                        </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">No office options available</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <!-- Account Expense Selection -->
                        <div class="col-md-12 mb-3">
                            <?= renderCOADropdown($coaList ?? [], null, 'account_id', false, 'cashout') ?>
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
                            <select name="payment_method" id="payment_method" class="form-control">
                                <option value="cash" <?= (isset($_POST['payment_method']) && $_POST['payment_method'] == 'cash') ? 'selected' : '' ?>>Cash</option>
                                <option value="bank" <?= (isset($_POST['payment_method']) && $_POST['payment_method'] == 'bank') ? 'selected' : '' ?>>Bank Transfer</option>
                                <option value="cheque" <?= (isset($_POST['payment_method']) && $_POST['payment_method'] == 'cheque') ? 'selected' : '' ?>>Cheque</option>
                                <option value="online" <?= (isset($_POST['payment_method']) && $_POST['payment_method'] == 'online') ? 'selected' : '' ?>>Online Payment</option>
                                <option value="other" <?= (isset($_POST['payment_method']) && $_POST['payment_method'] == 'other') ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>

                        <!-- Bank Selection (shown only for Bank Transfer) -->
                        <div class="col-md-6 mb-3" id="bank_field" style="display:none;">
                            <label class="form-label">Select Bank: <span class="text-danger">*</span></label>
                            <select name="bank_id" id="bank_id" class="form-control" data-placeholder="Select Bank">
                                <option value="">-- Select Bank --</option>
                                <?php if (!empty($banks)): ?>
                                    <?php foreach ($banks as $bank): ?>
                                        <option value="<?= $bank['id'] ?>"
                                            <?= (isset($_POST['bank_id']) && $_POST['bank_id'] == $bank['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($bank['bank_name'] . ' - ' . $bank['branch_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>No banks available</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- Transaction Reference -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Transaction Reference (Optional):</label>
                            <input type="text" name="transaction_ref" class="form-control" 
                                placeholder="e.g., Invoice #, PO #, etc."
                                value="<?= htmlspecialchars($_POST['transaction_ref'] ?? '') ?>">
                        </div>

                               <div class="col-md-12 mb-3">
                                   <label class="form-label">Attachments (Optional):</label>
                                   <input type="file" name="attachments[]" id="attachmentInput" class="form-control"
                                    accept=".doc,.docx,.pdf,.jpg,.jpeg,.png" multiple>
                                   <small class="text-muted">Max 5MB per file. Allowed: DOC, DOCX, PDF, JPG, PNG. You can select multiple files.</small>

                                   <!-- File preview list -->
                                   <ul id="filePreviewList" class="list-group mt-2" style="display:none;"></ul>
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
document.addEventListener('DOMContentLoaded', function () {

    // ── Bank field toggle ──────────────────────────────────────────────────────
    // Uses pure JS for show/hide (jQuery not yet loaded at DOMContentLoaded time).
    // select2 is lazily initialized later via window load event (jQuery guaranteed).
    var paymentMethodSel = document.getElementById('payment_method');
    var bankField        = document.getElementById('bank_field');
    var bankSelectEl     = document.getElementById('bank_id');

    window.__bankSelect2Ready = false;

    function toggleBankField() {
        if (!paymentMethodSel || !bankField) return;
        var isBank = paymentMethodSel.value === 'bank';

        if (isBank) {
            bankField.style.display = '';
            bankSelectEl.required   = true;
        } else {
            bankField.style.display = 'none';
            bankSelectEl.required   = false;
            // Reset: use select2 API if already inited, else native
            if (window.__bankSelect2Ready && typeof $ !== 'undefined') {
                $(bankSelectEl).val(null).trigger('change');
            } else {
                bankSelectEl.value = '';
            }
        }
    }

    if (paymentMethodSel) {
        paymentMethodSel.addEventListener('change', toggleBankField);
        toggleBankField(); // restore POST state on load
    }
    // ──────────────────────────────────────────────────────────────────────────

    const paymentMethodSelect = document.querySelector('select[name="payment_method"]');
    const transactionRefInput = document.querySelector('input[name="transaction_ref"]');

    if (paymentMethodSelect && transactionRefInput) {
        paymentMethodSelect.addEventListener('change', function () {
            const placeholders = {
                bank:   'e.g., Bank Transaction ID, Account No.',
                cheque: 'e.g., Cheque No.',
                online: 'e.g., Payment Gateway Reference, Transaction ID',
            };
            transactionRefInput.placeholder = placeholders[this.value] || 'e.g., Invoice #, PO #, etc.';
        });
        paymentMethodSelect.dispatchEvent(new Event('change'));
    }

    // Multiple file preview — accumulates across selections
    const attachmentInput = document.getElementById('attachmentInput');
    const filePreviewList = document.getElementById('filePreviewList');
    const MAX_SIZE_BYTES  = 5 * 1024 * 1024;
    const ALLOWED_EXTS    = ['doc', 'docx', 'pdf', 'jpg', 'jpeg', 'png'];

    // Master file list persisted across selections
    let allFiles = new DataTransfer();

    attachmentInput.addEventListener('change', function () {
        // Merge newly selected files into allFiles, skip duplicates by name+size
        Array.from(this.files).forEach(function (newFile) {
            const isDuplicate = Array.from(allFiles.files).some(
                f => f.name === newFile.name && f.size === newFile.size
            );
            if (!isDuplicate) {
                allFiles.items.add(newFile);
            }
        });

        // Assign merged list back to input
        attachmentInput.files = allFiles.files;

        renderPreview();
    });

    function renderPreview() {
        filePreviewList.innerHTML = '';

        if (!allFiles.files.length) {
            filePreviewList.style.display = 'none';
            return;
        }

        let hasError = false;

        Array.from(allFiles.files).forEach(function (file, index) {
            const ext  = file.name.split('.').pop().toLowerCase();
            const size = (file.size / 1024 / 1024).toFixed(2);

            const icon = ['jpg','jpeg','png'].includes(ext) ? 'fa-image'
                       : ext === 'pdf'                      ? 'fa-file-pdf'
                       :                                      'fa-file-word';

            let errorMsg = '';
            if (!ALLOWED_EXTS.includes(ext)) {
                errorMsg = ' — <span class="text-danger">Invalid type</span>';
                hasError = true;
            } else if (file.size > MAX_SIZE_BYTES) {
                errorMsg = ' — <span class="text-danger">Exceeds 5MB</span>';
                hasError = true;
            }

            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center py-1 px-2';
            li.dataset.index = index;
            li.innerHTML = `
                <span>
                    <i class="fa-light ${icon} me-2 text-muted"></i>
                    <strong>${file.name}</strong>${errorMsg}
                </span>
                <span class="d-flex align-items-center gap-2">
                    <span class="badge bg-secondary">${size} MB</span>
                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1 remove-file" data-index="${index}" title="Remove">
                        <i class="fa-light fa-x m-0 p-2"></i>
                    </button>
                </span>`;
            filePreviewList.appendChild(li);
        });

        filePreviewList.style.display = 'block';

        // Remove button handler
        filePreviewList.querySelectorAll('.remove-file').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const removeIndex = parseInt(this.dataset.index);
                const updated = new DataTransfer();
                Array.from(allFiles.files).forEach(function (f, i) {
                    if (i !== removeIndex) updated.items.add(f);
                });
                allFiles = updated;
                attachmentInput.files = allFiles.files;
                renderPreview();
            });
        });

        if (hasError) {
            // Remove invalid files automatically
            const cleaned = new DataTransfer();
            Array.from(allFiles.files).forEach(function (file) {
                const ext = file.name.split('.').pop().toLowerCase();
                if (ALLOWED_EXTS.includes(ext) && file.size <= MAX_SIZE_BYTES) {
                    cleaned.items.add(file);
                }
            });
            allFiles = cleaned;
            attachmentInput.files = allFiles.files;
            renderPreview();
        }
    }
});
</script>

<script>
// This block runs after jQuery + select2 are loaded (footer scripts come after view content).
// Use window.addEventListener('load') to guarantee jQuery is fully available.
window.addEventListener('load', function () {
    if (typeof $ === 'undefined' || !$.fn.select2) return;

    var paymentMethodSel = document.getElementById('payment_method');
    var bankField        = document.getElementById('bank_field');
    var bankSelectEl     = document.getElementById('bank_id');

    // Re-init project/office select2 with correct width (they were inited by footer already,
    // but re-triggering ensures correct width after layout stabilises).
    $('#project_id, #office').each(function () {
        if (!$(this).hasClass('select2-hidden-accessible')) {
            $(this).select2({
                placeholder: $(this).data('placeholder') || 'Select option(s)',
                allowClear : true,
                width      : '100%'
            });
        }
    });

    // Lazy-init bank select2 only when field becomes visible (to avoid width=0 bug)
    function initBankSelect2IfNeeded() {
        if (!bankField || bankField.style.display === 'none') return;
        if ($(bankSelectEl).hasClass('select2-hidden-accessible')) return;

        $(bankSelectEl).select2({
            placeholder: 'Select Bank',
            allowClear : true,
            width      : '100%'
        });
        window.__bankSelect2Ready = true;
    }

    if (paymentMethodSel) {
        // If bank is already selected on page load (e.g. POST fail), init immediately
        if (paymentMethodSel.value === 'bank') {
            initBankSelect2IfNeeded();
        }

        paymentMethodSel.addEventListener('change', function () {
            if (this.value === 'bank') {
                // Small delay so display:'' has applied before select2 measures width
                setTimeout(initBankSelect2IfNeeded, 50);
            }
        });
    }
});
</script>
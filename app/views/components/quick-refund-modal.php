<?php
$bdBanksList = function_exists('getBangladeshBanks') ? getBangladeshBanks() : [];
$quickRefundRedirectUrl = $_SERVER['REQUEST_URI'] ?? '';
?>
<!-- Quick Refund Confirmation Modal -->
<div id="quickRefundModal" class="modal custom-modal fade" tabindex="-1" aria-hidden="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white py-2.5 px-3">
                <h5 class="modal-title fs-15 fw-bold text-white">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>Initiate Client Refund Request
                </h5>
                <button type="button" class="bd-btn-close btn-close-white" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large text-white"></i>
                </button>
            </div>
            <form action="<?= $GLOBALS['base_url'] ?>/booking-quick-refund" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-3">
                    <input type="hidden" name="booking_id" id="refund_booking_id">
                    <input type="hidden" name="redirect_url" id="refund_redirect_url" value="<?= htmlspecialchars($quickRefundRedirectUrl) ?>">

                    <div class="alert alert-warning d-flex align-items-start gap-2 mb-3 py-2 px-3">
                        <i class="fa-solid fa-circle-info fs-5 mt-0.5 text-warning flex-shrink-0"></i>
                        <div class="small">
                            <strong>Notice:</strong> Initiating refund will set booking status to <strong>Refunded</strong>, instantly release the flat unit as <strong>Available</strong>, and submit this refund request for Management &amp; Accounts processing.
                        </div>
                    </div>

                    <div class="bg-light p-3 rounded-2 mb-3 border">
                        <div class="row g-2 small">
                            <div class="col-sm-6"><strong>Client:</strong> <span id="refund_client_name" class="text-dark fw-semibold">—</span></div>
                            <div class="col-sm-6"><strong>Project:</strong> <span id="refund_project_name" class="text-dark fw-semibold">—</span></div>
                            <div class="col-sm-6"><strong>Flat Unit:</strong> <span id="refund_flat_name" class="text-dark fw-semibold">—</span></div>
                            <div class="col-sm-6"><strong>Agreement Value:</strong> ৳<span id="refund_agreement_val" class="font-monospace">0.00</span></div>
                        </div>
                    </div>

                    <!-- Total Verified Paid & Optional Expected Refund Date -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small mb-1">Total Verified Paid (আদায়কৃত টাকা)</label>
                            <input type="text" id="refund_total_paid_display" class="form-control form-control-sm bg-light fw-bold text-success font-monospace" readonly value="৳ 0.00">
                            <small class="text-muted d-block mt-1" id="refund_paid_helper" style="font-size: 11px;">
                                <i class="fa-light fa-circle-check text-success me-1"></i>Verified collection from client
                            </small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small mb-1">Expected Refund Date <span class="text-muted">(Optional)</span></label>
                            <input type="date" name="expected_refund_date" id="refund_expected_date" class="form-control form-control-sm" min="<?= date('Y-m-d') ?>">
                            <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                <i class="fa-light fa-calendar me-1"></i>Client's expected payout date if requested
                            </small>
                        </div>
                    </div>

                    <!-- Refund Reason -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold small mb-1">Refund Reason (রিফান্ডের কারণ) <span class="text-danger">*</span></label>
                        <textarea name="reason" id="refund_reason" class="form-control form-control-sm" rows="2" required placeholder="State why the client is requesting this refund..."></textarea>
                    </div>

                    <!-- Registration Completed (Optional) -->
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_registration_completed" id="refund_registration_completed" value="1">
                            <label class="form-check-label fw-semibold small" for="refund_registration_completed">
                                Registration Completed
                            </label>
                        </div>
                    </div>

                    <!-- Client Payout Bank & Payment Information -->
                    <div class="card custom-card border mb-3 shadow-none">
                        <div class="card-header bg-light py-2 px-3 border-bottom d-flex align-items-center">
                            <i class="fa-solid fa-building-columns text-primary me-2"></i>
                            <h6 class="card-title mb-0 fs-13 fw-semibold text-primary">Client Payout Bank &amp; Payment Details</h6>
                        </div>
                        <div class="card-body p-3 bg-white">
                            <div class="row g-2">
                                <!-- Preferred Payment Method -->
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small mb-1">Payment Method</label>
                                    <select name="preferred_payment_method" id="refund_preferred_method" class="form-select form-select-sm">
                                        <option value="bank" selected>Bank Transfer</option>
                                        <option value="cheque">Cheque</option>
                                        <option value="cash">Cash</option>
                                    </select>
                                </div>

                                <!-- Client Bank Name -->
                                <div class="col-md-8">
                                    <label class="form-label fw-semibold small mb-1">Client Bank Name</label>
                                    <select name="client_bank_name" id="refund_client_bank_name" class="form-select form-select-sm">
                                        <option value="">-- Select Client Bank --</option>
                                        <option value="Other Country / Foreign Bank">Other Country / Foreign Bank</option>
                                        <option value="Other Bank">Other Bank</option>
                                        <?php foreach ($bdBanksList as $bName): ?>
                                            <option value="<?= htmlspecialchars($bName) ?>"><?= htmlspecialchars($bName) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Bank Account Name -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">A/C Holder / Client Name</label>
                                    <input type="text" name="client_bank_account_name" id="refund_client_bank_acc_name" class="form-control form-control-sm" placeholder="Name as per bank account">
                                </div>

                                <!-- Bank Account Number -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">Bank Account Number</label>
                                    <input type="text" name="client_bank_account_no" id="refund_client_bank_acc_no" class="form-control form-control-sm" placeholder="Client bank account number">
                                </div>

                                <!-- Bank Branch Name -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">Branch Name</label>
                                    <input type="text" name="client_bank_branch" id="refund_client_bank_branch" class="form-control form-control-sm" placeholder="e.g. Gulshan Branch / Principal Branch">
                                </div>

                                <!-- Routing Number (Optional) -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">Routing Number <span class="text-muted">(Optional)</span></label>
                                    <input type="text" name="client_bank_routing_no" id="refund_client_bank_routing" class="form-control form-control-sm" placeholder="e.g. 090271234">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Client Application Attachment (PDF / Image) -->
                    <div class="mb-2">
                        <label class="form-label fw-semibold small mb-1">
                            <i class="fa-light fa-paperclip text-primary me-1"></i> Client Refund Application / Cheque Copy / Document Attachment
                            <span class="text-muted small ms-1">(PDF, JPG, JPEG, PNG — max 10MB)</span>
                        </label>
                        <input type="file" name="refund_attachment" id="refund_attachment_file" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted fs-11 d-block mt-1">
                            Upload client's written refund application letter, bank cheque scan, or ID document.
                        </small>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm fw-bold px-3" onclick="return confirm('Are you sure you want to release this flat and initiate refund request?');">
                        <i class="fa-solid fa-paper-plane me-1"></i> Submit Refund Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    if (window.__quickRefundModalInitialized) return;
    window.__quickRefundModalInitialized = true;

    function populateQuickRefundModal(btn) {
        if (!btn) return;
        const bookingId = btn.dataset.id || '';
        const client = btn.dataset.client || '—';
        const project = btn.dataset.project || '—';
        const flat = btn.dataset.flat || '—';
        const agreement = btn.dataset.agreement || '0.00';
        const datasetPaid = parseFloat(btn.dataset.paid) || 0;

        const idInput = document.getElementById('refund_booking_id');
        const clientSpan = document.getElementById('refund_client_name');
        const projectSpan = document.getElementById('refund_project_name');
        const flatSpan = document.getElementById('refund_flat_name');
        const agreementSpan = document.getElementById('refund_agreement_val');
        const reasonInput = document.getElementById('refund_reason');
        const paidInput = document.getElementById('refund_total_paid_display');
        const paidHelper = document.getElementById('refund_paid_helper');
        const expectedDateInput = document.getElementById('refund_expected_date');
        const regCompletedInput = document.getElementById('refund_registration_completed');
        const accNameInput = document.getElementById('refund_client_bank_acc_name');
        const bankNameInput = document.getElementById('refund_client_bank_name');
        const bankBranchInput = document.getElementById('refund_client_bank_branch');
        const bankAccNoInput = document.getElementById('refund_client_bank_acc_no');
        const bankRoutingInput = document.getElementById('refund_client_bank_routing');
        const attachmentInput = document.getElementById('refund_attachment_file');

        if (idInput) idInput.value = bookingId;
        if (clientSpan) clientSpan.textContent = client;
        if (projectSpan) projectSpan.textContent = project;
        if (flatSpan) flatSpan.textContent = flat;
        if (agreementSpan) agreementSpan.textContent = agreement;
        if (reasonInput) reasonInput.value = '';
        if (expectedDateInput) expectedDateInput.value = '';
        if (regCompletedInput) regCompletedInput.checked = false;

        if (paidInput) {
            paidInput.value = '৳ ' + datasetPaid.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
        if (paidHelper) {
            paidHelper.innerHTML = datasetPaid > 0
                ? '<span class="text-success"><i class="fa-light fa-circle-check me-1"></i>Verified collection from client</span>'
                : '<span class="text-muted"><i class="fa-light fa-info-circle me-1"></i>Checking verified payments...</span>';
        }

        if (accNameInput) accNameInput.value = (client && client !== '—') ? client : '';
        if (bankNameInput) bankNameInput.value = '';
        if (bankBranchInput) bankBranchInput.value = '';
        if (bankAccNoInput) bankAccNoInput.value = '';
        if (bankRoutingInput) bankRoutingInput.value = '';
        if (attachmentInput) attachmentInput.value = '';

        if (bookingId) {
            fetch('<?= $GLOBALS['base_url'] ?>/get-booking-payment-summary/' + bookingId)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const vPaid = parseFloat(data.total_verified_paid) || 0;
                        const pPaid = parseFloat(data.total_pending_paid) || 0;
                        if (paidInput) {
                            paidInput.value = '৳ ' + vPaid.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        }
                        if (paidHelper) {
                            if (vPaid > 0) {
                                paidHelper.innerHTML = `<span class="text-success"><i class="fa-light fa-circle-check me-1"></i>Verified: ৳ ${vPaid.toLocaleString(undefined, {minimumFractionDigits: 2})} (${data.verified_count} transaction${data.verified_count > 1 ? 's' : ''})</span>`;
                            } else if (pPaid > 0) {
                                paidHelper.innerHTML = `<span class="text-warning"><i class="fa-light fa-clock me-1"></i>৳ ${pPaid.toLocaleString(undefined, {minimumFractionDigits: 2})} pending Accounts verification</span>`;
                            } else {
                                paidHelper.innerHTML = '<span class="text-muted"><i class="fa-light fa-circle-exclamation me-1"></i>No payment recorded yet for this booking</span>';
                            }
                        }
                    }
                })
                .catch(err => console.error('Failed fetching payment summary:', err));
        }
    }

    const modalEl = document.getElementById('quickRefundModal');
    if (modalEl) {
        modalEl.addEventListener('show.bs.modal', function(e) {
            if (e.relatedTarget) {
                populateQuickRefundModal(e.relatedTarget);
            }
        });
    }

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.quickRefundBtn');
        if (!btn) return;
        populateQuickRefundModal(btn);
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal && modalEl && !modalEl.classList.contains('show')) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    }, true);
})();
</script>

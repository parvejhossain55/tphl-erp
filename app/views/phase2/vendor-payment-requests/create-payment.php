<div class="pagetitle d-flex justify-content-between align-items-center">
    <div>
        <h1><?= htmlspecialchars($page_title) ?></h1>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-vendor-payment-requests">Payment Requests</a></li>
                <li class="breadcrumb-item active">New Payment</li>
            </ol>
        </nav>
    </div>
    <a href="<?= $base_url ?>/con-vendor-payment-requests" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

<section class="section">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card__wrapper shadow-sm">
                <div class="card__header">
                    <h6 class="card__title mb-0">
                        <i class="bi bi-wallet2 me-2"></i>New Vendor Payment Request
                    </h6>
                </div>
                <div class="card__body p-4">
                    <form action="<?= $base_url ?>/con-vendor-payment-store" method="POST" id="paymentForm">

                        <!-- Payment Type -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Payment Type <span class="text-danger">*</span></label>
                            <div class="d-flex gap-3">
                                <?php foreach ($payment_types as $pt): ?>
                                <div class="form-check form-check-inline p-0">
                                    <input class="btn-check" type="radio" name="payment_type"
                                           id="pt_<?= $pt ?>" value="<?= $pt ?>"
                                           <?= $pt === 'Final' ? 'checked' : '' ?>>
                                    <label class="btn <?= $pt === 'Advance' ? 'btn-outline-primary' : ($pt === 'Partial' ? 'btn-outline-warning' : 'btn-outline-success') ?> px-4"
                                           for="pt_<?= $pt ?>">
                                        <?= $pt === 'Advance' ? '<i class="bi bi-clock me-1"></i>' : ($pt === 'Partial' ? '<i class="bi bi-hourglass-split me-1"></i>' : '<i class="bi bi-check-circle me-1"></i>') ?>
                                        <?= $pt ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div id="advanceNote" class="alert alert-info mt-2 py-2 small d-none">
                                <i class="bi bi-info-circle me-1"></i>
                                <strong>Advance Payment:</strong> মাল আসার আগে দেওয়া হয়। Delivery Verified থাকবে না। Net Payable = Advance Amount (কোনো Penalty deduction নেই)।
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Project <span class="text-danger">*</span></label>
                                <select name="project_id" id="projectSelect" class="form-select" required>
                                    <option value="">-- Select Project --</option>
                                    <?php foreach ($projects as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Date</label>
                                <input type="date" name="payment_date" class="form-control"
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Vendor <span class="text-danger">*</span></label>
                                <select name="vendor_id" id="vendorSelect" class="form-select" required>
                                    <option value="">-- Select Vendor --</option>
                                    <?php foreach ($vendors as $v): ?>
                                        <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['name'] ?? 'N/A') ?>-<?= htmlspecialchars($v['company_name'] ?? 'N/A') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Purchase Order (Optional)</label>
                                <select name="purchase_order_id" id="poSelect" class="form-select">
                                    <option value="0">-- No PO / Direct Payment --</option>
                                    <?php foreach ($all_pos as $po): ?>
                                        <option value="<?= $po['id'] ?>">PO-<?= $po['id'] ?> — <?= htmlspecialchars($po['project_name'] ?? '') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                        </div>

                        <!-- Delivery Verified (hidden for Advance) -->
                        <div id="deliveryVerifiedRow" class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="delivery_verified"
                                       id="deliveryVerified" value="1">
                                <label class="form-check-label fw-bold" for="deliveryVerified">
                                    Delivery Verified
                                    <span class="text-muted small fw-normal">(মাল রিসিভ হয়েছে নিশ্চিত করুন)</span>
                                </label>
                            </div>
                        </div>

                        <!-- Financial Details -->
                        <div class=" p-4 rounded border mb-4">
                            <h6 class="text-secondary fw-bold border-bottom pb-2 mb-3">
                                <i class="bi bi-calculator me-2"></i>Financial Details
                            </h6>
                            <div class="row ">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold" id="invoiceLabel">Invoice Amount (৳) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" name="invoice_amount" id="invoiceAmt"
                                           class="form-control form-control-lg text-end fw-bold"
                                           placeholder="0.00" required min="0.01">
                                </div>
                                <div class="col-md-4" id="advancePaidRow" style="display:none;">
                                    <label class="form-label fw-bold text-primary">Advance Already Paid (৳)</label>
                                    <input type="text" id="advancePaidAmt"
                                           class="form-control form-control-lg text-end text-primary fw-bold bg-white"
                                           readonly value="0.00">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-danger">Pending Penalty (৳)</label>
                                    <input type="text" id="penaltyAmt"
                                           class="form-control form-control-lg text-end text-danger fw-bold bg-white"
                                           readonly value="0.00">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-success">Net Payable (৳)</label>
                                    <input type="text" id="netPayableAmt"
                                           class="form-control form-control-lg text-end text-success fw-bold bg-white"
                                           readonly value="0.00">
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="3"
                                      placeholder="Enter any extra details..."></textarea>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary px-5 py-2 fw-bold"
                                    onclick="return confirm('Create Payment Request? Please confirm all details are correct.');">
                                <i class="bi bi-send-check me-1"></i> Submit Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const paymentTypeRadios   = document.querySelectorAll('input[name="payment_type"]');
    const vendorSelect        = document.getElementById('vendorSelect');
    const poSelect            = document.getElementById('poSelect');
    const invoiceInput        = document.getElementById('invoiceAmt');
    const invoiceLabel        = document.getElementById('invoiceLabel');
    const penaltyInput        = document.getElementById('penaltyAmt');
    const advancePaidInput    = document.getElementById('advancePaidAmt');
    const advancePaidRow      = document.getElementById('advancePaidRow');
    const netInput            = document.getElementById('netPayableAmt');
    const advanceNote         = document.getElementById('advanceNote');
    const deliveryVerifiedRow = document.getElementById('deliveryVerifiedRow');
    const deliveryCheckbox    = document.getElementById('deliveryVerified');

    const BASE = '<?= $GLOBALS['base_url'] ?>';

    function getPaymentType() {
        const selected = document.querySelector('input[name="payment_type"]:checked');
        return selected ? selected.value : 'Final';
    }

    function calculateNet() {
        const inv     = parseFloat(invoiceInput.value)     || 0;
        const penalty = parseFloat(penaltyInput.value)     || 0;
        const advance = parseFloat(advancePaidInput.value) || 0;
        const type    = getPaymentType();

        let net;
        if (type === 'Advance') {
            net = inv; // No penalty, no advance deduction for advance payments
        } else {
            net = Math.max(0, inv - penalty - advance);
        }
        netInput.value = net.toFixed(2);
    }

    function togglePaymentTypeUI() {
        const type = getPaymentType();

        if (type === 'Advance') {
            advanceNote.classList.remove('d-none');
            deliveryVerifiedRow.style.display = 'none';
            deliveryCheckbox.checked  = false;
            deliveryCheckbox.disabled = true;
            advancePaidRow.style.display    = 'none';
            advancePaidInput.value = '0.00';
            penaltyInput.value     = '0.00';
            invoiceLabel.innerHTML = 'Advance Amount (৳) <span class="text-danger">*</span>';
        } else {
            advanceNote.classList.add('d-none');
            deliveryVerifiedRow.style.display = '';
            deliveryCheckbox.disabled = false;
            invoiceLabel.innerHTML = 'Invoice Amount (৳) <span class="text-danger">*</span>';

            // Show advance paid row for Final/Partial
            advancePaidRow.style.display = '';

            // Fetch advance paid + penalty
            fetchAdvanceSummary();
        }
        calculateNet();
    }

    function fetchPenalty() {
        const vid = vendorSelect.value;
        if (!vid || getPaymentType() === 'Advance') {
            penaltyInput.value = '0.00';
            calculateNet();
            return;
        }
        fetch(`${BASE}/con-vendor-payment-ajax-penalty?vendor_id=${vid}`)
            .then(r => r.json())
            .then(d => {
                penaltyInput.value = d.success ? parseFloat(d.penalty).toFixed(2) : '0.00';
                calculateNet();
            })
            .catch(() => {
                penaltyInput.value = '0.00';
                calculateNet();
            });
    }

    function fetchAdvanceSummary() {
        const poId = poSelect.value;
        const vid  = vendorSelect.value;
        const type = getPaymentType();

        if (type === 'Advance') return;

        // Build URL
        const params = new URLSearchParams();
        if (poId) params.append('po_id', poId);
        if (vid)  params.append('vendor_id', vid);

        fetch(`${BASE}/con-vendor-payment-ajax-advance?${params}`)
            .then(r => r.json())
            .then(d => {
                advancePaidInput.value = d.advance_paid ? parseFloat(d.advance_paid).toFixed(2) : '0.00';
                penaltyInput.value     = d.penalty      ? parseFloat(d.penalty).toFixed(2)      : '0.00';
                calculateNet();
            })
            .catch(() => calculateNet());
    }

    // Event Listeners
    paymentTypeRadios.forEach(r => r.addEventListener('change', togglePaymentTypeUI));
    vendorSelect.addEventListener('change', () => { fetchPenalty(); fetchAdvanceSummary(); });
    poSelect.addEventListener('change', fetchAdvanceSummary);
    invoiceInput.addEventListener('input', calculateNet);

    // Init
    togglePaymentTypeUI();
});
</script>

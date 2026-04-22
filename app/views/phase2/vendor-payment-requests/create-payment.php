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
        <div class="col-lg-10">
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
                                <strong>Advance Payment:</strong> মাল আসার আগে দেওয়া হয়। PO select করা optional।
                            </div>
                        </div>

                        <!-- Row: Vendor + Project -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Vendor <span class="text-danger">*</span></label>
                                <select name="vendor_id" id="vendorSelect" class="form-select" required>
                                    <option value="">-- Select Vendor --</option>
                                    <?php foreach ($vendors as $v): ?>
                                        <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['name'] ?? 'N/A') ?> - <?= htmlspecialchars($v['company_name'] ?? 'N/A') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Project <span class="text-danger">*</span></label>
                                <select name="project_id" id="projectSelect" class="form-select" required>
                                    <option value="">-- Select Project --</option>
                                    <?php foreach ($projects as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- PO Dropdown — loads on vendor+project change -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Purchase Order</label>
                            <select name="purchase_order_id" id="poSelect" class="form-select">
                                <option value="0">-- Select Vendor & Project First (or No PO for Advance) --</option>
                            </select>
                            <div id="poLoader" class="text-muted small mt-1" style="display:none">
                                <i class="bi bi-hourglass-split me-1"></i> Loading purchase orders...
                            </div>
                        </div>

                        <!-- PO Financial Summary Card (auto-filled) -->
                        <div id="poSummaryCard" class="d-none mb-4 p-3 border rounded" style="background:#f8f9fa">
                            <h6 class="fw-bold text-secondary mb-3"><i class="bi bi-receipt me-2"></i>PO Financial Summary</h6>
                            <div class="row text-center g-2">
                                <div class="col-6 col-md-3">
                                    <div class="p-2 border rounded bg-white">
                                        <div class="text-muted small">Total PO Value</div>
                                        <div class="fw-bold text-dark" id="poTotalValue">৳ 0.00</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="p-2 border rounded bg-white">
                                        <div class="text-muted small">Received Amount</div>
                                        <div class="fw-bold text-success" id="poReceivedAmt">৳ 0.00</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="p-2 border rounded bg-white">
                                        <div class="text-muted small">Advance Paid</div>
                                        <div class="fw-bold text-primary" id="poAdvancePaid">৳ 0.00</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="p-2 border rounded bg-white">
                                        <div class="text-muted small">Due Amount</div>
                                        <div class="fw-bold text-danger" id="poDueAmt">৳ 0.00</div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 small text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Due = Received Amount − Advance Already Paid
                            </div>
                        </div>

                        <!-- Date + Delivery Verified -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Date</label>
                                <input type="date" name="payment_date" class="form-control"
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-6 d-flex align-items-end" id="deliveryVerifiedRow">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="delivery_verified"
                                           id="deliveryVerified" value="1">
                                    <label class="form-check-label fw-bold" for="deliveryVerified">
                                        Delivery Verified
                                        <span class="text-muted small fw-normal">(মাল রিসিভ হয়েছে)</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Financial Details -->
                        <div class="p-4 rounded border mb-4">
                            <h6 class="text-secondary fw-bold border-bottom pb-2 mb-3">
                                <i class="bi bi-calculator me-2"></i>Financial Details
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold" id="invoiceLabel">Invoice / Request Amount (৳) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" name="invoice_amount" id="invoiceAmt"
                                           class="form-control form-control-lg text-end fw-bold"
                                           placeholder="0.00" required min="0.01">
                                    <div class="form-text text-muted">Due amount থেকে পূরণ করুন</div>
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
    const BASE                = '<?= $GLOBALS['base_url'] ?>';
    const paymentTypeRadios   = document.querySelectorAll('input[name="payment_type"]');
    const vendorSelect        = document.getElementById('vendorSelect');
    const projectSelect       = document.getElementById('projectSelect');
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
    const poLoader            = document.getElementById('poLoader');
    const poSummaryCard       = document.getElementById('poSummaryCard');

    // ── PO Financial Summary display ─────────────────────────────────────────
    let currentPOData = null;

    function showPOSummary(po) {
        currentPOData = po;
        if (!po) { poSummaryCard.classList.add('d-none'); return; }
        document.getElementById('poTotalValue').textContent  = '৳ ' + fmt(po.total_order_value);
        document.getElementById('poReceivedAmt').textContent = '৳ ' + fmt(po.received_amount);
        document.getElementById('poAdvancePaid').textContent = '৳ ' + fmt(po.advance_paid);
        document.getElementById('poDueAmt').textContent      = '৳ ' + fmt(po.due_amount);
        poSummaryCard.classList.remove('d-none');

        // Auto-fill invoice amount with due amount
        if (parseFloat(invoiceInput.value) === 0 || !invoiceInput.value) {
            invoiceInput.value = po.due_amount.toFixed(2);
        }
        calculateNet();
    }

    function fmt(n) {
        return parseFloat(n || 0).toLocaleString('en-BD', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // ── Load POs by vendor+project ─────────────────────────────────────────
    function loadPOs() {
        const vid = vendorSelect.value;
        const pid = projectSelect.value;

        poSelect.innerHTML = '<option value="0">-- Loading... --</option>';
        poSummaryCard.classList.add('d-none');
        currentPOData = null;

        if (!vid) {
            poSelect.innerHTML = '<option value="0">-- Select Vendor First --</option>';
            return;
        }

        poLoader.style.display = 'block';
        let url = BASE + '/con-ajax-po-by-vendor?vendor_id=' + vid;
        if (pid) url += '&project_id=' + pid;

        fetch(url)
            .then(r => r.json())
            .then(data => {
                poLoader.style.display = 'none';
                poSelect.innerHTML = '<option value="0">-- No PO / Direct/Advance Payment --</option>';
                data.forEach(po => {
                    const opt = document.createElement('option');
                    opt.value = po.id;
                    opt.textContent = po.label;
                    opt.dataset.info = JSON.stringify(po);
                    poSelect.appendChild(opt);
                });
            })
            .catch(() => {
                poLoader.style.display = 'none';
                poSelect.innerHTML = '<option value="0">-- Error loading POs --</option>';
            });
    }

    // ── PO change → show summary ──────────────────────────────────────────
    poSelect.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        if (opt && opt.dataset.info) {
            showPOSummary(JSON.parse(opt.dataset.info));
            fetchAdvanceSummary();
        } else {
            poSummaryCard.classList.add('d-none');
        }
    });

    // ── Penalty & Net ─────────────────────────────────────────────────────
    function getPaymentType() {
        const sel = document.querySelector('input[name="payment_type"]:checked');
        return sel ? sel.value : 'Final';
    }

    function calculateNet() {
        const inv     = parseFloat(invoiceInput.value)     || 0;
        const penalty = parseFloat(penaltyInput.value)     || 0;
        const advance = parseFloat(advancePaidInput.value) || 0;
        const type    = getPaymentType();
        let net = type === 'Advance' ? inv : Math.max(0, inv - penalty - advance);
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
            invoiceLabel.innerHTML = 'Invoice / Request Amount (৳) <span class="text-danger">*</span>';
            advancePaidRow.style.display = '';
            fetchAdvanceSummary();
        }
        calculateNet();
    }

    function fetchPenalty() {
        const vid = vendorSelect.value;
        if (!vid || getPaymentType() === 'Advance') {
            penaltyInput.value = '0.00'; calculateNet(); return;
        }
        fetch(BASE + `/con-vendor-payment-ajax-penalty?vendor_id=${vid}`)
            .then(r => r.json())
            .then(d => { penaltyInput.value = d.success ? parseFloat(d.penalty).toFixed(2) : '0.00'; calculateNet(); })
            .catch(() => { penaltyInput.value = '0.00'; calculateNet(); });
    }

    function fetchAdvanceSummary() {
        const poId = poSelect.value;
        const vid  = vendorSelect.value;
        if (getPaymentType() === 'Advance') return;
        const params = new URLSearchParams();
        if (poId && poId !== '0') params.append('po_id', poId);
        if (vid)  params.append('vendor_id', vid);
        fetch(BASE + `/con-vendor-payment-ajax-advance?${params}`)
            .then(r => r.json())
            .then(d => {
                advancePaidInput.value = d.advance_paid ? parseFloat(d.advance_paid).toFixed(2) : '0.00';
                penaltyInput.value     = d.penalty      ? parseFloat(d.penalty).toFixed(2)      : '0.00';
                calculateNet();
            })
            .catch(() => calculateNet());
    }

    // ── Event listeners ───────────────────────────────────────────────────
    paymentTypeRadios.forEach(r => r.addEventListener('change', togglePaymentTypeUI));
    vendorSelect.addEventListener('change', () => { loadPOs(); fetchPenalty(); });
    projectSelect.addEventListener('change', () => { if (vendorSelect.value) loadPOs(); });
    invoiceInput.addEventListener('input', calculateNet);

    // Init
    togglePaymentTypeUI();
});
</script>

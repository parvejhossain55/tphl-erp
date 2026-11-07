<?php $base_url = $GLOBALS['base_url']; ?>
<div class="pagetitle d-flex justify-content-between align-items-center">
    <div>
        <h1><?= htmlspecialchars($page_title) ?></h1>
        <nav><ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-vendor-payment-requests">Payment Requests</a></li>
            <li class="breadcrumb-item active">New Payment</li>
        </ol></nav>
    </div>
    <a href="<?= $base_url ?>/con-vendor-payment-requests" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<section class="section">
<?php include __DIR__ . '/../../layouts/management_approval_notice.php'; ?>
<div class="row justify-content-center">
    <!-- ── LEFT: Form ── -->
    <div class="col-lg-7">
        <div class="card__wrapper shadow-sm mb-4">
            <div class="card__header">
                <h6 class="card__title mb-0"><i class="bi bi-wallet2 me-2"></i>New Vendor Payment Request</h6>
            </div>
            <div class="card__body p-4">
                <form action="<?= $base_url ?>/con-vendor-payment-store" method="POST" id="paymentForm" enctype="multipart/form-data">

                    <!-- Payment Type -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Payment Type <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3">
                            <?php foreach ($payment_types as $pt): ?>
                            <div class="form-check form-check-inline p-0">
                                <input class="btn-check" type="radio" name="payment_type" id="pt_<?= $pt ?>" value="<?= $pt ?>" <?= $pt === 'Final' ? 'checked' : '' ?>>
                                <label class="btn <?= $pt === 'Advance' ? 'btn-outline-primary' : ($pt === 'Partial' ? 'btn-outline-warning' : 'btn-outline-success') ?> px-4" for="pt_<?= $pt ?>">
                                    <?= $pt === 'Advance' ? '<i class="bi bi-clock me-1"></i>' : ($pt === 'Partial' ? '<i class="bi bi-hourglass-split me-1"></i>' : '<i class="bi bi-check-circle me-1"></i>') ?>
                                    <?= $pt ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div id="advanceNote" class="alert alert-info mt-2 py-2 small d-none">
                            <i class="bi bi-info-circle me-1"></i>
                            <strong>Advance Payment:</strong> মাল আসার <strong>আগে</strong> দেওয়া হয়। Material Receive (GRN) না করেও এই payment submit করা যাবে।
                            Purchase Order বাছাই করা <strong>optional</strong> — কোনো PO না থাকলে ছেড়ে দিন।
                        </div>
                    </div>

                    <!-- Row: Project + Vendor -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Project <span class="text-danger">*</span></label>
                            <select name="project_id" id="projectSelect" class="form-select select2" required>
                                <option value="">-- Select Project --</option>
                                <?php foreach ($projects as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Vendor <span class="text-danger">*</span></label>
                            <select name="vendor_id" id="vendorSelect" class="form-select select2" required>
                                <option value="">-- Select Vendor --</option>
                                <?php foreach ($vendors as $v): ?>
                                    <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['name'] ?? 'N/A') ?> - <?= htmlspecialchars($v['company_name'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    

                    <!-- PO Dropdown -->
                    <div class="mb-3" id="poDropdownWrapper">
                        <label class="form-label fw-bold">
                            <i class="bi bi-receipt me-1 text-success"></i>
                            Purchase Order
                            <span class="text-muted fw-normal small" id="poRequiredBadge"></span>
                        </label>
                        <select name="purchase_order_id" id="poSelect" class="form-select" disabled>
                            <option value="0">-- Select Project First --</option>
                        </select>
                        <div id="poLoader" class="text-muted small mt-1" style="display:none">
                            <i class="bi bi-hourglass-split me-1"></i> Loading POs...
                        </div>
                        <div id="poOptionalNote" class="text-muted small mt-1 d-none">
                            <i class="bi bi-info-circle me-1"></i> Advance payment এ PO select না করলেও চলবে।
                        </div>
                    </div>

                    <!-- PO Financial Summary -->
                    <div id="poSummaryCard" class="d-none mb-3 p-3 border rounded" style="background:#f0fdf4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-success mb-0"><i class="bi bi-receipt me-2"></i>PO Financial Summary</h6>
                            <span id="poStatusBadge" class="badge bg-secondary">—</span>
                        </div>
                        <div class="row g-2 text-center">
                            <div class="col-3">
                                <div class="p-2 border rounded bg-white">
                                    <div class="text-muted" style="font-size:10px">PO Value</div>
                                    <div class="fw-bold " id="poTotalValue">৳ 0</div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="p-2 border rounded bg-white">
                                    <div class="text-muted" style="font-size:10px">Received</div>
                                    <div class="fw-bold text-success" id="poReceivedAmt">৳ 0</div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="p-2 border rounded bg-white">
                                    <div class="text-muted" style="font-size:10px">Total Paid</div>
                                    <div class="fw-bold text-primary" id="poTotalPaid">৳ 0</div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="p-2 border rounded bg-white">
                                    <div class="text-muted" style="font-size:10px">Due</div>
                                    <div class="fw-bold text-danger" id="poDueAmt">৳ 0</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Details -->
                    <div class="p-3 rounded border mb-3">
                        <h6 class="text-secondary fw-bold border-bottom pb-2 mb-3">
                            <i class="bi bi-calculator me-2"></i>Financial Details
                        </h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-receipt me-1 text-primary"></i>Vendor Bill / Invoice # <span class="text-muted fw-normal small">(Optional)</span>
                                </label>
                                <input type="text" name="invoice_number" id="invoiceNumber" class="form-control" placeholder="e.g. INV-10293 or Bill #">
                                <div class="text-muted small mt-1" style="font-size: 11px;">
                                    Optional. If left blank, it will auto-assign from the selected Purchase Order.
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold" id="invoiceLabel">Invoice Amount (৳) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="invoice_amount" id="invoiceAmt"
                                       class="form-control form-control-lg text-end fw-bold"
                                       placeholder="0.00" required min="0.01">
                            </div>
                            <div class="col-md-6 mb-3" id="advancePaidRow">
                                <label class="form-label fw-bold text-primary">Advance Paid (৳)</label>
                                <input type="text" id="advancePaidAmt" class="form-control form-control-lg text-end text-primary fw-bold bg-white" readonly value="0.00">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold text-danger">Pending Penalty (৳)</label>
                                <input type="text" id="penaltyAmt" class="form-control form-control-lg text-end text-danger fw-bold bg-white" readonly value="0.00">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold text-success">Net Payable (৳)</label>
                                <input type="text" id="netPayableAmt" class="form-control form-control-lg text-end text-success fw-bold bg-white" readonly value="0.00">
                            </div>
                        </div>
                    </div>

                    <!-- Account Head -->
                    <div class="p-3 rounded border mb-3">
                        <?= renderCOADropdown($coaList ?? [], null, 'account_id', false, 'vendorpay') ?>
                    </div>

                    <!-- Date + Delivery -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date</label>
                            <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6 d-flex align-items-end" id="deliveryVerifiedRow">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="delivery_verified" id="deliveryVerified" value="1">
                                <label class="form-check-label fw-bold" for="deliveryVerified">
                                    Delivery Verified <span class="text-muted small fw-normal">(মাল রিসিভ হয়েছে)</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Remarks + Attachment -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Extra details..."></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold">Attachment <span class="text-muted fw-normal small">(Optional)</span></label>
                        <input type="file" name="payment_attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary px-5 py-2 fw-bold"
                                onclick="return confirm('Submit Payment Request?');">
                            <i class="bi bi-send-check me-1"></i> Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ── RIGHT: Payment History Panel ── -->
    <div class="col-lg-5 d-none" id="rightSidePanel">
        <div id="poPaymentHistoryPanel" class="d-none">
            <div class="card__wrapper shadow-sm mb-4">
                <div class="card__header">
                    <h6 class="card__title mb-0">
                        <i class="bi bi-clock-history me-2 text-warning"></i>
                        Previous Payments — <span id="poHistoryLabel">PO</span>
                    </h6>
                </div>
                <div class="card__body p-0">
                    <div id="poPaymentHistoryContent" style="max-height:520px;overflow-y:auto"></div>
                </div>
            </div>
        </div>

        <!-- PO List for project (shown after project select) -->
        <div id="projectPoListPanel" class="d-none">
            <div class="card__wrapper shadow-sm">
                <div class="card__header">
                    <h6 class="card__title mb-0">
                        <i class="bi bi-list-ul me-2 text-info"></i>
                        All POs for Selected Project
                    </h6>
                </div>
                <div class="card__body p-0">
                    <div id="projectPoListContent" style="max-height:360px;overflow-y:auto"></div>
                </div>
            </div>
        </div>
    </div>
</div>
</section>

<script>
const BASE = '<?= $GLOBALS['base_url'] ?>';
let allProjectPOs = [];

function fmt(n) {
    return '৳ ' + parseFloat(n||0).toLocaleString('en-BD', {minimumFractionDigits:2, maximumFractionDigits:2});
}

function getPaymentType() {
    const s = document.querySelector('input[name="payment_type"]:checked');
    return s ? s.value : 'Final';
}

function calculateNet() {
    const inv     = parseFloat(document.getElementById('invoiceAmt').value)     || 0;
    const penalty = parseFloat(document.getElementById('penaltyAmt').value)     || 0;
    const advance = parseFloat(document.getElementById('advancePaidAmt').value) || 0;
    const type    = getPaymentType();
    const net     = type === 'Advance' ? inv : Math.max(0, inv - penalty - advance);
    document.getElementById('netPayableAmt').value = net.toFixed(2);
}

function togglePaymentTypeUI() {
    const type = getPaymentType();
    const advNote  = document.getElementById('advanceNote');
    const delivRow = document.getElementById('deliveryVerifiedRow');
    const adv      = document.getElementById('deliveryVerified');
    const poOptNote   = document.getElementById('poOptionalNote');
    const poReqBadge  = document.getElementById('poRequiredBadge');

    if (type === 'Advance') {
        advNote.classList.remove('d-none');
        delivRow.style.display = 'none';
        adv.checked = adv.disabled = true;
        document.getElementById('invoiceLabel').innerHTML = 'Advance Amount (৳) <span class="text-danger">*</span>';
        document.getElementById('advancePaidAmt').value = '0.00';
        document.getElementById('penaltyAmt').value     = '0.00';
        // PO is optional for advance
        poOptNote.classList.remove('d-none');
        poReqBadge.innerHTML = '<span class="badge bg-info ms-1" style="font-size:9px">Optional</span>';
    } else {
        advNote.classList.add('d-none');
        delivRow.style.display = '';
        adv.disabled = false;
        document.getElementById('invoiceLabel').innerHTML = 'Invoice / Request Amount (৳) <span class="text-danger">*</span>';
        poOptNote.classList.add('d-none');
        poReqBadge.innerHTML = '<span class="badge bg-secondary ms-1" style="font-size:9px">Recommended</span>';
        fetchAdvanceSummary();
    }
    calculateNet();
}

// ── Project select → load POs ──────────────────────────────────────────────
document.getElementById('projectSelect').addEventListener('change', function () {
    const pid    = this.value;
    const poSel  = document.getElementById('poSelect');
    const loader = document.getElementById('poLoader');
    const listPanel = document.getElementById('projectPoListPanel');
    const histPanel = document.getElementById('poPaymentHistoryPanel');
    const rightSide = document.getElementById('rightSidePanel');

    poSel.innerHTML = '<option value="0">-- Select PO --</option>';
    poSel.disabled  = true;
    document.getElementById('poSummaryCard').classList.add('d-none');
    listPanel.classList.add('d-none');
    histPanel.classList.add('d-none');
    rightSide.classList.add('d-none');
    allProjectPOs = [];

    if (!pid) { poSel.innerHTML = '<option value="0">-- Select Project First --</option>'; return; }

    loader.style.display = 'block';

    fetch(BASE + '/con-ajax-po-by-project?project_id=' + pid)
        .then(r => r.json())
        .then(data => {
            loader.style.display = 'none';
            allProjectPOs = data;

            if (!data || data.length === 0) {
                poSel.innerHTML = '<option value="0">No POs found for this project</option>';
                return;
            }

            poSel.innerHTML = '<option value="0">-- Select PO --</option>';
            data.forEach(po => {
                const opt = document.createElement('option');
                opt.value = po.id;
                opt.textContent = po.label;
                opt.dataset.info = JSON.stringify(po);
                poSel.appendChild(opt);
            });
            poSel.disabled = false;

            // Show all POs summary list on right panel
            renderProjectPoList(data);
            listPanel.classList.remove('d-none');
            rightSide.classList.remove('d-none');

            // Auto-select PO if po_id parameter is present in URL
            const urlParams = new URLSearchParams(window.location.search);
            const paramPO = urlParams.get('po_id');
            if (paramPO) {
                const poSelect = document.getElementById('poSelect');
                if (poSelect.querySelector(`option[value="${paramPO}"]`)) {
                    poSelect.value = paramPO;
                    poSelect.dispatchEvent(new Event('change'));
                }
            }
        })
        .catch(() => {
            loader.style.display = 'none';
            poSel.innerHTML = '<option value="0">-- Error loading POs --</option>';
        });
});

function renderProjectPoList(pos) {
    const statusBadge = s => {
        const m = {Approved:'success', Draft:'warning', Cancelled:'danger', Submitted:'info'};
        return `<span class="badge bg-${m[s]||'secondary'}" style="font-size:9px">${s}</span>`;
    };
    let html = '<table class="table table-sm table-hover mb-0" style="font-size:12px">';
    html += '<thead class="table-light"><tr><th>PO#</th><th>Vendor &amp; Address</th><th>PO Value</th><th>Paid</th><th>Due</th><th>Status</th></tr></thead><tbody>';
    pos.forEach(po => {
        const paid = po.total_paid || 0;
        const due  = po.due_amount || 0;
        html += `<tr style="cursor:pointer" onclick="selectPO(${po.id})">
            <td class="fw-bold text-primary" style="vertical-align: middle;">PO-${po.id}</td>
            <td style="vertical-align: middle; white-space: normal;">
                <strong class="">${po.vendor_name}</strong>
                ${po.vendor_company ? `<br><span class="text-muted small" style="font-size:10.5px">${po.vendor_company}</span>` : ''}
                ${po.vendor_address ? `<br><span class="text-muted" style="font-size:10px;"><i class="bi bi-geo-alt"></i> ${po.vendor_address}</span>` : ''}
            </td>
            <td style="vertical-align: middle;">${fmt(po.total_order_value)}</td>
            <td class="text-success" style="vertical-align: middle;">${fmt(paid)}</td>
            <td class="fw-bold ${due > 0 ? 'text-danger' : 'text-muted'}" style="vertical-align: middle;">${fmt(due)}</td>
            <td style="vertical-align: middle;">${statusBadge(po.status)}</td>
        </tr>`;
    });
    html += '</tbody></table>';
    document.getElementById('projectPoListContent').innerHTML = html;
}

function selectPO(poId) {
    const poSel = document.getElementById('poSelect');
    poSel.value = poId;
    poSel.dispatchEvent(new Event('change'));
}

// ── PO select → show summary + payment history ─────────────────────────────
document.getElementById('poSelect').addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    const histPanel = document.getElementById('poPaymentHistoryPanel');
    const rightSide = document.getElementById('rightSidePanel');

    document.getElementById('poSummaryCard').classList.add('d-none');
    histPanel.classList.add('d-none');

    if (!this.value || this.value === '0' || !opt.dataset.info) return;

    const po = JSON.parse(opt.dataset.info);

    // Summary card
    document.getElementById('poTotalValue').textContent  = fmt(po.total_order_value);
    document.getElementById('poReceivedAmt').textContent = fmt(po.received_amount);
    document.getElementById('poTotalPaid').textContent   = fmt(po.total_paid);
    document.getElementById('poDueAmt').textContent      = fmt(po.due_amount);
    const sb = document.getElementById('poStatusBadge');
    sb.textContent = po.status;
    sb.className   = 'badge bg-' + ({Approved:'success',Draft:'warning',Cancelled:'danger',Submitted:'info'}[po.status]||'secondary');
    document.getElementById('poSummaryCard').classList.remove('d-none');

    // Auto-fill invoice with due amount
    const inv = document.getElementById('invoiceAmt');
    if (!inv.value || parseFloat(inv.value) === 0) {
        inv.value = parseFloat(po.due_amount).toFixed(2);
    }
    calculateNet();

    // Payment history on right panel
    renderPaymentHistory(po);
    histPanel.classList.remove('d-none');
    rightSide.classList.remove('d-none');

    // Also fetch advance/penalty
    fetchAdvanceSummary();
});

function renderPaymentHistory(po) {
    document.getElementById('poHistoryLabel').textContent = 'PO-' + po.id;
    const cont = document.getElementById('poPaymentHistoryContent');

    if (!po.payments || po.payments.length === 0) {
        cont.innerHTML = '<div class="text-center text-muted py-4" style="font-size:12px"><i class="bi bi-inbox" style="font-size:24px;display:block;opacity:.3;margin-bottom:6px"></i>No previous payments for this PO.</div>';
        return;
    }

    const statusColor = {Draft:'warning',Submitted:'info',Approved:'success',Rejected:'danger',Paid:'primary',Partially_Paid:'warning',Fully_Paid:'success'};
    const typeColor   = {Advance:'primary',Partial:'warning',Final:'success'};

    let html = '<div class="p-2">';
    po.payments.forEach(pay => {
        const sc = statusColor[pay.status] || 'secondary';
        const tc = typeColor[pay.payment_type] || 'secondary';
        const dt = pay.created_at ? new Date(pay.created_at).toLocaleDateString('en-BD', {day:'2-digit',month:'short',year:'numeric'}) : '—';
        html += `<div class="border rounded p-2 mb-2" style="font-size:12px;background:rgba(0,0,0,.02)">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <div>
                    <span class="badge bg-${tc} me-1" style="font-size:9px">${pay.payment_type}</span>
                    <span class="fw-bold">#PR-${pay.id}</span>
                    ${pay.vendor_name ? `<span class="text-muted ms-1">· ${pay.vendor_name}</span>` : ''}
                </div>
                <span class="badge bg-${sc}" style="font-size:9px">${pay.status}</span>
            </div>
            <div class="d-flex justify-content-between">
                <span class="text-muted">${dt}</span>
                <div class="text-end">
                    <div class="text-muted" style="font-size:10px">Invoice: ${fmt(pay.invoice_amount)}</div>
                    <div class="fw-bold text-success">Net: ${fmt(pay.net_payable_amount)}</div>
                </div>
            </div>
        </div>`;
    });

    // Summary totals
    const totalPaid = po.total_paid || 0;
    const due       = po.due_amount || 0;
    html += `<div class="border-top pt-2 mt-1">
        <div class="d-flex justify-content-between fw-bold" style="font-size:12px">
            <span>PO Value:</span><span>${fmt(po.total_order_value)}</span>
        </div>
        <div class="d-flex justify-content-between text-success fw-bold" style="font-size:12px">
            <span>Total Paid:</span><span>${fmt(totalPaid)}</span>
        </div>
        <div class="d-flex justify-content-between fw-bold ${due > 0 ? 'text-danger' : 'text-muted'}" style="font-size:13px">
            <span>Due Balance:</span><span>${fmt(due)}</span>
        </div>
    </div></div>`;

    cont.innerHTML = html;
}

function fetchPenalty() {
    const vid = document.getElementById('vendorSelect').value;
    if (!vid || getPaymentType() === 'Advance') {
        document.getElementById('penaltyAmt').value = '0.00'; calculateNet(); return;
    }
    fetch(BASE + `/con-vendor-payment-ajax-penalty?vendor_id=${vid}`)
        .then(r => r.json())
        .then(d => { document.getElementById('penaltyAmt').value = d.success ? parseFloat(d.penalty).toFixed(2) : '0.00'; calculateNet(); })
        .catch(() => calculateNet());
}

function fetchAdvanceSummary() {
    const poId = document.getElementById('poSelect').value;
    const vid  = document.getElementById('vendorSelect').value;
    if (getPaymentType() === 'Advance') return;
    const params = new URLSearchParams();
    if (poId && poId !== '0') params.append('po_id', poId);
    if (vid) params.append('vendor_id', vid);
    fetch(BASE + `/con-vendor-payment-ajax-advance?${params}`)
        .then(r => r.json())
        .then(d => {
            document.getElementById('advancePaidAmt').value = d.advance_paid ? parseFloat(d.advance_paid).toFixed(2) : '0.00';
            document.getElementById('penaltyAmt').value     = d.penalty      ? parseFloat(d.penalty).toFixed(2)      : '0.00';
            calculateNet();
        })
        .catch(() => calculateNet());
}

function fetchVendorReceivable() {
    const vid = document.getElementById('vendorSelect').value;
    const pid = document.getElementById('projectSelect').value;
    if (!vid || getPaymentType() === 'Advance') return;

    const params = new URLSearchParams({ vendor_id: vid });
    if (pid) params.append('project_id', pid);

    fetch(BASE + `/con-vendor-payment-ajax-receivable?${params}`)
        .then(r => r.json())
        .then(d => {
            if (!d.success) return;
            // Auto-fill invoice amount with net due amount
            const invoiceEl = document.getElementById('invoiceAmt');
            if (!invoiceEl.value || parseFloat(invoiceEl.value) === 0) {
                invoiceEl.value = parseFloat(d.due_amount).toFixed(2);
            }
            document.getElementById('penaltyAmt').value     = parseFloat(d.penalty).toFixed(2);
            document.getElementById('advancePaidAmt').value = parseFloat(d.total_paid).toFixed(2);

            // Show summary info box
            let infoEl = document.getElementById('vendorReceivableInfo');
            if (!infoEl) {
                infoEl = document.createElement('div');
                infoEl.id = 'vendorReceivableInfo';
                infoEl.style.cssText = 'margin-top:10px;padding:10px 14px;background:rgba(16,185,129,.07);border:1px solid rgba(16,185,129,.25);border-radius:8px;font-size:12px;';
                document.getElementById('vendorSelect').closest('.col-md-6').appendChild(infoEl);
            }
            infoEl.innerHTML = `
                <div class="d-flex gap-3 flex-wrap">
                    <span><strong style="color:#065f46">Net Received:</strong> ৳ ${parseFloat(d.net_receivable).toLocaleString('en-BD', {minimumFractionDigits:2})}</span>
                    <span class="ps-2"><strong style="color:#1d4ed8">Already Paid:</strong> ৳ ${parseFloat(d.total_paid).toLocaleString('en-BD', {minimumFractionDigits:2})}</span>
                    <span><strong style="color:#dc2626" class="ps-2">Due:</strong> ৳ ${parseFloat(d.due_amount).toLocaleString('en-BD', {minimumFractionDigits:2})}</span>
                </div>`;

            calculateNet();
        })
        .catch(() => calculateNet());
}

// ── Event listeners ────────────────────────────────────────────────────────
document.querySelectorAll('input[name="payment_type"]').forEach(r => r.addEventListener('change', togglePaymentTypeUI));
document.getElementById('vendorSelect').addEventListener('change', () => {
    fetchVendorReceivable();
    fetchAdvanceSummary();
});
document.getElementById('invoiceAmt').addEventListener('input', calculateNet);

// Init
togglePaymentTypeUI();

// Auto-fill from URL parameters if present
const urlParams = new URLSearchParams(window.location.search);
const paramProj = urlParams.get('project_id');
const paramVend = urlParams.get('vendor_id');

if (paramProj) {
    const projectSelect = document.getElementById('projectSelect');
    if (projectSelect) {
        projectSelect.value = paramProj;
        projectSelect.dispatchEvent(new Event('change'));
    }
}
if (paramVend) {
    const vendorSelect = document.getElementById('vendorSelect');
    if (vendorSelect) {
        vendorSelect.value = paramVend;
        vendorSelect.dispatchEvent(new Event('change'));
    }
}
</script>

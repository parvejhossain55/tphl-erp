<?php
$base_url   = $GLOBALS['base_url'];
$page_title = $page_title ?? 'New Work Order';
?>
<div class="container-fluid">
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h4 class="page-title fw-semibold fs-18 mb-0"><?= $page_title ?></h4>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-work-orders">Work Orders</a></li>
                <li class="breadcrumb-item active">New</li>
            </ol>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-xl-10">
            <form action="<?= $base_url ?>/con-work-order-store" method="POST">
                <!-- Section 1: Basic Info -->
                <div class="card custom-card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0"><i class="bi bi-info-circle me-2 text-primary"></i>Basic Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row ">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Project <span class="text-danger">*</span></label>
                                <select name="project_id" id="wo_project_id" class="form-select" required onchange="loadDrawings(this.value);">
                                    <option value="">-- Select Project --</option>
                                    <?php foreach ($projects as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Contractor <span class="text-danger">*</span></label>
                                <select name="contractor_id" class="form-select" required>
                                    <option value="">-- Select Contractor --</option>
                                    <?php foreach ($contractors as $c): ?>
                                        <option value="<?= $c['id'] ?>">
                                            <?= htmlspecialchars($c['name']) ?>
                                            (<?= htmlspecialchars($c['trade'] ?? $c['role'] ?? '') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Work Design Type <span class="text-danger">*</span></label>
                                <select name="design_id" id="wo_design_id" class="form-select" required>
                                    <option value="">-- Select Design Type --</option>
                                </select>
                                <small class="text-muted">Select Project first to load designs</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Work Type</label>
                                <select name="work_type_id" class="form-select">
                                    <option value="">-- Select Work Type --</option>
                                    <?php foreach ($workTypes as $wt): ?>
                                        <option value="<?= $wt['id'] ?>"><?= htmlspecialchars($wt['work_type_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Work Order Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" required
                                       placeholder="e.g. Foundation Work — Block A">
                            </div>

                            <!-- Working Plan Link Removed as WO is independent -->
                            <div class="col-12">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea name="description" class="form-control" rows="3"
                                          placeholder="Enter description..."></textarea>
                            </div>
                            <div class="col-12 mt-3">
                                <label class="form-label fw-semibold">Scope of Work</label>
                                <textarea name="scope_of_work" class="form-control" rows="4"
                                          placeholder="Describe the full scope of work assigned to the contractor..."></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Notes <span class="text-muted small">(optional)</span></label>
                                <textarea name="notes" class="form-control" rows="2"
                                          placeholder="Any additional notes or conditions..."></textarea>
                            </div>
                            <!-- Document Link -->
                            <div class="col-md-8 mt-2">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-link-45deg me-1 text-primary"></i>
                                    Document / BOQ Link
                                    <span class="text-muted small">(Google Drive, Docs, etc.)</span>
                                </label>
                                <div class="input-group">
                                    <input type="url" name="document_link" id="wo_doc_link" class="form-control"
                                           placeholder="https://drive.google.com/..." oninput="syncDocBtn()">
                                    <a href="#" target="_blank" id="wo_doc_open_btn"
                                       class="btn btn-outline-primary" style="display:none">
                                        <i class="bi bi-box-arrow-up-right"></i> Open
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Work Items Rate Schedule -->
                <div class="card custom-card mb-4" id="work-items-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-list-ol me-2 text-primary"></i>
                            কাজের বিবরণ ও রেট সিডিউল
                            <small class="text-muted">(Rate Schedule)</small>
                        </h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addWorkItemRow()">
                            <i class="bi bi-plus-circle me-1"></i> Add Row
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <input type="hidden" name="work_items_json" id="work_items_json">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0" id="workItemsTable" style="font-size:.85rem">
                                <thead style="background:#1e3a8a;color:#fff">
                                    <tr>
                                        <th style="width:40px" class="text-center">নং</th>
                                        <th style="width:35%">বিবরণ (Description)</th>
                                        <th style="width:8%">একক (Unit)</th>
                                        <th style="width:10%">দর/Rate (৳)</th>
                                        <th style="width:10%">পরিমাণ (Qty)</th>
                                        <th style="width:12%">মোট (Subtotal)</th>
                                        <th style="width:35px" class="text-center">Del</th>
                                    </tr>
                                </thead>
                                <tbody id="workItemsBody"></tbody>
                                <tfoot>
                                    <tr style="background:#f8fafc">
                                        <td colspan="5" class="text-end fw-bold py-2">মোট চুক্তি মূল্য (Grand Total):</td>
                                        <td class="fw-bold text-primary fs-6" id="wo_grand_total_cell">৳ 0.00</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Rate & Budget -->

                <div class="card custom-card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0"><i class="bi bi-calculator me-2 text-success"></i>Rate & Budget</h6>
                    </div>
                    <div class="card-body">
                        <div class="row ">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Unit</label>
                                <select name="unit" class="form-select">
                                    <option value="">-- Select Unit --</option>
                                    <option value="sqft">SqFt</option>
                                    <option value="rft">RFT</option>
                                    <option value="cft">CFT</option>
                                    <option value="nos">Nos</option>
                                    <option value="kg">KG</option>
                                    <option value="ton">Ton</option>
                                    <option value="ls">LS (Lump Sum)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Unit Rate (৳)</label>
                                <input type="number" name="unit_rate" id="wo_unit_rate" class="form-control"
                                       step="0.01" min="0" placeholder="0.00" oninput="calcTotal()">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Quantity</label>
                                <input type="number" name="quantity" id="wo_quantity" class="form-control"
                                       step="0.01" min="0" placeholder="0.00" oninput="calcTotal()">
                            </div>
                            <div class="col-12 my-2">
                                <div class="alert alert-primary py-2 mb-0">
                                    <strong>Total Contract Amount: ৳<span id="wo_total_display">0.00</span></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Timeline -->
                <div class="card custom-card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0"><i class="bi bi-calendar-range me-2 text-warning"></i>Timeline</h6>
                    </div>
                    <div class="card-body">
                        <div class="row ">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">End Date (Deadline)</label>
                                <input type="date" name="end_date" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Payment Terms -->
                <div class="card custom-card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0"><i class="bi bi-wallet2 me-2 text-danger"></i>Payment Terms</h6>
                    </div>
                    <div class="card-body">
                        <div class="row ">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Advance (%)</label>
                                <input type="number" name="advance_percent" id="wo_adv_pct" class="form-control"
                                       value="20" min="0" max="100" step="1" oninput="calcPayments()">
                                <small class="text-muted">Paid before work starts</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Running Bill (%)</label>
                                <input type="number" name="running_percent" id="wo_run_pct" class="form-control"
                                       value="70" min="0" max="100" step="1" oninput="calcPayments()">
                                <small class="text-muted">Paid during work progress</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Retention (%)</label>
                                <input type="number" name="retention_percent" id="wo_ret_pct" class="form-control"
                                       value="10" min="0" max="100" step="1" oninput="calcPayments()">
                                <small class="text-muted">Held until QC approval</small>
                            </div>
                        </div>

                        <!-- Payment breakdown -->
                        <div class="row  mt-2">
                            <div class="col-md-4">
                                <div class="p-3 rounded bg-info-transparent border border-info-subtle">
                                    <div class="text-muted fs-12">Advance Amount</div>
                                    <div class="fs-16 fw-bold text-info">৳<span id="wo_adv_amount">0.00</span></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 rounded bg-primary-transparent border border-primary-subtle">
                                    <div class="text-muted fs-12">Running Bill Amount</div>
                                    <div class="fs-16 fw-bold text-primary">৳<span id="wo_run_amount">0.00</span></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 rounded bg-danger-transparent border border-danger-subtle">
                                    <div class="text-muted fs-12">Retention Amount</div>
                                    <div class="fs-16 fw-bold text-danger">৳<span id="wo_ret_amount">0.00</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mb-5">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i> Create Work Order
                    </button>
                    <a href="<?= $base_url ?>/con-work-orders" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= $base_url ?>';

// ── Preset rate schedule from document ────────────────────────────────────
const PRESET_ITEMS = [
    { sl:'ক-১', desc:'বেজমেন্ট বটম স্ল্যাব — ফাউন্ডেশন হতে বেজমেন্টের বটম স্ল্যাব পর্যন্ত (পাইল ক্যাপার, মাটি কাটা, লেভেল-ড্রেসিং, সলিং/সিসি, ট্যাংক, পাইল ভাঙা, সাটারিং, রড বাধা, ঢালাই, প্লাস্টার ইত্যাদি)', unit:'sqft', rate:210, qty:'' },
    { sl:'ক-২', desc:'বেজমেন্ট টপ স্ল্যাব — বেজমেন্ট বটম স্ল্যাবের উপর থেকে কলাম, ওয়াল, ট্যাংক, বেজমেন্টের টপ স্ল্যাব/গ্রাউন্ড ফ্লোরের বটম স্ল্যাব পর্যন্ত সকল সিভিল কাজ', unit:'sqft', rate:220, qty:'' },
    { sl:'ক-৩', desc:'গ্রাউন্ড ফ্লোর থেকে সকল টিপিক্যাল ফ্লোর — স্ল্যাবের উপর/কলাম থেকে উপরের স্ল্যাব পর্যন্ত সকল সিভিল কাজ (সাটারিং, ডাউন/ফলস স্ল্যাব, রড, ঢালাই, গাথুনি, দরজার ফ্রেম ও গ্রীল ফিটিং, প্লাস্টার)', unit:'sqft', rate:220, qty:'' },
    { sl:'ক-৪', desc:'রুফটপ ফ্লোর — স্ল্যাবের উপর/কলাম থেকে উপরের স্ল্যাব পর্যন্ত সকল (মিটিং রুম, প্রেয়ার রুম, লিফট, লবি, সিড়ি ইত্যাদি) সিভিল কাজ', unit:'sqft', rate:240, qty:'' },
    { sl:'ক-৫', desc:'রুফটপ লিফট মেশিন রুম এবং পানির ট্যাংক — স্ল্যাবের উপর/কলাম থেকে উপরের স্ল্যাব পর্যন্ত সকল (লিফট মেশিন রুম, ওভারহেড ওয়াটার ট্যাংক ইত্যাদি) সিভিল কাজ', unit:'sqft', rate:250, qty:'' },
    { sl:'ক-৬', desc:'প্যারাপেট ওয়াল — ওয়ালের সকল সিভিল কাজ (সাটারিং, রড, ঢালাই, গাথুনি, প্লাস্টার ইত্যাদি) এরিয়া (উচ্চতাঃ ছাদের টপ থেকে প্যারাপেট ওয়ালের টপ)', unit:'sqft', rate:130, qty:'' },
    { sl:'ক-৭', desc:'বাউন্ডারি ওয়াল ও প্রজেক্টের সামনের ওয়ালের সকল সিভিল কাজ (সাটারিং, রড, ঢালাই, গাথুনি, প্লাস্টার, গ্রভ করা ইত্যাদি)', unit:'sqft', rate:100, qty:'' },
    { sl:'ক-৮', desc:'ছাদ মাপের মধ্যের সকল কাজ — ছাদের কোনায় কোনায় (প্রতি স্ল্যাবের জন্য)', unit:'sqft', rate:0, qty:'' },
    { sl:'ক-৯', desc:'রিটেইনিং ওয়াল ও এক্সটার্নাল পাইল ক্যাপ — (প্রযোজ্য ক্ষেত্রে আইটেম রেটে)', unit:'sqft', rate:0, qty:'' },
    { sl:'ক-১০', desc:'ছাদ মাপের বাইরের আইটেম অনুযায়ী কাজের দর (লে-আউট, মাটি কাটা, মাটি কাটা ও ভরাট, ড্রেসিং, দরমুজ, পলিথিন, সলিং, ৫" গাথুনী, ১০" গাথুনী, সাটার ভাড়া, সাটারিং করা...)', unit:'rft/sqft', rate:0, qty:'' },
];

let wiRowCount = 0;

function fmt(n) { return parseFloat(n||0).toLocaleString('en-BD',{minimumFractionDigits:2,maximumFractionDigits:2}); }

// ── Add work item row ──────────────────────────────────────────────────────
function addWorkItemRow(preset) {
    wiRowCount++;
    const tbody = document.getElementById('workItemsBody');
    const ex    = preset || {};
    const sl    = ex.sl || wiRowCount;
    const tr    = document.createElement('tr');
    tr.id       = 'wi-row-' + wiRowCount;
    tr.innerHTML = `
        <td class="text-center fw-bold" style="font-size:.8rem">${sl}</td>
        <td><textarea name="wi_desc[]" class="form-control form-control-sm" rows="2"
                style="resize:none;font-size:.8rem"
                placeholder="বিবরণ লিখুন...">${ex.desc || ''}</textarea></td>
        <td><select name="wi_unit[]" class="form-select form-select-sm wi-unit">
                <option value="sqft" ${ex.unit==='sqft'?'selected':''}>sqft</option>
                <option value="rft"  ${ex.unit==='rft'?'selected':''}>rft</option>
                <option value="cft"  ${ex.unit==='cft'?'selected':''}>cft</option>
                <option value="nos"  ${ex.unit==='nos'?'selected':''}>nos</option>
                <option value="ls"   ${ex.unit==='ls'?'selected':''}>ls</option>
                <option value="rft/sqft" ${ex.unit==='rft/sqft'?'selected':''}>rft/sqft</option>
            </select></td>
        <td><input type="number" name="wi_rate[]" class="form-control form-control-sm wi-rate"
                step="0.01" min="0" value="${ex.rate||''}" placeholder="0.00"
                oninput="calcWiRow(this)"></td>
        <td><input type="number" name="wi_qty[]" class="form-control form-control-sm wi-qty"
                step="0.01" min="0" value="${ex.qty||''}" placeholder="0.00"
                oninput="calcWiRow(this)"></td>
        <td class="text-end fw-bold wi-sub" style="font-size:.83rem">৳ 0.00</td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger"
                onclick="removeWiRow('wi-row-${wiRowCount}')">
            <i class="bi bi-trash3"></i></button></td>
    `;
    tbody.appendChild(tr);
    calcWiRow(tr.querySelector('.wi-rate'));
}

function removeWiRow(rowId) {
    const el = document.getElementById(rowId);
    if (el) { el.remove(); calcWiGrand(); }
}

function calcWiRow(el) {
    const tr   = el.closest('tr');
    const rate = parseFloat(tr.querySelector('.wi-rate').value) || 0;
    const qty  = parseFloat(tr.querySelector('.wi-qty').value)  || 0;
    const sub  = rate * qty;
    tr.querySelector('.wi-sub').textContent = '৳ ' + fmt(sub);
    calcWiGrand();
}

function calcWiGrand() {
    let grand = 0;
    document.querySelectorAll('#workItemsBody .wi-sub').forEach(el => {
        grand += parseFloat(el.textContent.replace(/[^\d.]/g,'')) || 0;
    });
    document.getElementById('wo_grand_total_cell').textContent = '৳ ' + fmt(grand);
    // Auto-fill total display
    document.getElementById('wo_total_display').textContent = fmt(grand);
    // Store in hidden total fields for server
    const rateEl = document.getElementById('wo_unit_rate');
    const qtyEl  = document.getElementById('wo_quantity');
    if (rateEl && qtyEl) {
        rateEl.value = grand;
        qtyEl.value  = 1;
    }
    calcPayments();
}

// ── Serialize work items to JSON before submit ─────────────────────────────
document.querySelector('form').addEventListener('submit', function() {
    const items = [];
    document.querySelectorAll('#workItemsBody tr').forEach(tr => {
        items.push({
            sl:   tr.querySelector('td:first-child').textContent.trim(),
            desc: tr.querySelector('textarea').value.trim(),
            unit: tr.querySelector('.wi-unit').value,
            rate: parseFloat(tr.querySelector('.wi-rate').value) || 0,
            qty:  parseFloat(tr.querySelector('.wi-qty').value)  || 0,
            sub:  parseFloat(tr.querySelector('.wi-sub').textContent.replace(/[^\d.]/g,'')) || 0,
        });
    });
    document.getElementById('work_items_json').value = JSON.stringify(items);
});

// ── Document link button sync ──────────────────────────────────────────────
function syncDocBtn() {
    const v   = (document.getElementById('wo_doc_link').value || '').trim();
    const btn = document.getElementById('wo_doc_open_btn');
    if (v && (v.startsWith('http://') || v.startsWith('https://'))) {
        btn.href = v;
        btn.style.display = '';
    } else {
        btn.href = '#';
        btn.style.display = 'none';
    }
}

// ── Cascade: Project → Drawings ───────────────────────────────────────────
function loadDrawings(projectId) {
    const drawSel = document.getElementById('wo_design_id');
    drawSel.innerHTML = '<option value="">-- Select Design Type --</option>';

    if (!projectId) return;

    fetch(`${BASE_URL}/con-ajax-wo-drawings?project_id=${projectId}`)
        .then(r => r.json())
        .then(data => {
            data.drawings.forEach(d => {
                drawSel.innerHTML += `<option value="${d.id}">${d.design_type_name}</option>`;
            });
        });
}

// ── Rate Calculations ─────────────────────────────────────────────────────
function calcTotal() {
    const rate = parseFloat(document.getElementById('wo_unit_rate').value) || 0;
    const qty  = parseFloat(document.getElementById('wo_quantity').value)  || 0;
    const total = rate * qty;
    document.getElementById('wo_total_display').textContent = total.toLocaleString('en-BD', {minimumFractionDigits: 2});
    calcPayments();
    return total;
}

function calcPayments() {
    const total  = calcTotalSilent();
    const advPct = parseFloat(document.getElementById('wo_adv_pct').value) || 0;
    const runPct = parseFloat(document.getElementById('wo_run_pct').value) || 0;
    const retPct = parseFloat(document.getElementById('wo_ret_pct').value) || 0;

    const fmt2 = v => v.toLocaleString('en-BD', {minimumFractionDigits: 2});
    document.getElementById('wo_adv_amount').textContent = fmt2(total * advPct / 100);
    document.getElementById('wo_run_amount').textContent = fmt2(total * runPct / 100);
    document.getElementById('wo_ret_amount').textContent = fmt2(total * retPct / 100);
    document.getElementById('wo_total_display').textContent = fmt2(total);
}

function calcTotalSilent() {
    const rate = parseFloat(document.getElementById('wo_unit_rate').value) || 0;
    const qty  = parseFloat(document.getElementById('wo_quantity').value)  || 0;
    return rate * qty;
}

// ── Init: load preset items ───────────────────────────────────────────────
PRESET_ITEMS.forEach(item => addWorkItemRow(item));
calcPayments();
</script>


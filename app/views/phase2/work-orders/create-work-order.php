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

    <!-- ── Existing WO Alert Banner ────────────────────────────────────── -->
    <div id="existing-wo-alert" class="alert alert-warning alert-dismissible d-none mb-4 shadow-sm" role="alert">
        <div class="d-flex align-items-start gap-3">
            <i class="bi bi-exclamation-triangle-fill fs-4 mt-1 text-warning"></i>
            <div class="flex-grow-1">
                <strong>আগের Work Order পাওয়া গেছে!</strong>
                <div id="existing-wo-info" class="mt-1 small text-muted"></div>
                <div class="mt-2 d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-sm btn-warning" onclick="loadExistingWOItems()">
                        <i class="bi bi-arrow-clockwise me-1"></i> আগের Items লোড করুন ও Edit করুন
                    </button>
                    <a href="#" id="existing-wo-view-link" class="btn btn-sm btn-outline-secondary" target="_blank">
                        <i class="bi bi-eye me-1"></i> পুরনো WO দেখুন
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="dismissExistingAlert()">
                        <i class="bi bi-plus-circle me-1"></i> নতুন WO তৈরি করুন
                    </button>
                </div>
            </div>
            <button type="button" class="btn-close" onclick="dismissExistingAlert()"></button>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-xl-11">
            <form action="<?= $base_url ?>/con-work-order-store" method="POST" id="wo-form">

                <!-- ── Section 1: Basic Information ─────────────────────── -->
                <div class="card custom-card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0"><i class="bi bi-info-circle me-2 text-primary"></i>Basic Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row ">
                            <!-- Project -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Project <span class="text-danger">*</span></label>
                                <select name="project_id" id="wo_project_id" class="form-select" required
                                        onchange="onProjectChange(this.value)">
                                    <option value="">-- Select Project --</option>
                                    <?php foreach ($projects as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Contractor -->
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

                            <!-- Design Type (Drawing) -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Work Design Type <span class="text-danger">*</span>
                                    <small class="text-muted">(Drawing)</small>
                                </label>
                                <select name="design_id" id="wo_design_id" class="form-select" required
                                        onchange="onDesignChange(this.value)">
                                    <option value="">-- Select Design Type --</option>
                                </select>
                                <small class="text-muted" id="design_hint">
                                    Project select করুন → তারপর Design Type দেখাবে
                                </small>
                                <div id="phase_info_container" class="mt-2 px-2 py-1 rounded" style="background:#eff6ff; display:none; font-size:0.82rem; border:1px solid #bfdbfe">
                                    <i class="bi bi-diagram-3 me-1 text-primary"></i>
                                    <span class="fw-semibold text-muted">Phase:</span>
                                    <span id="wo_phase_name" class="badge bg-primary ms-1 me-2"></span>
                                    <span class="text-muted">Weight:</span>
                                    <span id="wo_phase_weight" class="badge bg-info text-dark ms-1"></span>
                                </div>
                            </div>

                            <!-- Work Order Title -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Work Order Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" required
                                       placeholder="e.g. Foundation Work — Block A">
                            </div>

                            <!-- Document Link -->
                            <div class="col-md-8">
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

                            <div class="col-12">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea name="description" class="form-control" rows="2"
                                          placeholder="Enter description..."></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Scope of Work</label>
                                <textarea name="scope_of_work" class="form-control" rows="3"
                                          placeholder="Describe the full scope of work..."></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Notes <span class="text-muted small">(optional)</span></label>
                                <textarea name="notes" class="form-control" rows="2"
                                          placeholder="Any additional notes or conditions..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Section 2: Rate Schedule Table ────────────────────── -->
                <div class="card custom-card mb-4" id="rate-schedule-card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h6 class="card-title mb-0">
                                <i class="bi bi-table me-2 text-success"></i>
                                কাজের বিবরণ ও রেট সিডিউল
                                <small class="text-muted">(Rate Schedule)</small>
                            </h6>
                            <small id="rate-table-hint" class="text-info mt-1 d-block">
                                <i class="bi bi-arrow-up me-1"></i>
                                Design Type select করুন — সেই design এর কাজের ধরন (Work Types) automatically আসবে
                            </small>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addWorkItemRow()"
                                    title="নতুন খালি row যোগ করুন">
                                <i class="bi bi-plus-circle me-1"></i> Row যোগ করুন
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">

                        <!-- Loading indicator -->
                        <div id="rate-loading" class="text-center py-4 d-none">
                            <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                            <span class="text-muted">Work types লোড হচ্ছে...</span>
                        </div>

                        <!-- Empty state -->
                        <div id="rate-empty" class="text-center py-5">
                            <i class="bi bi-table fs-1 text-muted opacity-25"></i>
                            <p class="text-muted mt-2 mb-1">উপরে Design Type select করুন</p>
                            <p class="text-muted small">সেই design এর work types automatically এখানে আসবে</p>
                        </div>

                        <div class="table-responsive d-none" id="rate-table-wrap">
                            <table class="table table-bordered align-middle mb-0" id="workItemsTable" style="font-size:.84rem">
                                <thead style="background:#1e3a8a;color:#fff">
                                    <tr>
                                        <th style="width:42px" class="text-center">নং</th>
                                        <th style="width:18%">Phase Item</th>
                                        <th style="width:18%">কাজের ধরন (Work Type)</th>
                                        <th>বিবরণ (Description)</th>
                                        <th style="width:10%">অবস্থান (Location)</th>
                                        <th style="width:6%">Unit</th>
                                        <th style="width:6%">Qty</th>
                                        <th style="width:7%">Rate (৳)</th>
                                        <th style="width:9%" class="text-end">Subtotal (৳)</th>
                                        <th style="width:34px" class="text-center">Del</th>
                                    </tr>
                                </thead>
                                <tbody id="workItemsBody">
                                </tbody>
                                <tfoot>
                                    <tr style="background:#f0f9ff">
                                        <td colspan="8" class="text-end fw-bold text-primary">Grand Total:</td>
                                        <td class="text-end fw-bold text-primary fs-15" id="grandTotalDisplay">৳ 0.00</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ── Section 3: Timeline ─────────────────────────────── -->
                <div class="card custom-card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0"><i class="bi bi-calendar-range me-2 text-warning"></i>Timeline</h6>
                    </div>
                    <div class="card-body">
                        <div class="row ">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Start Date</label>
                                <input type="date" name="start_date" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">End Date (Deadline)</label>
                                <input type="date" name="end_date" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Section 4: Payment Terms ──────────────────────── -->
                <div class="card custom-card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0"><i class="bi bi-wallet2 me-2 text-danger"></i>Payment Terms</h6>
                    </div>
                    <div class="card-body">
                        <!-- Hidden computed fields -->
                        <input type="hidden" name="unit"      value="sqft">
                        <input type="hidden" name="unit_rate" id="wo_unit_rate" value="0">
                        <input type="hidden" name="quantity"  id="wo_quantity"  value="1">

                        <div class="row  mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Total Amount</label>
                                <div class="form-control bg-light fw-bold text-primary" id="totalAmountDisplay">৳ 0.00</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Advance %</label>
                                <div class="input-group">
                                    <input type="number" name="advance_percent" id="wo_adv_pct"
                                           class="form-control" value="20" min="0" max="100" step="0.01"
                                           oninput="recalcPayments()">
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted">Amount: <strong id="adv_amt_display">৳ 0.00</strong></small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Running %</label>
                                <div class="input-group">
                                    <input type="number" name="running_percent" id="wo_run_pct"
                                           class="form-control" value="70" min="0" max="100" step="0.01"
                                           oninput="recalcPayments()">
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted">Amount: <strong id="run_amt_display">৳ 0.00</strong></small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Retention %</label>
                                <div class="input-group">
                                    <input type="number" name="retention_percent" id="wo_ret_pct"
                                           class="form-control" value="10" min="0" max="100" step="0.01"
                                           oninput="recalcPayments()">
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted">Amount: <strong id="ret_amt_display">৳ 0.00</strong></small>
                            </div>
                        </div>

                        <!-- Payment visual summary -->
                        <div class="row g-2 mt-1">
                            <div class="col-md-4">
                                <div class="p-3 rounded" style="background:#e0f2fe;border-left:4px solid #0284c7">
                                    <div class="small text-muted mb-1">Advance Payment</div>
                                    <div class="fw-bold text-info fs-15" id="adv_card">৳ 0.00</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 rounded" style="background:#ede9fe;border-left:4px solid #7c3aed">
                                    <div class="small text-muted mb-1">Running Bill</div>
                                    <div class="fw-bold fs-15" style="color:#7c3aed" id="run_card">৳ 0.00</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 rounded" style="background:#fee2e2;border-left:4px solid #b91c1c">
                                    <div class="small text-muted mb-1">Retention</div>
                                    <div class="fw-bold text-danger fs-15" id="ret_card">৳ 0.00</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2 justify-content-end">
                            <a href="<?= $base_url ?>/con-work-orders" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary px-5">
                                <i class="bi bi-save me-1"></i> Save Work Order
                            </button>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
// ============================================================================
// STATE
// ============================================================================
let rowIdx        = 0;
let existingWOData = null;
let currentPhaseItems = [];
let currentWorkTypes  = [];
const BASE_URL    = '<?= $base_url ?>';

// Unit options list
const UNITS = ['sqft','rft','cft','nos','kg','ton','ls','set','bag','cum','lm','point'];

// ============================================================================
// PROJECT CHANGE → Load Drawings (design types for this project)
// ============================================================================
function onProjectChange(projectId) {
    const sel = document.getElementById('wo_design_id');
    sel.innerHTML = '<option value="">-- Loading... --</option>';
    dismissExistingAlert();
    clearRateTable();

    if (!projectId) {
        sel.innerHTML = '<option value="">-- Select Design Type --</option>';
        document.getElementById('design_hint').textContent = 'Project select করুন → তারপর Design Type দেখাবে';
        return;
    }

    fetch(`${BASE_URL}/con-ajax-wo-drawings?project_id=${projectId}`)
        .then(r => r.json())
        .then(data => {
            sel.innerHTML = '<option value="">-- Select Design Type --</option>';
            const drawings = data.drawings || [];
            if (drawings.length === 0) {
                sel.innerHTML = '<option value="">-- কোনো Design নেই --</option>';
                document.getElementById('design_hint').textContent = 'এই project এ কোনো drawing নেই';
                return;
            }
            drawings.forEach(d => {
                const opt = document.createElement('option');
                opt.value = d.id;
                opt.dataset.designTypeId = d.design_type_id;
                opt.textContent = d.design_type_name;
                sel.appendChild(opt);
            });
            document.getElementById('design_hint').textContent = 'Design Type select করুন';
        })
        .catch(() => {
            sel.innerHTML = '<option value="">-- Error loading designs --</option>';
        });
}

// ============================================================================
// DESIGN CHANGE → Load Work Types from DB + Check Existing WO
// ============================================================================
function onDesignChange(designId) {
    dismissExistingAlert();
    clearRateTable();

    if (!designId) return;

    const projectId = document.getElementById('wo_project_id').value;

    // Run both AJAX calls in parallel
    Promise.all([
        fetch(`${BASE_URL}/con-ajax-wo-work-types?design_id=${designId}`).then(r => r.json()),
        projectId
            ? fetch(`${BASE_URL}/con-ajax-wo-check?project_id=${projectId}&design_id=${designId}`).then(r => r.json())
            : Promise.resolve({ found: false })
    ]).then(([wtData, woData]) => {
        // ── Phase Info ──────────────────────────────────────────
        const pInfo = wtData.phase_info || null;
        const pContainer = document.getElementById('phase_info_container');
        currentPhaseItems = wtData.phase_items || [];
        currentWorkTypes  = wtData.work_types  || [];
        
        if (pInfo && pInfo.phase_name) {
            document.getElementById('wo_phase_name').textContent = pInfo.phase_name;
            document.getElementById('wo_phase_weight').textContent = parseFloat(pInfo.weight_percentage).toFixed(2) + '%';
            pContainer.style.display = 'block';
        } else {
            pContainer.style.display = 'none';
        }

        // ── Load rows (one per work type, or one empty row) ──────────────────────
        if (currentWorkTypes.length > 0) {
            showRateTable();
            currentWorkTypes.forEach((wt, i) => {
                addWorkItemRow({ sl: i + 1, work_type_id: wt.id, work_type_name: wt.work_type_name, desc: wt.description || '', loc: '', unit: 'sqft', rate: 0, qty: '', phase_item_id: '' });
            });
            document.getElementById('rate-table-hint').innerHTML =
                `<i class="bi bi-check-circle text-success me-1"></i><strong>${currentWorkTypes.length}টি</strong> work type লোড হয়েছে — Phase Item ও Qty/Rate fill করুন`;
        } else {
            showRateTable();
            addWorkItemRow({ sl: 1 });
            document.getElementById('rate-table-hint').innerHTML =
                `<i class="bi bi-info-circle text-warning me-1"></i> কোনো work type নেই — manually fill করুন`;
        }

        // ── Check existing WO ────────────────────────────────────
        if (woData.found) {
            existingWOData = woData;
            const wo = woData.wo;
            const statusMap = {
                draft:'Draft', approved:'Approved', active:'Active',
                work_completed:'Work Completed', closed:'Closed', rejected:'Rejected'
            };
            document.getElementById('existing-wo-info').innerHTML =
                `<strong>${htmlEsc(wo.wo_number)}</strong> — ${htmlEsc(wo.title || '')} &nbsp;|&nbsp;
                 Contractor: <strong>${htmlEsc(wo.contractor_name || '')}</strong> &nbsp;|&nbsp;
                 Status: <span class="badge bg-secondary">${statusMap[wo.status] || wo.status}</span> &nbsp;|&nbsp;
                 Total: <strong>৳${parseFloat(wo.total_amount||0).toLocaleString()}</strong>`;
            document.getElementById('existing-wo-view-link').href = `${BASE_URL}/con-work-order/${wo.id}`;
            document.getElementById('existing-wo-alert').classList.remove('d-none');
        }
    }).catch(err => {
        console.error('AJAX error:', err);
    });
}

// ============================================================================
// Load existing WO items (replaces current rows)
// ============================================================================
function loadExistingWOItems() {
    if (!existingWOData || !existingWOData.items) return;
    clearRateTable();
    showRateTable();
    existingWOData.items.forEach((it, i) => {
        addWorkItemRow({
            sl:             it.sl_no || (i + 1),
            work_type_id:  it.work_type_id || '',
            work_type_name: '',
            desc:           it.description,
            loc:            it.location  || '',
            unit:           it.unit      || 'sqft',
            rate:           parseFloat(it.rate     || 0),
            qty:            parseFloat(it.quantity || 0),
        });
    });
    recalcGrandTotal();
    document.getElementById('existing-wo-alert').classList.add('d-none');
}

function dismissExistingAlert() {
    existingWOData = null;
    document.getElementById('existing-wo-alert').classList.add('d-none');
}

// ============================================================================
// RATE TABLE show/hide/clear
// ============================================================================
function showRateTable() {
    document.getElementById('rate-empty').classList.add('d-none');
    document.getElementById('rate-loading').classList.add('d-none');
    document.getElementById('rate-table-wrap').classList.remove('d-none');
}

function clearRateTable() {
    document.getElementById('workItemsBody').innerHTML = '';
    rowIdx = 0;
    document.getElementById('rate-table-wrap').classList.add('d-none');
    document.getElementById('rate-empty').classList.remove('d-none');
    recalcGrandTotal();
    document.getElementById('rate-table-hint').innerHTML =
        '<i class="bi bi-arrow-up me-1"></i> Design Type select করুন — সেই design এর কাজের ধরন (Work Types) automatically আসবে';
}

// ============================================================================
// ADD ROW
// ============================================================================
function addWorkItemRow(data = {}) {
    const idx          = rowIdx++;
    const sl           = data.sl            || '';
    const workTypeId   = data.work_type_id  || '';
    const workTypeName = data.work_type_name || '';
    const desc         = data.desc          || '';
    const loc          = data.loc           || '';
    const unit         = data.unit          || 'sqft';
    const rate         = parseFloat(data.rate  || 0);
    const qty          = (data.qty !== '' && data.qty !== undefined) ? parseFloat(data.qty) : '';
    const phaseItemId  = data.phase_item_id || '';

    const unitOpts = UNITS.map(u => `<option value="${u}" ${u===unit?'selected':''}>${u}</option>`).join('');

    // ── Phase Item dropdown ──────────────────────────────────────────
    let phaseItemOpts = '<option value="">-- Phase Item Select করুন --</option>';
    if (typeof currentPhaseItems !== 'undefined' && currentPhaseItems.length > 0) {
        currentPhaseItems.forEach(pi => {
            const isSel = (parseInt(pi.id) === parseInt(phaseItemId)) ? 'selected' : '';
            phaseItemOpts += `<option value="${pi.id}" ${isSel}>${htmlEscAttr(pi.item_name)}</option>`;
        });
    }

    // ── Work Type dropdown ───────────────────────────────────────────
    let workTypeOpts = '<option value="">-- Work Type Select করুন --</option>';
    if (typeof currentWorkTypes !== 'undefined' && currentWorkTypes.length > 0) {
        currentWorkTypes.forEach(wt => {
            const isSel = (parseInt(wt.id) === parseInt(workTypeId)) ? 'selected' : '';
            workTypeOpts += `<option value="${wt.id}" ${isSel}>${htmlEscAttr(wt.work_type_name)}</option>`;
        });
    } else if (workTypeId && workTypeName) {
        // Fallback: pre-selected item passed directly
        workTypeOpts += `<option value="${htmlEscAttr(String(workTypeId))}" selected>${htmlEscAttr(workTypeName)}</option>`;
    }

    const tr = document.createElement('tr');
    tr.dataset.idx = idx;
    tr.innerHTML = `
        <td class="text-center p-1">
            <input type="text" name="wi_items[${idx}][sl_no]"
                   class="form-control form-control-sm text-center"
                   value="${htmlEscAttr(String(sl))}" placeholder="${idx+1}"
                   style="min-width:38px">
        </td>
        <td class="p-1">
            <select name="wi_items[${idx}][phase_item_id]"
                    class="form-select form-select-sm"
                    style="font-size:0.78rem; min-width:110px">
                ${phaseItemOpts}
            </select>
        </td>
        <td class="p-1">
            <input type="hidden" name="wi_items[${idx}][work_type_id]" value="${htmlEscAttr(String(workTypeId))}">
            <select name="wi_items[${idx}][work_type_select]"
                    class="form-select form-select-sm wo-wt-sel"
                    style="font-size:0.78rem; min-width:110px"
                    onchange="syncWorkType(this, ${idx})">
                ${workTypeOpts}
            </select>
        </td>
        <td class="p-1">
            <textarea name="wi_items[${idx}][description]"
                      class="form-control form-control-sm" rows="2"
                      placeholder="বিস্তারিত বিবরণ..."
                      style="min-width:130px;resize:vertical">${htmlEscContent(desc)}</textarea>
        </td>
        <td class="p-1">
            <input type="text" name="wi_items[${idx}][location]"
                   class="form-control form-control-sm"
                   value="${htmlEscAttr(loc)}"
                   placeholder="Floor..." style="min-width:80px">
        </td>
        <td class="p-1">
            <select name="wi_items[${idx}][unit]" class="form-select form-select-sm">${unitOpts}</select>
        </td>
        <td class="p-1">
            <input type="number" name="wi_items[${idx}][quantity]"
                   class="form-control form-control-sm wi-qty"
                   value="${qty}" min="0" step="0.001" placeholder="0"
                   oninput="recalcRow(this)" style="min-width:60px">
        </td>
        <td class="p-1">
            <input type="number" name="wi_items[${idx}][rate]"
                   class="form-control form-control-sm wi-rate"
                   value="${rate}" min="0" step="0.01" placeholder="0"
                   oninput="recalcRow(this)" style="min-width:65px">
        </td>
        <td class="text-end fw-semibold wi-subtotal p-2" style="color:#1d4ed8;min-width:80px">৳ 0.00</td>
        <td class="text-center p-1">
            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1"
                    onclick="this.closest('tr').remove(); recalcGrandTotal();" title="Delete">
                <i class="bi bi-x"></i>
            </button>
        </td>`;
    document.getElementById('workItemsBody').appendChild(tr);

    // Show table if hidden
    showRateTable();
    recalcRow(tr.querySelector('.wi-qty'));
}

// Sync hidden work_type_id when dropdown changes
function syncWorkType(sel, idx) {
    const selectedOpt = sel.options[sel.selectedIndex];
    const hiddenInput = sel.closest('td').querySelector(`input[name="wi_items[${idx}][work_type_id]"]`);
    if (hiddenInput) hiddenInput.value = selectedOpt.value;
}


// ============================================================================
// RECALCULATE
// ============================================================================
function recalcRow(inp) {
    const tr   = inp.closest('tr');
    const qty  = parseFloat(tr.querySelector('.wi-qty').value)  || 0;
    const rate = parseFloat(tr.querySelector('.wi-rate').value) || 0;
    const sub  = qty * rate;
    tr.querySelector('.wi-subtotal').textContent =
        '৳ ' + sub.toLocaleString('en-US', { minimumFractionDigits:2, maximumFractionDigits:2 });
    recalcGrandTotal();
}

function recalcGrandTotal() {
    let grand = 0;
    document.querySelectorAll('#workItemsBody .wi-qty').forEach(qEl => {
        const tr  = qEl.closest('tr');
        const qty = parseFloat(qEl.value) || 0;
        const rt  = parseFloat(tr.querySelector('.wi-rate').value) || 0;
        grand += qty * rt;
    });

    const fmt = grand.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
    document.getElementById('grandTotalDisplay').textContent  = '৳ ' + fmt;
    document.getElementById('totalAmountDisplay').textContent = '৳ ' + fmt;
    document.getElementById('wo_unit_rate').value = grand.toFixed(2);
    document.getElementById('wo_quantity').value  = 1;
    recalcPayments(grand);
}

function recalcPayments(grand) {
    if (grand === undefined) {
        grand = parseFloat(document.getElementById('wo_unit_rate').value) || 0;
    }
    const advPct = parseFloat(document.getElementById('wo_adv_pct').value) || 0;
    const runPct = parseFloat(document.getElementById('wo_run_pct').value) || 0;
    const retPct = parseFloat(document.getElementById('wo_ret_pct').value) || 0;
    const fmt    = v => '৳ ' + v.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});

    document.getElementById('adv_amt_display').textContent = fmt(grand * advPct / 100);
    document.getElementById('run_amt_display').textContent = fmt(grand * runPct / 100);
    document.getElementById('ret_amt_display').textContent = fmt(grand * retPct / 100);
    document.getElementById('adv_card').textContent = fmt(grand * advPct / 100);
    document.getElementById('run_card').textContent = fmt(grand * runPct / 100);
    document.getElementById('ret_card').textContent = fmt(grand * retPct / 100);
}

// ============================================================================
// HELPERS
// ============================================================================
function htmlEsc(str) {
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function htmlEscAttr(str)    { return htmlEsc(str); }
function htmlEscContent(str) { return htmlEsc(str); }

function syncDocBtn() {
    const url = document.getElementById('wo_doc_link').value.trim();
    const btn = document.getElementById('wo_doc_open_btn');
    if (url) { btn.href = url; btn.style.display = ''; }
    else      { btn.style.display = 'none'; }
}
</script>

<?php
$base_url   = $GLOBALS['base_url'];
$page_title = $page_title ?? 'Edit Work Order';

// Helper
$val  = fn($k, $def = '') => htmlspecialchars($wo[$k] ?? $def);
$sel  = fn($field, $v)    => ($wo[$field] ?? '') == $v ? 'selected' : '';
?>
<div class="container-fluid">
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h4 class="page-title fw-semibold fs-18 mb-0"><?= $page_title ?></h4>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-work-orders">Work Orders</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-work-order/<?= $wo['id'] ?>"><?= $val('wo_number') ?></a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </div>
        <div>
            <a href="<?= $base_url ?>/con-work-order/<?= $wo['id'] ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to WO
            </a>
        </div>
    </div>

    <!-- Draft-only notice -->
    <div class="alert alert-info border-0 shadow-sm mb-4" role="alert">
        <i class="bi bi-pencil-square me-2"></i>
        <strong><?= $val('wo_number') ?></strong> এর Draft version edit করছেন।
        সব changes সরাসরি save হবে।
    </div>

    <div class="row justify-content-center">
        <div class="col-xl-11">
            <form action="<?= $base_url ?>/con-work-order-update/<?= $wo['id'] ?>" method="POST" id="wo-edit-form">

                <!-- ── Section 1: Basic Information ─────────────────────── -->
                <div class="card custom-card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-info-circle me-2 text-primary"></i>Basic Information
                        </h6>
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
                                        <option value="<?= $p['id'] ?>" <?= $sel('project_id', $p['id']) ?>>
                                            <?= htmlspecialchars($p['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Contractor -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Contractor <span class="text-danger">*</span></label>
                                <select name="contractor_id" class="form-select" required>
                                    <option value="">-- Select Contractor --</option>
                                    <?php foreach ($contractors as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= $sel('contractor_id', $c['id']) ?>>
                                            <?= htmlspecialchars($c['name']) ?>
                                            (<?= htmlspecialchars($c['trade'] ?? $c['role'] ?? '') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Design Type (loaded by JS on page load) -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Work Design Type <span class="text-danger">*</span>
                                    <small class="text-muted">(Drawing)</small>
                                </label>
                                <select name="design_id" id="wo_design_id" class="form-select">
                                    <option value="">-- Loading... --</option>
                                </select>
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
                                       value="<?= $val('title') ?>"
                                       placeholder="e.g. Foundation Work — Block A">
                            </div>

                            <!-- Document Link -->
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-link-45deg me-1 text-primary"></i>
                                    Document / BOQ Link
                                </label>
                                <div class="input-group">
                                    <input type="url" name="document_link" id="wo_doc_link"
                                           class="form-control"
                                           value="<?= $val('document_link') ?>"
                                           placeholder="https://drive.google.com/..."
                                           oninput="syncDocBtn()">
                                    <a href="<?= $val('document_link', '#') ?>" target="_blank"
                                       id="wo_doc_open_btn"
                                       class="btn btn-outline-primary"
                                       style="<?= empty($wo['document_link']) ? 'display:none' : '' ?>">
                                        <i class="bi bi-box-arrow-up-right"></i> Open
                                    </a>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea name="description" class="form-control" rows="2"><?= $val('description') ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Scope of Work</label>
                                <textarea name="scope_of_work" class="form-control" rows="3"><?= $val('scope_of_work') ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Notes <span class="text-muted small">(optional)</span></label>
                                <textarea name="notes" class="form-control" rows="2"><?= $val('notes') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Section 2: Rate Schedule Table ────────────────────── -->
                <div class="card custom-card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h6 class="card-title mb-0">
                                <i class="bi bi-table me-2 text-success"></i>
                                কাজের বিবরণ ও রেট সিডিউল
                                <small class="text-muted">(Rate Schedule)</small>
                            </h6>
                            <small id="rate-table-hint" class="text-muted mt-1 d-block">
                                <?php if (!empty($woItems)): ?>
                                    <i class="bi bi-check-circle text-success me-1"></i>
                                    <strong><?= count($woItems) ?>টি</strong> saved item লোড হয়েছে
                                <?php else: ?>
                                    Design Type change করলে নতুন work types লোড হবে
                                <?php endif; ?>
                            </small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addWorkItemRow()">
                            <i class="bi bi-plus-circle me-1"></i> Row যোগ করুন
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" id="rate-table-wrap">
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
                                    <!-- Pre-filled by PHP then managed by JS -->
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
                        <h6 class="card-title mb-0">
                            <i class="bi bi-calendar-range me-2 text-warning"></i>Timeline
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row ">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Start Date</label>
                                <input type="date" name="start_date" class="form-control"
                                       value="<?= $val('start_date') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">End Date (Deadline)</label>
                                <input type="date" name="end_date" class="form-control"
                                       value="<?= $val('end_date') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Section 4: Update Tracking ────────────────────────── -->
                <div class="card custom-card mb-4 border-warning border-2">
                    <div class="card-header" style="background:#fffbeb;border-bottom:2px solid #fbbf24">
                        <h6 class="card-title mb-0 text-warning-emphasis">
                            <i class="bi bi-clock-history me-2 text-warning"></i>
                            এই Edit এর তথ্য
                            <small class="text-muted fw-normal">(Update Tracking)</small>
                        </h6>
                        <small class="text-muted">প্রতিটি edit এর date ও কী পরিবর্তন হয়েছে সেটা record থাকবে</small>
                    </div>
                    <div class="card-body" style="background:#fffdf0">
                        <div class="row ">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">
                                    Update Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="update_date" class="form-control border-warning"
                                       value="<?= date('Y-m-d') ?>" required>
                                <small class="text-muted">কোন তারিখে এই পরিবর্তন হয়েছে</small>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">
                                    কী কী পরিবর্তন হয়েছে <span class="text-muted small">(Update Notes)</span>
                                </label>
                                <textarea name="update_notes" class="form-control border-warning" rows="3"
                                          placeholder="যেমন: ক-৩ Typical Floor এর Rate 220 থেকে 230 করা হয়েছে। Parapet Wall Qty 1200 sqft যোগ করা হয়েছে..."
                                          required></textarea>
                                <small class="text-muted">এই field টি fill না করলে history track করা যাবে না</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Section 5: Payment Terms ──────────────────────── -->
                <div class="card custom-card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-wallet2 me-2 text-danger"></i>Payment Terms
                        </h6>
                    </div>
                    <div class="card-body">
                        <!-- Hidden computed fields -->
                        <input type="hidden" name="unit"      value="sqft">
                        <input type="hidden" name="unit_rate" id="wo_unit_rate"
                               value="<?= $val('total_amount', '0') ?>">
                        <input type="hidden" name="quantity"  id="wo_quantity" value="1">

                        <div class="row  mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Total Amount</label>
                                <div class="form-control bg-light fw-bold text-primary" id="totalAmountDisplay">
                                    ৳ <?= number_format((float)($wo['total_amount'] ?? 0), 2) ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Advance %</label>
                                <div class="input-group">
                                    <input type="number" name="advance_percent" id="wo_adv_pct"
                                           class="form-control"
                                           value="<?= $val('advance_percent', '20') ?>"
                                           min="0" max="100" step="0.01"
                                           oninput="recalcPayments()">
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted">Amount: <strong id="adv_amt_display">৳ 0.00</strong></small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Running %</label>
                                <div class="input-group">
                                    <input type="number" name="running_percent" id="wo_run_pct"
                                           class="form-control"
                                           value="<?= $val('running_percent', '70') ?>"
                                           min="0" max="100" step="0.01"
                                           oninput="recalcPayments()">
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted">Amount: <strong id="run_amt_display">৳ 0.00</strong></small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Retention %</label>
                                <div class="input-group">
                                    <input type="number" name="retention_percent" id="wo_ret_pct"
                                           class="form-control"
                                           value="<?= $val('retention_percent', '10') ?>"
                                           min="0" max="100" step="0.01"
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
                                <div class="p-3 rounded" style="background:rgba(var(--clr-theme-primary-rgb),.08);border-left:4px solid var(--clr-theme-secondary)">
                                    <div class="small text-muted mb-1">Running Bill</div>
                                    <div class="fw-bold fs-15" style="color:var(--clr-theme-secondary)" id="run_card">৳ 0.00</div>
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
                            <a href="<?= $base_url ?>/con-work-order/<?= $wo['id'] ?>"
                               class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary px-5">
                                <i class="bi bi-save me-1"></i> Update Work Order
                            </button>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
// ============================================================
// STATE
// ============================================================
const BASE_URL   = '<?= $base_url ?>';
const SAVED_WO_PROJECT_ID = <?= (int)($wo['project_id'] ?? 0) ?>;
const SAVED_WO_DESIGN_ID  = <?= (int)($wo['design_id']  ?? 0) ?>;
const UNITS = ['sqft','rft','cft','nos','kg','ton','ls','set','bag','cum','lm','point'];
let rowIdx = 0;
let currentPhaseItems = [];
let currentWorkTypes  = [];
let savedItemsRendered = false;

// ============================================================
// SAVED ITEMS from PHP (pre-fill on page load)
// ============================================================
const SAVED_ITEMS = <?= json_encode(array_values($woItems ?? []), JSON_UNESCAPED_UNICODE) ?>;

function renderSavedItems() {
    if (savedItemsRendered) return;
    SAVED_ITEMS.forEach((it, i) => {
        addWorkItemRow({
            sl:             it.sl_no          || (i + 1),
            work_type_id:  it.work_type_id   || '',
            work_type_name: '',
            desc:           it.description,
            loc:            it.location       || '',
            unit:           it.unit           || 'sqft',
            rate:           parseFloat(it.rate     || 0),
            qty:            parseFloat(it.quantity || 0),
            phase_item_id:  it.phase_item_id  || ''
        });
    });
    recalcGrandTotal();
    savedItemsRendered = true;
}

// ============================================================
// ON PAGE LOAD — pre-fill saved items + load design dropdown
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    // Load design dropdown for current project, then select saved design_id
    if (SAVED_WO_PROJECT_ID) {
        onProjectChange(SAVED_WO_PROJECT_ID, SAVED_WO_DESIGN_ID);
    } else {
        renderSavedItems();
    }

    // Init payment cards
    recalcPayments();
});

// ============================================================
// PROJECT CHANGE
// ============================================================
function onProjectChange(projectId, preselectDesignId = null) {
    const sel = document.getElementById('wo_design_id');
    sel.innerHTML = '<option value="">-- Loading... --</option>';

    if (!projectId) {
        sel.innerHTML = '<option value="">-- Select Design Type --</option>';
        return;
    }

    fetch(`${BASE_URL}/con-ajax-wo-drawings?project_id=${projectId}`)
        .then(r => r.json())
        .then(data => {
            sel.innerHTML = '<option value="">-- Select Design Type --</option>';
            (data.drawings || []).forEach(d => {
                const opt = document.createElement('option');
                opt.value = d.id;
                opt.dataset.designTypeId = d.design_type_id;
                opt.textContent = d.design_type_name;
                if (preselectDesignId && parseInt(d.id) === parseInt(preselectDesignId)) {
                    opt.selected = true;
                }
                sel.appendChild(opt);
            });
            document.getElementById('design_hint').textContent =
                preselectDesignId ? 'Saved design type selected' : 'Design Type select করুন';
            
            if (preselectDesignId) {
                loadPhaseInfo(preselectDesignId, true);
            }
        });
}

// ============================================================
// PHASE INFO LOADER
// ============================================================
function loadPhaseInfo(designId, isInit = false) {
    if (!designId) return;
    fetch(`${BASE_URL}/con-ajax-wo-work-types?design_id=${designId}`)
        .then(r => r.json())
        .then(data => {
            currentPhaseItems = data.phase_items  || [];
            currentWorkTypes  = data.work_types   || [];
            
            const pInfo = data.phase_info || null;
            const pContainer = document.getElementById('phase_info_container');
            if (pInfo && pInfo.phase_name) {
                document.getElementById('wo_phase_name').textContent   = pInfo.phase_name;
                document.getElementById('wo_phase_weight').textContent = parseFloat(pInfo.weight_percentage).toFixed(2) + '%';
                pContainer.style.display = 'block';
            } else {
                pContainer.style.display = 'none';
            }
            
            if (isInit) {
                renderSavedItems();
            }
        }).catch(err => {
            console.error('Phase Info Error:', err);
            if (isInit) renderSavedItems();
        });
}

// ============================================================
// DESIGN CHANGE — reload work types (clears current rows!)
// ============================================================
function onDesignChange(designId) {
    if (!designId) {
        document.getElementById('phase_info_container').style.display = 'none';
        return;
    }
    if (!confirm('Design Type পরিবর্তন করলে বর্তমান rate schedule rows মুছে যাবে। Continue করবেন?')) {
        // Revert to saved design
        const sel = document.getElementById('wo_design_id');
        for (let opt of sel.options) {
            if (parseInt(opt.value) === SAVED_WO_DESIGN_ID) { opt.selected = true; break; }
        }
        return;
    }

    // Load Phase Info
    loadPhaseInfo(designId);

    // Clear rows and load new work types
    document.getElementById('workItemsBody').innerHTML = '';
    rowIdx = 0;
    recalcGrandTotal();

    fetch(`${BASE_URL}/con-ajax-wo-work-types?design_id=${designId}`)
        .then(r => r.json())
        .then(data => {
            const workTypes = data.work_types || [];
            workTypes.forEach((wt, i) => {
                addWorkItemRow({
                    sl:             i + 1,
                    work_type_id:  wt.id,
                    work_type_name: wt.work_type_name,
                    desc:           wt.description || wt.work_type_name,
                    loc:            '',
                    unit:           'sqft',
                    rate:           0,
                    qty:            '',
                });
            });
            recalcGrandTotal();
            document.getElementById('rate-table-hint').innerHTML =
                workTypes.length > 0
                    ? `<i class="bi bi-check-circle text-success me-1"></i><strong>${workTypes.length}টি</strong> work type লোড হয়েছে`
                    : `<i class="bi bi-info-circle text-warning me-1"></i> কোনো work type নেই — manually add করুন`;
        });
}

// ============================================================
// ADD ROW
// ============================================================
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

    // Phase Item dropdown
    let phaseItemOpts = '<option value="">-- Phase Item Select করুন --</option>';
    if (typeof currentPhaseItems !== 'undefined' && currentPhaseItems.length > 0) {
        currentPhaseItems.forEach(pi => {
            const isSel = (parseInt(pi.id) === parseInt(phaseItemId)) ? 'selected' : '';
            phaseItemOpts += `<option value="${pi.id}" ${isSel}>${esc(pi.item_name)}</option>`;
        });
    }

    // Work Type dropdown
    let workTypeOpts = '<option value="">-- Work Type Select করুন --</option>';
    if (typeof currentWorkTypes !== 'undefined' && currentWorkTypes.length > 0) {
        currentWorkTypes.forEach(wt => {
            const isSel = (parseInt(wt.id) === parseInt(workTypeId)) ? 'selected' : '';
            workTypeOpts += `<option value="${wt.id}" ${isSel}>${esc(wt.work_type_name)}</option>`;
        });
    } else if (workTypeId && workTypeName) {
        workTypeOpts += `<option value="${esc(String(workTypeId))}" selected>${esc(workTypeName)}</option>`;
    }

    const tr = document.createElement('tr');
    tr.dataset.idx = idx;
    tr.innerHTML = `
        <td class="text-center p-1">
            <input type="text" name="wi_items[${idx}][sl_no]"
                   class="form-control form-control-sm text-center"
                   value="${esc(String(sl))}" placeholder="${idx+1}" style="min-width:38px">
        </td>
        <td class="p-1">
            <select name="wi_items[${idx}][phase_item_id]"
                    class="form-select form-select-sm"
                    style="font-size:0.78rem; min-width:110px">
                ${phaseItemOpts}
            </select>
        </td>
        <td class="p-1">
            <input type="hidden" name="wi_items[${idx}][work_type_id]" value="${esc(String(workTypeId))}">
            <select name="wi_items[${idx}][work_type_select]"
                    class="form-select form-select-sm"
                    style="font-size:0.78rem; min-width:110px"
                    onchange="syncWorkType(this, ${idx})">
                ${workTypeOpts}
            </select>
        </td>
        <td class="p-1">
            <textarea name="wi_items[${idx}][description]"
                      class="form-control form-control-sm" rows="2"
                      placeholder="বিস্তারিত বিবরণ..." style="min-width:130px;resize:vertical">${esc(desc)}</textarea>
        </td>
        <td class="p-1">
            <input type="text" name="wi_items[${idx}][location]"
                   class="form-control form-control-sm"
                   value="${esc(loc)}" placeholder="Floor..." style="min-width:80px">
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
    recalcRow(tr.querySelector('.wi-qty'));
}

function syncWorkType(sel, idx) {
    const hiddenInput = sel.closest('td').querySelector(`input[name="wi_items[${idx}][work_type_id]"]`);
    if (hiddenInput) hiddenInput.value = sel.value;
}


// ============================================================
// RECALCULATE
// ============================================================
function recalcRow(inp) {
    const tr   = inp.closest('tr');
    const qty  = parseFloat(tr.querySelector('.wi-qty').value)  || 0;
    const rate = parseFloat(tr.querySelector('.wi-rate').value) || 0;
    tr.querySelector('.wi-subtotal').textContent =
        '৳ ' + (qty * rate).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
    recalcGrandTotal();
}

function recalcGrandTotal() {
    let grand = 0;
    document.querySelectorAll('#workItemsBody .wi-qty').forEach(qEl => {
        const tr = qEl.closest('tr');
        grand += (parseFloat(qEl.value) || 0) * (parseFloat(tr.querySelector('.wi-rate').value) || 0);
    });
    const fmt = v => '৳ ' + v.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
    document.getElementById('grandTotalDisplay').textContent  = fmt(grand);
    document.getElementById('totalAmountDisplay').textContent = fmt(grand);
    document.getElementById('wo_unit_rate').value = grand.toFixed(2);
    document.getElementById('wo_quantity').value  = 1;
    recalcPayments(grand);
}

function recalcPayments(grand) {
    if (grand === undefined) grand = parseFloat(document.getElementById('wo_unit_rate').value) || 0;
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

// ============================================================
// HELPERS
// ============================================================
function esc(str) {
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function syncDocBtn() {
    const url = document.getElementById('wo_doc_link').value.trim();
    const btn = document.getElementById('wo_doc_open_btn');
    if (url) { btn.href = url; btn.style.display = ''; }
    else      { btn.style.display = 'none'; }
}
</script>

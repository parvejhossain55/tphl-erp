<?php
$base_url     = $GLOBALS['base_url'];
$preBoqId     = $preBoqId     ?? 0;
$preProjectId = $preProjectId ?? 0;
$preBoqData   = $preBoqData   ?? null;
$categories   = $categories   ?? [];
?>
<div class="pagetitle d-flex justify-content-between align-items-center">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-purchase-requests">Purchase Requests</a></li>
                <li class="breadcrumb-item active">New Request</li>
            </ol>
        </nav>
    </div>
    <a href="<?= $base_url ?>/con-purchase-requests" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

<section class="section">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="card__wrapper shadow-sm">
                <div class="card__header">
                    <h6 class="card__title mb-0">
                        <i class="bi bi-cart-plus me-2"></i>Create Purchase Request
                    </h6>
                </div>
                <div class="card__body p-4">
                    <form action="<?= $base_url ?>/con-purchase-request-store" method="POST" id="prForm">

                        <!-- Hidden fields -->
                        <input type="hidden" name="bom_id" id="prHiddenBomId" value="<?= $preBoqId ? (int)$preBoqId : '' ?>">
                        <input type="hidden" name="pr_type" id="prHiddenType" value="<?= $preBoqId ? 'boq_linked' : 'boq_linked' ?>">

                        <!-- ── PR TYPE TOGGLE ─────────────────────────────────────────── -->
                        <div class="mb-4">
                            <label class="form-label fw-bold d-block mb-2">
                                <i class="bi bi-toggles me-1 text-primary"></i> Purchase Request Type
                                <span class="text-muted fw-normal" style="font-size:11px">(select type before filling form)</span>
                            </label>
                            <div class="d-flex gap-3" id="prTypeToggle">
                                <!-- BOQ Linked -->
                                <div class="pr-type-card flex-fill" id="typeCardBoq" onclick="setPrType('boq_linked')"
                                     style="cursor:pointer;padding:14px 18px;border-radius:12px;border:2.5px solid #2563eb;
                                            background:linear-gradient(135deg,rgba(37,99,235,.08),rgba(37,99,235,.03));
                                            transition:all .2s ease;user-select:none;">
                                    <div class="d-flex align-items-center gap-3">
                                        <div style="width:40px;height:40px;border-radius:10px;background:rgba(37,99,235,.12);
                                                    display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                            <i class="bi bi-clipboard-data" style="color:#2563eb;font-size:18px"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold" style="font-size:13px;color:#1d4ed8">📋 Link with monthly target</div>
                                            <div class="text-muted" style="font-size:11px">Based on approved BOQ/BOM — quantity validated</div>
                                        </div>
                                        <div class="ms-auto">
                                            <div id="typeCheckBoq" style="width:22px;height:22px;border-radius:50%;background:#2563eb;
                                                  display:flex;align-items:center;justify-content:center">
                                                <i class="bi bi-check-lg text-white" style="font-size:12px"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Independent -->
                                <div class="pr-type-card flex-fill" id="typeCardInd" onclick="setPrType('independent')"
                                     style="cursor:pointer;padding:14px 18px;border-radius:12px;border:2px solid #e5e7eb;
                                            background:#fafafa;transition:all .2s ease;user-select:none;">
                                    <div class="d-flex align-items-center gap-3">
                                        <div style="width:40px;height:40px;border-radius:10px;background:#f3f4f6;
                                                    display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                            <i class="bi bi-pencil-square" style="color:#6b7280;font-size:18px"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold" style="font-size:13px;color:#374151">✏️ Not link with monthly target</div>
                                            <div class="text-muted" style="font-size:11px">No BOQ required — direct material request</div>
                                        </div>
                                        <div class="ms-auto">
                                            <div id="typeCheckInd" style="width:22px;height:22px;border-radius:50%;background:#e5e7eb;
                                                  display:flex;align-items:center;justify-content:center">
                                                <i class="bi bi-check-lg text-white" style="font-size:12px;opacity:0"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /PR TYPE TOGGLE -->

                        <?php if ($preBoqId): ?>
                        <!-- BOQ Prefill Banner -->
                        <div style="display:flex;align-items:center;gap:14px;padding:14px 18px;border-radius:12px;
                            background:linear-gradient(135deg,rgba(16,185,129,.08),rgba(5,150,105,.05));
                            border:1.5px solid rgba(16,185,129,.25);margin-bottom:18px">
                            <div style="width:40px;height:40px;border-radius:10px;background:rgba(16,185,129,.12);
                                display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                <i class="bi bi-clipboard-check" style="color:#059669;font-size:18px"></i>
                            </div>
                            <div style="flex:1">
                                <div style="font-size:12.5px;font-weight:800;color:#065f46">
                                    Creating PR from BOQ #<?= $preBoqId ?> — <?= htmlspecialchars($preBoqData['project_name'] ?? '') ?>
                                </div>
                                <div style="font-size:11px;color:#6b7280;margin-top:2px">
                                    <?= (int)($preBoqData['item_count'] ?? 0) ?> materials &nbsp;·&nbsp;
                                    BOQ v<?= htmlspecialchars($preBoqData['version'] ?? '1.0') ?>
                                    &nbsp;·&nbsp; Approved <?= !empty($preBoqData['approved_at']) ? date('d M Y', strtotime($preBoqData['approved_at'])) : '' ?>
                                </div>
                            </div>
                            <a href="<?= $base_url ?>/con-boq-show/<?= $preBoqId ?>" target="_blank"
                               style="font-size:11px;color:#2563eb;text-decoration:none;white-space:nowrap">
                                <i class="bi bi-box-arrow-up-right me-1"></i>View BOQ
                            </a>
                        </div>
                        <?php endif; ?>

                        <!-- ── Row 1: Project + Status + Notes ── -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Project <span class="text-danger">*</span></label>
                                <select name="project_id" id="prProjectSelect" class="form-select select2" required>
                                    <option value="">-- Select Project --</option>
                                    <?php foreach ($projects as $p): ?>
                                        <option value="<?= $p['id'] ?>" <?= ($preProjectId && $preProjectId == $p['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($p['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Status</label>
                                <select name="status" class="form-select">
                                    <option value="Draft">Draft</option>
                                    <option value="Submitted">Submitted</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-bold">Notes</label>
                                <input type="text" name="notes" class="form-control" placeholder="Urgency, purpose...">
                            </div>
                        </div>

                        <!-- ── Row 2: Required Date + Purpose (Independent only) ── -->
                        <div id="independentFields" class="row mb-3" style="display:none!important">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-calendar-event me-1 text-warning"></i>
                                    Required By Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="required_date" id="prRequiredDate" class="form-control"
                                       min="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-justify-left me-1 text-info"></i>
                                    Purpose / Description <span class="text-danger">*</span>
                                </label>
                                <textarea name="purpose" id="prPurpose" class="form-control" rows="2"
                                          placeholder="Describe why these materials are needed (e.g. Foundation work for Block-A, Floor 3...)"></textarea>
                            </div>
                        </div>

                        <!-- ── Row 3: BOQ Selection (BOQ Linked only) ── -->
                        <div id="boqSection" class="row mb-3">
                            <div class="col-md-8">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-clipboard-data me-1 text-success"></i>
                                    BOQ (Bill of Quantities)
                                    <span class="text-muted fw-normal" style="font-size:11px">(select project first — shows Approved BOQs)</span>
                                </label>
                                <select id="prBoqSelect" class="form-select" disabled>
                                    <option value="">-- Select Project First --</option>
                                </select>
                                <div id="prBoqLoading" class="text-muted small mt-1" style="display:none">
                                    <i class="bi bi-hourglass-split me-1"></i> Loading BOQs...
                                </div>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="button" id="prLoadBoqItems" class="btn btn-outline-success w-100 fw-bold" disabled>
                                    <i class="bi bi-arrow-down-circle me-1"></i> Load BOQ Materials
                                </button>
                            </div>
                        </div>

                        <!-- ── BOQ Info Card ── -->
                        <div id="prBoqInfoCard" class="alert alert-success d-none mb-3 py-2" style="font-size:12.5px">
                            <i class="bi bi-check-circle me-1"></i>
                            <span id="prBoqInfoText">—</span>
                        </div>

                        <!-- ── No BOQ Warning ── -->
                        <div id="prNoBoqWarn" class="alert alert-warning d-none mb-3 py-2" style="font-size:12.5px">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            <strong>No approved BOQ found</strong> for this project. You can still manually add materials below,
                            but they won't have BOQ quantity limits.
                        </div>

                        <hr class="my-3">

                        <!-- ── PR Items Table — BOQ Linked (full columns) ── -->
                        <div id="tableBoqLinked" class="table-responsive">
                            <table class="table table-bordered align-middle" id="prItemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:26%;">Material <span class="text-danger">*</span></th>
                                        <th style="width:10%; font-size:13px;" class="text-center">BOQ Qty</th>
                                        <th style="width:10%; font-size:13px;" class="text-center">Requested</th>
                                        <th style="width:10%; font-size:13px;" class="text-center">PO Qty</th>
                                        <th style="width:10%; font-size:13px;" class="text-center">Received</th>
                                        <th style="width:10%; font-size:13px;" class="text-center">Remaining</th>
                                        <th style="width:17%; font-size:13px;">Request Qty <span class="text-danger">*</span></th>
                                        <th style="width:7%;" class="text-center">Del</th>
                                    </tr>
                                </thead>
                                <tbody id="prItemsBody">
                                    <!-- Rows added here -->
                                </tbody>
                            </table>
                        </div>

                        <!-- ── PR Items Table — Independent (simplified) ── -->
                        <div id="tableIndependent" class="table-responsive" style="display:none">
                            <table class="table table-bordered align-middle" id="prItemsTableInd">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:40%;">Material <span class="text-danger">*</span></th>
                                        <th style="width:12%; font-size:13px;" class="text-center">Unit</th>
                                        <th style="width:20%; font-size:13px;">Quantity <span class="text-danger">*</span></th>
                                        <th style="width:20%; font-size:13px;">Required By</th>
                                        <th style="width:8%;" class="text-center">Del</th>
                                    </tr>
                                </thead>
                                <tbody id="prItemsBodyInd">
                                    <!-- Rows added here -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Add row buttons -->
                        <div class="mt-2 d-flex gap-2 align-items-center flex-wrap">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="prAddRow">
                                <i class="bi bi-plus-circle me-1"></i> Add Material Row
                            </button>
                            <button type="button" class="btn btn-outline-warning btn-sm" id="prOpenAddMaterialModal">
                                <i class="bi bi-plus-square-dotted me-1"></i> Add New Material to System
                            </button>
                            <span class="text-muted" style="font-size:11px">
                                <i class="bi bi-info-circle me-1"></i>
                                Can't find a material? Use "Add New Material" to register it first.
                            </span>
                        </div>

                        <!-- Empty state hint -->
                        <div id="prEmptyHint" class="text-center text-muted py-4" style="font-size:12.5px">
                            <i class="bi bi-arrow-up-circle" style="font-size:22px;display:block;margin-bottom:6px;opacity:.4"></i>
                            Select a project, then load BOQ materials or add manually.
                        </div>

                        <div class="mt-4 text-end">
                            <a href="<?= $base_url ?>/con-purchase-requests" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4 fw-bold">
                                <i class="bi bi-save me-1"></i> Submit Purchase Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════════════════════════
     ADD NEW MATERIAL MODAL
═════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="addMaterialModal" tabindex="-1" aria-labelledby="addMaterialModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
        <div class="modal-content" style="border-radius:16px;overflow:hidden">
            <div class="modal-header" style="background:linear-gradient(135deg,#f59e0b,#d97706);border:none">
                <h5 class="modal-title text-white fw-bold" id="addMaterialModalLabel">
                    <i class="bi bi-plus-square-dotted me-2"></i>Add New Material
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div id="addMatAlert" class="alert d-none py-2" style="font-size:12.5px"></div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Category <span class="text-danger">*</span></label>
                    <select id="matCategory" class="form-select">
                        <option value="">-- Select Category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Material Name <span class="text-danger">*</span></label>
                    <input type="text" id="matName" class="form-control" placeholder="e.g. Portland Cement Grade-53">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Unit <span class="text-danger">*</span></label>
                    <div class="d-flex gap-2 flex-wrap mb-2" id="unitQuickBtns">
                        <?php foreach (['bag','kg','ton','pcs','cft','sft','rft','ltr','mtr','set'] as $u): ?>
                            <button type="button" class="btn btn-outline-secondary btn-sm unit-quick"
                                    onclick="document.getElementById('matUnit').value='<?= $u ?>'; document.querySelectorAll('.unit-quick').forEach(b=>b.classList.remove('active')); this.classList.add('active');">
                                <?= $u ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <input type="text" id="matUnit" class="form-control" placeholder="or type custom unit...">
                </div>

                <div class="text-muted" style="font-size:11px">
                    <i class="bi bi-info-circle me-1"></i>
                    The material will be saved to the system and immediately available in the form dropdown.
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning fw-bold px-4" id="saveMaterialBtn">
                    <i class="bi bi-check-circle me-1"></i> Save & Add to Form
                </button>
            </div>
        </div>
    </div>
</div>
<!-- /ADD NEW MATERIAL MODAL -->

<script>
const BASE_URL        = '<?= $base_url ?>';
const ALL_MATERIALS   = <?= json_encode($materials) ?>; // [{id, name, unit, category_name}]
const ALL_CATEGORIES  = <?= json_encode($categories) ?>;

let currentBoqData    = null;  // selected BOQ data
let allBoqsForProject = [];    // list of approved BOQs
let currentPrType     = 'boq_linked'; // 'boq_linked' or 'independent'

// ── PR TYPE TOGGLE ─────────────────────────────────────────────────────────
function setPrType(type) {
    currentPrType = type;
    document.getElementById('prHiddenType').value = type;

    const boqCard = document.getElementById('typeCardBoq');
    const indCard = document.getElementById('typeCardInd');
    const boqCheck = document.getElementById('typeCheckBoq');
    const indCheck = document.getElementById('typeCheckInd');
    const boqSection = document.getElementById('boqSection');
    const indFields  = document.getElementById('independentFields');
    const tableBoq   = document.getElementById('tableBoqLinked');
    const tableInd   = document.getElementById('tableIndependent');
    const reqDate    = document.getElementById('prRequiredDate');
    const purpose    = document.getElementById('prPurpose');

    // Clear items when switching
    document.getElementById('prItemsBody').innerHTML    = '';
    document.getElementById('prItemsBodyInd').innerHTML = '';
    updateEmptyHint();

    if (type === 'boq_linked') {
        // BOQ card active
        boqCard.style.border   = '2.5px solid #2563eb';
        boqCard.style.background = 'linear-gradient(135deg,rgba(37,99,235,.08),rgba(37,99,235,.03))';
        indCard.style.border   = '2px solid #e5e7eb';
        indCard.style.background = '#fafafa';
        boqCheck.style.background = '#2563eb';
        boqCheck.querySelector('i').style.opacity = '1';
        indCheck.style.background = '#e5e7eb';
        indCheck.querySelector('i').style.opacity = '0';

        boqSection.style.display  = '';
        indFields.style.cssText   = 'display:none!important';
        tableBoq.style.display    = '';
        tableInd.style.display    = 'none';
        reqDate.removeAttribute('required');
        purpose.removeAttribute('required');
    } else {
        // Independent card active
        indCard.style.border   = '2.5px solid #f59e0b';
        indCard.style.background = 'linear-gradient(135deg,rgba(245,158,11,.08),rgba(245,158,11,.03))';
        boqCard.style.border   = '2px solid #e5e7eb';
        boqCard.style.background = '#fafafa';
        indCheck.style.background = '#f59e0b';
        indCheck.querySelector('i').style.opacity = '1';
        boqCheck.style.background = '#e5e7eb';
        boqCheck.querySelector('i').style.opacity = '0';

        boqSection.style.display  = 'none';
        indFields.style.cssText   = '';   // show
        tableBoq.style.display    = 'none';
        tableInd.style.display    = '';
        reqDate.setAttribute('required','required');
        purpose.setAttribute('required','required');

        // Reset BOQ
        document.getElementById('prHiddenBomId').value = '';
        document.getElementById('prBoqInfoCard').classList.add('d-none');
        document.getElementById('prNoBoqWarn').classList.add('d-none');
    }
}

// ── Material select HTML for manual rows (BOQ Linked) ─────────────────────
function makeMaterialSelect(selectedId = '', unit = '-') {
    let html = `<div class="mb-1">
        <select name="material_id[]" class="form-select form-select-sm pr-material select2" required>
            <option value="">-- Select Material --</option>`;
    ALL_MATERIALS.forEach(m => {
        const sel = (m.id == selectedId) ? 'selected' : '';
        const cat = m.category_name || 'Uncategorized';
        html += `<option value="${m.id}" data-unit="${m.unit}" ${sel}>${m.name} (${cat})</option>`;
    });
    html += `</select></div>
        <small class="pr-mat-unit text-muted">${unit !== '-' ? unit : ''}</small>`;
    return html;
}

// ── Material select HTML for Independent rows ─────────────────────────────
function makeMaterialSelectInd(selectedId = '') {
    let html = `<select name="material_id[]" class="form-select form-select-sm pr-material-ind select2" required>
        <option value="">-- Select Material --</option>`;
    ALL_MATERIALS.forEach(m => {
        const sel = (m.id == selectedId) ? 'selected' : '';
        const cat = m.category_name || 'Uncategorized';
        html += `<option value="${m.id}" data-unit="${m.unit}" ${sel}>${m.name} (${cat})</option>`;
    });
    html += `</select>`;
    return html;
}

// ── Build BOQ-Linked table row ────────────────────────────────────────────
function makePrRow(matId = '', matName = '', unit = '-', boqQty = '', usedQty = '', poQty = '', recvQty = '', remainQty = '', reqQty = '', removable = true) {
    const boqQtyFmt = boqQty    !== '' ? parseFloat(boqQty).toLocaleString(undefined,  {maximumFractionDigits:3}) : '—';
    const usedFmt   = usedQty   !== '' ? parseFloat(usedQty).toLocaleString(undefined,  {maximumFractionDigits:3}) : '—';
    const poFmt     = poQty     !== '' ? parseFloat(poQty).toLocaleString(undefined,  {maximumFractionDigits:3}) : '—';
    const recvFmt   = recvQty   !== '' ? parseFloat(recvQty).toLocaleString(undefined,  {maximumFractionDigits:3}) : '—';
    const remFmt    = remainQty !== '' ? parseFloat(remainQty) : null;
    let remCell     = '—';
    let remClass    = 'text-muted';
    if (remFmt !== null) {
        remCell  = remFmt.toLocaleString(undefined, {maximumFractionDigits:3});
        remClass = remFmt <= 0 ? 'text-danger fw-bold' : 'text-success fw-bold';
    }

    let matCell;
    if (matId && matName) {
        matCell = `<input type="hidden" name="material_id[]" value="${matId}">
                   <div class="fw-bold" style="font-size:13px">${matName}</div>
                   <small class="text-muted">${unit}</small>`;
    } else {
        matCell = makeMaterialSelect(matId, unit);
    }

    return `<tr data-boq-row="${matId ? '1' : '0'}">
        <td>${matCell}</td>
        <td class="text-center fw-bold text-primary pr-boq-qty">${boqQtyFmt}</td>
        <td class="text-center text-warning pr-used-qty">${usedFmt}</td>
        <td class="text-center text-info pr-po-qty">${poFmt}</td>
        <td class="text-center text-success pr-recv-qty">${recvFmt}</td>
        <td class="text-center ${remClass} pr-remaining">${remCell}</td>
        <td>
            <div class="input-group input-group-sm">
                <input type="number" step="0.001" name="quantity[]" class="form-control pr-qty"
                       required min="0.001" value="${reqQty ? reqQty : (remFmt > 0 ? remFmt : '')}"
                       ${remFmt !== null && remFmt > 0 ? `max="${remFmt}"` : ''}>
                <span class="input-group-text pr-unit">${unit !== '-' ? unit : '-'}</span>
            </div>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger pr-remove-row" title="Remove">
               <i class="bi bi-x-lg"></i></button>
        </td>
    </tr>`;
}

// ── Build Independent table row ───────────────────────────────────────────
function makeIndRow(matId = '', unit = '') {
    return `<tr>
        <td>${makeMaterialSelectInd(matId)}</td>
        <td class="text-center"><span class="pr-ind-unit text-muted fw-bold">${unit || '—'}</span></td>
        <td>
            <div class="input-group input-group-sm">
                <input type="number" step="0.001" name="quantity[]" class="form-control" required min="0.001">
                <span class="input-group-text pr-ind-unit-label">${unit || '-'}</span>
            </div>
        </td>
        <td>
            <input type="date" name="item_req_date[]" class="form-control form-control-sm"
                   min="<?= date('Y-m-d') ?>">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger pr-remove-row-ind" title="Remove">
               <i class="bi bi-x-lg"></i></button>
        </td>
    </tr>`;
}

function updateEmptyHint() {
    const bodyBoq = document.getElementById('prItemsBody');
    const bodyInd = document.getElementById('prItemsBodyInd');
    const hint    = document.getElementById('prEmptyHint');
    const isEmpty = (currentPrType === 'boq_linked')
        ? bodyBoq.children.length === 0
        : bodyInd.children.length === 0;
    hint.style.display = isEmpty ? '' : 'none';
}

// ── Project select → Load BOQs ────────────────────────────────────────────
document.getElementById('prProjectSelect').addEventListener('change', function () {
    const pid      = this.value;
    const boqSel   = document.getElementById('prBoqSelect');
    const loading  = document.getElementById('prBoqLoading');
    const loadBtn  = document.getElementById('prLoadBoqItems');
    const infoCard = document.getElementById('prBoqInfoCard');
    const warnCard = document.getElementById('prNoBoqWarn');

    boqSel.innerHTML = '<option value="">-- Select BOQ --</option>';
    boqSel.disabled  = true;
    loadBtn.disabled = true;
    infoCard.classList.add('d-none');
    warnCard.classList.add('d-none');
    allBoqsForProject = [];
    currentBoqData    = null;
    document.getElementById('prHiddenBomId').value = '';
    document.getElementById('prItemsBody').innerHTML = '';
    updateEmptyHint();

    if (!pid || currentPrType !== 'boq_linked') {
        boqSel.innerHTML = '<option value="">-- Select Project First --</option>';
        return;
    }

    loading.style.display = 'block';
    fetch(BASE_URL + '/con-ajax-pr-boqs?project_id=' + pid)
        .then(r => r.json())
        .then(data => {
            loading.style.display = 'none';
            allBoqsForProject = data.boqs || [];
            if (allBoqsForProject.length === 0) {
                boqSel.innerHTML = '<option value="">No approved BOQ found</option>';
                warnCard.classList.remove('d-none');
            } else {
                boqSel.innerHTML = '<option value="">-- Select BOQ --</option>';
                allBoqsForProject.forEach((b, idx) => {
                    const opt = document.createElement('option');
                    opt.value = idx;
                    opt.textContent = b.boq_label;
                    boqSel.appendChild(opt);
                });
                boqSel.disabled = false;
            }
        })
        .catch(() => {
            loading.style.display = 'none';
            boqSel.innerHTML = '<option value="">-- Error loading BOQs --</option>';
            warnCard.classList.remove('d-none');
        });
});

// ── BOQ select → Update info card ────────────────────────────────────────
document.getElementById('prBoqSelect').addEventListener('change', function () {
    const idx     = this.value;
    const loadBtn = document.getElementById('prLoadBoqItems');
    const infoCard = document.getElementById('prBoqInfoCard');
    const infoTxt  = document.getElementById('prBoqInfoText');

    if (idx === '' || allBoqsForProject.length === 0) {
        loadBtn.disabled = true;
        currentBoqData   = null;
        infoCard.classList.add('d-none');
        return;
    }
    currentBoqData = allBoqsForProject[parseInt(idx)];
    loadBtn.disabled = false;
    document.getElementById('prHiddenBomId').value = currentBoqData.id || '';

    let info = `<strong>${currentBoqData.boq_label}</strong> — `;
    info += `${currentBoqData.item_count} materials in BOQ.`;
    if (currentBoqData.target_title) info += ` Target: <strong>${currentBoqData.target_title}</strong>.`;
    if (currentBoqData.design_type_name) info += ` Design: <strong>${currentBoqData.design_type_name}</strong>.`;
    infoTxt.innerHTML = info;
    infoCard.classList.remove('d-none');
});

// ── Load BOQ Items ────────────────────────────────────────────────────────
document.getElementById('prLoadBoqItems').addEventListener('click', function () {
    if (!currentBoqData || !currentBoqData.items) return;
    const tbody = document.getElementById('prItemsBody');
    tbody.innerHTML = '';
    currentBoqData.items.forEach(item => {
        const cat = item.category_name || 'Uncategorized';
        const matDisplayName = `${item.material_name} (${cat})`;
        const html = makePrRow(
            item.material_id, matDisplayName, item.unit,
            item.boq_qty, item.used_qty, item.po_qty, item.received_qty,
            item.remaining_qty, '', true
        );
        tbody.insertAdjacentHTML('beforeend', html);
    });
    updateEmptyHint();
});

// ── Add Row (BOQ Linked) ──────────────────────────────────────────────────
document.getElementById('prAddRow').addEventListener('click', function () {
    if (currentPrType === 'boq_linked') {
        const html = makePrRow('', '', '-', '', '', '', '', '', '', true);
        document.getElementById('prItemsBody').insertAdjacentHTML('beforeend', html);
        bindLastManualRow();
    } else {
        const html = makeIndRow('', '');
        document.getElementById('prItemsBodyInd').insertAdjacentHTML('beforeend', html);
        bindLastIndRow();
    }
    updateEmptyHint();
});

function bindLastManualRow() {
    const rows = document.getElementById('prItemsBody').querySelectorAll('tr');
    const newRow = rows[rows.length - 1];
    const sel = newRow?.querySelector('.pr-material');
    if (!sel) return;

    // Initialize select2 on this dynamic dropdown
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $(sel).select2({
            placeholder: '-- Select Material --',
            allowClear: true,
            width: '100%',
            dropdownParent: $(sel).parent()
        });
    }

    $(sel).on('change', function () {
        const opt  = this.options[this.selectedIndex];
        if (!opt) return;
        const unit = opt.dataset.unit || '-';
        newRow.querySelector('.pr-unit').textContent = unit;
        const small = newRow.querySelector('.pr-mat-unit');
        if (small) small.textContent = unit;
        if (currentBoqData && currentBoqData.items) {
            const mid     = this.value;
            const boqItem = currentBoqData.items.find(i => i.material_id == mid);
            if (boqItem) {
                newRow.querySelector('.pr-boq-qty').textContent  = parseFloat(boqItem.boq_qty).toFixed(3);
                newRow.querySelector('.pr-used-qty').textContent = parseFloat(boqItem.used_qty).toFixed(3);
                if (newRow.querySelector('.pr-po-qty'))   newRow.querySelector('.pr-po-qty').textContent   = parseFloat(boqItem.po_qty || 0).toFixed(3);
                if (newRow.querySelector('.pr-recv-qty')) newRow.querySelector('.pr-recv-qty').textContent = parseFloat(boqItem.received_qty || 0).toFixed(3);
                const rem = parseFloat(boqItem.remaining_qty);
                const remCell = newRow.querySelector('.pr-remaining');
                remCell.textContent = rem.toFixed(3);
                remCell.className = 'text-center pr-remaining ' + (rem <= 0 ? 'text-danger fw-bold' : 'text-success fw-bold');
                const qtyInput = newRow.querySelector('.pr-qty');
                if (rem > 0) { qtyInput.value = rem; qtyInput.max = rem; }
            }
        }
    });
}

function bindLastIndRow() {
    const rows = document.getElementById('prItemsBodyInd').querySelectorAll('tr');
    const newRow = rows[rows.length - 1];
    const sel = newRow?.querySelector('.pr-material-ind');
    if (!sel) return;

    // Initialize select2 on this dynamic dropdown
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $(sel).select2({
            placeholder: '-- Select Material --',
            allowClear: true,
            width: '100%',
            dropdownParent: $(sel).parent()
        });
    }

    $(sel).on('change', function () {
        const opt  = this.options[this.selectedIndex];
        if (!opt) return;
        const unit = opt.dataset.unit || '—';
        newRow.querySelector('.pr-ind-unit').textContent      = unit;
        newRow.querySelector('.pr-ind-unit-label').textContent = unit;
    });
}

// ── Remove Rows ───────────────────────────────────────────────────────────
document.getElementById('prItemsBody').addEventListener('click', function (e) {
    if (e.target.closest('.pr-remove-row')) {
        e.target.closest('tr').remove();
        updateEmptyHint();
    }
});
document.getElementById('prItemsBodyInd').addEventListener('click', function (e) {
    if (e.target.closest('.pr-remove-row-ind')) {
        e.target.closest('tr').remove();
        updateEmptyHint();
    }
});

// ── Form Submit Validation ────────────────────────────────────────────────
document.getElementById('prForm').addEventListener('submit', function (e) {
    const isInd = currentPrType === 'independent';
    const tbody = document.getElementById(isInd ? 'prItemsBodyInd' : 'prItemsBody');
    const rows  = tbody.querySelectorAll('tr');

    if (rows.length === 0) {
        e.preventDefault();
        alert('Please add at least one material item.');
        return false;
    }
    let valid = true;
    rows.forEach(row => {
        const qty = parseFloat(row.querySelector('input[name="quantity[]"]')?.value || 0);
        if (!qty || qty <= 0) valid = false;
    });
    if (!valid) {
        e.preventDefault();
        alert('All rows must have a valid quantity > 0.');
        return false;
    }

    // Independent: require required_date
    if (isInd) {
        const rd = document.getElementById('prRequiredDate').value;
        if (!rd) {
            e.preventDefault();
            alert('Please select a Required By Date for Independent PR.');
            return false;
        }
    }
});

// Initial hint
updateEmptyHint();

// ── ADD NEW MATERIAL MODAL ────────────────────────────────────────────────
document.getElementById('prOpenAddMaterialModal').addEventListener('click', function () {
    document.getElementById('matCategory').value = '';
    document.getElementById('matName').value     = '';
    document.getElementById('matUnit').value     = '';
    document.querySelectorAll('.unit-quick').forEach(b => b.classList.remove('active'));
    const al = document.getElementById('addMatAlert');
    al.className = 'alert d-none py-2';
    al.textContent = '';
    const modal = new bootstrap.Modal(document.getElementById('addMaterialModal'));
    modal.show();
});

document.getElementById('saveMaterialBtn').addEventListener('click', function () {
    const categoryId = document.getElementById('matCategory').value;
    const name       = document.getElementById('matName').value.trim();
    const unit       = document.getElementById('matUnit').value.trim();
    const al         = document.getElementById('addMatAlert');

    if (!categoryId || !name || !unit) {
        al.className = 'alert alert-danger py-2';
        al.textContent = 'Please fill in all fields: Category, Name, and Unit.';
        return;
    }

    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

    const formData = new FormData();
    formData.append('category_id', categoryId);
    formData.append('name', name);
    formData.append('unit', unit);

    fetch(BASE_URL + '/con-ajax-add-material', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Save & Add to Form';

        if (!data.success) {
            al.className = 'alert alert-danger py-2';
            al.textContent = data.error || 'Failed to add material.';
            return;
        }

        // 1. Add to global materials array
        const newMat = data.material;
        ALL_MATERIALS.push(newMat);

        // 2. Add option to all existing material dropdowns
        const allSelects = document.querySelectorAll('.pr-material, .pr-material-ind');
        allSelects.forEach(sel => {
            const opt = document.createElement('option');
            opt.value            = newMat.id;
            opt.dataset.unit     = newMat.unit;
            opt.textContent      = `${newMat.name} (${newMat.category_name})`;
            sel.appendChild(opt);
            if (typeof $ !== 'undefined' && $.fn.select2) {
                $(sel).trigger('change');
            }
        });

        // 3. Auto-add a new row with this material selected
        if (currentPrType === 'boq_linked') {
            const html = makePrRow('', '', '-', '', '', '', '', '', '', true);
            document.getElementById('prItemsBody').insertAdjacentHTML('beforeend', html);
            bindLastManualRow();
            // Select the new material in the last row
            const lastSel = document.getElementById('prItemsBody').querySelector('tr:last-child .pr-material');
            if (lastSel) {
                if (typeof $ !== 'undefined' && $.fn.select2) {
                    $(lastSel).val(newMat.id).trigger('change');
                } else {
                    lastSel.value = newMat.id;
                    lastSel.dispatchEvent(new Event('change'));
                }
            }
        } else {
            const html = makeIndRow(newMat.id, newMat.unit);
            document.getElementById('prItemsBodyInd').insertAdjacentHTML('beforeend', html);
            bindLastIndRow();
            // Trigger change to set unit display
            const lastSel = document.getElementById('prItemsBodyInd').querySelector('tr:last-child .pr-material-ind');
            if (lastSel) {
                if (typeof $ !== 'undefined' && $.fn.select2) {
                    $(lastSel).val(newMat.id).trigger('change');
                } else {
                    lastSel.dispatchEvent(new Event('change'));
                }
            }
        }
        updateEmptyHint();

        // 4. Show success + close modal
        al.className = 'alert alert-success py-2';
        al.textContent = `✓ "${newMat.name}" added successfully!`;
        setTimeout(() => {
            bootstrap.Modal.getInstance(document.getElementById('addMaterialModal')).hide();
        }, 1200);
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Save & Add to Form';
        al.className = 'alert alert-danger py-2';
        al.textContent = 'Network error. Please try again.';
    });
});

// ── Auto-trigger if coming from Procurement Dashboard with preBoqId ───────
const PRE_BOQ_ID     = <?= $preBoqId     ? (int)$preBoqId     : 0 ?>;
const PRE_PROJECT_ID = <?= $preProjectId ? (int)$preProjectId : 0 ?>;

if (PRE_BOQ_ID && PRE_PROJECT_ID) {
    const projSel = document.getElementById('prProjectSelect');
    if (projSel && projSel.value) {
        projSel.dispatchEvent(new Event('change'));
        const boqSel = document.getElementById('prBoqSelect');
        const observer = new MutationObserver(() => {
            for (let i = 0; i < boqSel.options.length; i++) {
                const boqIdx = parseInt(boqSel.options[i].value);
                if (!isNaN(boqIdx) && allBoqsForProject[boqIdx] &&
                    allBoqsForProject[boqIdx].id == PRE_BOQ_ID) {
                    boqSel.value = boqIdx;
                    boqSel.dispatchEvent(new Event('change'));
                    setTimeout(() => {
                        const loadBtn = document.getElementById('prLoadBoqItems');
                        if (loadBtn && !loadBtn.disabled) loadBtn.click();
                    }, 200);
                    observer.disconnect();
                    break;
                }
            }
        });
        observer.observe(boqSel, { childList: true });
    }
}
</script>

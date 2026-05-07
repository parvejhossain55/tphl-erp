<?php
$base_url     = $GLOBALS['base_url'];
$preBoqId     = $preBoqId     ?? 0;
$preProjectId = $preProjectId ?? 0;
$preBoqData   = $preBoqData   ?? null;
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

                        <!-- Hidden BOQ id — updates when user selects from dropdown OR pre-filled from URL -->
                        <input type="hidden" name="bom_id" id="prHiddenBomId" value="<?= $preBoqId ? (int)$preBoqId : '' ?>">
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
                                <select name="project_id" id="prProjectSelect" class="form-select" required>
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

                        <!-- ── Row 2: BOQ Selection (target-wise) ── -->
                        <div class="row mb-3">
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

                        <!-- ── PR Items Table ── -->
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle" id="prItemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:36%;">Material <span class="text-danger">*</span></th>
                                        <th style="width:16%;">BOQ Qty</th>
                                        <th style="width:14%;">Used Qty</th>
                                        <th style="width:14%;">Remaining</th>
                                        <th style="width:14%;">Request Qty <span class="text-danger">*</span></th>
                                        <th style="width:6%;" class="text-center">Del</th>
                                    </tr>
                                </thead>
                                <tbody id="prItemsBody">
                                    <!-- Rows added here -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Manual add row button -->
                        <div class="mt-2 d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="prAddRow">
                                <i class="bi bi-plus-circle me-1"></i> Add Material Manually
                            </button>
                        </div>

                        <!-- Empty state hint -->
                        <div id="prEmptyHint" class="text-center text-muted py-4" style="font-size:12.5px">
                            <i class="bi bi-arrow-up-circle" style="font-size:22px;display:block;margin-bottom:6px;opacity:.4"></i>
                            Select a project and load BOQ materials, or add manually.
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

<script>
const BASE_URL        = '<?= $base_url ?>';
const ALL_MATERIALS   = <?= json_encode($materials) ?>; // [{id, name, unit}]

let currentBoqData    = null;  // selected BOQ data
let allBoqsForProject = [];    // list of approved BOQs

// ── Material select HTML for manual rows ──────────────────────────────────
function makeMaterialSelect(selectedId = '', unit = '-') {
    let html = `<div class="mb-1">
        <select name="material_id[]" class="form-select form-select-sm pr-material" required>
            <option value="">-- Select Material --</option>`;
    ALL_MATERIALS.forEach(m => {
        const sel = (m.id == selectedId) ? 'selected' : '';
        html += `<option value="${m.id}" data-unit="${m.unit}" ${sel}>${m.name} (${m.unit})</option>`;
    });
    html += `</select></div>
        <small class="pr-mat-unit text-muted">${unit !== '-' ? unit : ''}</small>`;
    return html;
}

function makePrRow(matId = '', matName = '', unit = '-', boqQty = '', usedQty = '', remainQty = '', reqQty = '', removable = true) {
    const boqQtyFmt  = boqQty    !== '' ? parseFloat(boqQty).toLocaleString(undefined,  {maximumFractionDigits:3}) : '—';
    const usedFmt    = usedQty   !== '' ? parseFloat(usedQty).toLocaleString(undefined,  {maximumFractionDigits:3}) : '—';
    const remFmt     = remainQty !== '' ? parseFloat(remainQty) : null;
    let remCell      = '—';
    let remClass     = 'text-muted';
    if (remFmt !== null) {
        remCell  = remFmt.toLocaleString(undefined, {maximumFractionDigits:3});
        remClass = remFmt <= 0 ? 'text-danger fw-bold' : 'text-success fw-bold';
    }

    const removeBtn = removable
        ? `<button type="button" class="btn btn-sm btn-outline-danger pr-remove-row" title="Remove">
               <i class="bi bi-x-lg"></i></button>`
        : `<button type="button" class="btn btn-sm btn-outline-danger pr-remove-row" title="Remove">
               <i class="bi bi-x-lg"></i></button>`;

    // If from BOQ: show readonly material name + hidden input
    let matCell;
    if (matId && matName) {
        matCell = `<input type="hidden" name="material_id[]" value="${matId}">
                   <div class="fw-bold" style="font-size:13px">${matName}</div>
                   <small class="text-muted">${unit}</small>`;
    } else {
        // Manual: dropdown
        matCell = makeMaterialSelect(matId, unit);
    }

    return `<tr data-boq-row="${matId ? '1' : '0'}">
        <td>${matCell}</td>
        <td class="text-center fw-bold text-primary pr-boq-qty">${boqQtyFmt}</td>
        <td class="text-center text-warning pr-used-qty">${usedFmt}</td>
        <td class="text-center ${remClass} pr-remaining">${remCell}</td>
        <td>
            <div class="input-group input-group-sm">
                <input type="number" step="0.001" name="quantity[]" class="form-control pr-qty"
                       required min="0.001" value="${reqQty ? reqQty : (remFmt > 0 ? remFmt : '')}"
                       ${remFmt !== null && remFmt > 0 ? `max="${remFmt}"` : ''}>
                <span class="input-group-text pr-unit">${unit !== '-' ? unit : '-'}</span>
            </div>
        </td>
        <td class="text-center">${removeBtn}</td>
    </tr>`;
}

function updateEmptyHint() {
    const tbody = document.getElementById('prItemsBody');
    const hint  = document.getElementById('prEmptyHint');
    hint.style.display = tbody.children.length === 0 ? '' : 'none';
}

// ── Project select → Load BOQs ────────────────────────────────────────────
document.getElementById('prProjectSelect').addEventListener('change', function () {
    const pid      = this.value;
    const boqSel   = document.getElementById('prBoqSelect');
    const loading  = document.getElementById('prBoqLoading');
    const loadBtn  = document.getElementById('prLoadBoqItems');
    const infoCard = document.getElementById('prBoqInfoCard');
    const warnCard = document.getElementById('prNoBoqWarn');

    // Reset
    boqSel.innerHTML = '<option value="">-- Select BOQ --</option>';
    boqSel.disabled  = true;
    loadBtn.disabled = true;
    infoCard.classList.add('d-none');
    warnCard.classList.add('d-none');
    allBoqsForProject = [];
    currentBoqData    = null;
    document.getElementById('prHiddenBomId').value = ''; // reset BOQ id

    // Clear items
    document.getElementById('prItemsBody').innerHTML = '';
    updateEmptyHint();

    if (!pid) {
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
                    opt.value = idx; // index in allBoqsForProject
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

// ── BOQ select → Update info card + enable load button ───────────────────
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

    // ── Update hidden bom_id so it gets submitted with the form
    document.getElementById('prHiddenBomId').value = currentBoqData.id || '';

    // Info card
    let info = `<strong>${currentBoqData.boq_label}</strong> — `;
    info += `${currentBoqData.item_count} materials in BOQ.`;
    if (currentBoqData.target_title) info += ` Target: <strong>${currentBoqData.target_title}</strong>.`;
    if (currentBoqData.design_type_name) info += ` Design: <strong>${currentBoqData.design_type_name}</strong>.`;
    infoTxt.innerHTML = info;
    infoCard.classList.remove('d-none');
});

// ── Load BOQ Items → Populate table ──────────────────────────────────────
document.getElementById('prLoadBoqItems').addEventListener('click', function () {
    if (!currentBoqData || !currentBoqData.items) return;

    const tbody = document.getElementById('prItemsBody');
    tbody.innerHTML = ''; // clear existing

    currentBoqData.items.forEach(item => {
        const html = makePrRow(
            item.material_id,
            item.material_name,
            item.unit,
            item.boq_qty,
            item.used_qty,
            item.remaining_qty,
            '', // user will type requisition qty (pre-filled with remaining)
            true
        );
        tbody.insertAdjacentHTML('beforeend', html);
    });

    updateEmptyHint();
});

// ── Manual Add Row ────────────────────────────────────────────────────────
document.getElementById('prAddRow').addEventListener('click', function () {
    const html = makePrRow('', '', '-', '', '', '', '', true);
    document.getElementById('prItemsBody').insertAdjacentHTML('beforeend', html);
    updateEmptyHint();

    // Bind change on new manual material select
    const rows = document.getElementById('prItemsBody').querySelectorAll('tr');
    const newRow = rows[rows.length - 1];
    const sel = newRow.querySelector('.pr-material');
    if (sel) {
        sel.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            const unit = opt.dataset.unit || '-';
            newRow.querySelector('.pr-unit').textContent = unit;
            // Try to fill from current BOQ remaining if available
            if (currentBoqData && currentBoqData.items) {
                const mid = this.value;
                const boqItem = currentBoqData.items.find(i => i.material_id == mid);
                if (boqItem) {
                    newRow.querySelector('.pr-boq-qty').textContent  = parseFloat(boqItem.boq_qty).toFixed(3);
                    newRow.querySelector('.pr-used-qty').textContent = parseFloat(boqItem.used_qty).toFixed(3);
                    const rem = parseFloat(boqItem.remaining_qty);
                    const remCell = newRow.querySelector('.pr-remaining');
                    remCell.textContent = rem.toFixed(3);
                    remCell.className   = 'text-center pr-remaining ' + (rem <= 0 ? 'text-danger fw-bold' : 'text-success fw-bold');
                    const qtyInput = newRow.querySelector('.pr-qty');
                    if (rem > 0) { qtyInput.value = rem; qtyInput.max = rem; }
                }
            }
        });
    }
});

// ── Remove Row ────────────────────────────────────────────────────────────
document.getElementById('prItemsBody').addEventListener('click', function (e) {
    const btn = e.target.closest('.pr-remove-row');
    if (btn) {
        btn.closest('tr').remove();
        updateEmptyHint();
    }
});

// ── Form Submit validation ────────────────────────────────────────────────
document.getElementById('prForm').addEventListener('submit', function (e) {
    const rows = document.getElementById('prItemsBody').querySelectorAll('tr');
    if (rows.length === 0) {
        e.preventDefault();
        alert('Please add at least one material item.');
        return false;
    }
    let valid = true;
    rows.forEach(row => {
        const qty = parseFloat(row.querySelector('.pr-qty')?.value || 0);
        if (!qty || qty <= 0) valid = false;
    });
    if (!valid) {
        e.preventDefault();
        alert('All rows must have a valid quantity > 0.');
        return false;
    }
});

// Initial hint
updateEmptyHint();

// ── Auto-trigger if coming from Procurement Dashboard with preBoqId ──────
const PRE_BOQ_ID     = <?= $preBoqId     ? (int)$preBoqId     : 0 ?>;
const PRE_PROJECT_ID = <?= $preProjectId ? (int)$preProjectId : 0 ?>;

if (PRE_BOQ_ID && PRE_PROJECT_ID) {
    // Project is already pre-selected (PHP selected attr). Fire change to load BOQs.
    const projSel = document.getElementById('prProjectSelect');
    if (projSel && projSel.value) {
        // dispatch change to trigger AJAX BOQ load
        projSel.dispatchEvent(new Event('change'));

        // After BOQs load, auto-select the correct one
        // We re-subscribe once using MutationObserver on #prBoqSelect
        const boqSel = document.getElementById('prBoqSelect');
        const observer = new MutationObserver(() => {
            for (let i = 0; i < boqSel.options.length; i++) {
                const boqIdx = parseInt(boqSel.options[i].value);
                if (!isNaN(boqIdx) && allBoqsForProject[boqIdx] &&
                    allBoqsForProject[boqIdx].id == PRE_BOQ_ID) {
                    boqSel.value = boqIdx;
                    boqSel.dispatchEvent(new Event('change'));
                    // Auto-load items
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

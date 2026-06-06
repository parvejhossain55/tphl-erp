<?php
// PHP data injected: $projects, $archs, $designTypes, $workTypes, $workTypesGrouped, $materials, $base_url
$wtGroupedJson = json_encode($workTypesGrouped ?? [], JSON_UNESCAPED_UNICODE);
$materialsJson = json_encode(array_map(fn($m) => [
    'id'   => $m['id'],
    'name' => ($m['name'] ?? '') . ' [' . ($m['category_name'] ?? 'Uncategorized') . '] (' . ($m['unit'] ?? '') . ')',
    'unit' => $m['unit'] ?? '',
], $materials ?? []), JSON_UNESCAPED_UNICODE);
$designTypesJson = json_encode($designTypes ?? [], JSON_UNESCAPED_UNICODE);
?>
<style>
:root {
    --est-primary: #2563eb;
    --est-success: #16a34a;
    --est-warning: #d97706;
    --est-danger:  #dc2626;
    --est-purple:  var(--clr-theme-secondary);
    --dt-colors: #2563eb,#16a34a,#d97706,var(--clr-theme-secondary),#db2777,#0891b2,#ea580c,#65a30d,#9333ea;
}
.est-form-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,.08);
    margin-bottom: 1.5rem;
    overflow: hidden;
}
.est-form-card .card-hd {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: .85rem 1.25rem;
    background: #f8fafc;
    border-bottom: 1px solid #e8edf3;
    font-weight: 600;
    font-size: .93rem;
}
.est-form-card .card-bd { padding: 1.25rem; }

/* Design Type Block */
.dt-block {
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    margin-bottom: 1.25rem;
    background: #fafcff;
    overflow: hidden;
    transition: border-color .2s;
}
.dt-block:hover { border-color: #93c5fd; }
.dt-block-header {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .8rem 1rem;
    background: linear-gradient(135deg,#1e3a8a 0%,#2563eb 100%);
    color: #fff;
}
.dt-color-1 .dt-block-header { 
    background: #166534; 
}
.dt-color-2 .dt-block-header { background: linear-gradient(135deg,#14532d,#16a34a); }
.dt-color-3 .dt-block-header { background: linear-gradient(135deg,#78350f,#d97706); }
.dt-color-4 .dt-block-header { background: linear-gradient(135deg,#4c1d95,var(--clr-theme-secondary)); }
.dt-color-5 .dt-block-header { background: linear-gradient(135deg,#831843,#db2777); }
.dt-color-6 .dt-block-header { background: linear-gradient(135deg,#164e63,#0891b2); }
.dt-color-7 .dt-block-header { background: linear-gradient(135deg,#7c2d12,#ea580c); }
.dt-color-8 .dt-block-header { background: linear-gradient(135deg,#365314,#65a30d); }
.dt-block-body { padding: 1rem; }

/* Design Type dropdown — fix white-on-white in header */
.dt-block-header select.dt-select {
    background-color: rgba(0,0,0,.35) !important;
    color: #fff !important;
    border-color: rgba(255,255,255,.5) !important;
    border-radius: 6px;
    font-weight: 600;
}
.dt-block-header select.dt-select option {
    background-color: #1e293b !important;
    color: #f1f5f9 !important;
    font-weight: 500;
}

/* Work Type Block */
.wt-block {
    border: 1px solid #dde3ef;
    border-radius: 8px;
    margin-bottom: .85rem;
    background: #fff;
    overflow: hidden;
}
.wt-block-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: .55rem .9rem;
    background: #f0f4ff;
    border-bottom: 1px solid #e0e8ff;
}
.wt-block-body { padding: .6rem 0 0; }

/* QS Table */
.qs-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .82rem;
}
.qs-table th {
    background: #1e40af;
    color: #fff;
    padding: .4rem .5rem;
    font-weight: 600;
    text-align: center;
    white-space: nowrap;
}
.qs-table td {
    padding: .3rem .35rem;
    border-bottom: 1px solid #edf2f7;
    vertical-align: middle;
}
.qs-table tr:last-child td { border-bottom: none; }
.qs-table tr:hover td { background: #f0f7ff; }
.qs-table input.form-control-sm {
    height: 28px;
    font-size: .8rem;
    padding: 2px 6px;
    border-radius: 4px;
}
.qs-table select.form-select-sm {
    height: 28px;
    font-size: .8rem;
    padding: 2px 6px;
}
.vol-cell {
    font-weight: 700;
    color: #1d4ed8;
    text-align: center;
    font-size: .85rem;
    background: #eff6ff;
}
.wt-subtotal-row td {
    background: #f0fdf4;
    font-weight: 600;
    color: #166534;
    font-size: .83rem;
}

/* Labor Table */
.labor-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .82rem;
}
.labor-table th {
    background: #374151;
    color: #fff;
    padding: .4rem .5rem;
    text-align: center;
}
.labor-table td {
    padding: .3rem .35rem;
    border-bottom: 1px solid #edf2f7;
    vertical-align: middle;
}
.labor-table tr:last-child td { border-bottom: none; }

/* Summary */
.dt-summary-row {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding: .55rem .9rem;
    background: #f8fafc;
    border-top: 1px solid #e5e7eb;
    font-size: .83rem;
    flex-wrap: wrap;
}
.dt-summary-row .sum-item {
    display: flex;
    align-items: center;
    gap: .35rem;
}
.sum-badge {
    font-weight: 700;
    padding: .2rem .6rem;
    border-radius: 20px;
    font-size: .8rem;
}
.sum-mat  { background: #dbeafe; color: #1d4ed8; }
.sum-lab  { background: #dcfce7; color: #166534; }
.sum-tot  { background: #fef3c7; color: #92400e; }

/* Grand summary table */
.grand-summary-table th { background: #1e293b; color: #fff; padding: .5rem .75rem; }
.grand-summary-table td { padding: .45rem .75rem; border-bottom: 1px solid #e2e8f0; }
.grand-summary-table tr:last-child td { border-bottom: none; font-weight: 700; background: #f1f5f9; }

/* Buttons */
.btn-draft  { background: #f59e0b; border-color: #f59e0b; color: #fff; }
.btn-draft:hover { background: #d97706; border-color: #d97706; color: #fff; }
.btn-submit { background: #2563eb; border-color: #2563eb; color: #fff; }
.btn-submit:hover { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }
</style>

<div class="pagetitle d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3><?= htmlspecialchars($page_title) ?></h3>
        <nav><ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-estimates">Estimates</a></li>
            <li class="breadcrumb-item active">New Estimate</li>
        </ol></nav>
    </div>
    <a href="<?= $base_url ?>/con-estimates" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<section class="section">
<form id="estimateForm" action="<?= $base_url ?>/con-estimate-store" method="POST">
<input type="hidden" name="submit_action" id="submitAction" value="draft">
<input type="hidden" name="drawing_id"    id="primaryDrawingId" value="">

<!-- ═══════════════════════════════════════════════════════════════
     SECTION 1: Budget Header
═══════════════════════════════════════════════════════════════ -->
<div class="est-form-card">
    <div class="card-hd">
        <span><i class="bi bi-info-circle me-2 text-primary"></i>Budget Header</span>
    </div>
    <div class="card-bd">
        <div class="row ">
            <div class="col-md-5">
                <label class="form-label fw-semibold text-muted">Project <span class="text-danger">*</span></label>
                <select name="project_id" id="estimateProjectId" class="form-select" required>
                    <option value="">-- Choose Project --</option>
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($projects)): ?>
                    <small class="text-danger">No projects assigned to you yet.</small>
                <?php endif; ?>
            </div>
            <div class="col-md-5">
                <label class="form-label fw-semibold text-muted">Architecture Record <span class="text-danger">*</span></label>
                <div class="d-flex gap-2">
                    <select name="architecture_id" id="archSelect" class="form-select" required>
                        <option value="">-- Select Project First --</option>
                    </select>
                    <a href="#" target="_blank" id="archViewLink" class="btn btn-outline-secondary" style="display:none" title="View Architecture Design">
                        <i class="bi bi-eye"></i> View
                    </a>
                </div>
                <div id="archLoader" class="text-muted small mt-1" style="display:none">
                    <i class="bi bi-hourglass-split me-1"></i> Loading...
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold text-muted">Start Date</label>
                <input type="date" name="start_date" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold text-muted">End Date</label>
                <input type="date" name="end_date" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold text-muted">Total Area (sqft)</label>
                <input type="number" step="0.01" name="total_area_sqft" class="form-control" placeholder="0.00">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold text-muted">Budget Link <small class="text-secondary">(Optional)</small></label>
                <input type="url" name="budget_link" class="form-control" placeholder="Google Drive link...">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold text-muted">Other Costs (৳)</label>
                <input type="number" step="0.01" name="other_costs" id="otherCosts" class="form-control" value="0" placeholder="0.00">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold text-muted">Other Cost Detail</label>
                <input type="text" name="other_cost_detail" class="form-control" placeholder="Note...">
            </div>
            
            <!-- Project Timeline Section -->
            <div class="col-md-12 mt-3" id="projectTimelineSection" style="display:none;"></div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     SECTION 2: Design Type-wise Estimate Blocks
═══════════════════════════════════════════════════════════════ -->
<div class="est-form-card">
    <div class="card-hd">
        <span><i class="bi bi-layers me-2 text-primary"></i>Estimate Details <small class="text-muted fw-normal">(Drawing Type wise)</small></span>
        <button type="button" class="btn btn-sm btn-primary" id="addDesignTypeBtn">
            <i class="bi bi-plus-circle me-1"></i> Add Drawing Type
        </button>
    </div>
    <div class="card-bd" id="dtBlocksContainer">
        <!-- Design type blocks injected here by JS -->
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     SECTION 3: Grand Total Summary
═══════════════════════════════════════════════════════════════ -->
<div class="est-form-card">
    <div class="card-hd">
        <span><i class="bi bi-calculator me-2 text-success"></i>Cost Summary</span>
        <div>
            <span class="badge bg-primary fs-6 px-3" id="grandTotalBadge">৳ 0.00</span>
        </div>
    </div>
    <div class="card-bd p-0">
        <div class="table-responsive">
            <table class="table grand-summary-table mb-0" id="grandSummaryTable">
                <thead>
                    <tr>
                        <th>Design Type</th>
                        <th class="text-end">Material Cost</th>
                        <th class="text-end">Labor Cost</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody id="grandSummaryBody">
                    <tr><td colspan="4" class="text-center text-muted py-3">Add design type sections above to see summary</td></tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td><strong>Other Costs</strong></td>
                        <td colspan="2"></td>
                        <td class="text-end"><strong id="summaryOtherFoot">৳ 0.00</strong></td>
                    </tr>
                    <tr style="background:#1e293b;color:#fff">
                        <td><strong>GRAND TOTAL</strong></td>
                        <td class="text-end"><strong id="summaryMatFoot">৳ 0.00</strong></td>
                        <td class="text-end"><strong id="summaryLabFoot">৳ 0.00</strong></td>
                        <td class="text-end"><strong id="summaryGrandFoot">৳ 0.00</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     ACTION BUTTONS
═══════════════════════════════════════════════════════════════ -->
<div class="d-flex justify-content-between align-items-center mb-5">
    <a href="<?= $base_url ?>/con-estimates" class="btn btn-secondary">
        <i class="bi bi-x-circle me-1"></i> Cancel
    </a>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-draft px-4" id="saveDraftBtn">
            <i class="bi bi-floppy me-1"></i> Save as Draft
        </button>
        <button type="button" class="btn btn-submit px-4" id="submitBtn">
            <i class="bi bi-send-check me-1"></i> Submit for Approval
        </button>
    </div>
</div>

</form>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     JavaScript
═══════════════════════════════════════════════════════════════ -->
<script>
(function () {
'use strict';

// ── Data from PHP ──────────────────────────────────────────────────────
const BASE_URL        = '<?= $GLOBALS["base_url"] ?>';
const WT_GROUPED      = <?= $wtGroupedJson ?>;      // {design_type_id: [{id, work_type_name, design_type_id},...]}
const MATERIALS       = <?= $materialsJson ?>;       // [{id, name, unit}]
const DESIGN_TYPES    = <?= $designTypesJson ?>;     // [{id, name}]

const dtNameMap = {};
DESIGN_TYPES.forEach(dt => { dtNameMap[dt.id] = dt.name; });

const matMap = {};
MATERIALS.forEach(m => { matMap[m.id] = m; });

// ── State ──────────────────────────────────────────────────────────────
let dtBlockCount    = 0;   // total dt blocks added
let projectDrawings = [];  // [{id, design_type_id, design_type, design_link, version, status}]
let projectPhaseItems = []; // all phase items for the project
let dtColors        = ['dt-color-1','dt-color-2','dt-color-3','dt-color-4',
                        'dt-color-5','dt-color-6','dt-color-7','dt-color-8'];

// ── Format number ─────────────────────────────────────────────────────
function fmt(n) {
    return parseFloat(n || 0).toLocaleString('en-BD', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function fmtQty(n) {
    return parseFloat(n || 0).toLocaleString('en-BD', { minimumFractionDigits: 3, maximumFractionDigits: 3 });
}

// ── Build Design Type block HTML ────────────────────────────────────────
function buildDtBlock(blockIdx) {
    const colorClass = dtColors[(blockIdx - 1) % dtColors.length];

    // Build options from PROJECT drawings only (filtered)
    let dtOptions = '';
    if (projectDrawings.length) {
        dtOptions = projectDrawings.map(d =>
            `<option value="${d.design_type_id}"
                     data-link="${d.design_link || ''}"
                     data-drawing-id="${d.id}"
                     data-version="${d.version || ''}"
                     style="background:#1e293b;color:#f1f5f9">
                ${d.design_type} ${d.version ? '(v'+d.version+')' : ''} — ${d.status}
             </option>`
        ).join('');
    } else {
        // fallback: all design types (when project not yet selected)
        dtOptions = DESIGN_TYPES.map(dt =>
            `<option value="${dt.id}" style="background:#1e293b;color:#f1f5f9">${dt.name}</option>`
        ).join('');
    }

    return `
    <div class="dt-block ${colorClass}" id="dt-block-${blockIdx}" data-block="${blockIdx}">
        <!-- Header -->
        <div class="dt-block-header">
            <i class="bi bi-layers fs-5"></i>
            <div class="flex-grow-1">
                <label class="text-white small mb-0 opacity-75">Design Type</label>
                <select name="dt_select_${blockIdx}" id="dt-select-${blockIdx}"
                        class="form-select form-select-sm dt-select"
                        style="max-width:320px"
                        onchange="onDtChange(${blockIdx})">
                    <option value="" style="background:#1e293b;color:#f1f5f9">-- Select Design Type --</option>
                    ${dtOptions}
                </select>
            </div>
            <!-- View Drawing Button -->
            <a href="#" target="_blank" id="dt-view-link-${blockIdx}"
               class="btn btn-sm ms-2"
               style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.4);color:#fff;display:none"
               title="View Design Drawing">
                <i class="bi bi-eye me-1"></i>View Drawing
            </a>
            <button type="button" class="btn btn-sm btn-light ms-2"
                    onclick="removeDtBlock(${blockIdx})" title="Remove this design type">
                <i class="bi bi-trash3"></i>
            </button>
        </div>

        <!-- Custom Link Row (shown after design type selected) -->
        <div id="dt-link-row-${blockIdx}"
             style="display:none;background:rgba(0,0,0,.18);padding:.5rem 1rem;border-bottom:1px solid rgba(255,255,255,.1)">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-link-45deg text-white opacity-75"></i>
                <span class="text-white small opacity-75" style="white-space:nowrap">Drawing Link:</span>
                <input type="url"
                       id="dt-custom-link-${blockIdx}"
                       name="dt_custom_link[]"
                       class="form-control form-control-sm"
                       style="background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.3);color:#000;max-width:480px"
                       placeholder="https://drive.google.com/... (link paste করুন)"
                       oninput="syncDtViewBtn(${blockIdx})">
                <a href="#" target="_blank" id="dt-custom-view-${blockIdx}"
                   class="btn btn-sm"
                   style="background:#198754;border:1px solid #198754;color:#fff;display:none;white-space:nowrap">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Open
                </a>
                <input type="hidden" name="dt_design_type_id_link[]" id="dt-dtid-link-${blockIdx}" value="">
            </div>
        </div>

        <!-- Phase Info Row (shown after design type selected) -->
        <div id="dt-phase-row-${blockIdx}"
             style="display:none;background:#f8fafc;padding:.6rem 1rem;border-bottom:1px solid #e2e8f0;font-size:.85rem;color:#1e293b">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div>
                    <span class="text-muted fw-semibold"><i class="bi bi-calendar-event me-1"></i>Phase:</span>
                    <span id="dt-phase-name-${blockIdx}" class="badge bg-primary px-2 py-1 ms-1" style="font-size: .8rem;">-</span>
                </div>
                <div>
                    <span class="text-muted fw-semibold"><i class="bi bi-percent me-1"></i>Phase Weight:</span>
                    <span id="dt-phase-weight-${blockIdx}" class="badge bg-info  px-2 py-1 ms-1" style="font-size: .8rem;">-</span>
                </div>
            </div>
        </div>

        <!-- Body -->
        <div class="dt-block-body" id="dt-body-${blockIdx}">

            <!-- Work Type Blocks container -->
            <div id="wt-blocks-${blockIdx}">
                <!-- First work type block -->
            </div>

            <!-- Add Work Type button -->
            <button type="button" class="btn btn-sm btn-outline-primary mb-2 mt-1"
                    onclick="addWtBlock(${blockIdx})">
                <i class="bi bi-plus-circle me-1"></i> Add Work Type Block
            </button>

            <!-- Labor section for this design type -->
            <div class="mt-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fw-semibold text-muted small"><i class="bi bi-people me-1"></i>Labor Costs</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                            onclick="addLaborRow(${blockIdx})">
                        <i class="bi bi-plus-circle me-1"></i> Add Labor Row
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="labor-table" id="labor-table-${blockIdx}">
                        <thead>
                            <tr>
                                <th style="width:30%">Labor Type / Phase Item</th>
                                <th style="width:15%">Description</th>
                                <th style="width:15%">Rate/sqft (৳)</th>
                                <th style="width:15%">Area (sqft)</th>
                                <th style="width:15%">Subtotal</th>
                                <th style="width:10%">Del</th>
                            </tr>
                        </thead>
                        <tbody id="labor-body-${blockIdx}">
                            <!-- labor rows here -->
                        </tbody>
                        <tfoot>
                            <tr style="background:#f0fdf4">
                                <td colspan="4" class="text-end fw-bold py-2 pe-3 text-success">
                                    Labor Total:
                                </td>
                                <td class="fw-bold text-success" id="labor-total-${blockIdx}">0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Design Type Subtotal bar -->
        <div class="dt-summary-row">
            <div class="sum-item">
                <span class="text-muted">Material:</span>
                <span class="sum-badge sum-mat" id="dt-mat-${blockIdx}">৳ 0.00</span>
            </div>
            <div class="sum-item">
                <span class="text-muted">Labor:</span>
                <span class="sum-badge sum-lab" id="dt-lab-${blockIdx}">৳ 0.00</span>
            </div>
            <div class="sum-item">
                <span class="text-muted">Total:</span>
                <span class="sum-badge sum-tot" id="dt-tot-${blockIdx}">৳ 0.00</span>
            </div>
        </div>
    </div>`;
}

// ── Add a Design Type block ────────────────────────────────────────────
function addDtBlock() {
    if (!document.getElementById('estimateProjectId').value) {
        alert('অনুগ্রহ করে আগে Project select করুন।');
        document.getElementById('estimateProjectId').focus();
        return;
    }
    dtBlockCount++;
    const container = document.getElementById('dtBlocksContainer');
    const div       = document.createElement('div');
    div.innerHTML   = buildDtBlock(dtBlockCount);
    container.appendChild(div.firstElementChild);
    // Add first work type block automatically
    addWtBlock(dtBlockCount);
}

// ── Remove a Design Type block ─────────────────────────────────────────
function removeDtBlock(blockIdx) {
    const el = document.getElementById('dt-block-' + blockIdx);
    if (el) { el.remove(); recalcGrand(); }
}

// ── Design Type select change ──────────────────────────────────────────
function onDtChange(blockIdx) {
    const sel     = document.getElementById('dt-select-' + blockIdx);
    const selOpt  = sel ? sel.options[sel.selectedIndex] : null;
    const dtId    = selOpt ? selOpt.value : '';
    const link    = selOpt ? (selOpt.getAttribute('data-link') || '') : '';

    // ── View Drawing button (from project drawing) ─────────────────────
    const viewBtn = document.getElementById('dt-view-link-' + blockIdx);
    if (viewBtn) {
        if (link && link !== 'null' && link.trim()) {
            viewBtn.href         = link;
            viewBtn.style.display = '';
        } else {
            viewBtn.href         = '#';
            viewBtn.style.display = 'none';
        }
    }

    // ── Custom link row ────────────────────────────────────────────────
    const linkRow    = document.getElementById('dt-link-row-'    + blockIdx);
    const linkInput  = document.getElementById('dt-custom-link-' + blockIdx);
    const dtidHidden = document.getElementById('dt-dtid-link-'   + blockIdx);

    if (dtId) {
        if (linkRow) linkRow.style.display = '';
        // Pre-fill with the project drawing link if input is empty
        if (linkInput && !linkInput.value && link && link !== 'null') {
            linkInput.value = link;
            syncDtViewBtn(blockIdx);
        }
        if (dtidHidden) dtidHidden.value = dtId;

        // Save drawing_id from this option into the budget header hidden field
        // Priority: first dt-block that has a drawing_id wins
        const drawingId = selOpt ? (selOpt.getAttribute('data-drawing-id') || '') : '';
        const primaryField = document.getElementById('primaryDrawingId');
        if (primaryField && drawingId && !primaryField.value) {
            primaryField.value = drawingId;
        }
        // If this is the first block (blockIdx==1) always set it
        if (primaryField && drawingId && String(blockIdx) === '1') {
            primaryField.value = drawingId;
        }
    } else {
        if (linkRow)    linkRow.style.display = 'none';
        if (linkInput)  linkInput.value = '';
        if (dtidHidden) dtidHidden.value = '';
    }

    // ── Render Phase Info ──────────────────────────────────────────────
    const drawing = projectDrawings.find(d => String(d.design_type_id) === String(dtId));
    const phaseRow = document.getElementById('dt-phase-row-' + blockIdx);
    const phaseNameSpan = document.getElementById('dt-phase-name-' + blockIdx);
    const phaseWeightSpan = document.getElementById('dt-phase-weight-' + blockIdx);

    if (drawing && drawing.phase_id) {
        if (phaseRow) phaseRow.style.display = '';
        if (phaseNameSpan) phaseNameSpan.textContent = drawing.phase_name || 'N/A';
        if (phaseWeightSpan) phaseWeightSpan.textContent = (drawing.phase_weight || '0') + '%';
    } else {
        if (phaseRow) phaseRow.style.display = 'none';
    }

    // ── Refresh phase item select in all work type blocks under this dt block ────────
    const body = document.getElementById('wt-blocks-' + blockIdx);
    if (body) {
        body.querySelectorAll('.phase-item-select').forEach(s => {
            const blockKey = s.id.replace('phase-item-select-', '');
            const curVal = s.value;
            refreshPhaseItemOptions(s, blockIdx, curVal);
        });
    }

    // ── Refresh labor phase item dropdowns ───────────────────────────────
    const laborBody = document.getElementById('labor-body-' + blockIdx);
    if (laborBody) {
        laborBody.querySelectorAll('.labor-phase-item-select').forEach(s => {
            const curVal = s.value;
            refreshLaborPhaseItemOptions(s, blockIdx, curVal);
        });
    }

    // ── Refresh work type dropdowns inside this dt block ───────────────
    if (body) {
        body.querySelectorAll('.wt-select').forEach(s => { refreshWtOptions(s, blockIdx); });
    }
}

// ── Sync the "Open" button next to custom link input ──────────────────
function syncDtViewBtn(blockIdx) {
    const input   = document.getElementById('dt-custom-link-' + blockIdx);
    const openBtn = document.getElementById('dt-custom-view-'  + blockIdx);
    if (!input || !openBtn) return;
    const val = input.value.trim();
    if (val && (val.startsWith('http://') || val.startsWith('https://'))) {
        openBtn.href         = val;
        openBtn.style.display = '';
    } else {
        openBtn.href         = '#';
        openBtn.style.display = 'none';
    }
}

// ── Refresh all dt selects when drawings change ────────────────────────
function refreshAllDtSelects() {
    document.querySelectorAll('.dt-block').forEach(block => {
        const idx    = block.dataset.block;
        const sel    = document.getElementById('dt-select-' + idx);
        if (!sel) return;
        const curVal = sel.value;

        let dtOptions = '';
        if (projectDrawings.length) {
            dtOptions = projectDrawings.map(d =>
                `<option value="${d.design_type_id}"
                         data-link="${d.design_link || ''}"
                         data-drawing-id="${d.id}"
                         data-version="${d.version || ''}"
                         style="background:#1e293b;color:#f1f5f9">
                    ${d.design_type} ${d.version ? '(v'+d.version+')' : ''} — ${d.status}
                 </option>`
            ).join('');
        } else {
            dtOptions = DESIGN_TYPES.map(dt =>
                `<option value="${dt.id}" style="background:#1e293b;color:#f1f5f9">${dt.name}</option>`
            ).join('');
        }

        sel.innerHTML = `<option value="" style="background:#1e293b;color:#f1f5f9">-- Select Design Type --</option>${dtOptions}`;
        if (curVal) sel.value = curVal;
        onDtChange(idx);  // refresh view button
    });
}

// ── Build Work Type block ──────────────────────────────────────────────
let wtCounts = {};  // {dtBlockIdx: count}
function addWtBlock(dtBlockIdx) {
    wtCounts[dtBlockIdx] = (wtCounts[dtBlockIdx] || 0) + 1;
    const wtIdx    = wtCounts[dtBlockIdx];
    const blockKey = `${dtBlockIdx}_${wtIdx}`;

    const html = `
    <div class="wt-block" id="wt-block-${blockKey}">
        <div class="wt-block-header">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-layers text-primary"></i>
                <select id="phase-item-select-${blockKey}" class="form-select form-select-sm phase-item-select"
                        style="max-width:240px" onchange="onPhaseItemChange('${blockKey}', ${dtBlockIdx})">
                    <option value="">-- Select Phase Item --</option>
                </select>
                
                <i class="bi bi-tools text-secondary"></i>
                <select id="wt-select-${blockKey}" class="form-select form-select-sm wt-select"
                        style="max-width:240px" onchange="recalcGrand()">
                    <option value="">-- Select Work Type --</option>
                </select>
            </div>
            <div class="d-flex gap-1">
                <button type="button" class="btn btn-sm btn-outline-primary"
                        onclick="addQsRow('${blockKey}', ${dtBlockIdx})">
                    <i class="bi bi-plus-circle"></i> Add Row
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger"
                        onclick="removeWtBlock('${blockKey}', ${dtBlockIdx})">
                    <i class="bi bi-trash3"></i>
                </button>
            </div>
        </div>
        <div class="wt-block-body table-responsive">
            <table class="qs-table w-100" id="qs-table-${blockKey}">
                <thead>
                    <tr>
                        <th style="width:35px">SL</th>
                        <th style="width:22%">Location / Description</th>
                        <th style="width:8%">Unit</th>
                        <th style="width:6%">Nos</th>
                        <th style="width:7%">L (ft)</th>
                        <th style="width:7%">W (ft)</th>
                        <th style="width:7%">H (ft)</th>
                        <th style="width:9%">Volume/Qty</th>
                        <th style="width:12%">Material</th>
                        <th style="width:9%">Rate (৳)</th>
                        <th style="width:9%">Subtotal</th>
                        <th style="width:28px">Del</th>
                    </tr>
                </thead>
                <tbody id="qs-body-${blockKey}">
                </tbody>
                <tfoot>
                    <tr class="wt-subtotal-row">
                        <td colspan="10" class="text-end pe-3">Work Type Subtotal:</td>
                        <td id="wt-subtotal-${blockKey}">0.000</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>`;

    const container = document.getElementById('wt-blocks-' + dtBlockIdx);
    const div = document.createElement('div');
    div.innerHTML = html;
    container.appendChild(div.firstElementChild);

    // Populate phase item dropdown
    const phaseSel = document.getElementById('phase-item-select-' + blockKey);
    refreshPhaseItemOptions(phaseSel, dtBlockIdx);

    // Populate work type dropdown
    const sel = document.getElementById('wt-select-' + blockKey);
    refreshWtOptions(sel, dtBlockIdx);

    // Add first row
    addQsRow(blockKey, dtBlockIdx);
}

// ── Refresh work type dropdown options ────────────────────────────────
function refreshWtOptions(sel, dtBlockIdx) {
    const dtSel   = document.getElementById('dt-select-' + dtBlockIdx);
    const dtId    = dtSel ? dtSel.value : '';
    const current = sel.value;

    sel.innerHTML = '<option value="">-- Work Type --</option>';

    const wts = dtId ? (WT_GROUPED[dtId] || []) : Object.values(WT_GROUPED).flat();
    wts.forEach(wt => {
        const opt = document.createElement('option');
        opt.value = wt.id;
        opt.textContent = wt.work_type_name;
        if (String(wt.id) === String(current)) opt.selected = true;
        sel.appendChild(opt);
    });
}

// ── Remove Work Type block ─────────────────────────────────────────────
function removeWtBlock(blockKey, dtBlockIdx) {
    const el = document.getElementById('wt-block-' + blockKey);
    if (el) { el.remove(); recalcGrand(); }
}

// ── Add QS Row ─────────────────────────────────────────────────────────
let rowCounts = {};
function addQsRow(blockKey, dtBlockIdx) {
    rowCounts[blockKey] = (rowCounts[blockKey] || 0) + 1;
    const sl   = rowCounts[blockKey];
    const tbody = document.getElementById('qs-body-' + blockKey);

    const matOptions = MATERIALS.map(m =>
        `<option value="${m.id}" data-unit="${m.unit}">${m.name}</option>`
    ).join('');

    const dtId = (() => {
        const s = document.getElementById('dt-select-' + dtBlockIdx);
        return s ? s.value : '';
    })();
    const wtId = (() => {
        const s = document.getElementById('wt-select-' + blockKey);
        return s ? s.value : '';
    })();

    const tr = document.createElement('tr');
    tr.className = 'qs-row';
    tr.dataset.blockKey  = blockKey;
    tr.dataset.dtBlockIdx = dtBlockIdx;

    tr.innerHTML = `
        <td class="text-center text-muted sl-cell">${sl}</td>
        <td><input type="text" name="item_location[]" class="form-control form-control-sm qs-location"
                   placeholder="e.g., Pile cap-08 [10 Nos]"></td>
        <td><input type="text" name="item_unit[]" class="form-control form-control-sm qs-unit"
                   placeholder="Cft" style="width:55px"></td>
        <td><input type="number" name="item_nos[]" step="0.001" min="0"
                   class="form-control form-control-sm qs-nos" placeholder="1" oninput="calcVol(this)"></td>
        <td><input type="number" name="item_length[]" step="0.001" min="0"
                   class="form-control form-control-sm qs-length" placeholder="—" oninput="calcVol(this)"></td>
        <td><input type="number" name="item_width[]"  step="0.001" min="0"
                   class="form-control form-control-sm qs-width" placeholder="—" oninput="calcVol(this)"></td>
        <td><input type="number" name="item_height[]" step="0.001" min="0"
                   class="form-control form-control-sm qs-height" placeholder="—" oninput="calcVol(this)"></td>
        <td class="vol-cell">
            <span class="qs-vol-display">0.000</span>
            <input type="hidden" name="item_volume[]" class="qs-vol-hidden" value="0">
        </td>
        <td>
            <select name="item_material_id[]" class="form-select form-select-sm qs-material"
                    onchange="onMaterialChange(this)">
                <option value="">-- Material --</option>
                ${matOptions}
            </select>
        </td>
        <td><input type="number" name="item_unit_cost[]" step="0.01" min="0"
                   class="form-control form-control-sm qs-cost" placeholder="0.00" oninput="calcSubtotal(this)"></td>
        <td class="text-end fw-bold qs-subtotal">0.00</td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger p-0 px-1"
                    onclick="removeQsRow(this)" title="Remove row">
                <i class="bi bi-x"></i>
            </button>
        </td>
        <!-- Hidden fields for design_type_id, work_type_id, sl_no, phase_item_id -->
        <input type="hidden" name="item_design_type_id[]" class="qs-dtid" value="${dtId}">
        <input type="hidden" name="item_work_type_id[]"   class="qs-wtid" value="${wtId}">
        <input type="hidden" name="item_phase_item_id[]"   class="qs-phase-item-id" value="">
        <input type="hidden" name="item_sl_no[]"          class="qs-slno" value="${sl}">
        <input type="hidden" name="item_remarks[]"        class="qs-rem"  value="">`;

    tbody.appendChild(tr);
    reNumberRows(blockKey);
}

// ── Recalculate volume for a row ────────────────────────────────────────
function calcVol(input) {
    const tr  = input.closest('tr.qs-row');
    if (!tr) return;
    const nos = parseFloat(tr.querySelector('.qs-nos').value)    || 0;
    const l   = parseFloat(tr.querySelector('.qs-length').value) || 0;
    const w   = parseFloat(tr.querySelector('.qs-width').value)  || 0;
    const h   = parseFloat(tr.querySelector('.qs-height').value) || 0;
    // Only multiply non-zero dimensions (H=0 means 2D, so skip H)
    let vol = nos;
    if (l > 0) vol *= l;
    if (w > 0) vol *= w;
    if (h > 0) vol *= h;
    const finalVol = (nos > 0) ? vol : 0;
    tr.querySelector('.qs-vol-display').textContent = fmtQty(finalVol);
    tr.querySelector('.qs-vol-hidden').value        = finalVol.toFixed(3);
    calcSubtotal(tr.querySelector('.qs-cost'));
}

// ── Recalculate subtotal for a row ─────────────────────────────────────
function calcSubtotal(input) {
    if (!input) return;
    const tr   = input.closest('tr.qs-row');
    if (!tr) return;
    const vol  = parseFloat(tr.querySelector('.qs-vol-hidden').value) || 0;
    const cost = parseFloat(tr.querySelector('.qs-cost').value)       || 0;
    const sub  = vol * cost;
    tr.querySelector('.qs-subtotal').textContent = fmt(sub);
    const bk   = tr.dataset.blockKey;
    recalcWtSubtotal(bk);
    recalcDtBlock(tr.dataset.dtBlockIdx);
    recalcGrand();
}

// ── Material select change → auto-fill unit ────────────────────────────
function onMaterialChange(sel) {
    const opt  = sel.options[sel.selectedIndex];
    const unit = opt ? (opt.getAttribute('data-unit') || '') : '';
    const tr   = sel.closest('tr.qs-row');
    if (tr) tr.querySelector('.qs-unit').value = unit;
}

// ── Recalculate work type subtotal ─────────────────────────────────────
function recalcWtSubtotal(blockKey) {
    const tbody = document.getElementById('qs-body-' + blockKey);
    if (!tbody) return 0;
    let total = 0;
    tbody.querySelectorAll('.qs-row').forEach(tr => {
        const vol  = parseFloat(tr.querySelector('.qs-vol-hidden').value) || 0;
        const cost = parseFloat(tr.querySelector('.qs-cost').value)       || 0;
        total += vol * cost;
    });
    const footEl = document.getElementById('wt-subtotal-' + blockKey);
    if (footEl) footEl.textContent = fmtQty(total);
    return total;
}

// ── Recalculate Design Type block totals ───────────────────────────────
function recalcDtBlock(dtBlockIdx) {
    // Material total = sum of all wt-subtotals in this dt block
    let matTotal = 0;
    const wtContainer = document.getElementById('wt-blocks-' + dtBlockIdx);
    if (wtContainer) {
        wtContainer.querySelectorAll('.qs-row').forEach(tr => {
            const vol  = parseFloat(tr.querySelector('.qs-vol-hidden').value) || 0;
            const cost = parseFloat(tr.querySelector('.qs-cost').value)       || 0;
            matTotal += vol * cost;
        });
    }
    // Labor total
    const labTotal = recalcLaborBlock(dtBlockIdx);

    // Update displayed subtotals
    const matEl = document.getElementById('dt-mat-' + dtBlockIdx);
    const labEl = document.getElementById('dt-lab-' + dtBlockIdx);
    const totEl = document.getElementById('dt-tot-' + dtBlockIdx);
    if (matEl) matEl.textContent = '৳ ' + fmt(matTotal);
    if (labEl) labEl.textContent = '৳ ' + fmt(labTotal);
    if (totEl) totEl.textContent = '৳ ' + fmt(matTotal + labTotal);

    return { mat: matTotal, lab: labTotal };
}

// ── Remove QS row ─────────────────────────────────────────────────────
function removeQsRow(btn) {
    const tr = btn.closest('tr.qs-row');
    if (!tr) return;
    const bk     = tr.dataset.blockKey;
    const dtIdx  = tr.dataset.dtBlockIdx;
    tr.remove();
    reNumberRows(bk);
    recalcWtSubtotal(bk);
    recalcDtBlock(dtIdx);
    recalcGrand();
}

// ── Renumber SL column ────────────────────────────────────────────────
function reNumberRows(blockKey) {
    const tbody = document.getElementById('qs-body-' + blockKey);
    if (!tbody) return;
    tbody.querySelectorAll('.qs-row').forEach((tr, i) => {
        const sl = i + 1;
        tr.querySelector('.sl-cell').textContent = sl;
        const slHidden = tr.querySelector('.qs-slno');
        if (slHidden) slHidden.value = sl;
    });
    rowCounts[blockKey] = tbody.querySelectorAll('.qs-row').length;
}

// ── Phase Item Helpers ────────────────────────────────────────────────
function refreshPhaseItemOptions(sel, dtBlockIdx, selectedPhaseItemId = '') {
    const dtSel = document.getElementById('dt-select-' + dtBlockIdx);
    const dtId = dtSel ? dtSel.value : '';
    const drawing = projectDrawings.find(d => String(d.design_type_id) === String(dtId));
    const phaseId = drawing ? drawing.phase_id : '';

    sel.innerHTML = '<option value="">-- Select Phase Item --</option>';

    if (phaseId) {
        const filtered = projectPhaseItems.filter(item => String(item.phase_id) === String(phaseId));
        filtered.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.item_name;
            if (String(item.id) === String(selectedPhaseItemId)) opt.selected = true;
            sel.appendChild(opt);
        });
    }
}

function refreshLaborPhaseItemOptions(sel, dtBlockIdx, selectedPhaseItemId = '') {
    const dtSel = document.getElementById('dt-select-' + dtBlockIdx);
    const dtId = dtSel ? dtSel.value : '';
    const drawing = projectDrawings.find(d => String(d.design_type_id) === String(dtId));
    const phaseId = drawing ? drawing.phase_id : '';

    sel.innerHTML = '<option value="">-- Phase Item --</option>';

    if (phaseId) {
        const filtered = projectPhaseItems.filter(item => String(item.phase_id) === String(phaseId));
        filtered.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.item_name;
            if (String(item.id) === String(selectedPhaseItemId)) opt.selected = true;
            sel.appendChild(opt);
        });
    }
}

function onPhaseItemChange(blockKey, dtBlockIdx) {
    const phaseItemSel = document.getElementById('phase-item-select-' + blockKey);
    const phaseItemId = phaseItemSel ? phaseItemSel.value : '';
    const wtSel = document.getElementById('wt-select-' + blockKey);

    if (phaseItemId && wtSel) {
        const phaseItem = projectPhaseItems.find(item => String(item.id) === String(phaseItemId));
        if (phaseItem && phaseItem.work_type_id) {
            wtSel.value = phaseItem.work_type_id;
        }
    }
    recalcGrand();
}

function onLaborPhaseItemChange(sel) {
    const phaseItemId = sel.value;
    const tr = sel.closest('tr.labor-row');
    if (!tr) return;
    const hiddenWt = tr.querySelector('input[name="labor_work_type_id[]"]');
    
    if (phaseItemId && hiddenWt) {
        const phaseItem = projectPhaseItems.find(item => String(item.id) === String(phaseItemId));
        if (phaseItem && phaseItem.work_type_id) {
            hiddenWt.value = phaseItem.work_type_id;
        } else {
            hiddenWt.value = '';
        }
    } else if (hiddenWt) {
        hiddenWt.value = '';
    }
}

// ── Add Labor Row ─────────────────────────────────────────────────────
function addLaborRow(dtBlockIdx, existingLabor) {
    const ex = existingLabor || {};
    const tbody = document.getElementById('labor-body-' + dtBlockIdx);
    if (!tbody) return;
    const dtSel = document.getElementById('dt-select-' + dtBlockIdx);
    const dtId  = dtSel ? dtSel.value : '';

    const drawing = projectDrawings.find(d => String(d.design_type_id) === String(dtId));
    const phaseId = drawing ? drawing.phase_id : '';
    
    let phaseItemOpts = '<option value="">-- Phase Item --</option>';
    if (phaseId) {
        const filtered = projectPhaseItems.filter(item => String(item.phase_id) === String(phaseId));
        phaseItemOpts += filtered.map(item => {
            const sel = String(item.id) === String(ex.phase_item_id || '') ? 'selected' : '';
            return `<option value="${item.id}" ${sel}>${item.item_name}</option>`;
        }).join('');
    }

    const tr = document.createElement('tr');
    tr.className = 'labor-row';
    tr.dataset.dtBlockIdx = dtBlockIdx;
    tr.innerHTML = `
        <td>
            <input type="text" name="labor_type_name[]"
                   class="form-control form-control-sm mb-1" placeholder="e.g., Civil Work, Painting"
                   value="${ex.labor_type_name || ''}">
            <select name="labor_phase_item_id[]" class="form-select form-select-sm labor-phase-item-select" onchange="onLaborPhaseItemChange(this)">
                ${phaseItemOpts}
            </select>
            <input type="hidden" name="labor_design_type_id[]" value="${dtId}">
            <input type="hidden" name="labor_work_type_id[]" value="${ex.work_type_id || ''}">
        </td>
        <td><input type="text" name="labor_description[]" class="form-control form-control-sm"
                   value="${ex.description || ''}" placeholder="Note..."></td>
        <td><input type="number" step="0.01" name="labor_rate_per_sqft[]"
                   class="form-control form-control-sm lab-rate" placeholder="0.00" min="0"
                   value="${ex.rate_per_sqft || ''}" oninput="calcLaborRow(this)"></td>
        <td><input type="number" step="0.01" name="labor_area_sqft[]"
                   class="form-control form-control-sm lab-area" placeholder="0.00" min="0"
                   value="${ex.area_sqft || ''}" oninput="calcLaborRow(this)"></td>
        <td class="fw-bold lab-subtotal">0.00</td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger p-0 px-1"
                    onclick="removeLaborRow(this, ${dtBlockIdx})">
                <i class="bi bi-x"></i>
            </button>
        </td>`;
    tbody.appendChild(tr);
    if (ex.rate_per_sqft || ex.area_sqft) {
        calcLaborRow(tr.querySelector('.lab-rate'));
    }
}

function calcLaborRow(input) {
    const tr   = input.closest('tr.labor-row');
    if (!tr) return;
    const rate = parseFloat(tr.querySelector('.lab-rate').value) || 0;
    const area = parseFloat(tr.querySelector('.lab-area').value) || 0;
    tr.querySelector('.lab-subtotal').textContent = fmt(rate * area);
    const dtIdx = tr.dataset.dtBlockIdx;
    recalcLaborBlock(dtIdx);
    recalcDtBlock(dtIdx);
    recalcGrand();
}

function removeLaborRow(btn, dtBlockIdx) {
    const tr = btn.closest('tr.labor-row');
    if (tr) { tr.remove(); recalcLaborBlock(dtBlockIdx); recalcDtBlock(dtBlockIdx); recalcGrand(); }
}

function recalcLaborBlock(dtBlockIdx) {
    const tbody = document.getElementById('labor-body-' + dtBlockIdx);
    if (!tbody) return 0;
    let total = 0;
    tbody.querySelectorAll('.labor-row').forEach(tr => {
        const rate = parseFloat(tr.querySelector('.lab-rate').value) || 0;
        const area = parseFloat(tr.querySelector('.lab-area').value) || 0;
        total += rate * area;
    });
    const el = document.getElementById('labor-total-' + dtBlockIdx);
    if (el) el.textContent = fmt(total);
    return total;
}

// ── Grand Total Recalc ─────────────────────────────────────────────────
function recalcGrand() {
    const container = document.getElementById('dtBlocksContainer');
    const tbody     = document.getElementById('grandSummaryBody');
    const otherEl   = document.getElementById('otherCosts');
    const otherCosts= otherEl ? (parseFloat(otherEl.value) || 0) : 0;

    let grandMat = 0, grandLab = 0;
    let rows = '';

    container.querySelectorAll('.dt-block').forEach(block => {
        const idx   = block.dataset.block;
        const dtSel = document.getElementById('dt-select-' + idx);
        const dtName= dtSel && dtSel.value ? (dtNameMap[dtSel.value] || 'Design Type #' + dtSel.value) : '(Not selected)';

        // Mat
        let mat = 0;
        const wtContainer = document.getElementById('wt-blocks-' + idx);
        if (wtContainer) {
            wtContainer.querySelectorAll('.qs-row').forEach(tr => {
                const vol  = parseFloat(tr.querySelector('.qs-vol-hidden').value) || 0;
                const cost = parseFloat(tr.querySelector('.qs-cost').value)       || 0;
                mat += vol * cost;
            });
        }
        // Lab
        let lab = 0;
        const labBody = document.getElementById('labor-body-' + idx);
        if (labBody) {
            labBody.querySelectorAll('.labor-row').forEach(tr => {
                const rate = parseFloat(tr.querySelector('.lab-rate').value) || 0;
                const area = parseFloat(tr.querySelector('.lab-area').value) || 0;
                lab += rate * area;
            });
        }

        grandMat += mat;
        grandLab += lab;
        rows += `<tr>
            <td>${dtName}</td>
            <td class="text-end">৳ ${fmt(mat)}</td>
            <td class="text-end">৳ ${fmt(lab)}</td>
            <td class="text-end fw-semibold">৳ ${fmt(mat + lab)}</td>
        </tr>`;
    });

    tbody.innerHTML = rows || '<tr><td colspan="4" class="text-center text-muted py-3">Add design type sections above</td></tr>';

    const grand = grandMat + grandLab + otherCosts;
    document.getElementById('summaryMatFoot').textContent   = '৳ ' + fmt(grandMat);
    document.getElementById('summaryLabFoot').textContent   = '৳ ' + fmt(grandLab);
    document.getElementById('summaryOtherFoot').textContent = '৳ ' + fmt(otherCosts);
    document.getElementById('summaryGrandFoot').textContent = '৳ ' + fmt(grand);
    document.getElementById('grandTotalBadge').textContent  = '৳ ' + fmt(grand);
}

// ── Update hidden dt/wt ids before submit ──────────────────────────────
function syncHiddenFields() {
    document.querySelectorAll('.qs-row').forEach(tr => {
        const dtIdx = tr.dataset.dtBlockIdx;
        const bk    = tr.dataset.blockKey;
        const dtSel = document.getElementById('dt-select-' + dtIdx);
        const wtSel = document.getElementById('wt-select-' + bk);
        const phaseItemSel = document.getElementById('phase-item-select-' + bk);
        
        if (dtSel) tr.querySelector('.qs-dtid').value = dtSel.value;
        if (wtSel) tr.querySelector('.qs-wtid').value = wtSel.value;
        if (phaseItemSel) {
            const hiddenPhaseItem = tr.querySelector('.qs-phase-item-id');
            if (hiddenPhaseItem) hiddenPhaseItem.value = phaseItemSel.value;
        }
    });
    // Sync labor design type and work type IDs
    document.querySelectorAll('.labor-row').forEach(tr => {
        const dtIdx = tr.dataset.dtBlockIdx;
        const dtSel = document.getElementById('dt-select-' + dtIdx);
        const hiddenDt = tr.querySelector('input[name="labor_design_type_id[]"]');
        if (dtSel && hiddenDt) hiddenDt.value = dtSel.value;
    });
}

// ── AJAX: Project → Architecture + Drawings ───────────────────────────
document.getElementById('estimateProjectId').addEventListener('change', function () {
    const pid     = this.value;
    const archSel = document.getElementById('archSelect');
    const loader  = document.getElementById('archLoader');

    // Reset
    archSel.innerHTML  = '<option value="">-- Loading... --</option>';
    archSel.disabled   = true;
    projectDrawings    = [];
    const archViewLink = document.getElementById('archViewLink');
    if (archViewLink) {
        archViewLink.href = '#';
        archViewLink.style.display = 'none';
    }

    const timelineSec = document.getElementById('projectTimelineSection');
    if (timelineSec) {
        timelineSec.innerHTML = '';
        timelineSec.style.display = 'none';
    }

    // Clear all existing dt blocks and notify
    document.getElementById('dtBlocksContainer').innerHTML =
        '<div class="text-muted text-center py-3 small"><i class="bi bi-hourglass-split me-1"></i>Loading project designs...</div>';
    dtBlockCount = 0;

    if (!pid) {
        archSel.innerHTML = '<option value="">-- Select Project First --</option>';
        archSel.disabled  = false;
        document.getElementById('dtBlocksContainer').innerHTML = '';
        return;
    }
    loader.style.display = 'block';

    // Fetch arch records
    const archFetch = fetch(BASE_URL + '/con-ajax-arch-by-project?project_id=' + pid)
        .then(r => r.json());

    // Fetch project drawings (design types)
    const drawFetch = fetch(BASE_URL + '/con-ajax-designs-by-project?project_id=' + pid)
        .then(r => r.json());

    Promise.all([archFetch, drawFetch]).then(([archs, drawData]) => {
        loader.style.display = 'none';

        // ── Architecture dropdown ─────────────────────────────────────
        if (!archs.length) {
            archSel.innerHTML = '<option value="">-- No Architecture Record --</option>';
        } else {
            archSel.innerHTML = '<option value="">-- Select Architecture --</option>';
            archs.forEach(a => {
                const opt = document.createElement('option');
                opt.value = a.id;
                opt.textContent = a.label;
                opt.setAttribute('data-link', a.design_link || '');
                archSel.appendChild(opt);
            });
            if (archs.length === 1) archSel.selectedIndex = 1;
        }
        archSel.disabled = false;
        archSel.dispatchEvent(new Event('change'));

        // ── Project drawings (design types) ───────────────────────────
        const drawings = drawData.drawings || [];

        // Deduplicate by design_type_id (keep latest)
        const seen = {};
        drawings.forEach(d => {
            if (!seen[d.design_type_id]) seen[d.design_type_id] = d;
        });
        projectDrawings = Object.values(seen);
        projectPhaseItems = drawData.phase_items || [];

        // ── Project Timeline ──────────────────────────────────────────
        const timeline = drawData.timeline || null;
        if (timelineSec) {
            if (timeline) {
                timelineSec.innerHTML = `
                    <div class="p-3 rounded border" style="background:#f8fafc; border-color:#e2e8f0;">
                        <div class="d-flex align-items-center gap-2 mb-2 text-primary fw-bold" style="font-size:0.9rem">
                            <i class="bi bi-clock-history"></i> Project Timeline Dates
                        </div>
                        <div class="row text-muted small g-2">
                            <div class="col-md-3"><strong>Project Kickoff:</strong> <span class=" fw-semibold ms-1">${timeline.project_start_date || 'N/A'}</span></div>
                            <div class="col-md-3"><strong>Construction Start:</strong> <span class=" fw-semibold ms-1">${timeline.construction_start_date || 'N/A'}</span></div>
                            <div class="col-md-3"><strong>Construction End:</strong> <span class=" fw-semibold ms-1">${timeline.construction_end_date || 'N/A'}</span></div>
                            <div class="col-md-3"><strong>Expected Handover:</strong> <span class=" fw-semibold ms-1">${timeline.expected_handover_date || 'N/A'}</span></div>
                        </div>
                    </div>`;
            } else {
                timelineSec.innerHTML = `
                    <div class="alert alert-warning py-2 mb-0 d-flex align-items-center gap-2" style="font-size:0.85rem">
                        <i class="bi bi-exclamation-triangle"></i>
                        <span>This project has no timeline set yet.</span>
                    </div>`;
            }
            timelineSec.style.display = '';
        }

        // Show info banner
        const container = document.getElementById('dtBlocksContainer');
        if (!projectDrawings.length) {
            container.innerHTML = `
                <div class="alert alert-warning py-2 mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    এই project-এ কোনো Drawing upload করা নেই।
                    <a href="${BASE_URL}/project-drawings" target="_blank" class="ms-1">Drawing Upload করুন →</a>
                </div>`;
            return;
        }

        container.innerHTML = '';

        // Show available designs summary
        const dtBadges = projectDrawings.map(d =>
            `<span class="badge me-1" style="background:rgba(37,99,235,.12);color:#1e40af;border:1px solid #93c5fd">
                <i class="bi bi-file-earmark-check me-1"></i>${d.design_type}
                ${d.design_link ? `<a href="${d.design_link}" target="_blank" class="ms-1" style="color:#1d4ed8" title="View Drawing"><i class="bi bi-box-arrow-up-right"></i></a>` : ''}
            </span>`
        ).join('');
        container.innerHTML = `
            <div class="mb-3 p-2 rounded" style="background:#f0f7ff;border:1px solid #bfdbfe">
                <span class="text-primary fw-semibold small me-2"><i class="bi bi-layers me-1"></i>Available Drawing:</span>
                ${dtBadges}
            </div>`;

        // Auto-add first dt block
        addDtBlock();

    }).catch(() => {
        loader.style.display = 'none';
        archSel.innerHTML = '<option value="">-- Error loading --</option>';
        archSel.disabled  = false;
        document.getElementById('dtBlocksContainer').innerHTML =
            '<div class="alert alert-danger py-2"><i class="bi bi-exclamation-circle me-1"></i>Drawing types load error. Refresh করুন।</div>';
    });
});

document.getElementById('archSelect').addEventListener('change', function () {
    const selOpt = this.options[this.selectedIndex];
    const link = selOpt ? (selOpt.getAttribute('data-link') || '') : '';
    const viewBtn = document.getElementById('archViewLink');
    if (viewBtn) {
        if (link && link !== 'null' && link.trim()) {
            viewBtn.href = link;
            viewBtn.style.display = '';
        } else {
            viewBtn.href = '#';
            viewBtn.style.display = 'none';
        }
    }
});

// ── Other costs → recalc ─────────────────────────────────────────────
document.getElementById('otherCosts').addEventListener('input', recalcGrand);

// ── Add Design Type button ────────────────────────────────────────────
document.getElementById('addDesignTypeBtn').addEventListener('click', addDtBlock);

// ── Submit buttons ────────────────────────────────────────────────────
document.getElementById('saveDraftBtn').addEventListener('click', function () {
    syncHiddenFields();
    document.getElementById('submitAction').value = 'draft';
    document.getElementById('estimateForm').submit();
});
document.getElementById('submitBtn').addEventListener('click', function () {
    syncHiddenFields();
    document.getElementById('submitAction').value = 'submit';
    document.getElementById('estimateForm').submit();
});

// ── Expose functions to global scope ─────────────────────────────────
window.addDtBlock         = addDtBlock;
window.removeDtBlock      = removeDtBlock;
window.onDtChange         = onDtChange;
window.syncDtViewBtn      = syncDtViewBtn;
window.refreshAllDtSelects= refreshAllDtSelects;
window.addWtBlock         = addWtBlock;
window.removeWtBlock      = removeWtBlock;
window.addQsRow           = addQsRow;
window.removeQsRow        = removeQsRow;
window.calcVol            = calcVol;
window.calcSubtotal       = calcSubtotal;
window.onMaterialChange   = onMaterialChange;
window.addLaborRow        = addLaborRow;
window.calcLaborRow       = calcLaborRow;
window.removeLaborRow     = removeLaborRow;
window.recalcGrand        = recalcGrand;
window.refreshPhaseItemOptions = refreshPhaseItemOptions;
window.refreshLaborPhaseItemOptions = refreshLaborPhaseItemOptions;
window.onPhaseItemChange  = onPhaseItemChange;
window.onLaborPhaseItemChange = onLaborPhaseItemChange;

// ── Initial state message (wait for project select) ─────────────────────
document.getElementById('dtBlocksContainer').innerHTML =
    '<div class="text-center text-muted py-4"><i class="bi bi-hand-index me-2 fs-5"></i>Select a Project above to load available Design Types</div>';

})();
</script>


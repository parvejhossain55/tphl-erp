<?php
// PHP data injected from edit() controller
$budget = $budget ?? [];
$projects = $projects ?? [];
$archs = $archs ?? [];
$designTypes = $designTypes ?? [];
$materials = $materials ?? [];
$workTypesGrouped = $workTypesGrouped ?? [];
$projectDrawings = $projectDrawings ?? [];
$itemsByDt = $itemsByDt ?? [];  // [design_type_id => [work_type_id => [items]]]
$laborsByDt = $laborsByDt ?? [];  // [design_type_id => [labors]]

$wtGroupedJson = json_encode($workTypesGrouped, JSON_UNESCAPED_UNICODE);
$materialsJson = json_encode(array_map(fn($m) => [
    'id' => $m['id'],
    'name' => ($m['name'] ?? '') . ' [' . ($m['category_name'] ?? 'Uncategorized') . '] (' . ($m['unit'] ?? '') . ')',
    'unit' => $m['unit'] ?? '',
], $materials), JSON_UNESCAPED_UNICODE);
$designTypesJson = json_encode($designTypes, JSON_UNESCAPED_UNICODE);
$projectDrawingsJson = json_encode($projectDrawings, JSON_UNESCAPED_UNICODE);

// Pre-build existing blocks for PHP→JS
// itemsByDt: {dtId: {wtId: [rows]}}
// laborsByDt: {dtId: [rows]}
$existingBlocksJson = json_encode($itemsByDt, JSON_UNESCAPED_UNICODE);
$existingLaborsJson = json_encode($laborsByDt, JSON_UNESCAPED_UNICODE);
?>
<style>
    /* Reuse create form CSS */
    :root {
        --est-primary: #2563eb;
        --est-success: #16a34a;
        --est-warning: #d97706;
        --est-danger: #dc2626;
    }

    .est-form-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, .08);
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

    .est-form-card .card-bd {
        padding: 1.25rem;
    }

    .dt-block {
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        margin-bottom: 1.25rem;
        background: #fafcff;
        overflow: hidden;
        transition: border-color .2s;
    }

    .dt-block:hover {
        border-color: #93c5fd;
    }

    .dt-block-header {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .8rem 1rem;
        background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
        color: #fff;
    }

    .dt-color-1 .dt-block-header {
        background: #14532d;
        ;
    }

    .dt-color-2 .dt-block-header {
        background: linear-gradient(135deg, #14532d, #16a34a);
    }

    .dt-color-3 .dt-block-header {
        background: linear-gradient(135deg, #78350f, #d97706);
    }

    .dt-color-4 .dt-block-header {
        background: linear-gradient(135deg, #4c1d95, #7c3aed);
    }

    .dt-color-5 .dt-block-header {
        background: linear-gradient(135deg, #831843, #db2777);
    }

    .dt-color-6 .dt-block-header {
        background: linear-gradient(135deg, #164e63, #0891b2);
    }

    .dt-color-7 .dt-block-header {
        background: linear-gradient(135deg, #7c2d12, #ea580c);
    }

    .dt-color-8 .dt-block-header {
        background: linear-gradient(135deg, #365314, #65a30d);
    }

    .dt-block-header select.dt-select {
        background-color: rgba(0, 0, 0, .35) !important;
        color: #fff !important;
        border-color: rgba(255, 255, 255, .5) !important;
        border-radius: 6px;
        font-weight: 600;
    }

    .dt-block-header select.dt-select option {
        background-color: #1e293b !important;
        color: #f1f5f9 !important;
    }

    .dt-block-body {
        padding: 1rem;
    }

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

    .wt-block-body {
        padding: .6rem 0 0;
    }

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

    .dt-summary-row {
        display: flex;
        gap: 1rem;
        padding: .6rem 1rem;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        flex-wrap: wrap;
    }

    .sum-item {
        display: flex;
        align-items: center;
        gap: .4rem;
        font-size: .83rem;
    }

    .sum-badge {
        font-weight: 700;
        padding: 2px 10px;
        border-radius: 20px;
        font-size: .83rem;
    }

    .sum-mat {
        background: #dcfce7;
        color: #166534;
    }

    .sum-lab {
        background: #dbeafe;
        color: #1e40af;
    }

    .sum-tot {
        background: #fef3c7;
        color: #92400e;
    }

    .grand-table {
        width: 100%;
        border-collapse: collapse;
        font-size: .88rem;
    }

    .grand-table th,
    .grand-table td {
        padding: .5rem .75rem;
        border: 1px solid #e2e8f0;
    }

    .grand-table thead th {
        background: #1e3a8a;
        color: #fff;
        text-align: center;
    }

    .grand-table tfoot td {
        background: #fef9c3;
        font-weight: 700;
    }

    .btn-draft {
        background: #6b7280;
        border-color: #6b7280;
        color: #fff;
        padding: .6rem 1.8rem;
    }

    .btn-draft:hover {
        background: #4b5563;
        color: #fff;
    }

    .btn-submit {
        background: #2563eb;
        border-color: #2563eb;
        color: #fff;
        padding: .6rem 1.8rem;
    }

    .btn-submit:hover {
        background: #1d4ed8;
        color: #fff;
    }
</style>

<div class="pagetitle d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3><?= htmlspecialchars($page_title) ?></h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-estimates">Estimates</a></li>
                <li class="breadcrumb-item active">Edit Draft</li>
            </ol>
        </nav>
    </div>
    <a href="<?= $base_url ?>/con-estimate-show/<?= $budget['id'] ?>" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<?php if (($budget['status'] ?? '') === 'draft'): ?>
    <div class="alert alert-secondary d-flex align-items-center gap-2 py-2 mb-3">
        <i class="bi bi-file-earmark-text fs-5"></i>
        <span><strong>Draft Mode</strong> — আগের সব data দেখাচ্ছে। নতুন Design Type add করুন বা edit করুন।
            Submit দিলে Approval-এ যাবে।</span>
    </div>
<?php endif; ?>

<section class="section">
    <form id="estimateForm" action="<?= $base_url ?>/con-estimate-update/<?= $budget['id'] ?>" method="POST">
        <input type="hidden" name="submit_action" id="submitAction" value="draft">
        <input type="hidden" name="drawing_id" id="primaryDrawingId"
            value="<?= (int) ($budget['drawing_id'] ?? 0) ?: '' ?>">

        <!-- ═══ SECTION 1: Budget Header ═══════════════════════════════════════ -->
        <div class="est-form-card">
            <div class="card-hd"><span><i class="bi bi-info-circle me-2 text-primary"></i>Budget Header</span></div>
            <div class="card-bd">
                <div class="row ">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold text-muted">Project <span
                                class="text-danger">*</span></label>
                        <select name="project_id" id="estimateProjectId" class="form-select" required>
                            <option value="">-- Choose Project --</option>
                            <?php foreach ($projects as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= $p['id'] == $budget['project_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-semibold text-muted">Architecture Record <span
                                class="text-danger">*</span></label>
                        <select name="architecture_id" id="archSelect" class="form-select" required>
                            <option value="">-- Select Architecture --</option>
                            <?php foreach ($archs as $a): ?>
                                <option value="<?= $a['id'] ?>" <?= $a['id'] == $budget['architecture_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars('Arch #' . $a['id']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="archLoader" class="text-muted small mt-1" style="display:none">
                            <i class="bi bi-hourglass-split me-1"></i> Loading...
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold text-muted">Weight %</label>
                        <input type="number" step="0.01" name="weight_percentage" class="form-control"
                            value="<?= htmlspecialchars($budget['weight_percentage'] ?? 100) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-muted">Start Date</label>
                        <input type="date" name="start_date" class="form-control"
                            value="<?= (!empty($budget['start_date']) && $budget['start_date'] !== '0000-00-00') ? $budget['start_date'] : '' ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-muted">End Date</label>
                        <input type="date" name="end_date" class="form-control"
                            value="<?= (!empty($budget['end_date']) && $budget['end_date'] !== '0000-00-00') ? $budget['end_date'] : '' ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-muted">Total Area (sqft)</label>
                        <input type="number" step="0.01" name="total_area_sqft" class="form-control"
                            value="<?= $budget['total_area_sqft'] ?? '' ?>" placeholder="0.00">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-muted">Other Costs (৳)</label>
                        <input type="number" step="0.01" name="other_costs" id="otherCosts" class="form-control"
                            value="<?= $budget['other_costs'] ?? 0 ?>" placeholder="0.00">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-muted">Budget Link</label>
                        <input type="url" name="budget_link" class="form-control"
                            value="<?= htmlspecialchars($budget['budget_link'] ?? '') ?>"
                            placeholder="Google Drive link...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-muted">Other Cost Detail</label>
                        <input type="text" name="other_cost_detail" class="form-control"
                            value="<?= htmlspecialchars($budget['other_cost_detail'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-muted">Work Details</label>
                        <input type="text" name="work_details" class="form-control"
                            value="<?= htmlspecialchars($budget['work_details'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ SECTION 2: Design Type Blocks ══════════════════════════════════ -->
        <div class="est-form-card">
            <div class="card-hd">
                <span><i class="bi bi-layers me-2 text-primary"></i>Design Type Wise Estimate</span>
                <button type="button" id="addDesignTypeBtn" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> Add Design Type
                </button>
            </div>
            <div class="card-bd">
                <div id="dtBlocksContainer">
                    <!-- JS will render existing blocks here -->
                </div>
            </div>
        </div>

        <!-- ═══ SECTION 3: Grand Total Summary ═════════════════════════════════ -->
        <div class="est-form-card">
            <div class="card-hd"><span><i class="bi bi-calculator me-2 text-success"></i>Grand Total Summary</span>
            </div>
            <div class="card-bd p-0">
                <table class="grand-table">
                    <thead>
                        <tr>
                            <th>Design Type</th>
                            <th class="text-end">Material (৳)</th>
                            <th class="text-end">Labor (৳)</th>
                            <th class="text-end">Total (৳)</th>
                        </tr>
                    </thead>
                    <tbody id="grandTableBody">
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">Design Type block add করলে এখানে
                                দেখাবে...</td>
                        </tr>
                    </tbody>
                    <tfoot id="grandTotals">
                        <tr>
                            <td class="text-end">Grand Total</td>
                            <td class="text-end" id="grandMat">৳ 0.00</td>
                            <td class="text-end" id="grandLab">৳ 0.00</td>
                            <td class="text-end text-primary" id="grandAll">৳ 0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- ═══ Submit Buttons ═══════════════════════════════════════════════════ -->
        <div class="d-flex justify-content-between align-items-center mb-5">
            <a href="<?= $base_url ?>/con-estimate-show/<?= $budget['id'] ?>" class="btn btn-outline-secondary">
                <i class="bi bi-x-circle me-1"></i> Cancel
            </a>
            <div class="d-flex gap-2">
                <button type="button" id="saveDraftBtn" class="btn btn-draft">
                    <i class="bi bi-floppy me-1"></i> Save Draft
                </button>
                <button type="button" id="submitBtn" class="btn btn-submit">
                    <i class="bi bi-send me-1"></i> Submit for Approval
                </button>
            </div>
        </div>

    </form>
</section>

<script>
    (function () {
        'use strict';

        const BASE_URL = '<?= $GLOBALS["base_url"] ?>';
        const WT_GROUPED = <?= $wtGroupedJson ?>;
        const MATERIALS = <?= $materialsJson ?>;
        const DESIGN_TYPES = <?= $designTypesJson ?>;
        const EXISTING_BLOCKS = <?= $existingBlocksJson ?>;   // {dtId: {wtId: [items]}}
        const EXISTING_LABORS = <?= $existingLaborsJson ?>;   // {dtId: [labors]}
        const BUDGET_PROJECT = <?= (int) ($budget['project_id'] ?? 0) ?>;

        const dtNameMap = {};
        DESIGN_TYPES.forEach(dt => { dtNameMap[dt.id] = dt.name; });
        const matMap = {};
        MATERIALS.forEach(m => { matMap[m.id] = m; });

        let dtBlockCount = 0;
        let projectDrawings = <?= $projectDrawingsJson ?>;
        let dtColors = ['dt-color-1', 'dt-color-2', 'dt-color-3', 'dt-color-4',
            'dt-color-5', 'dt-color-6', 'dt-color-7', 'dt-color-8'];

        function fmt(n) { return parseFloat(n || 0).toLocaleString('en-BD', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
        function fmtQty(n) { return parseFloat(n || 0).toLocaleString('en-BD', { minimumFractionDigits: 3, maximumFractionDigits: 3 }); }

        // ── Build Design Type block HTML ──────────────────────────────────────
        function buildDtBlock(blockIdx, selectedDtId) {
            const colorClass = dtColors[(blockIdx - 1) % dtColors.length];
            let dtOptions = '';
            if (projectDrawings.length) {
                dtOptions = projectDrawings.map(d =>
                    `<option value="${d.design_type_id}"
                     data-link="${d.design_link || ''}"
                     data-drawing-id="${d.id}"
                     data-version="${d.version || ''}"
                     style="background:#1e293b;color:#f1f5f9"
                     ${String(d.design_type_id) === String(selectedDtId) ? 'selected' : ''}>
                ${d.design_type} ${d.version ? '(v' + d.version + ')' : ''} — ${d.status}
             </option>`
                ).join('');
            } else {
                dtOptions = DESIGN_TYPES.map(dt =>
                    `<option value="${dt.id}" style="background:#1e293b;color:#f1f5f9"
                     ${String(dt.id) === String(selectedDtId) ? 'selected' : ''}>${dt.name}</option>`
                ).join('');
            }

            return `
    <div class="dt-block ${colorClass}" id="dt-block-${blockIdx}" data-block="${blockIdx}">
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
            <a href="#" target="_blank" id="dt-view-link-${blockIdx}" class="btn btn-sm ms-2"
               style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.4);color:#fff;display:none">
                <i class="bi bi-eye me-1"></i>View Drawing
            </a>
            <button type="button" class="btn btn-sm btn-light ms-2" onclick="removeDtBlock(${blockIdx})">
                <i class="bi bi-trash3"></i>
            </button>
        </div>

        <div id="dt-link-row-${blockIdx}"
             style="display:none;background:#407340;padding:.5rem 1rem;border-bottom:1px solid rgba(255,255,255,.1)">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-link-45deg text-white opacity-75"></i>
                <span class="text-white small opacity-75" style="white-space:nowrap">Drawing Link:</span>
                <input type="url" id="dt-custom-link-${blockIdx}" name="dt_custom_link[]"
                       class="form-control form-control-sm"
                       style="background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.3);color:#fff;max-width:480px"
                       placeholder="https://..."
                       oninput="syncDtViewBtn(${blockIdx})">
                <a href="#" target="_blank" id="dt-custom-view-${blockIdx}" class="btn btn-sm"
                   style="background:rgba(74,222,128,.2);border:1px solid rgba(74,222,128,.4);color:#fff;display:none">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Open
                </a>
                <input type="hidden" name="dt_design_type_id_link[]" id="dt-dtid-link-${blockIdx}" value="${selectedDtId || ''}">
            </div>
        </div>

        <div class="dt-block-body" id="dt-body-${blockIdx}">
            <div id="wt-blocks-${blockIdx}"></div>
            <button type="button" class="btn btn-sm btn-outline-primary mb-2 mt-1" onclick="addWtBlock(${blockIdx})">
                <i class="bi bi-plus-circle me-1"></i> Add Work Type Block
            </button>
            <div class="mt-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fw-semibold text-muted small"><i class="bi bi-people me-1"></i>Labor Costs</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addLaborRow(${blockIdx})">
                        <i class="bi bi-plus-circle me-1"></i> Add Labor Row
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="labor-table" id="labor-table-${blockIdx}">
                        <thead><tr>
                            <th style="width:25%">Labor Type</th>
                            <th style="width:20%">Description</th>
                            <th style="width:15%">Rate/sqft (৳)</th>
                            <th style="width:15%">Area (sqft)</th>
                            <th style="width:15%">Subtotal</th>
                            <th style="width:10%">Del</th>
                        </tr></thead>
                        <tbody id="labor-body-${blockIdx}"></tbody>
                    </table>
                </div>
            </div>
            <div class="dt-summary-row mt-2">
                <div class="sum-item"><span class="text-muted">Material:</span><span class="sum-badge sum-mat" id="dt-mat-${blockIdx}">৳ 0.00</span></div>
                <div class="sum-item"><span class="text-muted">Labor:</span><span class="sum-badge sum-lab" id="dt-lab-${blockIdx}">৳ 0.00</span></div>
                <div class="sum-item"><span class="text-muted">Total:</span><span class="sum-badge sum-tot" id="dt-tot-${blockIdx}">৳ 0.00</span></div>
            </div>
        </div>
    </div>`;
        }

        // ── Add Design Type block ──────────────────────────────────────────────
        function addDtBlock(selectedDtId) {
            dtBlockCount++;
            const container = document.getElementById('dtBlocksContainer');
            const div = document.createElement('div');
            div.innerHTML = buildDtBlock(dtBlockCount, selectedDtId || '');
            container.appendChild(div.firstElementChild);
            return dtBlockCount;
        }

        // ── Remove Design Type block ───────────────────────────────────────────
        function removeDtBlock(blockIdx) {
            const el = document.getElementById('dt-block-' + blockIdx);
            if (el) { el.remove(); recalcGrand(); }
        }

        // ── onDtChange ────────────────────────────────────────────────────────
        function onDtChange(blockIdx) {
            const sel = document.getElementById('dt-select-' + blockIdx);
            const selOpt = sel ? sel.options[sel.selectedIndex] : null;
            const dtId = selOpt ? selOpt.value : '';
            const link = selOpt ? (selOpt.getAttribute('data-link') || '') : '';

            const viewBtn = document.getElementById('dt-view-link-' + blockIdx);
            if (viewBtn) {
                if (link && link !== 'null' && link.trim()) { viewBtn.href = link; viewBtn.style.display = ''; }
                else { viewBtn.href = '#'; viewBtn.style.display = 'none'; }
            }

            const linkRow = document.getElementById('dt-link-row-' + blockIdx);
            const linkInput = document.getElementById('dt-custom-link-' + blockIdx);
            const dtidHidden = document.getElementById('dt-dtid-link-' + blockIdx);

            if (dtId) {
                if (linkRow) linkRow.style.display = '';
                if (linkInput && !linkInput.value && link && link !== 'null') { linkInput.value = link; syncDtViewBtn(blockIdx); }
                if (dtidHidden) dtidHidden.value = dtId;
                const drawingId = selOpt ? (selOpt.getAttribute('data-drawing-id') || '') : '';
                const pf = document.getElementById('primaryDrawingId');
                if (pf && drawingId && !pf.value) pf.value = drawingId;
                if (pf && drawingId && String(blockIdx) === '1') pf.value = drawingId;
            } else {
                if (linkRow) linkRow.style.display = 'none';
                if (linkInput) linkInput.value = '';
                if (dtidHidden) dtidHidden.value = '';
            }

            const body = document.getElementById('wt-blocks-' + blockIdx);
            if (!body) return;
            body.querySelectorAll('.wt-select').forEach(s => { refreshWtOptions(s, blockIdx); });
        }

        function syncDtViewBtn(blockIdx) {
            const input = document.getElementById('dt-custom-link-' + blockIdx);
            const btn = document.getElementById('dt-custom-view-' + blockIdx);
            if (!input || !btn) return;
            const v = input.value.trim();
            if (v && (v.startsWith('http://') || v.startsWith('https://'))) { btn.href = v; btn.style.display = ''; }
            else { btn.href = '#'; btn.style.display = 'none'; }
        }

        // ── Work Type block ────────────────────────────────────────────────────
        let wtCounts = {};
        function addWtBlock(dtIdx, existingWtId) {
            if (!wtCounts[dtIdx]) wtCounts[dtIdx] = 0;
            wtCounts[dtIdx]++;
            const wtIdx = dtIdx + '_' + wtCounts[dtIdx];
            const dtSel = document.getElementById('dt-select-' + dtIdx);
            const dtId = dtSel ? dtSel.value : '';
            const wts = dtId ? (WT_GROUPED[dtId] || []) : Object.values(WT_GROUPED).flat();

            let wtOpts = `<option value="" style="background:#1e293b;color:#f1f5f9">-- Select Work Type --</option>`;
            wts.forEach(wt => {
                const sel = String(wt.id) === String(existingWtId) ? 'selected' : '';
                wtOpts += `<option value="${wt.id}" ${sel}>${wt.work_type_name}</option>`;
            });

            const html = `
    <div class="wt-block" id="wt-block-${wtIdx}">
        <div class="wt-block-header">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-tools text-primary"></i>
                <select class="form-select form-select-sm wt-select" id="wt-select-${wtIdx}"
                        style="max-width:250px" onchange="onWtChange('${wtIdx}',${dtIdx})">
                    ${wtOpts}
                </select>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeWtBlock('${wtIdx}',${dtIdx})">
                <i class="bi bi-dash-circle"></i>
            </button>
        </div>
        <div class="wt-block-body">
            <div class="table-responsive">
                <table class="qs-table">
                    <thead><tr>
                        <th style="width:40px">SL</th>
                        <th style="width:22%">Location / Description</th>
                        <th style="width:8%">Unit</th>
                        <th style="width:6%">Nos</th>
                        <th style="width:7%">L (ft)</th>
                        <th style="width:7%">W (ft)</th>
                        <th style="width:7%">H (ft)</th>
                        <th style="width:8%" class="vol-cell">Vol/Qty</th>
                        <th style="width:15%">Material</th>
                        <th style="width:8%">Rate (৳)</th>
                        <th style="width:9%">Subtotal</th>
                        <th style="width:40px">Del</th>
                    </tr></thead>
                    <tbody id="qs-body-${wtIdx}"></tbody>
                </table>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary mt-1 ms-1 mb-1"
                    onclick="addQsRow('${wtIdx}',${dtIdx})">
                <i class="bi bi-plus-square me-1"></i> Add Row
            </button>
        </div>
    </div>`;

            document.getElementById('wt-blocks-' + dtIdx).insertAdjacentHTML('beforeend', html);
            return wtIdx;
        }

        function refreshWtOptions(sel, dtIdx) {
            const dtSel = document.getElementById('dt-select-' + dtIdx);
            const dtId = dtSel ? dtSel.value : '';
            const wts = dtId ? (WT_GROUPED[dtId] || []) : Object.values(WT_GROUPED).flat();
            const cur = sel.value;
            sel.innerHTML = `<option value="">-- Select Work Type --</option>`;
            wts.forEach(wt => {
                const opt = document.createElement('option');
                opt.value = wt.id;
                opt.textContent = wt.work_type_name;
                if (String(wt.id) === String(cur)) opt.selected = true;
                sel.appendChild(opt);
            });
        }

        function onWtChange(wtIdx, dtIdx) { recalcGrand(); }

        function removeWtBlock(wtIdx, dtIdx) {
            const el = document.getElementById('wt-block-' + wtIdx);
            if (el) { el.remove(); recalcGrand(); }
        }

        // ── QS Row ─────────────────────────────────────────────────────────────
        function addQsRow(wtIdx, dtIdx, existingRow) {
            const ex = existingRow || {};
            const tbody = document.getElementById('qs-body-' + wtIdx);
            if (!tbody) return;
            const rowIdx = tbody.children.length + 1;

            let matOpts = `<option value="">-- Material --</option>`;
            MATERIALS.forEach(m => {
                const sel = String(m.id) === String(ex.material_id || '') ? 'selected' : '';
                matOpts += `<option value="${m.id}" data-unit="${m.unit || ''}" ${sel}>${m.name}</option>`;
            });

            const dtSel = document.getElementById('dt-select-' + dtIdx);
            const dtId = dtSel ? dtSel.value : '';
            const wtSel = document.getElementById('wt-select-' + wtIdx);
            const wtId = wtSel ? wtSel.value : '';

            const vol = ex.volume_qty || '';
            const unit = ex.material_unit || '';

            const tr = document.createElement('tr');
            tr.innerHTML = `
        <td><input type="hidden" name="item_design_type_id[]" value="${dtId}">
            <input type="hidden" name="item_work_type_id[]"   value="${wtId}">
            <input type="number" name="item_sl_no[]" class="form-control-sm form-control" style="width:45px"
                   value="${ex.sl_no || rowIdx}" min="1"></td>
        <td><input type="text" name="item_location_desc[]" class="form-control-sm form-control"
                   value="${ex.location_desc || ''}" placeholder="Location..."></td>
        <td><input type="text" name="item_material_unit[]" class="form-control-sm form-control unit-display"
                   readonly value="${unit}" placeholder="-"></td>
        <td><input type="number" step="0.001" name="item_nos[]"
                   class="form-control-sm form-control qs-nos" value="${ex.nos || ''}"
                   oninput="calcVol(this)" placeholder="0"></td>
        <td><input type="number" step="0.001" name="item_length_ft[]"
                   class="form-control-sm form-control qs-l" value="${ex.length_ft || ''}"
                   oninput="calcVol(this)" placeholder="0"></td>
        <td><input type="number" step="0.001" name="item_width_ft[]"
                   class="form-control-sm form-control qs-w" value="${ex.width_ft || ''}"
                   oninput="calcVol(this)" placeholder="0"></td>
        <td><input type="number" step="0.001" name="item_height_ft[]"
                   class="form-control-sm form-control qs-h" value="${ex.height_ft || ''}"
                   oninput="calcVol(this)" placeholder="0"></td>
        <td class="vol-cell">
            <input type="number" step="0.001" name="item_estimated_qty[]"
                   class="form-control-sm form-control qs-vol text-center fw-bold"
                   style="background:#eff6ff;color:#1d4ed8;width:80px"
                   value="${vol}" readonly placeholder="auto">
        </td>
        <td><select name="item_material_id[]" class="form-select-sm form-select mat-sel"
                    onchange="onMaterialChange(this)">${matOpts}</select></td>
        <td><input type="number" step="0.01" name="item_unit_cost[]"
                   class="form-control-sm form-control qs-rate" value="${ex.unit_cost_approx || ''}"
                   oninput="calcSubtotal(this)" placeholder="0.00"></td>
        <td class="qs-sub text-end fw-bold" style="font-size:.82rem">৳ 0.00</td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeQsRow(this)">
            <i class="bi bi-trash3"></i></button></td>
    `;
            tbody.appendChild(tr);

            if (ex.material_id) {
                const matSel = tr.querySelector('.mat-sel');
                onMaterialChange(matSel);
            }
            calcSubtotal(tr.querySelector('.qs-rate'));
            updateDtHiddens(tr, dtId, wtId);
        }

        function updateDtHiddens(tr, dtId, wtId) {
            const dti = tr.querySelector('input[name="item_design_type_id[]"]');
            const wti = tr.querySelector('input[name="item_work_type_id[]"]');
            if (dti) dti.value = dtId;
            if (wti) wti.value = wtId;
        }

        function calcVol(el) {
            const tr  = el.closest('tr');
            const nos = parseFloat(tr.querySelector('.qs-nos').value) || 0;
            const l   = parseFloat(tr.querySelector('.qs-l').value)   || 0;
            const w   = parseFloat(tr.querySelector('.qs-w').value)   || 0;
            const h   = parseFloat(tr.querySelector('.qs-h').value)   || 0;
            // Only multiply dimensions that are > 0 (skip 0 = 1 factor)
            let vol = nos;
            if (l > 0) vol *= l;
            if (w > 0) vol *= w;
            if (h > 0) vol *= h;
            const volEl = tr.querySelector('.qs-vol');
            if (volEl) volEl.value = (nos > 0 && vol > 0) ? vol.toFixed(3) : '';
            calcSubtotal(tr.querySelector('.qs-rate'));
        }

        function calcSubtotal(el) {
            if (!el) return;
            const tr = el.closest('tr');
            const vol = parseFloat(tr.querySelector('.qs-vol').value) || 0;
            const rate = parseFloat(tr.querySelector('.qs-rate').value) || 0;
            const sub = vol * rate;
            const subEl = tr.querySelector('.qs-sub');
            if (subEl) subEl.textContent = '৳ ' + fmt(sub);
            recalcGrand();
        }

        function onMaterialChange(sel) {
            const tr = sel.closest('tr');
            const opt = sel.options[sel.selectedIndex];
            const unit = opt ? (opt.getAttribute('data-unit') || '') : '';
            const ud = tr.querySelector('.unit-display');
            if (ud) ud.value = unit;
        }

        function removeQsRow(btn) {
            btn.closest('tr').remove();
            recalcGrand();
        }

        // ── Labor Row ──────────────────────────────────────────────────────────
        function addLaborRow(dtIdx, existingLabor) {
            const ex = existingLabor || {};
            const tbody = document.getElementById('labor-body-' + dtIdx);
            if (!tbody) return;
            const dtSel = document.getElementById('dt-select-' + dtIdx);
            const dtId = dtSel ? dtSel.value : '';

            const tr = document.createElement('tr');
            tr.innerHTML = `
        <td><input type="hidden" name="labor_design_type_id[]" value="${dtId}">
            <input type="hidden" name="labor_work_type_id[]"   value="">
            <input type="text" name="labor_type_name[]" class="form-control form-control-sm"
                   value="${ex.labor_type_name || ''}" placeholder="Mason, Painter..."></td>
        <td><input type="text" name="labor_description[]" class="form-control form-control-sm"
                   value="${ex.description || ''}" placeholder="Description..."></td>
        <td><input type="number" step="0.01" name="labor_rate_per_sqft[]"
                   class="form-control form-control-sm labor-rate"
                   value="${ex.rate_per_sqft || ''}" oninput="calcLaborRow(this)" placeholder="0.00"></td>
        <td><input type="number" step="0.01" name="labor_area_sqft[]"
                   class="form-control form-control-sm labor-area"
                   value="${ex.area_sqft || ''}" oninput="calcLaborRow(this)" placeholder="0.00"></td>
        <td class="labor-sub text-end fw-bold">৳ 0.00</td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeLaborRow(this)">
            <i class="bi bi-trash3"></i></button></td>
    `;
            tbody.appendChild(tr);
            calcLaborRow(tr.querySelector('.labor-rate'));
        }

        function calcLaborRow(el) {
            const tr = el.closest('tr');
            const rate = parseFloat(tr.querySelector('.labor-rate').value) || 0;
            const area = parseFloat(tr.querySelector('.labor-area').value) || 0;
            const sub = rate * area;
            tr.querySelector('.labor-sub').textContent = '৳ ' + fmt(sub);
            recalcGrand();
        }

        function removeLaborRow(btn) {
            btn.closest('tr').remove();
            recalcGrand();
        }

        // ── Grand Total recalc ─────────────────────────────────────────────────
        function recalcGrand() {
            const tbody = document.getElementById('grandTableBody');
            let gMat = 0, gLab = 0;
            tbody.innerHTML = '';

            document.querySelectorAll('.dt-block').forEach(block => {
                const idx = block.dataset.block;
                const dtSel = document.getElementById('dt-select-' + idx);
                const dtName = dtSel ? (dtSel.options[dtSel.selectedIndex]?.textContent?.trim() || 'Unknown') : 'Unknown';
                let mat = 0, lab = 0;

                block.querySelectorAll('.qs-sub').forEach(cell => {
                    mat += parseFloat(cell.textContent.replace(/[^\d.]/g, '')) || 0;
                });
                block.querySelectorAll('.labor-sub').forEach(cell => {
                    lab += parseFloat(cell.textContent.replace(/[^\d.]/g, '')) || 0;
                });

                const tot = mat + lab;
                document.getElementById('dt-mat-' + idx) && (document.getElementById('dt-mat-' + idx).textContent = '৳ ' + fmt(mat));
                document.getElementById('dt-lab-' + idx) && (document.getElementById('dt-lab-' + idx).textContent = '৳ ' + fmt(lab));
                document.getElementById('dt-tot-' + idx) && (document.getElementById('dt-tot-' + idx).textContent = '৳ ' + fmt(tot));

                tbody.innerHTML += `<tr>
            <td>${dtName}</td>
            <td class="text-end">৳ ${fmt(mat)}</td>
            <td class="text-end">৳ ${fmt(lab)}</td>
            <td class="text-end fw-bold text-primary">৳ ${fmt(tot)}</td>
        </tr>`;
                gMat += mat; gLab += lab;
            });

            document.getElementById('grandMat').textContent = '৳ ' + fmt(gMat);
            document.getElementById('grandLab').textContent = '৳ ' + fmt(gLab);
            document.getElementById('grandAll').textContent = '৳ ' + fmt(gMat + gLab);
        }

        // ── Sync hidden fields before submit ──────────────────────────────────
        function syncHiddenFields() {
            document.querySelectorAll('.dt-block').forEach(block => {
                const idx = block.dataset.block;
                const dtSel = document.getElementById('dt-select-' + idx);
                const dtId = dtSel ? dtSel.value : '';

                block.querySelectorAll('input[name="item_design_type_id[]"]').forEach(h => { h.value = dtId; });
                block.querySelectorAll('input[name="labor_design_type_id[]"]').forEach(h => { h.value = dtId; });

                block.querySelectorAll('[id^="wt-block-"]').forEach(wtBlock => {
                    const wtSel = wtBlock.querySelector('.wt-select');
                    const wtId = wtSel ? wtSel.value : '';
                    wtBlock.querySelectorAll('input[name="item_work_type_id[]"]').forEach(h => { h.value = wtId; });
                });
            });
        }

        // ── Init: render existing blocks ───────────────────────────────────────
        function initExistingBlocks() {
            const existingDtIds = Object.keys(EXISTING_BLOCKS);

            if (existingDtIds.length > 0) {
                existingDtIds.forEach(dtId => {
                    const blockIdx = addDtBlock(dtId);

                    // Trigger onDtChange to show link row
                    setTimeout(() => {
                        onDtChange(blockIdx);

                        const wtGroups = EXISTING_BLOCKS[dtId];
                        const wtIds = Object.keys(wtGroups);

                        if (wtIds.length === 0) {
                            addWtBlock(blockIdx);
                        } else {
                            wtIds.forEach(wtId => {
                                const wtBlockIdx = addWtBlock(blockIdx, wtId);
                                const rows = wtGroups[wtId];
                                rows.forEach(row => { addQsRow(wtBlockIdx, blockIdx, row); });
                            });
                        }

                        // Add existing labors for this dt
                        const labors = EXISTING_LABORS[dtId] || [];
                        labors.forEach(l => { addLaborRow(blockIdx, l); });

                        recalcGrand();
                    }, 50);
                });
            } else {
                // No existing — show placeholder message
                document.getElementById('dtBlocksContainer').innerHTML =
                    '<div class="text-center text-muted py-4"><i class="bi bi-hand-index me-2 fs-5"></i>' +
                    '+ Add Design Type button চাপুন।</div>';
            }
        }

        // ── Project change → reload drawings ──────────────────────────────────
        document.getElementById('estimateProjectId').addEventListener('change', function () {
            const pid = this.value;
            const archSel = document.getElementById('archSelect');
            const loader = document.getElementById('archLoader');

            archSel.innerHTML = '<option value="">-- Loading... --</option>';
            archSel.disabled = true;
            projectDrawings = [];

            if (!pid) {
                archSel.innerHTML = '<option value="">-- Select Project First --</option>';
                archSel.disabled = false;
                return;
            }
            loader.style.display = 'block';

            const archFetch = fetch(BASE_URL + '/con-ajax-arch-by-project?project_id=' + pid).then(r => r.json());
            const drawFetch = fetch(BASE_URL + '/con-ajax-designs-by-project?project_id=' + pid).then(r => r.json());

            Promise.all([archFetch, drawFetch]).then(([archs, drawData]) => {
                loader.style.display = 'none';
                if (!archs.length) {
                    archSel.innerHTML = '<option value="">-- No Architecture Record --</option>';
                } else {
                    archSel.innerHTML = '<option value="">-- Select Architecture --</option>';
                    archs.forEach(a => {
                        const opt = document.createElement('option');
                        opt.value = a.id;
                        opt.textContent = a.label;
                        if (String(a.id) === '<?= $budget['architecture_id'] ?>') opt.selected = true;
                        archSel.appendChild(opt);
                    });
                }
                archSel.disabled = false;

                const seen = {};
                (drawData.drawings || []).forEach(d => {
                    if (!seen[d.design_type_id]) seen[d.design_type_id] = d;
                });
                projectDrawings = Object.values(seen);
            }).catch(() => {
                loader.style.display = 'none';
                archSel.innerHTML = '<option value="">-- Error --</option>';
                archSel.disabled = false;
            });
        });

        // ── Submit buttons ─────────────────────────────────────────────────────
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
        document.getElementById('addDesignTypeBtn').addEventListener('click', function () {
            const idx = addDtBlock();
            addWtBlock(idx);
            setTimeout(() => onDtChange(idx), 50);
        });
        document.getElementById('otherCosts').addEventListener('input', recalcGrand);

        // Expose globals
        window.onDtChange = onDtChange;
        window.syncDtViewBtn = syncDtViewBtn;
        window.addDtBlock = addDtBlock;
        window.removeDtBlock = removeDtBlock;
        window.addWtBlock = addWtBlock;
        window.removeWtBlock = removeWtBlock;
        window.addQsRow = addQsRow;
        window.removeQsRow = removeQsRow;
        window.calcVol = calcVol;
        window.calcSubtotal = calcSubtotal;
        window.onMaterialChange = onMaterialChange;
        window.addLaborRow = addLaborRow;
        window.calcLaborRow = calcLaborRow;
        window.removeLaborRow = removeLaborRow;
        window.recalcGrand = recalcGrand;

        // Init on load
        initExistingBlocks();

    })();
</script>
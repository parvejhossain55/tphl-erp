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

$projectPhaseItemsJson = json_encode($projectPhaseItems ?? [], JSON_UNESCAPED_UNICODE);
$projectFloorsJson = json_encode($projectFloors ?? [], JSON_UNESCAPED_UNICODE);

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
        background: linear-gradient(135deg, #4c1d95, var(--clr-theme-secondary));
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
        min-width: 1180px !important;
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

    /* Column widths optimization for laptops/tablets */
    .qs-table th:nth-child(1), .qs-table td:nth-child(1) { width: 55px !important; min-width: 55px !important; text-align: center; } /* SL */
    .qs-table th:nth-child(2), .qs-table td:nth-child(2) { min-width: 180px !important; } /* Location / Description */
    .qs-table th:nth-child(3), .qs-table td:nth-child(3) { min-width: 120px !important; } /* Floor */
    .qs-table th:nth-child(4), .qs-table td:nth-child(4) { min-width: 65px !important; }  /* Unit */
    .qs-table th:nth-child(5), .qs-table td:nth-child(5) { min-width: 75px !important; }  /* Nos */
    .qs-table th:nth-child(6), .qs-table td:nth-child(6) { min-width: 70px !important; }  /* L */
    .qs-table th:nth-child(7), .qs-table td:nth-child(7) { min-width: 70px !important; }  /* W */
    .qs-table th:nth-child(8), .qs-table td:nth-child(8) { min-width: 70px !important; }  /* H */
    .qs-table th:nth-child(9), .qs-table td:nth-child(9) { min-width: 90px !important; }  /* Volume/Qty */
    .qs-table th:nth-child(10), .qs-table td:nth-child(10) { min-width: 170px !important; } /* Material */
    .qs-table th:nth-child(11), .qs-table td:nth-child(11) { min-width: 90px !important; }  /* Rate */
    .qs-table th:nth-child(12), .qs-table td:nth-child(12) { min-width: 100px !important; }  /* Subtotal */
    .qs-table th:nth-child(13), .qs-table td:nth-child(13) { width: 40px !important; min-width: 40px !important; text-align: center; } /* Del */

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
                        <div class="d-flex gap-2">
                            <select name="architecture_id" id="archSelect" class="form-select" required>
                                <option value="">-- Select Architecture --</option>
                                <?php foreach ($archs as $a): ?>
                                    <option value="<?= $a['id'] ?>" <?= $a['id'] == $budget['architecture_id'] ? 'selected' : '' ?>
                                            data-link="<?= htmlspecialchars($a['full_architecture_design_link'] ?? '') ?>">
                                        <?= htmlspecialchars('Arch #' . $a['id']) ?>
                                    </option>
                                <?php endforeach; ?>
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
                        <label class="form-label fw-semibold text-muted d-flex justify-content-between align-items-center">
                            <span>Total Area (sqft)</span>
                            <button type="button" class="btn btn-link p-0 text-decoration-none" style="font-size:11px" onclick="syncTotalArea()" title="Sync from Floor Setup">
                                <i class="bi bi-arrow-repeat"></i> Sync
                            </button>
                        </label>
                        <input type="number" step="0.01" name="total_area_sqft" id="totalAreaSqft" class="form-control"
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
                    <!-- Timeline Display -->
                    <div class="col-12 mt-3" id="projectTimelineAlert" style="<?= isset($budget['project_id']) ? '' : 'display:none;' ?>">
                        <div class="alert py-2 mb-0 <?= !empty($projectTimeline) ? 'border-info alert-info' : 'border-warning alert-warning' ?>" id="projectTimelineBox" style="font-size:0.9rem;">
                            <i class="bi <?= !empty($projectTimeline) ? 'bi-calendar-range text-info' : 'bi-exclamation-triangle text-warning' ?> me-2" id="projectTimelineIcon"></i>
                            <span id="projectTimelineText">
                                <?php if (!empty($projectTimeline)): ?>
                                    <strong>Timeline:</strong> 
                                    Start: <span class="badge bg-secondary"><?= $projectTimeline['project_start_date'] ?: 'N/A' ?></span> | 
                                    Construction: <span class="badge bg-secondary"><?= $projectTimeline['construction_start_date'] ?: 'N/A' ?></span> to <span class="badge bg-secondary"><?= $projectTimeline['construction_end_date'] ?: 'N/A' ?></span> | 
                                    Handover: <span class="badge bg-success"><?= $projectTimeline['expected_handover_date'] ?: 'N/A' ?></span>
                                <?php else: ?>
                                    <strong>Warning:</strong> No timeline set for this project. <a href="<?= $base_url ?>/project-timelines" target="_blank">Set Timeline</a>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    <!-- Project Floor Setup Details Display -->
                    <div class="col-12 mt-3">
                        <?php if (!empty($projectFloors)): ?>
                            <?php 
                            $totalSlabArea = 0; 
                            foreach ($projectFloors as $f) {
                                $totalSlabArea += (float)($f['slab_area_sqft'] ?? 0);
                            }
                            ?>
                            <div class="p-3 rounded border" style="background:#f0fdf4; border-color:#bbf7d0;">
                                <div class="d-flex align-items-center justify-content-between mb-2 text-success fw-bold" style="font-size:0.9rem">
                                    <span><i class="bi bi-layers-half"></i> Project Floor Setup Details</span>
                                    <span class="badge bg-success">Total: <?= number_format($totalSlabArea, 2) ?> sqft</span>
                                </div>
                                <div class="d-flex flex-wrap">
                                    <?php foreach ($projectFloors as $f): ?>
                                        <?php 
                                        $label = '';
                                        if ($f['floor_type'] === 'Roof') {
                                            $label = 'Roof (Lvl ' . $f['floor_no'] . ')';
                                        } else if ($f['floor_type'] === 'Ground_Floor') {
                                            $label = 'Ground Floor';
                                        } else if ($f['floor_type'] === 'Basement') {
                                            $label = 'Basement ' . abs($f['floor_no']);
                                        } else {
                                            $n = (int)$f['floor_no'];
                                            $ends = array('th','st','nd','rd','th','th','th','th','th','th');
                                            if ((($n % 100) >= 11) && (($n % 100) <= 13)) {
                                                $ordinal = $n . 'th';
                                            } else {
                                                $ordinal = $n . $ends[$n % 10];
                                            }
                                            $label = $ordinal . ' Floor';
                                        }
                                        ?>
                                        <span class="badge bg-light text-dark border p-2 me-2 mb-2" style="font-size:0.85rem">
                                            <i class="bi bi-building"></i> <?= htmlspecialchars($label) ?>: <strong><?= number_format($f['slab_area_sqft'], 2) ?> sqft</strong>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning py-2 mb-0 d-flex align-items-center gap-2" style="font-size:0.85rem">
                                <i class="bi bi-exclamation-triangle text-warning"></i>
                                <span>This project has no Floor Setup configured. <a href="<?= $base_url ?>/con-floor-setup/<?= $budget['project_id'] ?>" target="_blank" class="fw-bold text-decoration-underline">Set up floors now →</a></span>
                            </div>
                        <?php endif; ?>
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
        const PROJECT_PHASE_ITEMS = <?= $projectPhaseItemsJson ?>;
        const PROJECT_FLOORS = <?= $projectFloorsJson ?>;

        const dtNameMap = {};
        DESIGN_TYPES.forEach(dt => { dtNameMap[dt.id] = dt.name; });
        const matMap = {};
        MATERIALS.forEach(m => { matMap[m.id] = m; });

        let dtBlockCount = 0;
        let projectDrawings = <?= $projectDrawingsJson ?>;
        let projectPhaseItems = PROJECT_PHASE_ITEMS;
        let projectFloors = PROJECT_FLOORS;
        let dtColors = ['dt-color-1', 'dt-color-2', 'dt-color-3', 'dt-color-4',
            'dt-color-5', 'dt-color-6', 'dt-color-7', 'dt-color-8'];

        function getOrdinal(n) {
            let s = ["th", "st", "nd", "rd"],
                v = n % 100;
            return n + (s[(v - 20) % 10] || s[v] || s[0]);
        }

        function buildFloorOptions(floors, selectedType = 'Typical_Floor', selectedNo = null) {
            let html = `<option value="Typical_Floor|" ${selectedType === 'Typical_Floor' && (selectedNo === null || selectedNo === '') ? 'selected' : ''}>General/Unassigned</option>`;
            if (floors && floors.length) {
                floors.forEach(f => {
                    let label = '';
                    if (f.floor_type === 'Roof') {
                        label = `Roof (Lvl ${f.floor_no})`;
                    } else if (f.floor_type === 'Ground_Floor') {
                        label = `Ground Floor`;
                    } else if (f.floor_type === 'Basement') {
                        label = `Basement ${Math.abs(f.floor_no)}`;
                    } else {
                        label = `${getOrdinal(f.floor_no)} Floor`;
                    }
                    const isSel = (String(f.floor_type) === String(selectedType) && (selectedNo !== null && selectedNo !== '' && String(f.floor_no) === String(selectedNo))) ? 'selected' : '';
                    html += `<option value="${f.floor_type}|${f.floor_no}" ${isSel}>${label}</option>`;
                });
            }
            return html;
        }

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
                            <th style="width:25%">Labor Type / Phase Item</th>
                            <th style="width:15%">Floor</th>
                            <th style="width:15%">Description</th>
                            <th style="width:15%">Rate/sqft (৳)</th>
                            <th style="width:15%">Area (sqft)</th>
                            <th style="width:10%">Subtotal</th>
                            <th style="width:5%">Del</th>
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
        function addWtBlock(dtIdx, existingPhaseItemId, existingWtId) {
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
                <i class="bi bi-layers text-primary"></i>
                <select id="phase-item-select-${wtIdx}" class="form-select form-select-sm phase-item-select"
                        style="max-width:240px" onchange="onPhaseItemChange('${wtIdx}', ${dtIdx})">
                    <option value="">-- Select Phase Item --</option>
                </select>
                
                <i class="bi bi-tools text-secondary"></i>
                <select class="form-select form-select-sm wt-select" id="wt-select-${wtIdx}"
                        style="max-width:240px" onchange="onWtChange('${wtIdx}',${dtIdx})">
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
                        <th style="width:35px">SL</th>
                        <th style="width:18%">Location / Description</th>
                        <th style="width:12%">Floor</th>
                        <th style="width:6%">Unit</th>
                        <th style="width:6%">Nos</th>
                        <th style="width:6%">L (ft)</th>
                        <th style="width:6%">W (ft)</th>
                        <th style="width:6%">H (ft)</th>
                        <th style="width:8%">Volume/Qty</th>
                        <th style="width:12%">Material</th>
                        <th style="width:8%">Rate (৳)</th>
                        <th style="width:9%">Subtotal</th>
                        <th style="width:28px">Del</th>
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
            
            const phaseSel = document.getElementById('phase-item-select-' + wtIdx);
            refreshPhaseItemOptions(phaseSel, dtIdx, existingPhaseItemId || '');

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
            <input type="hidden" name="item_phase_item_id[]"   value="${ex.phase_item_id || ''}">
            <input type="number" name="item_sl_no[]" class="form-control-sm form-control" style="width:45px"
                   value="${ex.sl_no || rowIdx}" min="1"></td>
        <td><input type="text" name="item_location_desc[]" class="form-control-sm form-control"
                   value="${ex.location_desc || ''}" placeholder="Location..."></td>
        <td>
            <select name="item_floor_tag[]" class="form-select form-select-sm" style="min-width:110px">
                ${floorOptions}
            </select>
        </td>
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
            updateDtHiddens(tr, dtId, wtId, ex.phase_item_id || '');
        }

        function updateDtHiddens(tr, dtId, wtId, phaseItemId) {
            const dti = tr.querySelector('input[name="item_design_type_id[]"]');
            const wti = tr.querySelector('input[name="item_work_type_id[]"]');
            const pii = tr.querySelector('input[name="item_phase_item_id[]"]');
            if (dti) dti.value = dtId;
            if (wti) wti.value = wtId;
            if (pii) pii.value = phaseItemId || '';
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

            const floorOptions = buildFloorOptions(projectFloors, ex.floor_type, ex.floor_no);

            const tr = document.createElement('tr');
            tr.className = 'labor-row';
            tr.innerHTML = `
        <td><input type="hidden" name="labor_design_type_id[]" value="${dtId}">
            <input type="hidden" name="labor_work_type_id[]"   value="${ex.work_type_id || ''}">
            <input type="text" name="labor_type_name[]" class="form-control form-control-sm mb-1"
                   value="${ex.labor_type_name || ''}" placeholder="Mason, Painter...">
            <select name="labor_phase_item_id[]" class="form-select form-select-sm labor-phase-item-select" onchange="onLaborPhaseItemChange(this)">
                ${phaseItemOpts}
            </select>
        </td>
        <td>
            <select name="labor_floor_tag[]" class="form-select form-select-sm" style="min-width:110px">
                ${floorOptions}
            </select>
        </td>
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
                    const phaseItemSel = wtBlock.querySelector('.phase-item-select');
                    const phaseItemId = phaseItemSel ? phaseItemSel.value : '';
                    wtBlock.querySelectorAll('input[name="item_work_type_id[]"]').forEach(h => { h.value = wtId; });
                    wtBlock.querySelectorAll('input[name="item_phase_item_id[]"]').forEach(h => { h.value = phaseItemId; });
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
                        const compositeKeys = Object.keys(wtGroups);

                        if (compositeKeys.length === 0) {
                            addWtBlock(blockIdx);
                        } else {
                            compositeKeys.forEach(compKey => {
                                const parts = compKey.split('_');
                                const phaseItemId = parts[0] || '0';
                                const wtId = parts[1] || '0';
                                const wtBlockIdx = addWtBlock(blockIdx, phaseItemId, wtId);
                                const rows = wtGroups[compKey];
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
            // Reset
            archSel.innerHTML = '<option value="">-- Loading... --</option>';
            archSel.disabled = true;
            projectDrawings = [];
            const archViewLink = document.getElementById('archViewLink');
            if (archViewLink) {
                archViewLink.href = '#';
                archViewLink.style.display = 'none';
            }

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
                        opt.setAttribute('data-link', a.design_link || '');
                        if (String(a.id) === '<?= $budget['architecture_id'] ?>') opt.selected = true;
                        archSel.appendChild(opt);
                    });
                }
                archSel.disabled = false;
                archSel.dispatchEvent(new Event('change'));

                const seen = {};
                (drawData.drawings || []).forEach(d => {
                    if (!seen[d.design_type_id]) seen[d.design_type_id] = d;
                });
                projectDrawings = Object.values(seen);
                projectPhaseItems = drawData.phase_items || [];
                projectFloors = drawData.floors || [];

                // ── Timeline Data ──────────────────────────────────────────────
                const tl = drawData.timeline;
                const tlAlert = document.getElementById('projectTimelineAlert');
                const tlBox   = document.getElementById('projectTimelineBox');
                const tlIcon  = document.getElementById('projectTimelineIcon');
                const tlText  = document.getElementById('projectTimelineText');
                
                if (tlAlert) tlAlert.style.display = 'block';
                if (tl) {
                    if (tlBox) tlBox.className = 'alert py-2 mb-0 border-info alert-info';
                    if (tlIcon) tlIcon.className = 'bi bi-calendar-range me-2 text-info';
                    if (tlText) tlText.innerHTML = `<strong>Timeline:</strong> 
                        Start: <span class="badge bg-secondary">${tl.project_start_date || 'N/A'}</span> | 
                        Construction: <span class="badge bg-secondary">${tl.construction_start_date || 'N/A'}</span> to <span class="badge bg-secondary">${tl.construction_end_date || 'N/A'}</span> | 
                        Handover: <span class="badge bg-success">${tl.expected_handover_date || 'N/A'}</span>`;
                    
                    // Auto-fill dates if empty
                    const startDateInput = document.querySelector('input[name="start_date"]');
                    const endDateInput   = document.querySelector('input[name="end_date"]');
                    if (startDateInput && !startDateInput.value && tl.construction_start_date) {
                        startDateInput.value = tl.construction_start_date;
                    }
                    if (endDateInput && !endDateInput.value && tl.construction_end_date) {
                        endDateInput.value = tl.construction_end_date;
                    }
                } else {
                    if (tlBox) tlBox.className = 'alert py-2 mb-0 border-warning alert-warning';
                    if (tlIcon) tlIcon.className = 'bi bi-exclamation-triangle me-2 text-warning';
                    if (tlText) tlText.innerHTML = `<strong>Warning:</strong> No timeline set for this project. <a href="${BASE_URL}/project-timelines" target="_blank">Set Timeline</a>`;
                }
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
        window.refreshPhaseItemOptions = refreshPhaseItemOptions;
        window.refreshLaborPhaseItemOptions = refreshLaborPhaseItemOptions;
        window.onPhaseItemChange = onPhaseItemChange;
        window.onLaborPhaseItemChange = onLaborPhaseItemChange;

        function syncTotalArea() {
            let totalSlabArea = 0;
            if (projectFloors && projectFloors.length > 0) {
                projectFloors.forEach(f => {
                    totalSlabArea += parseFloat(f.slab_area_sqft || 0);
                });
            }
            const totalAreaInput = document.getElementById('totalAreaSqft');
            if (totalAreaInput) {
                totalAreaInput.value = totalSlabArea.toFixed(2);
                Swal.fire({
                    icon: 'success',
                    title: 'Synced!',
                    text: 'Total area synced from project floor setup (' + totalSlabArea.toFixed(2) + ' sqft).',
                    timer: 1500,
                    showConfirmButton: false
                });
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Floor Config',
                    text: 'No floor setup configurations found for this project.',
                    confirmButtonColor: 'var(--clr-theme-primary)'
                });
            }
        }
        window.syncTotalArea = syncTotalArea;

        // Init on load
        initExistingBlocks();
        document.getElementById('archSelect').dispatchEvent(new Event('change'));

    })();
</script>
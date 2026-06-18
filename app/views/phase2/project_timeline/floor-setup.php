<?php
$page_title = "Project Floor Setup";
$page = 'Timeline Setup';
$show_breadcrumb = false;
$base = $GLOBALS['base_url'];

// Decode sales floor units meta to calculate sum of units
$unitMetaSum = 0;
$unitDetails = [];
if (!empty($project['floor_wise_unit_meta'])) {
    $meta = json_decode($project['floor_wise_unit_meta'], true);
    $typicalUnits = $meta['typical_floor'] ?? [];
    foreach ($typicalUnits as $u) {
        $unitMetaSum += (float)($u['sqft'] ?? 0);
        $unitDetails[] = ($u['unit_name'] ?? '?') . ' (' . ($u['sqft'] ?? 0) . ' sqft)';
    }
}
?>
<div class="row">
  <div class="col-12">
    <style>
      @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

      .fs-setup-container {
        font-family: 'Plus Jakarta Sans', sans-serif;
      }

      /* ── Breadcrumb & Header ─────────────────────────────── */
      .fs-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 12px;
      }

      .fs-breadcrumb {
        font-size: 12px;
        color: var(--clr-text-muted, #64748b);
        display: flex;
        align-items: center;
        gap: 6px;
      }

      .fs-breadcrumb a {
        color: #3b82f6;
        text-decoration: none;
        font-weight: 500;
      }

      .fs-breadcrumb a:hover {
        text-decoration: underline;
      }

      .fs-title {
        font-size: 24px;
        font-weight: 800;
        margin: 4px 0 0;
        letter-spacing: -0.5px;
      }

      /* ── Grid Layout ─────────────────────────────────────── */
      .fs-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
        align-items: start;
      }

      .fs-panel {
        border-radius: 16px;
        background: var(--clr-bg-card, #ffffff);
        border: 1px solid var(--clr-border-1, rgba(226, 232, 240, 0.8));
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03);
        padding: 24px;
        min-height: 500px;
      }

      .fs-panel-title {
        font-size: 15px;
        font-weight: 700;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--clr-border-1, #f1f5f9);
        display: flex;
        align-items: center;
        justify-content: space-between;
      }

      .fs-panel-title span {
        display: flex;
        align-items: center;
        gap: 8px;
      }

      /* Form inputs */
      .form-control, .form-select {
        border-radius: 10px;
        border: 1px solid var(--clr-border-1, #cbd5e1);
        padding: 8px 12px;
        font-size: 13px;
        background-color: var(--clr-bg-card, #ffffff);
        color: inherit;
        transition: all 0.15s ease;
      }

      .form-control:focus, .form-select:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        outline: none;
      }

      .fs-table-wrapper {
        overflow-x: auto;
        width: 100%;
        margin-bottom: 15px;
      }

      /* Dynamic table */
      .fs-table {
        width: 100%;
        min-width: 750px;
        border-collapse: collapse;
      }

      .fs-table th {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--clr-text-muted, #64748b);
        padding: 10px 8px;
        border-bottom: 2px solid var(--clr-border-1, #e2e8f0);
        text-align: left;
      }

      .fs-table td {
        padding: 10px 8px;
        border-bottom: 1px solid var(--clr-border-1, #f1f5f9);
        vertical-align: middle;
      }

      .btn-add-row {
        background: var(--clr-border-1, #f1f5f9);
        color: inherit;
        border: 1px dashed var(--clr-border-1, #cbd5e1);
        font-size: 12.5px;
        font-weight: 600;
        padding: 8px 16px;
        border-radius: 8px;
        width: 100%;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        margin-top: 15px;
      }

      .btn-add-row:hover {
        background: var(--clr-border-1, #e2e8f0);
        color: inherit;
      }

      .btn-delete-row {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1px solid #fee2e2;
        background: #fef2f2;
        color: #ef4444;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
      }

      .btn-delete-row:hover {
        background: #fee2e2;
        color: #dc2626;
      }

      .btn-save-floors {
        border-radius: 10px;
        font-weight: 700;
        font-size: 14px;
        padding: 12px 24px;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border: none;
      }

      /* ── Building stack visual preview ────────────────────── */
      .building-wrapper {
        background: var(--clr-bg-body, #f8fafc);
        border-radius: 12px;
        border: 1px solid var(--clr-border-1, #e2e8f0);
        padding: 30px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-end;
        min-height: 400px;
        position: relative;
        overflow-y: auto;
        max-height: 600px;
      }

      .building-sky {
        position: absolute;
        top: 20px;
        left: 20px;
        font-size: 11px;
        font-weight: 600;
        color: var(--clr-text-muted, #94a3b8);
        display: flex;
        align-items: center;
        gap: 6px;
      }

      .building-stack {
        display: flex;
        flex-direction: column-reverse;
        width: 100%;
        max-width: 280px;
        gap: 4px;
        z-index: 2;
      }

      .building-floor {
        border-radius: 6px;
        padding: 12px 16px;
        text-align: center;
        color: white;
        font-weight: 700;
        font-size: 12.5px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        position: relative;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-height: 48px;
      }

      .building-floor:hover {
        transform: scale(1.03);
        box-shadow: 0 6px 12px rgba(0,0,0,0.1);
      }

      .floor-meta {
        font-size: 10px;
        font-weight: 500;
        opacity: 0.9;
        margin-top: 2px;
      }

      /* Floor types specific colors */
      .bf-roof {
        background: linear-gradient(135deg, #10b981, #059669);
        border: 1px solid #047857;
      }
      .bf-typical {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        border: 1px solid #1d4ed8;
      }
      .bf-ground {
        background: linear-gradient(135deg, #6366f1, #4f46e5);
        border: 1px solid #4338ca;
      }
      .bf-basement {
        background: linear-gradient(135deg, #64748b, #475569);
        border: 1px solid #334155;
      }

      .building-ground-line {
        width: 120%;
        height: 6px;
        background: var(--clr-border-1, #cbd5e1);
        margin-top: 15px;
        border-radius: 4px;
        z-index: 1;
      }

      .empty-building-state {
        color: var(--clr-text-muted, #94a3b8);
        font-size: 13px;
        font-weight: 600;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
        margin-bottom: auto;
        margin-top: auto;
      }

      .empty-building-state i {
        font-size: 32px;
        color: var(--clr-border-1, #cbd5e1);
      }

      /* Quick fill button group */
      .quick-actions {
        display: flex;
        gap: 8px;
        margin-bottom: 15px;
      }

      .btn-quick {
        font-size: 11px;
        font-weight: 700;
        padding: 5px 10px;
        border-radius: 6px;
        border: 1px solid var(--clr-border-1, #cbd5e1);
        background: var(--clr-bg-card, #ffffff);
        color: inherit;
        transition: all 0.15s ease;
      }

      .btn-quick:hover {
        border-color: #3b82f6;
        color: #3b82f6;
        background: rgba(59, 130, 246, 0.04);
      }
    </style>

    <div class="fs-setup-container">
      
      <!-- ═══════════════════════ HEADER ════════════════════════════ -->
      <div class="fs-header">
        <div>
          <div class="fs-breadcrumb">
            <a href="<?= $base ?>/con-dashboard"><i class="fal fa-tachometer-alt me-1"></i>Dashboard</a>
            <span>›</span>
            <a href="<?= $base ?>/con-projects">Projects</a>
            <span>›</span>
            <a href="<?= $base ?>/con-project/<?= $project['id'] ?>"><?= htmlspecialchars($project['name']) ?></a>
            <span>›</span>
            <span>Floor Configuration</span>
          </div>
          <h4 class="fs-title"><i class="fal fa-layer-group me-2" style="color:#3b82f6"></i>Project Floor Setup</h4>
        </div>
      </div>

      <!-- ═══════════════════════ MAIN CONTENT GRID ════════════════════ -->
      <div class="fs-grid">
        
        <!-- LEFT PANEL: DYNAMIC FORM -->
        <div class="fs-panel">
          <div class="fs-panel-title">
            <span>
              <i class="fal fa-sliders-h" style="color:#3b82f6"></i>
              Floor Configurations for <?= htmlspecialchars($project['name']) ?>
              <?php if (!empty($project['building_type'])): ?>
                <span class="badge bg-secondary ms-2" style="font-size: 11px; font-weight: 600; padding: 4px 8px; border-radius: 6px; background-color: #64748b !important; color: #ffffff;"><?= htmlspecialchars($project['building_type']) ?></span>
              <?php endif; ?>
            </span>
          </div>

          <?php if ($unitMetaSum > 0 || !empty($project['building_type'])): ?>
            <div class="alert alert-info py-2 px-3 mb-3 d-flex align-items-center justify-content-between" style="border-radius: 12px; font-size: 13px; background-color:#eff6ff; border: 1px solid #bfdbfe; color:#1e40af;">
              <div>
                <i class="fal fa-info-circle me-2 text-primary"></i>
                <?php if (!empty($project['building_type'])): ?>
                  <strong>Building Type:</strong> <span class="badge bg-primary px-2 py-1" style="font-size: 12px; background-color: #3b82f6 !important; color: #ffffff;"><?= htmlspecialchars($project['building_type']) ?></span>
                  <?php if ($unitMetaSum > 0): ?><br><span style="display:block; margin-top:6px;"></span><?php endif; ?>
                <?php endif; ?>
                <?php if ($unitMetaSum > 0): ?>
                  <strong>Sales Unit Configuration Found:</strong> Typical Floor Units Sum = <strong><?= number_format($unitMetaSum, 2) ?> SQFT</strong>
                  <span style="display:block; font-size:11px; color:#475569; margin-top:2px;">
                    Units: <?= htmlspecialchars(implode(', ', $unitDetails)) ?>
                  </span>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>

          <div class="quick-actions">
            <button type="button" class="btn-quick" id="btnQuickTypical"><i class="fal fa-plus me-1"></i>Add Typical Floor</button>
            <button type="button" class="btn-quick" id="btnQuickBasement"><i class="fal fa-plus me-1"></i>Add Basement</button>
            <?php if (!empty($project['building_type'])): ?>
              <button type="button" class="btn-quick" id="btnQuickAutoGenerate" style="background-color: #eff6ff; color: #1d4ed8; border-color: #bfdbfe;"><i class="fal fa-magic me-1"></i>Auto-Generate (<?= htmlspecialchars($project['building_type']) ?>)</button>
            <?php endif; ?>
            <button type="button" class="btn-quick" id="btnQuickClear"><i class="fal fa-trash me-1"></i>Clear All Rows</button>
          </div>

          <form action="<?= $base ?>/con-floor-setup-save/<?= $project['id'] ?>" method="POST" id="floorForm">
            <div class="fs-table-wrapper">
              <table class="fs-table" id="floorTable">
                <thead>
                  <tr>
                    <th style="width: 12%">Floor Number</th>
                    <th style="width: 18%">Floor Type</th>
                    <th style="width: 22%">Unit Area (SQFT)</th>
                    <th style="width: 20%">Extra Area (SQFT)</th>
                    <th style="width: 18%">Slab Area (SQFT)</th>
                    <th style="width: 10%">Height (FT)</th>
                    <th style="width: 10%">Action</th>
                  </tr>
                </thead>
                <tbody id="floorTableBody">
                  <!-- Rows loaded via PHP or Javascript -->
                  <?php if (empty($floors)): ?>
                    <!-- Default structure suggestion if empty -->
                    <tr class="floor-row">
                      <td><input type="number" class="form-control floor-no-input" name="floor_no[]" value="0" required></td>
                      <td>
                        <select class="form-select floor-type-select" name="floor_type[]">
                          <option value="Ground_Floor">Ground Floor</option>
                          <option value="Basement">Basement</option>
                          <option value="Typical_Floor">Typical Floor</option>
                          <option value="Roof">Roof</option>
                        </select>
                      </td>
                      <td>
                        <input type="number" step="0.01" class="form-control unit-area-input" name="unit_area_sqft[]" value="<?= number_format($unitMetaSum, 2, '.', '') ?>" placeholder="e.g. 1200" required>
                      </td>
                      <td>
                        <input type="number" step="0.01" class="form-control extra-area-input" name="extra_sqft[]" value="0.00" placeholder="e.g. 300" required>
                      </td>
                      <td>
                        <input type="number" step="0.01" class="form-control slab-area-input" name="slab_area_sqft[]" value="<?= number_format($unitMetaSum, 2, '.', '') ?>" readonly style="background-color: var(--clr-bg-body, #f1f5f9); cursor: not-allowed;" required>
                      </td>
                      <td><input type="number" step="0.1" class="form-control height-input" name="height_ft[]" value="10.0" required></td>
                      <td><button type="button" class="btn-delete-row" title="Delete Floor"><i class="fal fa-times"></i></button></td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($floors as $floor): ?>
                      <tr class="floor-row">
                        <td><input type="number" class="form-control floor-no-input" name="floor_no[]" value="<?= $floor['floor_no'] ?>" required></td>
                        <td>
                          <select class="form-select floor-type-select" name="floor_type[]">
                            <option value="Basement" <?= $floor['floor_type'] === 'Basement' ? 'selected' : '' ?>>Basement</option>
                            <option value="Ground_Floor" <?= $floor['floor_type'] === 'Ground_Floor' ? 'selected' : '' ?>>Ground Floor</option>
                            <option value="Typical_Floor" <?= $floor['floor_type'] === 'Typical_Floor' ? 'selected' : '' ?>>Typical Floor</option>
                            <option value="Roof" <?= $floor['floor_type'] === 'Roof' ? 'selected' : '' ?>>Roof</option>
                          </select>
                        </td>
                        <td>
                          <input type="number" step="0.01" class="form-control unit-area-input" name="unit_area_sqft[]" value="<?= number_format($floor['unit_area_sqft'], 2, '.', '') ?>" required>
                        </td>
                        <td>
                          <input type="number" step="0.01" class="form-control extra-area-input" name="extra_sqft[]" value="<?= number_format($floor['extra_sqft'], 2, '.', '') ?>" required>
                        </td>
                        <td>
                          <input type="number" step="0.01" class="form-control slab-area-input" name="slab_area_sqft[]" value="<?= number_format($floor['slab_area_sqft'], 2, '.', '') ?>" readonly style="background-color: var(--clr-bg-body, #f1f5f9); cursor: not-allowed;" required>
                        </td>
                        <td><input type="number" step="0.1" class="form-control height-input" name="height_ft[]" value="<?= $floor['height_ft'] ?>" required></td>
                        <td><button type="button" class="btn-delete-row" title="Delete Floor"><i class="fal fa-times"></i></button></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

            <button type="button" class="btn-add-row" id="btnAddRow">
              <i class="fal fa-plus-circle"></i> Add Custom Floor Row
            </button>

            <div class="mt-4 pt-3 d-flex justify-content-end border-top" style="border-color:#f1f5f9 !important">
              <button type="submit" class="btn btn-primary btn-save-floors">
                <i class="fal fa-save"></i> Save Floor Configurations
              </button>
            </div>
          </form>
        </div>



      </div>

    </div>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
  const tableBody = document.getElementById("floorTableBody");
  const btnAddRow = document.getElementById("btnAddRow");
  // Redraw Building Stack Visual (Visualization Removed)
  function updateBuildingStack() {
    // No-op
  }

  function getOrdinal(n) {
    let s = ["th", "st", "nd", "rd"],
        v = n % 100;
    return n + (s[(v - 20) % 10] || s[v] || s[0]);
  }

  const UNIT_META_SUM = <?= (float)$unitMetaSum ?>;

  // Create Row DOM
  function createRow(fNo = '', type = 'Typical_Floor', unitArea = null, extraArea = '0.00', height = '10.0') {
    if (unitArea === null) {
      unitArea = (type === 'Typical_Floor' || type === 'Ground_Floor') ? UNIT_META_SUM : 0;
    }
    const unitAreaVal = parseFloat(unitArea) || 0;
    const extraVal = parseFloat(extraArea) || 0;
    const slabVal = unitAreaVal + extraVal;

    const tr = document.createElement("tr");
    tr.className = "floor-row";

    tr.innerHTML = `
      <td><input type="number" class="form-control floor-no-input" name="floor_no[]" value="${fNo}" required></td>
      <td>
        <select class="form-select floor-type-select" name="floor_type[]">
          <option value="Basement" ${type === 'Basement' ? 'selected' : ''}>Basement</option>
          <option value="Ground_Floor" ${type === 'Ground_Floor' ? 'selected' : ''}>Ground Floor</option>
          <option value="Typical_Floor" ${type === 'Typical_Floor' ? 'selected' : ''}>Typical Floor</option>
          <option value="Roof" ${type === 'Roof' ? 'selected' : ''}>Roof</option>
        </select>
      </td>
      <td>
        <input type="number" step="0.01" class="form-control unit-area-input" name="unit_area_sqft[]" value="${unitAreaVal.toFixed(2)}" required>
      </td>
      <td>
        <input type="number" step="0.01" class="form-control extra-area-input" name="extra_sqft[]" value="${extraVal.toFixed(2)}" required>
      </td>
      <td>
        <input type="number" step="0.01" class="form-control slab-area-input" name="slab_area_sqft[]" value="${slabVal.toFixed(2)}" readonly style="background-color: var(--clr-bg-body, #f1f5f9); cursor: not-allowed;" required>
      </td>
      <td><input type="number" step="0.1" class="form-control height-input" name="height_ft[]" value="${height}" required></td>
      <td><button type="button" class="btn-delete-row" title="Delete Floor"><i class="fal fa-times"></i></button></td>
    `;
    return tr;
  }

  // Add Row Handler
  btnAddRow.addEventListener("click", function() {
    // Guess next floor no based on existing
    const inputs = tableBody.querySelectorAll(".floor-no-input");
    let nextNo = 1;
    if (inputs.length > 0) {
      let max = -999;
      inputs.forEach(inp => {
        let val = parseInt(inp.value);
        if (!isNaN(val) && val > max) max = val;
      });
      nextNo = max + 1;
    }

    const newRow = createRow(nextNo);
    tableBody.appendChild(newRow);
    bindRowEvents(newRow);
    updateBuildingStack();
  });

  // Bind Events for input changes & deletion
  function bindRowEvents(row) {
    const deleteBtn = row.querySelector(".btn-delete-row");
    deleteBtn.addEventListener("click", function() {
      row.remove();
      updateBuildingStack();
    });

    const unitInput = row.querySelector(".unit-area-input");
    const extraInput = row.querySelector(".extra-area-input");
    const slabInput = row.querySelector(".slab-area-input");
    const typeSelect = row.querySelector(".floor-type-select");

    function recalcRowSlab() {
      const uVal = parseFloat(unitInput.value) || 0;
      const eVal = parseFloat(extraInput.value) || 0;
      slabInput.value = (uVal + eVal).toFixed(2);
      updateBuildingStack();
    }

    if (unitInput && extraInput) {
      unitInput.addEventListener("input", recalcRowSlab);
      extraInput.addEventListener("input", recalcRowSlab);
    }

    if (typeSelect) {
      typeSelect.addEventListener("change", function() {
        const selectedType = typeSelect.value;
        if (selectedType === 'Typical_Floor' || selectedType === 'Ground_Floor') {
          unitInput.value = UNIT_META_SUM.toFixed(2);
        } else {
          unitInput.value = (0).toFixed(2);
        }
        recalcRowSlab();
      });
    }

    const inputs = row.querySelectorAll("input, select");
    inputs.forEach(input => {
      input.addEventListener("input", updateBuildingStack);
      input.addEventListener("change", updateBuildingStack);
    });
  }

  // Initialize existing rows
  tableBody.querySelectorAll(".floor-row").forEach(row => {
    bindRowEvents(row);
  });

  // Quick action Typical Floor
  document.getElementById("btnQuickTypical").addEventListener("click", function() {
    const inputs = tableBody.querySelectorAll(".floor-no-input");
    let nextNo = 1;
    if (inputs.length > 0) {
      let max = 0;
      inputs.forEach(inp => {
        let val = parseInt(inp.value);
        if (!isNaN(val) && val > max) max = val;
      });
      nextNo = max + 1;
    }
    const newRow = createRow(nextNo, 'Typical_Floor', null, '0.00', '10.0');
    tableBody.appendChild(newRow);
    bindRowEvents(newRow);
    updateBuildingStack();
  });

  // Quick action Basement
  document.getElementById("btnQuickBasement").addEventListener("click", function() {
    const inputs = tableBody.querySelectorAll(".floor-no-input");
    let nextNo = -1;
    if (inputs.length > 0) {
      let min = 0;
      inputs.forEach(inp => {
        let val = parseInt(inp.value);
        if (!isNaN(val) && val < min) min = val;
      });
      nextNo = min - 1;
    }
    const newRow = createRow(nextNo, 'Basement', 0, '0.00', '11.0');
    tableBody.appendChild(newRow);
    bindRowEvents(newRow);
    updateBuildingStack();
  });

  // Quick action Clear All
  document.getElementById("btnQuickClear").addEventListener("click", function() {
    tableBody.innerHTML = "";
    updateBuildingStack();
  });

  // Parse building_type function
  function parseBuildingType(str) {
    if (!str) return null;
    str = str.toUpperCase().replace(/\s+/g, '');
    const parts = str.split('+');
    let basements = []; // array of negative numbers
    let ground = false;
    let typicalCount = 0;
    
    parts.forEach(part => {
      // Match e.g. "B", "B1", "B2", "B3"
      let mB = part.match(/^B(\d*)$/);
      if (mB) {
        let num = mB[1] ? parseInt(mB[1]) : 1;
        if (!basements.includes(-num)) {
          basements.push(-num);
        }
        return;
      }
      
      // Match e.g. "2B", "3B"
      let mB2 = part.match(/^(\d+)B$/);
      if (mB2) {
        let count = parseInt(mB2[1]);
        for (let i = 1; i <= count; i++) {
          if (!basements.includes(-i)) {
            basements.push(-i);
          }
        }
        return;
      }
      
      if (part === 'G') {
        ground = true;
        return;
      }
      
      // Typical floors
      let num = parseInt(part);
      if (!isNaN(num)) {
        typicalCount = num;
      }
    });
    
    // If we got individual basements like B2, make sure lower basements exist
    if (basements.length > 0) {
      let minB = Math.min(...basements);
      for (let i = -1; i >= minB; i--) {
        if (!basements.includes(i)) {
          basements.push(i);
        }
      }
    }
    
    basements.sort((a, b) => a - b);
    return { basements, ground, typicalCount };
  }

  const BUILDING_TYPE = "<?= $project['building_type'] ?? '' ?>";

  function autoGenerateFromBuildingType(quiet = false) {
    if (!BUILDING_TYPE) return;
    const parsed = parseBuildingType(BUILDING_TYPE);
    if (!parsed) return;

    if (!quiet) {
      if (tableBody.querySelectorAll(".floor-row").length > 0) {
        if (!confirm("This will clear all current rows and auto-generate floors from building type: " + BUILDING_TYPE + ". Proceed?")) {
          return;
        }
      }
    }

    tableBody.innerHTML = "";

    // 1. Basements
    parsed.basements.forEach(bNo => {
      const row = createRow(bNo, 'Basement', 0, UNIT_META_SUM || 1500, 11.0);
      tableBody.appendChild(row);
      bindRowEvents(row);
    });

    // 2. Ground Floor
    if (parsed.ground) {
      const row = createRow(0, 'Ground_Floor', UNIT_META_SUM || 1500, 0, 10.0);
      tableBody.appendChild(row);
      bindRowEvents(row);
    }

    // 3. Typical Floors
    if (parsed.typicalCount > 0) {
      for (let i = 1; i <= parsed.typicalCount; i++) {
        const row = createRow(i, 'Typical_Floor', UNIT_META_SUM || 1500, 0, 10.0);
        tableBody.appendChild(row);
        bindRowEvents(row);
      }
    }

    updateBuildingStack();
    
    if (!quiet && window.Swal) {
      Swal.fire({
        icon: 'success',
        title: 'Auto-Generated!',
        text: 'Floors generated according to Building Type: ' + BUILDING_TYPE,
        timer: 2000,
        showConfirmButton: false
      });
    }
  }

  const btnAutoGenerate = document.getElementById("btnQuickAutoGenerate");
  if (btnAutoGenerate) {
    btnAutoGenerate.addEventListener("click", function() {
      autoGenerateFromBuildingType(false);
    });
  }

  // Trigger on load if no floors are configured in database
  const HAS_EXISTING_FLOORS = <?= !empty($floors) ? 'true' : 'false' ?>;
  if (!HAS_EXISTING_FLOORS && BUILDING_TYPE) {
    autoGenerateFromBuildingType(true);
  }

  // Initial draw
  updateBuildingStack();
});
</script>

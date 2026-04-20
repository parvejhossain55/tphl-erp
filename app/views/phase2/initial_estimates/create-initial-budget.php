<div class="pagetitle d-flex justify-content-between align-items-center">
    <div>
        <h1><?= htmlspecialchars($page_title) ?></h1>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-estimates">Initial Estimates</a></li>
                <li class="breadcrumb-item active">New Estimate</li>
            </ol>
        </nav>
    </div>
    <a href="<?= $base_url ?>/con-estimates" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

<section class="section">
    <form action="<?= $base_url ?>/con-estimate-store" method="POST" id="estimateForm">

        <!-- SECTION 1: Budget Header -->
        <div class="card__wrapper shadow-sm mb-4">
            <div class="card__header">
                <h6 class="card__title mb-0"><i class="bi bi-info-circle me-2"></i>Budget Header</h6>
            </div>
            <div class="card__body p-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Project <span class="text-danger">*</span></label>
                        <select name="project_id" class="form-select" required>
                            <option value="">-- Choose Project --</option>
                            <?php foreach ($projects as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Architecture Record <span class="text-danger">*</span></label>
                        <select name="architecture_id" class="form-select" required>
                            <option value="">-- Choose Architecture --</option>
                            <?php foreach ($archs as $a): ?>
                                <option value="<?= $a['id'] ?>">
                                    <?= htmlspecialchars($a['project_name'] ?? 'Arch #' . $a['id']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Start Date</label>
                        <input type="date" name="start_date" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">End Date</label>
                        <input type="date" name="end_date" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Total Area (sqft)</label>
                        <input type="number" step="0.01" name="total_area_sqft" id="totalAreaSqft"
                               class="form-control" placeholder="0.00">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Other Costs (৳)</label>
                        <input type="number" step="0.01" name="other_costs" id="otherCosts"
                               class="form-control" value="0" placeholder="Transport, Permit…">
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 2: Material Items -->
        <div class="card__wrapper shadow-sm mb-4">
            <div class="card__header d-flex justify-content-between align-items-center">
                <h6 class="card__title mb-0">
                    <i class="bi bi-boxes me-2"></i>Material Items
                    <small class="text-muted">(Design Type wise)</small>
                </h6>
                <button type="button" class="btn btn-outline-primary btn-sm" id="addItemRow">
                    <i class="bi bi-plus-circle me-1"></i> Add Row
                </button>
            </div>
            <div class="card__body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" id="itemsTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width:18%">Design Type</th>
                                <th style="width:25%">Material</th>
                                <th style="width:8%">Unit</th>
                                <th style="width:13%">Qty</th>
                                <th style="width:13%">Unit Cost (৳)</th>
                                <th style="width:13%">Subtotal</th>
                                <th style="width:10%" class="text-center">Del</th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <tr class="item-row">
                                <td>
                                    <select name="item_design_type_id[]" class="form-select form-select-sm" required>
                                        <option value="">-- Type --</option>
                                        <?php foreach ($designTypes as $dt): ?>
                                            <option value="<?= $dt['id'] ?>"><?= htmlspecialchars($dt['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="item_material_id[]" class="form-select form-select-sm mat-select" required>
                                        <option value="">-- Material --</option>
                                        <?php foreach ($materials as $m): ?>
                                            <option value="<?= $m['id'] ?>" data-unit="<?= htmlspecialchars($m['unit']) ?>">
                                                <?= htmlspecialchars($m['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="item_material_unit[]"
                                           class="form-control form-control-sm unit-display"
                                           readonly placeholder="-">
                                </td>
                                <td>
                                    <input type="number" step="0.001" name="item_estimated_qty[]"
                                           class="form-control form-control-sm item-qty" placeholder="0.000" min="0">
                                </td>
                                <td>
                                    <input type="number" step="0.01" name="item_unit_cost[]"
                                           class="form-control form-control-sm item-cost" placeholder="0.00" min="0">
                                </td>
                                <td class="text-end fw-bold item-subtotal pe-2">0.00</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-item-row" style="display:none;">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="table-light">
                                <td colspan="5" class="text-end fw-bold py-2">Material Total:</td>
                                <td class="text-end fw-bold text-success pe-2" id="materialTotal">0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- SECTION 3: Labor Costs -->
        <div class="card__wrapper shadow-sm mb-4">
            <div class="card__header d-flex justify-content-between align-items-center">
                <h6 class="card__title mb-0">
                    <i class="bi bi-people me-2"></i>Labor Costs
                    <small class="text-muted">(Design Type wise)</small>
                </h6>
                <button type="button" class="btn btn-outline-primary btn-sm" id="addLaborRow">
                    <i class="bi bi-plus-circle me-1"></i> Add Row
                </button>
            </div>
            <div class="card__body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" id="laborsTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width:20%">Design Type</th>
                                <th style="width:30%">Labor Type Name</th>
                                <th style="width:15%">Rate / sqft (৳)</th>
                                <th style="width:15%">Area (sqft)</th>
                                <th style="width:12%">Subtotal</th>
                                <th style="width:8%" class="text-center">Del</th>
                            </tr>
                        </thead>
                        <tbody id="laborsBody">
                            <tr class="labor-row">
                                <td>
                                    <select name="labor_design_type_id[]" class="form-select form-select-sm" required>
                                        <option value="">-- Type --</option>
                                        <?php foreach ($designTypes as $dt): ?>
                                            <option value="<?= $dt['id'] ?>"><?= htmlspecialchars($dt['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="labor_type_name[]"
                                           class="form-control form-control-sm"
                                           placeholder="e.g., Civil Work, Painting, Tiles">
                                </td>
                                <td>
                                    <input type="number" step="0.01" name="labor_rate_per_sqft[]"
                                           class="form-control form-control-sm labor-rate" placeholder="0.00" min="0">
                                </td>
                                <td>
                                    <input type="number" step="0.01" name="labor_area_sqft[]"
                                           class="form-control form-control-sm labor-area" placeholder="0.00" min="0">
                                </td>
                                <td class="text-end fw-bold labor-subtotal pe-2">0.00</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-labor-row" style="display:none;">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="table-light">
                                <td colspan="4" class="text-end fw-bold py-2">Labor Total:</td>
                                <td class="text-end fw-bold text-info pe-2" id="laborTotal">0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- SECTION 4: Live Cost Summary -->
        <div class="card__wrapper shadow-sm mb-4">
            <div class="card__header">
                <h6 class="card__title mb-0">
                    <i class="bi bi-calculator me-2"></i>Cost Summary <small class="text-muted">(Live Preview)</small>
                </h6>
            </div>
            <div class="card__body p-4">
                <div class="row g-3 text-center">
                    <div class="col-6 col-md-3">
                        <div class="p-3 border rounded bg-light">
                            <div class="text-muted small mb-1">Material Cost</div>
                            <div class="fw-bold fs-5 text-success" id="summaryMaterial">৳ 0.00</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 border rounded bg-light">
                            <div class="text-muted small mb-1">Labor Cost</div>
                            <div class="fw-bold fs-5 text-info" id="summaryLabor">৳ 0.00</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 border rounded bg-light">
                            <div class="text-muted small mb-1">Other Costs</div>
                            <div class="fw-bold fs-5 text-warning" id="summaryOther">৳ 0.00</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 border rounded bg-primary text-white">
                            <div class="small mb-1">Grand Total Estimate</div>
                            <div class="fw-bold fs-4" id="summaryGrand">৳ 0.00</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-end mb-5">
            <a href="<?= $base_url ?>/con-estimates" class="btn btn-secondary me-2">Cancel</a>
            <button type="submit" class="btn btn-primary px-5 py-2">
                <i class="bi bi-save me-1"></i> Save Estimate
            </button>
        </div>

    </form>
</section>

<!-- Pure Vanilla JS — same pattern as lead-lists.php -->
<script>
document.addEventListener('DOMContentLoaded', function () {

    // Format number with 2 decimal places
    function fmt(n) {
        return parseFloat(n || 0).toLocaleString('en-BD', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // Recalculate all totals
    function recalc() {
        var matTotal   = 0;
        var labTotal   = 0;
        var otherEl    = document.getElementById('otherCosts');
        var otherCosts = otherEl ? (parseFloat(otherEl.value) || 0) : 0;

        document.querySelectorAll('#itemsBody .item-row').forEach(function (row) {
            var qty  = parseFloat(row.querySelector('.item-qty').value)  || 0;
            var cost = parseFloat(row.querySelector('.item-cost').value) || 0;
            var sub  = qty * cost;
            matTotal += sub;
            row.querySelector('.item-subtotal').textContent = fmt(sub);
        });

        document.querySelectorAll('#laborsBody .labor-row').forEach(function (row) {
            var rate = parseFloat(row.querySelector('.labor-rate').value) || 0;
            var area = parseFloat(row.querySelector('.labor-area').value) || 0;
            var sub  = rate * area;
            labTotal += sub;
            row.querySelector('.labor-subtotal').textContent = fmt(sub);
        });

        var grand = matTotal + labTotal + otherCosts;

        document.getElementById('materialTotal').textContent   = fmt(matTotal);
        document.getElementById('laborTotal').textContent      = fmt(labTotal);
        document.getElementById('summaryMaterial').textContent = '৳ ' + fmt(matTotal);
        document.getElementById('summaryLabor').textContent    = '৳ ' + fmt(labTotal);
        document.getElementById('summaryOther').textContent    = '৳ ' + fmt(otherCosts);
        document.getElementById('summaryGrand').textContent    = '৳ ' + fmt(grand);
    }

    // Material select → auto-fill unit field (event delegation)
    document.getElementById('itemsBody').addEventListener('change', function (e) {
        if (e.target.classList.contains('mat-select')) {
            var sel  = e.target.options[e.target.selectedIndex];
            var unit = sel ? (sel.getAttribute('data-unit') || '') : '';
            e.target.closest('tr').querySelector('.unit-display').value = unit;
            recalc();
        }
    });

    // Recalc on qty/cost input
    document.getElementById('itemsBody').addEventListener('input', function (e) {
        if (e.target.classList.contains('item-qty') || e.target.classList.contains('item-cost')) {
            recalc();
        }
    });

    // Recalc on labor input
    document.getElementById('laborsBody').addEventListener('input', function (e) {
        if (e.target.classList.contains('labor-rate') || e.target.classList.contains('labor-area')) {
            recalc();
        }
    });

    // Other costs field
    var otherEl = document.getElementById('otherCosts');
    if (otherEl) otherEl.addEventListener('input', recalc);

    // Add Material row
    document.getElementById('addItemRow').addEventListener('click', function () {
        var tbody  = document.getElementById('itemsBody');
        var source = tbody.querySelector('.item-row');
        var newRow = source.cloneNode(true);
        newRow.querySelectorAll('input').forEach(function (el) { el.value = ''; });
        newRow.querySelectorAll('select').forEach(function (el) { el.selectedIndex = 0; });
        newRow.querySelector('.item-subtotal').textContent = '0.00';
        var btn = newRow.querySelector('.remove-item-row');
        if (btn) btn.style.display = 'inline-block';
        tbody.appendChild(newRow);
    });

    // Remove Material row
    document.getElementById('itemsBody').addEventListener('click', function (e) {
        var btn = e.target.closest('.remove-item-row');
        if (btn) {
            var rows = document.querySelectorAll('#itemsBody .item-row');
            if (rows.length > 1) {
                btn.closest('tr').remove();
                recalc();
            }
        }
    });

    // Add Labor row
    document.getElementById('addLaborRow').addEventListener('click', function () {
        var tbody  = document.getElementById('laborsBody');
        var source = tbody.querySelector('.labor-row');
        var newRow = source.cloneNode(true);
        newRow.querySelectorAll('input').forEach(function (el) { el.value = ''; });
        newRow.querySelectorAll('select').forEach(function (el) { el.selectedIndex = 0; });
        newRow.querySelector('.labor-subtotal').textContent = '0.00';
        var btn = newRow.querySelector('.remove-labor-row');
        if (btn) btn.style.display = 'inline-block';
        tbody.appendChild(newRow);
    });

    // Remove Labor row
    document.getElementById('laborsBody').addEventListener('click', function (e) {
        var btn = e.target.closest('.remove-labor-row');
        if (btn) {
            var rows = document.querySelectorAll('#laborsBody .labor-row');
            if (rows.length > 1) {
                btn.closest('tr').remove();
                recalc();
            }
        }
    });

    // Run on page load
    recalc();
});
</script>

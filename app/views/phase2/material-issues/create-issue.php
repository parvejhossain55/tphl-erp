<div class="pagetitle d-flex justify-content-between align-items-center">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-material-issues">Material Issues</a></li>
                <li class="breadcrumb-item active">New Issue</li>
            </ol>
        </nav>
    </div>
    <a href="<?= $base_url ?>/con-material-issues" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

<section class="section">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card__wrapper shadow-sm">
                <div class="card__header">
                    <h6 class="card__title mb-0">
                        <i class="bi bi-box-arrow-right me-2"></i>Issue Material from Warehouse (Stock OUT)
                    </h6>
                </div>
                <div class="card__body p-4">
                    <form action="<?= $base_url ?>/con-material-issue-store" method="POST" id="issueForm">

                        <div class="alert alert-warning small mb-4">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            <strong>Stock OUT:</strong> Issuing material will deduct from warehouse stock immediately.
                        </div>

                        <!-- Row 1: Project + Warehouse + Date -->
                        <div class="row  mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Project <span class="text-danger">*</span></label>
                                <select name="project_id" class="form-select" required>
                                    <option value="">-- Select Project --</option>
                                    <?php foreach ($projects as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Warehouse <span class="text-danger">*</span></label>
                                <select name="warehouse_id" id="issueWarehouse" class="form-select" required>
                                    <option value="">-- Select Warehouse --</option>
                                    <?php foreach ($warehouses as $w): ?>
                                        <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Issue Date</label>
                                <input type="date" name="issue_date" class="form-control"
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>

                        <!-- Row 2: Material + Qty -->
                        <div class="row  mb-3">
                            <div class="col-md-5">
                                <label class="form-label fw-bold">Material <span class="text-danger">*</span></label>
                                <select name="material_id" id="issueMaterial" class="form-select" required>
                                    <option value="">-- Select Material --</option>
                                    <?php foreach ($materials as $m): ?>
                                        <option value="<?= $m['id'] ?>" data-unit="<?= $m['unit'] ?>">
                                            <?= htmlspecialchars($m['name']) ?> (<?= $m['unit'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Available Stock</label>
                                <div id="availableStock" class="form-control bg-light text-center fw-bold" style="pointer-events:none;">
                                    —
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Issue Quantity <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.001" name="quantity" id="issueQty"
                                           class="form-control" required min="0.001" placeholder="0.000">
                                    <span class="input-group-text" id="issueUnit">—</span>
                                </div>
                            </div>
                        </div>

                        <!-- Row 3: Issued To + Purpose -->
                        <div class="row  mb-3">
                            <div class="col-md-5">
                                <label class="form-label fw-bold">Issued To (Person/Contractor)</label>
                                <input type="text" name="issued_to" class="form-control"
                                       placeholder="e.g., Contractor Rahim, Site Engineer Karim...">
                            </div>
                            <div class="col-md-7">
                                <label class="form-label fw-bold">Purpose</label>
                                <input type="text" name="purpose" class="form-control"
                                       placeholder="e.g., Floor 3 construction, Electrical work...">
                            </div>
                        </div>

                        <div class="mt-5 text-end">
                            <a href="<?= $base_url ?>/con-material-issues" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4 fw-bold"
                                    onclick="return confirm('Issue this material? Stock will be deducted immediately.');">
                                <i class="bi bi-box-arrow-right me-1"></i> Issue Material
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Stock data lookup: key = "warehouseId_materialId" → available qty
var stockData = <?= json_encode(
    array_reduce($stocks, function($carry, $s) {
        $key = $s['warehouse_id'] . '_' . $s['material_id'];
        $carry[$key] = (float)$s['current_quantity'];
        return $carry;
    }, [])
) ?>;

document.addEventListener('DOMContentLoaded', function () {
    var warehouseSel = document.getElementById('issueWarehouse');
    var materialSel  = document.getElementById('issueMaterial');
    var availDiv     = document.getElementById('availableStock');
    var unitSpan     = document.getElementById('issueUnit');
    var qtyInput     = document.getElementById('issueQty');

    function updateAvailable() {
        var wid = warehouseSel.value;
        var mid = materialSel.value;
        var opt = materialSel.options[materialSel.selectedIndex];
        var unit = opt && opt.getAttribute('data-unit') ? opt.getAttribute('data-unit') : '—';
        unitSpan.textContent = unit;

        if (wid && mid) {
            var key = wid + '_' + mid;
            var avail = stockData[key] || 0;
            availDiv.innerHTML = '<span class="' + (avail > 0 ? 'text-success' : 'text-danger') + '">' +
                avail.toFixed(3) + '</span> <small class="text-muted">' + unit + '</small>';
            qtyInput.max = avail;
        } else {
            availDiv.textContent = '—';
        }
    }

    warehouseSel.addEventListener('change', updateAvailable);
    materialSel.addEventListener('change', updateAvailable);
});
</script>

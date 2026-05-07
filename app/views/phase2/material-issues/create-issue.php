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
    <!-- STEP 1: Select Project Cards -->
    <div id="projectCardsSection">
        <h5 class="mb-3 text-muted fw-bold"><i class="bi bi-building me-2"></i>Select a Project to Issue Material</h5>
        <div class="row g-3">
            <?php if (!empty($projects)): ?>
                <?php foreach ($projects as $p): ?>
                    <div class="col-md-4 col-sm-6">
                        <div class="card card__wrapper h-100 border-0 shadow-sm" style="cursor: pointer; transition: transform 0.2s;" onclick="selectProject(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>')" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                            <div class="card-body p-4 text-center">
                                <div class="project-icon mb-3">
                                    <i class="fas fa-city fa-3x" style=""></i>
                                </div>
                                <h6 class="fw-bold mb-1" style=""><?= htmlspecialchars($p['name']) ?></h6>
                                <span class="badge bg-success bg-opacity-10 text-light rounded-pill p-2 mt-2">
                                    <i class="fas fa-check-circle me-1"></i>Material Received
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-box-open fa-3x text-muted mb-3 opacity-50"></i>
                    <h5 class="text-muted">No projects available for issuing.</h5>
                    <p class="text-muted small">Only projects with completed material receivings (GRN) will appear here.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- STEP 2: Issue Material Form -->
    <div id="issueFormSection" style="display: none;">
        
        <div class="d-flex align-items-center mb-4">
            <button class="btn btn-outline-secondary btn-sm me-3" onclick="backToProjects()">
                <i class="fas fa-arrow-left me-1"></i> Back to Projects
            </button>
            <h5 class="mb-0 fw-bold" style="color:var(--cd-blue)">Issuing Material For: <span id="selectedProjectName" class=""></span></h5>
        </div>

        <div class="row">
            <!-- Left Side: Information Panels -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4" style="border-top: 4px solid var(--cd-blue) !important;">
                    <div class="card-header bg-transparent border-bottom-0 pt-4 pb-0">
                        <h6 class="fw-bold"><i class="fas fa-boxes-stacked me-2 text-primary"></i>Received Materials (GRN)</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Material</th>
                                        <th class="text-end">Qty</th>
                                        <th>Unit</th>
                                    </tr>
                                </thead>
                                <tbody id="receivedMaterialsTbody">
                                    <!-- Populated by JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4" style="border-top: 4px solid #d97706 !important;">
                    <div class="card-header bg-transparent border-bottom-0 pt-4 pb-0">
                        <h6 class="fw-bold"><i class="fas fa-bullseye me-2 text-warning"></i>Fixed Targets</h6>
                    </div>
                    <div class="card-body p-0" id="projectTargetsDiv">
                        <!-- Populated by JS -->
                    </div>
                </div>
            </div>

            <!-- Right Side: Issue Form -->
            <div class="col-lg-8">
                <div class="card__wrapper shadow-sm">
                    <div class="card__header">
                        <h6 class="card__title mb-0">
                            <i class="bi bi-box-arrow-right me-2"></i>Issue Material (Stock OUT)
                        </h6>
                    </div>
                    <div class="card__body p-4">
                        <form action="<?= $base_url ?>/con-material-issue-store" method="POST" id="issueForm">
                            <input type="hidden" name="project_id" id="hiddenProjectId">

                            <div class="alert alert-warning small mb-4">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                <strong>Stock OUT:</strong> Issuing material will deduct from warehouse stock immediately.
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Warehouse <span class="text-danger">*</span></label>
                                    <select name="warehouse_id" id="issueWarehouse" class="form-select" required>
                                        <option value="">-- Select Warehouse --</option>
                                        <?php foreach ($warehouses as $w): ?>
                                            <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Issue Date</label>
                                    <input type="date" name="issue_date" class="form-control"
                                           value="<?= date('Y-m-d') ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Target <span class="text-muted">(Optional)</span></label>
                                    <select name="target_id" id="issueTarget" class="form-select">
                                        <option value="">-- Select Target --</option>
                                        <!-- Populated by JS -->
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Working Plan <span class="text-muted">(Optional)</span></label>
                                    <select name="work_plan_id" id="issueWorkPlan" class="form-select">
                                        <option value="">-- Select Plan --</option>
                                        <?php foreach ($workPlans as $wp): ?>
                                            <option value="<?= $wp['id'] ?>" data-design="<?= htmlspecialchars($wp['design_type_name'] ?? '') ?>">
                                                <?= htmlspecialchars(date('M Y', strtotime($wp['year'].'-'.$wp['month'].'-01')) . ' - ' . $wp['work_description']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Design Type</label>
                                    <input type="text" id="autoDesignType" class="form-control bg-light" readonly placeholder="Auto-selected based on plan...">
                                </div>
                            </div>

                            <div class="row mb-3">
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

                            <div class="row mb-3">
                                <div class="col-md-5">
                                    <label class="form-label fw-bold">Issued To (Contractor) <span class="text-danger">*</span></label>
                                    <select name="issued_to" class="form-select" required>
                                        <option value="">-- Select Contractor --</option>
                                        <?php foreach ($contractors ?? [] as $c): ?>
                                            <option value="<?= $c['id'] ?>">
                                                <?= htmlspecialchars($c['name']) ?>
                                                <?= !empty($c['trade']) ? ' (' . htmlspecialchars($c['trade']) . ')' : '' ?>
                                                <?= !empty($c['phone']) ? ' - ' . htmlspecialchars($c['phone']) : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-7">
                                    <label class="form-label fw-bold">Purpose</label>
                                    <input type="text" name="purpose" class="form-control"
                                           placeholder="e.g., Floor 3 construction, Electrical work...">
                                </div>
                            </div>

                            <div class="mt-4 text-end">
                                <button type="submit" class="btn btn-primary px-4 fw-bold"
                                        onclick="return confirm('Issue this material? Stock will be deducted immediately.');">
                                    <i class="bi bi-check2-circle me-1"></i> Issue Material
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- PHP to JS conversion for easy dynamic UI -->
<script>
const receivedMaterials = <?= json_encode($receivedMaterials ?? []) ?>;
const projectTargets = <?= json_encode(array_reduce($targets ?? [], function($carry, $t) {
    $carry[$t['project_id']][] = $t;
    return $carry;
}, [])) ?>;

// Stock data lookup: key = "warehouseId_materialId" → available qty
var stockData = <?= json_encode(
    array_reduce($stocks, function($carry, $s) {
        $key = $s['warehouse_id'] . '_' . $s['material_id'];
        $carry[$key] = (float)$s['current_quantity'];
        return $carry;
    }, [])
) ?>;

function selectProject(projectId, projectName) {
    document.getElementById('projectCardsSection').style.display = 'none';
    document.getElementById('issueFormSection').style.display = 'block';
    document.getElementById('hiddenProjectId').value = projectId;
    document.getElementById('selectedProjectName').textContent = projectName;
    
    // Populate Received Materials Table
    const matTbody = document.getElementById('receivedMaterialsTbody');
    matTbody.innerHTML = '';
    if (receivedMaterials[projectId] && receivedMaterials[projectId].length > 0) {
        receivedMaterials[projectId].forEach(mat => {
            matTbody.innerHTML += `
                <tr>
                    <td class="fw-semibold">${mat.material_name}</td>
                    <td class="text-end fw-bold text-primary">${parseFloat(mat.total_received).toFixed(2)}</td>
                    <td>${mat.unit}</td>
                </tr>`;
        });
    } else {
        matTbody.innerHTML = `<tr><td colspan="3" class="text-center text-muted">No material received yet.</td></tr>`;
    }

    // Populate Targets List
    const targetDiv = document.getElementById('projectTargetsDiv');
    targetDiv.innerHTML = '';
    
    const targetSelect = document.getElementById('issueTarget');
    targetSelect.innerHTML = '<option value="">-- Select Target --</option>';

    if (projectTargets[projectId] && projectTargets[projectId].length > 0) {
        let ul = '<ul class="list-group list-group-flush">';
        projectTargets[projectId].forEach(t => {
            ul += `<li class="list-group-item d-flex justify-content-between align-items-center">
                       <div><i class="fas fa-bullseye me-2 text-warning"></i>${t.target_title || 'Target #'+t.id}</div>
                       <span class="badge bg-primary rounded-pill">${t.target_percentage}%</span>
                   </li>`;
            targetSelect.innerHTML += `<option value="${t.id}">${t.target_title || 'Target #'+t.id}</option>`;
        });
        ul += '</ul>';
        targetDiv.innerHTML = ul;
    } else {
        targetDiv.innerHTML = `<div class="text-muted text-center py-2">No targets fixed for this project yet.</div>`;
    }

    // Reset Form
    document.getElementById('issueForm').reset();
    document.getElementById('availableStock').innerHTML = '—';
    document.getElementById('issueUnit').textContent = '—';
    document.getElementById('hiddenProjectId').value = projectId;
}

function backToProjects() {
    document.getElementById('issueFormSection').style.display = 'none';
    document.getElementById('projectCardsSection').style.display = 'block';
}

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

    // Auto-select Design Type based on Working Plan
    var workPlanSel = document.getElementById('issueWorkPlan');
    var designTypeDisplay = document.getElementById('autoDesignType');

    workPlanSel.addEventListener('change', function() {
        var opt = workPlanSel.options[workPlanSel.selectedIndex];
        if (opt && opt.value) {
            var design = opt.getAttribute('data-design') || 'Not specified';
            designTypeDisplay.value = design;
        } else {
            designTypeDisplay.value = '';
        }
    });

});
</script>

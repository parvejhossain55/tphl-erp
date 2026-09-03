<?php
// Defaults for variables passed from controller
$phasesByProject       = $phasesByProject       ?? [];
$monthlyTargetsByPhase = $monthlyTargetsByPhase ?? [];
$receivedMaterials     = $receivedMaterials     ?? [];
$warehouses            = $warehouses            ?? [];
$materials             = $materials             ?? [];
$stocks                = $stocks                ?? [];
$contractors           = $contractors           ?? [];
$projects              = $projects              ?? [];

$fromWeekly   = $fromWeekly   ?? false;
$preProjectId = (int)($preProjectId ?? 0);
$prePhaseId   = (int)($prePhaseId   ?? 0);
$preTargetId  = (int)($preTargetId  ?? 0); // phase_monthly_targets.id
$preLogDate   = $preLogDate   ?? '';
$backUrl      = $backUrl      ?? '';
?>

<div class="pagetitle d-flex justify-content-between align-items-center mb-4">

      
        <nav>
            <ol class="breadcrumb mb-0">
                  <h4><?= htmlspecialchars($page_title) ?></h4>
            </ol>
        </nav>
    <div class="d-flex gap-2">
        <?php if ($fromWeekly && !empty($backUrl)): ?>
        <a href="<?= htmlspecialchars($backUrl) ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-arrow-left-circle me-1"></i>Back to Weekly Progress
        </a>
        <?php endif; ?>
        <a href="<?= $base_url ?>/con-material-issues" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
    </div>
</div>

<?php if ($fromWeekly && !empty($preLogDate)): ?>
<div class="alert alert-info d-flex align-items-center gap-2 mb-3" style="border-radius:12px">
    <i class="bi bi-calendar-check-fill fs-5"></i>
    <div>Logging material used on <strong><?= date('d M Y', strtotime($preLogDate)) ?></strong>. After saving, you'll be returned to your weekly progress entry.</div>
</div>
<?php endif; ?>

<section class="section">
    <!-- STEP 1: Select Project Cards -->
    <div id="projectCardsSection" <?= $preProjectId ? 'style="display:none"' : '' ?>>
        <h5 class="mb-3 text-muted fw-bold"><i class="bi bi-building me-2"></i>Select a Project to Issue Material</h5>
        <div class="row ">
            <?php if (!empty($projects)): ?>
                <?php foreach ($projects as $p): ?>
                    <div class="col-md-4 col-sm-6">
                        <div class="card card__wrapper h-100 border-0 shadow-sm"
                             style="cursor:pointer;transition:transform 0.2s;"
                             onclick="selectProject(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>')"
                             onmouseover="this.style.transform='translateY(-5px)'"
                             onmouseout="this.style.transform='translateY(0)'">
                            <div class="card-body p-4 text-center">
                                <div class="project-icon mb-3">
                                    <i class="fas fa-city fa-3x"></i>
                                </div>
                                <h6 class="fw-bold mb-1"><?= htmlspecialchars($p['name']) ?></h6>
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
    <div id="issueFormSection" <?= $preProjectId ? '' : 'style="display:none;"' ?>>

        <div class="d-flex align-items-center mb-4">
            <button class="btn btn-outline-secondary btn-sm me-3" onclick="backToProjects()" type="button">
                <i class="fas fa-arrow-left me-1"></i> Back to Projects
            </button>
            <h5 class="mb-0 fw-bold" style="color:var(--cd-blue)">
                Issuing Material For: <span id="selectedProjectName"></span>
            </h5>
        </div>

        <div class="row">
            <!-- Left Side: Information Panels -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4" style="border-top:4px solid var(--cd-blue) !important;">
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

                <!-- Selected Target Summary -->
                <div class="card border-0 shadow-sm mb-4" style="border-top:4px solid #0ea5e9 !important;" id="selectedTargetCard">
                    <div class="card-header bg-transparent border-bottom-0 pt-3 pb-0">
                        <h6 class="fw-bold"><i class="bi bi-bullseye me-2 text-info"></i>Selected Monthly Target</h6>
                    </div>
                    <div class="card-body py-2" id="selectedTargetBody">
                        <div class="text-muted text-center py-2" style="font-size:12px">
                            <i class="bi bi-arrow-up-right-circle me-1"></i>Select Phase & Month above
                        </div>
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
                            <input type="hidden" name="project_id"            id="hiddenProjectId"   value="<?= $preProjectId ?: '' ?>">
                            <input type="hidden" name="phase_monthly_target_id" id="hiddenTargetId"  value="<?= $preTargetId  ?: '' ?>">
                            <input type="hidden" name="back_url"              value="<?= htmlspecialchars($backUrl) ?>">

                            <div class="alert alert-warning small mb-4">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                <strong>Stock OUT:</strong> Issuing material will deduct from warehouse stock immediately.
                            </div>

                            <!-- ── Row 1: Phase → Month ── -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">
                                        Phase <span class="text-danger">*</span>
                                    </label>
                                    <select id="phaseSelect" class="form-select" onchange="onPhaseChange(this.value)">
                                        <option value="">-- Select Phase --</option>
                                        <!-- Populated by JS when project selected -->
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">
                                        Month (Target) <span class="text-danger">*</span>
                                    </label>
                                    <select id="monthSelect" class="form-select" onchange="onMonthChange(this.value)" disabled>
                                        <option value="">-- Select Month --</option>
                                    </select>
                                </div>
                            </div>

                            <!-- ── Row 2: Warehouse + Issue Date ── -->
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
                                    <label class="form-label fw-bold">Issue Date <span class="text-danger">*</span></label>
                                    <input type="date" name="issue_date" class="form-control"
                                           value="<?= $preLogDate ?: date('Y-m-d') ?>" required>
                                </div>
                            </div>

                            <!-- ── Row 3: Material + Available Stock + Qty ── -->
                            <div class="row mb-3">
                                <div class="col-md-5">
                                    <label class="form-label fw-bold">Material <span class="text-danger">*</span></label>
                                    <select name="material_id" id="issueMaterial" class="form-select" required>
                                        <option value="">-- Select Material --</option>
                                        <?php foreach ($materials as $m): ?>
                                            <option value="<?= $m['id'] ?>" data-unit="<?= $m['unit'] ?>">
                                                <?= htmlspecialchars($m['name'] . ' [' . ($m['category_name'] ?? 'Uncategorized') . '] (' . $m['unit'] . ')') ?>
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

                            <!-- ── Row 4: Issued To + Purpose ── -->
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
                                           placeholder="e.g., Floor 3 construction, Column casting...">
                                </div>
                            </div>

                            <div class="mt-4 text-end">
                                <button type="submit" class="btn btn-primary px-5 fw-bold"
                                        id="submitBtn"
                                        onclick="return validateAndConfirm()">
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

<!-- PHP → JS data bridges -->
<script>
const receivedMaterials     = <?= json_encode($receivedMaterials) ?>;
const phasesByProject       = <?= json_encode($phasesByProject) ?>;
const monthlyTargetsByPhase = <?= json_encode($monthlyTargetsByPhase) ?>;

// Stock: "warehouseId_materialId" → qty
const stockData = <?= json_encode(
    array_reduce($stocks, function($carry, $s) {
        $key = $s['warehouse_id'] . '_' . $s['material_id'];
        $carry[$key] = (float)$s['current_quantity'];
        return $carry;
    }, [])
) ?>;

// Pre-fill values from weekly progress (0 means not set)
const preProjectId = <?= (int)$preProjectId ?>;
const prePhaseId   = <?= (int)$prePhaseId ?>;
const preTargetId  = <?= (int)$preTargetId ?>;

// ── Project selection ──────────────────────────────────────────────────────

function selectProject(projectId, projectName) {
    document.getElementById('projectCardsSection').style.display = 'none';
    document.getElementById('issueFormSection').style.display = 'block';
    document.getElementById('hiddenProjectId').value = projectId;
    document.getElementById('selectedProjectName').textContent = projectName;

    // Populate received materials table
    const matTbody = document.getElementById('receivedMaterialsTbody');
    matTbody.innerHTML = '';
    const mats = receivedMaterials[projectId] || [];
    if (mats.length > 0) {
        mats.forEach(mat => {
            matTbody.innerHTML += `
                <tr>
                    <td>
                        <span class="fw-semibold">${mat.material_name}</span>
                        <br><small class="text-muted">${mat.category_name || 'Uncategorized'}</small>
                    </td>
                    <td class="text-end fw-bold text-primary">${parseFloat(mat.total_received).toFixed(2)}</td>
                    <td>${mat.unit}</td>
                </tr>`;
        });
    } else {
        matTbody.innerHTML = `<tr><td colspan="3" class="text-center text-muted">No material received yet.</td></tr>`;
    }

    // Populate Phase dropdown
    const phaseSel = document.getElementById('phaseSelect');
    phaseSel.innerHTML = '<option value="">-- Select Phase --</option>';
    const phases = phasesByProject[projectId] || [];
    phases.forEach(ph => {
        const opt = document.createElement('option');
        opt.value = ph.phase_id;
        opt.textContent = ph.phase_name;
        phaseSel.appendChild(opt);
    });

    // Reset month and target
    resetMonthSelect();
    resetTargetSummary();

    // If pre-filled from weekly progress
    if (prePhaseId) {
        phaseSel.value = prePhaseId;
        onPhaseChange(prePhaseId, preTargetId);
    }
}

function backToProjects() {
    document.getElementById('issueFormSection').style.display = 'none';
    document.getElementById('projectCardsSection').style.display = 'block';
}

// ── Phase selection ────────────────────────────────────────────────────────

function onPhaseChange(phaseId, autoSelectTargetId) {
    phaseId = parseInt(phaseId) || 0;
    const monthSel = document.getElementById('monthSelect');
    monthSel.innerHTML = '<option value="">-- Select Month --</option>';
    resetTargetSummary();
    document.getElementById('hiddenTargetId').value = '';

    if (!phaseId) {
        monthSel.disabled = true;
        return;
    }

    const targets = monthlyTargetsByPhase[phaseId] || [];
    if (targets.length === 0) {
        monthSel.innerHTML = '<option value="">No monthly targets for this phase</option>';
        monthSel.disabled = true;
        return;
    }

    targets.forEach(t => {
        const d = new Date(t.target_month + 'T00:00:00');
        const label = d.toLocaleString('en-US', { month: 'long', year: 'numeric' });
        const opt = document.createElement('option');
        opt.value = t.monthly_target_id;
        opt.setAttribute('data-phase-id', t.phase_id);
        opt.setAttribute('data-month', t.target_month);
        opt.setAttribute('data-status', t.planning_status);
        opt.textContent = label + ' [' + t.planning_status + ']';
        monthSel.appendChild(opt);
    });

    monthSel.disabled = false;

    // Auto-select if pre-filled
    if (autoSelectTargetId) {
        monthSel.value = autoSelectTargetId;
        onMonthChange(autoSelectTargetId);
    }
}

// ── Month/Target selection ─────────────────────────────────────────────────

function onMonthChange(targetId) {
    targetId = parseInt(targetId) || 0;
    document.getElementById('hiddenTargetId').value = targetId || '';

    if (!targetId) {
        resetTargetSummary();
        return;
    }

    // Find the selected option details
    const sel = document.getElementById('monthSelect');
    const opt = sel.options[sel.selectedIndex];
    const month = opt.getAttribute('data-month') || '';
    const status = opt.getAttribute('data-status') || '';

    const d = new Date(month + 'T00:00:00');
    const monthLabel = d.toLocaleString('en-US', { month: 'long', year: 'numeric' });

    const statusColors = {
        'Approved': '#10b981', 'In Review': '#f59e0b', 'Draft': '#64748b'
    };
    const color = statusColors[status] || '#64748b';

    document.getElementById('selectedTargetBody').innerHTML = `
        <div class="d-flex flex-column gap-2">
            <div>
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;font-weight:700">Monthly Target</div>
                <div style="font-size:15px;font-weight:800;color:#1e293b">${monthLabel}</div>
            </div>
            <div>
                <span style="background:${color}20;color:${color};font-size:11px;font-weight:700;
                             padding:3px 10px;border-radius:999px">${status}</span>
            </div>
            <div style="font-size:11px;color:#64748b">Target ID: PMT-${targetId}</div>
        </div>`;
}

function resetMonthSelect() {
    const monthSel = document.getElementById('monthSelect');
    monthSel.innerHTML = '<option value="">-- Select Month --</option>';
    monthSel.disabled = true;
}

function resetTargetSummary() {
    document.getElementById('selectedTargetBody').innerHTML = `
        <div class="text-muted text-center py-2" style="font-size:12px">
            <i class="bi bi-arrow-up-right-circle me-1"></i>Select Phase & Month above
        </div>`;
}

// ── Stock availability display ─────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
    const warehouseSel = document.getElementById('issueWarehouse');
    const materialSel  = document.getElementById('issueMaterial');
    const availDiv     = document.getElementById('availableStock');
    const unitSpan     = document.getElementById('issueUnit');
    const qtyInput     = document.getElementById('issueQty');

    function updateAvailable() {
        const wid  = warehouseSel.value;
        const mid  = materialSel.value;
        const opt  = materialSel.options[materialSel.selectedIndex];
        const unit = opt && opt.getAttribute('data-unit') ? opt.getAttribute('data-unit') : '—';
        unitSpan.textContent = unit;

        if (wid && mid) {
            const key   = wid + '_' + mid;
            const avail = stockData[key] || 0;
            availDiv.innerHTML = '<span class="' + (avail > 0 ? 'text-success' : 'text-danger') + '">' +
                avail.toFixed(3) + '</span> <small class="text-muted">' + unit + '</small>';
            qtyInput.max = avail;
        } else {
            availDiv.textContent = '—';
        }
    }

    warehouseSel.addEventListener('change', updateAvailable);
    materialSel.addEventListener('change',  updateAvailable);

    // ── Auto-trigger pre-fill on page load if coming from weekly progress ──
    if (preProjectId > 0) {
        // Find the project name from the project card list
        const projectNameEl = document.getElementById('selectedProjectName');
        // Look for project name in the cards section (even if hidden)
        const cards = document.querySelectorAll('#projectCardsSection .card__wrapper, #projectCardsSection .card');
        let foundName = 'Project #' + preProjectId;
        cards.forEach(c => {
            const h6 = c.querySelector('h6');
            if (h6) foundName = h6.textContent.trim();
        });
        // Try PHP-embedded fallback
        <?php if ($preProjectId): ?>
        foundName = <?= json_encode(array_reduce($projects, function($c, $p) use ($preProjectId) {
            return (int)$p['id'] === $preProjectId ? $p['name'] : $c;
        }, 'Project #' . $preProjectId)) ?>;
        <?php endif; ?>

        selectProject(preProjectId, foundName);
    }
});

// ── Form validation ────────────────────────────────────────────────────────

function validateAndConfirm() {
    const targetId = document.getElementById('hiddenTargetId').value;
    if (!targetId) {
        alert('Please select a Phase and Monthly Target before issuing.');
        return false;
    }
    return confirm('Issue this material? Stock will be deducted immediately.');
}
</script>

<?php
$base_url   = $GLOBALS['base_url'];
$page_title = $page_title ?? 'New Work Order';
?>
<div class="container-fluid">
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h4 class="page-title fw-semibold fs-18 mb-0"><?= $page_title ?></h4>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-work-orders">Work Orders</a></li>
                <li class="breadcrumb-item active">New</li>
            </ol>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-xl-10">
            <form action="<?= $base_url ?>/con-work-order-store" method="POST">
                <!-- Section 1: Basic Info -->
                <div class="card custom-card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0"><i class="bi bi-info-circle me-2 text-primary"></i>Basic Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row ">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Project <span class="text-danger">*</span></label>
                                <select name="project_id" id="wo_project_id" class="form-select" required onchange="loadWorkPlans(this.value)">
                                    <option value="">-- Select Project --</option>
                                    <?php foreach ($projects as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Contractor <span class="text-danger">*</span></label>
                                <select name="contractor_id" class="form-select" required>
                                    <option value="">-- Select Contractor --</option>
                                    <?php foreach ($contractors as $c): ?>
                                        <option value="<?= $c['id'] ?>">
                                            <?= htmlspecialchars($c['name']) ?>
                                            (<?= htmlspecialchars($c['trade'] ?? $c['role'] ?? '') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Work Type</label>
                                <select name="work_type_id" class="form-select">
                                    <option value="">-- Select Work Type --</option>
                                    <?php foreach ($workTypes as $wt): ?>
                                        <option value="<?= $wt['id'] ?>"><?= htmlspecialchars($wt['work_type_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Work Order Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" required
                                       placeholder="e.g. Foundation Work — Block A">
                            </div>

                            <!-- Target Fixed Link (cascading) -->
                            <div class="col-12"><hr class="my-1"><small class="text-muted fw-semibold">Link to Target Fixed (optional — select Project first)</small></div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Working Plan</label>
                                <select name="work_plan_id" id="wo_work_plan_id" class="form-select" onchange="loadTargets(this.value)">
                                    <option value="">-- Select Working Plan --</option>
                                </select>
                                <small class="text-muted">Select Project first to load plans</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Target Fixed (Week)</label>
                                <select name="target_id" id="wo_target_id" class="form-select" onchange="autoFillFromTarget(this)">
                                    <option value="">-- Select Target --</option>
                                </select>
                                <small class="text-muted">Select Working Plan first</small>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Scope of Work</label>
                                <textarea name="scope_of_work" class="form-control" rows="4"
                                          placeholder="Describe the full scope of work assigned to the contractor..."></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Notes <span class="text-muted small">(optional)</span></label>
                                <textarea name="notes" class="form-control" rows="2"
                                          placeholder="Any additional notes or conditions..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Rate & Budget -->
                <div class="card custom-card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0"><i class="bi bi-calculator me-2 text-success"></i>Rate & Budget</h6>
                    </div>
                    <div class="card-body">
                        <div class="row ">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Unit</label>
                                <select name="unit" class="form-select">
                                    <option value="">-- Select Unit --</option>
                                    <option value="sqft">SqFt</option>
                                    <option value="rft">RFT</option>
                                    <option value="cft">CFT</option>
                                    <option value="nos">Nos</option>
                                    <option value="kg">KG</option>
                                    <option value="ton">Ton</option>
                                    <option value="ls">LS (Lump Sum)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Unit Rate (৳)</label>
                                <input type="number" name="unit_rate" id="wo_unit_rate" class="form-control"
                                       step="0.01" min="0" placeholder="0.00" oninput="calcTotal()">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Quantity</label>
                                <input type="number" name="quantity" id="wo_quantity" class="form-control"
                                       step="0.01" min="0" placeholder="0.00" oninput="calcTotal()">
                            </div>
                            <div class="col-12">
                                <div class="alert alert-primary py-2 mb-0">
                                    <strong>Total Contract Amount: ৳<span id="wo_total_display">0.00</span></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Timeline -->
                <div class="card custom-card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0"><i class="bi bi-calendar-range me-2 text-warning"></i>Timeline</h6>
                    </div>
                    <div class="card-body">
                        <div class="row ">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">End Date (Deadline)</label>
                                <input type="date" name="end_date" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Payment Terms -->
                <div class="card custom-card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0"><i class="bi bi-wallet2 me-2 text-danger"></i>Payment Terms</h6>
                    </div>
                    <div class="card-body">
                        <div class="row ">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Advance (%)</label>
                                <input type="number" name="advance_percent" id="wo_adv_pct" class="form-control"
                                       value="20" min="0" max="100" step="1" oninput="calcPayments()">
                                <small class="text-muted">Paid before work starts</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Running Bill (%)</label>
                                <input type="number" name="running_percent" id="wo_run_pct" class="form-control"
                                       value="70" min="0" max="100" step="1" oninput="calcPayments()">
                                <small class="text-muted">Paid during work progress</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Retention (%)</label>
                                <input type="number" name="retention_percent" id="wo_ret_pct" class="form-control"
                                       value="10" min="0" max="100" step="1" oninput="calcPayments()">
                                <small class="text-muted">Held until QC approval</small>
                            </div>
                        </div>

                        <!-- Payment breakdown -->
                        <div class="row  mt-2">
                            <div class="col-md-4">
                                <div class="p-3 rounded bg-info-transparent border border-info-subtle">
                                    <div class="text-muted fs-12">Advance Amount</div>
                                    <div class="fs-16 fw-bold text-info">৳<span id="wo_adv_amount">0.00</span></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 rounded bg-primary-transparent border border-primary-subtle">
                                    <div class="text-muted fs-12">Running Bill Amount</div>
                                    <div class="fs-16 fw-bold text-primary">৳<span id="wo_run_amount">0.00</span></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 rounded bg-danger-transparent border border-danger-subtle">
                                    <div class="text-muted fs-12">Retention Amount</div>
                                    <div class="fs-16 fw-bold text-danger">৳<span id="wo_ret_amount">0.00</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mb-5">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i> Create Work Order
                    </button>
                    <a href="<?= $base_url ?>/con-work-orders" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= $base_url ?>';

// ── Cascade: Project → Work Plans ─────────────────────────────────────────
function loadWorkPlans(projectId) {
    const wpSel = document.getElementById('wo_work_plan_id');
    const tgSel = document.getElementById('wo_target_id');

    // Reset downstream
    wpSel.innerHTML = '<option value="">-- Select Working Plan --</option>';
    tgSel.innerHTML = '<option value="">-- Select Target --</option>';

    if (!projectId) return;

    fetch(`${BASE_URL}/con-ajax-wo-work-plans?project_id=${projectId}`)
        .then(r => r.json())
        .then(data => {
            data.work_plans.forEach(wp => {
                const label = `${wp.work_description} (${wp.month}/${wp.year})`;
                wpSel.innerHTML += `<option value="${wp.id}">${label}</option>`;
            });
        });
}

// ── Cascade: Work Plan → Targets ──────────────────────────────────────────
function loadTargets(planId) {
    const tgSel = document.getElementById('wo_target_id');
    tgSel.innerHTML = '<option value="">-- Select Target --</option>';
    if (!planId) return;

    fetch(`${BASE_URL}/con-ajax-wo-targets?work_plan_id=${planId}`)
        .then(r => r.json())
        .then(data => {
            data.targets.forEach(t => {
                const label = `${t.target_title} (Week ${t.week_number} | ${t.target_percentage}%)`;
                tgSel.innerHTML += `<option value="${t.id}">${label}</option>`;
            });
        });
}

// ── Auto-fill title hint from selected target ─────────────────────────────
function autoFillFromTarget(sel) {
    const label = sel.options[sel.selectedIndex]?.text ?? '';
    const titleInput = document.querySelector('[name="title"]');
    if (titleInput && !titleInput.value && label) {
        titleInput.value = label.split(' (')[0]; // use target title as default
    }
}

// ── Rate Calculations ─────────────────────────────────────────────────────
function calcTotal() {
    const rate = parseFloat(document.getElementById('wo_unit_rate').value) || 0;
    const qty  = parseFloat(document.getElementById('wo_quantity').value)  || 0;
    const total = rate * qty;
    document.getElementById('wo_total_display').textContent = total.toLocaleString('en-BD', {minimumFractionDigits: 2});
    calcPayments();
    return total;
}

function calcPayments() {
    const total  = calcTotalSilent();
    const advPct = parseFloat(document.getElementById('wo_adv_pct').value) || 0;
    const runPct = parseFloat(document.getElementById('wo_run_pct').value) || 0;
    const retPct = parseFloat(document.getElementById('wo_ret_pct').value) || 0;

    const fmt = v => v.toLocaleString('en-BD', {minimumFractionDigits: 2});
    document.getElementById('wo_adv_amount').textContent = fmt(total * advPct / 100);
    document.getElementById('wo_run_amount').textContent = fmt(total * runPct / 100);
    document.getElementById('wo_ret_amount').textContent = fmt(total * retPct / 100);
    document.getElementById('wo_total_display').textContent = fmt(total);
}

function calcTotalSilent() {
    const rate = parseFloat(document.getElementById('wo_unit_rate').value) || 0;
    const qty  = parseFloat(document.getElementById('wo_quantity').value)  || 0;
    return rate * qty;
}

// Init
calcPayments();
</script>

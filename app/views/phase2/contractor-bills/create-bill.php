<?php
$base_url   = $GLOBALS['base_url'];
$page_title = $page_title ?? 'New Contractor Bill';
?>
<div class="container-fluid">
    <!-- Breadcrumb -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h4 class="page-title fw-semibold fs-18 mb-0"><?= $page_title ?></h4>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-contractor-bills">Contractor Bills</a></li>
                <li class="breadcrumb-item active">New Bill</li>
            </ol>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-xl-8">
            <div class="card custom-card">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-receipt me-2 text-primary"></i> Contractor Bill Form</h6>
                </div>
                <div class="card-body">
                    <form action="<?= $base_url ?>/con-contractor-bill-store" method="POST" enctype="multipart/form-data">

                        <div class="row ">
                            <!-- Project -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Project <span class="text-danger">*</span></label>
                                <select name="project_id" id="project_id" class="form-select" required>
                                    <option value="">-- Select Project --</option>
                                    <?php foreach ($projects as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Contractor -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Contractor <span class="text-danger">*</span></label>
                                <select name="operator_id" class="form-select" required>
                                    <option value="">-- Select Contractor --</option>
                                    <?php foreach ($operators as $o): ?>
                                        <option value="<?= $o['id'] ?>">
                                            <?= htmlspecialchars($o['name']) ?>
                                            (<?= htmlspecialchars($o['trade'] ?? $o['role'] ?? '') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Work Order (cascades from project) -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Link to Work Order <span class="text-danger">*</span>
                                </label>
                                <select name="work_order_id" id="work_order_id" class="form-select" required disabled>
                                    <option value="">-- Select Project First --</option>
                                </select>
                            </div>

                            <!-- Target (cascades from project via AJAX) -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Link to Site Target <span class="text-muted small">(optional)</span>
                                </label>
                                <select name="target_id" id="target_id" class="form-select" disabled>
                                    <option value="">-- Select Project First --</option>
                                </select>
                                <div id="targetBudgetInfo" class="alert alert-warning mt-2 d-none py-2 small">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    Bill requires a linked target with Gain % &gt; 0 for approval.
                                    <span id="targetGainText" class="ms-2 fw-semibold"></span>
                                </div>
                            </div>

                            <!-- Work Type -->
                            <div class="col-md-12 mt-3">
                                <label class="form-label fw-semibold">Work Type <span class="text-danger">*</span></label>
                                <select name="work_type_id" id="work_type_id" class="form-select" required>
                                    <option value="">-- Select Work Type --</option>
                                    <?php foreach ($workTypes as $wt): ?>
                                        <option value="<?= $wt['id'] ?>"><?= htmlspecialchars($wt['work_type_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">A Passed QC Inspection is required for this work type.</small>
                            </div>

                            <!-- Bill Date -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Bill Date <span class="text-danger">*</span></label>
                                <input type="date" name="bill_date" class="form-control"
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>

                            <!-- Bill Amount -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Bill Amount (৳) <span class="text-danger">*</span></label>
                                <input type="number" name="bill_amount" class="form-control"
                                       step="0.01" min="0" placeholder="0.00" required>
                            </div>

                            <!-- Work Description -->
                            <div class="col-12">
                                <label class="form-label fw-semibold">Work Description <span class="text-danger">*</span></label>
                                <textarea name="work_description" class="form-control" rows="4" required
                                          placeholder="Describe the work done by the contractor..."></textarea>
                            </div>

                            <!-- Attachment -->
                            <div class="col-12">
                                <label class="form-label fw-semibold">
                                    Attachment <span class="text-muted small">(optional)</span>
                                </label>
                                <input type="file" name="attachment" class="form-control"
                                       accept=".pdf,.jpg,.jpeg,.png">
                                <div class="form-text">Supported: PDF, JPG, PNG (max 5MB)</div>
                            </div>
                        </div>

                        <hr class="my-4">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send me-1"></i> Submit Bill
                            </button>
                            <a href="<?= $base_url ?>/con-contractor-bills" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= $base_url ?>';

document.getElementById('project_id').addEventListener('change', function () {
    const pid        = this.value;
    const targetSel  = document.getElementById('target_id');
    const woSel      = document.getElementById('work_order_id');
    const infoBox    = document.getElementById('targetBudgetInfo');
    const gainTxt    = document.getElementById('targetGainText');

    targetSel.innerHTML = '<option value="">-- Loading targets... --</option>';
    targetSel.disabled  = true;
    woSel.innerHTML     = '<option value="">-- Loading work orders... --</option>';
    woSel.disabled      = true;
    infoBox.classList.add('d-none');
    gainTxt.textContent = '';

    if (!pid) {
        targetSel.innerHTML = '<option value="">-- Select Project First --</option>';
        woSel.innerHTML     = '<option value="">-- Select Project First --</option>';
        return;
    }

    // Fetch Work Orders for this project
    fetch(BASE_URL + '/con-ajax-wo-by-project?project_id=' + pid)
        .then(r => r.json())
        .then(data => {
            woSel.innerHTML = '<option value="">(No linked work order)</option>';
            if (data.work_orders && data.work_orders.length) {
                data.work_orders.forEach(wo => {
                    const total = parseFloat(wo.total_amount) || 0;
                    const billed = parseFloat(wo.billed_amount) || 0;
                    const rem = total - billed;
                    woSel.innerHTML += `<option value="${wo.id}">${wo.wo_number} — ${wo.title} (${wo.contractor_name}) | Limit: ৳${rem.toFixed(2)} remaining</option>`;
                });
                woSel.disabled = false;
            }
        });

    // Fetch targets for this project from the targets endpoint
    fetch(BASE_URL + '/con-ajax-project-targets?project_id=' + pid)
        .then(r => r.json())
        .then(data => {
            targetSel.innerHTML = '<option value="">(No linked target)</option>';
            if (!data.targets || !data.targets.length) {
                targetSel.innerHTML += '<option disabled>No approved targets for this project</option>';
            } else {
                data.targets.forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = t.id;
                    opt.textContent = '[' + t.year + '/W' + (t.week_number || 'Monthly') + '] '
                                    + t.target_title + ' — Gain: ' + t.gain_percentage + '%';
                    opt.dataset.gain = t.gain_percentage;
                    targetSel.appendChild(opt);
                });
            }
            targetSel.disabled = false;
        })
        .catch(() => {
            targetSel.innerHTML = '<option value="">(Error loading targets)</option>';
            targetSel.disabled  = false;
        });
});

document.getElementById('target_id').addEventListener('change', function () {
    const opt     = this.options[this.selectedIndex];
    const infoBox = document.getElementById('targetBudgetInfo');
    const gainTxt = document.getElementById('targetGainText');

    if (this.value && opt.dataset.gain !== undefined) {
        infoBox.classList.remove('d-none');
        const gain = parseFloat(opt.dataset.gain);
        gainTxt.textContent = gain > 0
            ? '✅ Gain ' + gain + '% — eligible for approval'
            : '⚠️ Gain 0% — approval will be blocked';
    } else {
        infoBox.classList.add('d-none');
    }
});
</script>

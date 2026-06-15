<?php
$base_url   = $GLOBALS['base_url'];
$page_title = $page_title ?? 'New Contractor Bill';
?>
<div class="container-fluid">
    <!-- Breadcrumb -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <ol class="breadcrumb mb-0">
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
                                <div id="woDetailsBox" class="alert alert-info mt-2 d-none py-2 small">
                                    <h6 class="fw-bold mb-1"><i class="bi bi-info-circle me-1"></i> Work Order Details</h6>
                                    <div id="woDetailsContent"></div>
                                </div>
                            </div>

                            <!-- Target (cascades from project via AJAX) -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Link to Site Target <span class="text-muted small">(optional)</span>
                                </label>
                                <select name="target_id" id="target_id" class="form-select" disabled>
                                    <option value="">-- Select Project First --</option>
                                </select>
                                <div id="targetBudgetInfo" class="alert alert-info mt-2 d-none py-2 small">
                                    <i class="bi bi-info-circle me-1"></i>
                                    <span id="targetGainText" class="fw-semibold"></span>
                                </div>
                            </div>

                            <!-- Work Type is dynamically fetched from Work Order in controller, no selector needed -->

                            <!-- Bill Type -->
                            <div class="col-md-6 mt-3">
                                <label class="form-label fw-semibold">Bill Type <span class="text-danger">*</span></label>
                                <select name="bill_type" class="form-select" required>
                                    <option value="Progress">Progress (After Work)</option>
                                    <option value="Advance">Advance Payment</option>
                                    <option value="Retention">Retention Payment</option>
                                </select>
                                <small class="text-muted">Select the type of payment request.</small>
                            </div>

                            <!-- Bill Date -->
                            <div class="col-md-6 mt-3">
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

let currentWorkOrders = [];

document.getElementById('project_id').addEventListener('change', function () {
    const pid        = this.value;
    const targetSel  = document.getElementById('target_id');
    const woSel      = document.getElementById('work_order_id');
    const infoBox    = document.getElementById('targetBudgetInfo');
    const gainTxt    = document.getElementById('targetGainText');
    const woBox      = document.getElementById('woDetailsBox');

    targetSel.innerHTML = '<option value="">-- Loading targets... --</option>';
    targetSel.disabled  = true;
    woSel.innerHTML     = '<option value="">-- Loading work orders... --</option>';
    woSel.disabled      = true;
    infoBox.classList.add('d-none');
    woBox.classList.add('d-none');
    gainTxt.textContent = '';
    currentWorkOrders = [];

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
                currentWorkOrders = data.work_orders;
                data.work_orders.forEach(wo => {
                    const total = parseFloat(wo.total_amount) || 0;
                    const billed = parseFloat(wo.billed_amount) || 0;
                    const rem = total - billed;
                    
                    const advPct = parseFloat(wo.advance_percent) || 0;
                    const advAmt = parseFloat(wo.advance_amount) || 0;
                    const paidAmt = parseFloat(wo.paid_amount) || 0;
                    
                    let advStatus = '';
                    if (advPct > 0) {
                        const isAdvPaid = paidAmt >= advAmt ? 'Paid' : (paidAmt > 0 ? 'Partially Paid' : 'Not Paid');
                        advStatus = ` | Adv: ${advPct}% (${isAdvPaid})`;
                    }
                    
                    woSel.innerHTML += `<option value="${wo.id}">${wo.wo_number} — ${wo.title} (${wo.contractor_name}) | Limit: ৳${rem.toFixed(2)} remaining${advStatus}</option>`;
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
        infoBox.classList.remove('d-none', 'alert-warning', 'alert-success', 'alert-info');
        const gain = parseFloat(opt.dataset.gain);
        if (gain > 0) {
            infoBox.classList.add('alert-success');
            gainTxt.textContent = 'Gain ' + gain + '% — eligible for progress bill';
        } else {
            infoBox.classList.add('alert-warning');
            gainTxt.textContent = 'Gain 0% — Progress approval may be blocked';
        }
    } else {
        infoBox.classList.add('d-none');
    }
});

document.getElementById('work_order_id').addEventListener('change', function () {
    const woid = this.value;
    const woBox = document.getElementById('woDetailsBox');
    const woContent = document.getElementById('woDetailsContent');
    
    if (!woid) {
        woBox.classList.add('d-none');
        return;
    }
    
    woContent.innerHTML = '<div><span class="spinner-border spinner-border-sm text-primary me-2"></span>Loading details...</div>';
    woBox.classList.remove('d-none');

    fetch(BASE_URL + '/con-ajax-wo-details?id=' + woid)
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const wo = res.wo;
                const items = res.items || [];
                
                // Get unique phase items and work types from items
                let phaseItems = [];
                let workTypes = [];
                let workTypeIdsMap = new Map();
                
                items.forEach(it => {
                    if (it.phase_item_name && !phaseItems.includes(it.phase_item_name)) {
                        phaseItems.push(it.phase_item_name);
                    }
                    if (it.work_type_id && it.work_type_name && !workTypeIdsMap.has(it.work_type_id)) {
                        workTypeIdsMap.set(it.work_type_id, it.work_type_name);
                        workTypes.push(it.work_type_name);
                    }
                });
                
                const total = parseFloat(wo.total_amount) || 0;
                const billed = parseFloat(wo.billed_amount) || 0;
                const advPct = parseFloat(wo.advance_percent) || 0;
                const advAmt = parseFloat(wo.advance_amount) || 0;
                const paidAmt = parseFloat(wo.paid_amount) || 0;
                
                let advText = `No advance specified (0%).`;
                if (advPct > 0) {
                    let statusText = `<span class="text-danger fw-bold">Not Paid</span>`;
                    if (paidAmt >= advAmt) {
                        statusText = `<span class="text-success fw-bold">Paid</span>`;
                    } else if (paidAmt > 0) {
                        statusText = `<span class="text-warning fw-bold">Partially Paid (৳${paidAmt.toFixed(2)})</span>`;
                    }
                    advText = `Advance: <strong>${advPct}%</strong> (৳${advAmt.toFixed(2)}) — Status: ${statusText}`;
                }
                
                let paymentText = paidAmt > 0 ? `<br>Total amount paid by Accounts (includes advance): <strong>৳${paidAmt.toFixed(2)}</strong>` : '';
                
                let phaseText = wo.drawing_phase_name ? `<div>Construction Phase: <strong class="text-primary">${wo.drawing_phase_name}</strong></div>` : '';
                let piText = phaseItems.length ? `<div>Phase Items: <strong>${phaseItems.join(', ')}</strong></div>` : '';
                let wtText = workTypes.length ? `<div>Work Types: <strong>${workTypes.join(', ')}</strong></div>` : '';

                woContent.innerHTML = `
                    <div>Total WO Amount: <strong>৳${total.toFixed(2)}</strong></div>
                    <div>Already Billed: <strong>৳${billed.toFixed(2)}</strong></div>
                    <div>${advText}</div>
                    ${paymentText}
                    ${phaseText}
                    ${piText}
                    ${wtText}
                `;
            } else {
                woContent.innerHTML = `<div class="text-danger">Failed to load Work Order details: ${res.message || 'Unknown error'}</div>`;
            }
        })
        .catch(err => {
            console.error('Error loading WO details:', err);
            woContent.innerHTML = '<div class="text-danger">Error loading Work Order details.</div>';
        });
});
</script>

<?php
$base_url   = $GLOBALS['base_url'];
$page_title = $page_title ?? 'New Fund Request';
?>
<div class="container-fluid">
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h4 class="page-title fw-semibold fs-18 mb-0"><?= $page_title ?></h4>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-fund-requests">Fund Requests</a></li>
                <li class="breadcrumb-item active">New Request</li>
            </ol>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-xl-8">
            <div class="card custom-card">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-cash-coin me-2 text-primary"></i> Operational Fund Request</h6>
                </div>
                <div class="card-body">
                    <form action="<?= $base_url ?>/con-fund-request-store" method="POST">

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

                            <!-- Linked Operator (optional) -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Operator <span class="text-muted small">(optional)</span>
                                </label>
                                <select name="operator_id" class="form-select">
                                    <option value="">-- No specific operator --</option>
                                    <?php foreach ($operators as $o): ?>
                                        <option value="<?= $o['id'] ?>"><?= htmlspecialchars($o['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-12 mt-3">
                                <?= renderCOADropdown($coaList, null, 'account_id', false, 'opfund') ?>
                            </div>

                            <!-- Working Plan cascade -->
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">
                                    Linked Working Plan <span class="text-muted small">(optional — auto-loads by project)</span>
                                </label>
                                <select name="work_plan_id" id="work_plan_id" class="form-select" disabled>
                                    <option value="">-- Select Project First --</option>
                                </select>
                            </div>

                            <!-- Budget Info card -->
                            <div class="col-12" id="budgetInfoWrap" style="display:none;">
                                <div class="alert alert-info py-2 mb-0">
                                    <i class="bi bi-piggy-bank me-2"></i>
                                    <strong>Available Budget:</strong>
                                    <span id="budgetInfoText" class="ms-1"></span>
                                </div>
                            </div>

                            <!-- Amount -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Amount (৳) <span class="text-danger">*</span></label>
                                <input type="number" name="amount" class="form-control"
                                       step="0.01" min="0" placeholder="0.00" required>
                            </div>

                            <!-- Fund Date -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Request Date <span class="text-danger">*</span></label>
                                <input type="date" name="fund_date" class="form-control"
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>

                            <!-- Purpose -->
                            <div class="col-12">
                                <label class="form-label fw-semibold">Purpose / Details <span class="text-danger">*</span></label>
                                <textarea name="purpose" class="form-control" rows="4" required
                                          placeholder="Describe the purpose of this fund request..."></textarea>
                            </div>
                        </div>

                        <hr class="my-4">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send me-1"></i> Submit Request
                            </button>
                            <a href="<?= $base_url ?>/con-fund-requests" class="btn btn-secondary">Cancel</a>
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
    const pid       = this.value;
    const planSel   = document.getElementById('work_plan_id');
    const budgetWrap= document.getElementById('budgetInfoWrap');
    const budgetTxt = document.getElementById('budgetInfoText');

    planSel.innerHTML = '<option value="">-- Loading... --</option>';
    planSel.disabled  = true;
    budgetWrap.style.display = 'none';
    budgetTxt.textContent    = '';

    if (!pid) {
        planSel.innerHTML = '<option value="">-- Select Project First --</option>';
        return;
    }

    fetch(BASE_URL + '/con-ajax-project-data?project_id=' + pid)
        .then(r => r.json())
        .then(data => {
            planSel.innerHTML = '<option value="">(No specific plan)</option>';
            data.work_plans.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.id;
                opt.textContent = p.label;
                opt.dataset.budget    = p.budget_label;
                opt.dataset.budgetId  = p.initial_budget_id;
                planSel.appendChild(opt);
            });
            planSel.disabled = false;

            // Show first available budget info
            if (data.budgets && data.budgets.length) {
                budgetWrap.style.display = '';
                budgetTxt.textContent    = data.budgets.map(b => b.label).join(' | ');
            }
        });
});

document.getElementById('work_plan_id').addEventListener('change', function () {
    const opt       = this.options[this.selectedIndex];
    const budgetWrap= document.getElementById('budgetInfoWrap');
    const budgetTxt = document.getElementById('budgetInfoText');

    if (opt.dataset.budget) {
        budgetWrap.style.display = '';
        budgetTxt.textContent    = opt.dataset.budget;
    }
});
</script>

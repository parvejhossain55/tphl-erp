<div class="pagetitle d-flex justify-content-between align-items-center">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-working-plans">Working Plans</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>
    <a href="<?= $base_url ?>/con-working-plans" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

<section class="section">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card__wrapper shadow-sm">
                <div class="card__header">
                    <h6 class="card__title mb-0">
                        <i class="bi bi-pencil-square me-2"></i>Edit Plan Entry
                    </h6>
                </div>
                <div class="card__body p-4">
                    <form action="<?= $base_url ?>/con-working-plan-update/<?= $plan['id'] ?>" method="POST">

                        <div class="row  mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Project <span class="text-danger">*</span></label>
                                <select name="project_id" id="projectSelect" class="form-select" required>
                                    <option value="">-- Select Project --</option>
                                    <?php foreach ($projects as $p): ?>
                                        <option value="<?= $p['id'] ?>" <?= $p['id'] == $plan['project_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($p['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Initial Budget <span class="text-danger">*</span></label>
                                <select name="initial_budget_id" id="budgetSelect" class="form-select" required>
                                    <option value="">-- Select Budget --</option>
                                    <?php foreach ($budgets as $b): ?>
                                        <option value="<?= $b['id'] ?>"
                                                data-project="<?= $b['project_id'] ?>"
                                                data-total="<?= $b['total_estimated_cost'] ?>"
                                                data-work-type-id="<?= $b['work_type_id'] ?? '' ?>"
                                                data-design-type-id="<?= $b['design_type_id'] ?? '' ?>"
                                                <?= $b['id'] == $plan['initial_budget_id'] ? 'selected' : '' ?>>
                                            #<?= $b['id'] ?> &mdash; <?= htmlspecialchars($b['project_name']) ?>
                                            <?php if (!empty($b['work_type_label'])): ?>
                                                [<?= htmlspecialchars($b['work_type_label']) ?>]
                                            <?php elseif(!empty($b['work_type_name'])): ?>
                                                [<?= htmlspecialchars($b['work_type_name']) ?>]
                                            <?php endif; ?>
                                            (৳<?= number_format($b['total_estimated_cost'], 0) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row  mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Design Type <span class="text-danger">*</span></label>
                                <select name="design_type_id" id="designTypeSelect" class="form-select" required>
                                    <option value="">-- Select --</option>
                                    <?php foreach ($designTypes as $dt): ?>
                                        <option value="<?= $dt['id'] ?>" <?= $dt['id'] == $plan['design_type_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($dt['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text text-info" id="designTypeHint"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Year <span class="text-danger">*</span></label>
                                <select name="year" class="form-select" required>
                                    <?php for ($y = date('Y') - 1; $y <= date('Y') + 3; $y++): ?>
                                        <option value="<?= $y ?>" <?= $y == $plan['year'] ? 'selected' : '' ?>><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Month <span class="text-danger">*</span></label>
                                <select name="month" class="form-select" required>
                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?= $m ?>" <?= $m == $plan['month'] ? 'selected' : '' ?>>
                                            <?= date('F', mktime(0, 0, 0, $m, 10)) ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Work Description -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                Work Description <span class="text-danger">*</span>
                            </label>
                            <textarea name="work_description" class="form-control" rows="3" required
                                      placeholder="এই মাসে কী কী কাজ করা হবে..."><?= htmlspecialchars($plan['work_description'] ?? '') ?></textarea>
                        </div>

                        <div class="row  mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Planned Quantity</label>
                                <input type="number" step="0.001" name="planned_quantity"
                                       class="form-control" value="<?= $plan['planned_quantity'] ?? 0 ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Estimated Cost (৳) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="estimated_cost"
                                       class="form-control" value="<?= $plan['estimated_cost'] ?>"
                                       min="0.01" required>
                            </div>
                        </div>

                        <div class="text-end">
                            <a href="<?= $base_url ?>/con-working-plans" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-warning px-4">
                                <i class="bi bi-save me-1"></i> Update Plan Entry
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var budgetSelect     = document.getElementById('budgetSelect');
    var designTypeSelect = document.getElementById('designTypeSelect');
    var designTypeHint   = document.getElementById('designTypeHint');

    function applyDesignTypeFromBudget(opt) {
        if (!opt || !designTypeSelect) return;
        var dtId = opt.getAttribute('data-design-type-id') || '';
        if (dtId) {
            designTypeSelect.value = dtId;
            var selOpt = designTypeSelect.options[designTypeSelect.selectedIndex];
            var dtLabel = selOpt ? selOpt.textContent.trim() : '';
            if (designTypeSelect.value == dtId && dtLabel) {
                if (designTypeHint) designTypeHint.innerHTML =
                    '<i class="bi bi-check-circle me-1"></i>Auto-selected: <strong>' + dtLabel + '</strong>';
            } else {
                if (designTypeHint) designTypeHint.textContent = '';
            }
        } else {
            if (designTypeHint) designTypeHint.textContent = '';
        }
    }

    // On budget change → auto-select design type
    if (budgetSelect) {
        budgetSelect.addEventListener('change', function () {
            var opt = this.options[this.selectedIndex];
            applyDesignTypeFromBudget(opt);
        });
    }
});
</script>

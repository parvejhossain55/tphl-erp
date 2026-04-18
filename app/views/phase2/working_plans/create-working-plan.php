<div class="pagetitle d-flex justify-content-between align-items-center">
    <div>
        <h1><?= htmlspecialchars($page_title) ?></h1>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-working-plans">Working Plans</a></li>
                <li class="breadcrumb-item active">New Entry</li>
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
                        <i class="bi bi-calendar-plus me-2"></i>Monthly Plan Entry
                    </h6>
                </div>
                <div class="card__body p-4">
                    <form action="<?= $base_url ?>/con-working-plan-store" method="POST">

                        <!-- Project — auto-filters budget -->
                        <div class="row  mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Project <span class="text-danger">*</span></label>
                                <select name="project_id" id="projectSelect" class="form-select" required>
                                    <option value="">-- Select Project --</option>
                                    <?php foreach ($projects as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    Initial Budget <span class="text-danger">*</span>
                                </label>
                                <select name="initial_budget_id" id="budgetSelect" class="form-select" required>
                                    <option value="">-- Select Budget --</option>
                                    <?php foreach ($budgets as $b): ?>
                                        <option value="<?= $b['id'] ?>"
                                                data-project="<?= $b['project_id'] ?>"
                                                data-total="<?= $b['total_estimated_cost'] ?>">
                                            #<?= $b['id'] ?> — <?= htmlspecialchars($b['project_name']) ?>
                                            (৳<?= number_format($b['total_estimated_cost'], 0) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text" id="budgetInfo"></div>
                            </div>
                        </div>

                        <!-- Design Type + Period -->
                        <div class="row  mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Design Type <span class="text-danger">*</span></label>
                                <select name="design_type_id" class="form-select" required>
                                    <option value="">-- Select --</option>
                                    <?php foreach ($designTypes as $dt): ?>
                                        <option value="<?= $dt['id'] ?>"><?= htmlspecialchars($dt['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Year <span class="text-danger">*</span></label>
                                <select name="year" class="form-select" required>
                                    <?php for ($y = date('Y') - 1; $y <= date('Y') + 3; $y++): ?>
                                        <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Month <span class="text-danger">*</span></label>
                                <select name="month" class="form-select" required>
                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?= $m ?>" <?= $m == date('n') ? 'selected' : '' ?>>
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
                                      placeholder="এই মাসে কী কী কাজ করা হবে তার বিস্তারিত লিখুন..."></textarea>
                        </div>

                        <!-- Qty + Cost -->
                        <div class="row  mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Planned Quantity (optional)</label>
                                <input type="number" step="0.001" name="planned_quantity"
                                       class="form-control" placeholder="0.000" value="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    Estimated Cost (৳) <span class="text-danger">*</span>
                                </label>
                                <input type="number" step="0.01" name="estimated_cost" id="estimatedCost"
                                       class="form-control" placeholder="0.00" min="0.01" required>
                                <div class="form-text text-danger" id="costWarning"></div>
                            </div>
                        </div>

                        <div class="text-end">
                            <a href="<?= $base_url ?>/con-working-plans" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-save me-1"></i> Save Plan Entry
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
    var projectSelect = document.getElementById('projectSelect');
    var budgetSelect  = document.getElementById('budgetSelect');
    var budgetInfo    = document.getElementById('budgetInfo');
    var allOptions    = budgetSelect.querySelectorAll('option[data-project]');

    // ─── Project change → auto-filter & auto-select budget ───────────────
    projectSelect.addEventListener('change', function () {
        var pid = this.value;

        // Reset budget
        budgetSelect.value = '';
        budgetInfo.textContent = '';

        // Show/hide budget options based on project_id
        var firstMatch = null;
        allOptions.forEach(function (opt) {
            if (opt.getAttribute('data-project') === pid) {
                opt.style.display = '';
                opt.disabled = false;
                if (!firstMatch) firstMatch = opt;
            } else {
                opt.style.display = 'none';
                opt.disabled = true;
            }
        });

        // Auto-select the first matching budget
        if (firstMatch) {
            budgetSelect.value = firstMatch.value;
            budgetInfo.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Budget Total: ৳ ' +
                parseFloat(firstMatch.getAttribute('data-total')).toLocaleString('en-BD') + '</span>';
        } else {
            budgetInfo.innerHTML = '<span class="text-warning">No budget found for this project.</span>';
        }
    });

    // ─── Budget manual change → show total ────────────────────────────────
    budgetSelect.addEventListener('change', function () {
        var opt = this.options[this.selectedIndex];
        if (opt && opt.getAttribute('data-total')) {
            budgetInfo.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Budget Total: ৳ ' +
                parseFloat(opt.getAttribute('data-total')).toLocaleString('en-BD') + '</span>';
        } else {
            budgetInfo.textContent = '';
        }
    });
});
</script>

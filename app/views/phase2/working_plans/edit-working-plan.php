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
                                                data-start="<?= $b['start_date'] ?? '' ?>"
                                                data-end="<?= $b['end_date'] ?? '' ?>"
                                                data-work-type-id="<?= $b['work_type_id'] ?? '' ?>"
                                                data-design-type-id="<?= $b['design_type_id'] ?? '' ?>"
                                                data-weight="<?= $b['weight_percentage'] ?? 0 ?>"
                                                data-allocated="<?= $b['allocated_weight'] ?? 0 ?>"
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
                                <label class="form-label fw-bold">Work Type <span class="text-danger">*</span></label>
                                <select name="work_type_id" id="workTypeSelect" class="form-select" required>
                                    <option value="">-- Loading... --</option>
                                </select>
                                <div class="form-text text-info" id="workTypeHint"></div>
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
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Planned Quantity</label>
                                <input type="number" step="0.001" name="planned_quantity"
                                       class="form-control" value="<?= $plan['planned_quantity'] ?? 0 ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Estimated Cost (৳) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="estimated_cost"
                                       class="form-control" value="<?= $plan['estimated_cost'] ?>"
                                       min="0.01" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Project Weight (%) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="weight_percentage" id="weightPercentageInput"
                                       class="form-control" value="<?= $plan['weight_percentage'] ?? '0.00' ?>"
                                       min="0.00" max="100.00" required>
                                <div class="form-text text-muted" id="weightHint">এই মাসটি পুরো প্রজেক্টের কত %</div>
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
    var workTypeSelect   = document.getElementById('workTypeSelect');
    var workTypeHint     = document.getElementById('workTypeHint');
    var projectSelect    = document.getElementById('projectSelect');
    
    var currentWorkTypeId = <?= (int)$plan['work_type_id'] ?>;

    var currentPlanWeight = <?= (float)($plan['weight_percentage'] ?? 0) ?>;

    function applyWorkTypeFromBudget(opt) {
        if (!opt) return;
        
        var weightHint = document.getElementById('weightHint');
        var weightInput = document.getElementById('weightPercentageInput');
        if (weightHint && opt) {
            var totalWeight = parseFloat(opt.getAttribute('data-weight')) || 0;
            var allocatedWeight = parseFloat(opt.getAttribute('data-allocated')) || 0;
            var budgetId = opt.value;
            var initialBudgetId = <?= (int)$plan['initial_budget_id'] ?>;
            
            // For editing, current plan's weight is already part of allocated, so we subtract it from allocated
            // to find the TRUE remaining excluding this plan itself (ONLY if the selected budget is the same as the original)
            if (budgetId == initialBudgetId) {
                allocatedWeight = allocatedWeight - currentPlanWeight;
            }
            
            var remainingWeight = Math.max(0, totalWeight - allocatedWeight);
            
            var startDate = opt.getAttribute('data-start') || '';
            var endDate = opt.getAttribute('data-end') || '';
            var hasValidDates = false;
            var sYear = startDate ? parseInt(startDate.substring(0, 4)) : 0;
            var eYear = endDate ? parseInt(endDate.substring(0, 4)) : 0;
            if (sYear > 2000 && eYear > 2000) hasValidDates = true;

            var autoSuggestedWeight = 0;
            var totalMonths = 1;
            if (hasValidDates) {
                var sDate = new Date(startDate);
                var eDate = new Date(endDate);
                var months = (eDate.getFullYear() - sDate.getFullYear()) * 12;
                months -= sDate.getMonth();
                months += eDate.getMonth();
                totalMonths = months <= 0 ? 1 : months;
            }
            autoSuggestedWeight = totalWeight > 0 ? (totalWeight / totalMonths) : 0;
            if (autoSuggestedWeight > remainingWeight) autoSuggestedWeight = remainingWeight;
            
            if (totalWeight > 0) {
                weightHint.innerHTML = '<span class="text-success fw-bold">Remaining: ' + remainingWeight.toFixed(2) + '%</span> (Total: ' + totalWeight.toFixed(2) + '%)';
                weightInput.max = remainingWeight.toFixed(2);
                
                // Only overwrite input if user changed the budget, OR if the original plan weight is 0
                if (budgetId != initialBudgetId || currentPlanWeight === 0) {
                    weightInput.value = autoSuggestedWeight.toFixed(2);
                }
            } else {
                weightHint.innerHTML = 'এই মাসটি পুরো প্রজেক্টের কত %';
            }
        }
        
        if (!workTypeSelect) return;
        var wtId = opt.getAttribute('data-work-type-id') || '';
        if (wtId) {
            workTypeSelect.value = wtId;
            var selOpt = workTypeSelect.options[workTypeSelect.selectedIndex];
            var wtLabel = selOpt ? selOpt.textContent.trim() : '';
            if (workTypeSelect.value == wtId && wtLabel) {
                if (workTypeHint) workTypeHint.innerHTML =
                    '<i class="bi bi-check-circle me-1"></i>Auto-selected: <strong>' + wtLabel + '</strong>';
            } else {
                if (workTypeHint) workTypeHint.textContent = '';
            }
        } else {
            if (workTypeHint) workTypeHint.textContent = '';
        }
    }

    function loadWorkTypes(projectId, callback) {
        workTypeSelect.innerHTML = '<option value="">Loading...</option>';
        fetch('<?= $base_url ?>/con-ajax-project-data?project_id=' + projectId)
            .then(r => r.json())
            .then(data => {
                workTypeSelect.innerHTML = '<option value="">-- Select Work Type --</option>';
                if (data.work_types && data.work_types.length > 0) {
                    data.work_types.forEach(wt => {
                        var opt = document.createElement('option');
                        opt.value = wt.id;
                        opt.textContent = wt.label;
                        workTypeSelect.appendChild(opt);
                    });
                    if (callback) callback();
                } else {
                    workTypeSelect.innerHTML = '<option value="">No Work Types Found</option>';
                }
            })
            .catch(() => {
                workTypeSelect.innerHTML = '<option value="">Error loading</option>';
            });
    }

    // On budget change → auto-select work type
    if (budgetSelect) {
        budgetSelect.addEventListener('change', function () {
            var opt = this.options[this.selectedIndex];
            applyWorkTypeFromBudget(opt);
        });
    }
    
    // Initial load
    if (projectSelect && projectSelect.value) {
        loadWorkTypes(projectSelect.value, function() {
            workTypeSelect.value = currentWorkTypeId;
        });
    }
    
    // Apply budget weight on load
    if (budgetSelect && budgetSelect.value) {
        applyWorkTypeFromBudget(budgetSelect.options[budgetSelect.selectedIndex]);
    }

    // On project change -> reload work types
    if (projectSelect) {
        projectSelect.addEventListener('change', function() {
            var pid = this.value;
            if (pid) {
                loadWorkTypes(pid);
            } else {
                workTypeSelect.innerHTML = '<option value="">-- Select Project First --</option>';
            }
        });
    }
});
</script>

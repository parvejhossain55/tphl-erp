<div class="pagetitle d-flex justify-content-between align-items-center">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
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
                                                data-total="<?= $b['total_estimated_cost'] ?>"
                                                data-start="<?= $b['start_date'] ?? '' ?>"
                                                data-end="<?= $b['end_date'] ?? '' ?>"
                                                data-work-type-id="<?= $b['work_type_id'] ?? '' ?>"
                                                data-design-type-id="<?= $b['design_type_id'] ?? '' ?>"
                                                data-budget-link="<?= htmlspecialchars($b['budget_link'] ?? '') ?>"
                                                data-work-details="<?= htmlspecialchars($b['work_details'] ?? '') ?>"
                                                data-weight="<?= $b['weight_percentage'] ?? 0 ?>"
                                                data-allocated="<?= $b['allocated_weight'] ?? 0 ?>">
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
                                <div class="form-text" id="budgetInfo"></div>
                            </div>
                        </div>

                        <!-- Work Type + Period -->
                        <div class="row  mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Work Type <span class="text-danger">*</span></label>
                                <select name="work_type_id" id="workTypeSelect" class="form-select" required>
                                    <option value="">-- Select Project First --</option>
                                </select>
                                <div class="form-text text-info" id="workTypeHint"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Year <span class="text-danger">*</span></label>
                                <select name="year" id="yearSelect" class="form-select" required>
                                    <option value="">-- Select Project First --</option>
                                </select>
                                <div class="form-text text-muted" id="yearHint"></div>
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

                        <!-- Qty, Cost & Weight -->
                        <div class="row  mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Planned Quantity</label>
                                <input type="number" step="0.001" name="planned_quantity"
                                       class="form-control" placeholder="0.000" value="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">
                                    Estimated Cost (৳) <span class="text-danger">*</span>
                                </label>
                                <input type="number" step="0.01" name="estimated_cost" id="estimatedCost"
                                       class="form-control" placeholder="0.00" min="0.01" required>
                                <div class="form-text text-danger" id="costWarning"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">
                                    Project Weight (%) <span class="text-danger">*</span>
                                </label>
                                <input type="number" step="0.01" name="weight_percentage" id="weightPercentageInput"
                                       class="form-control" placeholder="0.00" value="0.00" min="0.00" max="100.00" required>
                                <div class="form-text text-muted" id="weightHint">এই মাসটি পুরো প্রজেক্টের কত %</div>
                            </div>
                        </div>

                        <!-- Range warning / exhausted notice -->
                        <div id="rangeWarning"></div>

                        <div class="text-end mt-3">
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
    var projectSelect    = document.getElementById('projectSelect');
    var budgetSelect     = document.getElementById('budgetSelect');
    var yearSelect       = document.getElementById('yearSelect');
    var monthSelect      = document.querySelector('select[name="month"]');
    var budgetInfo       = document.getElementById('budgetInfo');
    var workTypeSelect   = document.getElementById('workTypeSelect');
    var workTypeHint     = document.getElementById('workTypeHint');
    var weightPercentageInput = document.getElementById('weightPercentageInput');
    var weightHint       = document.getElementById('weightHint');
    var allBudgetOpts    = budgetSelect.querySelectorAll('option[data-project]');
    var currentYear      = <?= date('Y') ?>;
    var submitBtn        = document.querySelector('button[type="submit"]');
    var rangeWarning     = document.getElementById('rangeWarning');

    // Pre-loaded data maps from PHP
    var workTypeMap      = <?= json_encode($workTypesByProject,  JSON_UNESCAPED_UNICODE) ?>;
    // { budget_id: [{year,month}, ...] } — months already planned
    var plannedMap       = <?= json_encode($plannedMonthsMap ?? [], JSON_UNESCAPED_UNICODE) ?>;

    var monthNames = ['','January','February','March','April','May','June',
                      'July','August','September','October','November','December'];

    // ── Rebuild Month dropdown within allowed date range ──────────────
    function rebuildMonthDropdown(startDate, endDate, plannedPairs, suggestYear, suggestMonth) {
        monthSelect.innerHTML = '';
        monthSelect.disabled  = false;

        var sY = startDate ? parseInt(startDate.substring(0,4)) : 0;
        var sM = startDate ? parseInt(startDate.substring(5,7)) : 1;
        var eY = endDate   ? parseInt(endDate.substring(0,4))   : 0;
        var eM = endDate   ? parseInt(endDate.substring(5,7))   : 12;

        var hasRange = sY > 2000 && eY > 2000;
        var selectedY = parseInt(yearSelect.value) || currentYear;

        // Determine which months are valid for the selected year
        var minM = 1, maxM = 12;
        if (hasRange) {
            if (selectedY === sY) minM = sM;
            if (selectedY === eY) maxM = eM;
            if (selectedY < sY || selectedY > eY) { minM = 99; } // none valid
        }

        // Build set of planned year+month keys
        var plannedSet = {};
        (plannedPairs || []).forEach(function(p) {
            plannedSet[p.year + '-' + p.month] = true;
        });

        var addedCount = 0;
        for (var m = minM; m <= maxM; m++) {
            var key = selectedY + '-' + m;
            var alreadyPlanned = !!plannedSet[key];
            if (alreadyPlanned) continue; // skip already-planned months
            var opt = document.createElement('option');
            opt.value = m;
            opt.textContent = monthNames[m];
            if (m === suggestMonth && selectedY === suggestYear) opt.selected = true;
            monthSelect.appendChild(opt);
            addedCount++;
        }

        // No months left for this year
        if (addedCount === 0) {
            var opt = document.createElement('option');
            opt.value = '';
            opt.textContent = '-- No available months --';
            monthSelect.appendChild(opt);
        }
    }

    // ── Check if all months in range are planned → block form ────────
    function checkRangeExhausted(startDate, endDate, plannedPairs, budgetId) {
        if (!rangeWarning) return;
        rangeWarning.innerHTML = '';
        if (submitBtn) submitBtn.disabled = false;

        if (!startDate || !endDate) return;

        var sY = parseInt(startDate.substring(0,4));
        var sM = parseInt(startDate.substring(5,7));
        var eY = parseInt(endDate.substring(0,4));
        var eM = parseInt(endDate.substring(5,7));

        if (sY < 2001 || eY < 2001) return;

        // Build all months in range
        var allMonths = [];
        var y = sY, m = sM;
        while (y < eY || (y === eY && m <= eM)) {
            allMonths.push(y + '-' + m);
            m++;
            if (m > 12) { m = 1; y++; }
        }

        // Build planned set
        var plannedSet = {};
        (plannedPairs || []).forEach(function(p) {
            plannedSet[p.year + '-' + p.month] = true;
        });

        var remaining = allMonths.filter(function(k) { return !plannedSet[k]; });

        if (remaining.length === 0 && allMonths.length > 0) {
            rangeWarning.innerHTML =
                '<div class="alert alert-warning mt-3 mb-0">' +
                '<i class="bi bi-exclamation-triangle me-2"></i>' +
                '<strong>সকল মাসের Working Plan সম্পন্ন!</strong> ' +
                'এই Budget এর ' + startDate + ' থেকে ' + endDate + ' পর্যন্ত সব মাসের plan করা হয়ে গেছে।' +
                '</div>';
            if (submitBtn) submitBtn.disabled = true;
        } else if (remaining.length > 0) {
            rangeWarning.innerHTML =
                '<div class="alert alert-info mt-2 mb-0 py-2" style="font-size:12px;">' +
                '<i class="bi bi-calendar-check me-1"></i>' +
                '<strong>' + remaining.length + ' মাস</strong> এখনো plan করা বাকি আছে।' +
                '</div>';
        }
    }

    // ── Compute the next month to suggest after last planned month ────
    function getNextSuggestedMonth(plannedPairs, startDate, endDate) {
        if (!plannedPairs || plannedPairs.length === 0) {
            // No plans yet → suggest first month of budget range
            if (startDate && parseInt(startDate.substring(0,4)) > 2000) {
                return {
                    year:  parseInt(startDate.substring(0,4)),
                    month: parseInt(startDate.substring(5,7))
                };
            }
            return { year: currentYear, month: new Date().getMonth() + 1 };
        }

        // Find the last planned month
        var last = plannedPairs[plannedPairs.length - 1];
        var nextY = last.year;
        var nextM = last.month + 1;
        if (nextM > 12) { nextM = 1; nextY++; }

        // Clamp to end date
        if (endDate && parseInt(endDate.substring(0,4)) > 2000) {
            var eY = parseInt(endDate.substring(0,4));
            var eM = parseInt(endDate.substring(5,7));
            if (nextY > eY || (nextY === eY && nextM > eM)) {
                return { year: eY, month: eM }; // return end, rangeExhausted will disable
            }
        }
        return { year: nextY, month: nextM };
    }

    // ── Master rebuild triggered on budget change ─────────────────────
    function onBudgetSelected(opt) {
        if (!opt || !opt.getAttribute('data-total')) {
            yearSelect.innerHTML = '<option value="">-- Select Budget First --</option>';
            monthSelect.innerHTML = '<option value="">-- Select Budget First --</option>';
            if (rangeWarning) rangeWarning.innerHTML = '';
            return;
        }

        var budgetId  = parseInt(opt.value);
        var startDate = opt.getAttribute('data-start') || '';
        var endDate   = opt.getAttribute('data-end')   || '';

        var sY = startDate ? parseInt(startDate.substring(0,4)) : 0;
        var eY = endDate   ? parseInt(endDate.substring(0,4))   : 0;
        var hasRange = sY > 2000 && eY > 2000;

        var plannedPairs = plannedMap[budgetId] || [];
        var suggested = getNextSuggestedMonth(plannedPairs, startDate, endDate);

        // ── Rebuild Year dropdown ──
        yearSelect.innerHTML = '';
        var startYear = hasRange ? sY : currentYear - 1;
        var endYear   = hasRange ? eY : currentYear + 4;

        for (var y = startYear; y <= endYear; y++) {
            var o = document.createElement('option');
            o.value = y;
            o.textContent = y;
            if (y === suggested.year) o.selected = true;
            yearSelect.appendChild(o);
        }

        var hint = document.getElementById('yearHint');
        if (hint) {
            hint.innerHTML = hasRange
                ? '<i class="bi bi-calendar-range me-1"></i>Budget period: <strong>' + startDate + '</strong> &rarr; <strong>' + endDate + '</strong>'
                : '<i class="bi bi-info-circle me-1 text-muted"></i><span class="text-muted">No date range set</span>';
        }

        // ── Rebuild Month dropdown ──
        rebuildMonthDropdown(startDate, endDate, plannedPairs, suggested.year, suggested.month);

        // ── Check if exhausted ──
        checkRangeExhausted(startDate, endDate, plannedPairs, budgetId);

        // ── Budget info banner ──
        var bLink = opt.getAttribute('data-budget-link');
        var wDet  = opt.getAttribute('data-work-details');
        var extHtml = '';
        if (bLink) extHtml += ' | <a href="'+bLink+'" target="_blank" style="text-decoration:underline">Budget Link <i class="bi bi-box-arrow-up-right ms-1"></i></a>';
        if (wDet)  extHtml += '<br><small class="text-muted"><i class="bi bi-info-circle me-1"></i>' + wDet + '</small>';
        budgetInfo.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Budget Total: ৳ ' +
            parseFloat(opt.getAttribute('data-total')).toLocaleString('en-BD') + '</span>' + extHtml;

        // ── Weight calculation ──
        var totalWeight     = parseFloat(opt.getAttribute('data-weight'))    || 0;
        var allocatedWeight = parseFloat(opt.getAttribute('data-allocated'))  || 0;
        var remainingWeight = Math.max(0, totalWeight - allocatedWeight);
        var totalMonths = 1;
        if (hasRange) {
            var sDate = new Date(startDate);
            var eDateD = new Date(endDate);
            var months = (eDateD.getFullYear() - sDate.getFullYear()) * 12
                       - sDate.getMonth() + eDateD.getMonth();
            totalMonths = months <= 0 ? 1 : months;
        }
        var autoWeight = totalWeight > 0 ? Math.min(totalWeight / totalMonths, remainingWeight) : 0;
        if (weightHint && totalWeight > 0) {
            weightHint.innerHTML = '<span class="text-success fw-bold">Remaining: ' + remainingWeight.toFixed(2) + '%</span> (Total: ' + totalWeight.toFixed(2) + '%)';
            weightPercentageInput.value = autoWeight.toFixed(2);
            weightPercentageInput.max   = remainingWeight.toFixed(2);
        }
    }

    // ── Year change → re-render month dropdown ────────────────────────
    yearSelect.addEventListener('change', function() {
        var opt = budgetSelect.options[budgetSelect.selectedIndex];
        if (!opt || !opt.getAttribute('data-total')) return;
        var budgetId  = parseInt(opt.value);
        var startDate = opt.getAttribute('data-start') || '';
        var endDate   = opt.getAttribute('data-end')   || '';
        var planned   = plannedMap[budgetId] || [];
        rebuildMonthDropdown(startDate, endDate, planned, parseInt(yearSelect.value), 0);
    });

    // ── Project change → filter budgets ──────────────────────────────
    projectSelect.addEventListener('change', function () {
        var pid = this.value;
        budgetSelect.value = '';
        budgetInfo.textContent = '';
        yearSelect.innerHTML = '<option value="">-- Select Budget First --</option>';
        monthSelect.innerHTML = '<option value="">-- Select Budget First --</option>';
        if (rangeWarning) rangeWarning.innerHTML = '';
        if (workTypeHint) workTypeHint.textContent = '';

        var firstMatch = null;
        allBudgetOpts.forEach(function (opt) {
            var match = opt.getAttribute('data-project') === String(pid);
            opt.style.display = match ? '' : 'none';
            opt.disabled      = !match;
            if (match && !firstMatch) firstMatch = opt;
        });

        if (firstMatch) {
            budgetSelect.value = firstMatch.value;
            onBudgetSelected(firstMatch);
        } else {
            budgetInfo.innerHTML = '<span class="text-warning">No budget found for this project.</span>';
        }

        // Fill Work Types
        workTypeSelect.innerHTML = '<option value="">-- Select Work Type --</option>';
        var types = workTypeMap[pid] || [];
        types.forEach(function(wt) {
            var o = document.createElement('option');
            o.value = wt.id;
            o.textContent = wt.label;
            workTypeSelect.appendChild(o);
        });
        if (workTypeHint) {
            workTypeHint.innerHTML = types.length > 0
                ? '<i class="bi bi-info-circle me-1"></i>' + types.length + ' Work Type(s) available'
                : '';
        }
    });

    // ── Budget manual change ──────────────────────────────────────────
    budgetSelect.addEventListener('change', function () {
        var opt = this.options[this.selectedIndex];
        onBudgetSelected(opt);
        // Auto-select work type from budget
        if (opt && opt.getAttribute('data-work-type-id')) {
            var wtId = opt.getAttribute('data-work-type-id');
            workTypeSelect.value = wtId;
        }
    });
});
</script>



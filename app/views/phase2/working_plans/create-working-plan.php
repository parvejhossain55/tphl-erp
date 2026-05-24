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
        <div class="col-lg-12">
            <div class="card__wrapper shadow-sm">
                <div class="card__header bg-primary-transparent">
                    <h6 class="card__title mb-0 fw-bold">
                        <i class="bi bi-calendar-plus me-2 text-primary"></i>Monthly Plan Entry (প্রজেক্ট ওয়ার্ক প্ল্যান এন্ট্রি)
                    </h6>
                </div>
                <div class="card__body p-4">
                    <form action="<?= $base_url ?>/con-working-plan-store" method="POST" id="workingPlanForm">

                        <!-- Shared Project & Timeframe Row -->
                        <div class="row mb-4 p-3  rounded border align-items-center">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Project <span class="text-danger">*</span></label>
                                <select name="project_id" id="projectSelect" class="form-select" required>
                                    <option value="">-- Select Project --</option>
                                    <?php foreach ($projects as $p): ?>
                                        <option value="<?= $p['id'] ?>" data-building-type="<?= htmlspecialchars($p['building_type'] ?? '') ?>"><?= htmlspecialchars($p['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
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
                                <select name="month" id="monthSelect" class="form-select" required>
                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?= $m ?>" <?= $m == date('n') ? 'selected' : '' ?>>
                                            <?= date('F', mktime(0, 0, 0, $m, 10)) ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Range warning / exhausted notice -->
                        <div id="rangeWarning" class="mb-3"></div>

                        <!-- Repeatable Work Type Sections Container -->
                        <div id="sectionsContainer">
                            <!-- JS will dynamically populate sections here -->
                        </div>

                        <!-- Add Section Button -->
                        <div class="mb-4 text-center">
                            <button type="button" class="btn btn-outline-primary px-4 py-2 fw-semibold" id="addSectionBtn">
                                <i class="bi bi-plus-circle-dotted me-2"></i> Add Another Work Type (নতুন কাজের ধরন যোগ করুন)
                            </button>
                        </div>

                        <div class="text-end mt-4">
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

<!-- Section Template (Hidden) -->
<div id="sectionTemplate" style="display:none;">
    <div class="card custom-card mb-4 border shadow-none work-section" data-section-index="__INDEX__">
        <div class="card-header bg-primary-transparent d-flex justify-content-between align-items-center py-2" style="background: rgba(13, 110, 253, 0.05);">
            <h6 class="card-title mb-0 fw-bold text-primary text-uppercase" style="font-size:0.85rem;">
                <i class="bi bi-gear-wide-connected me-2"></i>Work Section #<span class="section-number">__NUMBER__</span> (কাজের বিবরণ ও বাজেট)
            </h6>
            <button type="button" class="btn btn-sm btn-danger remove-section-btn" style="display:none;">
                <i class="bi bi-trash me-1"></i> Remove Section
            </button>
        </div>
        <div class="card-body p-4">
            <!-- Budget & Work Type selectors -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Initial Budget <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <select name="sections[__INDEX__][initial_budget_id]" class="form-select budget-select" required>
                            <option value="">-- Select Project First --</option>
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
                        <button type="button" class="btn btn-outline-info text-nowrap view-estimate-btn" style="display:none;">
                            <i class="bi bi-eye"></i> View Estimate
                        </button>
                    </div>
                    <div class="form-text budget-info"></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Work Type <span class="text-danger">*</span></label>
                    <select class="form-select work-type-select" disabled>
                        <option value="">-- Select Project First --</option>
                    </select>
                    <input type="hidden" name="sections[__INDEX__][work_type_id]" class="work-type-hidden">
                    <div class="form-text text-info work-type-hint"></div>
                </div>
            </div>

            <!-- Cost Info Card -->
            <div class="alert alert-info d-flex align-items-center gap-3 mb-4 prev-cost-card" style="display:none !important;">
                <i class="bi bi-cash-stack fs-4 text-primary"></i>
                <div>
                    <span class="text-muted d-block small">পূর্ববর্তী মাসসমূহের মোট খরচ (Cumulative Approved Cost for this Design Type):</span>
                    <strong class="fs-6 text-primary prev-cost-amount">৳ ০.০০</strong>
                </div>
            </div>

            <!-- Activities Table -->
            <div class="card mb-4 border shadow-none">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                    <h6 class="card-title mb-0 fw-bold text-dark text-uppercase" style="font-size:0.8rem;">
                        <i class="bi bi-list-task me-2 text-primary"></i>কাজ ও অ্যাক্টিভিটি শিডিউল (Schedule Activities/Phases)
                    </h6>
                    <button type="button" class="btn btn-sm btn-success add-row-btn">
                        <i class="bi bi-plus-circle me-1"></i> Add Activity
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0 activities-table" style="font-size: 0.86rem;">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th class="text-center" style="width: 4%;">নং</th>
                                    <th style="width: 32%;">কাজের বিবরণ ও অবস্থান (Activity & Location) <span class="text-danger">*</span></th>
                                    <th style="width: 14%;">ফ্লোর (Floor)</th>
                                    <th style="width: 10%;">পরিমাণ (Quantity)</th>
                                    <th style="width: 11%;">শুরু (Start Date) <span class="text-danger">*</span></th>
                                    <th style="width: 7%;">দিন (Days) <span class="text-danger">*</span></th>
                                    <th style="width: 11%;">শেষ (Completion Date)</th>
                                    <th style="width: 15%;">মন্তব্য (Remarks)</th>
                                    <th class="text-center" style="width: 5%;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Rows will be added dynamically by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Cost & Weight Details -->
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Estimated Cost (৳) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="sections[__INDEX__][estimated_cost]" class="form-control estimated-cost-input" placeholder="0.00" min="0.01" required>
                    <div class="form-text text-danger cost-warning"></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Project Weight (%) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="sections[__INDEX__][weight_percentage]" class="form-control weight-pct-input" placeholder="0.00" value="0.00" min="0.00" max="100.00" required>
                    <div class="form-text text-muted weight-hint">এই মাসটি পুরো প্রজেক্টের কত %</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Estimate View Modal -->
<div class="modal fade" id="estimateModal" tabindex="-1" aria-labelledby="estimateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="estimateModalLabel">
                    <i class="bi bi-calculator me-2"></i>বাজেট এস্টিমেট বিবরণ (Detailed Budget Estimation)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="estimateModalContent">
                <div class="text-center py-5" id="estimateLoading">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 text-muted">এস্টিমেট বিবরণ লোড হচ্ছে, অনুগ্রহ করে অপেক্ষা করুন...</div>
                </div>
                
                <div id="estimateDataArea" style="display:none;">
                    <!-- Metadata Info Banner -->
                    <div class="row g-3 mb-4 p-3 bg-light rounded border">
                        <div class="col-md-3">
                            <span class="text-muted small">বাজেট আইডি:</span>
                            <div class="fw-bold fs-6 text-dark" id="modalBudgetId">#0</div>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted small">মোট বাজেট প্রাক্কলন:</span>
                            <div class="fw-bold fs-5 text-primary" id="modalBudgetTotal">৳ ০.০০</div>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted small">প্রজেক্টে বরাদ্দকৃত ওজন:</span>
                            <div class="fw-bold fs-6 text-success" id="modalBudgetWeight">০.০০%</div>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted small">প্রজেক্টের সময়সীমা:</span>
                            <div class="fw-semibold text-dark" id="modalBudgetDates">―</div>
                        </div>
                    </div>

                    <!-- Tabs/Section: Materials -->
                    <div class="card mb-4 shadow-none border">
                        <div class="card-header bg-success-light text-success-emphasis fw-bold py-2" style="background: rgba(25, 135, 84, 0.08);">
                            <i class="bi bi-box-seam me-2"></i>Part-01: মালামাল প্রাক্কলন (Materials Estimation)
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0 align-middle" style="font-size:0.84rem;">
                                    <thead class="table-light text-muted">
                                        <tr>
                                            <th class="text-center" style="width: 5%;">নং</th>
                                            <th>মালামালের নাম (Material Name)</th>
                                            <th style="width: 15%;">একক (Unit)</th>
                                            <th class="text-end" style="width: 15%;">পরিমাণ (Estimated Qty)</th>
                                            <th class="text-end" style="width: 15%;">একক হার (Unit Rate)</th>
                                            <th class="text-end" style="width: 20%;">মোট মূল্য (Amount)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="modalMaterialBody">
                                        <!-- Dynamic rows -->
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-light fw-bold text-success text-end">
                                            <td colspan="5">মালামাল বাবদ মোট খরচ:</td>
                                            <td id="modalMaterialTotal">৳ ০.০০</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs/Section: Labor -->
                    <div class="card mb-0 shadow-none border">
                        <div class="card-header bg-info-light text-info-emphasis fw-bold py-2" style="background: rgba(13, 202, 240, 0.08);">
                            <i class="bi bi-people me-2"></i>Part-02: সাব-কন্ট্রাক্টর বিল পূর্বাভাস (Sub-contractor Bill Forecast)
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0 align-middle" style="font-size:0.84rem;">
                                    <thead class="table-light text-muted">
                                        <tr>
                                            <th class="text-center" style="width: 5%;">নং</th>
                                            <th>কাজের ধরণ (Labor / Work Type)</th>
                                            <th class="text-end" style="width: 15%;">হার / বর্গফুট (Rate / Sqft)</th>
                                            <th class="text-end" style="width: 15%;">ক্ষেত্রফল (Area Sqft)</th>
                                            <th>কাজের বর্ণনা (Description)</th>
                                            <th class="text-end" style="width: 20%;">মোট বিল (Amount)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="modalLaborBody">
                                        <!-- Dynamic rows -->
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-light fw-bold text-info text-end">
                                            <td colspan="5">লেবার বাবদ মোট খরচ:</td>
                                            <td id="modalLaborTotal">৳ ০.০০</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var projectSelect        = document.getElementById('projectSelect');
    var yearSelect           = document.getElementById('yearSelect');
    var monthSelect          = document.getElementById('monthSelect');
    var rangeWarning         = document.getElementById('rangeWarning');
    var sectionsContainer    = document.getElementById('sectionsContainer');
    var addSectionBtn        = document.getElementById('addSectionBtn');
    var submitBtn            = document.querySelector('button[type="submit"]');

    var currentYear          = <?= date('Y') ?>;
    var workTypeMap          = <?= json_encode($workTypesByProject,  JSON_UNESCAPED_UNICODE) ?>;
    var plannedMap           = <?= json_encode($plannedMonthsMap ?? [], JSON_UNESCAPED_UNICODE) ?>;

    var monthNames = ['','January','February','March','April','May','June',
                      'July','August','September','October','November','December'];

    // Modal References
    var estimateModal        = new bootstrap.Modal(document.getElementById('estimateModal'));
    var estimateLoading      = document.getElementById('estimateLoading');
    var estimateDataArea     = document.getElementById('estimateDataArea');

    var sectionIndex = 0;
    var floors = [];

    function getFloorsFromBuildingType(buildingType) {
        if (!buildingType) {
            return [
                'Foundation', 'Basement', 'G.floor', 
                '1st floor', '2nd floor', '3rd floor', '4th floor', '5th floor', 
                '6th floor', '7th floor', '8th floor', '9th floor', '10th floor', 
                '11th floor', '12th floor', 'Roof'
            ];
        }
        var str = buildingType.toUpperCase().replace(/\s+/g, '');
        var parts = str.split('+');
        var basements = [];
        var hasGround = false;
        var upperFloorsCount = 0;
        parts.forEach(function(part) {
            if (part.startsWith('B')) {
                var numStr = part.substring(1);
                if (numStr === '') {
                    basements.push('Basement');
                } else {
                    var num = parseInt(numStr);
                    if (!isNaN(num)) {
                        basements.push(part);
                    }
                }
            } else if (part === 'G' || part === 'GF' || part.indexOf('GROUND') !== -1) {
                hasGround = true;
            } else {
                var num = parseInt(part);
                if (!isNaN(num)) {
                    upperFloorsCount = num;
                }
            }
        });
        basements.sort(function(a, b) {
            var numA = parseInt(a.replace(/\D/g, '')) || 1;
            var numB = parseInt(b.replace(/\D/g, '')) || 1;
            return numB - numA;
        });
        var floorList = ['Foundation'];
        basements.forEach(function(b) {
            floorList.push(b);
        });
        if (hasGround) {
            floorList.push('G.floor');
        }
        for (var i = 1; i <= upperFloorsCount; i++) {
            var suffix = 'th';
            if (i === 1) suffix = 'st';
            else if (i === 2) suffix = 'nd';
            else if (i === 3) suffix = 'rd';
            floorList.push(i + suffix + ' floor');
        }
        floorList.push('Roof');
        return floorList;
    }

    // ── Rebuild Shared Year & Month dropdowns based on all project budgets ──
    function updateYearMonthForProject(pid) {
        if (!pid) {
            yearSelect.innerHTML = '<option value="">-- Select Project First --</option>';
            monthSelect.innerHTML = '<option value="">-- Select Project First --</option>';
            return;
        }

        // Find all options in the template select that match this project
        var templateNode = document.getElementById('sectionTemplate');
        var opts = Array.from(templateNode.querySelectorAll('.budget-select option[data-project="' + pid + '"]'));
        
        var minDate = null;
        var maxDate = null;

        opts.forEach(function(opt) {
            var start = opt.getAttribute('data-start');
            var end = opt.getAttribute('data-end');
            if (start) {
                var d = new Date(start);
                if (!minDate || d < minDate) minDate = d;
            }
            if (end) {
                var d = new Date(end);
                if (!maxDate || d > maxDate) maxDate = d;
            }
        });

        var sY = minDate ? minDate.getFullYear() : currentYear - 1;
        var eY = maxDate ? maxDate.getFullYear() : currentYear + 4;

        yearSelect.innerHTML = '';
        for (var y = sY; y <= eY; y++) {
            var o = document.createElement('option');
            o.value = y;
            o.textContent = y;
            if (y === currentYear) o.selected = true;
            yearSelect.appendChild(o);
        }

        var hint = document.getElementById('yearHint');
        if (hint && minDate && maxDate) {
            var sStr = minDate.toISOString().split('T')[0];
            var eStr = maxDate.toISOString().split('T')[0];
            hint.innerHTML = '<i class="bi bi-calendar-range me-1"></i>Project Budgets span: <strong>' + sStr + '</strong> &rarr; <strong>' + eStr + '</strong>';
        } else if (hint) {
            hint.textContent = '';
        }

        // Build all 12 months (standard monthly plan)
        monthSelect.innerHTML = '';
        var currentMonth = new Date().getMonth() + 1;
        for (var m = 1; m <= 12; m++) {
            var opt = document.createElement('option');
            opt.value = m;
            opt.textContent = monthNames[m];
            if (m === currentMonth) opt.selected = true;
            monthSelect.appendChild(opt);
        }
    }

    // ── Clone and add new section ──
    var sectionTemplateHtml = document.getElementById('sectionTemplate').innerHTML;

    function addWorkSection() {
        var tempDiv = document.createElement('div');
        // Replace templates placeholders
        var count = sectionsContainer.querySelectorAll('.work-section').length + 1;
        var html = sectionTemplateHtml
            .replace(/__INDEX__/g, sectionIndex)
            .replace(/__NUMBER__/g, count);

        tempDiv.innerHTML = html;
        var sectionNode = tempDiv.firstElementChild;
        sectionsContainer.appendChild(sectionNode);

        // Bind events for selectors inside this section
        bindSectionEvents(sectionNode, sectionIndex);

        // Filter budgets & work types immediately if project is selected
        var pid = projectSelect.value;
        if (pid) {
            filterBudgetAndWorkTypesForSection(sectionNode, pid);
        }

        // Add first activity row
        addActivityRow(sectionNode, sectionIndex);

        sectionIndex++;
        updateSectionNumbers();
        updateRemoveButtons();
    }

    function updateSectionNumbers() {
        var sections = sectionsContainer.querySelectorAll('.work-section');
        sections.forEach(function(sec, idx) {
            var numSpan = sec.querySelector('.section-number');
            if (numSpan) numSpan.textContent = idx + 1;
        });
    }

    function updateRemoveButtons() {
        var sections = sectionsContainer.querySelectorAll('.work-section');
        sections.forEach(function(sec) {
            var btn = sec.querySelector('.remove-section-btn');
            if (btn) {
                btn.style.display = sections.length > 1 ? 'block' : 'none';
            }
        });
    }

    sectionsContainer.addEventListener('click', function(e) {
        var removeBtn = e.target.closest('.remove-section-btn');
        if (removeBtn) {
            var sec = removeBtn.closest('.work-section');
            if (sec) {
                sec.remove();
                updateSectionNumbers();
                updateRemoveButtons();
            }
        }
    });

    // ── Filter budgets & work types inside a specific section ──
    function filterBudgetAndWorkTypesForSection(sectionNode, pid) {
        var budgetSelect = sectionNode.querySelector('.budget-select');
        var workTypeSelect = sectionNode.querySelector('.work-type-select');
        var workTypeHint = sectionNode.querySelector('.work-type-hint');

        // Reset
        budgetSelect.value = '';
        var opts = budgetSelect.querySelectorAll('option');
        var firstMatch = null;
        opts.forEach(function(opt) {
            var dProj = opt.getAttribute('data-project');
            if (!dProj) return; // placeholder option
            var match = dProj === String(pid);
            opt.style.display = match ? '' : 'none';
            opt.disabled      = !match;
            if (match && !firstMatch) firstMatch = opt;
        });

        if (firstMatch) {
            budgetSelect.value = firstMatch.value;
            onSectionBudgetSelected(sectionNode, firstMatch);
            var wtId = firstMatch.getAttribute('data-work-type-id');
            if (wtId) {
                workTypeSelect.value = wtId;
                var hiddenInput = sectionNode.querySelector('.work-type-hidden');
                if (hiddenInput) {
                    hiddenInput.value = wtId;
                }
            }
        } else {
            var info = sectionNode.querySelector('.budget-info');
            if (info) info.innerHTML = '<span class="text-warning">No budget found for this project.</span>';
            var hiddenInput = sectionNode.querySelector('.work-type-hidden');
            if (hiddenInput) {
                hiddenInput.value = '';
            }
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
    }

    // ── Bind event handlers for a new section ──
    function bindSectionEvents(sectionNode, secIdx) {
        var budgetSelect = sectionNode.querySelector('.budget-select');
        var workTypeSelect = sectionNode.querySelector('.work-type-select');
        var viewEstimateBtn = sectionNode.querySelector('.view-estimate-btn');
        var addRowBtn = sectionNode.querySelector('.add-row-btn');

        budgetSelect.addEventListener('change', function() {
            var opt = this.options[this.selectedIndex];
            onSectionBudgetSelected(sectionNode, opt);
            var hiddenInput = sectionNode.querySelector('.work-type-hidden');
            if (opt && opt.getAttribute('data-work-type-id')) {
                var wtId = opt.getAttribute('data-work-type-id');
                workTypeSelect.value = wtId;
                if (hiddenInput) {
                    hiddenInput.value = wtId;
                }
            } else {
                workTypeSelect.value = '';
                if (hiddenInput) {
                    hiddenInput.value = '';
                }
            }
        });

        viewEstimateBtn.addEventListener('click', function() {
            var budgetId = budgetSelect.value;
            var yearVal  = yearSelect.value;
            var monthVal = monthSelect.value;
            fetchBudgetEstimate(budgetId, yearVal, monthVal);
        });

        addRowBtn.addEventListener('click', function() {
            addActivityRow(sectionNode, secIdx);
        });
    }

    // ── Handle budget selection inside a section ──
    function onSectionBudgetSelected(sectionNode, opt) {
        var viewEstimateBtn = sectionNode.querySelector('.view-estimate-btn');
        var budgetInfo = sectionNode.querySelector('.budget-info');
        var weightHint = sectionNode.querySelector('.weight-hint');
        var weightInput = sectionNode.querySelector('.weight-pct-input');
        var prevCostCard = sectionNode.querySelector('.prev-cost-card');

        if (!opt || !opt.value) {
            viewEstimateBtn.style.display = 'none';
            budgetInfo.innerHTML = '';
            prevCostCard.style.setProperty('display', 'none', 'important');
            return;
        }

        viewEstimateBtn.style.display = 'inline-block';

        var budgetId  = parseInt(opt.value);
        var startDate = opt.getAttribute('data-start') || '';
        var endDate   = opt.getAttribute('data-end')   || '';
        var sY = startDate ? parseInt(startDate.substring(0,4)) : 0;
        var eY = endDate   ? parseInt(endDate.substring(0,4))   : 0;
        var hasRange = sY > 2000 && eY > 2000;

        // Budget info text
        var bLink = opt.getAttribute('data-budget-link');
        var wDet  = opt.getAttribute('data-work-details');
        var extHtml = '';
        if (bLink) extHtml += ' | <a href="'+bLink+'" target="_blank" style="text-decoration:underline">Budget Link <i class="bi bi-box-arrow-up-right ms-1"></i></a>';
        if (wDet)  extHtml += '<br><small class="text-muted"><i class="bi bi-info-circle me-1"></i>' + wDet + '</small>';
        budgetInfo.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Budget Total: ৳ ' +
            parseFloat(opt.getAttribute('data-total')).toLocaleString('en-BD') + '</span>' + extHtml;

        // Weight calculation
        var totalWeight     = parseFloat(opt.getAttribute('data-weight'))    || 0;
        var allocatedWeight = parseFloat(opt.getAttribute('data-allocated'))  || 0;
        var remainingWeight = Math.max(0, totalWeight - allocatedWeight);
        var totalMonths = 1;
        if (hasRange) {
            var sDate = new Date(startDate);
            var eDateD = new Date(endDate);
            var months = (eDateD.getFullYear() - sDate.getFullYear()) * 12
                       - sDate.getMonth() + eDateD.getMonth() + 1;
            totalMonths = months <= 0 ? 1 : months;
        }
        var autoWeight = totalWeight > 0 ? Math.min(totalWeight / totalMonths, remainingWeight) : 0;
        if (weightHint && totalWeight > 0) {
            weightHint.innerHTML = '<span class="text-success fw-bold">Remaining: ' + remainingWeight.toFixed(2) + '%</span> (Total: ' + totalWeight.toFixed(2) + '%)';
            weightInput.value = autoWeight.toFixed(2);
            weightInput.max   = remainingWeight.toFixed(2);
        }

        // Fetch cumulative previous cost
        fetchPreviousCost(sectionNode, budgetId, yearSelect.value, monthSelect.value);
    }

    function fetchPreviousCost(sectionNode, budgetId, yearVal, monthVal) {
        var prevCostCard = sectionNode.querySelector('.prev-cost-card');
        var prevCostAmount = sectionNode.querySelector('.prev-cost-amount');

        if (!budgetId || !yearVal || !monthVal) {
            prevCostCard.style.setProperty('display', 'none', 'important');
            return;
        }
        var url = '<?= $base_url ?>/con-ajax-budget-estimate?budget_id=' + budgetId + '&year=' + yearVal + '&month=' + monthVal;
        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.previous_cost > 0) {
                    prevCostAmount.textContent = '৳ ' + parseFloat(data.previous_cost).toLocaleString('en-BD', {minimumFractionDigits: 2});
                    prevCostCard.style.setProperty('display', 'flex', 'important');
                } else {
                    prevCostCard.style.setProperty('display', 'none', 'important');
                }
            })
            .catch(err => console.error(err));
    }

    // ── AJAX: Load Detailed Estimate details into Modal ──
    function fetchBudgetEstimate(budgetId, yearVal, monthVal) {
        if (!budgetId) return;
        
        estimateLoading.style.display = 'block';
        estimateDataArea.style.display = 'none';
        estimateModal.show();

        var url = '<?= $base_url ?>/con-ajax-budget-estimate?budget_id=' + budgetId;
        if (yearVal && monthVal) {
            url += '&year=' + yearVal + '&month=' + monthVal;
        }

        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    var b = data.budget;
                    document.getElementById('modalBudgetId').textContent = '#' + b.id;
                    document.getElementById('modalBudgetTotal').textContent = '৳ ' + parseFloat(b.total_estimated_cost).toLocaleString('en-BD', {minimumFractionDigits:2});
                    document.getElementById('modalBudgetWeight').textContent = parseFloat(b.weight_percentage).toFixed(2) + '%';
                    document.getElementById('modalBudgetDates').textContent = (b.start_date ? b.start_date : '—') + ' to ' + (b.end_date ? b.end_date : '—');

                    // Part 1: Materials
                    var mBody = document.getElementById('modalMaterialBody');
                    mBody.innerHTML = '';
                    var mTotal = 0;
                    if (data.materials.length === 0) {
                        mBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">No materials estimated.</td></tr>';
                    } else {
                        data.materials.forEach((item, idx) => {
                            var amt = parseFloat(item.estimated_qty) * parseFloat(item.unit_cost_approx);
                            mTotal += amt;
                            mBody.innerHTML += `
                                <tr>
                                    <td class="text-center">${idx + 1}</td>
                                    <td class="fw-semibold text-dark">${escapeHtml(item.material_name || 'Material #' + item.material_id)}</td>
                                    <td>${escapeHtml(item.material_unit)}</td>
                                    <td class="text-end">${parseFloat(item.estimated_qty).toLocaleString()}</td>
                                    <td class="text-end">৳ ${parseFloat(item.unit_cost_approx).toLocaleString()}</td>
                                    <td class="text-end fw-bold text-success-emphasis">৳ ${amt.toLocaleString('en-BD', {minimumFractionDigits:2})}</td>
                                </tr>
                            `;
                        });
                    }
                    document.getElementById('modalMaterialTotal').textContent = '৳ ' + mTotal.toLocaleString('en-BD', {minimumFractionDigits:2});

                    // Part 2: Labor
                    var lBody = document.getElementById('modalLaborBody');
                    lBody.innerHTML = '';
                    var lTotal = 0;
                    if (data.labors.length === 0) {
                        lBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">No labor bill forecasted.</td></tr>';
                    } else {
                        data.labors.forEach((item, idx) => {
                            var amt = parseFloat(item.rate_per_sqft) * parseFloat(item.area_sqft);
                            lTotal += amt;
                            lBody.innerHTML += `
                                <tr>
                                    <td class="text-center">${idx + 1}</td>
                                    <td class="fw-semibold text-dark">${escapeHtml(item.labor_type_name)}</td>
                                    <td class="text-end">৳ ${parseFloat(item.rate_per_sqft).toLocaleString()}</td>
                                    <td class="text-end">${parseFloat(item.area_sqft).toLocaleString()} sqft</td>
                                    <td class="text-muted">${escapeHtml(item.description || '')}</td>
                                    <td class="text-end fw-bold text-info-emphasis">৳ ${amt.toLocaleString('en-BD', {minimumFractionDigits:2})}</td>
                                </tr>
                            `;
                        });
                    }
                    document.getElementById('modalLaborTotal').textContent = '৳ ' + lTotal.toLocaleString('en-BD', {minimumFractionDigits:2});

                    // Show data area
                    estimateLoading.style.display = 'none';
                    estimateDataArea.style.display = 'block';
                }
            })
            .catch(err => {
                console.error(err);
            });
    }

    // ── Multi-row dynamic table management inside a Work Section ──
    var activityRowIndex = 0;

    function addActivityRow(sectionNode, secIdx, data = null) {
        var tbody     = sectionNode.querySelector('.activities-table tbody');
        var tr        = document.createElement('tr');
        tr.className  = 'activity-row';
        tr.dataset.index = activityRowIndex;

        var slNo      = data ? data.sl_no : (tbody.querySelectorAll('.activity-row').length + 1);
        var actName   = data ? data.activity_name : '';
        var floorName = data ? data.floor_name : '';
        var qty       = data ? data.quantity : '1 Job';
        var startDate = data ? data.start_date : new Date().toISOString().split('T')[0];
        var reqDays   = data ? data.req_days : 15;
        var endDate   = data ? data.end_date : '';
        var remarks   = data ? data.remarks : '';

        // Build floor options
        var floorOpts = '<option value="">-- Select Floor --</option>';
        floors.forEach(function(fl) {
            var sel = fl === floorName ? 'selected' : '';
            floorOpts += '<option value="' + fl + '" ' + sel + '>' + fl + '</option>';
        });

        tr.innerHTML = `
            <td class="text-center row-index fw-bold text-primary pt-2">${slNo}</td>
            <td>
                <input type="text" name="sections[${secIdx}][items][${activityRowIndex}][activity_name]" class="form-control form-control-sm" required placeholder="যেমন: 2nd floor slab shuttering..." value="${escapeHtml(actName)}">
            </td>
            <td>
                <select name="sections[${secIdx}][items][${activityRowIndex}][floor_name]" class="form-select form-select-sm floor-select">
                    ${floorOpts}
                </select>
            </td>
            <td>
                <input type="text" name="sections[${secIdx}][items][${activityRowIndex}][quantity]" class="form-control form-control-sm" placeholder="যেমন: 1 Job" value="${escapeHtml(qty)}">
            </td>
            <td>
                <input type="date" name="sections[${secIdx}][items][${activityRowIndex}][start_date]" class="form-control form-control-sm start-date" required value="${startDate}">
            </td>
            <td>
                <input type="number" name="sections[${secIdx}][items][${activityRowIndex}][req_days]" class="form-control form-control-sm req-days" required min="1" value="${reqDays}">
            </td>
            <td>
                <input type="date" name="sections[${secIdx}][items][${activityRowIndex}][end_date]" class="form-control form-control-sm end-date" readonly value="${endDate}">
            </td>
            <td>
                <input type="text" name="sections[${secIdx}][items][${activityRowIndex}][remarks]" class="form-control form-control-sm" placeholder="মন্তব্য" value="${escapeHtml(remarks)}">
                <input type="hidden" name="sections[${secIdx}][items][${activityRowIndex}][sort_order]" value="${slNo}">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn"><i class="bi bi-trash"></i></button>
            </td>
        `;

        tbody.appendChild(tr);
        
        // Add change listeners
        var startDateInput = tr.querySelector('.start-date');
        var reqDaysInput   = tr.querySelector('.req-days');
        
        startDateInput.addEventListener('change', function() { updateRowDates(tr); });
        reqDaysInput.addEventListener('input', function() { updateRowDates(tr); });
        
        // Remove handler
        tr.querySelector('.remove-row-btn').addEventListener('click', function() {
            tr.remove();
            reorderIndices(tbody);
        });

        // Initialize End Date calculation
        updateRowDates(tr);
        activityRowIndex++;
    }

    function updateRowDates(row) {
        var startInput = row.querySelector('.start-date');
        var daysInput  = row.querySelector('.req-days');
        var endInput   = row.querySelector('.end-date');

        if (startInput.value && daysInput.value) {
            var startDate = new Date(startInput.value);
            var days      = parseInt(daysInput.value) || 0;
            if (days > 0) {
                var endDate = new Date(startDate.getTime());
                endDate.setDate(startDate.getDate() + days - 1);
                var yyyy = endDate.getFullYear();
                var mm   = String(endDate.getMonth() + 1).padStart(2, '0');
                var dd   = String(endDate.getDate()).padStart(2, '0');
                endInput.value = yyyy + '-' + mm + '-' + dd;
            }
        }
    }

    function reorderIndices(tbody) {
        var rows = tbody.querySelectorAll('tr.activity-row');
        rows.forEach(function(row, idx) {
            row.querySelector('.row-index').textContent = idx + 1;
            var orderInput = row.querySelector('input[name*="[sort_order]"]');
            if (orderInput) orderInput.value = idx + 1;
        });
    }

    function updateAllFloorSelects() {
        var selects = sectionsContainer.querySelectorAll('.floor-select');
        selects.forEach(function(select) {
            var currentValue = select.value;
            var floorOpts = '<option value="">-- Select Floor --</option>';
            floors.forEach(function(fl) {
                var sel = fl === currentValue ? 'selected' : '';
                floorOpts += '<option value="' + fl + '" ' + sel + '>' + fl + '</option>';
            });
            select.innerHTML = floorOpts;
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        return text
            .toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // ── Listeners for year/month change to reload previous cost in all sections ──
    yearSelect.addEventListener('change', function() {
        var sections = sectionsContainer.querySelectorAll('.work-section');
        sections.forEach(function(sec) {
            var bSelect = sec.querySelector('.budget-select');
            if (bSelect && bSelect.value) {
                fetchPreviousCost(sec, bSelect.value, yearSelect.value, monthSelect.value);
            }
        });
    });

    monthSelect.addEventListener('change', function() {
        var sections = sectionsContainer.querySelectorAll('.work-section');
        sections.forEach(function(sec) {
            var bSelect = sec.querySelector('.budget-select');
            if (bSelect && bSelect.value) {
                fetchPreviousCost(sec, bSelect.value, yearSelect.value, monthSelect.value);
            }
        });
    });

    // ── Project selection ──
    projectSelect.addEventListener('change', function () {
        var pid = this.value;
        
        // Clear existing sections
        sectionsContainer.innerHTML = '';
        sectionIndex = 0;

        // Rebuild Year & Month range
        updateYearMonthForProject(pid);

        // Update floors based on project's building type
        var selectedOpt = projectSelect.options[projectSelect.selectedIndex];
        var buildingType = selectedOpt ? selectedOpt.getAttribute('data-building-type') : '';
        floors = getFloorsFromBuildingType(buildingType);

        if (pid) {
            // Add first section automatically
            addWorkSection();
        }
    });

    addSectionBtn.addEventListener('click', function() {
        var pid = projectSelect.value;
        if (!pid) {
            alert('Please select a project first!');
            return;
        }
        addWorkSection();
    });
});
</script>

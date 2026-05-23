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
        <div class="col-lg-12">
            <div class="card__wrapper shadow-sm">
                <div class="card__header bg-warning-transparent">
                    <h6 class="card__title mb-0 fw-bold text-warning-emphasis">
                        <i class="bi bi-pencil-square me-2"></i>Edit Plan Entry (প্রজেক্ট ওয়ার্ক প্ল্যান সংশোধন)
                    </h6>
                </div>
                <div class="card__body p-4">
                    <form action="<?= $base_url ?>/con-working-plan-update/<?= $plan['id'] ?>" method="POST" id="workingPlanForm">

                        <!-- Project & Initial Budget select -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Project <span class="text-danger">*</span></label>
                                <select name="project_id" id="projectSelect" class="form-select" required>
                                    <option value="">-- Select Project --</option>
                                    <?php foreach ($projects as $p): ?>
                                        <option value="<?= $p['id'] ?>" data-building-type="<?= htmlspecialchars($p['building_type'] ?? '') ?>" <?= $p['id'] == $plan['project_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($p['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    Initial Budget <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
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
                                    <button type="button" class="btn btn-outline-info text-nowrap" id="viewEstimateBtn" data-bs-toggle="modal" data-bs-target="#estimateModal">
                                        <i class="bi bi-eye"></i> View Estimate
                                    </button>
                                </div>
                                <div class="form-text" id="budgetInfo"></div>
                            </div>
                        </div>

                        <!-- Work Type + Period -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Work Type <span class="text-danger">*</span></label>
                                <select id="workTypeSelect" class="form-select" disabled>
                                    <option value="">-- Loading... --</option>
                                </select>
                                <input type="hidden" name="work_type_id" id="workTypeHidden" value="<?= $plan['work_type_id'] ?>">
                                <div class="form-text text-info" id="workTypeHint"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Year <span class="text-danger">*</span></label>
                                <select name="year" id="yearSelect" class="form-select" required>
                                    <?php for ($y = date('Y') - 2; $y <= date('Y') + 4; $y++): ?>
                                        <option value="<?= $y ?>" <?= $y == $plan['year'] ? 'selected' : '' ?>><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                                <div class="form-text text-muted" id="yearHint"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Month <span class="text-danger">*</span></label>
                                <select name="month" id="monthSelect" class="form-select" required>
                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?= $m ?>" <?= $m == $plan['month'] ? 'selected' : '' ?>>
                                            <?= date('F', mktime(0, 0, 0, $m, 10)) ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Previous Months' Cost Info Card -->
                        <div class="alert alert-info d-flex align-items-center gap-3 mb-4" id="prevCostCard" style="display:none !important;">
                            <i class="bi bi-cash-stack fs-4 text-primary"></i>
                            <div>
                                <span class="text-muted d-block small">পূর্ববর্তী মাসসমূহের মোট খরচ (Cumulative Approved Cost for this Design Type):</span>
                                <strong id="prevCostAmount" class="fs-6 text-primary">৳ ০.০০</strong>
                            </div>
                        </div>

                        <!-- Activities Dynamic Table Grid -->
                        <div class="card custom-card mb-4 border shadow-none">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                                <h6 class="card-title mb-0 fw-bold text-dark text-uppercase" style="font-size:0.85rem;">
                                    <i class="bi bi-list-task me-2 text-primary"></i>কাজ ও অ্যাক্টিভিটি শিডিউল (Schedule Activities/Phases)
                                </h6>
                                <button type="button" class="btn btn-sm btn-success" id="addRowBtn">
                                    <i class="bi bi-plus-circle me-1"></i> Add Activity
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm align-middle mb-0" id="activitiesTable" style="font-size: 0.86rem;">
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
                                            <!-- Dynamic rows will be pre-filled via JS -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Estimated Cost & Weight (Plan metadata) -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    Estimated Cost (৳) <span class="text-danger">*</span>
                                </label>
                                <input type="number" step="0.01" name="estimated_cost" id="estimatedCost"
                                       class="form-control" placeholder="0.00" value="<?= $plan['estimated_cost'] ?>" min="0.01" required>
                                <div class="form-text text-danger" id="costWarning"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    Project Weight (%) <span class="text-danger">*</span>
                                </label>
                                <input type="number" step="0.01" name="weight_percentage" id="weightPercentageInput"
                                       class="form-control" placeholder="0.00" value="<?= $plan['weight_percentage'] ?? '0.00' ?>" min="0.00" max="100.00" required>
                                <div class="form-text text-muted" id="weightHint">এই মাসটি পুরো প্রজেক্টের কত %</div>
                            </div>
                        </div>

                        <!-- Range warning / exhausted notice -->
                        <div id="rangeWarning"></div>

                        <div class="text-end mt-4">
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

                    <!-- Part 1: Materials -->
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

                    <!-- Part 2: Labor -->
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
    var projectSelect    = document.getElementById('projectSelect');
    var budgetSelect     = document.getElementById('budgetSelect');
    var yearSelect       = document.getElementById('yearSelect');
    var monthSelect      = document.getElementById('monthSelect');
    var budgetInfo       = document.getElementById('budgetInfo');
    var workTypeSelect   = document.getElementById('workTypeSelect');
    var workTypeHint     = document.getElementById('workTypeHint');
    var weightPercentageInput = document.getElementById('weightPercentageInput');
    var weightHint       = document.getElementById('weightHint');
    var allBudgetOpts    = budgetSelect.querySelectorAll('option[data-project]');
    
    // View Estimate Modal references
    var viewEstimateBtn  = document.getElementById('viewEstimateBtn');
    var estimateLoading  = document.getElementById('estimateLoading');
    var estimateDataArea = document.getElementById('estimateDataArea');
    var prevCostCard     = document.getElementById('prevCostCard');
    var prevCostAmount   = document.getElementById('prevCostAmount');

    // Pre-loaded data maps
    var workTypeMap      = <?= json_encode($workTypesByProject,  JSON_UNESCAPED_UNICODE) ?>;
    var plannedMap       = <?= json_encode($plannedMonthsMap ?? [], JSON_UNESCAPED_UNICODE) ?>;
    
    var currentWorkTypeId   = <?= (int)$plan['work_type_id'] ?>;
    var currentPlanWeight   = <?= (float)($plan['weight_percentage'] ?? 0) ?>;
    var initialBudgetId     = <?= (int)$plan['initial_budget_id'] ?>;
    var existingItems       = <?= json_encode($planItems) ?>;

    var monthNames = ['','January','February','March','April','May','June',
                      'July','August','September','October','November','December'];

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

    var selectedOpt = projectSelect.options[projectSelect.selectedIndex];
    var buildingType = selectedOpt ? selectedOpt.getAttribute('data-building-type') : '';
    var floors = getFloorsFromBuildingType(buildingType);

    function updateAllFloorSelects() {
        var rows = activitiesTableBody.querySelectorAll('tr.activity-row');
        rows.forEach(function(row) {
            var select = row.querySelector('select[name*="[floor_name]"]');
            if (select) {
                var currentValue = select.value;
                var floorOpts = '<option value="">-- Select Floor --</option>';
                floors.forEach(function(fl) {
                    var sel = fl === currentValue ? 'selected' : '';
                    floorOpts += '<option value="' + fl + '" ' + sel + '>' + fl + '</option>';
                });
                select.innerHTML = floorOpts;
            }
        });
    }

    // ── Multi-row dynamic table management ──
    var activitiesTableBody = document.querySelector('#activitiesTable tbody');
    var addRowBtn           = document.getElementById('addRowBtn');
    var rowIndex            = 0;

    function addActivityRow(data = null) {
        var tr = document.createElement('tr');
        tr.className = 'activity-row';
        tr.dataset.index = rowIndex;

        var slNo      = data ? data.sl_no : (rowIndex + 1);
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
                <input type="text" name="wi_items[${rowIndex}][activity_name]" class="form-control form-control-sm" required placeholder="যেমন: 2nd floor slab shuttering..." value="${escapeHtml(actName)}">
            </td>
            <td>
                <select name="wi_items[${rowIndex}][floor_name]" class="form-select form-select-sm">
                    ${floorOpts}
                </select>
            </td>
            <td>
                <input type="text" name="wi_items[${rowIndex}][quantity]" class="form-control form-control-sm" placeholder="যেমন: 1 Job" value="${escapeHtml(qty)}">
            </td>
            <td>
                <input type="date" name="wi_items[${rowIndex}][start_date]" class="form-control form-control-sm start-date" required value="${startDate}">
            </td>
            <td>
                <input type="number" name="wi_items[${rowIndex}][req_days]" class="form-control form-control-sm req-days" required min="1" value="${reqDays}">
            </td>
            <td>
                <input type="date" name="wi_items[${rowIndex}][end_date]" class="form-control form-control-sm end-date" readonly value="${endDate}">
            </td>
            <td>
                <input type="text" name="wi_items[${rowIndex}][remarks]" class="form-control form-control-sm" placeholder="মন্তব্য" value="${escapeHtml(remarks)}">
                <input type="hidden" name="wi_items[${rowIndex}][sort_order]" value="${rowIndex}">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn"><i class="bi bi-trash"></i></button>
            </td>
        `;

        activitiesTableBody.appendChild(tr);
        
        // Add change listeners
        var startDateInput = tr.querySelector('.start-date');
        var reqDaysInput   = tr.querySelector('.req-days');
        
        startDateInput.addEventListener('change', function() { updateRowDates(tr); });
        reqDaysInput.addEventListener('input', function() { updateRowDates(tr); });
        
        // Remove handler
        tr.querySelector('.remove-row-btn').addEventListener('click', function() {
            tr.remove();
            reorderIndex();
        });

        updateRowDates(tr);
        rowIndex++;
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

    function reorderIndex() {
        var rows = activitiesTableBody.querySelectorAll('tr.activity-row');
        rows.forEach(function(row, idx) {
            row.querySelector('.row-index').textContent = idx + 1;
        });
    }

    addRowBtn.addEventListener('click', function() {
        addActivityRow();
    });

    // Populate existing items or add a default row
    if (existingItems && existingItems.length > 0) {
        existingItems.forEach(function(item) {
            addActivityRow(item);
        });
    } else {
        addActivityRow();
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

    // ── AJAX: Load Detailed Estimate & Previous Cost ──
    function fetchBudgetEstimate(budgetId, yearVal, monthVal) {
        if (!budgetId) return;
        
        estimateLoading.style.display = 'block';
        estimateDataArea.style.display = 'none';

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

                    // 4. Update Previous Cost Info Card
                    if (data.previous_cost > 0) {
                        prevCostAmount.textContent = '৳ ' + parseFloat(data.previous_cost).toLocaleString('en-BD', {minimumFractionDigits: 2});
                        prevCostCard.style.setProperty('display', 'flex', 'important');
                    } else {
                        prevCostCard.style.setProperty('display', 'none', 'important');
                    }

                    // Show data area
                    estimateLoading.style.display = 'none';
                    estimateDataArea.style.display = 'block';
                }
            })
            .catch(err => {
                console.error(err);
            });
    }

    viewEstimateBtn.addEventListener('click', function() {
        var budgetId = budgetSelect.value;
        var yearVal  = yearSelect.value;
        var monthVal = monthSelect.value;
        fetchBudgetEstimate(budgetId, yearVal, monthVal);
    });

    function applyWorkTypeFromBudget(opt) {
        if (!opt) return;

        var budgetId = opt.value;
        var totalWeight = parseFloat(opt.getAttribute('data-weight')) || 0;
        var allocatedWeight = parseFloat(opt.getAttribute('data-allocated')) || 0;
        
        // Deduct current plan's weight if editing the original budget
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
            months += 1;
            totalMonths = months <= 0 ? 1 : months;
        }
        autoSuggestedWeight = totalWeight > 0 ? (totalWeight / totalMonths) : 0;
        if (autoSuggestedWeight > remainingWeight) autoSuggestedWeight = remainingWeight;

        if (totalWeight > 0) {
            weightHint.innerHTML = '<span class="text-success fw-bold">Remaining: ' + remainingWeight.toFixed(2) + '%</span> (Total: ' + totalWeight.toFixed(2) + '%)';
            weightPercentageInput.max = remainingWeight.toFixed(2);
            if (budgetId != initialBudgetId || currentPlanWeight === 0) {
                weightPercentageInput.value = autoSuggestedWeight.toFixed(2);
            }
        } else {
            weightHint.innerHTML = 'এই মাসটি পুরো প্রজেক্টের কত %';
        }

        // Auto-select work type from budget
        if (workTypeSelect) {
            var wtId = opt.getAttribute('data-work-type-id') || '';
            if (wtId) {
                workTypeSelect.value = wtId;
                var workTypeHidden = document.getElementById('workTypeHidden');
                if (workTypeHidden) {
                    workTypeHidden.value = wtId;
                }
                var selOpt = workTypeSelect.options[workTypeSelect.selectedIndex];
                var wtLabel = selOpt ? selOpt.textContent.trim() : '';
                if (workTypeSelect.value == wtId && wtLabel) {
                    if (workTypeHint) workTypeHint.innerHTML = '<i class="bi bi-check-circle me-1"></i>Auto-selected: <strong>' + wtLabel + '</strong>';
                }
            }
        }

        // Fetch cumulative previous cost
        fetchPreviousCost(budgetId, yearSelect.value, monthSelect.value);
    }

    function fetchPreviousCost(budgetId, yearVal, monthVal) {
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

    // Budget manual selection
    budgetSelect.addEventListener('change', function () {
        var opt = this.options[this.selectedIndex];
        applyWorkTypeFromBudget(opt);
    });

    // Listeners for year/month change to reload previous cost
    yearSelect.addEventListener('change', function() {
        fetchPreviousCost(budgetSelect.value, yearSelect.value, monthSelect.value);
    });

    monthSelect.addEventListener('change', function() {
        fetchPreviousCost(budgetSelect.value, yearSelect.value, monthSelect.value);
    });

    // Project manual selection
    projectSelect.addEventListener('change', function() {
        var pid = this.value;
        budgetSelect.value = '';
        budgetInfo.textContent = '';
        if (pid) {
            loadWorkTypes(pid);
        } else {
            workTypeSelect.innerHTML = '<option value="">-- Select Project First --</option>';
            var workTypeHidden = document.getElementById('workTypeHidden');
            if (workTypeHidden) {
                workTypeHidden.value = '';
            }
        }
        prevCostCard.style.setProperty('display', 'none', 'important');

        var selectedOpt = projectSelect.options[projectSelect.selectedIndex];
        var buildingType = selectedOpt ? selectedOpt.getAttribute('data-building-type') : '';
        floors = getFloorsFromBuildingType(buildingType);
        updateAllFloorSelects();
    });

    // Initial load
    if (projectSelect && projectSelect.value) {
        loadWorkTypes(projectSelect.value, function() {
            workTypeSelect.value = currentWorkTypeId;
            var workTypeHidden = document.getElementById('workTypeHidden');
            if (workTypeHidden) {
                workTypeHidden.value = currentWorkTypeId;
            }
        });
    }
    
    if (budgetSelect && budgetSelect.value) {
        applyWorkTypeFromBudget(budgetSelect.options[budgetSelect.selectedIndex]);
    }
});
</script>

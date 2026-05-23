<?php
$base_url   = $GLOBALS['base_url'];
$page_title = $page_title ?? 'New Site Target';
$months     = ['January','February','March','April','May','June','July','August','September','October','November','December'];
?>
<div class="container-fluid">
    <!-- Breadcrumb -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h4 class="page-title fw-semibold fs-18 mb-0"><?= $page_title ?></h4>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-targets">Site Targets</a></li>
                <li class="breadcrumb-item active">New Target</li>
            </ol>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-xxl-8 col-xl-10">
            <div class="card custom-card">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-bullseye me-2 text-primary"></i> Create Site Target</h6>
                </div>
                <div class="card-body">
                    <form action="<?= $base_url ?>/con-target-store" method="POST">

                        <!-- Project & Work Plan Row -->
                        <div class="row  mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Project <span class="text-danger">*</span></label>
                                <select name="project_id" id="project_id" class="form-select" required>
                                    <option value="">-- Select Project --</option>
                                    <?php foreach ($projects as $p): ?>
                                        <option value="<?= $p['id'] ?>" data-building-type="<?= htmlspecialchars($p['building_type'] ?? '') ?>"><?= htmlspecialchars($p['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Working Plan <span class="text-danger">*</span></label>
                                <select name="work_plan_id" id="work_plan_id" class="form-select" required disabled>
                                    <option value="">-- Select Project First --</option>
                                </select>
                                <div id="planInfo" class="mt-1 text-muted small"></div>
                            </div>
                        </div>



                        <!-- Target Linked To -->
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Work Order (Optional)</label>
                                <select name="work_order_id" id="work_order_id" class="form-select" disabled>
                                    <option value="">-- Select Project First --</option>
                                </select>
                                <div class="form-text">Link this target to a specific work order.</div>
                                <div id="woDetailsBox" class="mt-2 d-none p-2 bg-light border rounded small text-muted"></div>
                            </div>
                        </div>

                        <!-- Target Title -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Target Title <span class="text-danger">*</span></label>
                            <input type="text" name="target_title" class="form-control" required
                                   placeholder="e.g. Column Cast — Zone A">
                        </div>

                        <!-- Year / Month / Floor / Week -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Year <span class="text-danger">*</span></label>
                                <input type="number" name="year" class="form-control"
                                       value="<?= date('Y') ?>" min="2020" max="2035" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Month <span class="text-danger">*</span></label>
                                <select name="month" class="form-select" required>
                                    <?php foreach ($months as $i => $m): ?>
                                        <option value="<?= $i+1 ?>" <?= (date('n') == $i+1) ? 'selected' : '' ?>>
                                            <?= $m ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Floor Name (Optional)</label>
                                <select name="floor_name" id="floor_name" class="form-select">
                                    <option value="">-- Select Floor --</option>
                                    <option value="Foundation">Foundation</option>
                                    <option value="Basement">Basement</option>
                                    <option value="G.floor">G.floor</option>
                                    <option value="1st floor">1st floor</option>
                                    <option value="2nd floor">2nd floor</option>
                                    <option value="3rd floor">3rd floor</option>
                                    <option value="4th floor">4th floor</option>
                                    <option value="5th floor">5th floor</option>
                                    <option value="6th floor">6th floor</option>
                                    <option value="7th floor">7th floor</option>
                                    <option value="8th floor">8th floor</option>
                                    <option value="9th floor">9th floor</option>
                                    <option value="10th floor">10th floor</option>
                                    <option value="11th floor">11th floor</option>
                                    <option value="12th floor">12th floor</option>
                                    <option value="Roof">Roof</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Week Number (Optional)</label>
                                <select name="week_number" class="form-select">
                                    <option value="">-- Monthly Target --</option>
                                    <option value="1">Week 1</option>
                                    <option value="2">Week 2</option>
                                    <option value="3">Week 3</option>
                                    <option value="4">Week 4</option>
                                </select>
                            </div>
                        </div>

                        <!-- Target Percentage -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Target Percentage (%) <span class="text-danger">*</span></label>
                            <input type="number" name="target_percentage" class="form-control"
                                   step="0.01" min="0" max="100" required placeholder="e.g. 25.00">
                            <div class="form-text">এই মাস/সপ্তাহে কতটুকু কাজ শেষ করতে হবে (%)</div>
                        </div>

                        <!-- Lab Test Section -->
                        <div class="card  border mb-3 p-3">
                            <div class="fw-semibold mb-2">
                                <i class="bi bi-flask me-1 text-info"></i> Lab Test Required?
                            </div>
                            <div class="d-flex gap-4 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="is_lab_test"
                                           id="labTestNo" value="0" checked>
                                    <label class="form-check-label" for="labTestNo">No</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="is_lab_test"
                                           id="labTestYes" value="1">
                                    <label class="form-check-label" for="labTestYes">Yes</label>
                                </div>
                            </div>
                            <div id="labDateWrapper" style="display:none;">
                                <label class="form-label fw-semibold">Lab Test Date <span class="text-danger">*</span></label>
                                <input type="date" name="lab_test_date" id="lab_test_date"
                                       class="form-control" style="max-width:260px;">
                            </div>
                        </div>

                        <!-- Target Description -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-card-text me-1"></i> Description
                            </label>
                            <textarea name="description" class="form-control" rows="3" id="tinymce_simple_textarea"
                                      placeholder="Description..."></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check2-circle me-1"></i> Create Target
                            </button>
                            <a href="<?= $base_url ?>/con-targets" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= $base_url ?>';

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

document.getElementById('project_id').addEventListener('change', function () {
    const pid = this.value;
    const planSel   = document.getElementById('work_plan_id');
    const woSel     = document.getElementById('work_order_id');
    const woDetails = document.getElementById('woDetailsBox');
    const planInfo  = document.getElementById('planInfo');
    const floorSel  = document.getElementById('floor_name');

    planSel.innerHTML = '<option value="">-- Loading... --</option>';
    planSel.disabled  = true;
    woSel.innerHTML   = '<option value="">-- Loading... --</option>';
    woSel.disabled    = true;
    woDetails.classList.add('d-none');
    planInfo.textContent = '';

    // Dynamic floors logic
    const selectedOpt = this.options[this.selectedIndex];
    const buildingType = selectedOpt ? selectedOpt.getAttribute('data-building-type') : '';
    const floors = getFloorsFromBuildingType(buildingType);
    if (floorSel) {
        let floorOpts = '<option value="">-- Select Floor --</option>';
        floors.forEach(function(fl) {
            floorOpts += '<option value="' + fl + '">' + fl + '</option>';
        });
        floorSel.innerHTML = floorOpts;
    }

    if (!pid) {
        planSel.innerHTML = '<option value="">-- Select Project First --</option>';
        woSel.innerHTML = '<option value="">-- Select Project First --</option>';
        return;
    }

    fetch(BASE_URL + '/con-ajax-project-data?project_id=' + pid)
        .then(r => r.json())
        .then(data => {
            planSel.innerHTML = '<option value="">-- Select Working Plan --</option>';
            if (!data.work_plans || !data.work_plans.length) {
                planSel.innerHTML += '<option disabled>No plans found for this project</option>';
            } else {
                data.work_plans.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.id;
                    opt.textContent = p.label;
                    opt.dataset.design  = p.design_type;
                    opt.dataset.desc    = p.work_description;
                    opt.dataset.budget  = p.budget_label;
                    opt.dataset.budgetId = p.initial_budget_id;
                    planSel.appendChild(opt);
                });
            }
            planSel.disabled = false;

            woSel.innerHTML = '<option value="">-- Select Work Order --</option>';
            if (data.work_orders && data.work_orders.length) {
                data.work_orders.forEach(w => {
                    const opt = document.createElement('option');
                    opt.value = w.id;
                    opt.textContent = w.wo_number + ' - ' + w.title;
                    opt.dataset.desc = w.description || '';
                    opt.dataset.design = w.design_name || '';
                    woSel.appendChild(opt);
                });
            } else {
                woSel.innerHTML += '<option disabled>No active work orders found</option>';
            }
            woSel.disabled = false;
        })
        .catch(() => {
            planSel.innerHTML = '<option value="">-- Error loading plans --</option>';
            planSel.disabled  = false;
            woSel.innerHTML = '<option value="">-- Error loading work orders --</option>';
            woSel.disabled = false;
        });
});

document.getElementById('work_plan_id').addEventListener('change', function () {
    const opt       = this.options[this.selectedIndex];
    const planInfo  = document.getElementById('planInfo');

    let planHtml = '';
    if (opt.dataset.design) planHtml += `<strong>Design:</strong> ${opt.dataset.design}<br>`;
    if (opt.dataset.desc) planHtml += `<strong>Description:</strong> ${opt.dataset.desc}`;
    planInfo.innerHTML = planHtml;
});

document.getElementById('work_order_id').addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    const detailsBox = document.getElementById('woDetailsBox');
    if (this.value && (opt.dataset.desc || opt.dataset.design)) {
        let html = '';
        if (opt.dataset.design) html += `<strong>Design:</strong> ${opt.dataset.design}<br>`;
        if (opt.dataset.desc) html += `<strong>Description:</strong> ${opt.dataset.desc}`;
        detailsBox.innerHTML = html;
        detailsBox.classList.remove('d-none');
    } else {
        detailsBox.classList.add('d-none');
    }
});

// Lab Test toggle
document.querySelectorAll('input[name="is_lab_test"]').forEach(function(radio) {
    radio.addEventListener('change', function () {
        const wrapper = document.getElementById('labDateWrapper');
        const dateInput = document.getElementById('lab_test_date');
        if (this.value === '1') {
            wrapper.style.display = 'block';
            dateInput.required = true;
        } else {
            wrapper.style.display = 'none';
            dateInput.required = false;
            dateInput.value = '';
        }
    });
});
</script>

<?php
$page_title = "Create Project";
$page = 'Add Project';
$show_breadcrumb = true;
?>

<div class="row">
    <div class="card__wrapper">
        <form action="<?= $GLOBALS['base_url'] ?>/store-project" method="POST" enctype="multipart/form-data">
            <div class="row gy-20">

                <!-- Project Name -->
                <div class="col-xl-6">
                    <label>Project Name *</label>
                    <input type="text" name="name" class="form-control" required>
                </div>

                <!-- Category -->
                <div class="col-xl-4">
                    <label>Project Category</label>
                    <select name="category_id" id="projectCategorySelect" class="form-control">
                        <option value="">— Select Category —</option>
                        <?php foreach ($categories ?? [] as $cat): ?>
                        <option value="<?= $cat['id'] ?>"
                                data-incentive="<?= number_format((float)$cat['incentive_percentage'], 2) ?>">
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Incentive % (auto-filled from category) -->
                <div class="col-xl-2">
                    <label>Incentive %</label>
                    <div class="input-group">
                        <input type="number" name="incentive_percentage" id="projectIncentiveInput"
                               class="form-control" step="0.01" min="0" max="100"
                               placeholder="0.00" readonly style="background:#f8f9fa;">
                        <span class="input-group-text">%</span>
                    </div>
                    <small class="text-muted" style="font-size:.72rem;">Auto from category</small>
                </div>

                <!-- Location -->
                <div class="col-xl-6">
                    <label>Location</label>
                    <div class="d-flex gap-2">
                        <select name="location_id" class="form-control" style="flex:1;">
                            <option value="">Select Location</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?= $location['id']; ?>"><?= $location['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <a href="<?= $GLOBALS['base_url'] ?>/location-lists"
                            class="btn btn-secondary"
                            style="height: 38px; display:flex; align-items:center; padding:0 12px;">
                            + Location
                        </a>
                    </div>
                </div>

                <!-- Community -->
                <div class="col-xl-6">
                    <label>Community</label>
                    <div class="d-flex gap-2">
                        <select name="community_id" class="form-control" style="flex:1;">
                            <option value="">Select Community</option>
                            <?php foreach ($communities as $community): ?>
                                <option value="<?= $community['id']; ?>"><?= $community['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <a href="<?= $GLOBALS['base_url'] ?>/community-lists"
                            class="btn btn-secondary"
                            style="height: 38px; display:flex; align-items:center; padding:0 12px;">
                            + Community
                        </a>
                    </div>
                </div>


                <!-- Building Type -->
                <div class="col-xl-6">
                    <label>Building Type</label>
                    <input type="text" name="building_type" class="form-control" placeholder="li G+7">
                </div>

                <!-- Building Face -->
                <div class="col-xl-6">
                    <label>Building Face</label>
                    <input type="text" name="building_face" class="form-control" placeholder="e.g. Infinity sea view">
                </div>

                <!-- Suite Sizes -->
                <div class="col-xl-6">
                    <label>Suite Sizes</label>
                    <input type="text" name="suite_sizes" class="form-control" placeholder="e.g. 670 to 1,700 sq ft">
                </div>

                <!-- Hotel Operation -->
                <div class="col-xl-6">
                    <label>Hotel Operation</label>
                    <input type="text" name="hotel_operation" class="form-control" placeholder="e.g. By The Nature’s dedicated hospitality team">
                </div>

                <!-- Construction Start -->
                <div class="col-xl-6">
                    <label>Construction Start</label>
                    <input type="text" name="construction_start" class="form-control" placeholder="e.g. February 2027 (planned)">
                </div>

                <!-- Handover -->
                <div class="col-xl-6">
                    <label>Handover</label>
                    <input type="text" name="handover" class="form-control" placeholder="e.g. August 2030 (tentative), plus a 6-month grace period">
                </div>

                <!-- Delivery -->
                <div class="col-xl-6">
                    <label>Delivery</label>
                    <input type="text" name="delivery" class="form-control" placeholder="e.g. Fully furnished suites">
                </div>

                <!-- Total Share -->
                <div class="col-xl-6">
                    <label>Total Share</label>
                    <input type="number" name="total_share" class="form-control">
                </div>

                <!-- Unit Per Floor -->
                <div class="col-xl-6">
                    <label>Unit Per Floor</label>
                    <input type="number" name="unit_per_floor" class="form-control">
                </div>

                <!-- Floor Wise Units -->
                <div class="col-12">
                    <label>Floor Wise Units</label>
                    <div id="unit-wrapper">
                        <div class="row unit-row mb-2 align-items-center">
                            <div class="col-md-6 col-12">
                                <input type="text" name="units[0][unit_name]" class="form-control" placeholder="Unit Name (A)">
                            </div>
                            <div class="col-md-5 col-12">
                                <input type="number" name="units[0][sqft]" class="form-control" placeholder="Sqft">
                            </div>
                            <div class="col-md-1 col-12 d-flex justify-content-center">
                                <button type="button" class="btn btn-danger remove-unit w-100">×</button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-secondary mt-2" id="add-unit">
                        + Add Unit
                    </button>
                </div>

                <!-- Project Type -->
                <div class="col-xl-6">
                    <label>Project Type</label>
                    <input type="text" name="types" class="form-control">
                </div>

                <!-- Price Range -->
                <div class="col-xl-6">
                    <label>Price Range</label>
                    <select name="price_range" class="form-control">
                        <option value="">Select Price Range</option>
                        <option value="Under 50 Lac BDT">Under 50 Lac BDT</option>
                        <option value="50 Lac - 1 Crore BDT">50 Lac - 1 Crore BDT</option>
                        <option value="1 Crore - 2 Crore BDT">1 Crore - 2 Crore BDT</option>
                        <option value="2 Crore - 5 Crore BDT">2 Crore - 5 Crore BDT</option>
                        <option value="Above 5 Crore BDT">Above 5 Crore BDT</option>
                    </select>
                </div>

                <!-- Service Charge Per EMI -->
                <div class="col-xl-6">
                    <label>Service Charge <span class="text-muted">(per EMI %)</span></label>
                    <input type="number" step="0.01" min="0" name="service_charge_per_emi" class="form-control" placeholder="e.g. 1.5">
                </div>
            <!-- Project wise Status -->
                <div class="col-xl-6">
                    <label>Project Sale Status</label>
                    <select name="project_sale_status" class="form-control">
                        <option value="">Select Sale Status</option>
                        <option value="Sold Out">Sold Out</option>
                        <option value="On Sale">On Sale</option>
                        <option value="Upcoming">Upcoming</option>
                    </select>
                </div>

                <!-- Front Road -->
                <div class="col-xxl-6 col-md-6 col-12">
                    <label>Front Road</label>
                    <input type="text" name="front_road" class="form-control" placeholder="In Feet">
                </div>
                <!-- Apartment Count -->
                <div class="col-xxl-6 col-md-6 col-12">
                    <label>Apartment Count</label>
                    <input type="number" name="apartment_count" class="form-control">
                </div>
                <!-- Hover Text -->
                <div class="col-xl-6">
                    <label>Hover Text</label>
                    <input type="text" name="hover_text" class="form-control">
                </div>

                <!-- plot Size -->
                <div class="col-xl-6">
                    <label>Plot Size in Katha</label>
                    <input type="text" name="plot_size" class="form-control">
                </div>
                <!-- plot Size -->
                <div class="col-xl-6">
                    <label>Plot No</label>
                    <input type="text" name="plot_no" class="form-control">
                </div>

                <!-- block Size -->
                <div class="col-xl-6">
                    <label>Block Number</label>
                    <input type="text" name="block_no" class="form-control">
                </div>
                <div class="col-xl-6 col-md-6">
                    <div class="from__input-box">
                        <div class="form__input-title">
                            <label>Passenger Lift</label>
                        </div>
                        <div class="form__input">
                            <input
                                type="text"
                                name="passenger_lift"
                                class="form-control">
                        </div>
                    </div>
                </div>

                <div class="col-xl-6 col-md-6">
                    <div class="from__input-box">
                        <div class="form__input-title">
                            <label>Cargo Lift</label>
                        </div>
                        <div class="form__input">
                            <input
                                type="text"
                                name="cargo_lift"
                                class="form-control">
                        </div>
                    </div>
                </div>

                <!-- Brochure Link -->
                <div class="col-xl-6">
                    <label>Brochure Link</label>
                    <input type="text" name="brochure_link" class="form-control">
                </div>

    
                <!-- Map Image -->
                <div class="col-xl-6">
                    <label>Map Image</label>
                    <input type="file" name="map_image" class="form-control">
                </div>
             <!-- Amenities Image -->
                <div class="col-xl-6">
                    <label>Project Map Location Name</label>
                    <input type="text" name="project_map_location" class="form-control">
                </div>

                <!-- Map Link -->
                <div class="col-xl-6">
                    <label>Map Link</label>
                    <input type="text" name="map_link" class="form-control">
                </div>
                <div class="col-xl-3 col-6">
                    <label>Amenities Image</label>
                    <input type="file" name="amenities_image" class="form-control">
                </div>
                <!-- Project Images -->
                <div class="col-xl-3 col-6">
                    <label>Main Image</label>
                    <input type="file" name="image" class="form-control">
                </div>

                <div class="col-xxl-3 col-md-6 col-6">
                    <label>Image 2</label>
                    <input type="file" name="image2" class="form-control">
                </div>

                <div class="col-xxl-3 col-md-6 col-6">
                    <label>Image 3</label>
                    <input type="file" name="image3" class="form-control">
                </div>

                <!-- Common Project -->
                <div class="col-xxl-3 col-md-6 col-6">
                    <label class="d-block">&nbsp;</label>
                    <div class="form-check mt-2">
                        <input type="checkbox" class="form-check-input" name="is_common" id="is_common" value="1">
                        <label class="form-check-label" for="is_common">Is Common Project</label>
                    </div>
                </div>


                <!-- Facilities -->
                <div class="col-12">
                    <h5 class="mb-2">Facilities</h5>
                </div>

                <div class="col-xl-3 col-md-6">
                    <label class="facility-box">
                        <input type="checkbox" name="rooftop_gardening" value="1">
                        <i class="fa-solid fa-seedling text-success"></i>
                        <span class="facility-label">Rooftop Gardening</span>
                    </label>
                </div>

                <div class="col-xl-3 col-md-6">
                    <label class="facility-box">
                        <input type="checkbox" name="car_parking" value="1">
                        <i class="fa-solid fa-person-car text-primary"></i>
                        <span class="facility-label">Car Parking</span>
                    </label>
                </div>

                <div class="col-xl-3 col-md-6">
                    <label class="facility-box">
                        <input type="checkbox" name="convention" value="1">
                        <i class="fa-solid fa-building-columns text-warning"></i>
                        <span class="facility-label">Convention Hall</span>
                    </label>
                </div>

                <div class="col-xl-3 col-md-6">
                    <label class="facility-box">
                        <input type="checkbox" name="electricity_backup" value="1">
                        <i class="fa-solid fa-bolt text-danger"></i>
                        <span class="facility-label">Electricity Backup</span>
                    </label>
                </div>

                <div class="col-xl-3 col-md-6">
                    <label class="facility-box">
                        <input type="checkbox" name="under_construction" value="1">
                        <i class="fa-solid fa-person-digging text-info"></i>
                        <span class="facility-label">Under Construction</span>
                    </label>
                </div>




                <!-- Amenities Details -->
                <div class="col-xl-12">
                    <label>Amenities Details</label>
                    <textarea name="amenities_details" id="tinymce_simple_textarea"></textarea>
                </div>


                <!-- Description -->
                <div class="col-12">
                    <label>Project Description *</label>
                    <textarea id="tinymce_simple_textarea" name="description"></textarea>
                </div>

                <!-- ════════════════════════════════════
                     PROJECT TEAM ASSIGNMENT
                ════════════════════════════════════ -->
                <div class="col-12 mt-3">
                    <h5 class="mb-3" style="border-bottom:2px solid #e2e8f0;padding-bottom:8px;">
                        <i class="fa-regular fa-users-gear text-primary me-2"></i>Project Team Assignment
                    </h5>
                </div>

                <!-- Project Manager -->
                <div class="col-xl-4 col-md-6">
                    <label for="team_pm_id">Project Manager (PM)</label>
                    <select id="team_pm_id" name="pm_id" class="form-control select2-no-scroll">
                        <option value="">-- Select PM --</option>
                        <?php foreach ($teamUsers['project_managers'] ?? [] as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Site Engineer -->
                <div class="col-xl-4 col-md-6">
                    <label for="team_site_engineer_id">Site Engineer</label>
                    <select id="team_site_engineer_id" name="site_engineer_id" class="form-control select2-no-scroll">
                        <option value="">-- Select Site Engineer --</option>
                        <?php foreach ($teamUsers['site_engineers'] ?? [] as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Land Manager -->
                <div class="col-xl-4 col-md-6">
                    <label for="team_landman_id">Land Manager</label>
                    <select id="team_landman_id" name="landman_id" class="form-control select2-no-scroll">
                        <option value="">-- Select Land Manager --</option>
                        <?php foreach ($teamUsers['land_managers'] ?? [] as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Structural Engineer -->
                <div class="col-xl-4 col-md-6">
                    <label for="team_struct_engineer_id">Structural Engineer</label>
                    <select id="team_struct_engineer_id" name="struct_engineer_id" class="form-control select2-no-scroll">
                        <option value="">-- Select Structural Engineer --</option>
                        <?php foreach ($teamUsers['struct_engineers'] ?? [] as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

 

                <!-- Draftsman -->
                <div class="col-xl-4 col-md-6">
                    <label for="team_draftsman_id">Draftsman</label>
                    <select id="team_draftsman_id" name="draftsman_id" class="form-control select2-no-scroll">
                        <option value="">-- Select Draftsman --</option>
                        <?php foreach ($teamUsers['draftsmen'] ?? [] as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Architect -->
                <div class="col-xl-4 col-md-6">
                    <label for="team_architect_id">Architect</label>
                    <select id="team_architect_id" name="architect_id" class="form-control select2-no-scroll">
                        <option value="">-- Select Architect --</option>
                        <?php foreach ($teamUsers['architects'] ?? [] as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Estimator -->
                <div class="col-xl-4 col-md-6">
                    <label for="team_estimator_id">Estimator</label>
                    <select id="team_estimator_id" name="estimator_id" class="form-control select2-no-scroll">
                        <option value="">-- Select Estimator --</option>
                        <?php foreach ($teamUsers['estimators'] ?? [] as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Quality Controller -->
                <div class="col-xl-4 col-md-6">
                    <label for="team_qc_id">Quality Controller (QC)</label>
                    <select id="team_qc_id" name="qc_id" class="form-control select2-no-scroll">
                        <option value="">-- Select QC --</option>
                        <?php foreach ($teamUsers['qc_controllers'] ?? [] as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Site Operator -->
                <div class="col-xl-4 col-md-6">
                    <label for="team_operator_id">Site Operator</label>
                    <select id="team_operator_id" name="operator_id" class="form-control select2-no-scroll">
                        <option value="">-- Select Site Operator --</option>
                        <?php foreach ($teamUsers['site_operators'] ?? [] as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Purchase Executive -->
                <div class="col-xl-4 col-md-6">
                    <label for="team_purchase_user_id">
                        <i class="fa-solid fa-cart-shopping text-success me-1"></i>
                        Purchase Executive
                    </label>
                    <select id="team_purchase_user_id" name="purchase_user_id" class="form-control select2-no-scroll">
                        <option value="">-- Select Purchase Executive --</option>
                        <?php foreach ($teamUsers['purchase_executives'] ?? [] as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 text-center mt-4">
                    <button class="btn btn-primary">Create Project</button>
                </div>

            </div>
        </form>
    </div>
</div>



<script>
    let unitIndex = 1;
    const wrapper = document.getElementById('unit-wrapper');

    // Add new unit
    document.getElementById('add-unit').addEventListener('click', function() {
        const html = `
           <div id="unit-wrapper">
    <div class="row unit-row mb-2 align-items-center">
        <div class="col-md-6 col-12">
            <input type="text" name="units[${unitIndex}][unit_name]" class="form-control" placeholder="Unit Name">
        </div>
        <div class="col-md-5 col-12">
            <input type="number" name="units[${unitIndex}][sqft]" class="form-control" placeholder="Sqft">
        </div>
        <div class="col-md-1 col-12 d-flex justify-content-center">
            <button type="button" class="btn btn-danger remove-unit w-100">×</button>
        </div>
    </div>

    `;
        wrapper.insertAdjacentHTML('beforeend', html);
        unitIndex++;
    });

    // Remove unit (delegate)
    wrapper.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-unit')) {
            e.preventDefault();
            const row = e.target.closest('.unit-row');
            if (row) row.remove();
        }
    });

    // Category → auto-fill incentive %
    var catSel = document.getElementById('projectCategorySelect');
    var incInput = document.getElementById('projectIncentiveInput');
    if (catSel && incInput) {
        catSel.addEventListener('change', function () {
            var opt = this.options[this.selectedIndex];
            incInput.value = opt ? (opt.getAttribute('data-incentive') || '') : '';
        });
    }
</script>
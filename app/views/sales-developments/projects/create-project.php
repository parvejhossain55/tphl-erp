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



                <!-- Location -->
                <div class="col-xl-6">
                    <label>Location *</label>
                    <div class="d-flex gap-2">
                        <select name="location_id" class="form-control" required style="flex:1;">
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
                    <label>Community *</label>
                    <div class="d-flex gap-2">
                        <select name="community_id" class="form-control" required style="flex:1;">
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
</script>
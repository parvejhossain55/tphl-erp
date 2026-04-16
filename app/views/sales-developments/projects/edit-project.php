<?php
$page_title = "Edit Project";
$page = 'Edit Project';
$show_breadcrumb = true;

// Decode floor units JSON
$floorUnits = [];
if (!empty($project['floor_wise_unit_meta'])) {
    $meta = json_decode($project['floor_wise_unit_meta'], true);
    $floorUnits = $meta['typical_floor'] ?? [];
}
?>
<div class="row">
    <div class="card__wrapper">
        <form action="<?= $GLOBALS['base_url'] ?>/update-project/<?= $project['id'] ?>"
            method="POST"
            enctype="multipart/form-data">

            <div class="row gy-20">

                <!-- Project Name -->
                <div class="col-xl-6">
                    <label>Project Name *</label>
                    <input type="text" name="name" class="form-control" required
                        value="<?= htmlspecialchars($project['name'] ?? '') ?>">
                </div>

                <!-- Location -->
                <div class="col-xl-6">
                    <label>Location *</label>
                    <select name="location_id" class="form-control" required>
                        <option value="">Select Location</option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?= $location['id'] ?>"
                                <?= ($project['location_id'] == $location['id']) ? 'selected' : '' ?>>
                                <?= $location['name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Community -->
                <div class="col-xl-6">
                    <label>Community *</label>
                    <select name="community_id" class="form-control" required>
                        <option value="">Select Community</option>
                        <?php foreach ($communities as $community): ?>
                            <option value="<?= $community['id'] ?>"
                                <?= ($project['community_id'] == $community['id']) ? 'selected' : '' ?>>
                                <?= $community['name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Building Type -->
                <div class="col-xl-6">
                    <label>Building Type</label>
                    <input type="text" name="building_type" class="form-control"
                        value="<?= $project['building_type'] ?? '' ?>">
                </div>

                <!-- Total Share -->
                <div class="col-xl-6">
                    <label>Total Share</label>
                    <input type="number" name="total_share" class="form-control"
                        value="<?= $project['total_share'] ?? '' ?>">
                </div>

                <!-- Unit Per Floor -->
                <div class="col-xl-6">
                    <label>Unit Per Floor</label>
                    <input type="number" name="unit_per_floor" class="form-control"
                        value="<?= $project['unit_per_floor'] ?? '' ?>">
                </div>


                <!-- Floor Wise Units -->
                <div class="col-12 mt-3">
                    <label>Floor Wise Units</label>
                    <div id="unit-wrapper">
                        <?php if (!empty($floorUnits)): ?>
                            <?php foreach ($floorUnits as $index => $unit): ?>
                                <div class="row unit-row mb-2 g-2 align-items-center">
                                    <div class="col-md-6 col-12">
                                        <input type="text" name="units[<?= $index ?>][unit_name]" class="form-control"
                                            placeholder="Unit Name" value="<?= htmlspecialchars($unit['unit_name']) ?>">
                                    </div>
                                    <div class="col-md-5 col-12">
                                        <input type="number" name="units[<?= $index ?>][sqft]" class="form-control"
                                            placeholder="Sqft" value="<?= htmlspecialchars($unit['sqft']) ?>">
                                    </div>
                                    <div class="col-md-1 col-12 d-flex justify-content-center">
                                        <button type="button" class="btn btn-danger btn-mg remove-unit w-100">×</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="row unit-row mb-2 g-2 align-items-center">
                                <div class="col-md-5 col-12">
                                    <input type="text" name="units[0][unit_name]" class="form-control"
                                        placeholder="Unit Name">
                                </div>
                                <div class="col-md-6 col-12">
                                    <input type="number" name="units[0][sqft]" class="form-control"
                                        placeholder="Sqft">
                                </div>
                                <div class="col-md-1 col-12 d-flex justify-content-center">
                                    <button type="button" class="btn btn-danger btn-sm remove-unit w-100">×</button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn-sm btn-secondary mt-2" id="add-unit">
                        + Add Unit
                    </button>
                </div>

                <!-- Project Type -->
                <div class="col-xl-6">
                    <label>Project Type</label>
                    <input type="text" name="types" class="form-control"
                        value="<?= $project['types'] ?? '' ?>">
                </div>

                <!-- Price Range -->
                <div class="col-xl-6">
                    <label>Price Range</label>
                    <select name="price_range" class="form-control">
                        <?php
                        $ranges = [
                            "Under 50 Lac BDT",
                            "50 Lac - 1 Crore BDT",
                            "1 Crore - 2 Crore BDT",
                            "2 Crore - 5 Crore BDT",
                            "Above 5 Crore BDT"
                        ];
                        foreach ($ranges as $range):
                        ?>
                            <option value="<?= $range ?>"
                                <?= (($project['price_range'] ?? '') === $range) ? 'selected' : '' ?>>
                                <?= $range ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                </div>

                <!-- Project Wise Tag -->
                <div class="col-xl-4">
                    <label>Project Sale Status</label>
                  <select name="project_sale_status" class="form-control">
                        <?php
                        $tags = ["Sold Out", "On Sale", "Upcoming"];
                        foreach ($tags as $tag):
                        ?>
                              <option value="">Select Sale Status</option>
                            <option value="<?= $tag ?>"
                                <?= (($project['project_sale_status'] ?? '') === $tag) ? 'selected' : '' ?>>
                                <?= $tag ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Project Construction Status -->
                <div class="col-xl-4">
                    <label>Project Construction Status</label>
                    <select name="project_status" class="form-control" id="project_status_edit">
                        <option value="">Select Construction Status</option>
                        <?php
                        $statuses = ['Planning', 'In Progress', 'On Hold', 'Completed', 'Cancelled', 'Delayed'];
                        foreach ($statuses as $status):
                        ?>
                            <option value="<?= $status ?>"
                                <?= (($project['project_status'] ?? '') === $status) ? 'selected' : '' ?>>
                                <?= $status ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Front Road -->
                <div class="col-xl-4">
                    <label>Front Road</label>
                    <input type="text" name="front_road" class="form-control"
                        value="<?= $project['front_road'] ?? '' ?>">
                </div>

                <!-- Apartment Count -->
                <div class="col-xl-4">
                    <label>Apartment Count <span>(Optional)</span></label>
                    <input type="number" name="apartment_count" class="form-control"
                        value="<?= $project['apartment_count'] ?? '' ?>">
                </div>

                <!-- Plot Info -->
                <div class="col-xl-4">
                    <label>Plot Size <span>(Optional)</span></label>
                    <input type="text" name="plot_size" class="form-control"
                        value="<?= $project['plot_size'] ?? '' ?>">
                </div>

                <div class="col-xl-4">
                    <label>Plot No <span>(Optional)</span></label>
                    <input type="text" name="plot_no" class="form-control"
                        value="<?= $project['plot_no'] ?? '' ?>">
                </div>

                <div class="col-xl-4">
                    <label>Block Number <span>(Optional)</span></label>
                    <input type="text" name="block_no" class="form-control"
                        value="<?= $project['block_no'] ?? '' ?>">
                </div>

                <!-- Lift -->
                <div class="col-xl-6">
                    <label>Passenger Lift</label>
                    <input type="text" name="passenger_lift" class="form-control"
                        value="<?= $project['passenger_lift'] ?? '' ?>">
                </div>

                <div class="col-xl-6">
                    <label>Cargo Lift</label>
                    <input type="text" name="cargo_lift" class="form-control"
                        value="<?= $project['cargo_lift'] ?? '' ?>">
                </div>
                <!-- Brochure Link -->
                <div class="col-xl-6">
                    <label>Brochure Link</label>
                    <input type="text" name="brochure_link" class="form-control" value="<?= $project['brochure_link'] ?? '' ?>">
                </div>

                <!-- Location  Image -->
                <div class="col-xl-6">
                    <label>Project Location Map Name <span>(Optional)</span></label>
                    <input type="text" name="project_map_location" class="form-control" value="<?= $project['project_map_location'] ?? '' ?>">
                </div>
                <!-- Map Image -->
                <div class="col-xl-6">
                    <label>Map Image <span>(Optional)</span></label>
                    <input type="file" name="map_image" class="form-control">
                </div>

                <!-- Map Link -->
                <div class="col-xl-6">
                    <label>Map Link</label>
                    <input type="text" name="map_link" class="form-control" value="<?= $project['map_link'] ?? '' ?>">
                </div>
                <div class="col-xl-3 col-12">
                    <label>Amenities Image  <span>(Optional)</span></label>
                    <input type="file" name="amenities_image" class="form-control">
                </div>
                <!-- Project Images -->
                <div class="col-xl-3 col-12">
                    <label>Main Image</label>
                    <input type="file" name="image" class="form-control">
                </div>

                <div class="col-xl-3 col-12">
                    <label>Image 2 <span>(Optional)</span></label>
                    <input type="file" name="image2" class="form-control">
                </div>

                <div class="col-xl-3 col-12">
                    <label>Image 3 <span>(Optional)</span></label>
                    <input type="file" name="image3" class="form-control">
                </div

                    <!-- Facilities -->
                <div class="col-12">
                    <h5>Facilities</h5>
                </div>

                <?php
                $facilities = [
                    'rooftop_gardening' => 'Rooftop Gardening',
                    'car_parking' => 'Car Parking',
                    'convention' => 'Convention Hall',
                    'electricity_backup' => 'Electricity Backup'
                ];
                foreach ($facilities as $key => $label):
                ?>
                    <div class="col-xl-3 col-md-6">
                        <label class="facility-box">
                            <input type="checkbox" name="<?= $key ?>" value="1"
                                <?= !empty($project[$key]) ? 'checked' : '' ?>>
                            <span><?= $label ?></span>
                        </label>
                    </div>
                <?php endforeach; ?>

                <!-- Amenities -->
                <div class="col-12">
                    <label>Amenities Details <span>(Optional)</span></label>
                    <textarea name="amenities_details" id="tinymce_simple_textarea">
<?= htmlspecialchars($project['amenities_details'] ?? '') ?>
            </textarea>
                </div>

                <!-- Description -->
                <div class="col-12">
                    <label>Description <span>(Optional)</span></label>
                    <textarea name="description" id="tinymce_simple_textarea">
<?= htmlspecialchars($project['description'] ?? '') ?>
            </textarea>
                </div>

                <!-- Submit -->
                <div class="col-12 text-center mt-4">
                    <button class="btn btn-primary px-5">Update Project</button>
                </div>

            </div>
        </form>

    </div>
</div>



<script>
    let unitIndex = <?= count($floorUnits) ?>;
    const wrapper = document.getElementById('unit-wrapper');

    // Add new unit
    document.getElementById('add-unit').addEventListener('click', function() {
        const html = `
    <div class="row unit-row mb-2 align-items-center">
        <div class="col-md-6 col-12">
            <input type="text" name="units[${unitIndex}][unit_name]" class="form-control" placeholder="Unit Name">
        </div>
        <div class="col-md-5 col-12">
            <input type="number" name="units[${unitIndex}][sqft]" class="form-control" placeholder="Sqft">
        </div>
       <div class="col-md-1 col-12 d-flex justify-content-center">
             <button type="button" class="btn btn-danger btn-sm remove-unit w-100">×</button>
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
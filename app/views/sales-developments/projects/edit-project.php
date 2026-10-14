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

// Decode gallery images JSON
$galleryImages = [];
if (!empty($project['gallery_images'])) {
    $galleryImages = json_decode($project['gallery_images'], true) ?: [];
}

// Decode nearby locations JSON
$nearbyLocations = [];
if (!empty($project['nearby_locations'])) {
    $nearbyLocations = json_decode($project['nearby_locations'], true) ?: [];
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

                <!-- Category -->
                <div class="col-xl-4">
                    <label>Project Category</label>
                    <select name="category_id" id="projectCategorySelect" class="form-control">
                        <option value="">— Select Category —</option>
                        <?php foreach ($categories ?? [] as $cat): ?>
                        <option value="<?= $cat['id'] ?>"
                                data-incentive="<?= number_format((float)$cat['incentive_percentage'], 2) ?>"
                                <?= ($project['category_id'] ?? null) == $cat['id'] ? 'selected' : '' ?>>
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
                               value="<?= htmlspecialchars($project['incentive_percentage'] ?? '') ?>"
                               placeholder="0.00" readonly style="background:#f8f9fa;">
                        <span class="input-group-text">%</span>
                    </div>
                    <small class="text-muted" style="font-size:.72rem;">Auto from category</small>
                </div>

                <!-- Location -->
                <div class="col-xl-6">
                    <label>Location</label>
                    <select name="location_id" class="form-control">
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
                    <label>Community</label>
                    <select name="community_id" class="form-control">
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

                <!-- Building Face -->
                <div class="col-xl-6">
                    <label>Building Face</label>
                    <input type="text" name="building_face" class="form-control"
                        placeholder="e.g. Infinity sea view"
                        value="<?= htmlspecialchars($project['building_face'] ?? '') ?>">
                </div>

                <!-- Suite Sizes -->
                <div class="col-xl-6">
                    <label>Suite Sizes</label>
                    <input type="text" name="suite_sizes" class="form-control"
                        placeholder="e.g. 670 to 1,700 sq ft"
                        value="<?= htmlspecialchars($project['suite_sizes'] ?? '') ?>">
                </div>

                <!-- Hotel Operation -->
                <div class="col-xl-6">
                    <label>Hotel Operation</label>
                    <input type="text" name="hotel_operation" class="form-control"
                        placeholder="e.g. By The Nature’s dedicated hospitality team"
                        value="<?= htmlspecialchars($project['hotel_operation'] ?? '') ?>">
                </div>

                <!-- Construction Start -->
                <div class="col-xl-6">
                    <label>Construction Start</label>
                    <input type="text" name="construction_start" class="form-control"
                        placeholder="e.g. February 2027 (planned)"
                        value="<?= htmlspecialchars($project['construction_start'] ?? '') ?>">
                </div>

                <!-- Handover -->
                <div class="col-xl-6">
                    <label>Handover</label>
                    <input type="text" name="handover" class="form-control"
                        placeholder="e.g. August 2030 (tentative), plus a 6-month grace period"
                        value="<?= htmlspecialchars($project['handover'] ?? '') ?>">
                </div>

                <!-- Delivery -->
                <div class="col-xl-6">
                    <label>Delivery</label>
                    <input type="text" name="delivery" class="form-control"
                        placeholder="e.g. Fully furnished suites"
                        value="<?= htmlspecialchars($project['delivery'] ?? '') ?>">
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
                    <label class="fw-semibold">Floor Wise Units</label>
                    <div id="unit-wrapper">
                        <?php if (!empty($floorUnits)): ?>
                            <?php foreach ($floorUnits as $index => $unit): ?>
                                <div class="unit-row card p-3 mb-2 border bg-light">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-md-3 col-12">
                                            <label class="small text-muted mb-1">Unit Name</label>
                                            <input type="text" name="units[<?= $index ?>][unit_name]" class="form-control"
                                                placeholder="Unit Name (e.g. Unit A)" value="<?= htmlspecialchars($unit['unit_name'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-2 col-12">
                                            <label class="small text-muted mb-1">Sqft</label>
                                            <input type="number" step="any" name="units[<?= $index ?>][sqft]" class="form-control"
                                                placeholder="Sqft (e.g. 1450)" value="<?= htmlspecialchars($unit['sqft'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-3 col-12">
                                            <label class="small text-muted mb-1">Unit Image</label>
                                            <?php if (!empty($unit['image'])): ?>
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <img src="<?= $GLOBALS['base_url'] ?>/public/uploads/projects/<?= htmlspecialchars($unit['image']) ?>" alt="Unit" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                                                    <span class="small text-muted text-truncate" style="max-width: 150px;"><?= htmlspecialchars($unit['image']) ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <input type="file" name="unit_image_<?= $index ?>" class="form-control form-control-sm" accept="image/*">
                                            <input type="hidden" name="units[<?= $index ?>][existing_image]" value="<?= htmlspecialchars($unit['image'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-3 col-12">
                                            <label class="small text-muted mb-1">Description</label>
                                            <input type="text" name="units[<?= $index ?>][description]" class="form-control"
                                                placeholder="Short description" value="<?= htmlspecialchars($unit['description'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-1 col-12 text-end">
                                            <label class="small text-muted mb-1 d-block">&nbsp;</label>
                                            <button type="button" class="btn btn-outline-danger remove-unit w-100" title="Remove Unit">×</button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="unit-row card p-3 mb-2 border bg-light">
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-3 col-12">
                                        <label class="small text-muted mb-1">Unit Name</label>
                                        <input type="text" name="units[0][unit_name]" class="form-control" placeholder="Unit Name (e.g. Unit A)">
                                    </div>
                                    <div class="col-md-2 col-12">
                                        <label class="small text-muted mb-1">Sqft</label>
                                        <input type="number" step="any" name="units[0][sqft]" class="form-control" placeholder="Sqft (e.g. 1450)">
                                    </div>
                                    <div class="col-md-3 col-12">
                                        <label class="small text-muted mb-1">Unit Image</label>
                                        <input type="file" name="unit_image_0" class="form-control form-control-sm" accept="image/*">
                                    </div>
                                    <div class="col-md-3 col-12">
                                        <label class="small text-muted mb-1">Description</label>
                                        <input type="text" name="units[0][description]" class="form-control" placeholder="Short description">
                                    </div>
                                    <div class="col-md-1 col-12 text-end">
                                        <label class="small text-muted mb-1 d-block">&nbsp;</label>
                                        <button type="button" class="btn btn-outline-danger remove-unit w-100" title="Remove Unit">×</button>
                                    </div>
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


                <!-- Service Charge Per EMI -->
                <div class="col-xl-6">
                    <label>Service Charge <span class="text-muted">(per EMI %)</span></label>
                    <input type="number" step="0.01" min="0" name="service_charge_per_emi" class="form-control"
                        value="<?= $project['service_charge_per_emi'] ?? '' ?>" placeholder="e.g. 1.5">
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
                    <label>Hover Text <span>(Optional)</span></label>
                    <input type="text" name="hover_text" class="form-control"
                        value="<?= $project['hover_text'] ?? '' ?>">
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
                    <?php if (!empty($project['map_image'])):
                        $prevMapImg = getProjectImage($project['map_image']); ?>
                        <div class="mt-2 d-flex align-items-center gap-2">
                            <?php if (!$prevMapImg['missing']): ?>
                                <a href="<?= $prevMapImg['url'] ?>" target="_blank">
                                    <img src="<?= $prevMapImg['url'] ?>" alt="Map Image" style="max-height: 80px; border-radius: 4px; border: 1px solid #ddd; padding: 2px;">
                                </a>
                            <?php else: ?>
                                <img src="<?= $prevMapImg['url'] ?>" alt="Map Placeholder" style="max-height: 80px; border-radius: 4px; border: 1px solid #ddd; padding: 2px; opacity: 0.4;">
                                <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>File Missing &ndash; Re-upload</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Map Link -->
                <div class="col-xl-6">
                    <label>Map Link</label>
                    <input type="text" name="map_link" class="form-control" value="<?= $project['map_link'] ?? '' ?>">
                </div>

                <!-- Nearby Locations -->
                <div class="col-12 mt-3">
                    <label class="fw-semibold">Nearby Locations (Landmarks with Map Indications & Distance)</label>
                    <div id="nearby-wrapper">
                        <?php if (!empty($nearbyLocations)): ?>
                            <?php foreach ($nearbyLocations as $nIndex => $nItem): ?>
                                <div class="nearby-row card p-3 mb-2 border bg-light">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-md-4 col-12">
                                            <label class="small text-muted mb-1">Location / Landmark Name</label>
                                            <input type="text" name="nearby[<?= $nIndex ?>][name]" class="form-control"
                                                placeholder="e.g. Jamuna Future Park, Metro Station"
                                                value="<?= htmlspecialchars($nItem['name'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-3 col-12">
                                            <label class="small text-muted mb-1">Distance / Time</label>
                                            <input type="text" name="nearby[<?= $nIndex ?>][distance]" class="form-control"
                                                placeholder="e.g. 500m / 5 mins walk"
                                                value="<?= htmlspecialchars($nItem['distance'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-4 col-12">
                                            <label class="small text-muted mb-1">Map / Location Indication Image</label>
                                            <?php if (!empty($nItem['image'])): ?>
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <img src="<?= $GLOBALS['base_url'] ?>/public/uploads/projects/<?= htmlspecialchars($nItem['image']) ?>" alt="Nearby" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                                                    <span class="small text-muted text-truncate" style="max-width: 150px;"><?= htmlspecialchars($nItem['image']) ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <input type="file" name="nearby_image_<?= $nIndex ?>" class="form-control form-control-sm" accept="image/*">
                                            <input type="hidden" name="nearby[<?= $nIndex ?>][existing_image]" value="<?= htmlspecialchars($nItem['image'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-1 col-12 text-end">
                                            <label class="small text-muted mb-1 d-block">&nbsp;</label>
                                            <button type="button" class="btn btn-outline-danger remove-nearby w-100" title="Remove">×</button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="nearby-row card p-3 mb-2 border bg-light">
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-4 col-12">
                                        <label class="small text-muted mb-1">Location / Landmark Name</label>
                                        <input type="text" name="nearby[0][name]" class="form-control" placeholder="e.g. Jamuna Future Park, Metro Station">
                                    </div>
                                    <div class="col-md-3 col-12">
                                        <label class="small text-muted mb-1">Distance / Time</label>
                                        <input type="text" name="nearby[0][distance]" class="form-control" placeholder="e.g. 500m / 5 mins walk">
                                    </div>
                                    <div class="col-md-4 col-12">
                                        <label class="small text-muted mb-1">Map / Location Indication Image</label>
                                        <input type="file" name="nearby_image_0" class="form-control" accept="image/*">
                                    </div>
                                    <div class="col-md-1 col-12 text-end">
                                        <label class="small text-muted mb-1 d-block">&nbsp;</label>
                                        <button type="button" class="btn btn-outline-danger remove-nearby w-100" title="Remove">×</button>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn-sm btn-secondary mt-1" id="add-nearby">
                        + Add Nearby Location
                    </button>
                </div>                <div class="col-xl-3 col-12">
                    <label>Amenities Image  <span>(Optional)</span></label>
                    <input type="file" name="amenities_image" class="form-control">
                    <?php if (!empty($project['amenities_image'])):
                        $prevAmImg = getProjectImage($project['amenities_image']); ?>
                        <div class="mt-2 d-flex align-items-center gap-2">
                            <?php if (!$prevAmImg['missing']): ?>
                                <a href="<?= $prevAmImg['url'] ?>" target="_blank">
                                    <img src="<?= $prevAmImg['url'] ?>" alt="Amenities Image" style="max-height: 80px; border-radius: 4px; border: 1px solid #ddd; padding: 2px;">
                                </a>
                            <?php else: ?>
                                <img src="<?= $prevAmImg['url'] ?>" alt="Amenities Placeholder" style="max-height: 80px; border-radius: 4px; border: 1px solid #ddd; padding: 2px; opacity: 0.4;">
                                <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>File Missing &ndash; Re-upload</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <!-- Project Images -->
                <div class="col-xl-3 col-12">
                    <label>Main Image</label>
                    <input type="file" name="image" class="form-control">
                    <?php if (!empty($project['image'])):
                        $prevImg1 = getProjectImage($project['image']); ?>
                        <div class="mt-2 d-flex align-items-center gap-2">
                            <?php if (!$prevImg1['missing']): ?>
                                <a href="<?= $prevImg1['url'] ?>" target="_blank">
                                    <img src="<?= $prevImg1['url'] ?>" alt="Main Image" style="max-height: 80px; border-radius: 4px; border: 1px solid #ddd; padding: 2px;">
                                </a>
                            <?php else: ?>
                                <img src="<?= $prevImg1['url'] ?>" alt="Placeholder" style="max-height: 80px; border-radius: 4px; border: 1px solid #ddd; padding: 2px; opacity: 0.4;">
                                <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>File Missing &ndash; Re-upload</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-xl-3 col-12">
                    <label>Image 2 <span>(Optional)</span></label>
                    <input type="file" name="image2" class="form-control">
                    <?php if (!empty($project['image2'])):
                        $prevImg2 = getProjectImage($project['image2']); ?>
                        <div class="mt-2 d-flex align-items-center gap-2">
                            <?php if (!$prevImg2['missing']): ?>
                                <a href="<?= $prevImg2['url'] ?>" target="_blank">
                                    <img src="<?= $prevImg2['url'] ?>" alt="Image 2" style="max-height: 80px; border-radius: 4px; border: 1px solid #ddd; padding: 2px;">
                                </a>
                            <?php else: ?>
                                <img src="<?= $prevImg2['url'] ?>" alt="Placeholder" style="max-height: 80px; border-radius: 4px; border: 1px solid #ddd; padding: 2px; opacity: 0.4;">
                                <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>File Missing &ndash; Re-upload</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-xl-3 col-12">
                    <label>Image 3 <span>(Optional)</span></label>
                    <input type="file" name="image3" class="form-control">
                    <?php if (!empty($project['image3'])):
                        $prevImg3 = getProjectImage($project['image3']); ?>
                        <div class="mt-2 d-flex align-items-center gap-2">
                            <?php if (!$prevImg3['missing']): ?>
                                <a href="<?= $prevImg3['url'] ?>" target="_blank">
                                    <img src="<?= $prevImg3['url'] ?>" alt="Image 3" style="max-height: 80px; border-radius: 4px; border: 1px solid #ddd; padding: 2px;">
                                </a>
                            <?php else: ?>
                                <img src="<?= $prevImg3['url'] ?>" alt="Placeholder" style="max-height: 80px; border-radius: 4px; border: 1px solid #ddd; padding: 2px; opacity: 0.4;">
                                <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>File Missing &ndash; Re-upload</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Project Gallery Images -->
                <div class="col-12 mt-3">
                    <label class="fw-semibold">Project Gallery (Images with Titles)</label>
                    <div id="gallery-wrapper">
                        <?php if (!empty($galleryImages)): ?>
                            <?php foreach ($galleryImages as $gIndex => $gItem): ?>
                                <div class="gallery-row card p-3 mb-2 border bg-light">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-md-5 col-12">
                                            <label class="small text-muted mb-1">Image Title / Name</label>
                                            <input type="text" name="gallery[<?= $gIndex ?>][title]" class="form-control"
                                                placeholder="e.g. Master Bedroom, Rooftop View"
                                                value="<?= htmlspecialchars($gItem['title'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-6 col-12">
                                            <label class="small text-muted mb-1">Gallery Image</label>
                                            <?php if (!empty($gItem['image'])): ?>
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <img src="<?= $GLOBALS['base_url'] ?>/public/uploads/projects/<?= htmlspecialchars($gItem['image']) ?>" alt="Gallery" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                                                    <span class="small text-muted text-truncate" style="max-width: 180px;"><?= htmlspecialchars($gItem['image']) ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <input type="file" name="gallery_image_<?= $gIndex ?>" class="form-control form-control-sm" accept="image/*">
                                            <input type="hidden" name="gallery[<?= $gIndex ?>][existing_image]" value="<?= htmlspecialchars($gItem['image'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-1 col-12 text-end">
                                            <label class="small text-muted mb-1 d-block">&nbsp;</label>
                                            <button type="button" class="btn btn-outline-danger remove-gallery w-100" title="Remove">×</button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="gallery-row card p-3 mb-2 border bg-light">
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-5 col-12">
                                        <label class="small text-muted mb-1">Image Title / Name</label>
                                        <input type="text" name="gallery[0][title]" class="form-control" placeholder="e.g. Master Bedroom, Rooftop View">
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <label class="small text-muted mb-1">Gallery Image</label>
                                        <input type="file" name="gallery_image_0" class="form-control" accept="image/*">
                                    </div>
                                    <div class="col-md-1 col-12 text-end">
                                        <label class="small text-muted mb-1 d-block">&nbsp;</label>
                                        <button type="button" class="btn btn-outline-danger remove-gallery w-100" title="Remove">×</button>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn-sm btn-secondary mt-1" id="add-gallery">
                        + Add Gallery Image
                    </button>
                </div>

                <!-- Common Project -->
                <div class="col-xl-3 col-12">
                    <label class="d-block">&nbsp;</label>
                    <div class="form-check mt-2">
                        <input type="checkbox" class="form-check-input" name="is_common" id="is_common" value="1" <?= (!empty($project['is_common'])) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_common">Is Common Project</label>
                    </div>
                </div>

                    <!-- Facilities -->
                <div class="col-12">
                    <h5>Facilities</h5>
                </div>

                <div class="col-xl-3 col-md-6">
                    <label class="facility-box">
                        <input type="checkbox" name="rooftop_gardening" value="1" <?= !empty($project['rooftop_gardening']) ? 'checked' : '' ?>>
                        <i class="fa-solid fa-seedling text-success"></i>
                        <span class="facility-label">Rooftop Gardening</span>
                    </label>
                </div>

                <div class="col-xl-3 col-md-6">
                    <label class="facility-box">
                        <input type="checkbox" name="car_parking" value="1" <?= !empty($project['car_parking']) ? 'checked' : '' ?>>
                        <i class="fa-solid fa-person-car text-primary"></i>
                        <span class="facility-label">Car Parking</span>
                    </label>
                </div>

                <div class="col-xl-3 col-md-6">
                    <label class="facility-box">
                        <input type="checkbox" name="convention" value="1" <?= !empty($project['convention']) ? 'checked' : '' ?>>
                        <i class="fa-solid fa-building-columns text-warning"></i>
                        <span class="facility-label">Convention Hall</span>
                    </label>
                </div>

                <div class="col-xl-3 col-md-6">
                    <label class="facility-box">
                        <input type="checkbox" name="electricity_backup" value="1" <?= !empty($project['electricity_backup']) ? 'checked' : '' ?>>
                        <i class="fa-solid fa-bolt text-danger"></i>
                        <span class="facility-label">Electricity Backup</span>
                    </label>
                </div>

                <div class="col-xl-3 col-md-6">
                    <label class="facility-box">
                        <input type="checkbox" name="under_construction" value="1" <?= !empty($project['under_construction']) ? 'checked' : '' ?>>
                        <i class="fa-solid fa-person-digging text-info"></i>
                        <span class="facility-label">Under Construction</span>
                    </label>
                </div>

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
                    <label for="edit_pm_id">Project Manager (PM)</label>
                    <select id="edit_pm_id" name="pm_id" class="form-control select2-no-scroll">
                        <option value="">-- Select PM --</option>
                        <?php foreach ($teamUsers['project_managers'] ?? [] as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($project['pm_id'] == $u['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Site Engineer -->
                <div class="col-xl-4 col-md-6">
                    <label for="edit_site_engineer_id">Site Engineer</label>
                    <select id="edit_site_engineer_id" name="site_engineer_id" class="form-control select2-no-scroll">
                        <option value="">-- Select Site Engineer --</option>
                        <?php foreach ($teamUsers['site_engineers'] ?? [] as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($project['site_engineer_id'] == $u['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Land Manager -->
                <div class="col-xl-4 col-md-6">
                    <label for="edit_landman_id">Land Manager</label>
                    <select id="edit_landman_id" name="landman_id" class="form-control select2-no-scroll">
                        <option value="">-- Select Land Manager --</option>
                        <?php foreach ($teamUsers['land_managers'] ?? [] as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($project['landman_id'] == $u['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Structural Engineer -->
                <div class="col-xl-4 col-md-6">
                    <label for="edit_struct_engineer_id">Structural Engineer</label>
                    <select id="edit_struct_engineer_id" name="struct_engineer_id" class="form-control select2-no-scroll">
                        <option value="">-- Select Structural Engineer --</option>
                        <?php foreach ($teamUsers['struct_engineers'] ?? [] as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($project['struct_engineer_id'] == $u['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

          

                <!-- Draftsman -->
                <div class="col-xl-4 col-md-6">
                    <label for="edit_draftsman_id">Draftsman</label>
                    <select id="edit_draftsman_id" name="draftsman_id" class="form-control select2-no-scroll">
                        <option value="">-- Select Draftsman --</option>
                        <?php foreach ($teamUsers['draftsmen'] ?? [] as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($project['draftsman_id'] == $u['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Architect -->
                <div class="col-xl-4 col-md-6">
                    <label for="edit_architect_id">Architect</label>
                    <select id="edit_architect_id" name="architect_id" class="form-control select2-no-scroll">
                        <option value="">-- Select Architect --</option>
                        <?php foreach ($teamUsers['architects'] ?? [] as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($project['architect_id'] == $u['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Estimator -->
                <div class="col-xl-4 col-md-6">
                    <label for="edit_estimator_id">Estimator</label>
                    <select id="edit_estimator_id" name="estimator_id" class="form-control select2-no-scroll">
                        <option value="">-- Select Estimator --</option>
                        <?php foreach ($teamUsers['estimators'] ?? [] as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($project['estimator_id'] == $u['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Quality Controller -->
                <div class="col-xl-4 col-md-6">
                    <label for="edit_qc_id">Quality Controller (QC)</label>
                    <select id="edit_qc_id" name="qc_id" class="form-control select2-no-scroll">
                        <option value="">-- Select QC --</option>
                        <?php foreach ($teamUsers['qc_controllers'] ?? [] as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($project['qc_id'] == $u['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Site Operator -->
                <div class="col-xl-4 col-md-6">
                    <label for="edit_operator_id">Site Operator</label>
                    <select id="edit_operator_id" name="operator_id" class="form-control select2-no-scroll">
                        <option value="">-- Select Site Operator --</option>
                        <?php foreach ($teamUsers['site_operators'] ?? [] as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($project['operator_id'] == $u['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Purchase Executive -->
                <div class="col-xl-4 col-md-6">
                    <label for="edit_purchase_user_id">
                        <i class="fa-solid fa-cart-shopping text-success me-1"></i>
                        Purchase Executive
                    </label>
                    <select id="edit_purchase_user_id" name="purchase_user_id" class="form-control select2-no-scroll">
                        <option value="">-- Select Purchase Executive --</option>
                        <?php foreach ($teamUsers['purchase_executives'] ?? [] as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($project['purchase_user_id'] == $u['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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
        <div class="unit-row card p-3 mb-2 border bg-light">
            <div class="row g-2 align-items-center">
                <div class="col-md-3 col-12">
                    <label class="small text-muted mb-1">Unit Name</label>
                    <input type="text" name="units[${unitIndex}][unit_name]" class="form-control" placeholder="Unit Name (e.g. Unit A)">
                </div>
                <div class="col-md-2 col-12">
                    <label class="small text-muted mb-1">Sqft</label>
                    <input type="number" step="any" name="units[${unitIndex}][sqft]" class="form-control" placeholder="Sqft (e.g. 1450)">
                </div>
                <div class="col-md-3 col-12">
                    <label class="small text-muted mb-1">Unit Image</label>
                    <input type="file" name="unit_image_${unitIndex}" class="form-control form-control-sm" accept="image/*">
                </div>
                <div class="col-md-3 col-12">
                    <label class="small text-muted mb-1">Description</label>
                    <input type="text" name="units[${unitIndex}][description]" class="form-control" placeholder="Short description">
                </div>
                <div class="col-md-1 col-12 text-end">
                    <label class="small text-muted mb-1 d-block">&nbsp;</label>
                    <button type="button" class="btn btn-outline-danger remove-unit w-100" title="Remove Unit">×</button>
                </div>
            </div>
        </div>
        `;
        wrapper.insertAdjacentHTML('beforeend', html);
        unitIndex++;
    });

    // Remove unit (delegate)
    wrapper.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-unit') || e.target.closest('.remove-unit')) {
            e.preventDefault();
            const row = e.target.closest('.unit-row');
            if (row) row.remove();
        }
    });

    // Gallery Dynamic Addition
    let galleryIndex = <?= !empty($galleryImages) ? count($galleryImages) : 1 ?>;
    const galleryWrapper = document.getElementById('gallery-wrapper');

    document.getElementById('add-gallery')?.addEventListener('click', function() {
        const html = `
        <div class="gallery-row card p-3 mb-2 border bg-light">
            <div class="row g-2 align-items-center">
                <div class="col-md-5 col-12">
                    <label class="small text-muted mb-1">Image Title / Name</label>
                    <input type="text" name="gallery[${galleryIndex}][title]" class="form-control" placeholder="e.g. Master Bedroom, Rooftop View">
                </div>
                <div class="col-md-6 col-12">
                    <label class="small text-muted mb-1">Gallery Image</label>
                    <input type="file" name="gallery_image_${galleryIndex}" class="form-control form-control-sm" accept="image/*">
                </div>
                <div class="col-md-1 col-12 text-end">
                    <label class="small text-muted mb-1 d-block">&nbsp;</label>
                    <button type="button" class="btn btn-outline-danger remove-gallery w-100" title="Remove">×</button>
                </div>
            </div>
        </div>
        `;
        galleryWrapper.insertAdjacentHTML('beforeend', html);
        galleryIndex++;
    });

    galleryWrapper?.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-gallery') || e.target.closest('.remove-gallery')) {
            e.preventDefault();
            const row = e.target.closest('.gallery-row');
            if (row) row.remove();
        }
    });

    // Nearby Locations Dynamic Addition
    let nearbyIndex = <?= !empty($nearbyLocations) ? count($nearbyLocations) : 1 ?>;
    const nearbyWrapper = document.getElementById('nearby-wrapper');

    document.getElementById('add-nearby')?.addEventListener('click', function() {
        const html = `
        <div class="nearby-row card p-3 mb-2 border bg-light">
            <div class="row g-2 align-items-center">
                <div class="col-md-4 col-12">
                    <label class="small text-muted mb-1">Location / Landmark Name</label>
                    <input type="text" name="nearby[${nearbyIndex}][name]" class="form-control" placeholder="e.g. Hospital, School, Mall">
                </div>
                <div class="col-md-3 col-12">
                    <label class="small text-muted mb-1">Distance / Time</label>
                    <input type="text" name="nearby[${nearbyIndex}][distance]" class="form-control" placeholder="e.g. 5 mins drive">
                </div>
                <div class="col-md-4 col-12">
                    <label class="small text-muted mb-1">Map / Location Indication Image</label>
                    <input type="file" name="nearby_image_${nearbyIndex}" class="form-control form-control-sm" accept="image/*">
                </div>
                <div class="col-md-1 col-12 text-end">
                    <label class="small text-muted mb-1 d-block">&nbsp;</label>
                    <button type="button" class="btn btn-outline-danger remove-nearby w-100" title="Remove">×</button>
                </div>
            </div>
        </div>
        `;
        nearbyWrapper.insertAdjacentHTML('beforeend', html);
        nearbyIndex++;
    });

    nearbyWrapper?.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-nearby') || e.target.closest('.remove-nearby')) {
            e.preventDefault();
            const row = e.target.closest('.nearby-row');
            if (row) row.remove();
        }
    });

    // Live Image Preview Logic
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const objectUrl = URL.createObjectURL(file);
                // Find or create the image preview container
                let previewContainer = input.nextElementSibling;
                if (!previewContainer || !previewContainer.classList.contains('mt-2')) {
                    previewContainer = document.createElement('div');
                    previewContainer.className = 'mt-2';
                    previewContainer.innerHTML = `<a href="${objectUrl}" target="_blank">
                        <img src="${objectUrl}" style="max-height: 80px; border-radius: 4px; border: 1px solid #ddd; padding: 2px;">
                    </a>`;
                    input.parentNode.insertBefore(previewContainer, input.nextSibling);
                } else {
                    const img = previewContainer.querySelector('img');
                    const link = previewContainer.querySelector('a');
                    if (img) img.src = objectUrl;
                    if (link) link.href = objectUrl;
                }
            }
        });
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
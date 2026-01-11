<?php
$page_title = "Project Details";
$page = 'View Project';
$show_breadcrumb = true;

// Assuming $project variable contains the project data from database
?>

<div class="row">
    <div class="card__wrapper">
        <div class="project-details">
            
            <!-- Project Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="mb-0"><?= $project['name'] ?? 'Project Name' ?></h3>
                        <div class="d-flex gap-2">
                            <a href="<?= $GLOBALS['base_url'] ?>/edit-project/<?= $project['id'] ?>" 
                               class="btn btn-primary">
                                <i class="fa-solid fa-edit"></i> Edit
                            </a>
                            <a href="<?= $GLOBALS['base_url'] ?>/project-lists" 
                               class="btn btn-secondary">
                                <i class="fa-solid fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Project Images -->
            <?php if (!empty($project['image']) || !empty($project['image2']) || !empty($project['image3'])): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <h5 class="mb-3">Project Images</h5>
                </div>
                <?php if (!empty($project['image'])): ?>
                <div class="col-md-4 mb-3">
                    <div class="project-image">
                        <img src="<?= getImage('projects',$project['image']) ?>" 
                             alt="Main Image" 
                             class="img-fluid rounded" 
                             style="width: 100%; height: 250px; object-fit: cover;">
                        <p class="text-center mt-2 mb-0"><small class="text-muted">Main Image</small></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($project['image2'])): ?>
                <div class="col-md-4 mb-3">
                    <div class="project-image">
                        <img src="<?= getImage('projects',$project['image2']) ?>" 
                             alt="Image 2" 
                             class="img-fluid rounded" 
                             style="width: 100%; height: 250px; object-fit: cover;">
                        <p class="text-center mt-2 mb-0"><small class="text-muted">Image 2</small></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($project['image3'])): ?>
                <div class="col-md-4 mb-3">
                    <div class="project-image">
                        <img src="<?= getImage('projects',$project['image']) ?>" 
                             alt="Image 3" 
                             class="img-fluid rounded" 
                             style="width: 100%; height: 250px; object-fit: cover;">
                        <p class="text-center mt-2 mb-0"><small class="text-muted">Image 3</small></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Basic Information -->
            <div class="row mb-4">
                <div class="col-12">
                    <h5 class="mb-3">Basic Information</h5>
                </div>
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="info-box p-3 border rounded">
                        <label class="text-muted mb-1"><small>Location</small></label>
                        <p class="mb-0 fw-bold"><?= $project['location_name'] ?? 'N/A' ?></p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="info-box p-3 border rounded">
                        <label class="text-muted mb-1"><small>Community</small></label>
                        <p class="mb-0 fw-bold"><?= $project['community_name'] ?? 'N/A' ?></p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="info-box p-3 border rounded">
                        <label class="text-muted mb-1"><small>Building Type</small></label>
                        <p class="mb-0 fw-bold"><?= $project['building_type'] ?? 'N/A' ?></p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="info-box p-3 border rounded">
                        <label class="text-muted mb-1"><small>Project Type</small></label>
                        <p class="mb-0 fw-bold"><?= $project['types'] ?? 'N/A' ?></p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="info-box p-3 border rounded">
                        <label class="text-muted mb-1"><small>Price Range</small></label>
                        <p class="mb-0 fw-bold"><?= $project['price_range'] ?? 'N/A' ?></p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="info-box p-3 border rounded">
                        <label class="text-muted mb-1"><small>Apartment Count</small></label>
                        <p class="mb-0 fw-bold"><?= $project['apartment_count'] ?? 'N/A' ?></p>
                    </div>
                </div>
            </div>

            <!-- Property Details -->
            <div class="row mb-4">
                <div class="col-12">
                    <h5 class="mb-3">Property Details</h5>
                </div>
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="info-box p-3 border rounded">
                        <label class="text-muted mb-1"><small>Total Share</small></label>
                        <p class="mb-0 fw-bold"><?= $project['total_share'] ?? 'N/A' ?></p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="info-box p-3 border rounded">
                        <label class="text-muted mb-1"><small>Unit Per Floor</small></label>
                        <p class="mb-0 fw-bold"><?= $project['unit_per_floor'] ?? 'N/A' ?></p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="info-box p-3 border rounded">
                        <label class="text-muted mb-1"><small>Front Road</small></label>
                        <p class="mb-0 fw-bold"><?= $project['front_road'] ? $project['front_road'] . ' ft' : 'N/A' ?></p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="info-box p-3 border rounded">
                        <label class="text-muted mb-1"><small>Plot Size</small></label>
                        <p class="mb-0 fw-bold"><?= $project['plot_size'] ? $project['plot_size'] . ' Katha' : 'N/A' ?></p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="info-box p-3 border rounded">
                        <label class="text-muted mb-1"><small>Plot No</small></label>
                        <p class="mb-0 fw-bold"><?= $project['plot_no'] ?? 'N/A' ?></p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="info-box p-3 border rounded">
                        <label class="text-muted mb-1"><small>Block Size</small></label>
                        <p class="mb-0 fw-bold"><?= $project['block_size'] ?? 'N/A' ?></p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="info-box p-3 border rounded">
                        <label class="text-muted mb-1"><small>Passenger Lift</small></label>
                        <p class="mb-0 fw-bold"><?= $project['passenger_lift'] ?? 'N/A' ?></p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="info-box p-3 border rounded">
                        <label class="text-muted mb-1"><small>Cargo Lift</small></label>
                        <p class="mb-0 fw-bold"><?= $project['cargo_lift'] ?? 'N/A' ?></p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="info-box p-3 border rounded">
                        <label class="text-muted mb-1"><small>Hover Text</small></label>
                        <p class="mb-0 fw-bold"><?= $project['hover_text'] ?? 'N/A' ?></p>
                    </div>
                </div>
            </div>

            <!-- Facilities -->
            <div class="row mb-4">
                <div class="col-12">
                    <h5 class="mb-3">Facilities</h5>
                </div>
                <div class="col-12">
                    <div class="facilities-grid d-flex flex-wrap gap-3 g-5">
                        <?php if (!empty($project['rooftop_gardening'])): ?>
                        <div class="facility-badge p-3 border rounded">
                            <i class="fa-solid fa-seedling text-success me-2"></i>
                            <span>Rooftop Gardening</span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($project['car_parking'])): ?>
                        <div class="facility-badge p-3 border rounded">
                            <i class="fa-solid fa-car text-primary me-2"></i>
                            <span>Car Parking</span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($project['convention'])): ?>
                        <div class="facility-badge p-3 border rounded">
                            <i class="fa-solid fa-building-columns text-warning me-2"></i>
                            <span>Convention Hall</span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($project['electricity_backup'])): ?>
                        <div class="facility-badge p-3 border rounded">
                            <i class="fa-solid fa-bolt text-danger me-2"></i>
                            <span>Electricity Backup</span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (empty($project['rooftop_gardening']) && empty($project['car_parking']) && 
                                  empty($project['convention']) && empty($project['electricity_backup'])): ?>
                        <p class="text-muted">No facilities available</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <?php if (!empty($project['description'])): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <h5 class="mb-3">Project Description</h5>
                    <div class="description-box p-3 fw-semibold border rounded bg-light">
                        <?= $project['description'] ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Amenities Details -->
            <?php if (!empty($project['amenities_details'])): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <h5 class="mb-3">Amenities Details</h5>
                    <div class="amenities-box p-3 fw-semibold border rounded bg-light">
                        <?= $project['amenities_details'] ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Documents & Media -->
            <div class="row mb-4">
                <div class="col-12">
                    <h5 class="mb-3">Documents & Media</h5>
                </div>
                
                <?php if (!empty($project['brochure_link'])): ?>
                <div class="col-md-4 mb-3">
                    <div class="info-box p-3 border rounded">
                        <label class="text-muted mb-2"><small>Brochure</small></label>
                        <a href="<?= $project['brochure_link'] ?>" 
                           target="_blank" 
                           class="btn btn-outline-primary btn-sm w-100">
                            <i class="fa-solid fa-file-pdf me-1"></i> View Brochure
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($project['map_link'])): ?>
                <div class="col-md-4 mb-3">
                    <div class="info-box p-3 border rounded">
                        <label class="text-muted mb-2"><small>Location Map</small></label>
                        <a href="<?= $project['map_link'] ?>" 
                           target="_blank" 
                           class="btn btn-outline-success btn-sm w-100">
                            <i class="fa-solid fa-map-location-dot me-1"></i> View on Map
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($project['amenities_image'])): ?>
                <div class="col-md-4 mb-3">
                    <div class="info-box p-3 border rounded">
                        <label class="text-muted mb-2"><small>Amenities Image</small></label>
                        <a href="<?= getImage('projects',$project['amenities_image']) ?>" 
                           target="_blank" 
                           class="btn btn-outline-info btn-sm w-100">
                            <i class="fa-solid fa-image me-1"></i> View Image
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($project['map_image'])): ?>
                <div class="col-md-4 mb-3">
                    <div class="info-box p-3 border rounded">
                        <label class="text-muted mb-2"><small>Map Image</small></label>
                        <a href="<?= getImage('projects', $project['map_image']) ?>" 
                           target="_blank" 
                           class="btn btn-outline-warning btn-sm w-100">
                            <i class="fa-solid fa-image me-1"></i> View Map Image
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<style>
.info-box {
    transition: all 0.3s ease;
}

.info-box:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.facility-badge {
    display: inline-flex;
    align-items: center;
    background-color: #f8f9fa;
    transition: all 0.3s ease;
}

.facility-badge:hover {
    background-color: #f4f7fa;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.project-image img {
    transition: transform 0.3s ease;
}

.project-image:hover img {
    transform: scale(1.05);
}

.description-box, .amenities-box {
    line-height: 1.8;
}
</style>
<?php
$page_title = "Flat Details";
$page = ' Flat details';
$show_breadcrumb = true;

// Assuming $flat variable contains the flat data with project information from database
?>

<div class="row">
    <div class="card__wrapper">
        <div class="flat-details">

            <!-- Flat Header -->
            <div class="row m-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1 flat_name"><?= $flat['flat_name'] ?? 'Flat Details' ?></h4>
                            <h5 class="text-muted mb-0 project_name">
                                <i class="fa-solid fa-building me-2"></i>
                                <strong>Project:</strong> <?= $flat['project_name'] ?? 'N/A' ?>
                            </h5>
                        </div>
                        <div class="d-flex gap-2">
                           
                            <a href="<?= $GLOBALS['base_url'] ?>/edit-flat/<?= $flat['id'] ?>"
                                class="btn btn-primary">
                                <i class="fa-solid fa-edit"></i> Edit
                            </a>
                            <a href="<?= $GLOBALS['base_url'] ?>/flat-lists"
                                class="btn btn-secondary">
                                <i class="fa-solid fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Price Highlight -->
            <?php if (!empty($flat['price_value'])): ?>
                <div class="row m-4">
                    <div class="col-12">
                        <div class="price-box p-2 text-center border rounded bg-primary bg-gradient text-white">
                            <div class=" mb-2">
                                 <?php if ($flat['is_booked'] === 1): ?>
                                <button class="btn btn-danger cursor-none">
                                    <span>Sold Out</span>
                                </button>
                            <?php else: ?>
                                <button class="btn btn-primary cursor-none">
                                    <span>Available</span>
                                </button>
                            <?php endif ?>
                            </div>
                            <h3 class="mb-0">
                                <i class="fa-solid fa-bangladeshi-taka-sign me-2 text-light"></i>
                                <span class="text-light"><?= format_bdt($flat['price_value']) ?> </span>
                            </h3>
                            <strong class="text-light">Flat/Unit Price</strong>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Basic Information -->
            <div class="row m-4">
                <div class="col-12">
                    <h5 class="mb-3"><i class="fa-solid fa-info-circle me-2"></i>Basic Information</h5>
                </div>

                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="info-box p-3 border rounded">
                        <label class="text-muted mb-1"><small><i class="fa-solid fa-home me-1"></i>Flat Name</small></label>
                        <p class="mb-0 fw-bold"><?= $flat['flat_name'] ?? 'N/A' ?></p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="info-box p-3 border rounded">
                        <label class="text-muted mb-1"><small><i class="fa-solid fa-building me-1"></i>Project</small></label>
                        <p class="mb-0 fw-bold">
                            <a href="<?= $GLOBALS['base_url'] ?>/show-project/<?= $flat['project_id'] ?>"
                                class="text-decoration-none">
                                <?= $flat['project_name'] ?? 'N/A' ?>
                            </a>
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="info-box p-3 border rounded">
                        <label class="text-muted mb-1"><small><i class="fa-solid fa-layer-group me-1"></i>Floor Number</small></label>
                        <p class="mb-0 fw-bold"><?= $flat['floor_no'] ?? 'N/A' ?></p>
                    </div>
                </div>
            </div>

            <!-- Space Details -->
            <div class="row m-4">
                <div class="col-12">
                    <h5 class="mb-3"><i class="fa-solid fa-ruler-combined me-2"></i>Space & Layout</h5>
                </div>

                <div class="col-md-6 col-lg-3 col-6 mb-3">
                    <div class="info-box p-3 border rounded text-center">
                        <div class="icon-wrapper mb-2">
                            <i class="fa-solid fa-expand fa-2x text-primary"></i>
                        </div>
                        <label class="text-muted mb-1"><small>Total Area</small></label>
                        <p class="mb-0 fw-bold fs-5"><?= $flat['sqft'] ?? 'N/A' ?> sqft</p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 col-6 mb-3">
                    <div class="info-box p-3 border rounded text-center">
                        <div class="icon-wrapper mb-2">
                            <i class="fa-solid fa-bed fa-2x text-success"></i>
                        </div>
                        <label class="text-muted mb-1"><small>Bedrooms</small></label>
                        <p class="mb-0 fw-bold fs-5"><?= $flat['bedroom'] ?? 'N/A' ?></p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 col-6 mb-3">
                    <div class="info-box p-3 border rounded text-center">
                        <div class="icon-wrapper mb-2">
                            <i class="fa-solid fa-bath fa-2x text-info"></i>
                        </div>
                        <label class="text-muted mb-1"><small>Bathrooms</small></label>
                        <p class="mb-0 fw-bold fs-5"><?= $flat['bathroom'] ?? 'N/A' ?></p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 col-6 mb-3">
                    <div class="info-box p-3 border rounded text-center">
                        <div class="icon-wrapper mb-2">
                            <i class="fa-solid fa-door-open fa-2x text-warning"></i>
                        </div>
                        <label class="text-muted mb-1"><small>Balconies</small></label>
                        <p class="mb-0 fw-bold fs-5"><?= $flat['balcony'] ?? 'N/A' ?></p>
                    </div>
                </div>

                <?php if (!empty($flat['bhk'])): ?>
                    <div class="col-md-6 col-lg-6 col-6 mb-3">
                        <div class="info-box p-3 border rounded">
                            <label class="text-muted mb-1"><small><i class="fa-solid fa-home me-1"></i>BHK</small></label>
                            <p class="mb-0 fw-bold"><?= $flat['bhk'] ?> BHK</p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($flat['facing_site'])): ?>
                    <div class="col-md-6 col-lg-6 col-6 mb-3">
                        <div class="info-box p-3 border rounded">
                            <label class="text-muted mb-1"><small><i class="fa-solid fa-compass me-1"></i>Facing Site</small></label>
                            <p class="mb-0 fw-bold"><?= $flat['facing_site'] ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Room Configuration -->
            <?php if (!empty($flat['drawing_dining_kitchen']) || !empty($flat['common_dining'])): ?>
                <div class="row m-4">
                    <div class="col-12">
                        <h5 class="mb-3"><i class="fa-solid fa-couch me-2"></i>Room Configuration</h5>
                    </div>
                    <div class="col-12 ">
                        <div class="configuration-grid d-flex flex-wrap">
                            <?php if (!empty($flat['drawing_dining_kitchen'])): ?>
                                <div class="config-badge p-3 border rounded me-3 mb-3">
                                    <i class="fa-solid fa-utensils text-success me-2"></i>
                                    <span>Drawing/Dining/Kitchen</span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($flat['common_dining'])): ?>
                                <div class="config-badge p-3 border rounded me-3 mb-3">
                                    <i class="fa-solid fa-plate-utensils text-primary me-2"></i>
                                    <span>Common Dining</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Facilities -->
            <?php if (!empty($flat['facilities'])): ?>
                <div class="row m-4">
                    <div class="col-12">
                        <h5 class="mb-3"><i class="fa-solid fa-star me-2"></i>Facilities</h5>
                    </div>
                    <div class="col-12">
                        <div class="facilities-box p-3 border rounded ">
                            <p class="mb-0"><?= nl2br(htmlspecialchars($flat['facilities'])) ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Quick Summary Card -->
            <div class="row">
                <div class="col-12">
                    <div class="summary-card p-4 border rounded ">
                        <h5 class="mb-3"><i class="fa-solid fa-clipboard-list me-2"></i>Quick Summary</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2">
                                        <i class="fa-solid fa-check-circle text-success me-2"></i>
                                        <strong>Area:</strong> <?= $flat['sqft'] ?? 'N/A' ?> sqft
                                    </li>
                                    <li class="mb-2">
                                        <i class="fa-solid fa-check-circle text-success me-2"></i>
                                        <strong>Bedrooms:</strong> <?= $flat['bedroom'] ?? 'N/A' ?>
                                    </li>
                                    <li class="mb-2">
                                        <i class="fa-solid fa-check-circle text-success me-2"></i>
                                        <strong>Bathrooms:</strong> <?= $flat['bathroom'] ?? 'N/A' ?>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2">
                                        <i class="fa-solid fa-check-circle text-success me-2"></i>
                                        <strong>Balconies:</strong> <?= $flat['balcony'] ?? 'N/A' ?>
                                    </li>
                                    <li class="mb-2">
                                        <i class="fa-solid fa-check-circle text-success me-2"></i>
                                        <strong>Floor:</strong> <?= $flat['floor_no'] ?? 'N/A' ?>
                                    </li>
                                    <?php if (!empty($flat['price_value'])): ?>
                                        <li class="mb-2">
                                            <i class="fa-solid fa-check-circle text-success me-2"></i>
                                            <strong>Price:</strong> ৳ <?= format_bdt($flat['price_value']) ?>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    .info-box {
        transition: all 0.3s ease;
        height: 100%;
    }

    .info-box:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        transform: translateY(-3px);
    }

    .price-box {
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }

    /* .config-badge,
    .facility-badge {
        display: inline-flex;
        align-items: center;
        transition: all 0.3s ease;
    } */
    .config-badge {
        min-width: 220px;
    }

    .config-badge:hover,
    .facility-badge:hover {
        background-color: #e9ecef !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }

    .summary-card {
        border-left: 4px solid #0d6efd;
    }

    .icon-wrapper {
        padding: 10px;
        border-radius: 50%;
        background-color: rgba(0, 0, 0, 0.05);
        display: inline-block;
    }

    .facilities-box {
        line-height: 1.8;
    }

    .cursor-none {
        cursor: none !important;
    }

    @media (max-width: 768px) {
        .d-flex.gap-2 {
            flex-direction: column;
        }

        .flat_name{
            font-size:17px;
            margin-bottom:10px;
        }
        .project_name{
            font-size: 15px;
            margin-top:10px;
        }
        .btn {
            padding: 5px;
        }
    }
</style>
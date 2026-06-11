<?php
$page = 'View Amenities';
$addPage = true;
$show_breadcrumb = true;
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card__wrapper">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">Amenities for <?= htmlspecialchars($location_name) ?></h4>
                <div>
                    <a href="<?= $GLOBALS['base_url'] ?>/edit-amenity/<?= $amenities[0]['location_id'] ?>" class="btn btn-sm btn-primary">
                        <i class="fa fa-pen"></i> Edit
                    </a>
                    <a href="<?= $GLOBALS['base_url'] ?>/amenities" class="btn btn-sm btn-secondary">
                        <i class="fa fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>

            <div class="row gy-4">
                <?php if (!empty($amenities)): ?>
                    <?php foreach ($amenities as $amenity): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 shadow-sm border-light">
                                <?php if (!empty($amenity['image'])): ?>
                                    <img src="<?= $GLOBALS['base_url'] ?>/public/uploads/amenities/<?= htmlspecialchars($amenity['image']) ?>" class="card-img-top p-3" alt="<?= htmlspecialchars($amenity['name']) ?>" style="height: 200px; object-fit: contain;">
                                <?php else: ?>
                                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center text-muted" style="height: 200px;">
                                        <i class="fa fa-image fa-3x"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="card-body text-center bg-light">
                                    <h5 class="card-title mb-0"><?= htmlspecialchars($amenity['name']) ?></h5>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <h5 class="text-muted">No amenities found for this location.</h5>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

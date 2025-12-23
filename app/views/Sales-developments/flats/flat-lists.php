<?php
$page = 'Locatiopn';
$addPage = true;
$show_breadcrumb = true;

?>

<div class="row">
    <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-solid fa-building"></i>
                    </span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Flat Available</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title"><?= $availableFlats ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-solid fa-building"></i>
                    </span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Flat Booked</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title"><?= $bookedFlats ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Full width Add button -->
    <div class="col-4 mt-3">
        <div class="card__wrapper">
            <a href="<?= $GLOBALS['base_url'] ?>/create-flat"
                class="btn btn-primary w-100">
                Add Flat
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table mb-20 multiple_tables">
                    <thead>
                        <tr class="table__title">
                            <th><input type="checkbox" id="selectall"></th>
                            <th>Project</th>
                            <th>Unit</th>
                            <th>BHK</th>
                            <th>Bedroom</th>
                            <th>Bathroom</th>
                            <th>Balcony</th>
                            <th>Drawing/Dining/Kitchen</th>
                            <th>Common Dining</th>
                            <th>Floor No</th>
                            <th>Facing Site</th>
                            <th>Sqft</th>
                            <th>Price</th>
                            <th>Facilities</th>
                            <th>Available/Booked </th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php foreach ($flats as $flat): ?>
                            <tr>
                                <td><input type="checkbox" class="selectedId" name="selectedId[]" value="<?= $flat['id'] ?>"></td>
                                <td><?= htmlspecialchars($flat['project_name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($flat['unit_name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($flat['bhk']) ?></td>
                                <td><?= htmlspecialchars($flat['bedroom']) ?></td>
                                <td><?= htmlspecialchars($flat['bathroom']) ?></td>
                                <td><?= htmlspecialchars($flat['balcony']) ?></td>
                                <td><?= $flat['drawing_dining_kitchen'] ? 'Yes' : 'No' ?></td>
                                <td><?= $flat['common_dining'] ? 'Yes' : 'No' ?></td>
                                <td><?= htmlspecialchars($flat['floor_no']) ?></td>
                                <td><?= htmlspecialchars($flat['facing_site']) ?></td>
                                <td><?= htmlspecialchars($flat['sqft']) ?></td>
                                <td><?= htmlspecialchars($flat['price_value']) ?></td>
                                <td><?= htmlspecialchars($flat['facilities']) ?></td>
                                <td>
                                    <?php if ($flat['is_booked'] == 1) : ?>
                                        <span class="bd-badge bg-danger">
                                            Booked
                                        </span>
                                    <?php else: ?>
                                        <span class="bd-badge bg-success">
                                            Available
                                        </span>

                                    <?php endif ?>
                                </td>
                                <td class="table__icon-box">
                                    <div class="d-flex align-items-center justify-content-center gap-10">
                                        <a href="<?= $GLOBALS['base_url'] ?>/edit-flat/<?= $flat['id'] ?>" class="table__icon edit">
                                            <i class="fa-sharp fa-light fa-pen"></i>
                                        </a>
                                        <button class="table__icon delete" data-id="<?= $flat['id'] ?>">
                                            <i class="fa-regular fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<?php
$page = 'Locatiopn';
$addPage = true;
$show_breadcrumb = true;

?>
<div class="breadcrumb__btn my-4">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNewLocation">Add Flat</button>
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
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php foreach($flats as $flat): ?>
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
                            <td class="table__icon-box">
                                <div class="d-flex align-items-center justify-content-center gap-10">
                                    <a href="edit-flat.php?id=<?= $flat['id'] ?>" class="table__icon edit">
                                        <i class="fa-sharp fa-light fa-pen"></i>
                                    </a>
                                    <button type="button" class="table__icon view" data-bs-toggle="modal" data-bs-target="#showFlatModal_<?= $flat['id'] ?>">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
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


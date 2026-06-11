<?php
$page = 'Amenities';
$addPage = true;
$show_breadcrumb = false;
?>
<div class="row mb-4 ">
    <div class="d-flex justify-content-evenly align-items-center mb-4">
        <h4 class="mb-0">Amenities Management</h4>
        <a href="<?= $GLOBALS['base_url'] ?>/create-amenity" class="btn btn-primary">
            Add Amenities
        </a>
    </div>
</div>

<div class="row justify-content-center mt-2">
    <div class="col-xxl-8 col-xl-10 col-lg-12">
        <div class="card__wrapper ">
            <div class="table__wrapper table-responsive">
                <table class="table mb-20 multiple_tables">
                    <thead>
                        <tr class="table__title table__sort">
                            <th>S/L</th>
                            <th>Location Name</th>
                            <th>Total Amenities</th>
                            <th>Created By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php if (!empty($groups)): ?>
                            <?php foreach ($groups as $key => $group): ?>
                                <tr>
                                    <td><?= $key + 1 ?></td>
                                    <td><?= htmlspecialchars($group['location_name'] ?? 'Unknown Location') ?></td>
                                    <td><span class="badge bg-info"><?= htmlspecialchars($group['total_amenities']) ?></span></td>
                                    <td><?= htmlspecialchars($group['created_by_name'] ?? 'Unknown') ?></td>
                                    <td>
                                        <div class="d-flex align-items-center justify-content-start gap-2">
                                            <a href="<?= $GLOBALS['base_url'] ?>/view-amenity/<?= $group['location_id'] ?>" class="table__icon" style="color: var(--bs-info);" title="View">
                                                <i class="fa-regular fa-eye"></i>
                                            </a>

                                            <a href="<?= $GLOBALS['base_url'] ?>/edit-amenity/<?= $group['location_id'] ?>" class="table__icon edit" title="Edit">
                                                <i class="fa-sharp fa-light fa-pen"></i>
                                            </a>

                                            <button class="removeBtnCust table__icon delete"
                                                data-id="<?= $group['location_id'] ?>"
                                                data-name="<?= htmlspecialchars($group['location_name']) ?>"
                                                data-action="delete-amenity">
                                                <i class="fa-regular fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                       
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

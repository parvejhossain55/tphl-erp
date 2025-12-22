

<?php
$page = 'Locatiopn';
$addPage = true;
$show_breadcrumb = true;

?>
<div class="breadcrumb__btn my-4">
    <a href="<?= $GLOBALS['base_url'] ?>/create-project" class="btn btn-primary" >Add location</a>
</div>
<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table mb-20 multiple_tables">
                    <thead>
                        <tr class="table__title">
                            <th><input type="checkbox" id="selectall"></th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Location</th>
                            <th>Community</th>
                            <th>Building Type</th>
                            <th>Total Share</th>
                            <th>Unit Per Floor</th>
                            <th>Type</th>
                            <th>Front Road</th>
                            <th>Plot Size</th>
                            <th>Price Range</th>
                            <th>Brochure</th>
                            <th>Apartment Count</th>
                            <th>Rooftop Gardening</th>
                            <th>Swimming Pool</th>
                            <th>Convention</th>
                            <th>Electricity Backup</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php foreach($projects as $project): ?>
                        <tr>
                            <td><input type="checkbox" class="selectedId" name="selectedId[]" value="<?= $project['id'] ?>"></td>

                            <td>
                                <?php if(!empty($project['image'])): ?>
                                    <img src="<?= getImage('projects',$project['image']) ?>" alt="image" width="50">
                                <?php endif; ?>
                            </td>

                            <td><?= htmlspecialchars($project['name']) ?></td>
                            <td><?= htmlspecialchars($project['location_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($project['community_name'] ?? '') ?> </td>
                            <td><?= htmlspecialchars($project['building_type']) ?></td>
                            <td><?= htmlspecialchars($project['total_share']) ?></td>
                            <td><?= htmlspecialchars($project['unit_per_floor']) ?></td>
                            <td><?= htmlspecialchars($project['types']) ?></td>
                            <td><?= htmlspecialchars($project['front_road']) ?></td>
                            <td><?= $project['plot_size'] ?? 'N/A'?></td>
                            <td><?= htmlspecialchars($project['price_range']) ?></td>
                            <td>
                                <?php if(!empty($project['brochure_link'])): ?>
                                    <a href="<?= $project['brochure_link'] ?>" target="_blank">View</a>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($project['apartment_count']) ?></td>
                            <td><?= $project['rooftop_gardening'] ? 'Yes' : 'No' ?></td>
                            <td><?= $project['swimming_pool'] ? 'Yes' : 'No' ?></td>
                            <td><?= $project['convention'] ? 'Yes' : 'No' ?></td>
                            <td><?= $project['electricity_backup'] ? 'Yes' : 'No' ?></td>

                            <td class="table__icon-box">
                                <div class="d-flex align-items-center justify-content-center gap-10">
                                    <a href="download.php?id=<?= $project['id'] ?>" class="table__icon download">
                                        <i class="fa-regular fa-arrow-down-to-bracket"></i>
                                    </a>
                                    <a href="edit-project.php?id=<?= $project['id'] ?>" class="table__icon edit">
                                        <i class="fa-sharp fa-light fa-pen"></i>
                                    </a>
                                    <button type="button" class="table__icon view" data-bs-toggle="modal" data-bs-target="#showItemsModal_<?= $project['id'] ?>">
                                        <i class="fa-regular fa-eye"></i>
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

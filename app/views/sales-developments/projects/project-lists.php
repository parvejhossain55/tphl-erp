<?php
$page = 'Projects';
$addPage = true;
$show_breadcrumb = true;

?>


<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

    <h4 class="mb-2 mb-md-0">
        Project Management
    </h4>
             <?php if (hasPermission("view lead")): ?>
    <div class="d-none gap-2">
        <a href="<?= $GLOBALS['base_url'] ?>/create-project" class="btn btn-primary"> <i class="fa-light fa-plus me-1"></i> Add Project</a>
    </div>
    <?php else: ?>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importCsvModal">
            Import CSV
        </button>
        <a href="<?= $GLOBALS['base_url'] ?>/create-project" class="btn btn-primary"> <i class="fa-light fa-plus me-1"></i> Add Project</a>
    </div>
    <?php endif; ?>

</div>
<div class="row">

    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table mb-20 multiple_tables">
                    <thead>
                        <tr class="table__title table__sort">
                      <tr class="table__title table__sort">

                            <th class="th_1">Name</th>
                            <th>Location</th>
                            <th>Community</th>
                            <th>Category</th>
                            <th>Building Type</th>
                            <th>Total Share</th>
                            <th>Unit Per Floor</th>
                            <th class="d-none">Type</th>
                            <th>Sale Status</th>
                            <th>Service Charge</th>
                            <th>Brochure</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php foreach ($projects as $project): ?>
                            <tr>
                                <td class="td_1">
                                    <?= htmlspecialchars($project['name']) ?>
                                </td>
                                <td><?= htmlspecialchars($project['location_name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($project['community_name'] ?? '') ?> </td>
                                <td><?= htmlspecialchars($project['category_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($project['building_type'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($project['total_share'] ?? 0) ?></td>
                                <td><?= htmlspecialchars($project['unit_per_floor'] ?? 0) ?></td>
                                <td class="d-none"><?= htmlspecialchars($project['types'] ?? 'N/A') ?></td>

                                <!-- Sale Status -->
                                <td>
                                    <?php
                                    $ss = $project['project_sale_status'] ?? null;
                                    $ssColor = match($ss) {
                                        'On Sale'  => 'success',
                                        'Sold Out' => 'danger',
                                        'Upcoming' => 'warning',
                                        default    => 'secondary'
                                    };
                                    ?>
                                    <?php if ($ss): ?>
                                        <span class="badge bg-<?= $ssColor ?>"><?= $ss ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($project['service_charge_per_emi'])): ?>
                                        <span class="badge bg-info text-dark"><?= $project['service_charge_per_emi'] ?>%</span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($project['brochure_link'])): ?>
                                        <a href="<?= $project['brochure_link'] ?>" target="_blank">Link</a>
                                    <?php endif; ?>
                                </td>


                                <td class="table__icon-box">
                                    <div class="d-flex align-items-center justify-content-center gap-10">

                                        <a class="table__icon download" href="<?= $GLOBALS['base_url'] ?>/project-details/<?= $project['id'] ?>"
                                            title="View Transaction History"> <i class="fa-regular fa-eye"></i>
                                        </a>
                                        <a href="<?= $GLOBALS['base_url'] ?>/edit-project/<?= $project['id'] ?>" class="table__icon edit">
                                            <i class="fa-sharp fa-light fa-pen"></i>
                                        </a>

                                        <button class="table__icon delete removeBtnCust  "
                                            data-id="<?= $project['id'] ?>"
                                            data-name="<?= $project['name'] ?>"
                                            data-action="delete-project">
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
<script>
    const base_url = "<?= $GLOBALS['base_url'] ?>";
</script>

<!-- CSV Import Modal -->
<div class="modal fade" id="importCsvModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Projects from CSV</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>

            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/bulk-projects-import-csv"
                    method="post"
                    enctype="multipart/form-data">

                    <div class="from__input-box mb-20">
                        <div class="form__input-title">
                            <label>Upload CSV File <span>*</span></label>
                        </div>
                        <div class="form__input">
                            <input type="file"
                                name="csv_file"
                                class="form-control"
                                accept=".csv"
                                required>
                        </div>
                    </div>

                    <div class="submit__btn text-center">
                        <button class="btn btn-primary">Import</button>
                    </div>
                </form>

                <div class="mt-15 p-2  rounded text-muted" style="font-size: 12px; max-height: 200px; overflow-y: auto;">
                    <strong>Required CSV Headers:</strong><br>
                    name, slug, location_id, community_id, building_type, total_share, unit_per_floor, floor_wise_unit_meta, types, image, image2, image3, front_road, price_range, hover_text, tag, plot_size, plot_no, block_no, brochure_link, description, apartment_count, amenities_details, amenities_image, map_image, map_link, project_map_location, passenger_lift, cargo_lift, rooftop_gardening, swimming_pool, convention, electricity_backup, car_parking, project_sale_status, service_charge_per_emi, project_status
                    <br><br>
                    <em>Note: Ensure headers exactly match.</em>
                </div>
            </div>
        </div>
    </div>
</div>
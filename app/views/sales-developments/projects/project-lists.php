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
                            <th>Building Type</th>
                            <th>Total Share</th>
                            <th>Unit Per Floor</th>
                            <th class="d-none">Type</th>
                            <th>Sale Status</th>
                            <th>Con. Status</th>
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

                                <!-- Construction Status -->
                                <td>
                                    <?php
                                    $cs = $project['project_status'] ?? null;
                                    $csColor = match($cs) {
                                        'Planning'    => 'info',
                                        'In Progress' => 'primary',
                                        'On Hold'     => 'warning',
                                        'Completed'   => 'success',
                                        'Cancelled'   => 'danger',
                                        'Delayed'     => 'secondary',
                                        default       => 'light'
                                    };
                                    ?>
                                    <?php if ($cs): ?>
                                        <span class="badge bg-<?= $csColor ?>"><?= $cs ?></span>
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
<?php
$page = 'Projects';
$addPage = true;
$show_breadcrumb = true;

?>


<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

    <h4 class="mb-2 mb-md-0">
        Project Management
    </h4>

    <div class="d-flex gap-2">
        <a href="<?= $GLOBALS['base_url'] ?>/create-project" class="btn btn-primary"> <i class="fa-light fa-plus me-1"></i> Add Project</a>
    </div>

</div>
<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table mb-20">
                    <thead>
                        <tr class="table__title">

                            <th class="th_1">Name</th>
                            <th>Location</th>
                            <th>Community</th>
                            <th>Building Type</th>
                            <th>Total Share</th>
                            <th>Unit Per Floor</th>
                            <th class="d-none">Type</th>
                            <th>Front Road</th>
                            <th>Plot Size</th>

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
                                <td><?= htmlspecialchars($project['building_type']) ?></td>
                                <td><?= htmlspecialchars($project['total_share']) ?></td>
                                <td><?= htmlspecialchars($project['unit_per_floor']) ?></td>
                                <td class="d-none"><?= htmlspecialchars($project['types']) ?></td>
                                <td><?= htmlspecialchars($project['front_road']) ?></td>
                                <td><?= $project['plot_size'] ?? 'N/A' ?></td>

                                <td>
                                    <?php if (!empty($project['brochure_link'])): ?>
                                        <a href="<?= $project['brochure_link'] ?>" target="_blank">Link</a>
                                    <?php endif; ?>
                                </td>


                                <td class="table__icon-box">
                                    <div class="d-flex align-items-center justify-content-center gap-10">
                                        <a href=" download.php?id=<?= $project['id'] ?>" class="d-none table__icon download">
                                            <i class="d-none fa-regular fa-arrow-down-to-bracket"></i>
                                        </a>
                                        <a class="table__icon download" href="<?= $GLOBALS['base_url'] ?>/project-details/<?= $project['id'] ?>"
                                            title="View Transaction History"> <i class="fa-regular fa-eye"></i>
                                        </a>
                                        <a href="<?= $GLOBALS['base_url'] ?>/edit-project/<?= $project['id'] ?>" class="table__icon edit">
                                            <i class="fa-sharp fa-light fa-pen"></i>
                                        </a>

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
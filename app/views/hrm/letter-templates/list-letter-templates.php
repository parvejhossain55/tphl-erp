<div class="row">
    <div class="card__wrapper">
        <div class="col-xxl-12 col-xl-12 col-lg-12">

            <!-- Title + Add button -->
            <div class="card__title-wrap mb-20 d-flex align-items-center justify-content-between">
                <h5 class="card__heading-title">Letter Templates</h5>
                <a href="<?= $GLOBALS['base_url'] ?>/create-letter-template" class="btn btn-primary btn-sm">
                    + Add New Template
                </a>
            </div>

            <!-- Table -->
            <div class="card__wrapper">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Designation</th>
                                <th>Letter Type</th>
                                <th>Subject</th>
                                <th>Created At</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($templates)): ?>
                                <?php foreach ($templates as $index => $template): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= $template['designation_name'] ?? 'All Designations' ?></td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?= ucfirst($template['type']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($template['subject']) ?></td>
                                    <td><?= date('d M Y', strtotime($template['created_at'] ?? 'now')) ?></td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <!-- Edit -->
                                            <a href="<?= $GLOBALS['base_url'] ?>/edit-letter-template/<?= $template['id'] ?>"
                                               class="btn btn-info btn-sm"
                                               title="Edit">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <!-- Delete -->
                                            <a href="<?= $GLOBALS['base_url'] ?>/delete-letter-template/<?= $template['id'] ?>"
                                               class="btn btn-danger btn-sm"
                                               title="Delete"
                                               onclick="return confirm('Are you sure you want to delete this template?')">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No letter templates found.</td>
                                </tr>
                            <?php endif ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- End Table -->

        </div>
    </div>
</div>

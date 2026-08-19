<div class="row">
    <div class="card__wrapper">
        <div class="col-xxl-12 col-xl-12 col-lg-12">

            <!-- Title + Add button -->
            <div class="card__title-wrap mb-20 d-flex align-items-center justify-content-between">
                <h5 class="card__heading-title">Letter Templates</h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTemplate"> +
                    Add New Template</button>

            </div>

            <!-- Table -->
            <div class="card__wrapper">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
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
                                        <td><?= $template['name'] ?></td>
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
                                                    class="btn btn-info btn-sm" title="Edit">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <!-- Delete -->
                                                <a href="<?= $GLOBALS['base_url'] ?>/delete-letter-template/<?= $template['id'] ?>"
                                                    class="btn btn-danger btn-sm" title="Delete"
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
<!-- new template modal start -->
<div id="addTemplate" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Template</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>

            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/store-letter-template" method="POST">

                    <div class="row gy-20">

                        <!-- Template Name -->
                        <div class="col-md-6">
                            <label>Template Name *</label>
                            <input class="form-control" name="name" type="text" required>
                        </div>

                        <!-- Type -->
                        <div class="col-md-6">
                            <label>Letter Type *</label>
                            <select name="type" class="form-control" required>
                                <option value="">Select Type</option>
                                <option value="appointment">Appointment</option>
                                <option value="joining">Joining</option>
                                <option value="promotion">Promotion</option>
                                <option value="increment">Increment</option>
                                <option value="experience">Experience</option>
                                <option value="noc">NOC</option>
                                <option value="show_cause">Show Cause</option>
                                <option value="warning">Warning</option>
                                <option value="caution">Caution</option>
                                <option value="improvement">Improvement</option>
                            </select>
                        </div>

                        <!-- Subject -->
                        <div class="col-md-12">
                            <label>Subject *</label>
                            <input class="form-control" name="subject" type="text" required>
                        </div>

                        <!-- Body -->
                        <div class="col-md-12">
                            <label>Template Body *</label>
                            <textarea name="body" id="template_body" class="form-control" rows="8" required></textarea>
                        </div>

                    </div>

                    <div class="submit__btn text-center mt-3">
                        <button class="btn btn-primary">Save Template</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
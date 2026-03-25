

<div class="row">
    <div class="card__wrapper">
        <div class="col-xxl-12 col-xl-12 col-lg-12">

            <!-- Title + Add button -->
            <div class="card__title-wrap mb-20 d-flex align-items-center justify-content-between">
                <h5 class="card__heading-title">Letter Templates</h5>
                <a href="<?= $GLOBALS['base_url'] ?>/create-letter-templete" class="btn btn-primary btn-sm">
                    + Add New Template
                </a>
            </div>

            <!-- Filter bar -->
            <form method="GET" action="" class="card__wrapper mb-20">
                <div class="row g-20 gy-20 align-items-end">

                    <div class="col-lg-4">
                        <div class="from__input-box">
                            <div class="form__input-title">
                                <label for="filter_designation">Designation</label>
                            </div>
                            <div class="form__input">
                                <select class="form-control" name="designation_id" id="filter_designation">
                                    <option value="">— All Designations —</option>
                                    <?php foreach ($designations as $designation): ?>
                                        <option value="<?= $designation['id'] ?>"
                                            <?= (isset($_GET['designation_id']) && $_GET['designation_id'] == $designation['id']) ? 'selected' : '' ?>>
                                            <?= $designation['designation_name'] ?>
                                        </option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="from__input-box">
                            <div class="form__input-title">
                                <label for="filter_type">Letter Type</label>
                            </div>
                            <div class="form__input">
                                <select class="form-control" name="type" id="filter_type">
                                    <option value="">— All Types —</option>
                                    <option value="appointment" <?= (isset($_GET['type']) && $_GET['type'] == 'appointment') ? 'selected' : '' ?>>Appointment</option>
                                    <option value="joining"     <?= (isset($_GET['type']) && $_GET['type'] == 'joining')     ? 'selected' : '' ?>>Joining</option>
                                    <option value="promotion"   <?= (isset($_GET['type']) && $_GET['type'] == 'promotion')   ? 'selected' : '' ?>>Promotion</option>
                                    <option value="increment"   <?= (isset($_GET['type']) && $_GET['type'] == 'increment')   ? 'selected' : '' ?>>Increment</option>
                                    <option value="experience"  <?= (isset($_GET['type']) && $_GET['type'] == 'experience')  ? 'selected' : '' ?>>Experience</option>
                                    <option value="noc"         <?= (isset($_GET['type']) && $_GET['type'] == 'noc')         ? 'selected' : '' ?>>NOC</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="submit__btn">
                            <button class="btn btn-primary" type="submit">Filter</button>
                            <a href="<?= $GLOBALS['base_url'] ?>/letter-templates" class="btn btn-secondary ms-2">Reset</a>
                        </div>
                    </div>

                </div>
            </form>

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
                                <th>Updated At</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($templates)): ?>
                                <?php foreach ($templates as $index => $template): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= $template['designation_name'] ?? '—' ?></td>
                                    <td>
                                        <span class="badge
                                            <?php
                                                $badgeClass = match($template['type']) {
                                                    'appointment' => 'bg-primary',
                                                    'joining'     => 'bg-success',
                                                    'promotion'   => 'bg-info',
                                                    'increment'   => 'bg-warning text-dark',
                                                    'experience'  => 'bg-secondary',
                                                    'noc'         => 'bg-dark',
                                                    default       => 'bg-secondary',
                                                };
                                                echo $badgeClass;
                                            ?>">
                                            <?= ucfirst($template['type']) ?>
                                        </span>
                                    </td>
                                    <td><?= $template['subject'] ?></td>
                                    <td><?= date('d M Y', strtotime($template['created_at'])) ?></td>
                                    <td><?= date('d M Y', strtotime($template['updated_at'])) ?></td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <!-- View -->
                                            <a href="<?= $GLOBALS['base_url'] ?>/letter-templates/<?= $template['id'] ?>/view"
                                               class="btn btn-info btn-sm"
                                               title="View">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <!-- Print -->
                                            <a href="<?= $GLOBALS['base_url'] ?>/letter-templates/<?= $template['id'] ?>/print"
                                               class="btn btn-secondary btn-sm"
                                               target="_blank"
                                               title="Print">
                                                <i class="fa fa-print"></i>
                                            </a>
                                            <!-- Download -->
                                            <a href="<?= $GLOBALS['base_url'] ?>/letter-templates/<?= $template['id'] ?>/download"
                                               class="btn btn-success btn-sm"
                                               title="Download">
                                                <i class="fa fa-download"></i>
                                            </a>
                                            <!-- Delete -->
                                            <a href="<?= $GLOBALS['base_url'] ?>/letter-templates/<?= $template['id'] ?>/delete"
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
                                    <td colspan="7" class="text-center">No letter templates found.</td>
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
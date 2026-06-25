<style>
@media (max-width: 767.98px) {
    /* Stack Title and Add Button */
    .card__title-wrap {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 12px !important;
    }
    
    .card__title-wrap a {
        width: 100% !important;
        text-align: center !important;
    }

    /* Side-by-side filter buttons on mobile */
    .submit__btn {
        display: flex !important;
        gap: 10px !important;
        width: 100% !important;
    }

    .submit__btn button,
    .submit__btn a {
        flex: 1 !important;
        margin: 0 !important;
        text-align: center !important;
        display: inline-flex !important;
        justify-content: center !important;
        align-items: center !important;
        height: 42px !important; /* Force a clean and consistent height */
        font-size: 14px !important;
    }
}
</style>

<!-- DEBUG: Show what's in letters array -->
<?php if (!empty($letters)): ?>
    <!-- DEBUG DATA (remove later) -->
    <div style="display:none;">
        <?php foreach ($letters as $letter): ?>
            <!-- id=<?= isset($letter['id']) ? $letter['id'] : 'NOT SET' ?>, employee_id=<?= $letter['employee_id'] ?>, type=<?= $letter['letter_type'] ?> -->
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="row">
    <div class="card__wrapper">
        <div class="col-xxl-12 col-xl-12 col-lg-12">

            <!-- Title + Add button -->
            <div class="card__title-wrap mb-20 d-flex align-items-center justify-content-between">
                <h5 class="card__heading-title">Issued Employee Letters</h5>
                <a href="<?= $GLOBALS['base_url'] ?>/create-letter-template" class="btn btn-primary btn-sm">
                    + Generate New Letter
                </a>
            </div>

            <!-- Filter bar -->
            <form method="GET" action="" class="card__wrapper mb-20">
                <div class="row g-20 gy-20 align-items-end">

                    <div class="col-lg-4 d-none">
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
                            <a href="<?= $GLOBALS['base_url'] ?>/generate-letter" class="btn btn-secondary ms-2">Reset</a>
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
                                <th>Employee</th>
                                <th>Designation</th>
                                <th>Letter Type</th>
                                <th>Subject</th>
                                <th>Letter Date</th>
                                <th>Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($letters)): ?>
                                <?php foreach ($letters as $index => $letter): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <strong><?= $letter['full_name'] ?></strong><br>
                                        <small class="text-muted"><?= $letter['employee_code'] ?></small>
                                    </td>
                                    <td><?= $letter['designation_name'] ?></td>
                                    <td>
                                        <span class="badge 
                                            <?php
                                                echo match($letter['letter_type']) {
                                                    'appointment' => 'bg-primary',
                                                    'joining'     => 'bg-success',
                                                    'promotion'   => 'bg-info',
                                                    'increment'   => 'bg-warning text-dark',
                                                    'experience'  => 'bg-secondary',
                                                    'noc'         => 'bg-dark',
                                                    default       => 'bg-secondary',
                                                };
                                            ?>">
                                            <?= ucfirst($letter['letter_type']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($letter['subject']) ?></td>
                                    <td><?= date('d M Y', strtotime($letter['letter_date'])) ?></td>
                                    <td>
                                        <span class="badge <?= $letter['status'] == 'issued' ? 'bg-success' : 'bg-warning' ?>">
                                            <?= ucfirst($letter['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <a href="<?= $GLOBALS['base_url'] ?>/view-letter/<?= $letter['id'] ?>" class="btn btn-info btn-sm" title="View">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <a href="<?= $GLOBALS['base_url'] ?>/edit-letter/<?= $letter['id'] ?>" class="btn btn-primary btn-sm" title="Edit">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <a href="<?= $GLOBALS['base_url'] ?>/pdf-letter/<?= $letter['id'] ?>" class="btn btn-danger btn-sm" target="_blank" title="PDF">
                                                <i class="fa fa-file-pdf"></i>
                                            </a>
                                            <a href="<?= $GLOBALS['base_url'] ?>/print-letter/<?= $letter['id'] ?>" class="btn btn-secondary btn-sm" target="_blank" title="Print">
                                                <i class="fa fa-print"></i>
                                            </a>
                                            <a href="<?= $GLOBALS['base_url'] ?>/delete-letter/<?= $letter['id'] ?>" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure?')">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">No issued letters found.</td>
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
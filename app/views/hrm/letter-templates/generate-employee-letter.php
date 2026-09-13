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

<!-- Flash Alerts -->
<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show mb-20" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($_SESSION['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-20" role="alert">
        <i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($_SESSION['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="row">
    <div class="card__wrapper">
        <div class="col-xxl-12 col-xl-12 col-lg-12">

            <!-- Title + Add button -->
            <div class="card__title-wrap mb-20 d-flex align-items-center justify-content-between">
                <h5 class="card__heading-title">Issued Employee Letters</h5>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#officialSignatureModal">
                        <i class="fa-solid fa-file-signature me-1"></i> Official HR Signature
                    </button>
                    <a href="<?= $GLOBALS['base_url'] ?>/create-letter-template" class="btn btn-primary btn-sm">
                        + Generate New Letter
                    </a>
                </div>
            </div>

            <!-- Filter bar -->
            <form method="GET" action="" class="card__wrapper mb-20">
                <div class="row g-15 gy-15 align-items-end">

                    <div class="col-lg-3 col-md-6">
                        <div class="from__input-box">
                            <div class="form__input-title">
                                <label for="filter_search">Search Employee / Subject</label>
                            </div>
                            <div class="form__input">
                                <input type="text" class="form-control" name="search" id="filter_search"
                                    placeholder="Name, ID, Subject, Ref..."
                                    value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="from__input-box">
                            <div class="form__input-title">
                                <label for="filter_designation">Designation</label>
                            </div>
                            <div class="form__input">
                                <select class="form-control select2" name="designation_id" id="filter_designation">
                                    <option value="">— All Designations —</option>
                                    <?php foreach ($designations as $designation): ?>
                                        <option value="<?= $designation['id'] ?>"
                                            <?= (isset($_GET['designation_id']) && $_GET['designation_id'] == $designation['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($designation['designation_name'] . ' (' . ($designation['department_name'] ?? 'N/A') . ')') ?>
                                        </option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="from__input-box">
                            <div class="form__input-title">
                                <label for="filter_type">Letter Type</label>
                            </div>
                            <div class="form__input">
                                <select class="form-control" name="type" id="filter_type">
                                    <option value="">— All Types —</option>
                                    <option value="appointment" <?= ((isset($_GET['type']) && $_GET['type'] == 'appointment') || (isset($_GET['letter_type']) && $_GET['letter_type'] == 'appointment')) ? 'selected' : '' ?>>Appointment</option>
                                    <option value="joining"     <?= ((isset($_GET['type']) && $_GET['type'] == 'joining') || (isset($_GET['letter_type']) && $_GET['letter_type'] == 'joining'))         ? 'selected' : '' ?>>Joining</option>
                                    <option value="promotion"   <?= ((isset($_GET['type']) && $_GET['type'] == 'promotion') || (isset($_GET['letter_type']) && $_GET['letter_type'] == 'promotion'))     ? 'selected' : '' ?>>Promotion</option>
                                    <option value="increment"   <?= ((isset($_GET['type']) && $_GET['type'] == 'increment') || (isset($_GET['letter_type']) && $_GET['letter_type'] == 'increment'))     ? 'selected' : '' ?>>Increment</option>
                                    <option value="experience"  <?= ((isset($_GET['type']) && $_GET['type'] == 'experience') || (isset($_GET['letter_type']) && $_GET['letter_type'] == 'experience'))   ? 'selected' : '' ?>>Experience</option>
                                    <option value="noc"         <?= ((isset($_GET['type']) && $_GET['type'] == 'noc') || (isset($_GET['letter_type']) && $_GET['letter_type'] == 'noc'))                 ? 'selected' : '' ?>>NOC</option>
                                    <option value="salary_certificate" <?= ((isset($_GET['type']) && $_GET['type'] == 'salary_certificate') || (isset($_GET['letter_type']) && $_GET['letter_type'] == 'salary_certificate')) ? 'selected' : '' ?>>Salary Certificate</option>
                                    <option value="show_cause"  <?= ((isset($_GET['type']) && $_GET['type'] == 'show_cause') || (isset($_GET['letter_type']) && $_GET['letter_type'] == 'show_cause'))   ? 'selected' : '' ?>>Show Cause</option>
                                    <option value="warning"     <?= ((isset($_GET['type']) && $_GET['type'] == 'warning') || (isset($_GET['letter_type']) && $_GET['letter_type'] == 'warning'))         ? 'selected' : '' ?>>Warning</option>
                                    <option value="caution"     <?= ((isset($_GET['type']) && $_GET['type'] == 'caution') || (isset($_GET['letter_type']) && $_GET['letter_type'] == 'caution'))         ? 'selected' : '' ?>>Caution</option>
                                    <option value="improvement" <?= ((isset($_GET['type']) && $_GET['type'] == 'improvement') || (isset($_GET['letter_type']) && $_GET['letter_type'] == 'improvement')) ? 'selected' : '' ?>>Improvement</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="submit__btn d-flex gap-2">
                            <button class="btn btn-primary flex-grow-1" type="submit"><i class="fa fa-filter me-1"></i> Filter</button>
                            <a href="<?= $GLOBALS['base_url'] ?>/generate-letter" class="btn btn-secondary flex-grow-1"><i class="fa fa-redo me-1"></i> Reset</a>
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
                                    <td><?= $index + 1 + ((($currentPage ?? 1) - 1) * ($limit ?? 15)) ?></td>
                                    <td>
                                        <strong><?= $letter['full_name'] ?></strong><br>
                                        <small class="text-muted"><?= $letter['employee_code'] ?></small>
                                    </td>
                                    <td><?= $letter['designation_name'] ?></td>
                                    <td>
                                        <span class="badge 
                                            <?php
                                                echo match($letter['letter_type']) {
                                                    'appointment'        => 'bg-primary',
                                                    'joining'            => 'bg-success',
                                                    'promotion'          => 'bg-info',
                                                    'increment'          => 'bg-warning text-dark',
                                                    'experience'         => 'bg-secondary',
                                                    'noc'                => 'bg-dark',
                                                    'caution'            => 'bg-danger text-white',
                                                    'warning'            => 'bg-danger text-white',
                                                    'show_cause'         => 'bg-danger text-white',
                                                    'improvement'        => 'bg-warning text-dark',
                                                    'salary_certificate' => 'text-white',
                                                    default              => 'bg-secondary',
                                                };
                                            ?>"
                                            <?= $letter['letter_type'] === 'salary_certificate' ? 'style="background:#0d7377;"' : '' ?>>
                                            <?= ucwords(str_replace('_', ' ', $letter['letter_type'])) ?>
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
                                            <a href="<?= $GLOBALS['base_url'] ?>/pdf-letter/<?= $letter['id'] ?>?fit=1" class="btn btn-danger btn-sm" target="_blank" title="Download PDF (Fit to 1 Page)">
                                                <i class="fa fa-file-pdf"></i>
                                            </a>
                                            <a href="<?= $GLOBALS['base_url'] ?>/print-letter/<?= $letter['id'] ?>?fit=1" class="btn btn-secondary btn-sm" target="_blank" title="Print Letter (Fit to 1 Page)">
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

                <!-- Pagination Controls -->
                <?php if (!empty($totalPages) && $totalPages > 1): ?>
                    <?php
                    $queryParams = $_GET;
                    unset($queryParams['page']);
                    $queryString = !empty($queryParams) ? '&' . http_build_query($queryParams) : '';
                    $startItem = ((($currentPage ?? 1) - 1) * ($limit ?? 15)) + 1;
                    $endItem = min($totalLetters ?? count($letters), ($currentPage ?? 1) * ($limit ?? 15));
                    ?>
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-20 pt-15 border-top">
                        <div class="text-muted small">
                            Showing <?= $startItem ?> to <?= $endItem ?> of <?= $totalLetters ?? count($letters) ?> letters
                        </div>
                        <nav aria-label="Page navigation">
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item <?= (($currentPage ?? 1) <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= $GLOBALS['base_url'] ?>/generate-letter?page=<?= ($currentPage ?? 1) - 1 ?><?= $queryString ?>">
                                        <i class="fa fa-chevron-left"></i> Prev
                                    </a>
                                </li>
                                <?php
                                $cur = $currentPage ?? 1;
                                $startPage = max(1, $cur - 2);
                                $endPage = min($totalPages, $cur + 2);
                                if ($startPage > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?= $GLOBALS['base_url'] ?>/generate-letter?page=1<?= $queryString ?>">1</a>
                                    </li>
                                    <?php if ($startPage > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                                <?php endif; ?>

                                <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                                    <li class="page-item <?= ($p == $cur) ? 'active' : '' ?>">
                                        <a class="page-link" href="<?= $GLOBALS['base_url'] ?>/generate-letter?page=<?= $p ?><?= $queryString ?>"><?= $p ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($endPage < $totalPages): ?>
                                    <?php if ($endPage < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?= $GLOBALS['base_url'] ?>/generate-letter?page=<?= $totalPages ?><?= $queryString ?>"><?= $totalPages ?></a>
                                    </li>
                                <?php endif; ?>

                                <li class="page-item <?= ($cur >= $totalPages) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= $GLOBALS['base_url'] ?>/generate-letter?page=<?= $cur + 1 ?><?= $queryString ?>">
                                        Next <i class="fa fa-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>

            </div>
            <!-- End Table -->

        </div>
    </div>
</div>

<?php include __DIR__ . '/modal-signatures-manager.php'; ?>
<?php
$addPage = true;
$show_breadcrumb = false;
?>
<h4 class="mb-2">Flat Management</h4>

<div class="card__wrapper mb-3">
    <form action="" method="get">
        <div class="row g-2 align-items-center">

            <!-- Project Filter -->
            <div class="col-xl-3 col-md-4 col-sm-6">
                <select class="form-control form-control-sm" name="project_id">
                    <option value="">All Projects</option>
                    <?php foreach ($projects as $project): ?>
                        <option value="<?= $project['id'] ?>"
                            <?= $selectedProject == $project['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($project['name']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

            <!-- Search -->
            <div class="col-xl-3 col-md-4 col-sm-6">
                <input type="text" class="form-control form-control-sm" name="q" id="flatName"
                    placeholder="Search Flat / Unit / Sqft"
                    value="<?= htmlspecialchars($searchTerm ?? '') ?>">
            </div>

            <!-- Filter + Reset -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-secondary btn-sm flex-fill">
                        <i class="fa-solid fa-filter me-1"></i> Filter
                    </button>
                    <a href="<?= $GLOBALS['base_url'] ?>/flat-lists" class="btn btn-success btn-sm flex-fill">
                        <i class="fa-solid fa-rotate-right me-1"></i> Reset
                    </a>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="col-xl-4 col-md-12">
                <div class="d-flex flex-wrap gap-2 justify-content-xl-end">
                    <a class="btn btn-primary btn-sm" href="<?= $GLOBALS['base_url'] ?>/create-flat">
                        <i class="fa-solid fa-plus me-1"></i> Add Flat/Shop
                    </a>
                    <?php if (!hasPermission("view lead")): ?>
                        <button type="button" class="btn btn-outline-secondary btn-sm"
                            data-bs-toggle="modal" data-bs-target="#importCsvModal">
                            <i class="fa-solid fa-building me-1"></i> Import Flat CSV
                        </button>
                        <button type="button" class="btn btn-warning btn-sm"
                            data-bs-toggle="modal" data-bs-target="#importShopCsvModal">
                            <i class="bi bi-shop me-1"></i> Import Shop CSV
                        </button>
                    <?php endif ?>
                </div>
            </div>

        </div>
    </form>
</div>


<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table mb-20 multiple_tables">
                    <thead>
                        <tr class="table__title">
                            <th>Project</th>
                            <th>Flat/Unit</th>
                            <th>Type</th>
                            <th>Flat Uploaded From</th>
                            <th>Facing Site</th>
                            <th>Sqft</th>
                            <th>Land Share Value</th>
                            <th>Dev. Charges</th>
                            <th>Price</th>
                            <th>Service Charge</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php foreach ($flats as $flat): ?>
                            <tr>
                                <td class="td_1"><?= htmlspecialchars($flat['project_name'] ?? '') ?></td>
                                <td class="td_2"><?= htmlspecialchars($flat['flat_name'] ?? 'N/A') ?></td>
                                <td>
                                    <?php if (($flat['unit_type'] ?? 'residential') === 'commercial'): ?>
                                        <span class="bd-badge bg-warning text-dark">
                                            <i class="bi bi-shop me-1"></i> Shop
                                        </span>
                                    <?php else: ?>
                                        <span class="bd-badge bg-primary">
                                            <i class="bi bi-building me-1"></i> Flat
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($flat['upload_from'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($flat['facing_site'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($flat['sqft'] ?? 'N/A') ?></td>
                                <td><?= format_bdt($flat['land_share_value'] ?? 0) ?></td>
                                <td><?= !empty($flat['development_charges']) ? format_bdt($flat['development_charges'] ?? 0) : 'N/A' ?></td>
                                <td><?= format_bdt($flat['price_value'] ?? 0) ?></td>
                                <td>
                                    <?php if (($flat['unit_type'] ?? 'residential') === 'commercial' && !empty($flat['shop_service_charge'])): ?>
                                        <span class="bd-badge bg-info text-dark"><?= (int)($flat['shop_service_charge'] ?? 0) ?>%</span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($flat['is_booked'] == 1) : ?>
                                        <span class="bd-badge bg-danger">Booked</span>
                                    <?php else: ?>
                                        <span class="bd-badge bg-success">Available</span>
                                    <?php endif ?>
                                </td>
                                <td class="table__icon-box">
                                    <div class="d-flex align-items-center justify-content-center gap-10">
                                        <a href="<?= $GLOBALS['base_url'] ?>/edit-flat/<?= $flat['id'] ?>" class="table__icon edit">
                                            <i class="fa-sharp fa-light fa-pen"></i>
                                        </a>
                                        <a class="table__icon download" href="<?= $GLOBALS['base_url'] ?>/flat-details/<?= $flat['id'] ?>"
                                            title="View Transaction History"> <i class="fa-regular fa-eye"></i>
                                        </a>
                                        <?php if (hasPermission("view lead")): ?>
                                            <button class="d-none table__icon delete removeBtnCust  "
                                                data-name="<?= $flat['flat_name'] ?>"
                                                data-action="delete-flat"
                                                data-id="<?= $flat['id'] ?>">
                                                <i class="fa-regular fa-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="table__icon delete removeBtnCust  "
                                                data-name="<?= $flat['flat_name'] ?>"
                                                data-action="delete-flat"
                                                data-id="<?= $flat['id'] ?>">
                                                <i class="fa-regular fa-trash"></i>
                                            </button>
                                        <?php endif ?>
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
    const base_url = "<?= $GLOBALS['base_url'] ?>"
</script>
<!-- CSV Import Modal (Flats) -->
<div class="modal fade" id="importCsvModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Flats from CSV</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>

            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/bulk-flats-import-csv"
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

                <div class="mt-15 p-2 rounded text-muted" style="font-size: 12px; max-height: 200px; overflow-y: auto;">
                    <strong>Required CSV Headers:</strong><br>
                    project_name, sqft, bhk, land_share_value, development_charges, bedroom, bathroom, balcony, drawing_dining_kitchen, common_dining, floor_no, flat_name, facing_site, price_value, facilities, status
                    <br><br>
                    <em>Note: project_name must exactly match an existing project. status can be available, booked, or sold.</em>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Shop CSV Import Modal -->
<div class="modal fade" id="importShopCsvModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg,#f59e0b,#d97706); color:#fff;">
                <h5 class="modal-title"><i class="bi bi-shop me-2"></i>Import Shops (Commercial) from CSV</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" style="color:#fff;">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>

            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/bulk-shops-import-csv"
                    method="post"
                    enctype="multipart/form-data">

                    <div class="from__input-box mb-20">
                        <div class="form__input-title">
                            <label>Upload Shop CSV File <span>*</span></label>
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
                        <button class="btn btn-warning">Import Shops</button>
                    </div>
                </form>

                <div class="mt-15 p-2 rounded" style="font-size: 12px; max-height: 220px; overflow-y: auto; background:#fffbeb; border:1px solid #fcd34d;">
                    <strong>📋 Required CSV Column Headers (for Shop):</strong><br><br>
                    <table style="width:100%; font-size:11px; border-collapse:collapse;">
                        <thead>
                            <tr style="background:#fef3c7;">
                                <th style="padding:3px 6px; border:1px solid #fcd34d;">Column in CSV</th>
                                <th style="padding:3px 6px; border:1px solid #fcd34d;">Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td style="padding:3px 6px; border:1px solid #fcd34d;"><code>project_name</code></td><td style="padding:3px 6px; border:1px solid #fcd34d;">Exact project name from system</td></tr>
                            <tr><td style="padding:3px 6px; border:1px solid #fcd34d;"><code>shop_name</code></td><td style="padding:3px 6px; border:1px solid #fcd34d;">Shop unit name / number</td></tr>
                            <tr><td style="padding:3px 6px; border:1px solid #fcd34d;"><code>sqft</code></td><td style="padding:3px 6px; border:1px solid #fcd34d;">Area in square feet</td></tr>
                            <tr><td style="padding:3px 6px; border:1px solid #fcd34d;"><code>floor_no</code></td><td style="padding:3px 6px; border:1px solid #fcd34d;">Floor number</td></tr>
                            <tr><td style="padding:3px 6px; border:1px solid #fcd34d;"><code>facing_site</code></td><td style="padding:3px 6px; border:1px solid #fcd34d;">e.g. North, East</td></tr>
                            <tr><td style="padding:3px 6px; border:1px solid #fcd34d;"><code>price_value</code></td><td style="padding:3px 6px; border:1px solid #fcd34d;">Shop price (BDT)</td></tr>
                            <tr><td style="padding:3px 6px; border:1px solid #fcd34d;"><code>land_share_value</code></td><td style="padding:3px 6px; border:1px solid #fcd34d;">Land share (15+15% booking+registration)</td></tr>
                            <tr><td style="padding:3px 6px; border:1px solid #fcd34d;"><code>shop_service_charge</code></td><td style="padding:3px 6px; border:1px solid #fcd34d;">Service charge % (e.g. 10)</td></tr>
                            <tr><td style="padding:3px 6px; border:1px solid #fcd34d;"><code>status</code></td><td style="padding:3px 6px; border:1px solid #fcd34d;">available / booked / sold</td></tr>
                        </tbody>
                    </table>
                    <br>
                    <em class="text-warning">⚠ unit_type=commercial and is_shop=1 are set automatically.</em>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$addPage = true;
$show_breadcrumb = false;
?>
<h4 class="mb-2">
    Flat Management
</h4>
<form action="" method="get">

    <div class="row flat-select">
        <!-- Search -->
        <!-- Department Filter -->
        <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-4 col-6">
            <div class="card__wrapper">
                <div class="from__input-box">
                    <select class="form-control" name="project_id">
                        <option value="">Select Project</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['id'] ?>"
                                <?= $selectedProject == $project['id'] ? 'selected' : '' ?>>
                                <?= $project['name'] ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-4 col-6">
            <div class="card__wrapper">
                <div class="search-box">
                    <input type="text" class="form-control" name="q" id="flatName"
                        placeholder="Flat/Unit/Sqft "
                        value="<?= htmlspecialchars($searchTerm ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-4 col-12">
            <div class="card__wrapper">
                <div class="d-flex flex-column flex-sm-row gap-2">
                    <button type="submit" class="btn btn-secondary w-100">
                        Filter
                    </button>

                    <a href="<?= $GLOBALS['base_url'] ?>/flat-lists" class="btn btn-success w-100">
                        Reset
                    </a>

                    <?php if (hasPermission("view lead")): ?>
                        <a class="d-none btn btn-primary w-100" href="<?= $GLOBALS['base_url'] ?>/create-flat">
                            Add Flat
                        </a>
                    <?php else: ?>
                        <button type="button" class="btn btn-outline-secondary w-100" data-bs-toggle="modal" data-bs-target="#importCsvModal">
                            Import CSV
                        </button>
                        <a class="btn btn-primary w-100" href="<?= $GLOBALS['base_url'] ?>/create-flat">
                            Add Flat
                        </a>
                    <?php endif ?>
                </div>
            </div>
        </div>

    </div>
</form>

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
                            <th>BHK</th>
                            <th>Floor No</th>
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
                                <td><?= htmlspecialchars($flat['bhk']) ?></td>
                                <td><?= htmlspecialchars($flat['floor_no']) ?></td>
                                <td><?= htmlspecialchars($flat['facing_site']) ?></td>
                                <td><?= htmlspecialchars($flat['sqft']) ?></td>
                                <td><?= format_bdt($flat['land_share_value']) ?? 'N/A' ?></td>
                                <td><?= !empty($flat['development_charges']) ? format_bdt($flat['development_charges']) : 'N/A' ?></td>
                                <td><?= format_bdt($flat['price_value']) ?></td>
                                <td>
                                    <?php if (($flat['unit_type'] ?? 'residential') === 'commercial' && !empty($flat['shop_service_charge'])): ?>
                                        <span class="bd-badge bg-info text-dark"><?= (int)$flat['shop_service_charge'] ?>%</span>
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
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const resetBtn = form.querySelector('button[type="reset"]');

        resetBtn.addEventListener('click', function(e) {
            e.preventDefault(); // avoid default reset
            // Clear all inputs
            form.querySelectorAll('input, select').forEach(function(el) {
                if (el.tagName === 'SELECT') el.selectedIndex = 0; // first option select
                else el.value = ''; // input clear
            });
        });
    });
</script>
<style>
    @media (max-width: 575.98px) {
        .card__wrapper .d-flex {
            flex-direction: row !important;
        }

        .card__wrapper .btn {
            flex: 1 1 0;
            width: auto !important;
            font-size: 0.8rem;
            padding: 0.45rem 0.5rem;
            white-space: nowrap;
        }
    }
</style>

<!-- CSV Import Modal -->
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

                <div class="mt-15 p-2  rounded text-muted" style="font-size: 12px; max-height: 200px; overflow-y: auto;">
                    <strong>Required CSV Headers:</strong><br>
                    project_name, sqft, bhk, land_share_value, development_charges, bedroom, bathroom, balcony, drawing_dining_kitchen, common_dining, floor_no, flat_name, facing_site, price_value, facilities, status
                    <br><br>
                    <em>Note: project_name must exactly match an existing project's name in the system. status can be available, booked, or sold.</em>
                </div>
            </div>
        </div>
    </div>
</div>
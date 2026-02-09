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
                    <input type="text" class="form-control" name="q" id="employeeName"
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
                            <th>BHK</th>

                            <th>Floor No</th>
                            <th>Facing Site</th>
                            <th>Sqft</th>
                            <th>Land Share Value</th>
                            <th>Price</th>
                            <th>Facilities</th>
                            <th>Available/Booked </th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php foreach ($flats as $flat): ?>
                            <tr>
                                <td class="td_1"><?= htmlspecialchars($flat['project_name'] ?? '') ?></td>
                                <td class="td_2"><?= htmlspecialchars($flat['flat_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($flat['bhk']) ?></td>

                                <td><?= htmlspecialchars($flat['floor_no']) ?></td>
                                <td><?= htmlspecialchars($flat['facing_site']) ?></td>
                                <td><?= htmlspecialchars($flat['sqft']) ?></td>
                                <td><?= format_bdt($flat['land_share_value']) ?? 'N/A' ?></td>
                                <td><?= format_bdt($flat['price_value']) ?></td>
                                <td><?= htmlspecialchars($flat['facilities']) ?></td>
                                <td>
                                    <?php if ($flat['is_booked'] == 1) : ?>
                                        <span class="bd-badge bg-danger">
                                            Booked
                                        </span>
                                    <?php else: ?>
                                        <span class="bd-badge bg-success">
                                            Available
                                        </span>

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
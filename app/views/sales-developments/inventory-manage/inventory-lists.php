<?php
$addPage = true;
$show_breadcrumb = false;

?>


<h4 class="mb-4">
    Project Inventory Lists
</h4>
<form action="" method="get">
    <div class="row flat-select">

        <!-- Search -->
        <!-- Department Filter -->
        <div class="row d-none">

            <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-4 col-6">
                <div class="card__wrapper">
                    <div class="from__input-box">
                        <select class="form-control" name="department_id">
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
                        <input type="text" class="form-control" name="q" id="bookedOrAvailable"
                            placeholder="Booked/"
                            value="">
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-4 col-6">
                <div class="card__wrapper">
                    <div class="d-grid">
                        <button type="submit" class="btn btn-secondary w-100">
                            Filter
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-4 col-xl-4 col-lg-4 col-12">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon">
                        <span><i class="fa-solid fa-building"></i>
                        </span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Total Projects </h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title"><?= $totalProjects ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-4 col-xl-4 col-lg-4 col-12">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon">
                        <span><i class="fa-solid fa-building"></i>
                        </span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Total Flat Available</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title"><?= $availableFlats ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-4 col-xl-4 col-lg-4 col-12">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon">
                        <span><i class="fa-solid fa-building"></i>
                        </span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Total Flat Booked</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title"><?= $bookedFlats ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</form>

<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper p-3">
            <div class="table__wrapper table-responsive">
                <table class="table table-bordered table-hover mb-0 text-center align-middle" id="inventoryTable">
                    <thead class="table-dark">
                        <tr>
                            <th class="th_1">Project Name</th>
                            <th>Total Flats</th>
                            <th>Stock/Available</th>
                            <th>Booked</th>
                            <th class="d-none">Sold</th>
                            <th>Total Value (৳)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventoryProjects as $index => $row): ?>
                            <!-- Project Summary Row -->
                            <tr class="fw-bold table-secondary">
                                <td class="text-start td_1"><?= htmlspecialchars($row['project_name']) ?></td>
                                <td>
                                    <span class=" bd-badge border border-secondary  bg-secondary bg-opacity-10 px-3 py-1 mt-2">
                                        <?= $row['total_flats'] ?>
                                    </span>
                                </td>
                                <td><span class="badge bg-success p-2"><?= $row['available_flats'] ?></span></td>
                                <td><span class="badge bg-danger p-2"><?= $row['booked_flats'] ?></span></td>
                                <td class="d-none"><span class="badge bg-dark text-white p-2"><?= $row['sold_flats'] ?? 0 ?></span></td>
                                <td><strong><?= format_bdt($row['total_flat_value']) ?> ৳</strong></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary show-flats-btn" data-target="flats<?= $index ?>">Show Details</button>
                                </td>
                            </tr>

                            <!-- Hidden Flat Rows -->
                            <?php if (!empty($row['flats'])): ?>
                                <?php foreach ($row['flats'] as $flat): ?>
                                    <tr class="flat-details-row" id="flats<?= $index ?>" style="display:none; background-color:rgb(253 246 246);">

                                        <td class="text-start ps-4 td_1"><strong class="me-1">Flat: </strong><?= htmlspecialchars($flat['flat_name']) ?>; <span><strong class="ms-2">Size:</strong><?= $flat['sqft'] ?> sqft</span></td>
                                        <td><strong class="pe-3">BHK-</strong><span><?= $flat['bhk'] ?></span></td>
                                        <td>

                                            <?= isset($flat['agreement_value']) && $flat['agreement_value'] > 0
                                                ?       '<span class="fw-semibold fs-6"> Booking Money: </span>' . format_bdt($flat['agreement_value']) . " ৳"
                                                : '<span class="bd-badge bg-primary  p-2">Not Yet Booked</span>'; ?>
                                        </td>


                                        <td>
                                            <?php
                                            switch ($flat['status']) {
                                                case 'available':
                                                    echo '<span class=" badge bg-success p-2">Available</span>';
                                                    break;
                                                case 'booked':
                                                    echo '<span class="badge bg-danger p-2">Booked</span>';
                                                    break;
                                                case 'sold':
                                                    echo '<span class="badge bg-dark text-white p-2">Sold</span>';
                                                    break;
                                            }
                                            ?>
                                        </td>
                                        <td><span class="fw-semibold ">Price:</span><?= format_bdt($flat['price_value']) ?> ৳</td>

                                        <td>
                                            <a href="<?= $GLOBALS['base_url'] ?>/edit-flat/<?= $flat['id'] ?>" class="btn btn-sm btn-secondary me-1">Edit</a>
                                            <a href="<?= $GLOBALS['base_url'] ?>/flat-details/<?= $flat['id'] ?>" class="btn btn-sm btn-primary me-1">Details</a>

                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- JS for Toggle -->
<script>
    document.querySelectorAll('.show-flats-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.dataset.target;
            const flatRows = document.querySelectorAll(`#${targetId}`);
            flatRows.forEach(row => {
                if (row.style.display === 'none') {
                    row.style.display = 'table-row';
                    btn.innerText = 'Hide Details';
                } else {
                    row.style.display = 'none';
                    btn.innerText = 'Show Details';
                }
            });
        });
    });
</script>
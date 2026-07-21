<?php
$page = 'Projects';
$addPage = true;
$show_breadcrumb = true;

$totalProjects = count($projects ?? []);
$totalConstruction = 0;
$totalPlanning = 0;
$totalSoldOut = 0;
$totalOnSale = 0;

foreach ($projects as $p) {
    if (!empty($p['under_construction'])) {
        $totalConstruction++;
    } else {
        $totalPlanning++;
    }

    $saleStatus = trim($p['project_sale_status'] ?? '');
    if (strcasecmp($saleStatus, 'Sold Out') === 0) {
        $totalSoldOut++;
    } elseif (strcasecmp($saleStatus, 'On Sale') === 0) {
        $totalOnSale++;
    }
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <h4 class="mb-2 mb-md-0">
        Project Management
    </h4>
    <?php if (hasPermission("view lead")): ?>
    <div class="d-none gap-2">
        <a href="<?= $GLOBALS['base_url'] ?>/create-project" class="btn btn-primary"> <i class="fa-light fa-plus me-1"></i> Add Project</a>
    </div>
    <?php else: ?>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importCsvModal">
            Import CSV
        </button>
        <a href="<?= $GLOBALS['base_url'] ?>/create-project" class="btn btn-primary"> <i class="fa-light fa-plus me-1"></i> Add Project</a>
    </div>
    <?php endif; ?>
</div>

<!-- Project Status Counter Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 filter-card p-3" id="cardFilterAll" style="cursor: pointer; background: #ffffff; border-left: 4px solid #4f46e5 !important; transition: transform 0.2s;" onclick="filterProjectStatus('all')">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-secondary fs-12 fw-bold d-block text-uppercase mb-1">Total Projects</span>
                    <h3 class="mb-0 fw-bold text-dark"><?= $totalProjects ?></h3>
                </div>
                <div class="avatar-sm bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="fa-solid fa-city fs-18"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 filter-card p-3" id="cardFilterConstruction" style="cursor: pointer; background: #f0fdf4; border-left: 4px solid #10b981 !important; transition: transform 0.2s;" onclick="filterProjectStatus('construction')">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-success fs-12 fw-bold d-block text-uppercase mb-1"><i class="fa-solid fa-helmet-safety me-1"></i>On Construction</span>
                    <h3 class="mb-0 fw-bold text-success"><?= $totalConstruction ?></h3>
                </div>
                <div class="avatar-sm bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="fa-solid fa-building-circle-check fs-18"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 filter-card p-3" id="cardFilterPlanning" style="cursor: pointer; background: #fefce8; border-left: 4px solid #f59e0b !important; transition: transform 0.2s;" onclick="filterProjectStatus('planning')">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-warning fs-12 fw-bold d-block text-uppercase mb-1"><i class="fa-solid fa-drafting-compass me-1"></i>On Planning</span>
                    <h3 class="mb-0 fw-bold text-warning"><?= $totalPlanning ?></h3>
                </div>
                <div class="avatar-sm bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="fa-solid fa-ruler-combined fs-18"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 filter-card p-3" id="cardFilterSoldOut" style="cursor: pointer; background: #fef2f2; border-left: 4px solid #ef4444 !important; transition: transform 0.2s;" onclick="filterProjectStatus('soldout')">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-danger fs-12 fw-bold d-block text-uppercase mb-1"><i class="fa-solid fa-ban me-1"></i>Sold Out</span>
                    <h3 class="mb-0 fw-bold text-danger"><?= $totalSoldOut ?></h3>
                </div>
                <div class="avatar-sm bg-danger-subtle text-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="fa-solid fa-store-slash fs-18"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <!-- Filter Bar -->
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 p-3 rounded border ">
                <div class="d-flex align-items-center gap-2">
                    <label class="form-label mb-0 fs-13 fw-bold text-secondary"><i class="fa-solid fa-filter me-1"></i>Status Filter:</label>
                    <select class="form-select form-select-sm fw-semibold w-auto" id="statusFilterSelect" onchange="filterProjectStatus(this.value)">
                        <option value="all">All Projects (<?= $totalProjects ?>)</option>
                        <option value="construction">On Construction (<?= $totalConstruction ?>)</option>
                        <option value="planning">On Planning (<?= $totalPlanning ?>)</option>
                        <option value="soldout">Sold Out (<?= $totalSoldOut ?>)</option>
                        <option value="onsale">On Sale (<?= $totalOnSale ?>)</option>
                    </select>
                </div>
                <div class="text-muted fs-12 d-flex flex-wrap gap-2">
                    <span class="badge bg-success-subtle text-success"><i class="fa-solid fa-helmet-safety me-1"></i>Construction: <?= $totalConstruction ?></span>
                    <span class="badge bg-warning-subtle text-warning"><i class="fa-solid fa-drafting-compass me-1"></i>Planning: <?= $totalPlanning ?></span>
                    <span class="badge bg-danger-subtle text-danger"><i class="fa-solid fa-ban me-1"></i>Sold Out: <?= $totalSoldOut ?></span>
                </div>
            </div>

            <div class="table__wrapper table-responsive">
                <table class="table mb-20 multiple_tables">
                    <thead>
                        <tr class="table__title table__sort">
                            <th class="th_1">Name</th>
                            <th>Location</th>
                            <th>Community</th>
                            <th>Category</th>
                            <th>Building Type</th>
                            <th>Total Share</th>
                            <th>Unit Per Floor</th>
                            <th class="d-none">Type</th>
                            <th>Sale Status</th>
                            <th>Service Charge</th>
                            <th>Brochure</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php foreach ($projects as $project): ?>
                            <tr>
                                <td class="td_1">
                                    <span class="fw-bold text-dark"><?= htmlspecialchars($project['name']) ?></span>
                                    <?php if (!empty($project['under_construction'])): ?>
                                        <span class="badge bg-success text-white ms-2 px-2 py-1 fs-11 fw-semibold status-tag" data-status="construction">
                                            <i class="fa-solid fa-helmet-safety me-1"></i>On Construction
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark ms-2 px-2 py-1 fs-11 fw-semibold status-tag" data-status="planning">
                                            <i class="fa-solid fa-drafting-compass me-1"></i>On Planning
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($project['location_name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($project['community_name'] ?? '') ?> </td>
                                <td><?= htmlspecialchars($project['category_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($project['building_type'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($project['total_share'] ?? 0) ?></td>
                                <td><?= htmlspecialchars($project['unit_per_floor'] ?? 0) ?></td>
                                <td class="d-none"><?= htmlspecialchars($project['types'] ?? 'N/A') ?></td>

                                <!-- Sale Status -->
                                <td>
                                    <?php
                                    $ss = $project['project_sale_status'] ?? null;
                                    $ssColor = match($ss) {
                                        'On Sale'  => 'success',
                                        'Sold Out' => 'danger',
                                        'Upcoming' => 'warning',
                                        default    => 'secondary'
                                    };
                                    ?>
                                    <?php if ($ss): ?>
                                        <span class="badge bg-<?= $ssColor ?> sale-status-tag"><?= $ss ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($project['service_charge_per_emi'])): ?>
                                        <span class="badge bg-info text-dark"><?= $project['service_charge_per_emi'] ?>%</span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($project['brochure_link'])): ?>
                                        <a href="<?= $project['brochure_link'] ?>" target="_blank">Link</a>
                                    <?php endif; ?>
                                </td>

                                <td class="table__icon-box">
                                    <div class="d-flex align-items-center justify-content-center gap-10">

                                        <a class="table__icon download" href="<?= $GLOBALS['base_url'] ?>/project-details/<?= $project['id'] ?>"
                                            title="View Transaction History"> <i class="fa-regular fa-eye"></i>
                                        </a>
                                        <a href="<?= $GLOBALS['base_url'] ?>/edit-project/<?= $project['id'] ?>" class="table__icon edit">
                                            <i class="fa-sharp fa-light fa-pen"></i>
                                        </a>

                                        <button class="table__icon delete removeBtnCust  "
                                            data-id="<?= $project['id'] ?>"
                                            data-name="<?= $project['name'] ?>"
                                            data-action="delete-project">
                                            <i class="fa-regular fa-trash"></i>
                                        </button>

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
    const base_url = "<?= $GLOBALS['base_url'] ?>";

    function filterProjectStatus(status) {
        const select = document.getElementById("statusFilterSelect");
        if (select && select.value !== status) {
            select.value = status;
        }

        // Highlight Active Card
        document.querySelectorAll('.filter-card').forEach(c => c.style.transform = 'none');
        if (status === 'construction') {
            const card = document.getElementById('cardFilterConstruction');
            if (card) card.style.transform = 'scale(1.02)';
        } else if (status === 'planning') {
            const card = document.getElementById('cardFilterPlanning');
            if (card) card.style.transform = 'scale(1.02)';
        } else if (status === 'soldout') {
            const card = document.getElementById('cardFilterSoldOut');
            if (card) card.style.transform = 'scale(1.02)';
        } else {
            const card = document.getElementById('cardFilterAll');
            if (card) card.style.transform = 'scale(1.02)';
        }

        // Filter Table Rows
        const rows = document.querySelectorAll('.multiple_tables tbody tr');
        rows.forEach(row => {
            const statusBadge = row.querySelector('.status-tag');
            const saleStatusBadge = row.querySelector('.sale-status-tag');
            
            const itemConstructionStatus = statusBadge ? statusBadge.getAttribute('data-status') : '';
            const itemSaleStatus = saleStatusBadge ? saleStatusBadge.textContent.trim().toLowerCase() : '';

            if (status === 'all') {
                row.style.display = '';
            } else if (status === 'construction') {
                row.style.display = (itemConstructionStatus === 'construction') ? '' : 'none';
            } else if (status === 'planning') {
                row.style.display = (itemConstructionStatus === 'planning') ? '' : 'none';
            } else if (status === 'soldout') {
                row.style.display = (itemSaleStatus.includes('sold out')) ? '' : 'none';
            } else if (status === 'onsale') {
                row.style.display = (itemSaleStatus.includes('on sale')) ? '' : 'none';
            }
        });

        // Apply DataTables search if DataTables is initialized
        if (window.jQuery && $.fn.DataTable && $.fn.DataTable.isDataTable('.multiple_tables')) {
            const table = $('.multiple_tables').DataTable();
            if (status === 'construction') {
                table.search('On Construction').draw();
            } else if (status === 'planning') {
                table.search('On Planning').draw();
            } else if (status === 'soldout') {
                table.search('Sold Out').draw();
            } else if (status === 'onsale') {
                table.search('On Sale').draw();
            } else {
                table.search('').draw();
            }
        }
    }
</script>

<!-- CSV Import Modal -->
<div class="modal fade" id="importCsvModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Projects from CSV</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>

            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/bulk-projects-import-csv"
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
                    name, slug, location_id, community_id, building_type, total_share, unit_per_floor, floor_wise_unit_meta, types, image, image2, image3, front_road, price_range, hover_text, tag, plot_size, plot_no, block_no, brochure_link, description, apartment_count, amenities_details, amenities_image, map_image, map_link, project_map_location, passenger_lift, cargo_lift, rooftop_gardening, swimming_pool, convention, electricity_backup, car_parking, project_sale_status, service_charge_per_emi, project_status
                    <br><br>
                    <em>Note: Ensure headers exactly match.</em>
                </div>
            </div>
        </div>
    </div>
</div>
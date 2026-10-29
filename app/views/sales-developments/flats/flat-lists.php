<?php
$addPage = true;
$show_breadcrumb = false;

// ── CSV import skipped-row report ────────────────────────────────────
$csvSkipped = $_SESSION['csv_import_skipped'] ?? [];
unset($_SESSION['csv_import_skipped']); // clear after reading
?>
<h4 class="mb-2">Flat Management</h4>

<?php if (!empty($csvSkipped)): ?>
<div class="card__wrapper mb-3" style="border-left: 4px solid #f59e0b;">
    <div class="d-flex align-items-center gap-2 mb-3">
        <i class="fa-solid fa-triangle-exclamation text-warning fs-5"></i>
        <h6 class="mb-0 fw-semibold text-warning">
            <?= count($csvSkipped) ?> row(s) were <u>not imported</u> from the CSV
        </h6>
    </div>
    <div class="table-responsive" style="max-height:320px; overflow-y:auto;">
        <table class="table table-sm table-bordered mb-0" style="font-size:13px;">
            <thead class="table-warning">
                <tr>
                    <th style="width:60px">CSV Row</th>
                    <th>Project</th>
                    <th>Flat / Unit</th>
                    <th>Reason</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($csvSkipped as $sk): ?>
                <tr>
                    <td class="text-center fw-bold"><?= (int)$sk['row'] ?></td>
                    <td><?= htmlspecialchars($sk['project'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($sk['flat']    ?? '—') ?></td>
                    <td class="text-danger"><?= htmlspecialchars($sk['reason'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="card__wrapper mb-3">
    <form action="" method="get">

        <!-- Row 1: Filter inputs -->
        <div class="row g-2 align-items-center mb-2">

            <!-- Project Filter -->
            <div class="col-xl-3 col-md-4 col-sm-6">
                <select class="form-control form-control-sm select2" name="project_id">
                    <option value="">All Projects</option>
                    <?php foreach ($projects as $project): ?>
                        <option value="<?= $project['id'] ?>"
                            <?= $selectedProject == $project['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($project['name']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

            <!-- Status Filter -->
            <div class="col-xl-2 col-md-3 col-sm-6">
                <select class="form-control form-control-sm" name="status">
                    <option value="">All Status</option>
                    <option value="available" <?= ($selectedStatus ?? '') === 'available' ? 'selected' : '' ?>>Available</option>
                    <option value="booked"    <?= ($selectedStatus ?? '') === 'booked'    ? 'selected' : '' ?>>Booked</option>
                    <option value="sold"      <?= ($selectedStatus ?? '') === 'sold'      ? 'selected' : '' ?>>Sold</option>
                </select>
            </div>

            <!-- Search -->
            <div class="col-xl-3 col-md-4 col-sm-6">
                <input type="text" class="form-control form-control-sm" name="q" id="flatName"
                    placeholder="Search Flat / Unit / Sqft"
                    value="<?= htmlspecialchars($searchTerm ?? '') ?>">
            </div>

            <!-- Filter + Reset -->
            <div class="col-xl-2 col-md-3 col-sm-6">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-secondary btn-sm flex-fill">
                        <i class="fa-solid fa-filter me-1"></i> Filter
                    </button>
                    <a href="<?= $GLOBALS['base_url'] ?>/flat-lists" class="btn btn-success btn-sm flex-fill">
                        <i class="fa-solid fa-rotate-right me-1"></i> Reset
                    </a>
                </div>
            </div>

        </div>

        <!-- Row 2: Action Buttons -->
        <div class="row mt-3">
            <div class="col-12">
                <div class="d-flex flex-wrap gap-2">
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
                        <button type="button" class="btn btn-info btn-sm"
                            data-bs-toggle="modal" data-bs-target="#importSuiteCsvModal">
                            <i class="fa-solid fa-file-csv me-1"></i> Import Flat Suite CSV
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
                <table class="table mb-20" id="flatListTable">
                    <thead>
                        <tr class="table__title">
                            <th style="width:40px">#</th>
                            <th>Project</th>
                            <th>Flat/Unit</th>
                            <th>Suite No.</th>
                            <th>Rate/SFT</th>
                            <th>Booking</th>
                            <th>Reg. Money</th>
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
                        <!-- Populated by DataTables AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<script>
    var base_url = "<?= $GLOBALS['base_url'] ?>";

    // Custom DataTable for flat-lists — Server Side Processing
    document.addEventListener('DOMContentLoaded', function () {
        // Read URL params if present (from the GET filter form)
        const urlParams = new URLSearchParams(window.location.search);
        const projectId = urlParams.get('project_id') || '';
        const status = urlParams.get('status') || '';
        const q = urlParams.get('q') || '';

        $('#flatListTable').DataTable({
            destroy: true,
            processing: true,
            serverSide: true,
            ajax: {
                url: base_url + '/ajax-flat-lists',
                type: 'GET',
                cache: false,
                data: function (d) {
                    d.project_id = projectId;
                    d.status = status;
                    if (q && !d.search.value) {
                        d.search.value = q; // Pass initial search query to DataTables search
                    }
                },
                error: function (xhr, error, thrown) {
                    console.error("DataTables AJAX error:", error, thrown);
                    console.error("Response Text:", xhr.responseText);
                }
            },
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
            order: [],   // default sort falls back to server-side (latest first)
            columnDefs: [
                { orderable: false, searchable: false, targets: 0 },  // # serial column
                { orderable: false, searchable: false, targets: -1 }, // Actions column
            ],
            drawCallback: function(settings) {
                // Re-bind sweetalert/delete events for dynamic buttons
                $('#flatListTable .removeBtnCust').off('click').on('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const itemId = $(this).data('id');
                    const itemName = $(this).data('name');
                    const actionUrl = $(this).attr('data-action');
                    
                    Swal.fire({
                        title: 'Are you sure?',
                        text: `You are about to delete "${itemName}". This action cannot be undone!`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, delete it!'
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            const form = $('<form>', {
                                method: 'POST',
                                action: base_url + '/' + actionUrl
                            });
                            form.append($('<input>', { type: 'hidden', name: 'id', value: itemId }));
                            $('body').append(form);
                            form.submit();
                        }
                    });
                });
            }
        });
    });
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

<!-- CSV Import Modal (Flat Suite) -->
<div class="modal fade" id="importSuiteCsvModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fa-solid fa-file-csv me-2"></i>Import Flat Suite from CSV</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>

            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/bulk-suite-import-csv"
                    method="post" enctype="multipart/form-data">
                    <div class="from__input-box mb-20">
                        <label class="form-label">Select Project <span>*</span></label>
                        <select name="project_id" class="form-control select2" required style="width: 100%;">
                            <option value="">-- Choose Project --</option>
                            <?php foreach ($projects as $proj): ?>
                                <option value="<?= $proj['id'] ?>"><?= htmlspecialchars($proj['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="from__input-box mb-20">
                        <div class="form__input-title">
                            <label>Upload CSV File <span>*</span></label>
                        </div>
                        <div class="form__input">
                            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        </div>
                    </div>

                    <div class="submit__btn text-center">
                        <button class="btn btn-info">Import CSV</button>
                    </div>
                </form>

                <div class="mt-15 p-2 rounded text-muted" style="font-size: 12px; max-height: 200px; overflow-y: auto;">
                    <strong>Required CSV Headers (in order):</strong><br>
                    unit_name, Floor, Suite No., Ownership Type, Unit Type, Size (Sq Ft), Rate per sft (BDT), Booking 20%, Registration 20%, development 10%, Total Price, Facing Site, Facilities, Status
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
                            <tr><td style="padding:3px 6px; border:1px solid #fcd34d;"><code>shop_service_charge</code></td><td style="padding:3px 6px; border:1px solid #fcd34d;">Shop Service charge (BDT)</td></tr>
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
<?php
$page_title = "Set Sales Target";
$page = 'Set Sales Target';
$show_breadcrumb = true;
?>


<div class="row">
    <div class="col-xxl-12">

        <div class="card__title-wrap mb-20">
            <h5 class="card__heading-title">
                Set Target for: <?= htmlspecialchars($user['full_name']) ?> 
                <span class="badge bg-primary ms-2"><?= htmlspecialchars($teamName ?? 'No Team') ?></span>
            </h5>
        </div>

        <form action="<?= $GLOBALS['base_url'] ?>/save-sales-target/<?= $user['id'] ?>" method="POST">

            <div class="card__wrapper mb-20">
                <div class="row g-20">

                    <!-- Month -->
                    <div class="col-lg-4 mb-3">
                        <label>Month <span>*</span></label>
                        <input type="month"
                            name="month"
                            id="create_target_month"
                            class="form-control"
                            required
                            value="<?= isset($target['month']) ? date('Y-m', strtotime($target['month'])) : date('Y-m') ?>">
                    </div>

                    <!-- Target From Date -->
                    <div class="col-lg-4 mb-3">
                        <label>Target From Date <span>*</span></label>
                        <input type="date"
                            name="from_date"
                            id="create_target_from_date"
                            class="form-control"
                            required
                            value="<?= $target['from_date'] ?? '' ?>">
                    </div>

                    <!-- Target To Date -->
                    <div class="col-lg-4 mb-3">
                        <label>Target To Date <span>*</span></label>
                        <input type="date"
                            name="to_date"
                            id="create_target_to_date"
                            class="form-control"
                            required
                            value="<?= $target['to_date'] ?? '' ?>">
                    </div>

                    <div class="col-12 mt-n2 mb-2">
                        <small class="text-primary">
                            <i class="fa-solid fa-circle-info me-1"></i>Dates automatically sync based on sales cycle rules (e.g. May 1-31, June 1-30, July 1-25, and 26th-to-25th for others). You can also customize dates manually.
                        </small>
                    </div>

                    <!-- Sales Role -->
                    <div class="col-lg-6 mb-3">
                        <label>Sales Role <span>*</span></label>
                        <select name="sales_role" class="form-control select2" data-placeholder="-- Select Sales Role --">
                            <option value="">-- Select Sales Role --</option>
                            <option value="KAM" <?= (isset($target['sales_role']) && $target['sales_role'] === 'KAM') ? 'selected' : '' ?>>KAM</option>
                            <option value="KAE" <?= (isset($target['sales_role']) && $target['sales_role'] === 'KAE') ? 'selected' : '' ?>>KAE</option>
                        </select>
                    </div>

                    <!-- Category -->
                    <div class="col-lg-6 mb-3">
                        <label>Assigned Category(s) (Optional)</label>
                        <select name="category_ids[]" id="category_select" class="form-control select2" multiple data-placeholder="-- Select Category(s) --">
                            <?php foreach ($categories as $cat): ?>
                                <?php $cLabel = !empty($cat['main_category']) ? $cat['category_name'] . ' (' . $cat['main_category'] . ')' : $cat['category_name']; ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">All projects under selected categories will automatically count as assigned projects.</small>
                    </div>

                    <!-- Project -->
                    <div class="col-lg-6 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="mb-0">Assigned Project(s) (Optional)</label>
                            <div>
                                <a href="javascript:void(0)" class="text-primary fw-bold" style="font-size: 12px; margin-right: 5px;" onclick="$('#project_select > option').prop('selected', true); $('#project_select').trigger('change');">Select All</a>
                                <a href="javascript:void(0)" class="text-danger fw-bold" style="font-size: 12px;" onclick="$('#project_select').val(null).trigger('change');">Clear</a>
                            </div>
                        </div>
                        <select name="project_ids[]" id="project_select" class="form-control select2" multiple data-placeholder="-- Select Project(s) --">
                            <?php foreach ($projects as $proj): ?>
                                <?php $catLabel = !empty($proj['main_category']) ? $proj['main_category'] : ($proj['cat_name'] ?? 'General'); ?>
                                <option value="<?= $proj['id'] ?>" data-category="<?= $proj['category_id'] ?>">
                                    <?= htmlspecialchars($proj['name']) ?> (<?= htmlspecialchars($catLabel) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">You can select multiple specific projects.</small>
                    </div>
                    
                    <!-- Flats / Units -->
                    <div class="col-lg-6 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="mb-0">Targeted Flats/Units <small class="text-danger">Optional</small></label>
                            <div>
                                <a href="javascript:void(0)" class="text-primary fw-bold" style="font-size: 12px; margin-right: 5px;" onclick="$('#flat_select > option').prop('selected', true); $('#flat_select').trigger('change');">Select All</a>
                                <a href="javascript:void(0)" class="text-danger fw-bold" style="font-size: 12px;" onclick="$('#flat_select').val(null).trigger('change');">Clear</a>
                            </div>
                        </div>
                        <select name="flat_ids[]" id="flat_select" class="form-control select2" multiple data-placeholder="-- Select Flats/Units --">
                            <!-- Populated via AJAX -->
                        </select>
                        <small class="text-muted">Selecting units will suggest the Land Share amount, but you can freely customize or type any target amount.</small>
                    </div>
                    <!-- Land Share Target (Amount) -->
                    <div class="col-lg-6 mb-3">
                        <label>Land Share Target (Amount) <span>*</span></label>
                        <input type="number"
                            name="booking_value_target"
                            id="booking_value_target"
                            class="form-control"
                            min="0"
                            step="0.01"
                            value="<?= $target['booking_value_target'] ?? 0 ?>" required>
                    </div>

                    <!-- Site Visit Target -->
                    <div class="col-lg-6 mb-3">
                        <label>Site Visit Ratio Target <small class="text-danger">Optional</small></label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="number"
                                name="site_visit_target"
                                class="form-control"
                                placeholder="Visits (e.g. 5)"
                                min="0"
                                value="<?= $target['site_visit_target'] ?? 0 ?>">
                            <span> / </span>
                            <input type="number"
                                name="site_visit_leads_target"
                                class="form-control"
                                placeholder="Leads (e.g. 10)"
                                min="0"
                                value="<?= $target['site_visit_leads_target'] ?? 0 ?>">
                        </div>
                        <small class="text-muted">E.g., 5 visits per 10 leads. Leave Leads as 0 for a fixed target.</small>
                    </div>

                    <!-- Booking Target (Flat) -->
                    <div class="col-lg-6 mb-3">
                        <label>Total Assigned Units (Booking Target) <small class="text-danger">Optional</small></label>
                        <input type="number"
                            name="booking_target"
                            id="booking_target"
                            class="form-control"
                            min="0"
                            value="<?= $target['booking_target'] ?? 0 ?>">
                    </div>



                    <div class="col-12 text-center mt-3">
                        <button class="btn btn-primary">Save Target</button>
                    </div>

                </div>
            </div>

        </form>
    </div>
</div>




<!-- your all month target  -->
<div class="col-12 mt-3">
    <div class="card__wrapper">
        <div class="card__header d-flex justify-content-between align-items-center mb-3">
            <h5 class="card__title mb-0" id="transactionsTitle">
                Target History & Progress
            </h5>

        </div>

        <div class="table__wrapper table-responsive">
            <table class="table mb-20">
                <thead>
                    <tr class="table__title">
                        <th>S/L</th>
                        <th>Month & Period</th>
                        <th>Category(s)</th>
                        <th>Project(s) & Units</th>
                        <th>Site Visit Target</th>
                        <th>Booking Target</th>
                        <th>Target Value</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="targetTableBody">
                    <?php if (!empty($targets)): ?>
                        <?php foreach ($targets as $index => $row): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td>
                                    <strong><?= date('F Y', strtotime($row['month'])) ?></strong>
                                    <?php if (!empty($row['from_date']) && !empty($row['to_date'])): ?>
                                        <br><span class="badge bg-light text-primary border fs-11 mt-1">
                                            <i class="fa-regular fa-calendar-days me-1"></i><?= date('d M, Y', strtotime($row['from_date'])) ?> &rarr; <?= date('d M, Y', strtotime($row['to_date'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['category_names_display'] ?? ($row['category_name'] ?? 'All / None')) ?></span></td>
                                <td>
                                    <div class="d-flex flex-column align-items-start gap-1">
                                    <?php 
                                        if (!empty($row['project_names_display'])) {
                                            echo '<div class="fw-bold text-primary" style="font-size: 13px;">' . count($row['project_names_display']) . ' project(s)</div>';
                                        } else {
                                            echo '<div class="text-muted" style="font-size: 13px;">Category-wide / All</div>';
                                        }

                                        if (!empty($row['flat_names_display'])) {
                                            echo '<div class="fw-bold text-info" style="font-size: 13px;">' . count($row['flat_names_display']) . ' specific unit(s)</div>';
                                        }
                                        
                                        if (!empty($row['project_names_display']) || !empty($row['flat_names_display'])) {
                                            $projList = htmlspecialchars(json_encode($row['project_names_display']));
                                            $flatList = htmlspecialchars(json_encode($row['flat_names_display']));
                                            echo "<button class='btn btn-xs btn-light border fs-12 mt-1' onclick='viewTargetDetails(this)' data-projects='{$projList}' data-flats='{$flatList}'><i class='fa-regular fa-eye me-1'></i>View</button>";
                                        }
                                    ?>
                                    </div>
                                </td>
                                <td>
                                    <?= $row['site_visit_target'] ?>
                                    <?php if (!empty($row['site_visit_leads_target'])): ?>
                                        <small class="text-muted"> / <?= $row['site_visit_leads_target'] ?> leads</small>
                                    <?php endif; ?>
                                </td>
                                <td><?= $row['booking_target'] ?></td>
                                <td><?= number_format($row['booking_value_target'], 2) ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <button type="button"
                                            class="btn btn-sm btn-outline-primary rounded-2 px-2 py-1"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editSalesTarget"
                                            data-id="<?= $row['id'] ?>"
                                            data-month="<?= $row['month'] ?>"
                                            data-from-date="<?= htmlspecialchars($row['from_date'] ?? '') ?>"
                                            data-to-date="<?= htmlspecialchars($row['to_date'] ?? '') ?>"
                                            data-category="<?= $row['category_id'] ?>"
                                            data-category-ids="<?= htmlspecialchars($row['category_ids'] ?? '') ?>"
                                            data-projects="<?= htmlspecialchars($row['project_ids']) ?>"
                                            data-flats="<?= htmlspecialchars($row['flat_ids'] ?? '[]') ?>"
                                            data-site_visit="<?= $row['site_visit_target'] ?>"
                                            data-site_visit_leads="<?= $row['site_visit_leads_target'] ?? 0 ?>"
                                            data-booking="<?= $row['booking_target'] ?>"
                                            data-booking_value="<?= $row['booking_value_target'] ?>"
                                            data-sales_role="<?= $row['sales_role'] ?>"
                                            title="Edit Target">
                                            <i class="fa-solid fa-pen-to-square me-1"></i>Edit
                                        </button>
                                        <a href="<?= $GLOBALS['base_url'] ?>/delete-sales-target/<?= $row['id'] ?>?user_id=<?= $user['id'] ?>"
                                           class="btn btn-sm btn-outline-danger rounded-2 px-2 py-1 ms-1"
                                           onclick="return confirm('Are you sure you want to delete this target?');"
                                           title="Delete Target">
                                            <i class="fa-solid fa-trash me-1"></i>Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No targets found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

        </div>
    </div>
</div>


<div class="modal fade" id="editSalesTarget" tabindex="-1" aria-labelledby="editSalesTargetLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSalesTargetLabel">Edit Sales Target</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>

            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/update-sales-target" method="POST">
                    <input type="hidden" name="id" id="edit_target_id">
                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">


                    <div class="card__wrapper mb-20">
                        <div class="row g-20">

                            <!-- Month -->
                            <div class="col-lg-4 mb-3">
                                <label>Month <span>*</span></label>
                                <input type="month" name="month" id="edit_target_month" class="form-control" required>
                            </div>

                            <!-- Target From Date -->
                            <div class="col-lg-4 mb-3">
                                <label>Target From Date <span>*</span></label>
                                <input type="date" name="from_date" id="edit_target_from_date" class="form-control" required>
                            </div>

                            <!-- Target To Date -->
                            <div class="col-lg-4 mb-3">
                                <label>Target To Date <span>*</span></label>
                                <input type="date" name="to_date" id="edit_target_to_date" class="form-control" required>
                            </div>

                            <div class="col-12 mt-n2 mb-2">
                                <small class="text-primary">
                                    <i class="fa-solid fa-circle-info me-1"></i>Dates automatically sync when month changes, or can be customized.
                                </small>
                            </div>

                            <!-- Sales Role -->
                            <div class="col-lg-6 mb-3">
                                <label>Sales Role <span>*</span></label>
                                <select name="sales_role" id="edit_target_sales_role" class="form-control" required>
                                    <option value="">-- Select Sales Role --</option>
                                    <option value="KAM">KAM</option>
                                    <option value="KAE">KAE</option>
                                </select>
                            </div>

                            <!-- Category -->
                            <div class="col-lg-6 mb-3">
                                <label>Assigned Category(s) (Optional)</label>
                                <select name="category_ids[]" id="edit_target_categories" class="form-control" multiple size="4">
                                    <?php foreach ($categories as $cat): ?>
                                        <?php $cLabel = !empty($cat['main_category']) ? $cat['category_name'] . ' (' . $cat['main_category'] . ')' : $cat['category_name']; ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cLabel) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Hold Ctrl (Windows) / Cmd (Mac) to select multiple.</small>
                            </div>

                            <!-- Project -->
                            <div class="col-lg-6 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="mb-0">Assigned Project(s) (Optional)</label>
                                    <div>
                                        <a href="javascript:void(0)" class="text-primary fw-bold" style="font-size: 12px; margin-right: 5px;" onclick="$('#edit_target_project > option').prop('selected', true); $('#edit_target_project').trigger('change');">Select All</a>
                                        <a href="javascript:void(0)" class="text-danger fw-bold" style="font-size: 12px;" onclick="$('#edit_target_project').val(null).trigger('change');">Clear</a>
                                    </div>
                                </div>
                                <select name="project_ids[]" id="edit_target_project" class="form-control select2" multiple data-placeholder="-- Select Project(s) --">
                                    <?php foreach ($projects as $proj): ?>
                                        <?php $catLabel = !empty($proj['main_category']) ? $proj['main_category'] : ($proj['cat_name'] ?? 'General'); ?>
                                        <option value="<?= $proj['id'] ?>" data-category="<?= $proj['category_id'] ?>">
                                            <?= htmlspecialchars($proj['name']) ?> (<?= htmlspecialchars($catLabel) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Flats / Units -->
                            <div class="col-lg-6 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="mb-0">Targeted Flats/Units <small class="text-danger">Optional</small></label>
                                    <div>
                                        <a href="javascript:void(0)" class="text-primary fw-bold" style="font-size: 12px; margin-right: 5px;" onclick="$('#edit_flat_select > option').prop('selected', true); $('#edit_flat_select').trigger('change');">Select All</a>
                                        <a href="javascript:void(0)" class="text-danger fw-bold" style="font-size: 12px;" onclick="$('#edit_flat_select').val(null).trigger('change');">Clear</a>
                                    </div>
                                </div>
                                <select name="flat_ids[]" id="edit_flat_select" class="form-control select2" multiple data-placeholder="-- Select Flats/Units --">
                                    <!-- Populated via AJAX -->
                                </select>
                            </div>

                            <!-- Site Visit Target -->
                            <div class="col-lg-4 mb-3">
                                <label>Site Visit Ratio Target</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="number" name="site_visit_target" id="edit_target_site_visit" class="form-control" placeholder="Visits" min="0">
                                    <span> / </span>
                                    <input type="number" name="site_visit_leads_target" id="edit_target_site_visit_leads" class="form-control" placeholder="Leads" min="0">
                                </div>
                            </div>

                            <!-- Booking Target -->
                            <div class="col-lg-4 mb-3">
                                <label>Total Assigned Units</label>
                                <input type="number" name="booking_target" id="edit_target_booking" class="form-control" min="0">
                            </div>

                            <!-- Booking Value Target -->
                            <div class="col-lg-4 mb-3">
                                <label>Land Share Target (Amount) <span>*</span></label>
                                <input type="number" name="booking_value_target" id="edit_target_booking_value" class="form-control" min="0" step="0.01" required>
                            </div>

                        </div>
                    </div>

                    <div class="submit__btn text-center">
                        <button class="btn btn-primary" type="submit">Update Target</button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>


<!-- Sales Targers Edit Modal End -->

<!-- View Target Details Modal -->
<div class="modal fade" id="viewTargetDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assigned Projects & Flats</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="targetDetailsContent"></div>
            </div>
        </div>
    </div>
</div>
<!-- View Target Details Modal End -->

<script>
    function viewTargetDetails(btn) {
        const projects = JSON.parse(btn.getAttribute('data-projects') || '[]');
        const flats = JSON.parse(btn.getAttribute('data-flats') || '[]');
        
        let html = '';
        
        if (projects.length > 0) {
            html += '<h6 class="text-primary mb-2">Projects (' + projects.length + ')</h6>';
            html += '<ul class="list-group mb-3">';
            projects.forEach(p => {
                html += '<li class="list-group-item py-1 fs-14">' + p + '</li>';
            });
            html += '</ul>';
        }
        
        if (flats.length > 0) {
            html += '<h6 class="text-info mb-2">Flats/Units (' + flats.length + ')</h6>';
            html += '<ul class="list-group">';
            flats.forEach(f => {
                html += '<li class="list-group-item py-1 fs-14">' + f + '</li>';
            });
            html += '</ul>';
        }
        
        document.getElementById('targetDetailsContent').innerHTML = html;
        var viewModal = new bootstrap.Modal(document.getElementById('viewTargetDetailsModal'));
        viewModal.show();
    }
</script>

<script>
    const base_url = "<?= $GLOBALS['base_url'] ?>";

    // Cycle calculation helper
    function getCycleDateRangeForMonth(monthVal) {
        if (!monthVal) return { start: '', end: '' };
        const parts = monthVal.split('-');
        const year = parseInt(parts[0], 10);
        const month = parseInt(parts[1], 10);

        // 2026 Historical transition rules
        if (year === 2026) {
            if (month === 5) {
                return { start: '2026-05-01', end: '2026-05-31' };
            }
            if (month === 6) {
                return { start: '2026-06-01', end: '2026-06-30' };
            }
            if (month === 7) {
                return { start: '2026-07-01', end: '2026-07-25' };
            }
        }

        // Standard 26th to 25th cycle
        let prevYear = year;
        let prevMonth = month - 1;
        if (prevMonth === 0) {
            prevMonth = 12;
            prevYear = year - 1;
        }
        const pad = (n) => String(n).padStart(2, '0');
        return {
            start: `${prevYear}-${pad(prevMonth)}-26`,
            end: `${year}-${pad(month)}-25`
        };
    }

document.addEventListener('DOMContentLoaded', function() {
    // Auto sync Create Form dates
    const createMonthInput = document.getElementById('create_target_month');
    const createFromInput = document.getElementById('create_target_from_date');
    const createToInput = document.getElementById('create_target_to_date');

    function syncCreateDates() {
        if (createMonthInput && createFromInput && createToInput) {
            const range = getCycleDateRangeForMonth(createMonthInput.value);
            createFromInput.value = range.start;
            createToInput.value = range.end;
        }
    }

    if (createMonthInput) {
        createMonthInput.addEventListener('change', syncCreateDates);
        if (createFromInput && (!createFromInput.value || !createToInput.value)) {
            syncCreateDates();
        }
    }

    // Auto sync Edit Modal dates on month change
    const editMonthInput = document.getElementById('edit_target_month');
    if (editMonthInput) {
        editMonthInput.addEventListener('change', function() {
            const range = getCycleDateRangeForMonth(this.value);
            const editFrom = document.getElementById('edit_target_from_date');
            const editTo = document.getElementById('edit_target_to_date');
            if (editFrom) editFrom.value = range.start;
            if (editTo) editTo.value = range.end;
        });
    }

    const modal = document.getElementById('editSalesTarget');

    modal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;

        const id = button.getAttribute('data-id');
        const month = button.getAttribute('data-month');
        const fromDate = button.getAttribute('data-from-date');
        const toDate = button.getAttribute('data-to-date');
        const category = button.getAttribute('data-category');
        let categoryIds = [];
        try { 
            categoryIds = JSON.parse(button.getAttribute('data-category-ids') || "[]"); 
        } catch(e){}
        if ((!Array.isArray(categoryIds) || categoryIds.length === 0) && category) {
            categoryIds = [String(category)];
        }

        let projects = [];
        try { projects = JSON.parse(button.getAttribute('data-projects') || "[]"); } catch(e){}
        const siteVisit = button.getAttribute('data-site_visit');
        const booking = button.getAttribute('data-booking');
        const bookingValue = button.getAttribute('data-booking_value');
        const salesRole = button.getAttribute('data-sales_role');

        document.getElementById('edit_target_id').value = id;
        const monthStr = month ? month.substring(0, 7) : '';
        document.getElementById('edit_target_month').value = monthStr;

        const editFrom = document.getElementById('edit_target_from_date');
        const editTo = document.getElementById('edit_target_to_date');
        if (fromDate) {
            editFrom.value = fromDate;
        } else if (monthStr) {
            editFrom.value = getCycleDateRangeForMonth(monthStr).start;
        }

        if (toDate) {
            editTo.value = toDate;
        } else if (monthStr) {
            editTo.value = getCycleDateRangeForMonth(monthStr).end;
        }

        document.getElementById('edit_target_site_visit').value = siteVisit ?? 0;
        document.getElementById('edit_target_site_visit_leads').value = button.getAttribute('data-site_visit_leads') ?? 0;
        document.getElementById('edit_target_booking').value = booking ?? 0;
        document.getElementById('edit_target_booking_value').value = bookingValue ?? 0;
        document.getElementById('edit_target_sales_role').value = salesRole || '';

        // Populate Categories in modal
        const catSelect = document.getElementById('edit_target_categories');
        if (catSelect) {
            const catIdStrings = categoryIds.map(String);
            for (let i = 0; i < catSelect.options.length; i++) {
                catSelect.options[i].selected = catIdStrings.includes(String(catSelect.options[i].value));
            }
            $(catSelect).trigger('change');
        }

        // Populate Projects in modal
        const projSelect = document.getElementById('edit_target_project');
        if (projSelect) {
            const projIdStrings = projects.map(String);
            for (let i = 0; i < projSelect.options.length; i++) {
                projSelect.options[i].selected = projIdStrings.includes(String(projSelect.options[i].value));
            }
            $(projSelect).trigger('change');
        }
        
        // Fetch and Populate Flats in modal
        let flatsArray = [];
        try { flatsArray = JSON.parse(button.getAttribute('data-flats') || "[]"); } catch(e){}
        const flatIdStrings = flatsArray.map(String);
        
        const editFlatSelect = document.getElementById('edit_flat_select');
        const editBookingValueTarget = document.getElementById('edit_target_booking_value');
        if (projSelect && editFlatSelect) {
            const selectedProjects = Array.from(projSelect.selectedOptions).map(opt => opt.value);
            $(editFlatSelect).empty();
            
            if (selectedProjects.length > 0) {
                fetch(`${base_url}/get-project-flats?project_ids[]=${selectedProjects.join('&project_ids[]=')}`)
                .then(res => res.json())
                .then(flats => {
                    flats.forEach(flat => {
                        const isBooked = (flat.is_booked == 1) || (flat.flat_status === 'booked') || (flat.flat_status === 'completed');
                        if (!isBooked) {
                            const optionText = `${flat.project_name} - Flat: ${flat.flat_name} (Floor: ${flat.floor_no}, ${flat.sqft} sqft)`;
                            const isSelected = flatIdStrings.includes(String(flat.id));
                            const newOption = new Option(optionText, flat.id, isSelected, isSelected);
                            newOption.setAttribute('data-price', flat.land_share_value);
                            $(editFlatSelect).append(newOption);
                        } else if (flatIdStrings.includes(String(flat.id))) {
                            // Already targeted previously, so we must show it to prevent losing selection
                            // Already targeted previously, so we must show it to prevent losing selection
                            const optionText = `${flat.project_name} - Flat: ${flat.flat_name} (Floor: ${flat.floor_no}, ${flat.sqft} sqft) [Booked]`;
                            const newOption = new Option(optionText, flat.id, true, true);
                            newOption.setAttribute('data-price', flat.land_share_value);
                            $(editFlatSelect).append(newOption);
                        }
                    });
                    if (window.jQuery) $(editFlatSelect).trigger('change');
                    else editFlatSelect.dispatchEvent(new Event('change', { bubbles: true }));
                })
                .catch(err => console.error(err));
            } else {
                if (window.jQuery) $(editFlatSelect).trigger('change');
                else editFlatSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }
            if (editBookingValueTarget) {
                editBookingValueTarget.readOnly = false;
            }
        }
    });

    // Dynamic Flats Logic for Edit Form
    const editProjectSelect = document.getElementById('edit_target_project');
    const editFlatSelect = document.getElementById('edit_flat_select');
    const editBookingValueTarget = document.getElementById('edit_target_booking_value');

    if (editProjectSelect && editFlatSelect) {
        if (window.jQuery) {
            $(editProjectSelect).on('change', function() {
                const selectedProjects = $(this).val() || [];
                
                $(editFlatSelect).empty().append('<option value="">Loading...</option>');
                $(editFlatSelect).trigger('change');
                
                if (selectedProjects.length === 0) {
                    $(editFlatSelect).empty();
                    $(editFlatSelect).trigger('change');
                    if (editBookingValueTarget) editBookingValueTarget.readOnly = false;
                    return;
                }

                fetch(`${base_url}/get-project-flats?project_ids[]=${selectedProjects.join('&project_ids[]=')}`)
                    .then(res => res.json())
                    .then(flats => {
                        $(editFlatSelect).empty();
                        flats.forEach(flat => {
                            const isBooked = (flat.is_booked == 1) || (flat.flat_status === 'booked') || (flat.flat_status === 'completed');
                            if (!isBooked) {
                                const optionText = `${flat.project_name} - Flat: ${flat.flat_name} (Floor: ${flat.floor_no}, ${flat.sqft} sqft)`;
                                const newOption = new Option(optionText, flat.id, false, false);
                                newOption.setAttribute('data-price', flat.land_share_value);
                                $(editFlatSelect).append(newOption);
                            }
                        });
                        $(editFlatSelect).trigger('change');
                    })
                    .catch(error => {
                        console.error("Error fetching flats:", error);
                        $(editFlatSelect).empty();
                        $(editFlatSelect).trigger('change');
                    });
                    
                syncTotalsFromAPI(document.getElementById('edit_target_categories'), this, editFlatSelect, document.getElementById('edit_target_booking'), document.getElementById('edit_target_booking_value'));
            });

            $(editFlatSelect).on('change', function() {
                const selectedFlats = $(this).find(':selected');
                const editBookingTarget = document.getElementById('edit_target_booking');
                
                if (selectedFlats.length > 0) {
                    let totalValue = 0;
                    selectedFlats.each(function() {
                        totalValue += parseFloat($(this).attr('data-price') || 0);
                    });
                    if (editBookingValueTarget) {
                        editBookingValueTarget.value = totalValue.toFixed(2);
                        editBookingValueTarget.readOnly = false;
                    }
                    if (editBookingTarget) {
                        editBookingTarget.value = selectedFlats.length;
                        editBookingTarget.readOnly = false;
                    }
                } else {
                    if (editBookingValueTarget) editBookingValueTarget.readOnly = false;
                    if (editBookingTarget) editBookingTarget.readOnly = false;
                }
            });
        }
    }

    // Dynamic Flats Logic for Create Form
    const projectSelect = document.getElementById('project_select');
    const flatSelect = document.getElementById('flat_select');
    const bookingValueTarget = document.getElementById('booking_value_target');

    if (projectSelect && flatSelect) {
        if (window.jQuery) {
            $(projectSelect).on('change', function() {
                const selectedProjects = $(this).val() || [];
                
                $(flatSelect).empty().append('<option value="">Loading...</option>');
                $(flatSelect).trigger('change');
                
                if (selectedProjects.length === 0) {
                    $(flatSelect).empty();
                    $(flatSelect).trigger('change');
                    if (bookingValueTarget) bookingValueTarget.readOnly = false;
                    return;
                }

                fetch(`${base_url}/get-project-flats?project_ids[]=${selectedProjects.join('&project_ids[]=')}`)
                    .then(res => res.json())
                    .then(flats => {
                        $(flatSelect).empty();
                        flats.forEach(flat => {
                            const isBooked = (flat.is_booked == 1) || (flat.flat_status === 'booked') || (flat.flat_status === 'completed');
                            if (!isBooked) {
                                const optionText = `${flat.project_name} - Flat: ${flat.flat_name} (Floor: ${flat.floor_no}, ${flat.sqft} sqft)`;
                                const newOption = new Option(optionText, flat.id, false, false);
                                newOption.setAttribute('data-price', flat.land_share_value);
                                $(flatSelect).append(newOption);
                            }
                        });
                        $(flatSelect).trigger('change');
                    })
                    .catch(error => {
                        console.error("Error fetching flats:", error);
                        $(flatSelect).empty();
                        $(flatSelect).trigger('change');
                    });
                    
                syncTotalsFromAPI(document.getElementById('category_select'), this, flatSelect, document.getElementById('booking_target'), bookingValueTarget);
            });
        }
    }


    function syncTotalsFromAPI(categorySelect, projectSelect, flatSelect, targetInput, valueInput) {
        if (!window.jQuery) return;
        const selectedFlats = $(flatSelect).find(':selected');
        if (selectedFlats.length > 0) {
            let totalValue = 0;
            selectedFlats.each(function() {
                totalValue += parseFloat($(this).attr('data-price') || 0);
            });
            if (valueInput) {
                valueInput.value = totalValue.toFixed(2);
                valueInput.readOnly = false;
            }
            if (targetInput) {
                targetInput.value = selectedFlats.length;
                targetInput.readOnly = false;
            }
            return;
        }
        
        const catIds = $(categorySelect).val() || [];
        const projIds = $(projectSelect).val() || [];
        
        if (catIds.length === 0 && projIds.length === 0) {
            if (valueInput) valueInput.readOnly = false;
            if (targetInput) targetInput.readOnly = false;
            return;
        }

        let url = `${base_url}/get-target-totals?`;
        if (catIds.length > 0) url += `category_ids[]=${catIds.join('&category_ids[]=')}&`;
        if (projIds.length > 0) url += `project_ids[]=${projIds.join('&project_ids[]=')}`;
        
        fetch(url)
        .then(res => res.json())
        .then(data => {
            if (valueInput) {
                valueInput.value = data.value.toFixed(2);
                valueInput.readOnly = false;
            }
            if (targetInput) {
                targetInput.value = data.units;
                targetInput.readOnly = false;
            }
        })
        .catch(err => console.error(err));
    }

    $(flatSelect).on('change', function() {
        syncTotalsFromAPI(document.getElementById('category_select'), projectSelect, flatSelect, document.getElementById('booking_target'), bookingValueTarget);
    });
    $('#category_select').on('change', function() {
        syncTotalsFromAPI(this, projectSelect, flatSelect, document.getElementById('booking_target'), bookingValueTarget);
    });

    if (window.jQuery) {
        $('#edit_target_categories').on('change', function() {
            syncTotalsFromAPI(this, document.getElementById('edit_target_project'), document.getElementById('edit_flat_select'), document.getElementById('edit_target_booking'), document.getElementById('edit_target_booking_value'));
        });
        
        // Ensure edit flat select syncs on change
        $(document.getElementById('edit_flat_select')).on('change', function() {
            syncTotalsFromAPI(document.getElementById('edit_target_categories'), document.getElementById('edit_target_project'), this, document.getElementById('edit_target_booking'), document.getElementById('edit_target_booking_value'));
        });
    }

});
</script>
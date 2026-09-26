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
                        <label>Assigned Project(s) (Optional)</label>
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
                    <!-- Land Share Target (Amount) -->
                    <div class="col-lg-6 mb-3">
                        <label>Land Share Target (Amount) <span>*</span></label>
                        <input type="number"
                            name="booking_value_target"
                            class="form-control"
                            min="0"
                            step="0.01"
                            value="<?= $target['booking_value_target'] ?? 0 ?>" required>
                    </div>

                    <!-- Site Visit Target -->
                    <div class="col-lg-6 mb-3">
                        <label>Site Visit Target <small class="text-danger">Optional</small></label>
                        <input type="number"
                            name="site_visit_target"
                            class="form-control"
                            min="0"
                            value="<?= $target['site_visit_target'] ?? 0 ?>">
                    </div>

                    <!-- Booking Target (Flat) -->
                    <div class="col-lg-6 mb-3">
                        <label>Booking Target (Flat) <small class="text-danger">Optional</small></label>
                        <input type="number"
                            name="booking_target"
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
                        <th>Project(s)</th>
                        <th>Site Visit Target</th>
                        <th>Booking Target</th>
                        <th>Booking Value Target</th>
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
                                    <?php 
                                        if (!empty($row['project_ids'])) {
                                            $pIds = json_decode($row['project_ids'], true);
                                            if (is_array($pIds)) {
                                                echo '<span class="badge bg-primary-transparent">' . count($pIds) . ' project(s)</span>';
                                            } else {
                                                echo htmlspecialchars($row['project_ids']);
                                            }
                                        } else {
                                            echo '<span class="text-muted">Category-wide / All</span>';
                                        }
                                    ?>
                                </td>
                                <td><?= $row['site_visit_target'] ?></td>
                                <td><?= $row['booking_target'] ?></td>
                                <td><?= number_format($row['booking_value_target'], 2) ?></td>
                                <td>
                                    <button type="button"
                                        class="table__icon edit"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editSalesTarget"
                                        data-id="<?= $row['id'] ?>"
                                        data-month="<?= $row['month'] ?>"
                                        data-from-date="<?= htmlspecialchars($row['from_date'] ?? '') ?>"
                                        data-to-date="<?= htmlspecialchars($row['to_date'] ?? '') ?>"
                                        data-category="<?= $row['category_id'] ?>"
                                        data-category-ids="<?= htmlspecialchars($row['category_ids'] ?? '') ?>"
                                        data-projects="<?= htmlspecialchars($row['project_ids']) ?>"
                                        data-site_visit="<?= $row['site_visit_target'] ?>"
                                        data-booking="<?= $row['booking_target'] ?>"
                                        data-booking_value="<?= $row['booking_value_target'] ?>"
                                        data-sales_role="<?= $row['sales_role'] ?>">
                                        <i class="fa-sharp fa-light fa-pen"></i>
                                    </button>

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
                                <label>Assigned Project(s) (Optional)</label>
                                <select name="project_ids[]" id="edit_target_project" class="form-control" multiple size="4">
                                    <?php foreach ($projects as $proj): ?>
                                        <?php $catLabel = !empty($proj['main_category']) ? $proj['main_category'] : ($proj['cat_name'] ?? 'General'); ?>
                                        <option value="<?= $proj['id'] ?>" data-category="<?= $proj['category_id'] ?>">
                                            <?= htmlspecialchars($proj['name']) ?> (<?= htmlspecialchars($catLabel) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Hold Ctrl (Windows) / Cmd (Mac) to select multiple.</small>
                            </div>

                            <!-- Site Visit Target -->
                            <div class="col-lg-4 mb-3">
                                <label>Site Visit Target</label>
                                <input type="number" name="site_visit_target" id="edit_target_site_visit" class="form-control" min="0">
                            </div>

                            <!-- Booking Target -->
                            <div class="col-lg-4 mb-3">
                                <label>Booking Target</label>
                                <input type="number" name="booking_target" id="edit_target_booking" class="form-control" min="0">
                            </div>

                            <!-- Booking Value Target -->
                            <div class="col-lg-4 mb-3">
                                <label>Booking Value Target</label>
                                <input type="number" name="booking_value_target" id="edit_target_booking_value" class="form-control" min="0" step="0.01">
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
        }

        // Populate Projects in modal
        const projSelect = document.getElementById('edit_target_project');
        if (projSelect) {
            const projIdStrings = projects.map(String);
            for (let i = 0; i < projSelect.options.length; i++) {
                projSelect.options[i].selected = projIdStrings.includes(String(projSelect.options[i].value));
            }
        }
    });
</script>
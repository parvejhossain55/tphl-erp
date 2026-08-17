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
            </h5>
        </div>

        <form action="<?= $GLOBALS['base_url'] ?>/save-sales-target/<?= $user['id'] ?>" method="POST">

            <div class="card__wrapper mb-20">
                <div class="row g-20">

                    <!-- Month -->
                    <div class="col-lg-6 mb-3">
                        <label>Month <span>*</span></label>
                        <input type="month"
                            name="month"
                            class="form-control"
                            required
                            value="<?= isset($target['month']) ? date('Y-m', strtotime($target['month'])) : date('Y-m') ?>">
                    </div>

                    <!-- Category -->
                    <div class="col-lg-6 mb-3">
                        <label>Assigned Category (Optional)</label>
                        <select name="category_id" id="category_select" class="form-control">
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Project -->
                    <div class="col-lg-6 mb-3">
                        <label>Assigned Project(s) (Optional)</label>
                        <select name="project_ids[]" id="project_select" class="form-control" multiple size="4">
                            <?php foreach ($projects as $proj): ?>
                                <option value="<?= $proj['id'] ?>" data-category="<?= $proj['category_id'] ?>"><?= htmlspecialchars($proj['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Hold Ctrl (Windows) or Cmd (Mac) to select multiple.</small>
                    </div>

                    <!-- Site Visit Target -->
                    <div class="col-lg-6 mb-3">
                        <label>Site Visit Target</label>
                        <input type="number"
                            name="site_visit_target"
                            class="form-control"
                            min="0"
                            value="<?= $target['site_visit_target'] ?? 0 ?>">
                    </div>

                    <!-- Booking Target -->
                    <div class="col-lg-6 mb-3">
                        <label>Booking Target</label>
                        <input type="number"
                            name="booking_target"
                            class="form-control"
                            min="0"
                            value="<?= $target['booking_target'] ?? 0 ?>">
                    </div>

                    <!-- Booking Value Target -->
                    <div class="col-lg-6 mb-3">
                        <label>Booking Value Target <small class="text-danger">Optional</small></label>
                        <input type="number"
                            name="booking_value_target"
                            class="form-control"
                            min="0"
                            step="0.01"
                            value="<?= $target['booking_value_target'] ?? 0 ?>">
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
                Today's Follow-ups
            </h5>

        </div>

        <div class="table__wrapper table-responsive">
            <table class="table mb-20">
                <thead>
                    <tr class="table__title">
                        <th>S/L</th>
                        <th>Month</th>
                        <th>Category</th>
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
                                <td><?= date('F Y', strtotime($row['month'])) ?></td>
                                <td><?= htmlspecialchars($row['category_name'] ?? 'All') ?></td>
                                <td>
                                    <?php 
                                        if (!empty($row['project_ids'])) {
                                            $pIds = json_decode($row['project_ids'], true);
                                            echo "Selected " . count($pIds) . " project(s)";
                                        } else {
                                            echo "All/None";
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
                                        data-category="<?= $row['category_id'] ?>"
                                        data-projects="<?= htmlspecialchars($row['project_ids']) ?>"
                                        data-site_visit="<?= $row['site_visit_target'] ?>"
                                        data-booking="<?= $row['booking_target'] ?>"
                                        data-booking_value="<?= $row['booking_value_target'] ?>">
                                        <i class="fa-sharp fa-light fa-pen"></i>
                                    </button>

                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No targets found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

        </div>
    </div>
</div>


<div class="modal fade" id="editSalesTarget" tabindex="-1" aria-labelledby="editSalesTargetLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
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
                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>"> <!-- Add this -->


                    <div class="card__wrapper mb-20">
                        <div class="row g-20">

                            <!-- Month -->
                            <div class="col-lg-6 mb-3">
                                <label>Month <span>*</span></label>
                                <input type="month" name="month" id="edit_target_month" class="form-control" required>
                            </div>

                            <!-- Category -->
                            <div class="col-lg-6 mb-3">
                                <label>Assigned Category (Optional)</label>
                                <select name="category_id" id="edit_target_category" class="form-control">
                                    <option value="">-- Select Category --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Project -->
                            <div class="col-lg-6 mb-3">
                                <label>Assigned Project(s) (Optional)</label>
                                <select name="project_ids[]" id="edit_target_project" class="form-control" multiple size="4">
                                    <?php foreach ($projects as $proj): ?>
                                        <option value="<?= $proj['id'] ?>" data-category="<?= $proj['category_id'] ?>"><?= htmlspecialchars($proj['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Hold Ctrl (Windows) or Cmd (Mac) to select multiple.</small>
                            </div>

                            <!-- Site Visit Target -->
                            <div class="col-lg-6 mb-3">
                                <label>Site Visit Target</label>
                                <input type="number" name="site_visit_target" id="edit_target_site_visit" class="form-control" min="0">
                            </div>

                            <!-- Booking Target -->
                            <div class="col-lg-6 mb-3">
                                <label>Booking Target</label>
                                <input type="number" name="booking_target" id="edit_target_booking" class="form-control" min="0">
                            </div>

                            <!-- Booking Value Target -->
                            <div class="col-lg-6 mb-3">
                                <label>Booking Value Target <small class="text-danger">Optional</small></label>
                                <input type="number" name="booking_value_target" id="edit_target_booking_value" class="form-control" min="0" step="0.01">
                            </div>

                        </div>
                    </div>

                    <div class="submit__btn text-center">
                        <button class="btn btn-primary" type="submit">Update</button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>


<!-- Sales Targers Edit Modal End -->


<script>
    const base_url = "<?= $GLOBALS['base_url'] ?>"; // PHP outputs the correct base URL

    const modal = document.getElementById('editSalesTarget');

    modal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;

        const id = button.getAttribute('data-id');
        const month = button.getAttribute('data-month');
        const category = button.getAttribute('data-category');
        let projects = [];
        try { projects = JSON.parse(button.getAttribute('data-projects') || "[]"); } catch(e){}
        const siteVisit = button.getAttribute('data-site_visit');
        const booking = button.getAttribute('data-booking');
        const bookingValue = button.getAttribute('data-booking_value');

        document.getElementById('edit_target_id').value = id;
        document.getElementById('edit_target_month').value = month ? month.substring(0, 7) : '';
        document.getElementById('edit_target_category').value = category || '';
        document.getElementById('edit_target_site_visit').value = siteVisit ?? 0;
        document.getElementById('edit_target_booking').value = booking ?? 0;
        document.getElementById('edit_target_booking_value').value = bookingValue ?? 0;

        const projSelect = document.getElementById('edit_target_project');
        for (let i = 0; i < projSelect.options.length; i++) {
            projSelect.options[i].selected = projects.includes(projSelect.options[i].value);
        }
    });

    // Filter projects by category
    document.getElementById('category_select').addEventListener('change', function() {
        const catId = this.value;
        const projs = document.getElementById('project_select').options;
        for (let i = 0; i < projs.length; i++) {
            if (!catId || projs[i].getAttribute('data-category') == catId) {
                projs[i].style.display = 'block';
            } else {
                projs[i].style.display = 'none';
                projs[i].selected = false;
            }
        }
    });
    
    document.getElementById('edit_target_category').addEventListener('change', function() {
        const catId = this.value;
        const projs = document.getElementById('edit_target_project').options;
        for (let i = 0; i < projs.length; i++) {
            if (!catId || projs[i].getAttribute('data-category') == catId) {
                projs[i].style.display = 'block';
            } else {
                projs[i].style.display = 'none';
                projs[i].selected = false;
            }
        }
    });
</script>
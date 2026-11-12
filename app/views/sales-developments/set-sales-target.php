<?php
$page_title = "Set Sales Target";
$page = 'Set Sales Target';
$show_breadcrumb = true;
?>


<div class="row">
    <div class="col-xxl-12">

        <!-- Executive & Team Profile Card -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white overflow-hidden">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-4">
                    <!-- Left: Avatar, Name & Metadata -->
                    <div class="d-flex align-items-center gap-3">
                        <div class="position-relative">
                            <?php if (!empty($empInfo['photo'])): ?>
                                <img src="<?= $GLOBALS['base_url'] . '/' . htmlspecialchars($empInfo['photo']) ?>" alt="Avatar" class="rounded-circle shadow-sm" style="width: 68px; height: 68px; object-fit: cover; border: 3px solid #e0e7ff;">
                            <?php else: ?>
                                <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 68px; height: 68px; font-size: 1.8rem; border: 3px solid #e0e7ff;">
                                    <?= strtoupper(substr(trim($empInfo['emp_full_name'] ?? $user['full_name']), 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <span class="position-absolute bottom-0 end-0 p-1 bg-success border border-2 border-white rounded-circle" title="Active Account">
                                <span class="visually-hidden">Active</span>
                            </span>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <h4 class="fw-bold text-dark mb-0"><?= htmlspecialchars($empInfo['emp_full_name'] ?? $user['full_name']) ?></h4>
                                <?php if (!empty($empInfo['employee_id'])): ?>
                                    <span class="badge bg-secondary-subtle text-secondary fw-semibold border px-2 py-1 fs-7">
                                        <i class="fa-solid fa-id-badge me-1"></i><?= htmlspecialchars($empInfo['employee_id']) ?>
                                    </span>
                                <?php endif; ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 extra-small rounded-pill">
                                    <i class="fa-solid fa-circle-check me-1"></i>Sales Force
                                </span>
                            </div>
                            <div class="text-muted small mt-1 d-flex align-items-center gap-3 flex-wrap">
                                <span><i class="fa-solid fa-briefcase me-1 text-primary"></i><strong><?= htmlspecialchars($empInfo['designation_name'] ?? 'Sales Executive') ?></strong></span>
                                <span class="text-muted">&bull;</span>
                                <span><i class="fa-solid fa-sitemap me-1 text-indigo"></i><?= htmlspecialchars($empInfo['department_name'] ?? 'Sales') ?></span>
                                <?php if (!empty($empInfo['emp_phone'])): ?>
                                    <span class="text-muted">&bull;</span>
                                    <span><i class="fa-solid fa-phone me-1 text-success"></i><?= htmlspecialchars($empInfo['emp_phone']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($empInfo['emp_email'])): ?>
                                    <span class="text-muted">&bull;</span>
                                    <span><i class="fa-solid fa-envelope me-1 text-info"></i><?= htmlspecialchars($empInfo['emp_email']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Team Assignment & Quick Navigation -->
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <div class="text-start text-md-end">
                            <div class="extra-small text-muted text-uppercase fw-bold mb-1">
                                <i class="fa-solid fa-users me-1 text-primary"></i>Sales Team Assignment
                            </div>
                            <?php if (!empty($teamName) && $teamName !== 'No Team'): ?>
                                <div class="d-inline-flex align-items-center px-3 py-2 rounded-pill bg-primary-subtle text-primary border border-primary-subtle shadow-2xs">
                                    <i class="fa-solid fa-people-group me-2 fs-6"></i>
                                    <span class="fw-bold fs-6"><?= htmlspecialchars($teamName) ?></span>
                                    <?php if (!empty($empInfo['is_team_leader'])): ?>
                                        <span class="badge bg-warning text-dark ms-2 extra-small rounded-pill"><i class="fa-solid fa-crown me-1"></i>Team Leader</span>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="d-inline-flex align-items-center px-3 py-2 rounded-pill bg-light text-muted border shadow-2xs">
                                    <i class="fa-solid fa-user-slash me-2 text-secondary"></i>
                                    <span class="fw-semibold text-secondary">Individual / Direct Sales (No Team Assigned)</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="border-start ps-3 d-none d-lg-block">
                            <a href="<?= $GLOBALS['base_url'] ?>/sales-executive-teams" class="btn btn-sm btn-outline-secondary rounded-pill px-3 shadow-2xs">
                                <i class="fa-solid fa-arrow-left me-1"></i>All Teams
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($_SESSION['flash_success']) || !empty($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-2xs" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($_SESSION['flash_success'] ?? $_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['flash_success'], $_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['flash_error']) || !empty($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-2xs" role="alert">
                <i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($_SESSION['flash_error'] ?? $_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['flash_error'], $_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Configure Sales Target Form Card -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
            <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="fa-solid fa-bullseye text-primary me-2"></i>Set Target for <?= htmlspecialchars($empInfo['emp_full_name'] ?? $user['full_name']) ?>
                    </h5>
                    <span class="text-muted extra-small">Configure monthly land share quota, sales cycle dates, and assigned projects/flats scope.</span>
                </div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill extra-small fw-semibold">
                    <i class="fa-solid fa-calendar-check me-1"></i>Standard 26th – 25th Cycle
                </span>
            </div>

            <div class="card-body p-4">
                <form action="<?= $GLOBALS['base_url'] ?>/save-sales-target/<?= $user['id'] ?>" method="POST">
                    <div class="row ">
                        <!-- Section 1: Cycle Month & Dates -->
                        <div class="col-12">
                            <div class="p-3 bg-light rounded-3 border">
                                <div class="row ">
                                    <!-- Month -->
                                    <div class="col-lg-4 col-md-12">
                                        <label class="form-label text-dark fw-bold small">
                                            <i class="fa-regular fa-calendar me-1 text-primary"></i>Target Month <span class="text-danger">*</span>
                                        </label>
                                        <input type="month"
                                            name="month"
                                            id="create_target_month"
                                            class="form-control form-control-lg bg-white fw-bold text-dark"
                                            required
                                            value="<?= isset($target['month']) ? date('Y-m', strtotime($target['month'])) : date('Y-m') ?>">
                                    </div>

                                    <!-- Target From Date -->
                                    <div class="col-lg-4 col-md-6">
                                        <label class="form-label text-dark fw-bold small">
                                            <i class="fa-regular fa-calendar-plus me-1 text-success"></i>Target From Date <span class="text-danger">*</span>
                                        </label>
                                        <input type="date"
                                            name="from_date"
                                            id="create_target_from_date"
                                            class="form-control form-control-lg bg-white"
                                            required
                                            value="<?= $target['from_date'] ?? '' ?>">
                                    </div>

                                    <!-- Target To Date -->
                                    <div class="col-lg-4 col-md-6">
                                        <label class="form-label text-dark fw-bold small">
                                            <i class="fa-regular fa-calendar-minus me-1 text-danger"></i>Target To Date <span class="text-danger">*</span>
                                        </label>
                                        <input type="date"
                                            name="to_date"
                                            id="create_target_to_date"
                                            class="form-control form-control-lg bg-white"
                                            required
                                            value="<?= $target['to_date'] ?? '' ?>">
                                    </div>

                                    <div class="col-12">
                                        <div class="alert alert-info py-2 px-3 mb-0 extra-small rounded-2 border-0 bg-info-subtle text-info d-flex align-items-center">
                                            <i class="fa-solid fa-circle-info me-2 fs-6"></i>
                                            <span>Dates automatically synchronize with company sales cycle rules (e.g. 26th to 25th cut-off). You can adjust dates manually if needed.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 2: Sales Role & Category -->
                        <div class="col-lg-6 col-12">
                            <label class="form-label text-dark fw-bold small">
                                <i class="fa-solid fa-user-shield me-1 text-primary"></i>Sales Role <span class="text-danger">*</span>
                            </label>
                            <select name="sales_role" class="form-select form-select-lg select2" data-placeholder="-- Select Sales Role --">
                                <option value="KAM" <?= (!isset($target['sales_role']) || $target['sales_role'] === 'KAM') ? 'selected' : '' ?>>KAM (Key Account Manager)</option>
                                <option value="KAE" <?= (isset($target['sales_role']) && $target['sales_role'] === 'KAE') ? 'selected' : '' ?>>KAE (Key Account Executive)</option>
                            </select>
                            <div class="extra-small text-muted mt-1">Designates the operational sales target hierarchy role.</div>
                        </div>

                        <div class="col-lg-6 col-12">
                            <label class="form-label text-dark fw-bold small">
                                <i class="fa-solid fa-layer-group me-1 text-indigo"></i>Assigned Category(s) <span class="text-muted fw-normal">(Optional)</span>
                            </label>
                            <select name="category_ids[]" id="category_select" class="form-control select2" multiple data-placeholder="-- Select Category(s) --">
                                <?php foreach ($categories as $cat): ?>
                                    <?php $cLabel = !empty($cat['main_category']) ? $cat['category_name'] . ' (' . $cat['main_category'] . ')' : $cat['category_name']; ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="extra-small text-muted mt-1">All projects under selected categories will automatically count as assigned projects.</div>
                        </div>

                        <!-- Section 3: Projects & Flats -->
                        <div class="col-lg-6 col-12">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label text-dark fw-bold small mb-0">
                                    <i class="fa-solid fa-building me-1 text-primary"></i>Assigned Project(s) <span class="text-muted fw-normal">(Optional)</span>
                                </label>
                                <div class="extra-small">
                                    <a href="javascript:void(0)" class="badge bg-primary-subtle text-primary border border-primary-subtle text-decoration-none me-1" onclick="$('#project_select > option').prop('selected', true); $('#project_select').trigger('change');">Select All</a>
                                    <a href="javascript:void(0)" class="badge bg-danger-subtle text-danger border border-danger-subtle text-decoration-none" onclick="$('#project_select').val(null).trigger('change');">Clear</a>
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
                            <div class="extra-small text-muted mt-1">Select one or multiple projects this executive is assigned to sell.</div>
                        </div>

                        <div class="col-lg-6 col-12">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label text-dark fw-bold small mb-0">
                                    <i class="fa-solid fa-door-open me-1 text-teal"></i>Targeted Flats/Units <span class="text-muted fw-normal">(Optional)</span>
                                </label>
                                <div class="extra-small">
                                    <a href="javascript:void(0)" class="badge bg-primary-subtle text-primary border border-primary-subtle text-decoration-none me-1" onclick="$('#flat_select > option').prop('selected', true); $('#flat_select').trigger('change');">Select All</a>
                                    <a href="javascript:void(0)" class="badge bg-danger-subtle text-danger border border-danger-subtle text-decoration-none" onclick="$('#flat_select').val(null).trigger('change');">Clear</a>
                                </div>
                            </div>
                            <select name="flat_ids[]" id="flat_select" class="form-control select2" multiple data-placeholder="-- Select Flats/Units --">
                                <!-- Populated via AJAX -->
                            </select>
                            <div class="extra-small text-muted mt-1">Selecting units auto-suggests Land Share target, but you can set any custom amount.</div>
                        </div>

                        <!-- Section 4: Target Metrics -->
                        <!-- Land Share Target Amount -->
                        <div class="col-lg-6 col-12">
                            <div class="p-3 bg-primary-subtle rounded-3 border border-primary-subtle h-100">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label text-dark fw-bold small mb-0">
                                        <i class="fa-solid fa-map-location-dot me-1 text-primary"></i>Land Share Target (Amount) <span class="text-danger">*</span>
                                    </label>
                                    <button type="button" class="btn btn-xs btn-primary rounded-pill px-2 py-0 extra-small fw-semibold shadow-2xs" onclick="autoCalculateLandShare('create')">
                                        <i class="fa-solid fa-calculator me-1"></i>Auto-sum from units
                                    </button>
                                </div>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-white fw-bold text-primary">৳</span>
                                    <input type="number"
                                        name="booking_value_target"
                                        id="booking_value_target"
                                        class="form-control form-control-lg bg-white fw-bold text-primary fs-5"
                                        min="0"
                                        step="0.01"
                                        placeholder="e.g. 2500000"
                                        value="<?= $target['booking_value_target'] ?? 0 ?>" required>
                                </div>
                                <div class="extra-small text-muted mt-2">
                                    <i class="fa-solid fa-info-circle me-1"></i>Official incentive threshold is based on Land Share Value (Minimum ৳25,00,000 for eligibility).
                                </div>
                            </div>
                        </div>

                        <!-- Site Visit Ratio & Booking Units Target -->
                        <div class="col-lg-6 col-12">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <div class="row g-2">
                                    <div class="col-12 mb-2">
                                        <label class="form-label text-dark fw-bold small mb-1">
                                            <i class="fa-solid fa-person-walking-luggage me-1 text-success"></i>Site Visit Ratio Target <span class="text-muted fw-normal">(Optional)</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="number"
                                                name="site_visit_target"
                                                class="form-control bg-white"
                                                placeholder="Visits (e.g. 5)"
                                                min="0"
                                                value="<?= $target['site_visit_target'] ?? 0 ?>">
                                            <span class="input-group-text bg-light text-muted fw-bold">visits per</span>
                                            <input type="number"
                                                name="site_visit_leads_target"
                                                class="form-control bg-white"
                                                placeholder="Leads (e.g. 10)"
                                                min="0"
                                                value="<?= $target['site_visit_leads_target'] ?? 0 ?>">
                                            <span class="input-group-text bg-light text-muted">leads</span>
                                        </div>
                                        <div class="extra-small text-muted mt-1">E.g., 5 visits per 10 leads. Leave Leads as 0 for a fixed visit count.</div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label text-dark fw-bold small mb-1">
                                            <i class="fa-solid fa-house-chimney-user me-1 text-indigo"></i>Total Assigned Units (Booking Target) <span class="text-muted fw-normal">(Optional)</span>
                                        </label>
                                        <input type="number"
                                            name="booking_target"
                                            id="booking_target"
                                            class="form-control bg-white"
                                            min="0"
                                            placeholder="e.g. 2 units"
                                            value="<?= $target['booking_target'] ?? 0 ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="col-12 text-center pt-3">
                            <button type="submit" class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow-sm fs-6">
                                <i class="fa-solid fa-circle-check me-2"></i>Save Sales Target
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Target History & Progress Table Card -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
            <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="fa-solid fa-clock-rotate-left text-indigo me-2"></i>Target History & Progress
                    </h5>
                    <span class="text-muted extra-small">Past and active monthly sales targets set for <?= htmlspecialchars($empInfo['emp_full_name'] ?? $user['full_name']) ?></span>
                </div>
                <span class="badge bg-light text-muted border px-3 py-2 rounded-pill extra-small fw-semibold">
                    <?= count($targets) ?> Record(s) Configured
                </span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted extra-small text-uppercase">
                        <tr>
                            <th class="ps-4">S/L</th>
                            <th>Month & Period</th>
                            <th>Category(s)</th>
                            <th>Project(s) & Units</th>
                            <th>Site Visit Target</th>
                            <th>Booking Target</th>
                            <th>Land Share Target Value</th>
                            <th class="pe-4 text-end" style="min-width: 140px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="targetTableBody">
                        <?php if (!empty($targets)): ?>
                            <?php foreach ($targets as $index => $row): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-muted"><?= $index + 1 ?></td>
                                    <td>
                                        <strong class="text-dark d-block"><?= date('F Y', strtotime($row['month'])) ?></strong>
                                        <?php if (!empty($row['from_date']) && !empty($row['to_date'])): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle extra-small mt-1">
                                                <i class="fa-regular fa-calendar me-1"></i><?= date('d M, Y', strtotime($row['from_date'])) ?> &rarr; <?= date('d M, Y', strtotime($row['to_date'])) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary border extra-small">
                                            <?= htmlspecialchars($row['category_names_display'] ?? ($row['category_name'] ?? 'All / None')) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column align-items-start gap-1">
                                        <?php 
                                            if (!empty($row['project_names_display'])) {
                                                echo '<div class="fw-bold text-primary small"><i class="fa-solid fa-building me-1"></i>' . count($row['project_names_display']) . ' project(s)</div>';
                                            } else {
                                                echo '<div class="text-muted extra-small">Category-wide / All</div>';
                                            }

                                            if (!empty($row['flat_names_display'])) {
                                                echo '<div class="fw-bold text-info extra-small"><i class="fa-solid fa-door-open me-1"></i>' . count($row['flat_names_display']) . ' specific unit(s)</div>';
                                            }
                                            
                                            if (!empty($row['project_names_display']) || !empty($row['flat_names_display'])) {
                                                $projList = htmlspecialchars(json_encode($row['project_names_display']));
                                                $flatList = htmlspecialchars(json_encode($row['flat_names_display']));
                                                echo "<button class='btn btn-xs btn-outline-info rounded-pill px-2 py-0 extra-small shadow-2xs mt-1' onclick='viewTargetDetails(this)' data-projects='{$projList}' data-flats='{$flatList}'><i class='fa-regular fa-eye me-1'></i>View List</button>";
                                            }
                                        ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark"><?= $row['site_visit_target'] ?></span>
                                        <?php if (!empty($row['site_visit_leads_target'])): ?>
                                            <span class="text-muted extra-small"> / <?= $row['site_visit_leads_target'] ?> leads</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= $row['booking_target'] ?> units</span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-success fs-6">৳<?= number_format($row['booking_value_target'], 2) ?></span>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <div class="d-flex align-items-center justify-content-end gap-1">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-primary rounded-2 px-2 py-1 shadow-2xs"
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
                                               class="btn btn-sm btn-outline-danger rounded-2 px-2 py-1 ms-1 shadow-2xs"
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
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-calendar-xmark fs-2 text-muted mb-2 d-block"></i>
                                    No sales targets found for this employee yet. Use the form above to configure one.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- Modal: Edit Sales Target -->
<div class="modal fade" id="editSalesTarget" tabindex="-1" aria-labelledby="editSalesTargetLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-white text-dark d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="fa-solid fa-pen-to-square text-primary"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0" id="editSalesTargetLabel">Edit Sales Target</h5>
                        <div class="extra-small text-white-50">Modify land share quota, dates, and project restrictions</div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <form action="<?= $GLOBALS['base_url'] ?>/update-sales-target" method="POST">
                    <input type="hidden" name="id" id="edit_target_id">
                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">

                    <div class="row ">
                        <!-- Month -->
                        <div class="col-lg-4 col-12">
                            <label class="form-label text-dark fw-bold small">Month <span class="text-danger">*</span></label>
                            <input type="month" name="month" id="edit_target_month" class="form-control" required>
                        </div>

                        <!-- Target From Date -->
                        <div class="col-lg-4 col-6">
                            <label class="form-label text-dark fw-bold small">From Date <span class="text-danger">*</span></label>
                            <input type="date" name="from_date" id="edit_target_from_date" class="form-control" required>
                        </div>

                        <!-- Target To Date -->
                        <div class="col-lg-4 col-6">
                            <label class="form-label text-dark fw-bold small">To Date <span class="text-danger">*</span></label>
                            <input type="date" name="to_date" id="edit_target_to_date" class="form-control" required>
                        </div>

                        <div class="col-12 mt-1">
                            <small class="text-info extra-small">
                                <i class="fa-solid fa-circle-info me-1"></i>Dates automatically sync when month changes, or can be customized.
                            </small>
                        </div>

                        <!-- Sales Role -->
                        <div class="col-lg-6 col-12">
                            <label class="form-label text-dark fw-bold small">Sales Role <span class="text-danger">*</span></label>
                            <select name="sales_role" id="edit_target_sales_role" class="form-select" required>
                                <option value="">-- Select Sales Role --</option>
                                <option value="KAM">KAM (Key Account Manager)</option>
                                <option value="KAE">KAE (Key Account Executive)</option>
                            </select>
                        </div>

                        <!-- Category -->
                        <div class="col-lg-6 col-12">
                            <label class="form-label text-dark fw-bold small">Assigned Category(s)</label>
                            <select name="category_ids[]" id="edit_target_categories" class="form-control" multiple size="3">
                                <?php foreach ($categories as $cat): ?>
                                    <?php $cLabel = !empty($cat['main_category']) ? $cat['category_name'] . ' (' . $cat['main_category'] . ')' : $cat['category_name']; ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="extra-small text-muted">Hold Ctrl (Windows) / Cmd (Mac) to select multiple.</div>
                        </div>

                        <!-- Project -->
                        <div class="col-lg-6 col-12">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label text-dark fw-bold small mb-0">Assigned Project(s)</label>
                                <div class="extra-small">
                                    <a href="javascript:void(0)" class="badge bg-primary-subtle text-primary border border-primary-subtle text-decoration-none me-1" onclick="$('#edit_target_project > option').prop('selected', true); $('#edit_target_project').trigger('change');">Select All</a>
                                    <a href="javascript:void(0)" class="badge bg-danger-subtle text-danger border border-danger-subtle text-decoration-none" onclick="$('#edit_target_project').val(null).trigger('change');">Clear</a>
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
                        <div class="col-lg-6 col-12">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label text-dark fw-bold small mb-0">Targeted Flats/Units</label>
                                <div class="extra-small">
                                    <a href="javascript:void(0)" class="badge bg-primary-subtle text-primary border border-primary-subtle text-decoration-none me-1" onclick="$('#edit_flat_select > option').prop('selected', true); $('#edit_flat_select').trigger('change');">Select All</a>
                                    <a href="javascript:void(0)" class="badge bg-danger-subtle text-danger border border-danger-subtle text-decoration-none" onclick="$('#edit_flat_select').val(null).trigger('change');">Clear</a>
                                </div>
                            </div>
                            <select name="flat_ids[]" id="edit_flat_select" class="form-control select2" multiple data-placeholder="-- Select Flats/Units --">
                                <!-- Populated via AJAX -->
                            </select>
                        </div>

                        <!-- Land Share Target -->
                        <div class="col-lg-6 col-12">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label text-dark fw-bold small mb-0">Land Share Target <span class="text-danger">*</span></label>
                                <button type="button" class="btn btn-xs btn-primary rounded-pill px-2 py-0 extra-small fw-semibold" onclick="autoCalculateLandShare('edit')">
                                    <i class="fa-solid fa-calculator me-1"></i>Sum units
                                </button>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold text-primary">৳</span>
                                <input type="number" name="booking_value_target" id="edit_target_booking_value" class="form-control fw-bold text-primary" min="0" step="0.01" required>
                            </div>
                        </div>

                        <!-- Site Visit Target -->
                        <div class="col-lg-6 col-12">
                            <label class="form-label text-dark fw-bold small mb-1">Site Visit Ratio Target</label>
                            <div class="input-group">
                                <input type="number" name="site_visit_target" id="edit_target_site_visit" class="form-control" placeholder="Visits" min="0">
                                <span class="input-group-text bg-light text-muted">/</span>
                                <input type="number" name="site_visit_leads_target" id="edit_target_site_visit_leads" class="form-control" placeholder="Leads" min="0">
                            </div>
                        </div>

                        <!-- Booking Units Target -->
                        <div class="col-lg-12 col-12">
                            <label class="form-label text-dark fw-bold small mb-1">Total Assigned Units</label>
                            <input type="number" name="booking_target" id="edit_target_booking" class="form-control" min="0">
                        </div>

                        <div class="col-12 text-end pt-3">
                            <button type="button" class="btn btn-secondary rounded-pill px-4 me-2" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn btn-primary rounded-pill px-5 fw-bold" type="submit">
                                <i class="fa-solid fa-circle-check me-1"></i>Update Target
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal: View Target Details -->
<div class="modal fade" id="viewTargetDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-layer-group text-primary fs-5"></i>
                    <h5 class="modal-title fw-bold text-white mb-0">Assigned Projects & Flats</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="targetDetailsContent"></div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

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
        const editValEl = document.getElementById('edit_target_booking_value');
        if (editValEl) {
            editValEl.value = bookingValue ?? 0;
            editValEl.dataset.manualEdited = "true";
        }
        document.getElementById('edit_target_sales_role').value = salesRole || 'KAM';

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


    // Global manual calculator if requested by user
    window.autoCalculateLandShare = function(type) {
        const flatSelectId = type === 'edit' ? '#edit_flat_select' : '#flat_select';
        const valueInputId = type === 'edit' ? 'edit_target_booking_value' : 'booking_value_target';
        const targetInputId = type === 'edit' ? 'edit_target_booking' : 'booking_target';
        
        const selectedFlats = $(flatSelectId).find(':selected');
        let totalVal = 0;
        selectedFlats.each(function() {
            totalVal += parseFloat($(this).attr('data-price') || 0);
        });
        
        const valEl = document.getElementById(valueInputId);
        const tgtEl = document.getElementById(targetInputId);
        if (valEl) {
            valEl.value = totalVal.toFixed(2);
            valEl.dataset.manualEdited = "true";
        }
        if (tgtEl && selectedFlats.length > 0) {
            tgtEl.value = selectedFlats.length;
        }
    };

    function syncTotalsFromAPI(categorySelect, projectSelect, flatSelect, targetInput, valueInput) {
        if (!window.jQuery) return;
        const selectedFlats = $(flatSelect).find(':selected');
        
        // Always auto-fill unit count if units are selected
        if (targetInput && selectedFlats.length > 0) {
            targetInput.value = selectedFlats.length;
        }

        // Only auto-fill Land Share if not manually edited or currently 0/empty
        const shouldUpdateValue = valueInput && (!valueInput.dataset.manualEdited || valueInput.value === '' || parseFloat(valueInput.value) === 0);
        
        if (selectedFlats.length > 0) {
            if (shouldUpdateValue) {
                let totalValue = 0;
                selectedFlats.each(function() {
                    totalValue += parseFloat($(this).attr('data-price') || 0);
                });
                valueInput.value = totalValue.toFixed(2);
            }
            return;
        }
        
        const catIds = $(categorySelect).val() || [];
        const projIds = $(projectSelect).val() || [];
        
        if (catIds.length === 0 && projIds.length === 0) {
            return;
        }

        let url = `${base_url}/get-target-totals?`;
        if (catIds.length > 0) url += `category_ids[]=${catIds.join('&category_ids[]=')}&`;
        if (projIds.length > 0) url += `project_ids[]=${projIds.join('&project_ids[]=')}`;
        
        fetch(url)
        .then(res => res.json())
        .then(data => {
            if (shouldUpdateValue && valueInput) {
                valueInput.value = data.value.toFixed(2);
            }
            if (targetInput && (!targetInput.value || targetInput.value === '0')) {
                targetInput.value = data.units;
            }
        })
        .catch(err => console.error(err));
    }

    // Bind manual edit listeners
    const bValEl = document.getElementById('booking_value_target');
    if (bValEl) {
        bValEl.addEventListener('input', function() { this.dataset.manualEdited = "true"; });
    }
    const eValEl = document.getElementById('edit_target_booking_value');
    if (eValEl) {
        eValEl.addEventListener('input', function() { this.dataset.manualEdited = "true"; });
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
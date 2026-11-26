<?php
$page = "Meal Tracker Workspace";
$show_breadcrumb = true;

// Default standard amount
$standardAmount = !empty($activeProject['standard_meal_amount']) ? (float)$activeProject['standard_meal_amount'] : 120.00;
$totalDaysInPeriod = count($dateColumns);

// Default dates for new project modal: 26th of last month to 25th of this month
$defaultFromDate = date('Y-m-26', strtotime('-1 month'));
$defaultToDate = date('Y-m-25');
?>

<style>
/* Meal Tracker Aesthetics (Automatic Light & Dark Mode Compatibility) */
.meal-tracker-wrapper .nav-pills .nav-link {
    font-weight: 600;
    font-size: 12.5px;
    padding: 5px 11px;
    border-radius: 6px;
    border: 1px solid #dce1e7;
    color: #334155;
    margin-right: 0;
    background: #ffffff;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    height: 30px;
}
.meal-tracker-wrapper .nav-pills .nav-link:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #0f172a;
}
.meal-tracker-wrapper .nav-pills .nav-link.active {
    background: #0f766e !important;
    color: #ffffff !important;
    border-color: #0f766e !important;
    box-shadow: 0 2px 4px rgba(15, 118, 110, 0.2);
}

.meal-table-container {
    overflow-x: auto;
    position: relative;
    max-height: 700px;
    border: 1px solid var(--clr-border-1, #e5e7eb);
    border-radius: 8px;
    background-color: var(--clr-bg-card, transparent);
}

#mealTrackerTable {
    margin-bottom: 0;
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
    --bs-table-bg: transparent !important;
    background-color: transparent !important;
    color: inherit;
}

#mealTrackerTable th, 
#mealTrackerTable td {
    vertical-align: middle;
    padding: 8px 10px;
    white-space: nowrap;
    border-bottom: 1px solid var(--clr-border-1, #e5e7eb);
    border-right: 1px solid var(--clr-border-1, #e5e7eb);
}

/* Sticky Headers */
#mealTrackerTable thead tr:nth-child(1) th {
    position: sticky;
    top: 0;
    background-color: #0f766e !important;
    color: #ffffff !important;
    z-index: 10;
}
#mealTrackerTable thead tr:nth-child(2) th {
    position: sticky;
    top: 38px;
    background-color: #115e59 !important;
    color: #f0fdfa !important;
    z-index: 10;
}

/* Sticky Left Columns */
.sticky-col-1 { position: sticky; left: 0; z-index: 5; width: 45px; text-align: center; }
.sticky-col-2 { position: sticky; left: 45px; z-index: 5; min-width: 180px; }
.sticky-col-3 { position: sticky; left: 225px; z-index: 5; min-width: 80px; }
.sticky-col-4 { position: sticky; left: 305px; z-index: 5; min-width: 75px; text-align: center; }
.sticky-col-5 { position: sticky; left: 380px; z-index: 5; min-width: 80px; text-align: right; }
.sticky-col-6 { position: sticky; left: 460px; z-index: 5; min-width: 85px; text-align: center; }
.sticky-col-7 { position: sticky; left: 545px; z-index: 5; min-width: 105px; text-align: right; }

/* Sticky body cells match theme background (opaque to prevent bleed-through on horizontal scroll) */
#mealTrackerTable tbody td.sticky-col-1,
#mealTrackerTable tbody td.sticky-col-2,
#mealTrackerTable tbody td.sticky-col-3,
#mealTrackerTable tbody td.sticky-col-4,
#mealTrackerTable tbody td.sticky-col-5,
#mealTrackerTable tbody td.sticky-col-6,
#mealTrackerTable tbody td.sticky-col-7 {
    background-color: var(--clr-bg-card, #ffffff) !important;
    color: inherit;
}

#mealTrackerTable tbody tr:hover td.sticky-col-1,
#mealTrackerTable tbody tr:hover td.sticky-col-2,
#mealTrackerTable tbody tr:hover td.sticky-col-3,
#mealTrackerTable tbody tr:hover td.sticky-col-4,
#mealTrackerTable tbody tr:hover td.sticky-col-5,
#mealTrackerTable tbody tr:hover td.sticky-col-6,
#mealTrackerTable tbody tr:hover td.sticky-col-7 {
    background-color: var(--clr-bg-secondary, #f8fafc) !important;
}

/* Header sticky intersection columns */
#mealTrackerTable thead th.sticky-col-1,
#mealTrackerTable thead th.sticky-col-2,
#mealTrackerTable thead th.sticky-col-3,
#mealTrackerTable thead th.sticky-col-4,
#mealTrackerTable thead th.sticky-col-5,
#mealTrackerTable thead th.sticky-col-6,
#mealTrackerTable thead th.sticky-col-7 {
    z-index: 15 !important;
    background-color: #0f766e !important;
    color: #ffffff !important;
}
#mealTrackerTable thead tr:nth-child(2) th.sticky-col-1,
#mealTrackerTable thead tr:nth-child(2) th.sticky-col-2,
#mealTrackerTable thead tr:nth-child(2) th.sticky-col-3,
#mealTrackerTable thead tr:nth-child(2) th.sticky-col-4,
#mealTrackerTable thead tr:nth-child(2) th.sticky-col-5,
#mealTrackerTable thead tr:nth-child(2) th.sticky-col-6,
#mealTrackerTable thead tr:nth-child(2) th.sticky-col-7 {
    z-index: 15 !important;
    background-color: #115e59 !important;
    color: #ffffff !important;
}

.meal-checkbox {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: #0f766e;
}
.weekend-col {
    background-color: rgba(239, 68, 68, 0.04) !important;
}
.total-cell-highlight {
    font-weight: 700;
    color: #0f766e;
}

/* Guest Row styling */
#guestMealRow td {
    background-color: #fef9ee !important;
    color: inherit;
}

/* Dark Mode explicit overrides to guarantee seamless auto-adaptation */
[bd-theme="bd-theme-dark"] .meal-table-container {
    border-color: var(--clr-border-1, #374151) !important;
    background-color: var(--clr-bg-card, #1f2937) !important;
}
[bd-theme="bd-theme-dark"] #mealTrackerTable {
    color: var(--clr-text-body, #f9fafb) !important;
}
[bd-theme="bd-theme-dark"] #mealTrackerTable td,
[bd-theme="bd-theme-dark"] #mealTrackerTable th {
    border-color: var(--clr-border-1, #374151) !important;
}
[bd-theme="bd-theme-dark"] #mealTrackerTable thead tr:nth-child(1) th {
    background-color: #0f766e !important;
    color: #ffffff !important;
}
[bd-theme="bd-theme-dark"] #mealTrackerTable thead tr:nth-child(2) th {
    background-color: #115e59 !important;
    color: #f0fdfa !important;
}
[bd-theme="bd-theme-dark"] #mealTrackerTable thead th.sticky-col-1,
[bd-theme="bd-theme-dark"] #mealTrackerTable thead th.sticky-col-2,
[bd-theme="bd-theme-dark"] #mealTrackerTable thead th.sticky-col-3,
[bd-theme="bd-theme-dark"] #mealTrackerTable thead th.sticky-col-4,
[bd-theme="bd-theme-dark"] #mealTrackerTable thead th.sticky-col-5,
[bd-theme="bd-theme-dark"] #mealTrackerTable thead th.sticky-col-6,
[bd-theme="bd-theme-dark"] #mealTrackerTable thead th.sticky-col-7 {
    background-color: #0f766e !important;
    color: #ffffff !important;
}
[bd-theme="bd-theme-dark"] #mealTrackerTable thead tr:nth-child(2) th.sticky-col-1,
[bd-theme="bd-theme-dark"] #mealTrackerTable thead tr:nth-child(2) th.sticky-col-2,
[bd-theme="bd-theme-dark"] #mealTrackerTable thead tr:nth-child(2) th.sticky-col-3,
[bd-theme="bd-theme-dark"] #mealTrackerTable thead tr:nth-child(2) th.sticky-col-4,
[bd-theme="bd-theme-dark"] #mealTrackerTable thead tr:nth-child(2) th.sticky-col-5,
[bd-theme="bd-theme-dark"] #mealTrackerTable thead tr:nth-child(2) th.sticky-col-6,
[bd-theme="bd-theme-dark"] #mealTrackerTable thead tr:nth-child(2) th.sticky-col-7 {
    background-color: #115e59 !important;
    color: #ffffff !important;
}
[bd-theme="bd-theme-dark"] #mealTrackerTable tbody td.sticky-col-1,
[bd-theme="bd-theme-dark"] #mealTrackerTable tbody td.sticky-col-2,
[bd-theme="bd-theme-dark"] #mealTrackerTable tbody td.sticky-col-3,
[bd-theme="bd-theme-dark"] #mealTrackerTable tbody td.sticky-col-4,
[bd-theme="bd-theme-dark"] #mealTrackerTable tbody td.sticky-col-5,
[bd-theme="bd-theme-dark"] #mealTrackerTable tbody td.sticky-col-6,
[bd-theme="bd-theme-dark"] #mealTrackerTable tbody td.sticky-col-7 {
    background-color: var(--clr-bg-card, #1f2937) !important;
    color: var(--clr-text-body, #f9fafb) !important;
}
[bd-theme="bd-theme-dark"] #mealTrackerTable tbody tr:hover td.sticky-col-1,
[bd-theme="bd-theme-dark"] #mealTrackerTable tbody tr:hover td.sticky-col-2,
[bd-theme="bd-theme-dark"] #mealTrackerTable tbody tr:hover td.sticky-col-3,
[bd-theme="bd-theme-dark"] #mealTrackerTable tbody tr:hover td.sticky-col-4,
[bd-theme="bd-theme-dark"] #mealTrackerTable tbody tr:hover td.sticky-col-5,
[bd-theme="bd-theme-dark"] #mealTrackerTable tbody tr:hover td.sticky-col-6,
[bd-theme="bd-theme-dark"] #mealTrackerTable tbody tr:hover td.sticky-col-7 {
    background-color: var(--clr-bg-secondary, #374151) !important;
}
[bd-theme="bd-theme-dark"] .weekend-col {
    background-color: rgba(255, 255, 255, 0.03) !important;
}
[bd-theme="bd-theme-dark"] #guestMealRow td {
    background-color: #272115 !important;
    color: var(--clr-text-body, #f9fafb) !important;
}

/* Modal Fixes & Stacking Context */
#createMealProjectModal.modal {
    z-index: 99999 !important;
}
#createMealProjectModal .modal-dialog {
    max-width: 520px !important;
    width: 100% !important;
    margin: 1.75rem auto !important;
}
</style>

<div class="meal-tracker-wrapper mb-4">
    <!-- Top Filter & Action Header (Compact Single-Line Bar) -->
    <div class="bg-white rounded-3 px-3 py-2 mb-2" style="border: 1px solid #e9ecef; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
        <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap: 10px;">
            <!-- Left: Project Selector & Project Actions -->
            <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                <span class="fw-semibold text-secondary" style="font-size: 12.5px; white-space: nowrap;">Meal Project:</span>
                <form method="GET" action="<?= $GLOBALS['base_url'] ?>/meal-tracker" class="d-inline-flex align-items-center m-0" id="projectSelectForm">
                    <select name="project_id" class="form-select form-select-sm text-dark fw-medium" style="width: 240px; height: 32px; font-size: 12.5px; border-color: #dee2e6;" onchange="this.form.submit()">
                        <?php if (empty($mealProjects)): ?>
                            <option value="">No Meal Projects Found</option>
                        <?php else: ?>
                            <?php foreach ($mealProjects as $p): ?>
                                <option value="<?= (int)$p['id'] ?>" <?= ($selectedProjectId == $p['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['name']) ?> (<?= date('d M', strtotime($p['from_date'])) ?> - <?= date('d M y', strtotime($p['to_date'])) ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <input type="hidden" name="branch_id" value="<?= (int)$selectedBranchId ?>">
                </form>

                <button type="button" class="btn btn-sm btn-outline-primary fw-semibold d-inline-flex align-items-center btn-open-meal-modal"
                        data-bs-toggle="modal" data-bs-target="#createMealProjectModal"
                        style="height: 32px; font-size: 12px; padding: 0 10px; gap: 5px;">
                    <i class="fa-solid fa-plus"></i><span>New</span>
                </button>

                <?php if (!empty($mealProjects)): ?>
                    <button type="button" class="btn btn-sm btn-outline-danger fw-semibold d-inline-flex align-items-center btn-open-delete-meal-modal" 
                            data-bs-toggle="modal" data-bs-target="#deleteMealProjectModal"
                            style="height: 32px; font-size: 12px; padding: 0 10px; gap: 5px;"
                            title="Delete meal projects and all associated data">
                        <i class="fa-regular fa-trash-can"></i><span>Delete</span>
                    </button>
                <?php endif; ?>
            </div>

            <!-- Middle: Compact Cycle & Rate Pills -->
            <?php if ($activeProject): ?>
                <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                    <span class="d-inline-flex align-items-center px-2.5 rounded fw-semibold"
                          style="height: 32px; font-size: 12px; padding: 0 10px; gap: 6px; background-color: #f0fdfa; color: #0f766e; border: 1px solid #99f6e4;">
                        <i class="fa-regular fa-calendar-days"></i>
                        <span><?= date('d M Y', strtotime($activeProject['from_date'])) ?> &rarr; <?= date('d M Y', strtotime($activeProject['to_date'])) ?> (<?= $totalDaysInPeriod ?>d)</span>
                    </span>
                    <button type="button" class="btn btn-sm d-inline-flex align-items-center btn-open-edit-rate-modal"
                            data-bs-toggle="modal" data-bs-target="#editStandardRateModal"
                            title="Click to edit Standard Meal Rate for this project"
                            style="height: 32px; font-size: 12px; padding: 0 10px; gap: 6px; background: #f8fafc; color: #334155; border: 1px solid #dce1e7; border-radius: 6px; font-weight: 500;">
                        <i class="fa-solid fa-utensils text-primary" style="font-size: 11px;"></i>
                        <span>Rate: <strong class="text-dark">৳<?= number_format($standardAmount, 2) ?></strong></span>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2" style="font-size: 10.5px; font-weight: 600;">
                            <i class="fa-solid fa-pen-to-square me-1"></i>Edit
                        </span>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Right: Action Buttons -->
            <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                <button type="button" class="btn btn-sm btn-outline-primary fw-semibold d-inline-flex align-items-center" id="btnActiveMealReport"
                        style="height: 32px; font-size: 12px; padding: 0 10px; gap: 5px;"
                        title="View office-wise active meal counts & employee list (PDF / Excel)">
                    <i class="fa-solid fa-clipboard-list"></i><span>Active Meal Report</span>
                </button>
                <button type="button" class="btn btn-sm btn-outline-success fw-semibold d-inline-flex align-items-center" id="btnSyncAttendance"
                        style="height: 32px; font-size: 12px; padding: 0 10px; gap: 5px;"
                        title="Sync Present Attendance & Opt-Outs from Tipsoi and Manual Attendance">
                    <i class="fa-solid fa-arrows-rotate"></i><span>Sync Attendance</span>
                </button>
                <a href="<?= $GLOBALS['base_url'] ?>/meal-tracker-history" class="btn btn-sm btn-outline-secondary fw-semibold d-inline-flex align-items-center"
                   style="height: 32px; font-size: 12px; padding: 0 10px; gap: 5px;">
                    <i class="fa-solid fa-chart-line"></i><span>History</span>
                </a>
                <button type="button" class="btn btn-sm btn-success fw-semibold d-inline-flex align-items-center shadow-sm" id="btnSaveMealGrid"
                        style="height: 32px; font-size: 12px; padding: 0 12px; gap: 5px; background-color: #0f766e; border-color: #0f766e;">
                    <i class="fa-solid fa-floppy-disk"></i><span>Save Meal Records</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Office Branch Navigation Tabs & Export Toolbar -->
    <div class="bg-white rounded-3 px-3 py-2 mb-2" style="border: 1px solid #e9ecef; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
        <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap: 10px;">
            <ul class="nav nav-pills flex-wrap mb-0" id="branchTabs" style="gap: 6px;">
                <?php foreach ($branches as $branch): ?>
                    <?php $bId = (int)$branch['id']; ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($selectedBranchId === $bId) ? 'active' : '' ?>" 
                           href="<?= $GLOBALS['base_url'] ?>/meal-tracker?project_id=<?= (int)$selectedProjectId ?>&branch_id=<?= $bId ?>">
                            <i class="fa-solid fa-building" style="font-size: 11px;"></i>
                            <span><?= htmlspecialchars($branch['name']) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="d-flex align-items-center flex-wrap" style="gap: 10px;">
                <?php if ($activeProject): ?>
                    <div class="d-inline-flex align-items-center flex-wrap rounded"
                         style="gap: 6px; padding: 3px 8px; background-color: #f8fafc !important; border: 1px solid #dce1e7 !important;">
                        <span class="fw-semibold" style="font-size: 11.5px; color: #475569; white-space: nowrap;">
                            <i class="fa-regular fa-calendar text-success me-1"></i>Export Range:
                        </span>
                        <input type="date" id="mealExportFromDate" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($activeProject['from_date'] ?? '') ?>"
                               min="<?= htmlspecialchars($activeProject['from_date'] ?? '') ?>"
                               max="<?= htmlspecialchars($activeProject['to_date'] ?? '') ?>"
                               style="width: 122px; height: 26px; font-size: 11.5px; padding: 1px 6px; background-color: #ffffff !important; color: #1e293b !important; border: 1px solid #cbd5e1 !important; border-radius: 4px;"
                               title="Export From Date (Default: Project Start Date)">
                        <span class="text-muted fw-bold" style="font-size: 11px;">&rarr;</span>
                        <input type="date" id="mealExportToDate" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($activeProject['to_date'] ?? '') ?>"
                               min="<?= htmlspecialchars($activeProject['from_date'] ?? '') ?>"
                               max="<?= htmlspecialchars($activeProject['to_date'] ?? '') ?>"
                               style="width: 122px; height: 26px; font-size: 11.5px; padding: 1px 6px; background-color: #ffffff !important; color: #1e293b !important; border: 1px solid #cbd5e1 !important; border-radius: 4px;"
                               title="Export To Date (Default: Project End Date)">
                        <button type="button" id="btnExportMealExcel" onclick="if(window.exportMealWorkspaceExcel) window.exportMealWorkspaceExcel();" class="btn btn-sm btn-success fw-semibold d-inline-flex align-items-center"
                                style="height: 26px; font-size: 11.5px; gap: 5px; padding: 0 10px; background-color: #15803d; border-color: #15803d; border-radius: 4px;"
                                title="Export formatted Excel sheet for the selected date range">
                            <i class="fa-solid fa-file-excel"></i>
                            <span>Export Excel</span>
                        </button>
                    </div>
                <?php endif; ?>
                <span class="d-inline-flex align-items-center px-2 rounded"
                      style="height: 30px; font-size: 12px; gap: 5px; background: #f8fafc; border: 1px solid #e2e8f0; color: #475569;">
                    <span>Active on Meal:</span>
                    <strong class="text-dark"><?= count($employees) ?></strong>
                </span>
            </div>
        </div>
    </div>

    <!-- Main Meal Tracker Matrix Table -->
    <div class="card__wrapper p-0">
        <div class="meal-table-container">
            <table class="table table-hover align-middle" id="mealTrackerTable">
                <thead>
                    <!-- Row 1: Header Titles and Daily Date Headers -->
                    <tr>
                        <th class="sticky-col-1">SL</th>
                        <th class="sticky-col-2">Employee Name</th>
                        <th class="sticky-col-3">ID</th>
                        <th class="sticky-col-4">Rate %</th>
                        <th class="sticky-col-5">Rate (৳)</th>
                        <th class="sticky-col-6">Total Meals</th>
                        <th class="sticky-col-7">Deduction (৳)</th>
                        <?php foreach ($dateColumns as $col): ?>
                            <th class="text-center meal-date-th <?= $col['is_weekend'] ? 'weekend-col' : '' ?>"
                                data-date="<?= htmlspecialchars($col['date']) ?>"
                                data-label="<?= htmlspecialchars($col['day_num'] . ' ' . $col['month_short']) ?>"
                                data-dayname="<?= htmlspecialchars($col['day_name']) ?>"
                                data-weekend="<?= $col['is_weekend'] ? '1' : '0' ?>"
                                style="min-width: 48px;">
                                <?= $col['day_num'] ?> <?= $col['month_short'] ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                    <!-- Row 2: Days of week & Column check all / totals -->
                    <tr>
                        <th class="sticky-col-1">#</th>
                        <th class="sticky-col-2">Day &rarr;</th>
                        <th class="sticky-col-3">-</th>
                        <th class="sticky-col-4">-</th>
                        <th class="sticky-col-5">-</th>
                        <th class="sticky-col-6" id="grandTotalEmployeeMeals">0</th>
                        <th class="sticky-col-7" id="grandTotalEmployeeDeduction">৳0.00</th>
                        <?php foreach ($dateColumns as $col): ?>
                            <th class="text-center <?= $col['is_weekend'] ? 'weekend-col' : '' ?>" style="font-size: 11px;">
                                <div><?= $col['day_name'] ?></div>
                                <div class="col-daily-count fw-bold text-light" data-date="<?= $col['date'] ?>">0</div>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody id="mealTableBody">
                    <?php if (empty($activeProject)): ?>
                        <tr>
                            <td colspan="<?= 7 + $totalDaysInPeriod ?>" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-circle-exclamation fs-3 text-warning mb-2"></i>
                                <p class="mb-0">No active meal project selected. Please create or select a project above.</p>
                            </td>
                        </tr>
                    <?php elseif (empty($employees)): ?>
                        <tr>
                            <td colspan="<?= 7 + $totalDaysInPeriod ?>" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-users-slash fs-3 text-secondary mb-2"></i>
                                <p class="mb-0">No active employees found with meal deduction enabled (<code>is_meal_deduction = 1</code>) for this branch.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $sl = 1;
                        foreach ($employees as $emp): 
                            $empId = (int)$emp['id'];
                            $pct = isset($emp['per_meal_percentage']) ? (float)$emp['per_meal_percentage'] : 100.00;
                            // Effective daily rate = standardAmount * (pct / 100)
                            $effectiveRate = round($standardAmount * ($pct / 100), 2);
                        ?>
                            <tr class="emp-meal-row"
                                data-emp-id="<?= $empId ?>"
                                data-rate="<?= $effectiveRate ?>"
                                data-pct="<?= number_format($pct, 0) ?>"
                                data-emp-name="<?= htmlspecialchars($emp['full_name'], ENT_QUOTES) ?>"
                                data-emp-code="<?= htmlspecialchars($emp['employee_id'], ENT_QUOTES) ?>"
                                data-designation="<?= htmlspecialchars($emp['designation_name'] ?? '', ENT_QUOTES) ?>">
                                <td class="sticky-col-1 text-muted"><?= $sl++ ?></td>
                                <td class="sticky-col-2 fw-semibold">
                                    <?= htmlspecialchars($emp['full_name']) ?>
                                    <div class="small text-muted" style="font-size:11px;"><?= htmlspecialchars($emp['designation_name'] ?? '') ?></div>
                                </td>
                                <td class="sticky-col-3">
                                    <span class="badge border" style="background: var(--clr-bg-secondary, rgba(0,0,0,0.05)); color: inherit;"><?= htmlspecialchars($emp['employee_id']) ?></span>
                                </td>
                                <td class="sticky-col-4">
                                    <span class="badge bg-info "><?= number_format($pct, 0) ?>%</span>
                                </td>
                                <td class="sticky-col-5 fw-semibold text-muted">
                                    ৳<?= number_format($effectiveRate, 2) ?>
                                </td>
                                <td class="sticky-col-6 fw-bold text-center row-meal-count text-primary fs-6">
                                    0
                                </td>
                                <td class="sticky-col-7 fw-bold text-end row-meal-deduction text-danger fs-6">
                                    ৳0.00
                                </td>

                                <!-- Daily Checkboxes -->
                                <?php foreach ($dateColumns as $col): ?>
                                    <?php 
                                    $d = $col['date'];
                                    $isOptedOut = isset($optOuts[$empId][$d]) && $optOuts[$empId][$d] === 'opt_out';
                                    if (isset($records[$empId][$d])) {
                                        $isChecked = ((int)$records[$empId][$d] === 1);
                                    } else {
                                        $isChecked = !$isOptedOut;
                                    }
                                    ?>
                                    <td class="text-center <?= $col['is_weekend'] ? 'weekend-col' : '' ?> <?= ($isOptedOut && !$isChecked) ? 'bg-danger bg-opacity-10' : '' ?>" style="position: relative;">
                                        <?php if ($isOptedOut && !$isChecked): ?>
                                            <span class="badge bg-danger p-1 d-block mb-1" style="font-size: 8px; cursor: help;" title="Off by Employee (১২টার আগে মিল অফ করা হয়েছে)">
                                                <i class="fa-solid fa-ban"></i> Off
                                            </span>
                                        <?php endif; ?>
                                        <input type="checkbox" 
                                               class="meal-checkbox" 
                                               data-emp-id="<?= $empId ?>" 
                                               data-date="<?= $d ?>" 
                                               data-opt-out="<?= $isOptedOut ? '1' : '0' ?>"
                                               <?= $isChecked ? 'checked' : '' ?>
                                               title="<?= htmlspecialchars($emp['full_name']) ?> (<?= $col['day_num'] ?> <?= $col['month_short'] ?>)<?= $isOptedOut ? ' [Employee Opted Out]' : '' ?>">
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>

                        <!-- Guest / Visitor Meals Row -->
                        <tr class="table-warning bg-opacity-25" id="guestMealRow">
                            <td class="sticky-col-1 text-center font-monospace">G</td>
                            <td class="sticky-col-2 fw-bold">
                                <i class="fa-solid fa-users me-1 text-warning"></i> Guest / Visitor Meals
                            </td>
                            <td class="sticky-col-3"><span class="badge bg-warning" style="color: #000 !important;">Branch</span></td>
                            <td class="sticky-col-4">-</td>
                            <td class="sticky-col-5 fw-semibold">৳<?= number_format($standardAmount, 2) ?></td>
                            <td class="sticky-col-6 fw-bold text-center" id="totalGuestMeals">0</td>
                            <td class="sticky-col-7 fw-bold text-end" id="totalGuestCost">৳0.00</td>

                            <?php foreach ($dateColumns as $col): ?>
                                <?php 
                                $d = $col['date'];
                                $guestCount = isset($guestRecords[$d]) ? (int)$guestRecords[$d] : 0;
                                ?>
                                <td class="text-center p-1 <?= $col['is_weekend'] ? 'weekend-col' : '' ?>">
                                    <input type="number" min="0" max="99" 
                                           class="form-control form-control-sm text-center guest-meal-input px-1" 
                                           style="width: 44px; height: 26px; font-size: 11px;"
                                           data-date="<?= $d ?>" 
                                           value="<?= $guestCount > 0 ? $guestCount : '' ?>"
                                           placeholder="0">
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Modal: Create Meal Project (Placed at bottom outside scrollable containers) -->
<!-- ========================================================================= -->
<div class="modal custom-modal fade" id="createMealProjectModal" tabindex="-1" aria-labelledby="createMealProjectLabel" aria-hidden="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header pb-3 mb-2 border-bottom">
                <h5 class="modal-title fw-bold" id="createMealProjectLabel">
                    <i class="fa-solid fa-calendar-plus me-2" style="color: #0f766e;"></i> Create New Meal Project
                </h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body p-0">
                <form method="POST" action="<?= $GLOBALS['base_url'] ?>/meal-project-store" id="formCreateMealProject">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Project Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Aug-Sep 2026 Meal Cycle" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Standard Meal Amount (৳) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text fw-bold">৳</span>
                            <input type="number" step="0.01" name="standard_meal_amount" class="form-control" value="120.00" required>
                        </div>
                        <small class="text-muted">Standard lunch cost before employee percentage subsidy.</small>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">From Date <span class="text-danger">*</span></label>
                            <input type="date" name="from_date" class="form-control" value="<?= $defaultFromDate ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">To Date <span class="text-danger">*</span></label>
                            <input type="date" name="to_date" class="form-control" value="<?= $defaultToDate ?>" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Link with Payroll Project (Optional)</label>
                        <select name="payroll_project_id" class="form-select">
                            <option value="">-- None / Standalone --</option>
                            <?php foreach ($payrollProjects as $pr): ?>
                                <option value="<?= (int)$pr['id'] ?>">
                                    <?= htmlspecialchars($pr['name']) ?> (<?= htmlspecialchars($pr['date'] ?? '') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="modal-footer px-0 pb-0 pt-3 border-top d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold" style="background: #0f766e; border-color: #0f766e;">
                            <i class="fa-solid fa-save me-1"></i> Create Project
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Modal: Delete Meal Project (Manage & Clean Duplicates)                    -->
<!-- ========================================================================= -->
<div class="modal fade" id="deleteMealProjectModal" tabindex="-1" aria-labelledby="deleteMealProjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header bg-danger  py-3">
                <h5 class="modal-title fw-bold" id="deleteMealProjectModalLabel">
                    <i class="fa-solid fa-trash-can me-2"></i> Delete Meal Project
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <?php if ($activeProject): ?>
                    <!-- Active Project Quick Delete Box -->
                    <div class="alert alert-warning border-warning d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4 shadow-sm" style="border-radius: 8px;">
                        <div>
                            <div class="fw-bold text-dark fs-6 mb-1">
                                <i class="fa-solid fa-triangle-exclamation text-danger me-1"></i> Current Active Project:
                            </div>
                            <div class="text-secondary fw-semibold">
                                <span class="badge bg-danger me-1">ID: #<?= $activeProject['id'] ?></span>
                                <span class="text-dark fw-bold fs-6"><?= htmlspecialchars($activeProject['name']) ?></span>
                                <span class="badge  text-dark border ms-1">
                                    <?= date('d M Y', strtotime($activeProject['from_date'])) ?> &rarr; <?= date('d M Y', strtotime($activeProject['to_date'])) ?>
                                </span>
                            </div>
                            <div class="small text-danger mt-1">
                                <i class="fa-solid fa-circle-info me-1"></i> Deleting will permanently remove this project and ALL its meal records and guest data.
                            </div>
                        </div>
                        <form method="POST" action="<?= $GLOBALS['base_url'] ?>/meal-project-delete" onsubmit="return confirm('Are you sure you want to permanently delete \'<?= htmlspecialchars(addslashes($activeProject['name'])) ?>\' (ID: <?= $activeProject['id'] ?>) and ALL its meal records? This cannot be undone.');" style="margin:0;">
                            <input type="hidden" name="project_id" value="<?= (int)$activeProject['id'] ?>">
                            <button type="submit" class="btn btn-danger fw-bold shadow-sm px-3 py-2">
                                <i class="fa-solid fa-trash-can me-1"></i> Delete Active Project
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- List of All Projects (Manage & Clean Duplicates) -->
                <div class="card border shadow-none" style="border-radius: 8px;">
                    <div class="card-header  py-2 d-flex align-items-center justify-content-between">
                        <span class="fw-bold text-dark">
                            <i class="fa-solid fa-list-check me-1 text-primary"></i> All Meal Projects (Duplicate Clean-up)
                        </span>
                        <span class="badge bg-secondary"><?= count($mealProjects ?? []) ?> Projects Found</span>
                    </div>
                    <div class="card-body p-0 table-responsive" style="max-height: 280px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="width: 70px;">ID</th>
                                    <th>Project Name</th>
                                    <th>Cycle Duration</th>
                                    <th style="width: 90px;">Status</th>
                                    <th style="width: 120px;" class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($mealProjects)): ?>
                                    <?php foreach ($mealProjects as $p): ?>
                                        <tr class="<?= ($selectedProjectId == $p['id']) ? 'table-warning' : '' ?>">
                                            <td class="fw-bold text-muted">#<?= $p['id'] ?></td>
                                            <td>
                                                <span class="fw-semibold text-dark"><?= htmlspecialchars($p['name']) ?></span>
                                                <?php if (!empty($p['payroll_project_id'])): ?>
                                                    <span class="badge bg-info text-dark ms-1" style="font-size: 0.75rem;">Payroll Linked</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-secondary small">
                                                <?= date('d M Y', strtotime($p['from_date'])) ?> &rarr; <?= date('d M Y', strtotime($p['to_date'])) ?>
                                            </td>
                                            <td>
                                                <?php if ($selectedProjectId == $p['id']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge  text-secondary border">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <form method="POST" action="<?= $GLOBALS['base_url'] ?>/meal-project-delete" onsubmit="return confirm('Are you sure you want to permanently delete \'<?= htmlspecialchars(addslashes($p['name'])) ?>\' (ID: <?= $p['id'] ?>) and ALL its meal data?');" style="display:inline-block; margin:0;">
                                                    <input type="hidden" name="project_id" value="<?= (int)$p['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger fw-semibold" title="Delete this meal project">
                                                        <i class="fa-solid fa-trash-can me-1"></i> Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">No meal projects found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer  py-2">
                <button type="button" class="btn btn-secondary px-4 fw-semibold" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Modal: Edit Standard Meal Rate                                            -->
<!-- ========================================================================= -->
<div class="modal fade" id="editStandardRateModal" tabindex="-1" aria-labelledby="editStandardRateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white py-3" style="background: #0f766e !important;">
                <h5 class="modal-title fw-bold" id="editStandardRateModalLabel">
                    <i class="fa-solid fa-pen-to-square me-2"></i> Edit Standard Meal Rate
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <?php if ($activeProject): ?>
                    <form method="POST" action="<?= $GLOBALS['base_url'] ?>/meal-project-update-rate" id="formEditStandardRate">
                        <input type="hidden" name="project_id" value="<?= (int)$activeProject['id'] ?>">
                        <input type="hidden" name="branch_id" value="<?= (int)$selectedBranchId ?>">

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-semibold mb-1">Target Meal Project</label>
                            <input type="text" class="form-control  fw-semibold" value="<?= htmlspecialchars($activeProject['name']) ?> (<?= date('d M', strtotime($activeProject['from_date'])) ?> - <?= date('d M Y', strtotime($activeProject['to_date'])) ?>)" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Standard Meal Rate (৳) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text fw-bold ">৳</span>
                                <input type="number" step="0.01" min="1" name="standard_meal_amount" class="form-control fw-bold fs-4 text-success" value="<?= number_format($standardAmount, 2, '.', '') ?>" required autofocus>
                            </div>
                            <small class="text-muted d-block mt-2">
                                <i class="fa-solid fa-circle-info text-primary me-1"></i>
                                Standard lunch cost before subsidy. Updating this rate will instantly recompute all individual employee deductions based on their subsidy percentages (e.g., 50%, 100%).
                            </small>
                        </div>

                        <div class="modal-footer px-0 pb-0 pt-3 border-top d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4 fw-bold" style="background: #0f766e; border-color: #0f766e;">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Update Rate
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="text-center py-3 text-muted">
                        <i class="fa-solid fa-circle-exclamation fs-3 text-warning mb-2"></i>
                        <p class="mb-0">No active meal project selected.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Modal: Active Meal Report (Office-wise Counts & Employee List)            -->
<!-- ========================================================================= -->
<div class="modal fade" id="activeMealReportModal" tabindex="-1" aria-labelledby="activeMealReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #0f766e, #115e59);">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="activeMealReportModalLabel">
                        <i class="fa-solid fa-clipboard-list me-2"></i>Active Meal Report (Office-Wise)
                    </h5>
                    <small class="opacity-75" id="activeMealReportSubtitle">
                        <?= $activeProject ? htmlspecialchars($activeProject['name']) : 'Meal Project' ?>
                    </small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4">
                <?php
                    $defaultReportDate = date('Y-m-d');
                    if ($activeProject && !empty($activeProject['from_date']) && !empty($activeProject['to_date'])) {
                        if ($defaultReportDate < $activeProject['from_date'] || $defaultReportDate > $activeProject['to_date']) {
                            $defaultReportDate = $activeProject['from_date'];
                        }
                    }
                ?>
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-3 mb-3 border-bottom">
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <label for="activeMealReportDate" class="fw-semibold mb-0" style="font-size: 13px;">
                            <i class="fa-regular fa-calendar-days text-success me-1"></i>Report Date:
                        </label>
                        <input type="date" id="activeMealReportDate" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($defaultReportDate) ?>"
                               min="<?= htmlspecialchars($activeProject['from_date'] ?? '') ?>"
                               max="<?= htmlspecialchars($activeProject['to_date'] ?? '') ?>"
                               style="width: 155px;">
                        <button type="button" class="btn btn-sm btn-outline-secondary fw-semibold" id="btnReloadActiveMealReport">
                            <i class="fa-solid fa-rotate me-1"></i>Refresh
                        </button>
                    </div>
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <button type="button" class="btn btn-sm btn-danger fw-semibold d-inline-flex align-items-center gap-1" id="btnExportActiveMealPdf" disabled>
                            <i class="fa-solid fa-file-pdf"></i><span>Download PDF</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-success fw-semibold d-inline-flex align-items-center gap-1" id="btnExportActiveMealExcel" disabled
                                style="background-color: #15803d; border-color: #15803d;">
                            <i class="fa-solid fa-file-excel"></i><span>Download Excel</span>
                        </button>
                    </div>
                </div>

                <div id="activeMealReportContent">
                    <div class="text-center py-5 text-muted">
                        <div class="spinner-border text-success mb-2" role="status"></div>
                        <div>Loading active meal report...</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Frontend JavaScript (Safe Ready Checker Pattern as per rules)              -->
<!-- ========================================================================= -->
<script>
(function() {
    function initModuleScripts() {
        if (typeof jQuery === 'undefined' || typeof bootstrap === 'undefined') {
            setTimeout(initModuleScripts, 50);
            return;
        }

        jQuery(function($) {
            const baseUrl = "<?= $GLOBALS['base_url'] ?>";
            const currentProjectId = <?= (int)$selectedProjectId ?>;
            const currentBranchId = <?= (int)$selectedBranchId ?>;
            const standardMealAmount = <?= (float)$standardAmount ?>;

            // Rule 4 (Stacking Context Fix): Append modals to body directly so they are never trapped in .page__body-wrapper
            if ($('#createMealProjectModal').parent().prop('tagName') !== 'BODY') {
                $('#createMealProjectModal').appendTo('body');
            }
            if ($('#deleteMealProjectModal').parent().prop('tagName') !== 'BODY') {
                $('#deleteMealProjectModal').appendTo('body');
            }
            if ($('#editStandardRateModal').parent().prop('tagName') !== 'BODY') {
                $('#editStandardRateModal').appendTo('body');
            }

            // Rule 3 (Dual Event Binding): Direct delegated click fallback for modal triggers
            $(document).on('click', '.btn-open-meal-modal, [data-bs-target="#createMealProjectModal"]', function(e) {
                e.preventDefault();
                const modalEl = document.getElementById('createMealProjectModal');
                if (modalEl) {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        const modalObj = bootstrap.Modal.getOrCreateInstance(modalEl);
                        modalObj.show();
                    } else if (typeof $ !== 'undefined' && $.fn.modal) {
                        $(modalEl).modal('show');
                    }
                }
            });

            $(document).on('click', '.btn-open-delete-meal-modal, [data-bs-target="#deleteMealProjectModal"]', function(e) {
                e.preventDefault();
                const modalEl = document.getElementById('deleteMealProjectModal');
                if (modalEl) {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        const modalObj = bootstrap.Modal.getOrCreateInstance(modalEl);
                        modalObj.show();
                    } else if (typeof $ !== 'undefined' && $.fn.modal) {
                        $(modalEl).modal('show');
                    }
                }
            });

            $(document).on('click', '.btn-open-edit-rate-modal, [data-bs-target="#editStandardRateModal"]', function(e) {
                e.preventDefault();
                const modalEl = document.getElementById('editStandardRateModal');
                if (modalEl) {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        const modalObj = bootstrap.Modal.getOrCreateInstance(modalEl);
                        modalObj.show();
                    } else if (typeof $ !== 'undefined' && $.fn.modal) {
                        $(modalEl).modal('show');
                    }
                }
            });

            // Recalculate row totals and column totals
            function calculateTotals() {
                let grandEmpMeals = 0;
                let grandEmpDeduction = 0;

                // 1. Employee Rows
                $('.emp-meal-row').each(function() {
                    const row = $(this);
                    const rate = parseFloat(row.data('rate')) || 0;
                    const checkedCount = row.find('.meal-checkbox:checked').length;
                    const deduction = checkedCount * rate;

                    row.find('.row-meal-count').text(checkedCount);
                    row.find('.row-meal-deduction').text('৳' + deduction.toFixed(2));

                    grandEmpMeals += checkedCount;
                    grandEmpDeduction += deduction;
                });

                $('#grandTotalEmployeeMeals').text(grandEmpMeals);
                $('#grandTotalEmployeeDeduction').text('৳' + grandEmpDeduction.toFixed(2));

                // 2. Daily Column Totals
                $('.col-daily-count').each(function() {
                    const date = $(this).data('date');
                    const empDailyChecked = $(`.meal-checkbox[data-date="${date}"]:checked`).length;
                    const guestDaily = parseInt($(`.guest-meal-input[data-date="${date}"]`).val()) || 0;
                    const totalDaily = empDailyChecked + guestDaily;
                    $(this).text(totalDaily);
                });

                // 3. Guest Row Totals
                let totalGuestCount = 0;
                $('.guest-meal-input').each(function() {
                    const val = parseInt($(this).val()) || 0;
                    totalGuestCount += val;
                });
                $('#totalGuestMeals').text(totalGuestCount);
                $('#totalGuestCost').text('৳' + (totalGuestCount * standardMealAmount).toFixed(2));
            }

            // Bind change events for interactive live feedback
            $(document).on('change', '.meal-checkbox', function() {
                calculateTotals();
            });

            $(document).on('input change', '.guest-meal-input', function() {
                calculateTotals();
            });

            // Initial calculation on page load
            calculateTotals();

            // Save Meal Records via AJAX
            $('#btnSaveMealGrid').on('click', function() {
                if (!currentProjectId || !currentBranchId) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Missing Context',
                        text: 'Please select a Meal Project and Office Branch first.'
                    });
                    return;
                }

                const btn = $(this);
                const originalHtml = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Saving...');

                // Collect employee checkboxes
                const records = [];
                $('.meal-checkbox').each(function() {
                    records.push({
                        employee_id: $(this).data('emp-id'),
                        meal_date: $(this).data('date'),
                        is_consumed: $(this).is(':checked') ? 1 : 0
                    });
                });

                // Collect guest inputs
                const guestRecords = {};
                $('.guest-meal-input').each(function() {
                    const d = $(this).data('date');
                    const cnt = parseInt($(this).val()) || 0;
                    guestRecords[d] = cnt;
                });

                fetch(`${baseUrl}/meal-tracker-save`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        project_id: currentProjectId,
                        branch_id: currentBranchId,
                        records: records,
                        guest_records: guestRecords
                    })
                })
                .then(r => r.json())
                .then(res => {
                    btn.prop('disabled', false).html(originalHtml);
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Saved Successfully!',
                            text: res.message || 'Meal records updated.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Save Failed',
                            text: res.message || 'An error occurred while saving.'
                        });
                    }
                })
                .catch(err => {
                    btn.prop('disabled', false).html(originalHtml);
                    console.error("Save error:", err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text: 'Failed to communicate with server. Check your connection.'
                    });
                });
            });

            // Sync Attendance Action via AJAX
            $('#btnSyncAttendance').on('click', function() {
                if (!currentProjectId) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Select Project',
                        text: 'Please select a Meal Project first.'
                    });
                    return;
                }

                const btn = $(this);
                const origHtml = btn.html();

                Swal.fire({
                    title: 'Sync Attendance with Meals?',
                    text: 'This will auto-tick meal records for all active employees who are marked Present in Attendance (via Tipsoi API or manual entries) and have NOT opted out before 12 PM.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Sync Attendance',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#0f766e'
                }).then((result) => {
                    if (result.isConfirmed) {
                        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Syncing...');

                        fetch(`${baseUrl}/api/meal-tracker/sync-attendance`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                project_id: currentProjectId,
                                branch_id: currentBranchId
                            })
                        })
                        .then(r => r.json())
                        .then(res => {
                            btn.prop('disabled', false).html(origHtml);
                            if (res.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Attendance Synced!',
                                    text: res.message,
                                    confirmButtonColor: '#0f766e'
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire('Sync Failed', res.message || 'Error occurred during sync.', 'error');
                            }
                        })
                        .catch(err => {
                            btn.prop('disabled', false).html(origHtml);
                            console.error("Sync error:", err);
                            Swal.fire('Error', 'Network or server error during sync.', 'error');
                        });
                    }
                });
            });

            // Delete Meal Project Action
            $(document).on('click', '#btnDeleteMealProject', function() {
                const projId = $(this).data('id');
                const projName = $(this).data('name');

                Swal.fire({
                    title: `Delete "${projName}"?`,
                    text: "Are you sure? This will permanently delete this meal project and ALL its meal records and guest data! This action cannot be undone.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, Delete Everything',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Deleting Project...',
                            text: 'Please wait while project and all meal records are being removed.',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        fetch(`${baseUrl}/meal-project-delete`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ project_id: projId })
                        })
                        .then(r => r.json())
                        .then(res => {
                            if (res.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted Successfully!',
                                    text: res.message,
                                    confirmButtonColor: '#0f766e'
                                }).then(() => {
                                    window.location.href = `${baseUrl}/meal-tracker`;
                                });
                            } else {
                                Swal.fire('Delete Failed', res.message || 'Error occurred.', 'error');
                            }
                        })
                        .catch(err => {
                            console.error("Delete error:", err);
                            Swal.fire('Error', 'Network or server error during project deletion.', 'error');
                        });
                    }
                });
            });

            // Formatted Excel Export for Selected / Custom Date Range
            window.exportMealWorkspaceExcel = function() {
                const fromInput = document.getElementById('mealExportFromDate');
                const toInput = document.getElementById('mealExportToDate');
                const fromDate = fromInput ? fromInput.value : '';
                const toDate = toInput ? toInput.value : '';

                if (!fromDate || !toDate) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Select Date Range',
                        text: 'Please select both From Date and To Date for exporting.'
                    });
                    return;
                }
                if (fromDate > toDate) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Invalid Date Range',
                        text: 'From Date cannot be later than To Date.'
                    });
                    return;
                }

                // Collect matching date columns from the table header
                const activeDateCols = [];
                $('#mealTrackerTable thead th.meal-date-th').each(function() {
                    const d = $(this).attr('data-date') || '';
                    if (d >= fromDate && d <= toDate) {
                        activeDateCols.push({
                            date: d,
                            label: $(this).attr('data-label') || d,
                            dayName: $(this).attr('data-dayname') || '',
                            isWeekend: $(this).attr('data-weekend') === '1'
                        });
                    }
                });

                if (activeDateCols.length === 0) {
                    Swal.fire({
                        icon: 'info',
                        title: 'No Dates in Range',
                        text: 'The selected date range does not overlap with any dates in the current Meal Project.'
                    });
                    return;
                }

                const projectName = <?= json_encode($activeProject['name'] ?? 'Meal Project') ?>;
                const branchName = $('#branchTabs .nav-link.active').text().trim() || 'Office Branch';
                const esc = function(str) {
                    return String(str === null || str === undefined ? '' : str)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;');
                };
                const fmtMoney = function(n) {
                    const val = parseFloat(n) || 0;
                    return val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                };

                // Collect employee row data for the selected date range
                const empRows = [];
                const dailyEmpTotals = {};
                const dailyGuestTotals = {};
                activeDateCols.forEach(c => {
                    dailyEmpTotals[c.date] = 0;
                    dailyGuestTotals[c.date] = 0;
                });

                let grandEmpMeals = 0;
                let grandEmpDeduction = 0;

                $('.emp-meal-row').each(function(idx) {
                    const row = $(this);
                    const empName = row.attr('data-emp-name') || row.find('.sticky-col-2').clone().children().remove().end().text().trim();
                    const designation = row.attr('data-designation') || row.find('.sticky-col-2 .small').text().trim();
                    const empCode = row.attr('data-emp-code') || row.find('.sticky-col-3').text().trim();
                    const ratePct = row.attr('data-pct') || row.find('.sticky-col-4').text().replace('%', '').trim();
                    const rateAmt = parseFloat(row.attr('data-rate')) || 0;

                    let empRangeMeals = 0;
                    const dayCells = [];

                    activeDateCols.forEach(c => {
                        const cb = row.find(`.meal-checkbox[data-date="${c.date}"]`);
                        const isChecked = cb.is(':checked');
                        const isOptOut = cb.attr('data-opt-out') === '1';
                        if (isChecked) {
                            empRangeMeals += 1;
                            dailyEmpTotals[c.date] += 1;
                            dayCells.push({ status: '1', isOptOut: false, isWeekend: c.isWeekend });
                        } else if (isOptOut) {
                            dayCells.push({ status: 'OFF', isOptOut: true, isWeekend: c.isWeekend });
                        } else {
                            dayCells.push({ status: '0', isOptOut: false, isWeekend: c.isWeekend });
                        }
                    });

                    const empRangeDeduction = Math.round(empRangeMeals * rateAmt * 100) / 100;
                    grandEmpMeals += empRangeMeals;
                    grandEmpDeduction += empRangeDeduction;

                    empRows.push({
                        sl: idx + 1,
                        name: empName,
                        designation: designation,
                        empCode: empCode,
                        ratePct: ratePct + '%',
                        rateAmt: rateAmt,
                        meals: empRangeMeals,
                        deduction: empRangeDeduction,
                        days: dayCells
                    });
                });

                // Collect Guest row data for the selected date range
                let grandGuestMeals = 0;
                const guestDayCells = [];
                activeDateCols.forEach(c => {
                    const gVal = parseInt($(`.guest-meal-input[data-date="${c.date}"]`).val(), 10) || 0;
                    dailyGuestTotals[c.date] = gVal;
                    grandGuestMeals += gVal;
                    guestDayCells.push({ count: gVal, isWeekend: c.isWeekend });
                });
                const grandGuestCost = Math.round(grandGuestMeals * standardMealAmount * 100) / 100;
                const totalCombinedMeals = grandEmpMeals + grandGuestMeals;

                const totalCols = 8 + activeDateCols.length;
                const nowStr = new Date().toLocaleString('en-GB', {
                    day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
                });

                let html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
                html += '<head><meta charset="UTF-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>';
                html += '<x:Name>Meal Workspace</x:Name>';
                html += '<x:WorksheetOptions><x:DisplayGridlines/><x:FreezePanes/><x:FrozenNoSplit/>';
                html += '<x:SplitHorizontal>7</x:SplitHorizontal><x:TopRowBottomPane>7</x:TopRowBottomPane>';
                html += '<x:SplitVertical>8</x:SplitVertical><x:LeftColumnRightPane>8</x:LeftColumnRightPane>';
                html += '<x:ActivePane>0</x:ActivePane></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
                html += '<style>';
                html += 'body, table { font-family: "Segoe UI", Calibri, Arial, sans-serif; font-size: 9.5pt; border-collapse: collapse; }';
                html += 'td, th { border: 1px solid #cbd5e1; padding: 5px 7px; vertical-align: middle; white-space: nowrap; }';
                html += '.num { mso-number-format:"#,##0.00"; text-align: right; }';
                html += '.qty { mso-number-format:"0"; text-align: center; }';
                html += '.txt { mso-number-format:"\\@"; text-align: left; }';
                html += '.txt-c { mso-number-format:"\\@"; text-align: center; }';
                html += '</style></head><body><table>';

                // Row 1: Company Banner
                html += '<tr><td colspan="' + totalCols + '" style="background:#0f766e; color:#ffffff; font-size:14pt; font-weight:bold; padding:10px 14px; border:none;">';
                html += 'THE PREMIUM HOMES LTD &mdash; MEAL TRACKER WORKSPACE REPORT</td></tr>';

                // Row 2: Project, Branch & Selected Date Range Metadata
                html += '<tr><td colspan="' + totalCols + '" style="background:#115e59; color:#ccfbf1; font-size:10pt; font-weight:600; padding:6px 14px; border:none;">';
                html += 'Meal Project: ' + esc(projectName) + ' &nbsp;&bull;&nbsp; Branch: ' + esc(branchName) + ' &nbsp;&bull;&nbsp; Export Range: ' + esc(fromDate) + ' to ' + esc(toDate) + ' (' + activeDateCols.length + ' Days) &nbsp;&bull;&nbsp; Generated: ' + esc(nowStr);
                html += '</td></tr>';

                // Row 3: Blank spacer
                html += '<tr><td colspan="' + totalCols + '" style="border:none; height:6px; background:#ffffff;"></td></tr>';

                // Row 4: Executive Summary KPI Strip
                const remCols = Math.max(1, totalCols - 12);
                html += '<tr>';
                html += '<td colspan="2" style="background:#f8fafc; border:1px solid #94a3b8; padding:6px 8px;"><b>Staff Count:</b> ' + empRows.length + ' Employees</td>';
                html += '<td colspan="2" style="background:#f0fdf4; border:1px solid #86efac; color:#166534; padding:6px 8px;"><b>Standard Rate:</b> TK ' + fmtMoney(standardMealAmount) + '</td>';
                html += '<td colspan="2" style="background:#eff6ff; border:1px solid #93c5fd; color:#1e40af; padding:6px 8px;"><b>Emp Meals:</b> ' + grandEmpMeals + '</td>';
                html += '<td colspan="2" style="background:#fffbeb; border:1px solid #fde047; color:#92400e; padding:6px 8px;"><b>Guest Meals:</b> ' + grandGuestMeals + ' (TK ' + fmtMoney(grandGuestCost) + ')</td>';
                html += '<td colspan="2" style="background:#ecfdf5; border:1px solid #6ee7b7; color:#065f46; padding:6px 8px;"><b>Total Combined Meals:</b> ' + totalCombinedMeals + '</td>';
                html += '<td colspan="2" style="background:#fef2f2; border:1px solid #fca5a5; color:#991b1b; padding:6px 8px;"><b>Total Emp Deduction:</b> TK ' + fmtMoney(grandEmpDeduction) + '</td>';
                if (totalCols > 12) {
                    html += '<td colspan="' + remCols + '" style="border:none; background:#ffffff;"></td>';
                }
                html += '</tr>';

                // Row 5: Blank spacer
                html += '<tr><td colspan="' + totalCols + '" style="border:none; height:6px; background:#ffffff;"></td></tr>';

                // Row 6: Table Header Row 1 (Identity + Summary + Date Labels)
                html += '<tr>';
                html += '<th rowspan="2" style="background:#0f766e; color:#ffffff; font-weight:bold; text-align:center;">SL</th>';
                html += '<th rowspan="2" style="background:#0f766e; color:#ffffff; font-weight:bold; text-align:left;">Employee Name</th>';
                html += '<th rowspan="2" style="background:#0f766e; color:#ffffff; font-weight:bold; text-align:left;">Designation</th>';
                html += '<th rowspan="2" style="background:#0f766e; color:#ffffff; font-weight:bold; text-align:center;">Emp ID</th>';
                html += '<th rowspan="2" style="background:#0f766e; color:#ffffff; font-weight:bold; text-align:center;">Rate %</th>';
                html += '<th rowspan="2" style="background:#0f766e; color:#ffffff; font-weight:bold; text-align:right;">Rate (TK)</th>';
                html += '<th style="background:#065f46; color:#ffffff; font-weight:bold; text-align:center;">Total Meals</th>';
                html += '<th style="background:#991b1b; color:#ffffff; font-weight:bold; text-align:right;">Deduction (TK)</th>';
                activeDateCols.forEach(c => {
                    const hdrBg = c.isWeekend ? '#475569' : '#0f766e';
                    html += '<th style="background:' + hdrBg + '; color:#ffffff; font-weight:bold; text-align:center;">' + esc(c.label) + '</th>';
                });
                html += '</tr>';

                // Row 7: Table Header Row 2 (Totals + Day of Week & Daily Meal Count)
                html += '<tr>';
                html += '<th class="qty" style="background:#047857; color:#ffffff; font-weight:bold;">' + grandEmpMeals + '</th>';
                html += '<th class="num" style="background:#b91c1c; color:#ffffff; font-weight:bold;">' + fmtMoney(grandEmpDeduction) + '</th>';
                activeDateCols.forEach(c => {
                    const subBg = c.isWeekend ? '#64748b' : '#115e59';
                    const dayTot = (dailyEmpTotals[c.date] || 0) + (dailyGuestTotals[c.date] || 0);
                    html += '<th style="background:' + subBg + '; color:#f0fdfa; font-size:8.5pt; text-align:center;">' + esc(c.dayName) + ' (' + dayTot + ')</th>';
                });
                html += '</tr>';

                // Employee Data Rows
                empRows.forEach((r, idx) => {
                    const rowBg = (idx % 2 === 0) ? '#ffffff' : '#f8fafc';
                    html += '<tr>';
                    html += '<td class="qty" style="background:' + rowBg + '; color:#475569;">' + r.sl + '</td>';
                    html += '<td class="txt" style="background:' + rowBg + '; font-weight:600; color:#0f172a;">' + esc(r.name) + '</td>';
                    html += '<td class="txt" style="background:' + rowBg + '; color:#475569;">' + esc(r.designation) + '</td>';
                    html += '<td class="txt-c" style="background:' + rowBg + '; font-weight:600; color:#1e293b;">' + esc(r.empCode) + '</td>';
                    html += '<td class="txt-c" style="background:' + rowBg + '; color:#0369a1; font-weight:600;">' + esc(r.ratePct) + '</td>';
                    html += '<td class="num" style="background:' + rowBg + '; color:#334155;">' + fmtMoney(r.rateAmt) + '</td>';
                    html += '<td class="qty" style="background:#eff6ff; color:#1d4ed8; font-weight:bold;">' + r.meals + '</td>';
                    html += '<td class="num" style="background:#fef2f2; color:#dc2626; font-weight:bold;">' + fmtMoney(r.deduction) + '</td>';

                    r.days.forEach(dCell => {
                        if (dCell.status === '1') {
                            const cBg = dCell.isWeekend ? '#dcfce7' : '#f0fdf4';
                            html += '<td class="qty" style="background:' + cBg + '; color:#15803d; font-weight:bold;">1</td>';
                        } else if (dCell.status === 'OFF') {
                            html += '<td class="txt-c" style="background:#fee2e2; color:#b91c1c; font-weight:bold; font-size:8.5pt;">OFF</td>';
                        } else {
                            const cBg = dCell.isWeekend ? '#f1f5f9' : rowBg;
                            html += '<td class="qty" style="background:' + cBg + '; color:#94a3b8;">0</td>';
                        }
                    });
                    html += '</tr>';
                });

                // Guest / Visitor Row
                html += '<tr>';
                html += '<td class="txt-c" style="background:#fef9c3; font-weight:bold; color:#854d0e;">G</td>';
                html += '<td class="txt" style="background:#fef9c3; font-weight:bold; color:#854d0e;">Guest / Visitor Meals</td>';
                html += '<td class="txt" style="background:#fef9c3; color:#854d0e;">Branch Guests</td>';
                html += '<td class="txt-c" style="background:#fef9c3; color:#854d0e;">-</td>';
                html += '<td class="txt-c" style="background:#fef9c3; color:#854d0e;">100%</td>';
                html += '<td class="num" style="background:#fef9c3; font-weight:600; color:#854d0e;">' + fmtMoney(standardMealAmount) + '</td>';
                html += '<td class="qty" style="background:#fef08a; font-weight:bold; color:#854d0e;">' + grandGuestMeals + '</td>';
                html += '<td class="num" style="background:#fef08a; font-weight:bold; color:#854d0e;">' + fmtMoney(grandGuestCost) + '</td>';
                guestDayCells.forEach(gCell => {
                    html += '<td class="qty" style="background:#fef9c3; font-weight:' + (gCell.count > 0 ? 'bold' : 'normal') + '; color:' + (gCell.count > 0 ? '#854d0e' : '#a1a1aa') + ';">' + gCell.count + '</td>';
                });
                html += '</tr>';

                // Grand Total Footer Row
                html += '<tr>';
                html += '<td colspan="6" style="background:#0f766e; color:#ffffff; font-weight:bold; text-align:right; padding-right:10px;">GRAND TOTAL (EMPLOYEES + GUESTS):</td>';
                html += '<td class="qty" style="background:#065f46; color:#ffffff; font-weight:bold;">' + totalCombinedMeals + '</td>';
                html += '<td class="num" style="background:#991b1b; color:#ffffff; font-weight:bold;">' + fmtMoney(grandEmpDeduction) + '</td>';
                activeDateCols.forEach(c => {
                    const dayTot = (dailyEmpTotals[c.date] || 0) + (dailyGuestTotals[c.date] || 0);
                    html += '<td class="qty" style="background:#0f766e; color:#ffffff; font-weight:bold;">' + dayTot + '</td>';
                });
                html += '</tr>';

                html += '</table></body></html>';

                const cleanProj = String(projectName).replace(/[^a-zA-Z0-9_-]/g, '_');
                const cleanBranch = String(branchName).replace(/[^a-zA-Z0-9_-]/g, '_');
                const fileName = `Meal_Workspace_${cleanProj}_${cleanBranch}_${fromDate}_to_${toDate}.xls`;

                const blob = new Blob(['\ufeff', html], { type: 'application/vnd.ms-excel;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = fileName;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                setTimeout(() => URL.revokeObjectURL(url), 1500);
            };

            // =========================================================================
            // Active Meal Report (Office-Wise Counts + Employee Names + PDF & Excel)
            // =========================================================================
            let activeMealReportData = null;

            function escHtml(str) {
                return String(str ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

            function renderActiveMealReport(data) {
                activeMealReportData = data;
                const container = $('#activeMealReportContent');
                const branches = data.branches || [];

                if (!data || !branches.length) {
                    container.html('<div class="alert alert-warning mb-0">No active office branches or meal data found for this date.</div>');
                    $('#btnExportActiveMealPdf, #btnExportActiveMealExcel').prop('disabled', true);
                    return;
                }

                $('#btnExportActiveMealPdf, #btnExportActiveMealExcel').prop('disabled', false);
                $('#activeMealReportSubtitle').text(`${data.project?.name || 'Meal Project'} — Date: ${data.date}`);

                let html = `
                    <div class="row g-2 mb-3">
                        <div class="col-6 col-md-3">
                            <div class="p-2 px-3 rounded border bg-light">
                                <div class="text-muted" style="font-size: 11px;">Total Offices</div>
                                <div class="fw-bold fs-5 text-dark">${branches.length}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-2 px-3 rounded border bg-light">
                                <div class="text-muted" style="font-size: 11px;">Employee Meals</div>
                                <div class="fw-bold fs-5 text-primary">${data.grand_total_employee_meals}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-2 px-3 rounded border bg-light">
                                <div class="text-muted" style="font-size: 11px;">Guest Meals</div>
                                <div class="fw-bold fs-5 text-warning">${data.grand_total_guest_meals}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-2 px-3 rounded border" style="background-color: #f0fdfa; border-color: #99f6e4 !important;">
                                <div class="fw-semibold" style="font-size: 11px; color: #0f766e;">Grand Total Meals</div>
                                <div class="fw-bold fs-5" style="color: #0f766e;">${data.grand_total_meals}</div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered align-middle mb-0" style="font-size: 12.5px;">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 45px;" class="text-center">#</th>
                                    <th>Office Branch</th>
                                    <th class="text-center" style="width: 130px;">Employee Meals</th>
                                    <th class="text-center" style="width: 120px;">Guest Meals</th>
                                    <th class="text-center" style="width: 120px;">Total Meals</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                branches.forEach((b, idx) => {
                    html += `
                        <tr>
                            <td class="text-center">${idx + 1}</td>
                            <td class="fw-semibold">${escHtml(b.branch_name)}</td>
                            <td class="text-center">${b.employee_meal_count}</td>
                            <td class="text-center">${b.guest_meal_count}</td>
                            <td class="text-center fw-bold text-success">${b.total_meals}</td>
                        </tr>
                    `;
                });

                html += `
                            </tbody>
                            <tfoot>
                                <tr class="table-secondary fw-bold">
                                    <td colspan="2" class="text-end">Grand Total:</td>
                                    <td class="text-center">${data.grand_total_employee_meals}</td>
                                    <td class="text-center">${data.grand_total_guest_meals}</td>
                                    <td class="text-center text-success">${data.grand_total_meals}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                `;

                // Office-wise Employee Lists
                branches.forEach((b) => {
                    const emps = b.employees || [];
                    html += `
                        <div class="card border mb-3 shadow-none">
                            <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center bg-light">
                                <span class="fw-bold" style="font-size: 13px;">
                                    <i class="fa-solid fa-building text-success me-1"></i>${escHtml(b.branch_name)}
                                </span>
                                <span class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Emp: ${b.employee_meal_count}</span>
                                    ${b.guest_meal_count > 0 ? `<span class="badge bg-warning-subtle text-dark border border-warning-subtle">Guest: ${b.guest_meal_count}</span>` : ''}
                                    <span class="badge bg-success">Total: ${b.total_meals}</span>
                                </span>
                            </div>
                            <div class="card-body p-0">
                    `;

                    if (emps.length === 0) {
                        html += `<div class="p-3 text-muted small fst-italic">No active employee meals for this office on ${escHtml(data.date)}.</div>`;
                    } else {
                        html += `
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0" style="font-size: 12px;">
                                    <thead>
                                        <tr class="text-muted">
                                            <th class="text-center" style="width: 45px;">SL</th>
                                            <th style="width: 110px;">Emp ID</th>
                                            <th>Employee Name</th>
                                            <th>Department</th>
                                            <th>Designation</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;
                        emps.forEach((emp, eIdx) => {
                            html += `
                                <tr>
                                    <td class="text-center text-muted">${eIdx + 1}</td>
                                    <td class="font-monospace">${escHtml(emp.employee_id)}</td>
                                    <td class="fw-semibold">${escHtml(emp.full_name)}</td>
                                    <td>${escHtml(emp.department_name)}</td>
                                    <td>${escHtml(emp.designation_name)}</td>
                                </tr>
                            `;
                        });
                        html += `</tbody></table></div>`;
                    }

                    html += `</div></div>`;
                });

                container.html(html);
            }

            function fetchActiveMealReport() {
                if (!currentProjectId) {
                    Swal.fire('No Project', 'Please select or create a Meal Project first.', 'warning');
                    return;
                }
                const reportDate = $('#activeMealReportDate').val() || '';
                $('#btnExportActiveMealPdf, #btnExportActiveMealExcel').prop('disabled', true);
                $('#activeMealReportContent').html(`
                    <div class="text-center py-5 text-muted">
                        <div class="spinner-border text-success mb-2" role="status"></div>
                        <div>Loading active meal report...</div>
                    </div>
                `);

                fetch(`${baseUrl}/api/meal-tracker/active-meal-report?project_id=${encodeURIComponent(currentProjectId)}&date=${encodeURIComponent(reportDate)}`, {
                    headers: { 'Accept': 'application/json' }
                })
                .then(r => r.json())
                .then(res => {
                    if (res && res.success) {
                        renderActiveMealReport(res);
                    } else {
                        $('#activeMealReportContent').html(`<div class="alert alert-danger mb-0">${escHtml(res?.message || 'Failed to load active meal report.')}</div>`);
                    }
                })
                .catch(err => {
                    console.error('Active Meal Report Error:', err);
                    $('#activeMealReportContent').html('<div class="alert alert-danger mb-0">Network or server error while loading report.</div>');
                });
            }

            $('#btnActiveMealReport').on('click', function() {
                if (!currentProjectId) {
                    Swal.fire('No Project', 'Please select or create a Meal Project first.', 'warning');
                    return;
                }
                const modalEl = document.getElementById('activeMealReportModal');
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
                fetchActiveMealReport();
            });

            $('#btnReloadActiveMealReport').on('click', fetchActiveMealReport);
            $('#activeMealReportDate').on('change', fetchActiveMealReport);

            // Export Active Meal Report -> Multi-Tab Excel (SpreadsheetML .xls: Summary tab + 1 tab per Office)
            $('#btnExportActiveMealExcel').on('click', function() {
                if (!activeMealReportData) return;
                const d = activeMealReportData;
                const branches = d.branches || [];
                const projName = d.project?.name || 'Meal Project';

                function escXml(str) {
                    return String(str ?? '')
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&apos;');
                }

                const usedSheetNames = new Set();
                function makeSheetName(rawName, fallback) {
                    let clean = String(rawName || fallback || 'Sheet')
                        .replace(/[:\\\/\?\*\[\]]/g, ' ')
                        .replace(/\s+/g, ' ')
                        .trim();
                    if (!clean) clean = fallback || 'Sheet';
                    if (clean.length > 31) clean = clean.substring(0, 31).trim();
                    let candidate = clean;
                    let counter = 2;
                    while (usedSheetNames.has(candidate.toLowerCase())) {
                        const suffix = ` (${counter++})`;
                        candidate = clean.substring(0, 31 - suffix.length).trim() + suffix;
                    }
                    usedSheetNames.add(candidate.toLowerCase());
                    return candidate;
                }

                let xml = '<?xml version="1.0" encoding="UTF-8"?>\n';
                xml += '<?mso-application progid="Excel.Sheet"?>\n';
                xml += '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"\n';
                xml += ' xmlns:o="urn:schemas-microsoft-com:office:office"\n';
                xml += ' xmlns:x="urn:schemas-microsoft-com:office:excel"\n';
                xml += ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">\n';

                // Styles
                xml += `<Styles>
                    <Style ss:ID="Default" ss:Name="Normal">
                        <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#1E293B"/>
                    </Style>
                    <Style ss:ID="sTitle">
                        <Font ss:FontName="Calibri" ss:Size="14" ss:Bold="1" ss:Color="#0F766E"/>
                    </Style>
                    <Style ss:ID="sSub">
                        <Font ss:FontName="Calibri" ss:Size="10" ss:Color="#475569"/>
                    </Style>
                    <Style ss:ID="sHeader">
                        <Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#FFFFFF"/>
                        <Interior ss:Color="#0F766E" ss:Pattern="Solid"/>
                        <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
                        <Borders>
                            <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
                            <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
                            <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
                            <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
                        </Borders>
                    </Style>
                    <Style ss:ID="sCell">
                        <Alignment ss:Vertical="Center"/>
                        <Borders>
                            <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
                            <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
                            <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
                            <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
                        </Borders>
                    </Style>
                    <Style ss:ID="sCellBold">
                        <Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#0F172A"/>
                        <Alignment ss:Vertical="Center"/>
                        <Borders>
                            <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
                            <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
                            <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
                            <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
                        </Borders>
                    </Style>
                    <Style ss:ID="sCellCenter">
                        <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
                        <Borders>
                            <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
                            <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
                            <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
                            <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
                        </Borders>
                    </Style>
                    <Style ss:ID="sCellCenterBold">
                        <Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#0F766E"/>
                        <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
                        <Borders>
                            <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
                            <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
                            <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
                            <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
                        </Borders>
                    </Style>
                    <Style ss:ID="sTotal">
                        <Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#0F766E"/>
                        <Interior ss:Color="#F0FDFA" ss:Pattern="Solid"/>
                        <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>
                        <Borders>
                            <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#99F6E4"/>
                            <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#99F6E4"/>
                            <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#99F6E4"/>
                            <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#99F6E4"/>
                        </Borders>
                    </Style>
                    <Style ss:ID="sTotalCenter">
                        <Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#0F766E"/>
                        <Interior ss:Color="#F0FDFA" ss:Pattern="Solid"/>
                        <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
                        <Borders>
                            <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#99F6E4"/>
                            <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#99F6E4"/>
                            <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#99F6E4"/>
                            <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#99F6E4"/>
                        </Borders>
                    </Style>
                </Styles>\n`;

                // 1. Summary Tab
                const summarySheetName = makeSheetName('Summary', 'Summary');
                xml += `<Worksheet ss:Name="${escXml(summarySheetName)}">\n<Table>\n`;
                xml += `<Column ss:Width="45"/><Column ss:Width="220"/><Column ss:Width="115"/><Column ss:Width="105"/><Column ss:Width="110"/>\n`;
                xml += `<Row ss:Height="24"><Cell ss:MergeAcross="4" ss:StyleID="sTitle"><Data ss:Type="String">Active Meal Report — Office-Wise Summary</Data></Cell></Row>\n`;
                xml += `<Row ss:Height="18"><Cell ss:MergeAcross="4" ss:StyleID="sSub"><Data ss:Type="String">Project: ${escXml(projName)} | Date: ${escXml(d.date)} | Grand Total Meals: ${d.grand_total_meals}</Data></Cell></Row>\n`;
                xml += `<Row/>\n`;
                xml += `<Row ss:Height="22">
                    <Cell ss:StyleID="sHeader"><Data ss:Type="String">#</Data></Cell>
                    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Office Branch</Data></Cell>
                    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Employee Meals</Data></Cell>
                    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Guest Meals</Data></Cell>
                    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Total Meals</Data></Cell>
                </Row>\n`;

                branches.forEach((b, idx) => {
                    xml += `<Row ss:Height="20">
                        <Cell ss:StyleID="sCellCenter"><Data ss:Type="Number">${idx + 1}</Data></Cell>
                        <Cell ss:StyleID="sCellBold"><Data ss:Type="String">${escXml(b.branch_name)}</Data></Cell>
                        <Cell ss:StyleID="sCellCenter"><Data ss:Type="Number">${b.employee_meal_count}</Data></Cell>
                        <Cell ss:StyleID="sCellCenter"><Data ss:Type="Number">${b.guest_meal_count}</Data></Cell>
                        <Cell ss:StyleID="sCellCenterBold"><Data ss:Type="Number">${b.total_meals}</Data></Cell>
                    </Row>\n`;
                });

                xml += `<Row ss:Height="22">
                    <Cell ss:MergeAcross="1" ss:StyleID="sTotal"><Data ss:Type="String">Grand Total:</Data></Cell>
                    <Cell ss:StyleID="sTotalCenter"><Data ss:Type="Number">${d.grand_total_employee_meals}</Data></Cell>
                    <Cell ss:StyleID="sTotalCenter"><Data ss:Type="Number">${d.grand_total_guest_meals}</Data></Cell>
                    <Cell ss:StyleID="sTotalCenter"><Data ss:Type="Number">${d.grand_total_meals}</Data></Cell>
                </Row>\n`;
                xml += `</Table>\n</Worksheet>\n`;

                // 2. Individual Tab for Each Office Branch
                branches.forEach((b, idx) => {
                    const sheetName = makeSheetName(b.branch_name, `Office_${idx + 1}`);
                    const emps = b.employees || [];
                    xml += `<Worksheet ss:Name="${escXml(sheetName)}">\n<Table>\n`;
                    xml += `<Column ss:Width="45"/><Column ss:Width="105"/><Column ss:Width="210"/><Column ss:Width="150"/><Column ss:Width="160"/>\n`;
                    xml += `<Row ss:Height="24"><Cell ss:MergeAcross="4" ss:StyleID="sTitle"><Data ss:Type="String">${escXml(b.branch_name)} — Active Meal List</Data></Cell></Row>\n`;
                    xml += `<Row ss:Height="18"><Cell ss:MergeAcross="4" ss:StyleID="sSub"><Data ss:Type="String">Project: ${escXml(projName)} | Date: ${escXml(d.date)} | Employee Meals: ${b.employee_meal_count} | Guest Meals: ${b.guest_meal_count} | Total Meals: ${b.total_meals}</Data></Cell></Row>\n`;
                    xml += `<Row/>\n`;
                    xml += `<Row ss:Height="22">
                        <Cell ss:StyleID="sHeader"><Data ss:Type="String">SL</Data></Cell>
                        <Cell ss:StyleID="sHeader"><Data ss:Type="String">Employee ID</Data></Cell>
                        <Cell ss:StyleID="sHeader"><Data ss:Type="String">Employee Name</Data></Cell>
                        <Cell ss:StyleID="sHeader"><Data ss:Type="String">Department</Data></Cell>
                        <Cell ss:StyleID="sHeader"><Data ss:Type="String">Designation</Data></Cell>
                    </Row>\n`;

                    if (emps.length === 0) {
                        xml += `<Row ss:Height="20"><Cell ss:MergeAcross="4" ss:StyleID="sCellCenter"><Data ss:Type="String">No active employee meals on ${escXml(d.date)}</Data></Cell></Row>\n`;
                    } else {
                        emps.forEach((emp, eIdx) => {
                            xml += `<Row ss:Height="19">
                                <Cell ss:StyleID="sCellCenter"><Data ss:Type="Number">${eIdx + 1}</Data></Cell>
                                <Cell ss:StyleID="sCellCenter"><Data ss:Type="String">${escXml(emp.employee_id)}</Data></Cell>
                                <Cell ss:StyleID="sCellBold"><Data ss:Type="String">${escXml(emp.full_name)}</Data></Cell>
                                <Cell ss:StyleID="sCell"><Data ss:Type="String">${escXml(emp.department_name)}</Data></Cell>
                                <Cell ss:StyleID="sCell"><Data ss:Type="String">${escXml(emp.designation_name)}</Data></Cell>
                            </Row>\n`;
                        });
                    }

                    xml += `<Row ss:Height="22">
                        <Cell ss:MergeAcross="3" ss:StyleID="sTotal"><Data ss:Type="String">Office Total (Employee: ${b.employee_meal_count} + Guest: ${b.guest_meal_count}):</Data></Cell>
                        <Cell ss:StyleID="sTotalCenter"><Data ss:Type="Number">${b.total_meals}</Data></Cell>
                    </Row>\n`;
                    xml += `</Table>\n</Worksheet>\n`;
                });

                xml += '</Workbook>';

                const cleanProj = String(projName).replace(/[^a-zA-Z0-9_-]/g, '_');
                const fileName = `Active_Meal_Report_${cleanProj}_${d.date}.xls`;
                const blob = new Blob([xml], { type: 'application/vnd.ms-excel;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = fileName;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                setTimeout(() => URL.revokeObjectURL(url), 1500);
            });

            // Export Active Meal Report -> Direct PDF File Download (Server-side Dompdf)
            $('#btnExportActiveMealPdf').on('click', function() {
                if (!currentProjectId) return;
                const reportDate = $('#activeMealReportDate').val() || (activeMealReportData?.date || '');
                const downloadUrl = `${baseUrl}/api/meal-tracker/active-meal-report-pdf?project_id=${encodeURIComponent(currentProjectId)}&date=${encodeURIComponent(reportDate)}`;
                window.location.href = downloadUrl;
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModuleScripts);
    } else {
        initModuleScripts();
    }
})();
</script>

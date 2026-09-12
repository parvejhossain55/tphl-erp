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
    padding: 9px 18px;
    border-radius: 8px;
    border: 1px solid var(--clr-border-1, #e5e7eb);
    color: inherit;
    margin-right: 8px;
    background: var(--clr-bg-card, transparent);
    transition: all 0.2s ease;
}
.meal-tracker-wrapper .nav-pills .nav-link:hover {
    background: var(--clr-bg-secondary, rgba(0, 0, 0, 0.05));
    color: inherit;
}
.meal-tracker-wrapper .nav-pills .nav-link.active {
    background: #0f766e !important;
    color: #ffffff !important;
    border-color: #0f766e !important;
    box-shadow: 0 4px 6px -1px rgba(15, 118, 110, 0.25);
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
    <!-- Top Filter & Action Header -->
    <div class="card__wrapper p-3 mb-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <!-- Left: Project Selector -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <label class="form-label fw-bold mb-0 text-muted">Meal Project:</label>
                <form method="GET" action="<?= $GLOBALS['base_url'] ?>/meal-tracker" class="d-flex align-items-center gap-2" id="projectSelectForm">
                    <select name="project_id" class="form-select custom-select2" style="min-width: 260px;" onchange="this.form.submit()">
                        <?php if (empty($mealProjects)): ?>
                            <option value="">No Meal Projects Found</option>
                        <?php else: ?>
                            <?php foreach ($mealProjects as $p): ?>
                                <option value="<?= (int)$p['id'] ?>" <?= ($selectedProjectId == $p['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['name']) ?> (<?= date('d M', strtotime($p['from_date'])) ?> - <?= date('d M Y', strtotime($p['to_date'])) ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <input type="hidden" name="branch_id" value="<?= (int)$selectedBranchId ?>">
                </form>

                <button type="button" class="btn btn-outline-primary fw-semibold btn-open-meal-modal" data-bs-toggle="modal" data-bs-target="#createMealProjectModal">
                    <i class="fa-solid fa-plus me-1"></i> New Project
                </button>
            </div>

            <!-- Middle: Info Badges -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <?php if ($activeProject): ?>
                    <span class="badge bg-teal text-white p-2 px-3" style="background-color:#0f766e;">
                        <i class="fa-solid fa-calendar-days me-1"></i> 
                        <?= date('d M Y', strtotime($activeProject['from_date'])) ?> &rarr; <?= date('d M Y', strtotime($activeProject['to_date'])) ?> (<?= $totalDaysInPeriod ?> Days)
                    </span>
                    <span class="badge border p-2 px-3" style="background: var(--clr-bg-card, transparent); color: inherit; border-color: var(--clr-border-1, #e5e7eb) !important;">
                        <i class="fa-solid fa-utensils me-1 text-primary"></i> 
                        Standard Rate: <strong>৳<?= number_format($standardAmount, 2) ?></strong>
                    </span>
                <?php endif; ?>
            </div>

            <!-- Right: Action Buttons -->
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-outline-success fw-semibold" id="btnSyncAttendance" title="Sync Present Attendance & Opt-Outs from Tipsoi and Manual Attendance">
                    <i class="fa-solid fa-arrows-rotate me-1"></i> Sync Attendance
                </button>
                <a href="<?= $GLOBALS['base_url'] ?>/meal-tracker-history" class="btn btn-outline-secondary fw-semibold">
                    <i class="fa-solid fa-chart-line me-1"></i> Branch History
                </a>
                <button type="button" class="btn btn-success fw-bold shadow-sm" id="btnSaveMealGrid" style="background-color: #0f766e; border-color: #0f766e;">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Save Meal Records
                </button>
            </div>
        </div>
    </div>

    <!-- Office Branch Navigation Tabs -->
    <div class="card__wrapper p-3 mb-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <ul class="nav nav-pills flex-wrap mb-0" id="branchTabs">
                <?php foreach ($branches as $branch): ?>
                    <?php $bId = (int)$branch['id']; ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($selectedBranchId === $bId) ? 'active' : '' ?>" 
                           href="<?= $GLOBALS['base_url'] ?>/meal-tracker?project_id=<?= (int)$selectedProjectId ?>&branch_id=<?= $bId ?>">
                            <i class="fa-solid fa-building me-1"></i> <?= htmlspecialchars($branch['name']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="text-muted small">
                Active Employees on Meal: <strong><?= count($employees) ?></strong>
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
                            <th class="text-center <?= $col['is_weekend'] ? 'weekend-col' : '' ?>" style="min-width: 48px;">
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
                            <tr class="emp-meal-row" data-emp-id="<?= $empId ?>" data-rate="<?= $effectiveRate ?>">
                                <td class="sticky-col-1 text-muted"><?= $sl++ ?></td>
                                <td class="sticky-col-2 fw-semibold">
                                    <?= htmlspecialchars($emp['full_name']) ?>
                                    <div class="small text-muted" style="font-size:11px;"><?= htmlspecialchars($emp['designation_name'] ?? '') ?></div>
                                </td>
                                <td class="sticky-col-3">
                                    <span class="badge border" style="background: var(--clr-bg-secondary, rgba(0,0,0,0.05)); color: inherit;"><?= htmlspecialchars($emp['employee_id']) ?></span>
                                </td>
                                <td class="sticky-col-4">
                                    <span class="badge bg-info text-white"><?= number_format($pct, 0) ?>%</span>
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
                                    $isChecked = !$isOptedOut && isset($records[$empId][$d]) && $records[$empId][$d] == 1;
                                    ?>
                                    <td class="text-center <?= $col['is_weekend'] ? 'weekend-col' : '' ?> <?= $isOptedOut ? 'bg-danger bg-opacity-10' : '' ?>" style="position: relative;">
                                        <?php if ($isOptedOut): ?>
                                            <span class="badge bg-danger p-1" style="font-size: 9px; cursor: help;" title="Off by Employee (১২টার আগে মিল অফ করা হয়েছে)">
                                                <i class="fa-solid fa-ban"></i> Off
                                            </span>
                                            <input type="checkbox" 
                                                   class="meal-checkbox d-none" 
                                                   data-emp-id="<?= $empId ?>" 
                                                   data-date="<?= $d ?>" 
                                                   data-opt-out="1"
                                                   disabled>
                                        <?php else: ?>
                                            <input type="checkbox" 
                                                   class="meal-checkbox" 
                                                   data-emp-id="<?= $empId ?>" 
                                                   data-date="<?= $d ?>" 
                                                   <?= $isChecked ? 'checked' : '' ?>
                                                   title="<?= htmlspecialchars($emp['full_name']) ?> (<?= $col['day_num'] ?> <?= $col['month_short'] ?>)">
                                        <?php endif; ?>
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

            // Rule 4 (Stacking Context Fix): Append modal to body directly so it is never trapped in .page__body-wrapper
            if ($('#createMealProjectModal').parent().prop('tagName') !== 'BODY') {
                $('#createMealProjectModal').appendTo('body');
            }

            // Rule 3 (Dual Event Binding): Direct delegated click fallback for modal trigger
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
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModuleScripts);
    } else {
        initModuleScripts();
    }
})();
</script>

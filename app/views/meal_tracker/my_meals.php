<?php
$page = "My Meal Tracker";
$show_breadcrumb = true;
$base_url = $GLOBALS['base_url'] ?? '';
?>

<!-- Custom CSS for My Meal Tracker -->
<style>
.meal-badge-consumed {
    background-color: rgba(16, 185, 129, 0.15) !important;
    color: #059669 !important;
    border: 1px solid rgba(16, 185, 129, 0.3) !important;
    font-weight: 600;
    padding: 6px 12px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.meal-badge-skipped {
    background-color: rgba(239, 68, 68, 0.12) !important;
    color: #dc2626 !important;
    border: 1px solid rgba(239, 68, 68, 0.25) !important;
    font-weight: 600;
    padding: 6px 12px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.meal-badge-scheduled {
    background-color: rgba(107, 114, 128, 0.12) !important;
    color: #6b7280 !important;
    border: 1px solid rgba(107, 114, 128, 0.25) !important;
    font-weight: 500;
    padding: 6px 12px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.meal-today-row {
    background-color: rgba(15, 118, 110, 0.08) !important;
    border-left: 4px solid #0f766e !important;
}
.stat-widget {
    border-radius: 12px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.stat-widget:hover {
    transform: translateY(-2px);
}
@media print {
    body * {
        visibility: hidden;
    }
    #printableMealArea, #printableMealArea * {
        visibility: visible;
    }
    #printableMealArea {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
    .no-print {
        display: none !important;
    }
}
</style>

<?php if (empty($is_enrolled)): ?>
    <!-- Notice for Unenrolled Employee -->
    <div class="card__wrapper p-5 text-center mb-4">
        <div class="mb-3">
            <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 72px; height: 72px; background: rgba(245, 158, 11, 0.15); color: #d97706;">
                <i class="fa-solid fa-utensils fs-2"></i>
            </span>
        </div>
        <h4 class="fw-bold mb-2">Meal Deduction Service Not Enabled</h4>
        <p class="text-muted mx-auto" style="max-width: 520px;">
            Your employee profile is currently not enrolled in the company subsidized lunch service (<code>is_meal_deduction = 0</code>). 
            If you would like to join the meal program, please contact HR or Operation management.
        </p>
        <div class="mt-3">
            <a href="<?= $base_url ?>/employee-dashboard" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>
    </div>
<?php else: ?>

<div id="printableMealArea">
    <!-- Header & Cycle Selector -->
    <div class="card__wrapper p-4 mb-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white shadow-sm"
                     style="width: 54px; height: 54px; background: linear-gradient(135deg, #0f766e, #0d9488); font-size: 24px;">
                    <i class="fa-solid fa-bowl-food"></i>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h4 class="fw-bold mb-0"><?= htmlspecialchars($employee['full_name'] ?? 'My Meal Tracker') ?></h4>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                            <i class="fa-solid fa-circle-check me-1"></i> Meal Service Active
                        </span>
                    </div>
                    <div class="text-muted small mt-1">
                        <span><strong>ID:</strong> <?= htmlspecialchars($employee['employee_id'] ?? 'N/A') ?></span>
                        <span class="mx-2">•</span>
                        <span><?= htmlspecialchars($employee['designation_name'] ?? 'Designation') ?></span>
                        <span class="mx-2">•</span>
                        <span><?= htmlspecialchars($employee['department_name'] ?? 'Department') ?></span>
                        <span class="mx-2">•</span>
                        <span><?= htmlspecialchars($employee['branch_name'] ?? 'Branch') ?></span>
                    </div>
                </div>
            </div>

            <!-- Cycle Selector & Action Buttons -->
            <div class="d-flex align-items-center gap-2 flex-wrap no-print">
                <form method="GET" action="<?= $base_url ?>/my-meal-tracker" class="d-flex align-items-center gap-2" id="cycleSelectForm">
                    <label class="form-label mb-0 text-muted small fw-semibold text-nowrap">
                        <i class="fa-regular fa-calendar-days me-1"></i> Meal Cycle:
                    </label>
                    <select name="project_id" class="form-select form-select-sm fw-semibold" style="min-width: 260px;" onchange="this.form.submit()">
                        <?php foreach ($mealProjects as $proj): ?>
                            <?php 
                            $isSel = ((int)$proj['id'] === (int)$selectedProjectId); 
                            $dateTxt = (!empty($proj['from_date']) && !empty($proj['to_date']))
                                ? date('d M', strtotime($proj['from_date'])) . ' - ' . date('d M Y', strtotime($proj['to_date']))
                                : '';
                            ?>
                            <option value="<?= (int)$proj['id'] ?>" <?= $isSel ? 'selected' : '' ?>>
                                <?= htmlspecialchars($proj['name']) ?> (<?= $dateTxt ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <button type="button" class="btn btn-sm btn-outline-secondary fw-semibold" onclick="window.print()" title="Print Meal Slip">
                    <i class="fa-solid fa-print me-1"></i> Print
                </button>
            </div>
        </div>

        <?php if (!empty($activeProject)): ?>
            <div class="mt-3 pt-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap text-muted small">
                    <span class="badge border" style="background: var(--clr-bg-card, transparent); color: inherit; border-color: var(--clr-border-1, #e5e7eb) !important;">
                        <i class="fa-regular fa-clock me-1 text-primary"></i> 
                        Cycle Period: <strong><?= date('d M Y', strtotime($activeProject['from_date'])) ?></strong> to <strong><?= date('d M Y', strtotime($activeProject['to_date'])) ?></strong>
                    </span>
                    <span class="badge border" style="background: var(--clr-bg-card, transparent); color: inherit; border-color: var(--clr-border-1, #e5e7eb) !important;">
                        Standard Meal: <strong>৳<?= number_format($stats['standard_rate'], 2) ?></strong>
                    </span>
                    <span class="badge border" style="background: var(--clr-bg-card, transparent); color: inherit; border-color: var(--clr-border-1, #e5e7eb) !important;">
                        Subsidy Rate: <strong><?= number_format($stats['percentage'], 0) ?>%</strong>
                    </span>
                </div>

                <?php if (!empty($stats['latest_updated'])): ?>
                    <div class="text-muted small">
                        <i class="fa-solid fa-rotate text-success me-1"></i>
                        Last Live Sync: <strong><?= date('d M Y, h:i A', strtotime($stats['latest_updated'])) ?></strong>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Summary Stat Cards -->
    <div class="row  mb-4">
        <!-- Card 1: Total Meals Consumed -->
        <div class="col-sm-6 col-l">
            <div class="card__wrapper p-3 stat-widget border-top border-4" style="border-top-color: #10b981 !important;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Total Meals Counted</div>
                        <h3 class="fw-bold mb-0 text-success mt-1">
                            <?= (int)$stats['total_consumed'] ?> <small class="fs-6 fw-normal text-muted">/ <?= (int)$stats['total_days'] ?> Days</small>
                        </h3>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" 
                         style="width: 46px; height: 46px; background: rgba(16, 185, 129, 0.15); color: #10b981; font-size: 20px;">
                        <i class="fa-solid fa-bowl-rice"></i>
                    </div>
                </div>
                <div class="mt-2 small text-muted">
                    <span class="text-danger fw-semibold"><i class="fa-solid fa-ban me-1"></i><?= (int)$stats['total_skipped'] ?></span> off / skipped days
                </div>
            </div>
        </div>

        <!-- Card 2: Per-Meal Deductible Rate -->
        <div class="col-sm-6 col-l">
            <div class="card__wrapper p-3 stat-widget border-top border-4" style="border-top-color: #0284c7 !important;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Your Meal Rate</div>
                        <h3 class="fw-bold mb-0 text-primary mt-1">
                            ৳<?= number_format($stats['employee_rate'], 2) ?>
                        </h3>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" 
                         style="width: 46px; height: 46px; background: rgba(2, 132, 199, 0.15); color: #0284c7; font-size: 20px;">
                        <i class="fa-solid fa-tags"></i>
                    </div>
                </div>
                <div class="mt-2 small text-muted">
                    Standard: ৳<?= number_format($stats['standard_rate'], 2) ?> (<?= number_format($stats['percentage'], 0) ?>%)
                </div>
            </div>
        </div>

        <!-- Card 3: Total Salary Deduction -->
        <div class="col-sm-6 col-l">
            <div class="card__wrapper p-3 stat-widget border-top border-4" style="border-top-color: #ef4444 !important;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Total Salary Deduction</div>
                        <h3 class="fw-bold mb-0 text-danger mt-1">
                            ৳<?= number_format($stats['total_deduction'], 2) ?>
                        </h3>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" 
                         style="width: 46px; height: 46px; background: rgba(239, 68, 68, 0.15); color: #ef4444; font-size: 20px;">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                </div>
                <div class="mt-2 small text-muted">
                    <?= (int)$stats['total_consumed'] ?> meals &times; ৳<?= number_format($stats['employee_rate'], 2) ?> in payroll
                </div>
            </div>
        </div>

        <!-- Card 4: Company Subsidy Provided -->
        <div class="col-sm-6 col-l">
            <div class="card__wrapper p-3 stat-widget border-top border-4" style="border-top-color: #8b5cf6 !important;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Company Subsidy Saved</div>
                        <h3 class="fw-bold mb-0 text-purple mt-1" style="color: #8b5cf6;">
                            ৳<?= number_format($stats['total_company_subsidy'], 2) ?>
                        </h3>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" 
                         style="width: 46px; height: 46px; background: rgba(139, 92, 246, 0.15); color: #8b5cf6; font-size: 20px;">
                        <i class="fa-solid fa-hand-holding-dollar"></i>
                    </div>
                </div>
                <div class="mt-2 small text-muted">
                    Benefit covered by employer
                </div>
            </div>
        </div>
    </div>

    <!-- Day-by-Day Meal Breakdown -->
    <div class="card__wrapper p-4 mb-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
            <div>
                <h5 class="fw-bold mb-1">
                    <i class="fa-solid fa-calendar-check text-primary me-2"></i> 26 to 25 Daily Meal Breakdown
                </h5>
                <small class="text-muted">Detailed record of each day in the cycle showing whether lunch was counted, opted out, or off.</small>
            </div>

            <!-- Quick Filter Buttons -->
            <div class="d-flex align-items-center gap-2 no-print flex-wrap">
                <div class="btn-group btn-group-sm" role="group" id="filterButtonGroup">
                    <button type="button" class="btn btn-outline-secondary active" onclick="filterTable('all')">
                        All (<?= count($dates) ?>)
                    </button>
                    <button type="button" class="btn btn-outline-success" onclick="filterTable('consumed')">
                        <i class="fa-solid fa-check me-1"></i> Counted (<?= (int)$stats['total_consumed'] ?>)
                    </button>
                    <button type="button" class="btn btn-outline-danger" onclick="filterTable('skipped')">
                        <i class="fa-solid fa-xmark me-1"></i> Off / Skipped (<?= (int)$stats['total_skipped'] ?>)
                    </button>
                    <?php if ((int)$stats['total_scheduled'] > 0): ?>
                    <button type="button" class="btn btn-outline-secondary" onclick="filterTable('scheduled')">
                        <i class="fa-regular fa-clock me-1"></i> Upcoming (<?= (int)$stats['total_scheduled'] ?>)
                    </button>
                    <?php endif; ?>
                </div>

                <div class="input-group input-group-sm" style="width: 200px;">
                    <span class="input-group-text"><i class="fa-solid fa-search"></i></span>
                    <input type="text" id="tableSearch" class="form-control" placeholder="Search date..." onkeyup="searchTable()">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="dailyMealTable">
                <thead style="background: var(--clr-bg-secondary, rgba(0,0,0,0.03)); color: inherit;">
                    <tr class="text-uppercase small fw-bold">
                        <th style="width: 50px;" class="text-center">#</th>
                        <th style="width: 140px;">Date</th>
                        <th style="width: 110px;">Day</th>
                        <th style="width: 160px;">Meal Status</th>
                        <th style="width: 120px;" class="text-end">Deduction (৳)</th>
                        <th>Attendance Context</th>
                        <th>Opt-Out / Note</th>
                        <th style="width: 160px;" class="text-end">Last Updated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dates)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="fa-solid fa-calendar-xmark fs-2 mb-2 d-block"></i>
                                No date records found for this meal project.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $sl = 1;
                        foreach ($dates as $d): 
                            $dtObj = new DateTime($d);
                            $dayName = $dtObj->format('l');
                            $isWeekend = in_array($dayName, ['Friday', 'Saturday']);
                            $isToday = ($d === $today);
                            $isFuture = ($d > $today);

                            $rec = $records[$d] ?? null;
                            $isConsumed = isset($rec['is_consumed']) ? (int)$rec['is_consumed'] : null;
                            $updatedAt = $rec['updated_at'] ?? null;

                            $opt = $optOuts[$d] ?? null;
                            $att = $attendances[$d] ?? null;

                            // Determine status type for filtering
                            $statusType = 'skipped';
                            if ($isConsumed === 1) {
                                $statusType = 'consumed';
                            } elseif ($isFuture && $isConsumed === null) {
                                $statusType = 'scheduled';
                            }
                        ?>
                            <tr class="<?= $isToday ? 'meal-today-row' : '' ?>" data-status-type="<?= $statusType ?>">
                                <td class="text-center text-muted fw-semibold">
                                    <?= $sl++ ?>
                                </td>
                                <td>
                                    <div class="fw-bold d-flex align-items-center gap-2">
                                        <?= date('d M Y', strtotime($d)) ?>
                                        <?php if ($isToday): ?>
                                            <span class="badge bg-primary px-2 py-0" style="font-size: 10px;">TODAY</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($isWeekend): ?>
                                        <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                                            <?= $dayName ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted fw-semibold"><?= $dayName ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isConsumed === 1): ?>
                                        <span class="meal-badge-consumed">
                                            <i class="fa-solid fa-circle-check text-success"></i> Meal Counted
                                        </span>
                                    <?php elseif ($isConsumed === 0): ?>
                                        <span class="meal-badge-skipped">
                                            <i class="fa-solid fa-circle-xmark text-danger"></i> No Meal
                                        </span>
                                    <?php else: ?>
                                        <?php if ($isFuture): ?>
                                            <span class="meal-badge-scheduled">
                                                <i class="fa-regular fa-clock"></i> Upcoming
                                            </span>
                                        <?php else: ?>
                                            <span class="meal-badge-skipped">
                                                <i class="fa-solid fa-minus"></i> Not Counted
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold <?= ($isConsumed === 1) ? 'text-danger' : 'text-muted' ?>">
                                    <?php if ($isConsumed === 1): ?>
                                        ৳<?= number_format($stats['employee_rate'], 2) ?>
                                    <?php else: ?>
                                        ৳0.00
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($att)): ?>
                                        <div class="small">
                                            <span class="badge bg-info-subtle text-info border border-info-subtle me-1">
                                                <?= htmlspecialchars($att['today_status'] ?? 'Recorded') ?>
                                            </span>
                                            <?php if (!empty($att['punch_in_time'])): ?>
                                                <span class="text-muted">In: <?= date('h:i A', strtotime($att['punch_in_time'])) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($opt)): ?>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle small">
                                            <i class="fa-solid fa-hand me-1"></i> Opted out 
                                            <?= !empty($opt['opt_out_time']) ? 'at ' . date('h:i A', strtotime($opt['opt_out_time'])) : '' ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end small text-muted">
                                    <?php if (!empty($updatedAt)): ?>
                                        <?= date('d M, h:i A', strtotime($updatedAt)) ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot style="background: var(--clr-bg-secondary, rgba(0,0,0,0.03));">
                    <tr class="fw-bold">
                        <td colspan="3" class="text-end">Total Counted Meals:</td>
                        <td>
                            <span class="badge bg-success px-3 py-2 fs-6">
                                <?= (int)$stats['total_consumed'] ?> Meals
                            </span>
                        </td>
                        <td class="text-end text-danger fs-6">
                            ৳<?= number_format($stats['total_deduction'], 2) ?>
                        </td>
                        <td colspan="3" class="text-muted small">
                            Rate: ৳<?= number_format($stats['employee_rate'], 2) ?> / meal (Subsidy: <?= number_format($stats['percentage'], 0) ?>%)
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script>
function filterTable(type) {
    // Update active button state
    const btnGroup = document.getElementById('filterButtonGroup');
    const buttons = btnGroup.getElementsByTagName('button');
    for (let btn of buttons) {
        btn.classList.remove('active');
    }
    event.target.classList.add('active');

    // Filter table rows
    const table = document.getElementById('dailyMealTable');
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const rowType = row.getAttribute('data-status-type');
        if (type === 'all' || rowType === type) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function searchTable() {
    const input = document.getElementById('tableSearch').value.toLowerCase();
    const table = document.getElementById('dailyMealTable');
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        if (text.includes(input)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<?php endif; ?>

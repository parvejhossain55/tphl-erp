<?php
$page = "Meal Tracker History";
$show_breadcrumb = true;
?>

<div class="card__wrapper p-3 mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="fa-solid fa-chart-pie text-primary me-2"></i> Monthly Meal History & Branch Cost
            </h4>
            <small class="text-muted">Overview of total employee meals, guest meals, and total catering billing per branch.</small>
        </div>
        <a href="<?= $GLOBALS['base_url'] ?>/meal-tracker" class="btn btn-outline-secondary fw-semibold">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Meal Tracker
        </a>
    </div>
</div>

<div class="row g-4">
    <?php if (empty($historyData)): ?>
        <div class="col-12">
            <div class="card__wrapper p-5 text-center">
                <i class="fa-solid fa-calendar-xmark text-muted fs-1 mb-3"></i>
                <h5>No Meal Projects Recorded Yet</h5>
                <p class="text-muted">Create a meal project and start tracking daily meals to see branch-wise bill history.</p>
                <a href="<?= $GLOBALS['base_url'] ?>/meal-tracker" class="btn btn-primary" style="background: #0f766e; border-color: #0f766e;">
                    <i class="fa-solid fa-plus me-1"></i> Go to Meal Tracker
                </a>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($historyData as $hist): ?>
            <?php 
            $proj = $hist['project']; 
            $standardRate = (float)$proj['standard_meal_amount'];
            ?>
            <div class="col-12">
                <div class="card__wrapper p-4 border-top border-4" style="border-top-color: #0f766e !important;">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3 border-bottom pb-3">
                        <div>
                            <h5 class="fw-bold mb-1"><?= htmlspecialchars($proj['name']) ?></h5>
                            <span class="badge border" style="background: var(--clr-bg-card, transparent); color: inherit; border-color: var(--clr-border-1, #e5e7eb) !important;">
                                <i class="fa-regular fa-calendar me-1"></i> 
                                <?= date('d M Y', strtotime($proj['from_date'])) ?> &rarr; <?= date('d M Y', strtotime($proj['to_date'])) ?>
                            </span>
                            <span class="badge border ms-2" style="background: var(--clr-bg-card, transparent); color: inherit; border-color: var(--clr-border-1, #e5e7eb) !important;">
                                Rate: <strong>৳<?= number_format($standardRate, 2) ?></strong>
                            </span>
                        </div>
                        <div class="text-end">
                            <div class="text-muted small">Grand Total Bill</div>
                            <h4 class="fw-bold text-success mb-0">৳<?= number_format($hist['grand_total_bill'], 2) ?></h4>
                        </div>
                    </div>

                    <!-- Branch Table Breakdown -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle mb-0">
                            <thead style="background: var(--clr-bg-secondary, rgba(0,0,0,0.03)); color: inherit;">
                                <tr>
                                    <th>SL</th>
                                    <th>Office Branch</th>
                                    <th class="text-center">Employee Meals</th>
                                    <th class="text-center">Guest Meals</th>
                                    <th class="text-center">Total Meals</th>
                                    <th class="text-end">Meal Rate</th>
                                    <th class="text-end">Total Branch Cost</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($hist['branches'])): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-3 text-muted">No meal records found for this period.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php $bSl = 1; foreach ($hist['branches'] as $b): ?>
                                        <tr>
                                            <td><?= $bSl++ ?></td>
                                            <td class="fw-semibold">
                                                <i class="fa-solid fa-building text-primary me-2"></i> <?= htmlspecialchars($b['branch_name']) ?>
                                            </td>
                                            <td class="text-center fw-bold text-secondary"><?= number_format($b['employee_meals']) ?></td>
                                            <td class="text-center fw-bold text-warning"><?= number_format($b['guest_meals']) ?></td>
                                            <td class="text-center fw-bold text-primary"><?= number_format($b['total_meals']) ?></td>
                                            <td class="text-end text-muted">৳<?= number_format($standardRate, 2) ?></td>
                                            <td class="text-end fw-bold">৳<?= number_format($b['total_cost'], 2) ?></td>
                                            <td class="text-center">
                                                <a href="<?= $GLOBALS['base_url'] ?>/meal-tracker?project_id=<?= (int)$proj['id'] ?>&branch_id=<?= (int)$b['branch_id'] ?>" 
                                                   class="btn btn-sm btn-outline-primary py-1 px-2">
                                                    <i class="fa-solid fa-eye me-1"></i> View Grid
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <!-- Total Summary Row -->
                                    <tr class="fw-bold" style="background: rgba(15, 118, 110, 0.08);">
                                        <td colspan="2" class="text-end">Total:</td>
                                        <td class="text-center"><?= number_format($hist['total_employee_meals']) ?></td>
                                        <td class="text-center"><?= number_format($hist['total_guest_meals']) ?></td>
                                        <td class="text-center"><?= number_format($hist['grand_total_meals']) ?></td>
                                        <td class="text-end">-</td>
                                        <td class="text-end text-success fs-6">৳<?= number_format($hist['grand_total_bill'], 2) ?></td>
                                        <td></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

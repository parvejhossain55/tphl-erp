<?php
// Get approval badge
$statusBadge = match($plan['approved_status'] ?? 'Pending') {
    'Approved' => '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Approved</span>',
    'Rejected' => '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Rejected</span>',
    default    => '<span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Pending</span>',
};
$monthName = date('F', mktime(0, 0, 0, $plan['month'], 10));
?>

<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-working-plans">Working Plans</a></li>
                <li class="breadcrumb-item active">View Plan</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $base_url ?>/con-working-plan-print/<?= $plan['id'] ?>"
           target="_blank"
           class="btn btn-primary">
            <i class="bi bi-printer me-1"></i> Print / PDF
        </a>
        <a href="<?= $base_url ?>/con-working-plan-edit/<?= $plan['id'] ?>" class="btn btn-outline-warning">
            <i class="bi bi-pencil-square me-1"></i> Edit
        </a>
        <a href="<?= $base_url ?>/con-working-plans" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
    </div>
</div>

<section class="section">
    <div class="row g-4">

        <!-- ── Left: Plan Details Card ───────────────────── -->
        <div class="col-lg-8">
            <div class="card__wrapper shadow-sm h-100">
                <div class="card__body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <h5 class="fw-bold text-primary mb-0">
                            <i class="bi bi-calendar-check me-2"></i>Plan Details
                        </h5>
                        <?= $statusBadge ?>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="text-muted small fw-semibold text-uppercase">Project</label>
                            <div class="fw-bold fs-6"><?= htmlspecialchars($plan['project_name'] ?? '—') ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small fw-semibold text-uppercase">Budget Reference</label>
                            <div>
                                <span class="badge border text-dark">Budget #<?= $plan['initial_budget_id'] ?></span>
                                <?php if ($plan['budget_total']): ?>
                                    <div class="small text-muted mt-1">৳ <?= number_format($plan['budget_total'], 2) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small fw-semibold text-uppercase">Work Type</label>
                            <div>
                                <span class="badge bg-info text-dark"><?= htmlspecialchars($plan['work_type_name'] ?? '—') ?></span>
                                <?php if (!empty($plan['design_type_name'])): ?>
                                    <span class="badge bg-light text-secondary ms-1"><?= htmlspecialchars($plan['design_type_name']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small fw-semibold text-uppercase">Period</label>
                            <div>
                                <span class="badge bg-secondary fs-6 px-3 py-1"><?= $monthName ?> <?= $plan['year'] ?></span>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="text-muted small fw-semibold text-uppercase">Work Description</label>
                            <div class="bg-light rounded p-3 mt-1" style="min-height:60px;">
                                <?= nl2br(htmlspecialchars($plan['work_description'] ?? '—')) ?>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <label class="text-muted small fw-semibold text-uppercase">Planned Quantity</label>
                            <div class="fw-bold"><?= number_format($plan['planned_quantity'] ?? 0, 2) ?></div>
                        </div>
                        <div class="col-sm-4">
                            <label class="text-muted small fw-semibold text-uppercase">Estimated Cost</label>
                            <div class="fw-bold text-primary fs-5">৳ <?= number_format($plan['estimated_cost'], 2) ?></div>
                        </div>
                        <div class="col-sm-4">
                            <label class="text-muted small fw-semibold text-uppercase">Weight (%)</label>
                            <div class="fw-bold text-success fs-5"><?= number_format($plan['weight_percentage'] ?? 0, 2) ?>%</div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="text-muted small fw-semibold text-uppercase">Created By</label>
                            <div><?= htmlspecialchars($plan['created_by_name'] ?? '—') ?></div>
                        </div>
                        <?php if (!empty($plan['approved_by_name'])): ?>
                        <div class="col-sm-6">
                            <label class="text-muted small fw-semibold text-uppercase">Approved By</label>
                            <div><?= htmlspecialchars($plan['approved_by_name']) ?>
                                <?php if (!empty($plan['approved_by_role'])): ?>
                                    <small class="text-muted">(<?= htmlspecialchars($plan['approved_by_role']) ?>)</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Right: Budget Summary ──────────────────────── -->
        <div class="col-lg-4">
            <div class="card__wrapper shadow-sm mb-4">
                <div class="card__body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-wallet2 me-2 text-primary"></i>Budget Summary</h6>
                    <?php
                    $initial   = $budgetSummary['initial_budget'] ?? 0;
                    $revised   = $budgetSummary['revised_total'] ?? 0;
                    $planned   = $budgetSummary['total_planned'] ?? 0;
                    $remaining = $budgetSummary['remaining_to_plan'] ?? 0;
                    $pct = $revised > 0 ? min(100, ($planned / $revised) * 100) : 0;
                    ?>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Initial Budget</span>
                        <span class="fw-semibold">৳ <?= number_format($initial, 2) ?></span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Revised Total</span>
                        <span class="fw-semibold">৳ <?= number_format($revised, 2) ?></span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Total Planned</span>
                        <span class="fw-semibold text-primary">৳ <?= number_format($planned, 2) ?></span>
                    </div>
                    <div class="mb-3 d-flex justify-content-between">
                        <span class="text-muted small">Remaining</span>
                        <span class="fw-bold <?= $remaining < 0 ? 'text-danger' : 'text-success' ?>">
                            ৳ <?= number_format($remaining, 2) ?>
                        </span>
                    </div>
                    <div class="progress" style="height:8px;">
                        <div class="progress-bar <?= $pct >= 100 ? 'bg-danger' : 'bg-primary' ?>"
                             style="width:<?= $pct ?>%"></div>
                    </div>
                    <div class="small text-muted mt-1 text-end"><?= number_format($pct, 1) ?>% utilized</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card__wrapper shadow-sm">
                <div class="card__body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-lightning me-2 text-warning"></i>Quick Actions</h6>
                    <div class="d-grid gap-2">
                        <a href="<?= $base_url ?>/con-working-plan-print/<?= $plan['id'] ?>"
                           target="_blank"
                           class="btn btn-primary">
                            <i class="bi bi-printer me-2"></i>Print / Export PDF
                        </a>
                        <a href="<?= $base_url ?>/con-working-plan-edit/<?= $plan['id'] ?>"
                           class="btn btn-outline-warning">
                            <i class="bi bi-pencil-square me-2"></i>Edit This Plan
                        </a>
                        <a href="<?= $base_url ?>/con-working-plans"
                           class="btn btn-outline-secondary">
                            <i class="bi bi-list-ul me-2"></i>All Working Plans
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Activities & Gantt Chart ─────────────────── -->
        <div class="col-12 mb-4">
            <div class="card__wrapper shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-3 border-bottom">
                    <h6 class="card-title mb-0 fw-bold text-dark text-uppercase">
                        <i class="bi bi-list-task me-2 text-primary"></i>কাজ ও অ্যাক্টিভিটি শিডিউল (Schedule Activities/Phases)
                    </h6>
                    <span class="badge bg-primary-transparent text-primary fw-bold"><?= count($planItems) ?> Activities</span>
                </div>
                <div class="card__body p-4">
                    
                    <!-- Part 1: Activities Table -->
                    <h6 class="fw-bold mb-3 text-secondary" style="font-size:0.92rem;"><i class="bi bi-table me-2 text-primary"></i>১. কাজের তালিকা ও অবস্থান (Activities List)</h6>
                    <div class="table-responsive mb-5">
                        <table class="table table-bordered table-striped table-hover align-middle mb-0" style="font-size: 0.88rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width: 5%;">নং</th>
                                    <th>কাজের বিবরণ ও অবস্থান (Activity & Location)</th>
                                    <th style="width: 15%;">ফ্লোর (Floor)</th>
                                    <th class="text-center" style="width: 12%;">পরিমাণ (Quantity)</th>
                                    <th class="text-center" style="width: 12%;">শুরুর তারিখ</th>
                                    <th class="text-center" style="width: 10%;">প্রয়োজনীয় দিন</th>
                                    <th class="text-center" style="width: 12%;">শেষের তারিখ</th>
                                    <th style="width: 15%;">মন্তব্য (Remarks)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($planItems)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">No activities registered for this plan.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($planItems as $idx => $item): ?>
                                        <tr>
                                            <td class="text-center fw-bold text-primary"><?= htmlspecialchars($item['sl_no'] ?: ($idx + 1)) ?></td>
                                            <td class="fw-semibold text-dark"><?= htmlspecialchars($item['activity_name']) ?></td>
                                            <td>
                                                <?php if (!empty($item['floor_name'])): ?>
                                                    <span class="badge bg-secondary text-white"><?= htmlspecialchars($item['floor_name']) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center"><?= htmlspecialchars($item['quantity'] ?: '1 Job') ?></td>
                                            <td class="text-center"><?= date('d M Y', strtotime($item['start_date'])) ?></td>
                                            <td class="text-center fw-bold text-info-emphasis"><?= (int)$item['req_days'] ?> দিন</td>
                                            <td class="text-center"><?= date('d M Y', strtotime($item['end_date'])) ?></td>
                                            <td class="text-muted small"><?= htmlspecialchars($item['remarks'] ?? '—') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Part 2: Gantt Chart -->
                    <h6 class="fw-bold mb-3 text-secondary" style="font-size:0.92rem;"><i class="bi bi-calendar-range me-2 text-primary"></i>২. সময়সূচী গ্যান্ট চার্ট (Weekly Gantt Chart / Schedule)</h6>
                    
                    <?php
                    // Gantt calculation logic
                    $minDate = null;
                    $maxDate = null;
                    if (!empty($planItems)) {
                        foreach ($planItems as $item) {
                            $sDate = $item['start_date'];
                            $eDate = $item['end_date'];
                            if (!$minDate || $sDate < $minDate) $minDate = $sDate;
                            if (!$maxDate || $eDate > $maxDate) $maxDate = $eDate;
                        }
                    }

                    if (!$minDate) {
                        $minDate = sprintf('%04d-%02d-01', $plan['year'], $plan['month']);
                        $maxDate = date('Y-m-t', strtotime($minDate));
                    } else {
                        // Align to nearest Monday and Sunday of start/end weeks
                        $minTime = strtotime($minDate);
                        $maxTime = strtotime($maxDate);
                        
                        $startDayOfWeek = date('N', $minTime);
                        $minTime = $minTime - ($startDayOfWeek - 1) * 86400;
                        $minDate = date('Y-m-d', $minTime);
                        
                        $endDayOfWeek = date('N', $maxTime);
                        $maxTime = $maxTime + (7 - $endDayOfWeek) * 86400;
                        $maxDate = date('Y-m-d', $maxTime);
                    }

                    $weeks = [];
                    $currTime = strtotime($minDate);
                    $endTime = strtotime($maxDate);
                    $wCount = 1;
                    while ($currTime <= $endTime) {
                        $wStart = date('Y-m-d', $currTime);
                        $wEnd = date('Y-m-d', $currTime + 6 * 86400);
                        $weeks[] = [
                            'label' => 'W' . $wCount,
                            'start' => $wStart,
                            'end' => $wEnd,
                            'start_display' => date('d M', strtotime($wStart)),
                            'end_display' => date('d M', strtotime($wEnd))
                        ];
                        $currTime += 7 * 86400;
                        $wCount++;
                    }
                    ?>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle text-center mb-0" style="table-layout: fixed; min-width: 800px; font-size: 0.82rem;">
                            <thead>
                                <tr class="table-light">
                                    <th class="text-start" style="width: 280px; font-size: 0.85rem; font-weight: bold;">Activity / Phase</th>
                                    <?php foreach ($weeks as $w): ?>
                                        <th style="font-size: 0.76rem; font-weight: 600;">
                                            <div class="text-primary"><?= $w['label'] ?></div>
                                            <div class="text-muted" style="font-size: 0.68rem; font-weight: 400;"><?= $w['start_display'] ?> - <?= $w['end_display'] ?></div>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($planItems)): ?>
                                    <tr>
                                        <td colspan="<?= count($weeks) + 1 ?>" class="text-muted py-4 text-start ps-3">No schedule data available.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($planItems as $idx => $item): 
                                        $itemStart = strtotime($item['start_date']);
                                        $itemEnd = strtotime($item['end_date']);
                                    ?>
                                        <tr>
                                            <td class="text-start text-dark fw-semibold text-truncate" style="max-width: 280px;" title="<?= htmlspecialchars($item['activity_name']) ?>">
                                                <span class="text-muted me-1" style="font-size:0.75rem;"><?= htmlspecialchars($item['sl_no'] ?: ($idx+1)) ?>.</span>
                                                <?= htmlspecialchars($item['activity_name']) ?>
                                                <?php if(!empty($item['floor_name'])): ?>
                                                    <span class="d-block small text-muted font-monospace" style="font-size:0.7rem;">Floor: <?= htmlspecialchars($item['floor_name']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <?php foreach ($weeks as $w): 
                                                $wStart = strtotime($w['start']);
                                                $wEnd = strtotime($w['end']);
                                                
                                                // Check intersection
                                                $intersects = ($itemStart <= $wEnd && $itemEnd >= $wStart);
                                                
                                                // Check if start week
                                                $isStartWeek = ($itemStart >= $wStart && $itemStart <= $wEnd);
                                                // Check if end week
                                                $isEndWeek = ($itemEnd >= $wStart && $itemEnd <= $wEnd);
                                                
                                                $barStyle = '';
                                                $cellStyle = '';
                                                if ($intersects) {
                                                    $cellStyle = 'background: rgba(13, 110, 253, 0.04);';
                                                    $barStyle = 'background: rgba(13, 110, 253, 0.2); border-top: 2px solid #0d6efd; border-bottom: 2px solid #0d6efd; color: #0d6efd;';
                                                    if ($isStartWeek) {
                                                        $barStyle .= ' border-left: 4px solid #0d6efd; border-top-left-radius: 4px; border-bottom-left-radius: 4px;';
                                                    }
                                                    if ($isEndWeek) {
                                                        $barStyle .= ' border-right: 4px solid #0d6efd; border-top-right-radius: 4px; border-bottom-right-radius: 4px;';
                                                    }
                                                }
                                            ?>
                                                <td class="p-1" style="height: 52px; vertical-align: middle; <?= $cellStyle ?>">
                                                    <?php if ($intersects): ?>
                                                        <div class="py-1 px-1 fw-bold" style="font-size: 0.72rem; <?= $barStyle ?>">
                                                            <?= $item['req_days'] ?> Days
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

        <!-- ── All Plans for this Project ─────────────────── -->
        <div class="col-12">
            <div class="card__wrapper shadow-sm">
                <div class="card__body p-0">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-table me-2 text-primary"></i>
                            All Plans — <?= htmlspecialchars($plan['project_name'] ?? '') ?>
                        </h6>
                        <span class="badge bg-primary"><?= count($projectPlans) ?> entries</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Period</th>
                                    <th>Work Type</th>
                                    <th>Description</th>
                                    <th class="text-end">Cost (৳)</th>
                                    <th class="text-end">Weight</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projectPlans as $i => $p): ?>
                                <tr class="<?= $p['id'] == $plan['id'] ? 'table-primary fw-bold' : '' ?>">
                                    <td><?= $i + 1 ?></td>
                                    <td><?= date('M', mktime(0,0,0,$p['month'],10)) ?> <?= $p['year'] ?></td>
                                    <td><span class="badge bg-info text-dark"><?= htmlspecialchars($p['work_type_name'] ?? '—') ?></span></td>
                                    <td>
                                        <div class="text-truncate" style="max-width:200px;" title="<?= htmlspecialchars($p['work_description'] ?? '') ?>">
                                            <?= htmlspecialchars($p['work_description'] ?? '—') ?>
                                        </div>
                                    </td>
                                    <td class="text-end">৳ <?= number_format($p['estimated_cost'], 2) ?></td>
                                    <td class="text-end"><?= number_format($p['weight_percentage'] ?? 0, 2) ?>%</td>
                                    <td class="text-center">
                                        <?php $s = $p['approved_status'] ?? 'Pending'; ?>
                                        <span class="badge <?= $s === 'Approved' ? 'bg-success' : ($s === 'Rejected' ? 'bg-danger' : 'bg-warning text-dark') ?>">
                                            <?= $s ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Total:</td>
                                    <td class="text-end fw-bold text-primary">
                                        ৳ <?= number_format(array_sum(array_column($projectPlans, 'estimated_cost')), 2) ?>
                                    </td>
                                    <td class="text-end fw-bold text-success">
                                        <?= number_format(array_sum(array_column($projectPlans, 'weight_percentage')), 2) ?>%
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<?php
$base_url   = $GLOBALS['base_url'];
$page_title = $page_title ?? 'Work Order Detail';

$statusColors = [
    'draft'          => 'secondary',
    'approved'       => 'info',
    'active'         => 'primary',
    'work_completed' => 'warning',
    'qc_approved'    => 'success',
    'closed'         => 'dark',
    'rejected'       => 'danger',
];
$statusLabels = [
    'draft'          => 'Draft',
    'approved'       => 'Approved',
    'active'         => 'Active',
    'work_completed' => 'Work Completed — Awaiting QC',
    'qc_approved'    => 'QC Approved',
    'closed'         => 'Closed',
    'rejected'       => 'Rejected',
];

$status     = $wo['status'];
$statusCol  = $statusColors[$status] ?? 'secondary';
$statusLbl  = $statusLabels[$status] ?? ucfirst($status);
$totalAmt   = (float)($wo['total_amount'] ?? 0);
// If advance_amount not stored, compute from percent (handles legacy records)
$advAmt     = (float)($wo['advance_amount'] ?? 0);
if ($advAmt == 0 && ($wo['advance_percent'] ?? 0) > 0) {
    $advAmt = round($totalAmt * (float)$wo['advance_percent'] / 100, 2);
}
$retAmt     = (float)($wo['retention_amount'] ?? 0);
if ($retAmt == 0 && ($wo['retention_percent'] ?? 0) > 0) {
    $retAmt = round($totalAmt * (float)$wo['retention_percent'] / 100, 2);
}
$runAmt     = $totalAmt - $advAmt - $retAmt;
?>

<div class="container-fluid">
    <!-- Breadcrumb -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h4 class="page-title fw-semibold text-muted fs-18 mb-0"><?= htmlspecialchars($wo['wo_number'] ?? 'Work Order') ?></h4>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-work-orders">Work Orders</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($wo['wo_number'] ?? '') ?></li>
            </ol>
        </div>
        <div class="d-flex gap-2 my-xl-auto right-content">
            <a href="<?= $base_url ?>/con-work-orders" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            <a href="<?= $base_url ?>/con-work-order-print/<?= $wo['id'] ?>" target="_blank"
               class="btn btn-outline-primary btn-sm">
                <i class="bi bi-printer me-1"></i> Print / PDF
            </a>
            <?php if (in_array($status, ['draft', 'rejected']) && in_array($userRole, [1, 3, 15, 22])): ?>
                <a href="<?= $base_url ?>/con-work-order-edit/<?= $wo['id'] ?>" class="btn btn-outline-warning btn-sm">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Status Banner -->
    <div class="alert alert-<?= $statusCol ?> d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-info-circle-fill fs-18"></i>
        <div>
            <strong>Status:</strong> <?= $statusLbl ?>
            <?php if ($status === 'approved'): ?>
                — Approved by <strong><?= htmlspecialchars($wo['approved_by_name'] ?? '') ?></strong>
                on <?= date('d M Y H:i', strtotime($wo['approved_at'])) ?>
            <?php elseif ($status === 'closed'): ?>
                — QC cleared by <strong><?= htmlspecialchars($wo['qc_approved_by_name'] ?? '') ?></strong>
                on <?= date('d M Y H:i', strtotime($wo['qc_approved_at'])) ?>
            <?php elseif ($status === 'rejected'): ?>
                — Reason: <?= htmlspecialchars($wo['rejection_reason'] ?? '-') ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left Column -->
        <div class="col-xl-8">
            <!-- Work Order Details -->
            <div class="card custom-card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-file-earmark-ruled me-2 text-primary"></i>Work Order Details</h6>
                </div>
                <div class="card-body">
                    <div class="row ">
                        <div class="col-md-6">
                            <div class="text-muted fs-12">WO Number</div>
                            <div class="fw-semibold text-muted"><?= htmlspecialchars($wo['wo_number'] ?? '-') ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted fs-12">Status</div>
                            <div><span class="badge bg-<?= $statusCol ?>-transparent text-<?= $statusCol ?> fs-12"><?= $statusLbl ?></span></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted fs-12">Project</div>
                            <div class="fw-semibold text-muted"><?= htmlspecialchars($wo['project_name'] ?? '-') ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted fs-12">Work Design Type</div>
                            <div class="text-muted"><?= htmlspecialchars($wo['design_type_name'] ?? '-') ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted fs-12">Work Type</div>
                            <div class="text-muted"><?= htmlspecialchars($wo['work_type_name'] ?? '-') ?></div>
                        </div>
                        <div class="col-12">
                            <div class="text-muted fs-12">Title</div>
                            <div class="fw-semibold text-muted fs-15"><?= htmlspecialchars($wo['title'] ?? '-') ?></div>
                        </div>
                        <?php if (!empty($wo['description'])): ?>
                        <div class="col-12">
                            <div class="text-muted fs-12">Description</div>
                            <div class="p-3 bg-light rounded"><?= nl2br(htmlspecialchars($wo['description'])) ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($wo['scope_of_work'])): ?>
                        <div class="col-12">
                            <div class="text-muted fs-12">Scope of Work</div>
                            <div class="p-3 bg-light rounded"><?= nl2br(htmlspecialchars($wo['scope_of_work'])) ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($wo['notes'])): ?>
                        <div class="col-12">
                            <div class="text-muted fs-12">Notes</div>
                            <div class="p-2 border-start border-warning border-3 ps-3"><?= nl2br(htmlspecialchars($wo['notes'])) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ── Rate Schedule Table ─────────────────────────────────── -->
            <?php if (!empty($woItems)): ?>
            <div class="card custom-card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-table me-2 text-success"></i>
                        কাজের বিবরণ ও রেট সিডিউল
                        <span class="badge bg-success-transparent text-success ms-1"><?= count($woItems) ?> items</span>
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0" style="font-size:.84rem">
                            <thead style="background:#1e3a8a;color:#fff">
                                <tr>
                                    <th class="text-center" style="width:50px">নং</th>
                                    <th>বিবরণ (Description)</th>
                                    <th style="width:18%">অবস্থান (Location)</th>
                                    <th style="width:7%" class="text-center">Unit</th>
                                    <th style="width:8%" class="text-end">Qty</th>
                                    <th style="width:9%" class="text-end">Rate (৳)</th>
                                    <th style="width:12%" class="text-end">Subtotal (৳)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $grandTotal = 0;
                                foreach ($woItems as $item):
                                    $sub = (float)$item['quantity'] * (float)$item['rate'];
                                    $grandTotal += $sub;
                                ?>
                                <tr>
                                    <td class="text-center fw-semibold text-primary"><?= htmlspecialchars($item['sl_no']) ?></td>
                                    <td>
                                        <?php if (!empty($item['phase_item_name'])): ?>
                                            <div class="mb-1"><span class="badge bg-secondary-transparent text-secondary border border-secondary">Phase Item: <?= htmlspecialchars($item['phase_item_name']) ?></span></div>
                                        <?php endif; ?>
                                        <div style="white-space:pre-wrap;max-width:280px"><?= nl2br(htmlspecialchars($item['description'])) ?></div>
                                    </td>
                                    <td class="text-muted"><?= htmlspecialchars($item['location'] ?? '') ?></td>
                                    <td class="text-center"><?= strtoupper(htmlspecialchars($item['unit'])) ?></td>
                                    <td class="text-end"><?= number_format((float)$item['quantity'], 2) ?></td>
                                    <td class="text-end">৳<?= number_format((float)$item['rate'], 2) ?></td>
                                    <td class="text-end fw-bold text-primary">৳<?= number_format($sub, 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr style="background:#eff6ff">
                                    <td colspan="6" class="text-end fw-bold text-primary">Grand Total:</td>
                                    <td class="text-end fw-bold text-primary fs-15">৳<?= number_format($grandTotal, 2) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Rate & Budget -->
            <div class="card custom-card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-calculator me-2 text-success"></i>Rate & Budget</h6>
                </div>
                <div class="card-body">
                    <div class="row ">
                        <div class="col-md-3">
                            <div class="text-muted fs-12">Unit</div>
                            <div class="fw-semibold text-muted"><?= strtoupper($wo['unit'] ?? '-') ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted fs-12">Unit Rate</div>
                            <div class="fw-semibold text-muted">৳<?= number_format($wo['unit_rate'] ?? 0, 2) ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted fs-12">Quantity</div>
                            <div class="fw-semibold text-muted"><?= number_format($wo['quantity'] ?? 0, 2) ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted fs-12">Total Amount</div>
                            <div class="fw-bold text-primary fs-16">৳<?= number_format($totalAmt, 2) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Timeline -->
            <div class="card custom-card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-calendar-range me-2 text-warning"></i>Timeline</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="text-muted fs-12">Start Date</div>
                            <div class="fw-semibold text-muted"><?= $wo['start_date'] ? date('d M Y', strtotime($wo['start_date'])) : '—' ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted fs-12">End Date (Deadline)</div>
                            <div class="fw-semibold text-muted <?= ($wo['end_date'] && strtotime($wo['end_date']) < time() && !in_array($status, ['closed','qc_approved'])) ? 'text-danger' : '' ?>">
                                <?= $wo['end_date'] ? date('d M Y', strtotime($wo['end_date'])) : '—' ?>
                                <?php if ($wo['end_date'] && strtotime($wo['end_date']) < time() && !in_array($status, ['closed','qc_approved','work_completed'])): ?>
                                    <span class="badge bg-danger ms-1">Overdue</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Working Plan logic removed as WO is now independent -->

            <!-- ── Update History Timeline ──────────────────────────── -->
            <?php if (!empty($updateLogs)): ?>
            <div class="card custom-card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center"
                     style="background:#fffbeb;border-bottom:2px solid #fbbf24">
                    <h6 class="card-title mb-0 text-warning-emphasis">
                        <i class="bi bi-clock-history me-2 text-warning"></i>
                        Update History
                        <span class="badge bg-warning text-dark ms-1"><?= count($updateLogs) ?></span>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="timeline-container" style="position:relative;padding-left:24px">
                        <!-- Vertical line -->
                        <div style="position:absolute;left:8px;top:0;bottom:0;width:2px;background:#fde68a"></div>

                        <?php foreach ($updateLogs as $log): ?>
                        <div class="mb-4" style="position:relative">
                            <!-- Dot -->
                            <div style="position:absolute;left:-20px;top:4px;width:12px;height:12px;
                                        background:#f59e0b;border-radius:50%;border:2px solid #fff;
                                        box-shadow:0 0 0 2px #fbbf24"></div>

                            <div class="d-flex align-items-start gap-2 flex-wrap">
                                <!-- Date badge -->
                                <span class="badge text-dark fw-bold px-3 py-2"
                                      style="background:#fef3c7;font-size:.85rem;min-width:110px">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    <?= date('d M Y', strtotime($log['update_date'])) ?>
                                </span>
                                <!-- Who updated -->
                                <span class="badge bg-secondary-subtle text-secondary px-2 py-2">
                                    <i class="bi bi-person me-1"></i>
                                    <?= htmlspecialchars($log['updated_by_name'] ?? 'User #' . $log['updated_by']) ?>
                                </span>
                                <small class="text-muted pt-1">
                                    <i class="bi bi-clock me-1"></i>
                                    <?= date('h:i A', strtotime($log['created_at'])) ?>
                                </small>
                            </div>

                            <?php if (!empty($log['update_notes'])): ?>
                            <div class="mt-2 p-3 rounded border-start border-warning border-3"
                                 style="background:#fffdf0;font-size:.9rem">
                                <?= nl2br(htmlspecialchars($log['update_notes'])) ?>
                            </div>
                            <?php else: ?>
                            <div class="mt-1 text-muted small">
                                <em>কোনো notes লেখা হয়নি।</em>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Linked Weekly Reports -->
            <?php if (!empty($linkedWeeklyReports)): ?>
            <div class="card custom-card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-graph-up-arrow me-2 text-success"></i>
                        Weekly Progress Reports
                        <span class="badge bg-success ms-2"><?= count($linkedWeeklyReports) ?></span>
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Report Date</th>
                                    <th>Engineer</th>
                                    <th>Work Done</th>
                                    <th>Progress</th>
                                    <th>Weather</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($linkedWeeklyReports as $wr): ?>
                                <tr>
                                    <td><?= date('d M Y', strtotime($wr['report_date'])) ?></td>
                                    <td><?= htmlspecialchars($wr['engineer_name'] ?? '—') ?></td>
                                    <td>
                                        <span title="<?= htmlspecialchars($wr['work_done']) ?>">
                                            <?= htmlspecialchars(mb_strimwidth($wr['work_done'], 0, 40, '...')) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <div class="progress flex-grow-1" style="height:6px;">
                                                <div class="progress-bar bg-success" style="width:<?= $wr['progress_percentage'] ?>%"></div>
                                            </div>
                                            <small><?= $wr['progress_percentage'] ?>%</small>
                                        </div>
                                    </td>
                                    <td><small><?= htmlspecialchars($wr['weather_condition'] ?? '—') ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Linked Bills -->
            <?php if (!empty($linkedBills)): ?>
            <div class="card custom-card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-receipt me-2 text-info"></i>Linked Contractor Bills</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Bill Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($linkedBills as $bill): ?>
                                <tr>
                                    <td><?= date('d M Y', strtotime($bill['bill_date'])) ?></td>
                                    <td>৳<?= number_format($bill['bill_amount'], 2) ?></td>
                                    <td><span class="badge bg-secondary"><?= ucfirst($bill['status']) ?></span></td>
                                    <td>
                                        <a href="<?= $base_url ?>/con-contractor-bill/<?= $bill['id'] ?>" class="btn btn-xs btn-outline-primary">View</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right Column -->
        <div class="col-xl-4">
            <!-- Contractor Info -->
            <div class="card custom-card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-person-badge me-2 text-primary"></i>Contractor</h6>
                </div>
                <div class="card-body">
                    <div class="fw-bold fs-15 mb-1 text-muted"><?= htmlspecialchars($wo['contractor_name'] ?? '-') ?></div>
                    <?php if (!empty($wo['contractor_trade'])): ?>
                        <div class="text-muted mb-1"><i class="bi bi-tools me-1"></i><?= htmlspecialchars($wo['contractor_trade']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($wo['contractor_phone'])): ?>
                        <div class="text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($wo['contractor_phone']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Payment Terms -->
            <div class="card custom-card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-wallet2 me-2 text-danger"></i>Payment Terms</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3 p-3 rounded bg-info-transparent">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Advance (<?= $wo['advance_percent'] ?? 0 ?>%)</span>
                            <strong class="text-info">৳<?= number_format($advAmt, 2) ?></strong>
                        </div>
                    </div>
                    <div class="mb-3 p-3 rounded bg-primary-transparent">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Running (<?= $wo['running_percent'] ?? 70 ?>%)</span>
                            <strong class="text-primary">৳<?= number_format($runAmt, 2) ?></strong>
                        </div>
                    </div>
                    <div class="p-3 rounded bg-danger-transparent">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Retention (<?= $wo['retention_percent'] ?? 10 ?>%)</span>
                            <strong class="text-danger">৳<?= number_format($retAmt, 2) ?></strong>
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="fw-semibold text-muted">Total</span>
                        <strong class="text-primary fs-15">৳<?= number_format($totalAmt, 2) ?></strong>
                    </div>
                </div>
            </div>

            <!-- ── Sequential Approval Status ──────────────────────────── -->
            <?php
            $userRole = (int)($_SESSION['user']['role_id'] ?? 0);
            $actingRoleId = 0;
            if ($userRole === 20) {
                $actingRoleId = 20;
            } elseif ($userRole === 22) {
                $actingRoleId = 22;
            } elseif (in_array($userRole, [1, 3])) {
                $actingRoleId = 3;
            }

            $canUserAct = false;
            $moStatus   = 'pending';
            $pmStatus   = 'pending';
            $mgmtStatus = 'pending';

            if (!empty($approvals)) {
                $appMap = [];
                foreach ($approvals as $a) {
                    $appMap[(int)$a['role_id']] = $a;
                }
                $moStatus   = $appMap[20]['status'] ?? 'pending';
                $pmStatus   = $appMap[22]['status'] ?? 'pending';
                $mgmtStatus = $appMap[3]['status']  ?? 'pending';

                if ($status === 'draft' && $actingRoleId > 0) {
                    if ($actingRoleId === 20) {
                        if ($moStatus === 'pending') {
                            $canUserAct = true;
                        }
                    } elseif ($actingRoleId === 22) {
                        if ($pmStatus === 'pending' && $moStatus !== 'pending') {
                            $canUserAct = true;
                        }
                    } elseif ($actingRoleId === 3) {
                        // Admin/Management can act at any time as long as Management is pending
                        if ($mgmtStatus === 'pending') {
                            $canUserAct = true;
                        }
                    }
                }
            }
            ?>
            <div class="card custom-card mb-4">
                <div class="card-header" style="background:#f8fafc;border-bottom:2px solid #e2e8f0">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-shield-check me-2 text-primary"></i>
                        Approval Workflow Progress
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex flex-column gap-3">
                        <?php
                        $roleLabels = [
                            20 => '1. Site Operator (MOperation)',
                            22 => '2. Project Manager (PM)',
                            3  => '3. Management (Director)',
                        ];
                        $badgeClasses = [
                            'pending'  => 'bg-warning-transparent text-warning',
                            'approved' => 'bg-success-transparent text-success',
                            'rejected' => 'bg-danger-transparent text-danger',
                        ];

                        foreach ([20, 22, 3] as $rId):
                            $app = $appMap[$rId] ?? ['status' => 'pending', 'remarks' => '', 'action_at' => null, 'user_name' => ''];
                            $appStatus = $app['status'];
                            $lbl = $roleLabels[$rId];
                            $badge = $badgeClasses[$appStatus] ?? 'bg-secondary-transparent';
                        ?>
                            <div class="p-3 rounded border" style="background: #fdfdfd;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-semibold text-muted" style="font-size: .88rem;"><?= $lbl ?></span>
                                    <span class="badge <?= $badge ?> text-uppercase fs-10 px-2"><?= $appStatus ?></span>
                                </div>
                                <?php if ($appStatus !== 'pending'): ?>
                                    <div class="text-muted small mb-1">
                                        <i class="bi bi-person me-1"></i>
                                        <strong><?= htmlspecialchars($app['user_name'] ?? 'User #' . $app['action_by']) ?></strong>
                                        on <?= date('d M Y h:i A', strtotime($app['action_at'])) ?>
                                    </div>
                                    <?php if (!empty($app['remarks'])): ?>
                                        <div class="mt-2 p-2 rounded text-dark-emphasis small" style="background:#fff5f5; border-left:3px solid #ef4444; font-size: .82rem;">
                                            <i class="bi bi-chat-right-text me-1"></i>
                                            <?= nl2br(htmlspecialchars($app['remarks'])) ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="text-muted small">
                                        <i class="bi bi-hourglass-split me-1 text-muted"></i>
                                        Awaiting review
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card custom-card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-lightning me-2 text-warning"></i>Actions</h6>
                </div>
                <div class="card-body d-grid gap-2">

                    <!-- Approve / Reject sequential actions -->
                    <?php if ($canUserAct): ?>
                        <form method="POST" action="<?= $base_url ?>/con-work-order-approve/<?= $wo['id'] ?>"
                               onsubmit="return confirm('Approve this Work Order?')">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-check-circle me-1"></i> Approve Work Order
                            </button>
                        </form>
                        <button class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="bi bi-x-circle me-1"></i> Reject
                        </button>
                    <?php endif; ?>

                    <!-- Mark Complete (approved/active → work_completed) -->
                    <?php if (in_array($status, ['approved','active']) && $canMarkComplete): ?>
                        <form method="POST" action="<?= $base_url ?>/con-work-order-complete/<?= $wo['id'] ?>"
                              onsubmit="return confirm('Mark this Work Order as Complete? QC review will be required.')">
                            <button type="submit" class="btn btn-warning w-100">
                                <i class="bi bi-check2-all me-1"></i> Mark Work Complete
                            </button>
                        </form>
                    <?php endif; ?>

                    <!-- QC Approve (work_completed → closed) -->
                    <?php if ($status === 'work_completed' && $canQcApprove): ?>
                        <form method="POST" action="<?= $base_url ?>/con-work-order-qc-approve/<?= $wo['id'] ?>"
                              onsubmit="return confirm('QC approve this work? This will CLOSE the Work Order and allow Contractor Bills.')">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-shield-check me-1"></i> QC Approve &amp; Close
                            </button>
                        </form>
                    <?php endif; ?>

                    <!-- Closed — Can now create bill -->
                    <?php if ($status === 'closed'): ?>
                        <a href="<?= $base_url ?>/con-contractor-bill-create" class="btn btn-outline-success w-100">
                            <i class="bi bi-receipt me-1"></i> Create Contractor Bill
                        </a>
                    <?php endif; ?>

                    <!-- Delete (draft only) -->
                    <?php if ($status === 'draft' && $canDelete): ?>
                        <form method="POST" action="<?= $base_url ?>/con-work-order-delete"
                              onsubmit="return confirm('Delete this Work Order permanently?')">
                            <input type="hidden" name="id" value="<?= $wo['id'] ?>">
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="bi bi-trash me-1"></i> Delete
                            </button>
                        </form>
                    <?php endif; ?>

                    <!-- Debug Panel -->
                    <?php if (isset($_GET['debug'])): ?>
                    <div class="alert alert-info small mt-3 p-2 font-monospace" style="font-size:.78rem; line-height:1.4">
                        <strong>Debug Info:</strong><br>
                        Logged In Role ID: <?= $userRole ?><br>
                        Acting Role: <?= $actingRoleId ?><br>
                        MOperation: <?= $moStatus ?><br>
                        PM Status: <?= $pmStatus ?><br>
                        Management: <?= $mgmtStatus ?><br>
                        Can Act: <?= $canUserAct ? 'Yes' : 'No' ?><br>
                        WO Status: <?= $status ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Meta Info -->
            <div class="card custom-card">
                <div class="card-body">
                    <div class="text-muted fs-12">Created By</div>
                    <div class="fw-semibold text-muted mb-2"><?= htmlspecialchars($wo['created_by_name'] ?? '-') ?></div>
                    <div class="text-muted fs-12">Created At</div>
                    <div class="mb-2 text-muted"><?= date('d M Y H:i', strtotime($wo['created_at'])) ?></div>
                    <?php if ($wo['completed_by_name']): ?>
                        <div class="text-muted fs-12">Completed By</div>
                        <div class="mb-2 text-muted"><?= htmlspecialchars($wo['completed_by_name']) ?> on <?= date('d M Y', strtotime($wo['completed_at'])) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= $base_url ?>/con-work-order-reject/<?= $wo['id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Work Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold text-muted">Rejection Reason</label>
                    <textarea name="rejection_reason" class="form-control" rows="3" required
                              placeholder="Explain why this Work Order is rejected..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Work Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

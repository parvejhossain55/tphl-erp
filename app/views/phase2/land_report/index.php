<?php
$roleId = (int)($_SESSION['user']['role_id'] ?? 0);

// Calculate summary stats from activities
$visibilityCount = 0;
$bayanaCount = 0;
$totalBayanaPaid = 0.0;
$totalRegCost = 0.0;

foreach ($activities as $act) {
    if ($act['event_type'] === 'visibility') {
        $visibilityCount++;
    } elseif ($act['event_type'] === 'bayana') {
        $bayanaCount++;
        $totalBayanaPaid += (float)($act['amount'] ?? 0);
    } elseif ($act['event_type'] === 'registration') {
        $totalRegCost += (float)($act['amount'] ?? 0);
    }
}
?>

<style>
@media print {
    /* Hide layout elements */
    .sidebar, 
    .app-sidebar,
    #sidebar,
    .app__header__area, 
    .pagetitle nav, 
    .pagetitle .bg-white,
    .no-print,
    .btn,
    .preloader,
    .footer-area,
    footer {
        display: none !important;
    }
    
    /* Remove padding and margin for layout wrappers */
    .page__full-wrapper, 
    .page__body-wrapper, 
    main, 
    .main-content, 
    body, 
    html {
        margin: 0 !important;
        padding: 0 !important;
        background: #fff !important;
    }
    
    /* Ensure content occupies full width */
    .page__body-wrapper {
        margin-left: 0 !important;
        width: 100% !important;
    }

    /* Print styling optimizations */
    .card {
        border: none !important;
        box-shadow: none !important;
        margin-bottom: 20px !important;
    }

    .table-responsive {
        overflow: visible !important;
        overflow-x: visible !important;
    }

    .table {
        width: 100% !important;
        border-collapse: collapse !important;
    }

    .table th, 
    .table td {
        border: 1px solid #dee2e6 !important;
        padding: 6px !important;
        font-size: 11px !important;
        color: #000 !important;
        word-break: break-word !important;
    }

    /* Ensure link colors or actions don't display href values next to them */
    a[href]:after {
        content: "" !important;
    }
}
</style>

<div class="mb-4 pagetitle d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <h4 class="mb-2">Land Progress Reports</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Land Progress Reports</li>
            </ol>
        </nav>
    </div>
    
    <div class="bg-white p-3 rounded shadow-sm border">
        <form action="<?= $base_url ?>/con-land-monthly-reports" method="GET" class="row  align-items-center m-0">
            <div class="col-auto">
                <label class="form-label mb-0 fw-semibold text-muted small"><i class="fa-light fa-calendar me-1"></i> Date Range:</label>
            </div>
            <div class="col-auto">
                <input type="date" name="from_date" class="form-control form-control-sm" value="<?= htmlspecialchars($from_date) ?>">
            </div>
            <div class="col-auto text-muted small">to</div>
            <div class="col-auto">
                <input type="date" name="to_date" class="form-control form-control-sm" value="<?= htmlspecialchars($to_date) ?>">
            </div>
            <div class="col-auto no-print">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="fa-light fa-filter me-1"></i> Filter
                </button>
                <a href="<?= $base_url ?>/con-land-monthly-reports" class="btn btn-sm btn-outline-secondary">
                    Reset
                </a>
                <button type="button" onclick="window.print()" class="btn btn-sm btn-info text-white ms-1">
                    <i class="fa-light fa-print me-1"></i> Print
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4 no-print">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 card-stat" style="border-left: 4px solid #4e73df !important;">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase mb-1">Total Projects</div>
                        <h3 class="mb-0 fw-bold text-dark"><?= $total_projects ?></h3>
                    </div>
                    <div class="bg-primary-subtle text-primary p-3 rounded-circle">
                        <i class="fa-light fa-diagram-project fa-xl"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 card-stat" style="border-left: 4px solid #36b9cc !important;">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase mb-1">Visibility Submitted</div>
                        <h3 class="mb-0 fw-bold text-dark"><?= $visibilityCount ?></h3>
                        <div class="text-muted small mt-1" style="font-size: 11px;">Within range</div>
                    </div>
                    <div class="bg-info-subtle text-info p-3 rounded-circle">
                        <i class="fa-light fa-eye fa-xl"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 card-stat" style="border-left: 4px solid #f6c23e !important;">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase mb-1">Bayana Paid (In Range)</div>
                        <h3 class="mb-0 fw-bold text-dark">BDT <?= number_format($totalBayanaPaid, 2) ?></h3>
                        <div class="text-muted small mt-1" style="font-size: 11px;"><?= $bayanaCount ?> transactions</div>
                    </div>
                    <div class="bg-warning-subtle text-warning p-3 rounded-circle">
                        <i class="fa-light fa-handshake fa-xl"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 card-stat" style="border-left: 4px solid #1cc88a !important;">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase mb-1">Reg Cost (In Range)</div>
                        <h3 class="mb-0 fw-bold text-dark">BDT <?= number_format($totalRegCost, 2) ?></h3>
                        <div class="text-muted small mt-1" style="font-size: 11px;">Within range</div>
                    </div>
                    <div class="bg-success-subtle text-success p-3 rounded-circle">
                        <i class="fa-light fa-file-signature fa-xl"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Consolidated Activity Table -->
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 py-3">
                <h5 class="card-title mb-0" style="font-size: 16px;">
                    Date-wise Consolidated Activity Report &mdash; <strong><?= date('d M Y', strtotime($from_date)) ?> to <?= date('d M Y', strtotime($to_date)) ?></strong>
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Date & Time</th>
                                <th>Project Name</th>
                                <th>Activity Type</th>
                                <th>Landowner Details</th>
                                <th>Area</th>
                                <th>Amount / Cost</th>
                                <th>Details / Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($activities)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="fa-light fa-calendar-xmark fa-2x mb-2 text-secondary d-block"></i>
                                        No activities or submissions found in the selected date range.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($activities as $index => $row): ?>
                                    <?php
                                    $badgeColor = match ($row['event_type']) {
                                        'visibility' => 'info',
                                        'bayana' => 'warning',
                                        'registration' => 'success',
                                        default => 'secondary'
                                    };
                                    $badgeText = match ($row['event_type']) {
                                        'visibility' => 'Visibility Study',
                                        'bayana' => 'Bayana Payment',
                                        'registration' => 'Land Registration',
                                        default => 'Other Event'
                                    };
                                    ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td class="fw-semibold text-dark">
                                            <?= date('d M Y h:i A', strtotime($row['event_date'])) ?>
                                        </td>
                                        <td class="fw-bold text-primary">
                                            <?= htmlspecialchars($row['project_name']) ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $badgeColor ?>-subtle text-<?= $badgeColor ?> border border-<?= $badgeColor ?>-subtle text-uppercase fw-semibold" style="font-size: 10px;">
                                                <?= $badgeText ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($row['landowner_name']): ?>
                                                <div class="fw-semibold text-dark"><?= htmlspecialchars($row['landowner_name']) ?></div>
                                                <?php if ($row['landowner_phone']): ?>
                                                    <div class="text-muted small" style="font-size: 11px;"><i class="fa-light fa-phone me-1"></i><?= htmlspecialchars($row['landowner_phone']) ?></div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted italic">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($row['area'] !== null): ?>
                                                <span class="fw-semibold text-dark"><?= number_format((float)$row['area'], 2) ?></span>
                                                <span class="text-muted small"><?= htmlspecialchars($row['area_unit'] ?? 'Dec/Katha') ?></span>
                                            <?php else: ?>
                                                <span class="text-muted italic">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-bold">
                                            <?php if ($row['amount'] !== null): ?>
                                                <?php
                                                $amtColor = match ($row['event_type']) {
                                                    'bayana' => 'text-warning',
                                                    'registration' => 'text-success',
                                                    default => 'text-dark'
                                                };
                                                ?>
                                                <span class="<?= $amtColor ?>">BDT <?= number_format((float)$row['amount'], 2) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted italic">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($row['event_type'] === 'visibility'): ?>
                                                <div class="d-flex align-items-center gap-2">
                                                    <?php
                                                    $visBadge = match ($row['land_legal_doc_status'] ?? '') {
                                                        'Verified' => 'success',
                                                        'Pending' => 'danger',
                                                        'Rejected' => 'warning',
                                                        default => 'secondary'
                                                    };
                                                    ?>
                                                    <span class="badge bg-<?= $visBadge ?>-subtle text-<?= $visBadge ?> border border-<?= $visBadge ?>-subtle py-1" style="font-size: 10px;">
                                                        Legal status: <?= htmlspecialchars($row['land_legal_doc_status'] ?? 'N/A') ?>
                                                    </span>
                                                    <?php if ($row['visibility_survey_link']): ?>
                                                        <a href="<?= htmlspecialchars($row['visibility_survey_link']) ?>" target="_blank" class="btn btn-xs btn-outline-info py-0 px-2 small no-print" style="font-size: 11px;">
                                                            <i class="fa-light fa-eye me-1"></i>View Study
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <div>
                                                    <span class="text-muted small d-block mb-1" style="font-size: 12px;"><?= htmlspecialchars($row['details']) ?></span>
                                                    <?php if ($row['status']): ?>
                                                        <?php
                                                        $statusBadge = match ($row['status']) {
                                                            'complete' => 'success',
                                                            'cancel' => 'danger',
                                                            default => 'warning'
                                                        };
                                                        $statusLabel = match ($row['status']) {
                                                            'complete' => 'Completed',
                                                            'cancel' => 'Cancelled',
                                                            default => 'Pending'
                                                        };
                                                        ?>
                                                        <span class="badge bg-<?= $statusBadge ?>-subtle text-<?= $statusBadge ?> border border-<?= $statusBadge ?>-subtle" style="font-size: 10px;">
                                                            Process: <?= $statusLabel ?>
                                                        </span>
                                                        <?php if ($row['status'] === 'complete' && $row['status_date']): ?>
                                                            <span class="text-muted small ms-1" style="font-size: 10px;">on <?= date('d M Y', strtotime($row['status_date'])) ?></span>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

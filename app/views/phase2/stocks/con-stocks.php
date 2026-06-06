
<?php
// Helper: format taka in Lakh notation
function fmtLakh($val) {
    if ($val >= 100000) return '&#2547;' . number_format($val / 100000, 1) . 'L';
    if ($val >= 1000)   return '&#2547;' . number_format($val / 1000, 1) . 'K';
    return '&#2547;' . number_format($val, 0);
}
$stats     = $stats     ?? ['total_value' => 0, 'total_materials' => 0, 'low_stock_count' => 0];
$byProject = $byProject ?? ['projects' => [], 'grand_total' => 0];
$projList  = $byProject['projects'] ?? [];
$grandTotal = $byProject['grand_total'] ?? 0;
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1><?= htmlspecialchars($page_title) ?></h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item">Inventory</li>
                <li class="breadcrumb-item active">Stock Levels</li>
            </ol>
        </nav>
    </div>

    <section class="section">

        <!-- ══════════════════════════════════════
             KPI CARDS &mdash; 3 cards on top
        ════════════════════════════════════════ -->
        <div class="row mb-4">

            <!-- Total Stock Value -->
            <div class="col-xl-4 col-md-4 col-sm-12">
                <div class="stock-kpi-card stock-kpi-card--blue">
                    <div class="stock-kpi-icon">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="stock-kpi-body">
                        <div class="stock-kpi-value"><?= fmtLakh($stats['total_value']) ?></div>
                        <div class="stock-kpi-label">Total Stock Value</div>
                        <div class="stock-kpi-sub">Current inventory valuation</div>
                    </div>
                </div>
            </div>

            <!-- Total Materials -->
            <div class="col-xl-4 col-md-4 col-sm-12">
                <div class="stock-kpi-card stock-kpi-card--green">
                    <div class="stock-kpi-icon">
                        <i class="fas fa-boxes-stacked"></i>
                    </div>
                    <div class="stock-kpi-body">
                        <div class="stock-kpi-value"><?= number_format($stats['total_materials']) ?></div>
                        <div class="stock-kpi-label">Total Materials</div>
                        <div class="stock-kpi-sub">Distinct materials in stock</div>
                    </div>
                </div>
            </div>

            <!-- Low Stock Items -->
            <div class="col-xl-4 col-md-4 col-sm-12">
                <div class="stock-kpi-card stock-kpi-card--<?= $stats['low_stock_count'] > 0 ? 'red' : 'teal' ?>">
                    <div class="stock-kpi-icon">
                        <i class="fas fa-triangle-exclamation"></i>
                    </div>
                    <div class="stock-kpi-body">
                        <div class="stock-kpi-value"><?= $stats['low_stock_count'] ?></div>
                        <div class="stock-kpi-label">Low Stock Items</div>
                        <div class="stock-kpi-sub">Below minimum stock level</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════
             TAB SYSTEM
        ════════════════════════════════════════ -->
        <div class="card shadow-sm border-0">
            <div class="card-header  pb-0">
                <ul class="nav nav-tabs card-header-tabs" id="stockTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active fw-semibold" id="tab-current" data-bs-toggle="tab"
                                data-bs-target="#pane-current" type="button" role="tab">
                            <i class="fas fa-layer-group me-1 text-primary"></i> Current Stock
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-semibold" id="tab-available" data-bs-toggle="tab"
                                data-bs-target="#pane-available" type="button" role="tab">
                            <i class="fas fa-check-circle me-1 text-success"></i> Available
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-semibold" id="tab-project" data-bs-toggle="tab"
                                data-bs-target="#pane-project" type="button" role="tab">
                            <i class="fas fa-building me-1 text-info"></i> By Project
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-semibold" id="tab-low" data-bs-toggle="tab"
                                data-bs-target="#pane-low" type="button" role="tab">
                            <i class="fas fa-triangle-exclamation me-1 text-danger"></i> Low Stock
                            <?php if ($stats['low_stock_count'] > 0): ?>
                                <span class="badge bg-danger rounded-pill ms-1"><?= $stats['low_stock_count'] ?></span>
                            <?php endif; ?>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-semibold" id="tab-budget" data-bs-toggle="tab"
                                data-bs-target="#pane-budget" type="button" role="tab">
                            <i class="fas fa-chart-pie me-1" style="color:#d97706"></i> Budget Tracking
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-0">
                <div class="tab-content" id="stockTabsContent">

                    <!-- ─────────────────────────
                         Tab 1: Current Stock
                    ───────────────────────── -->
                    <div class="tab-pane fade show active" id="pane-current" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped custom-table mb-0 datatable-stock">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Material</th>
                                        <th>Warehouse</th>
                                        <th>Current Qty</th>
                                        <th>Unit</th>
                                        <th>Unit Price</th>
                                        <th>Stock Value</th>
                                        <th>Min Level</th>
                                        <th>Status</th>
                                        <th>Last Issued</th>
                                        <th>Last Updated</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($stocks)): ?>
                                        <?php foreach ($stocks as $i => $row):
                                            $isLow = $row['current_quantity'] < $row['min_stock_level'];
                                        ?>
                                        <tr>
                                            <td><?= $i + 1 ?></td>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($row['material_name']) ?></div>
                                                <small class="text-muted">[Category: <?= htmlspecialchars($row['category_name'] ?? 'Uncategorized') ?>]</small>
                                            </td>
                                            <td><?= htmlspecialchars($row['warehouse_name']) ?></td>
                                            <td class="fw-bold <?= $isLow ? 'text-danger' : 'text-primary' ?>">
                                                <?= number_format($row['current_quantity'], 3) ?>
                                            </td>
                                            <td><?= htmlspecialchars($row['unit']) ?></td>
                                            <td>&#2547;<?= number_format($row['unit_price'], 2) ?></td>
                                            <td class="fw-semibold text-success"><?= fmtLakh($row['current_quantity'] * $row['unit_price']) ?></td>
                                            <td><?= number_format($row['min_stock_level'], 2) ?></td>
                                            <td>
                                                <?php if ($isLow): ?>
                                                    <span class="badge bg-danger"><i class="fas fa-exclamation-triangle me-1"></i>Low Stock</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success"><i class="fas fa-check me-1"></i>OK</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($row['last_issued_qty'])): ?>
                                                    <div class="fw-bold text-danger">
                                                        <?= number_format($row['last_issued_qty'], 3) ?>
                                                        <span class="text-muted small"><?= htmlspecialchars($row['unit']) ?></span>
                                                    </div>
                                                    <?php if (!empty($row['last_issued_by_name'])): ?>
                                                        <?php if (($row['last_issued_ref_type'] ?? '') === 'wastage'): ?>
                                                            <div class="text-danger fw-bold mt-1" style="font-size:11px;">
                                                                <i class="fas fa-trash-alt me-1"></i> Wastage Collected
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="text-muted" style="font-size:11px;">
                                                                By: <?= htmlspecialchars($row['last_issued_by_name']) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                    <?php if (!empty($row['last_issued_at'])): ?>
                                                        <div class="text-muted" style="font-size:11px;">
                                                            <?= date('d M Y', strtotime($row['last_issued_at'])) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">&mdash;</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $row['updated_at'] ? date('d M Y h:i A', strtotime($row['updated_at'])) : '&mdash;' ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="11" class="text-center py-5 text-muted">
                                            <i class="fas fa-inbox fa-2x mb-2 d-block opacity-50"></i>
                                            No stock data. Stock is added when materials are received and QC-approved.
                                        </td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ─────────────────────────
                         Tab 2: Available
                    ───────────────────────── -->
                    <div class="tab-pane fade" id="pane-available" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped custom-table mb-0 datatable-available">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Material</th>
                                        <th>Warehouse</th>
                                        <th>Available Qty</th>
                                        <th>Unit</th>
                                        <th>Unit Price</th>
                                        <th>Stock Value</th>
                                        <th>Stock Level</th>
                                        <th>Last Updated</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($availableStock)): ?>
                                        <?php foreach ($availableStock as $i => $row):
                                            $pct = $row['min_stock_level'] > 0
                                                ? min(100, round(($row['current_quantity'] / $row['min_stock_level']) * 100))
                                                : 100;
                                            $barColor = $pct >= 100 ? 'bg-success' : ($pct >= 50 ? 'bg-warning' : 'bg-danger');
                                        ?>
                                        <tr>
                                            <td><?= $i + 1 ?></td>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($row['material_name']) ?></div>
                                                <small class="text-muted">[Category: <?= htmlspecialchars($row['category_name'] ?? 'Uncategorized') ?>]</small>
                                            </td>
                                            <td><?= htmlspecialchars($row['warehouse_name']) ?></td>
                                            <td class="fw-bold text-primary"><?= number_format($row['current_quantity'], 3) ?></td>
                                            <td><?= htmlspecialchars($row['unit']) ?></td>
                                            <td>&#2547;<?= number_format($row['unit_price'], 2) ?></td>
                                            <td class="fw-semibold text-success"><?= fmtLakh($row['current_quantity'] * $row['unit_price']) ?></td>
                                            <td style="min-width:140px;">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="progress flex-grow-1" style="height:8px;">
                                                        <div class="progress-bar <?= $barColor ?>" style="width: <?= $pct ?>%;"></div>
                                                    </div>
                                                    <small class="text-muted"><?= $pct ?>%</small>
                                                </div>
                                                <small class="text-muted">Min: <?= number_format($row['min_stock_level'], 2) ?></small>
                                            </td>
                                            <td><?= $row['updated_at'] ? date('d M Y h:i A', strtotime($row['updated_at'])) : '&mdash;' ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="9" class="text-center py-5 text-muted">
                                            <i class="fas fa-box-open fa-2x mb-2 d-block opacity-50"></i>
                                            No available stock found.
                                        </td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ─────────────────────────
                         Tab 3: By Project
                    ───────────────────────── -->
                    <div class="tab-pane fade" id="pane-project" role="tabpanel">
                        <div class="p-3">

                            <?php if (!empty($projList)): ?>

                                <h6 class="fw-bold text-muted mb-3">
                                    <i class="fas fa-building me-1"></i> Project-wise Inventory Summary
                                </h6>

                                <!-- Project Accordion Cards -->
                                <div class="accordion mb-4" id="projectAccordion">
                                    <?php foreach ($projList as $pidx => $proj): ?>
                                    <div class="accordion-item border-0 mb-2 shadow-sm rounded overflow-hidden">
                                        <h2 class="accordion-header" id="ph-<?= $pidx ?>">
                                            <button class="accordion-button <?= $pidx > 0 ? 'collapsed' : '' ?> fw-semibold"
                                                    type="button"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#pc-<?= $pidx ?>"
                                                    aria-expanded="<?= $pidx === 0 ? 'true' : 'false' ?>"
                                                    aria-controls="pc-<?= $pidx ?>">
                                                <div class="d-flex align-items-center justify-content-between w-100 pe-3">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <div class="project-dot" style="background: <?= ['var(--clr-theme-primary)','#059669','#d97706','#dc2626','var(--clr-theme-secondary)'][$pidx % 5] ?>;"></div>
                                                        <div>
                                                            <div class="fw-bold"><?= htmlspecialchars($proj['project_name']) ?></div>
                                                            <small class="text-muted"><?= $proj['po_count'] ?> POs &bull; <?= $proj['material_count'] ?> material types</small>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex gap-3 align-items-center">
                                                        <div class="text-center">
                                                            <div class="fw-bold text-success"><?= fmtLakh($proj['total_value']) ?></div>
                                                            <small class="text-muted">Total Value</small>
                                                        </div>
                                                        <div class="text-center">
                                                            <div class="fw-bold text-primary"><?= $proj['pct'] ?>%</div>
                                                            <small class="text-muted">of Total</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </button>
                                        </h2>
                                        <div id="pc-<?= $pidx ?>" class="accordion-collapse collapse <?= $pidx === 0 ? 'show' : '' ?>"
                                             aria-labelledby="ph-<?= $pidx ?>" data-bs-parent="#projectAccordion">
                                            <div class="accordion-body p-0">
                                                <div class="px-3 py-2 border-bottom">
                                                    <small class="fw-semibold text-primary">
                                                        <i class="fas fa-table me-1"></i>
                                                        Material Breakdown: <?= htmlspecialchars($proj['project_name']) ?>
                                                    </small>
                                                </div>
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-hover mb-0">
                                                        <thead class="table-light">
                                                            <tr>

                                                                <th>Material</th>
                                                                <th class="text-end">Received</th>
                                                                <th class="text-end">Issued</th>
                                                                <th class="text-end">Remaining</th>
                                                                <th>Unit</th>
                                                                <th class="text-end">Avg Price</th>
                                                                <th class="text-end">Total Value</th>
                                                                <th>Issue Details</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($proj['materials'] as $mat): ?>
                                                            <tr>
                                                                 <td>
                                                                     <span class="fw-semibold"><?= htmlspecialchars($mat['material_name']) ?></span>
                                                                     <br><small class="text-muted">[Category: <?= htmlspecialchars($mat['category_name'] ?? 'Uncategorized') ?>]</small>
                                                                 </td>
                                                                <td class="text-end text-primary fw-bold"><?= number_format($mat['total_received'] ?? 0, 3) ?></td>
                                                                <td class="text-end fw-bold <?= ($mat['total_issued'] ?? 0) > 0 ? 'text-danger' : 'text-muted' ?>">
                                                                    <?= ($mat['total_issued'] ?? 0) > 0 ? number_format($mat['total_issued'], 3) : '-' ?>
                                                                </td>
                                                                <td class="text-end fw-bold <?= ($mat['remaining_qty'] ?? 0) <= 0 ? 'text-danger' : 'text-success' ?>">
                                                                    <?= number_format($mat['remaining_qty'] ?? 0, 3) ?>
                                                                </td>
                                                                <td><?= htmlspecialchars($mat['unit']) ?></td>
                                                                <td class="text-end">&#2547;<?= number_format($mat['avg_price'] ?? 0, 2) ?></td>
                                                                <td class="text-end text-success fw-semibold"><?= fmtLakh($mat['total_value'] ?? 0) ?></td>
                                                                <td>
                                                                    <?php if (!empty($mat['issue_details'])): ?>
                                                                        <button class="btn btn-sm btn-outline-secondary py-0 px-1" type="button"
                                                                                data-bs-toggle="collapse"
                                                                                data-bs-target="#iss-<?= $pidx ?>-<?= $mat['material_id'] ?>">
                                                                            <i class="fas fa-list-ul me-1"></i><?= count($mat['issue_details']) ?> issue(s)
                                                                        </button>
                                                                        <div class="collapse mt-1" id="iss-<?= $pidx ?>-<?= $mat['material_id'] ?>">
                                                                            <table class="table table-bordered mb-0 small">
                                                                                <thead class="table-secondary">
                                                                                    <tr><th>Date</th><th>Qty</th><th>Target</th><th>Work Plan</th><th>Issued By</th></tr>
                                                                                </thead>
                                                                                <tbody>
                                                                                    <?php foreach ($mat['issue_details'] as $iss): ?>
                                                                                    <tr>
                                                                                        <td><?= date('d M Y', strtotime($iss['issue_date'])) ?></td>
                                                                                        <td class="text-danger fw-bold"><?= number_format($iss['quantity'], 3) ?></td>
                                                                                        <td><?= htmlspecialchars($iss['target_title'] ?? '-') ?></td>
                                                                                        <td><?= htmlspecialchars($iss['work_plan_title'] ?? '-') ?></td>
                                                                                        <td><?= htmlspecialchars($iss['issued_by_name'] ?? '-') ?></td>
                                                                                    </tr>
                                                                                    <?php endforeach; ?>
                                                                                </tbody>
                                                                            </table>
                                                                        </div>
                                                                    <?php else: ?>
                                                                        <span class="text-muted small">No issues</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                        <tfoot class="table-light fw-bold">
                                                            <tr>
                                                                <td>Total</td>
                                                                <td class="text-end text-primary"><?= number_format(array_sum(array_column($proj['materials'], 'total_received')), 3) ?></td>
                                                                <td class="text-end text-danger"><?= number_format(array_sum(array_column($proj['materials'], 'total_issued')), 3) ?></td>
                                                                <td class="text-end text-success"><?= number_format(array_sum(array_column($proj['materials'], 'remaining_qty')), 3) ?></td>
                                                                <td colspan="4"></td>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                </div><!-- /table-responsive -->
                                            </div><!-- /accordion-body -->
                                        </div><!-- /accordion-collapse -->
                                    </div><!-- /accordion-item -->

                                    <?php endforeach; ?>
                                </div>

                                <!-- Project Comparison Table -->
                                <div class="card border-0 shadow-sm mt-3">
                                    <div class="card-header ">
                                        <h6 class="mb-0 fw-bold"><i class="fas fa-chart-bar me-2"></i>Project Comparison</h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Project</th>
                                                        <th>Purchase Orders</th>
                                                        <th>Materials</th>
                                                        <th>Total Value</th>
                                                        <th>% of Total</th>
                                                        <th>Share</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($projList as $pidx => $proj): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center gap-2">
                                                                <div class="project-dot" style="background: <?= ['var(--clr-theme-primary)','#059669','#d97706','#dc2626','var(--clr-theme-secondary)'][$pidx % 5] ?>;"></div>
                                                                <span class="fw-semibold"><?= htmlspecialchars($proj['project_name']) ?></span>
                                                            </div>
                                                        </td>
                                                        <td><?= $proj['po_count'] ?></td>
                                                        <td><?= $proj['material_count'] ?></td>
                                                        <td class="fw-bold text-success"><?= fmtLakh($proj['total_value']) ?></td>
                                                        <td><?= $proj['pct'] ?>%</td>
                                                        <td style="min-width:120px;">
                                                            <div class="progress" style="height:8px;">
                                                                <div class="progress-bar" style="width:<?= $proj['pct'] ?>%; background: <?= ['var(--clr-theme-primary)','#059669','#d97706','#dc2626','var(--clr-theme-secondary)'][$pidx % 5] ?>;"></div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                                <tfoot class="table-dark">
                                                    <tr>
                                                        <td class="fw-bold">Total</td>
                                                        <td><?= array_sum(array_column($projList, 'po_count')) ?></td>
                                                        <td>–</td>
                                                        <td class="fw-bold"><?= fmtLakh($grandTotal) ?></td>
                                                        <td class="fw-bold">100%</td>
                                                        <td></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                            <?php else: ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="fas fa-building fa-2x mb-2 d-block opacity-40"></i>
                                    No project-wise stock data available yet.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- ─────────────────────────
                         Tab 4: Low Stock
                    ───────────────────────── -->
                    <div class="tab-pane fade" id="pane-low" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped custom-table mb-0 datatable-low">
                                <thead class="table-danger">
                                    <tr>
                                        <th>#</th>
                                        <th>Material</th>
                                        <th>Warehouse</th>
                                        <th>Current Qty</th>
                                        <th>Min Level</th>
                                        <th>Deficit</th>
                                        <th>Unit</th>
                                        <th>Stock Level</th>
                                        <th>Unit Price</th>
                                        <th>Last Updated</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($lowStockItems)): ?>
                                        <?php foreach ($lowStockItems as $i => $row):
                                            $pct = (float)$row['stock_pct'];
                                            $barColor = $pct < 25 ? 'bg-danger' : ($pct < 60 ? 'bg-warning' : 'bg-info');
                                        ?>
                                        <tr>
                                            <td><?= $i + 1 ?></td>
                                            <td>
                                                <div class="fw-bold text-danger"><?= htmlspecialchars($row['material_name']) ?></div>
                                                <small class="text-muted">[Category: <?= htmlspecialchars($row['category_name'] ?? 'Uncategorized') ?>]</small>
                                            </td>
                                            <td><?= htmlspecialchars($row['warehouse_name']) ?></td>
                                            <td class="fw-bold text-danger"><?= number_format($row['current_quantity'], 3) ?></td>
                                            <td><?= number_format($row['min_stock_level'], 2) ?></td>
                                            <td class="fw-semibold text-warning">
                                                <i class="fas fa-arrow-down me-1"></i><?= number_format($row['deficit'], 2) ?>
                                            </td>
                                            <td><?= htmlspecialchars($row['unit']) ?></td>
                                            <td style="min-width:140px;">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="progress flex-grow-1" style="height:8px;">
                                                        <div class="progress-bar <?= $barColor ?>" style="width: <?= $pct ?>%;"></div>
                                                    </div>
                                                    <small><?= $pct ?>%</small>
                                                </div>
                                            </td>
                                            <td>&#2547;<?= number_format($row['unit_price'], 2) ?></td>
                                            <td><?= $row['updated_at'] ? date('d M Y', strtotime($row['updated_at'])) : '&mdash;' ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="10" class="text-center py-5 text-success">
                                            <i class="fas fa-check-circle fa-2x mb-2 d-block"></i>
                                            <strong>All good!</strong> No items are below minimum stock level.
                                        </td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ─────────────────────────
                         Tab 5: Budget Tracking
                    ───────────────────────── -->
                    <div class="tab-pane fade" id="pane-budget" role="tabpanel">
                        <div class="p-3">
                            <?php if (!empty($budgetVsReceived)): ?>
                                <h6 class="fw-bold text-muted mb-3">
                                    <i class="fas fa-chart-pie me-1"></i> Budget vs Received Tracking
                                </h6>
                                <div class="accordion mb-4" id="budgetAccordion">
                                    <?php $bidx = 0; foreach ($budgetVsReceived as $pid => $bProj): ?>
                                    <div class="accordion-item border-0 mb-3 shadow-sm rounded overflow-hidden">
                                        <h2 class="accordion-header" id="bh-<?= $bidx ?>">
                                            <button class="accordion-button <?= $bidx > 0 ? 'collapsed' : '' ?> fw-bold"
                                                    type="button"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#bc-<?= $bidx ?>"
                                                    aria-expanded="<?= $bidx === 0 ? 'true' : 'false' ?>">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="project-dot" style="background: <?= ['var(--clr-theme-primary)','#059669','#d97706','#dc2626','var(--clr-theme-secondary)'][$bidx % 5] ?>;"></div>
                                                    Project: <?= htmlspecialchars($bProj['project_name']) ?>
                                                </div>
                                            </button>
                                        </h2>
                                        <div id="bc-<?= $bidx ?>" class="accordion-collapse collapse <?= $bidx === 0 ? 'show' : '' ?>"
                                             data-bs-parent="#budgetAccordion">
                                            <div class="accordion-body p-0">
                                                <?php foreach ($bProj['work_types'] as $wname => $materials): ?>
                                                    <div class="bg-light px-3 py-2 border-bottom fw-bold" style="color:var(--cd-blue)">
                                                        <i class="fas fa-layer-group me-1"></i> <?= htmlspecialchars($wname) ?>
                                                    </div>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-hover mb-0">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th style="min-width:150px">Material</th>
                                                                    <th class="text-end" style="min-width:85px">Budget Qty</th>
                                                                    <th class="text-end text-primary" style="min-width:85px">Received</th>
                                                                    <th class="text-end text-danger" style="min-width:85px">Issued Qty</th>
                                                                    <th class="text-end text-danger" style="min-width:110px">Issued Value</th>
                                                                    <th class="text-end text-success" style="min-width:85px">Remaining</th>
                                                                    <th style="min-width:190px">Budget Usage</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($materials as $mat):
                                                                    $budgetQty   = (float)$mat['initial_budget_qty'];
                                                                    $receivedQty = (float)$mat['total_received'];
                                                                    $issuedQty   = (float)($mat['issued_qty'] ?? 0);
                                                                    $issuedVal   = (float)($mat['issued_value'] ?? 0);
                                                                    $budgetLeft  = (float)($mat['budget_remaining'] ?? max(0, $budgetQty - $issuedQty));
                                                                    $issuedPct    = $budgetQty > 0 ? min(100, round(($issuedQty / $budgetQty) * 1000) / 10) : 0;
                                                                    $receivedPct  = $budgetQty > 0 ? min(100, round(($receivedQty / $budgetQty) * 1000) / 10) : 0;
                                                                    $issuedPctFmt = number_format($issuedPct, $issuedPct < 1 ? 2 : 1);
                                                                    $receivedPctFmt = number_format($receivedPct, $receivedPct < 1 ? 2 : 1);
                                                                    $barIssuedW   = max(0.5, $issuedPct);   // min bar width so it's visible
                                                                    $barRecvW     = max(0.5, $receivedPct);
                                                                    $barIssued    = $issuedPct >= 100 ? 'bg-danger' : ($issuedPct >= 80 ? 'bg-warning' : 'bg-primary');
                                                                ?>
                                                                <tr>
                                                                    <td>
                                                                        <span class="fw-semibold"><?= htmlspecialchars($mat['material_name']) ?></span>
                                                                        <small class="text-muted">(<?= $mat['unit'] ?>)</small>
                                                                        <br><small class="text-muted">[Category: <?= htmlspecialchars($mat['category_name'] ?? 'Uncategorized') ?>]</small>
                                                                    </td>
                                                                    <td class="text-end fw-bold"><?= number_format($budgetQty, 3) ?></td>
                                                                    <td class="text-end text-primary fw-bold"><?= number_format($receivedQty, 3) ?></td>
                                                                    <td class="text-end fw-bold <?= $issuedQty > 0 ? 'text-danger' : 'text-muted' ?>">
                                                                        <?= $issuedQty > 0 ? number_format($issuedQty, 3) : '-' ?>
                                                                    </td>
                                                                    <td class="text-end fw-bold <?= $issuedVal > 0 ? 'text-danger' : 'text-muted' ?>">
                                                                        <?= $issuedVal > 0 ? '&#2547;' . number_format($issuedVal, 0) : '-' ?>
                                                                    </td>
                                                                    <td class="text-end fw-bold <?= $budgetLeft <= 0 ? 'text-danger' : 'text-success' ?>">
                                                                        <?= number_format($budgetLeft, 3) ?>
                                                                    </td>
                                                                    <td>
                                                                        <div class="d-flex align-items-center gap-1 mb-1">
                                                                            <small class="text-muted" style="min-width:52px">Rcvd:</small>
                                                                            <div class="progress flex-grow-1" style="height:6px;">
                                                                                <div class="progress-bar bg-primary" style="width:<?= $barRecvW ?>%"></div>
                                                                            </div>
                                                                            <small class="text-primary fw-semibold" style="min-width:40px;text-align:right"><?= $receivedPctFmt ?>%</small>
                                                                        </div>
                                                                        <div class="d-flex align-items-center gap-1">
                                                                            <small class="text-muted" style="min-width:52px">Issued:</small>
                                                                            <div class="progress flex-grow-1" style="height:6px;">
                                                                                <div class="progress-bar <?= $barIssued ?>" style="width:<?= $barIssuedW ?>%"></div>
                                                                            </div>
                                                                            <small class="fw-semibold <?= $issuedPct >= 80 ? 'text-danger' : 'text-muted' ?>" style="min-width:40px;text-align:right"><?= $issuedPctFmt ?>%</small>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                            <tfoot class="table-light fw-bold small">
                                                                <tr>
                                                                    <td>Total</td>
                                                                    <td class="text-end"><?= number_format(array_sum(array_column($materials, 'initial_budget_qty')), 3) ?></td>
                                                                    <td class="text-end text-primary"><?= number_format(array_sum(array_column($materials, 'total_received')), 3) ?></td>
                                                                    <td class="text-end text-danger"><?= number_format(array_sum(array_column($materials, 'issued_qty')), 3) ?></td>
                                                                    <td class="text-end text-danger">&#2547;<?= number_format(array_sum(array_column($materials, 'issued_value')), 0) ?></td>
                                                                    <td class="text-end text-success"><?= number_format(array_sum(array_column($materials, 'budget_remaining')), 3) ?></td>
                                                                    <td></td>
                                                                </tr>
                                                            </tfoot>
                                                        </table>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php $bidx++; endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="fas fa-chart-pie fa-2x mb-2 d-block opacity-40"></i>
                                    No budget tracking data available yet.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div><!-- /tab-content -->
            </div><!-- /card-body -->
        </div><!-- /card -->

    </section>
</main>

<!-- ══════════════════════════════════════
     STYLES
════════════════════════════════════════ -->
<style>
/* KPI Cards */
.stock-kpi-card {
    display: flex;
    align-items: center;
    gap: 18px;
    padding: 22px 24px;
    border-radius: 14px;
    box-shadow: 0 4px 20px rgba(0,0,0,.08);
    transition: transform .2s, box-shadow .2s;
    color: #fff;
}
.stock-kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 28px rgba(0,0,0,.14);
}
.stock-kpi-card--blue  { background: linear-gradient(135deg, var(--clr-theme-primary), #818cf8); }
.stock-kpi-card--green { background: linear-gradient(135deg, #059669, #34d399); }
.stock-kpi-card--red   { background: linear-gradient(135deg, #dc2626, #f87171); }
.stock-kpi-card--teal  { background: linear-gradient(135deg, #0891b2, #38bdf8); }

.stock-kpi-icon {
    width: 58px; height: 58px;
    border-radius: 50%;
    /* background: rgba(255,255,255,.22); */
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}
.stock-kpi-value { font-size: 1.75rem; font-weight: 800; line-height: 1; }
.stock-kpi-label { font-size: .85rem; font-weight: 600; margin-top: 4px; opacity: .92; }
.stock-kpi-sub   { font-size: .75rem; opacity: .75; margin-top: 2px; }

/* Accordion project dot */
.project-dot {
    width: 12px; height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
}

/* Tab active style */
.nav-tabs .nav-link.active {
    border-bottom: 3px solid var(--clr-theme-primary);
    color: var(--clr-theme-primary) !important;
    font-weight: 700;
}
.nav-tabs .nav-link:not(.active) { color: #6b7280; }

/* Accordion button */
.accordion-button:not(.collapsed) {
 /*   background: #f5f3ff;*/
    color: #1e1b4b;
    box-shadow: none;
}
.accordion-button { font-size: .9rem; }
</style>

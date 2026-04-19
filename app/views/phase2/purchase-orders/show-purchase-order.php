<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-purchase-orders">Purchase Orders</a></li>
                <li class="breadcrumb-item active">PO-<?= $po['id'] ?></li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer me-1"></i> Print
        </button>
        <a href="<?= $base_url ?>/con-purchase-orders" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<section class="section">
    <div class="row justify-content-center">
        <div class="col-lg-11">

            <!-- PO Header -->
            <div class="card__wrapper shadow-sm mb-4">
                <div class="card__body p-4">
                    <div class="row mb-3 g-3">
                        <div class="col-md-2">
                            <label class="small text-uppercase text-muted d-block">PO Number</label>
                            <span class="fw-bold fs-4 text-primary">PO-<?= $po['id'] ?></span>
                        </div>
                        <div class="col-md-3">
                            <label class="small text-uppercase text-muted d-block">Project</label>
                            <span class="fw-bold"><?= htmlspecialchars($po['project_name']) ?></span>
                        </div>
                        <div class="col-md-2">
                            <label class="small text-uppercase text-muted d-block">Status</label>
                            <?php
                            $badge = match($po['status']) {
                                'Issued'             => 'bg-info',
                                'Partially Received' => 'bg-warning text-dark',
                                'Fully Received'     => 'bg-success',
                                'Cancelled'          => 'bg-danger',
                                default              => 'bg-secondary',
                            };
                            ?>
                            <span class="badge <?= $badge ?> px-3"><?= $po['status'] ?></span>
                        </div>
                        <div class="col-md-2">
                            <label class="small text-uppercase text-muted d-block">Order Date</label>
                            <span class="fw-bold"><?= date('d M Y', strtotime($po['order_date'])) ?></span>
                        </div>
                        <div class="col-md-3">
                            <label class="small text-uppercase text-muted d-block">Created By</label>
                            <span class="fw-bold"><?= htmlspecialchars($po['created_by_name'] ?? '—') ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PO Items -->
            <div class="card__wrapper shadow-sm mb-4">
                <div class="card__header">
                    <h6 class="card__title mb-0"><i class="bi bi-list-check me-2"></i>Order Items</h6>
                </div>
                <div class="card__body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Material</th>
                                    <th>Vendor</th>
                                    <th>Unit</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $grandTotal = 0;
                                foreach ($items as $i => $item):
                                    $lineTotal = $item['quantity'] * $item['unit_price'];
                                    $grandTotal += $lineTotal;
                                ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($item['material_name']) ?></td>
                                    <td><?= htmlspecialchars($item['vendor_name']) ?></td>
                                    <td><?= htmlspecialchars($item['unit']) ?></td>
                                    <td class="text-end"><?= number_format($item['quantity'], 3) ?></td>
                                    <td class="text-end">৳ <?= number_format($item['unit_price'], 2) ?></td>
                                    <td class="text-end fw-bold">৳ <?= number_format($lineTotal, 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="6" class="text-end py-3">TOTAL ORDER VALUE:</th>
                                    <th class="text-end py-3 fs-5 text-primary">৳ <?= number_format($grandTotal, 2) ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pending Delivery Tracker -->
            <div class="card__wrapper shadow-sm">
                <div class="card__header">
                    <h6 class="card__title mb-0"><i class="bi bi-truck me-2"></i>Delivery Tracker (Pending Items)</h6>
                </div>
                <div class="card__body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Material</th>
                                    <th>Vendor</th>
                                    <th class="text-end">Ordered Qty</th>
                                    <th class="text-end">Received Qty</th>
                                    <th class="text-end">Pending Qty</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingItems as $pi): ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($pi['material_name']) ?></td>
                                    <td><?= htmlspecialchars($pi['vendor_name']) ?></td>
                                    <td class="text-end"><?= number_format($pi['quantity'], 3) ?></td>
                                    <td class="text-end text-success"><?= number_format($pi['received_total'], 3) ?></td>
                                    <td class="text-end <?= ($pi['pending_qty'] > 0) ? 'text-danger fw-bold' : 'text-success' ?>">
                                        <?= number_format($pi['pending_qty'], 3) ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($pi['pending_qty'] <= 0): ?>
                                            <span class="badge bg-success"><i class="bi bi-check-lg"></i> Complete</span>
                                        <?php elseif ($pi['received_total'] > 0): ?>
                                            <span class="badge bg-warning text-dark">Partial</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Action: Create Material Receiving -->
            <?php if ($po['status'] !== 'Fully Received' && $po['status'] !== 'Cancelled'): ?>
            <div class="mt-4 text-end">
                <a href="<?= $base_url ?>/con-material-receiving-create/<?= $po['id'] ?>"
                   class="btn btn-primary px-4">
                    <i class="bi bi-box-seam me-1"></i> Log Material Receiving (GRN)
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
@media print {
    .pagetitle .d-flex.gap-2, .sidebar, .main-header-container, .main-footer,
    .mt-4.text-end { display: none !important; }
    .main-content { margin: 0 !important; padding: 0 !important; }
    .card__wrapper { border: none !important; box-shadow: none !important; }
}
</style>

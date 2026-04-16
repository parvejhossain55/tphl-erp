<div class="container-fluid pt-4">
    <div class="row">
        <div class="col-12 col-xl-10 mx-auto">
            <div class="card__wrapper border-0 shadow-sm">
                <div class="card__header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
                    <div>
                        <h4 class="mb-0 text-primary">BOQ Details</h4>
                        <small class="text-muted">Project: <?= htmlspecialchars($boq['project_name']) ?></small>
                    </div>
                    <div class="btn-group">
                        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                            <i class="fa-solid fa-print me-1"></i> Print
                        </button>
                        <a href="<?= $base_url ?>/con-boq" class="btn btn-secondary btn-sm">
                            <i class="fa-solid fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
                <div class="card__body p-4">
                    <div class="row mb-4 g-4">
                        <div class="col-md-3">
                            <label class="small text-uppercase text-muted d-block">Version</label>
                            <span class="fw-bold fs-5"><?= htmlspecialchars($boq['version']) ?></span>
                        </div>
                        <div class="col-md-3">
                            <label class="small text-uppercase text-muted d-block">Status</label>
                            <span class="badge bg-<?= $boq['status'] == 'Approved' ? 'success' : 'warning' ?> px-3">
                                <?= $boq['status'] ?>
                            </span>
                        </div>
                        <div class="col-md-3">
                            <label class="small text-uppercase text-muted d-block">Total Estimated Budget</label>
                            <span class="fw-bold fs-5 text-dark">
                                <?php 
                                    $total = 0;
                                    foreach($items as $item) $total += $item['total_cost'];
                                    echo number_format($total, 2);
                                ?> 
                            </span>
                        </div>
                        <div class="col-md-3">
                            <label class="small text-uppercase text-muted d-block">Created On</label>
                            <span class="fw-bold"><?= date('d M, Y', strtotime($boq['created_at'])) ?></span>
                        </div>
                    </div>

                    <div class="table__wrapper">
                        <table class="table table-striped table-bordered align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Material Name</th>
                                    <th>Unit</th>
                                    <th class="text-end">Quantity</th>
                                    <th class="text-end">Estimated Unit Cost</th>
                                    <th class="text-end">Total Estimated Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $index => $item): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td class="fw-bold"><?= htmlspecialchars($item['material_name']) ?></td>
                                        <td><?= htmlspecialchars($item['unit']) ?></td>
                                        <td class="text-end"><?= number_format($item['quantity'], 3) ?></td>
                                        <td class="text-end"><?= number_format($item['unit_cost'], 2) ?></td>
                                        <td class="text-end fw-bold"><?= number_format($item['total_cost'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="5" class="text-end py-3">GRAND TOTAL ESTIMATED COST:</th>
                                    <th class="text-end py-3 fs-5 text-primary"><?= number_format($total, 2) ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .btn-group, .sidebar, .main-header-container, .main-footer {
        display: none !important;
    }
    .main-content {
        margin: 0 !important;
        padding: 0 !important;
    }
    .card__wrapper {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>

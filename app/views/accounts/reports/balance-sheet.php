<?php 
// Layout handled by Controller via master.php
?>

<div class="container-fluid mt-4">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h2 class="mb-0">
                <i class="fa-light fa-scale-balanced"></i> Balance Sheet
            </h2>
            <p class="text-muted mb-0">Statement of Financial Position</p>
        </div>
        <div class="col-md-6 text-end">
            <button class="btn btn-outline-primary me-2" onclick="window.print()">
                <i class="fa-light fa-print"></i> Print
            </button>
            <a href="<?= $base_url ?>/financial-dashboard" class="btn btn-outline-secondary">
                <i class="fa-light fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card shadow-sm border-0 mb-4 d-print-none">
        <div class="card-body py-3">
            <form method="GET" action="<?= $base_url ?>/reports/balance-sheet" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-bold small">As of Date</label>
                    <input type="date" class="form-control" name="as_of_date" value="<?= htmlspecialchars($filters['as_of_date']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small">Project</label>
                    <select class="form-select" name="project_id">
                        <option value="">All Projects</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= ($filters['project_id'] == $p['id']) ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small">Office</label>
                    <select class="form-select" name="office">
                        <option value="">All Offices</option>
                        <option value="Corporate Office" <?= ($filters['office'] == 'Corporate Office') ? 'selected' : '' ?>>Corporate Office</option>
                        <option value="Site Office" <?= ($filters['office'] == 'Site Office') ? 'selected' : '' ?>>Site Office</option>
                        <option value="Site Office 2" <?= ($filters['office'] == 'Site Office 2') ? 'selected' : '' ?>>Site Office 2</option>
                        <option value="Zonal Office" <?= ($filters['office'] == 'Zonal Office') ? 'selected' : '' ?>>Zonal Office</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa-light fa-filter me-1"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Printable Report -->
    <div class="printable-area">
        <div class="report-wrapper" style="max-width: 900px; margin: 0 auto;">
            <!-- Print Header -->
            <div class="text-center mb-2 pb-2 border-bottom border-dark">
                <h3 class="mb-0 fw-bold text-uppercase">THE PREMIUM HOMES LTD</h3>
                <h5 class="mb-1">Balance Sheet</h5>
                <p class="text-muted mb-0">As of <?= date('d M Y', strtotime($filters['as_of_date'])) ?></p>
                <?php if(!empty($filters['project_id']) || !empty($filters['office'])): ?>
                <p class="small text-muted mb-0">
                    <?php 
                        $filterParts = [];
                        if(!empty($filters['project_id'])) {
                            foreach($projects as $p) {
                                if($p['id'] == $filters['project_id']) { $filterParts[] = 'Project: ' . $p['name']; break; }
                            }
                        }
                        if(!empty($filters['office'])) $filterParts[] = 'Office: ' . $filters['office'];
                        echo htmlspecialchars(implode(' | ', $filterParts));
                    ?>
                </p>
                <?php endif; ?>
            </div>

            <!-- Assets Section -->
            <table class="table table-bordered border-dark report-table mb-0">
                <thead>
                    <tr class="table-dark">
                        <th colspan="2" class="text-center fs-6">ASSETS</th>
                    </tr>
                    <tr>
                        <th style="width: 70%;">Account</th>
                        <th class="text-end" style="width: 30%;">Amount (BDT)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($report['assets'])): ?>
                        <?php foreach($report['assets'] as $acc): ?>
                        <tr>
                            <td class="ps-4">
                                <?= htmlspecialchars($acc['account_code']) ?> - 
                                <?= htmlspecialchars($acc['parent_name'] ? $acc['parent_name'] . ' > ' . $acc['account_name'] : $acc['account_name']) ?>
                            </td>
                            <td class="text-end"><?= number_format($acc['balance'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2" class="text-center text-muted fst-italic">No asset transactions found</td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="fw-bold table-light">
                        <td class="text-end">Total Assets</td>
                        <td class="text-end"><?= number_format($report['total_assets'], 2) ?></td>
                    </tr>
                </tfoot>
            </table>

            <!-- Liabilities Section -->
            <table class="table table-bordered border-dark report-table mb-0 mt-3">
                <thead>
                    <tr class="table-dark">
                        <th colspan="2" class="text-center fs-6">LIABILITIES</th>
                    </tr>
                    <tr>
                        <th style="width: 70%;">Account</th>
                        <th class="text-end" style="width: 30%;">Amount (BDT)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($report['liabilities'])): ?>
                        <?php foreach($report['liabilities'] as $acc): ?>
                        <tr>
                            <td class="ps-4">
                                <?= htmlspecialchars($acc['account_code']) ?> - 
                                <?= htmlspecialchars($acc['parent_name'] ? $acc['parent_name'] . ' > ' . $acc['account_name'] : $acc['account_name']) ?>
                            </td>
                            <td class="text-end"><?= number_format($acc['balance'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2" class="text-center text-muted fst-italic">No liability transactions found</td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="fw-bold table-light">
                        <td class="text-end">Total Liabilities</td>
                        <td class="text-end"><?= number_format($report['total_liabilities'], 2) ?></td>
                    </tr>
                </tfoot>
            </table>

            <!-- Equity Section -->
            <table class="table table-bordered border-dark report-table mb-0 mt-3">
                <thead>
                    <tr class="table-dark">
                        <th colspan="2" class="text-center fs-6">EQUITY</th>
                    </tr>
                    <tr>
                        <th style="width: 70%;">Account</th>
                        <th class="text-end" style="width: 30%;">Amount (BDT)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($report['equity'])): ?>
                        <?php foreach($report['equity'] as $acc): ?>
                        <tr>
                            <td class="ps-4">
                                <?= htmlspecialchars($acc['account_code']) ?> - 
                                <?= htmlspecialchars($acc['parent_name'] ? $acc['parent_name'] . ' > ' . $acc['account_name'] : $acc['account_name']) ?>
                            </td>
                            <td class="text-end"><?= number_format($acc['balance'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2" class="text-center text-muted fst-italic">No equity transactions found</td></tr>
                    <?php endif; ?>
                    <!-- Current Period Net Income -->
                    <tr class="fst-italic">
                        <td class="ps-4">Current Period Net Income</td>
                        <td class="text-end"><?= number_format($report['net_income'], 2) ?></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="fw-bold table-light">
                        <td class="text-end">Total Equity</td>
                        <td class="text-end"><?= number_format($report['total_equity_with_income'], 2) ?></td>
                    </tr>
                </tfoot>
            </table>

            <!-- Grand Total -->
            <table class="table table-bordered border-dark report-table mb-4 mt-3">
                <tfoot>
                    <tr class="fw-bold table-dark">
                        <td class="text-end fs-6" style="width: 70%;">Total Liabilities & Equity</td>
                        <td class="text-end fs-6" style="width: 30%;"><?= number_format($report['total_liabilities_and_equity'], 2) ?></td>
                    </tr>
                </tfoot>
            </table>

            <!-- Balance Check -->
            <?php if($report['is_balanced']): ?>
                <div class="alert alert-success text-center d-print-none">
                    <i class="fa-light fa-check-circle me-1"></i> <strong>Balanced!</strong> Assets = Liabilities + Equity
                </div>
            <?php else: ?>
                <div class="alert alert-danger text-center d-print-none">
                    <i class="fa-light fa-exclamation-triangle me-1"></i> <strong>Not Balanced!</strong> 
                    Difference: <?= number_format(abs($report['total_assets'] - $report['total_liabilities_and_equity']), 2) ?> BDT
                </div>
            <?php endif; ?>

            <!-- Print Footer -->
            <div class="row mt-4 pt-3">
                <div class="col-6">
                    <p class="text-muted small">Generated on: <?= date('d M Y, h:i A') ?></p>
                </div>
                <div class="col-6 text-end d-none d-print-block mt-4">
                    <div class="d-inline-block text-center pt-2" style="width: 200px; border-top: 1px solid #000;">
                        Authorized Signature
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.report-table th, .report-table td {
    border: 1px solid #000 !important;
}
@media print {
    @page { margin: 10mm; }
    html, body { background-color: #fff !important; color: #000 !important; }
    body * { visibility: hidden; }
    .printable-area, .printable-area * { visibility: visible; color: #000 !important; background-color: transparent !important; }
    .printable-area { position: absolute; left: 0; top: 0; width: 100%; background-color: #fff !important; }
    .table-dark th { background-color: #333 !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .report-table th, .report-table td { border-color: #000 !important; }
    .text-muted { color: #6c757d !important; }
}
</style>

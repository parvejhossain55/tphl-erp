<?php 
// Layout handled by Controller via master.php
$accounts = $report['accounts'] ?? [];
$totalInflow = $report['total_inflow'] ?? 0;
$totalOutflow = $report['total_outflow'] ?? 0;
$netCashflow = $report['net_cashflow'] ?? 0;
$isPositiveNet = $netCashflow >= 0;
$layoutMode = $_GET['layout'] ?? 'vertical';
?>

<div class="container-fluid mt-4">
    <!-- Header & Action Bar -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h2 class="mb-1 text-dark fw-bold">
                <i class="fa-light fa-money-bill-transfer text-purple me-2" style="color: #6f42c1;"></i> Cashflow Statement (ক্যাশ ফ্লো বিবরণী)
            </h2>
            <p class="text-muted mb-0 fs-13">
                Statement of Cash Inflows and Outflows for period <?= date('d M Y', strtotime($filters['date_from'])) ?> to <?= date('d M Y', strtotime($filters['date_to'])) ?>
            </p>
        </div>
        <div class="col-md-6 text-end">
            <button type="button" onclick="window.print()" class="btn btn-outline-secondary me-2">
                <i class="fa-light fa-print me-1"></i> Print Statement
            </button>
            <a href="<?= $base_url ?>/financial-dashboard" class="btn btn-outline-primary">
                <i class="fa-light fa-chart-line me-1"></i> Financial Dashboard
            </a>
        </div>
    </div>

    <!-- Filter Bar & Layout Switcher -->
    <div class="card shadow-sm border-0 mb-4 d-print-none">
        <div class="card-body py-3">
            <form method="GET" action="<?= $base_url ?>/reports/cashflow" class="row  align-items-end">
                <div class="col-md-3">
                    <label class="form-label fs-11 fw-semibold mb-1">Layout Format</label>
                    <select name="layout" class="form-select form-select-sm fw-bold text-purple" style="color: #6f42c1;" onchange="this.form.submit()">
                        <option value="vertical" <?= $layoutMode === 'vertical' ? 'selected' : '' ?>>📊 Vertical Statement Format (Standard)</option>
                        <option value="horizontal" <?= $layoutMode === 'horizontal' ? 'selected' : '' ?>>⚖️ Horizontal Dual-Column Format (Side-by-Side)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fs-11 fw-semibold mb-1">From Date</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_from']) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fs-11 fw-semibold mb-1">To Date</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_to']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fs-11 fw-semibold mb-1">Project</label>
                    <select name="project_id" class="form-select form-select-sm">
                        <option value="">All Projects</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= ($filters['project_id'] == $p['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill">
                        <i class="fa-solid fa-filter me-1"></i> Apply
                    </button>
                    <a href="<?= $base_url ?>/reports/cashflow" class="btn btn-light btn-sm flex-fill text-center">
                        <i class="fa-solid fa-rotate me-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row  mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-success-transparent p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted fs-12 fw-semibold">TOTAL CASH INFLOW (DEBIT)</span>
                        <h3 class="mb-0 fw-bold text-success mt-1">৳ <?= number_format($totalInflow, 2) ?></h3>
                    </div>
                    <div class="avatar avatar-md bg-success text-white rounded-circle">
                        <i class="fa-light fa-arrow-down-left fs-18"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-danger-transparent p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted fs-12 fw-semibold">TOTAL CASH OUTFLOW (CREDIT)</span>
                        <h3 class="mb-0 fw-bold text-danger mt-1">৳ <?= number_format($totalOutflow, 2) ?></h3>
                    </div>
                    <div class="avatar avatar-md bg-danger text-white rounded-circle">
                        <i class="fa-light fa-arrow-up-right fs-18"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm <?= $isPositiveNet ? 'bg-primary-transparent' : 'bg-warning-transparent' ?> p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted fs-12 fw-semibold"><?= $isPositiveNet ? 'NET CASH SURPLUS' : 'NET CASH DEFICIT' ?></span>
                        <h3 class="mb-0 fw-bold <?= $isPositiveNet ? 'text-primary' : 'text-warning' ?> mt-1">
                            ৳ <?= number_format(abs($netCashflow), 2) ?>
                        </h3>
                        <small class="text-muted fs-11">Total Inflow minus Outflow</small>
                    </div>
                    <div class="avatar avatar-md <?= $isPositiveNet ? 'bg-primary' : 'bg-warning' ?> text-white rounded-circle">
                        <i class="fa-light <?= $isPositiveNet ? 'fa-wallet' : 'fa-money-bill-simple-wave' ?> fs-18"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Area -->
    <div class="printable-area mb-5">
        <?php if ($layoutMode === 'horizontal'): ?>
            <!-- HORIZONTAL DUAL COLUMN LAYOUT -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-dark text-white py-3">
                    <div class="text-center">
                        <h4 class="mb-1 fw-bold text-uppercase text-white">THE PREMIUM HOMES LTD</h4>
                        <h5 class="mb-0 text-info fw-bold">CASHFLOW STATEMENT (SIDE-BY-SIDE DUAL COLUMN)</h5>
                        <small class="text-white-50">Period: <?= date('d M Y', strtotime($filters['date_from'])) ?> to <?= date('d M Y', strtotime($filters['date_to'])) ?></small>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="row g-0">
                        <!-- Left Side: Cash Inflow -->
                        <div class="col-md-6 border-end">
                            <div class="bg-light p-3 border-bottom text-center">
                                <h6 class="mb-0 fw-bold text-success"><i class="fa-light fa-arrow-down-left me-1"></i> CASH INFLOWS (COLLECTIONS & DEPOSITS)</h6>
                            </div>
                            <div class="p-3">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr class="table-light">
                                            <th>Account Code & Particulars</th>
                                            <th class="text-end">Inflow (BDT)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($accounts)): ?>
                                            <tr><td colspan="2" class="text-center text-muted py-4">No cash inflow transactions found</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($accounts as $acc): ?>
                                                <?php if ($acc['total_inflow'] > 0): ?>
                                                    <tr>
                                                        <td>
                                                            <span class="badge bg-light text-dark font-monospace border me-2"><?= htmlspecialchars($acc['account_code']) ?></span>
                                                            <span class="fw-semibold text-dark fs-13"><?= htmlspecialchars($acc['account_name']) ?></span>
                                                        </td>
                                                        <td class="text-end font-monospace text-success fw-bold fs-13">৳ <?= number_format($acc['total_inflow'], 2) ?></td>
                                                    </tr>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot class="border-top border-dark">
                                        <tr class="fw-bold table-light">
                                            <td>TOTAL CASH INFLOW</td>
                                            <td class="text-end font-monospace text-success fs-14">৳ <?= number_format($totalInflow, 2) ?></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Right Side: Cash Outflow -->
                        <div class="col-md-6">
                            <div class="bg-light p-3 border-bottom text-center">
                                <h6 class="mb-0 fw-bold text-danger"><i class="fa-light fa-arrow-up-right me-1"></i> CASH OUTFLOWS (DISBURSEMENTS & PAYMENTS)</h6>
                            </div>
                            <div class="p-3">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr class="table-light">
                                            <th>Account Code & Particulars</th>
                                            <th class="text-end">Outflow (BDT)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($accounts)): ?>
                                            <tr><td colspan="2" class="text-center text-muted py-4">No cash outflow transactions found</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($accounts as $acc): ?>
                                                <?php if ($acc['total_outflow'] > 0): ?>
                                                    <tr>
                                                        <td>
                                                            <span class="badge bg-light text-dark font-monospace border me-2"><?= htmlspecialchars($acc['account_code']) ?></span>
                                                            <span class="fw-semibold text-dark fs-13"><?= htmlspecialchars($acc['account_name']) ?></span>
                                                        </td>
                                                        <td class="text-end font-monospace text-danger fw-bold fs-13">৳ <?= number_format($acc['total_outflow'], 2) ?></td>
                                                    </tr>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot class="border-top border-dark">
                                        <tr class="fw-bold table-light">
                                            <td>TOTAL CASH OUTFLOW</td>
                                            <td class="text-end font-monospace text-danger fs-14">৳ <?= number_format($totalOutflow, 2) ?></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- VERTICAL STATEMENT LAYOUT (STANDARD DEFAULT) -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <div class="text-center">
                        <h4 class="mb-1 fw-bold text-uppercase text-dark">THE PREMIUM HOMES LTD</h4>
                        <h5 class="mb-1 text-primary fw-bold">CASHFLOW STATEMENT</h5>
                        <p class="text-muted mb-0 fs-13">
                            For the period: <strong><?= date('d M Y', strtotime($filters['date_from'])) ?></strong> to <strong><?= date('d M Y', strtotime($filters['date_to'])) ?></strong>
                        </p>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="140">Account Code</th>
                                    <th>Account Description</th>
                                    <th class="text-end" width="180">Inflow (Debit BDT)</th>
                                    <th class="text-end" width="180">Outflow (Credit BDT)</th>
                                    <th class="text-end" width="180">Net Cash Position</th>
                                    <th width="80" class="text-center">Ledger</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($accounts)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted fs-13">
                                            <i class="fa-light fa-inbox fs-2 d-block mb-2 text-secondary"></i>
                                            No cash/bank activity recorded for the selected period.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($accounts as $acc): ?>
                                        <?php $net = $acc['total_inflow'] - $acc['total_outflow']; ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-light text-dark font-monospace border px-2 py-1 fs-12 fw-bold">
                                                    <?= htmlspecialchars($acc['account_code']) ?>
                                                </span>
                                            </td>
                                            <td class="fw-semibold text-dark fs-13">
                                                <?= htmlspecialchars($acc['account_name']) ?>
                                            </td>
                                            <td class="text-end font-monospace text-success fw-bold fs-13">
                                                <?= $acc['total_inflow'] > 0 ? '৳ ' . number_format($acc['total_inflow'], 2) : '—' ?>
                                            </td>
                                            <td class="text-end font-monospace text-danger fw-bold fs-13">
                                                <?= $acc['total_outflow'] > 0 ? '৳ ' . number_format($acc['total_outflow'], 2) : '—' ?>
                                            </td>
                                            <td class="text-end font-monospace fw-bold fs-13 <?= $net >= 0 ? 'text-success' : 'text-danger' ?>">
                                                ৳ <?= number_format($net, 2) ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="<?= $base_url ?>/accounts/ledger/<?= $acc['account_id'] ?>" class="btn btn-sm btn-outline-info btn-icon" title="View Cash/Bank Ledger">
                                                    <i class="fa-light fa-book-open"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <tfoot class="table-dark">
                                <tr>
                                    <td colspan="2" class="text-end fw-bold fs-14">GRAND TOTAL:</td>
                                    <td class="text-end font-monospace fw-bold fs-14 text-success">৳ <?= number_format($totalInflow, 2) ?></td>
                                    <td class="text-end font-monospace fw-bold fs-14 text-danger">৳ <?= number_format($totalOutflow, 2) ?></td>
                                    <td class="text-end font-monospace fw-bold fs-14 <?= $isPositiveNet ? 'text-success' : 'text-warning' ?>">
                                        ৳ <?= number_format($netCashflow, 2) ?>
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Net Cash Summary Box -->
                    <div class="mt-4 p-4 rounded border <?= $isPositiveNet ? 'bg-success-transparent border-success' : 'bg-warning-transparent border-warning' ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1 fw-bold <?= $isPositiveNet ? 'text-success' : 'text-dark' ?>">
                                    <i class="fa-light <?= $isPositiveNet ? 'fa-wallet text-success' : 'fa-money-bill-simple-wave text-warning' ?> me-2"></i>
                                    <?= $isPositiveNet ? 'NET CASH SURPLUS INFLOW' : 'NET CASH DEFICIT OUTFLOW' ?>
                                </h5>
                                <small class="text-muted">Calculated as (Total Inflow - Total Outflow)</small>
                            </div>
                            <h3 class="mb-0 font-monospace fw-bold <?= $isPositiveNet ? 'text-success' : 'text-danger' ?>">
                                ৳ <?= number_format(abs($netCashflow), 2) ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
// Layout handled by Controller via master.php
$revenue = $report['revenue'] ?? [];
$expenses = $report['expenses'] ?? [];
$directRevenue = $report['direct_revenue'] ?? [];
$indirectRevenue = $report['indirect_revenue'] ?? [];
$directExpenses = $report['direct_expenses'] ?? [];
$operatingExpenses = $report['operating_expenses'] ?? [];
$totalDirectRevenue = $report['total_direct_revenue'] ?? 0;
$totalIndirectRevenue = $report['total_indirect_revenue'] ?? 0;
$totalRevenue = $report['total_revenue'] ?? 0;
$totalDirectExpenses = $report['total_direct_expenses'] ?? 0;
$totalOperatingExpenses = $report['total_operating_expenses'] ?? 0;
$totalExpenses = $report['total_expenses'] ?? 0;
$grossProfit = $report['gross_profit'] ?? 0;
$operatingProfit = $report['operating_profit'] ?? 0;
$netIncome = $report['net_income'] ?? 0;
$isProfit = $netIncome >= 0;
$layoutMode = $_GET['layout'] ?? 'vertical';
?>
<style>
    .cursor-pointer { cursor: pointer; }
    .module-breakdown-row { background-color: #f8f9fa; }
    .collapse-icon { transition: transform 0.2s ease; }
    .collapsed-row[aria-expanded="true"] .collapse-icon { transform: rotate(90deg); }
</style>
<div class="container-fluid mt-4">
    <!-- Header & Action Bar -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h2 class="mb-1 text-dark fw-bold">
                <i class="fa-light fa-chart-line text-success me-2"></i> Profit & Loss Statement (লাভ-ক্ষতি বিবরণী)
            </h2>
            <p class="text-muted mb-0 fs-13">
                Statement of Comprehensive Income for period <?= date('d M Y', strtotime($filters['date_from'])) ?> to <?= date('d M Y', strtotime($filters['date_to'])) ?>
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
            <form method="GET" action="<?= $base_url ?>/reports/profit-loss" class="row  align-items-end">
                <div class="col-md-3">
                    <label class="form-label fs-11 fw-semibold mb-1">Layout Format</label>
                    <select name="layout" class="form-select form-select-sm fw-bold text-success" onchange="this.form.submit()">
                        <option value="vertical" <?= $layoutMode === 'vertical' ? 'selected' : '' ?>>📊 Vertical Statement Format (Standard)</option>
                        <option value="horizontal" <?= $layoutMode === 'horizontal' ? 'selected' : '' ?>>⚖️ Horizontal T-Account Format (Two Column)</option>
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
                    <a href="<?= $base_url ?>/reports/profit-loss" class="btn btn-light btn-sm flex-fill text-center">
                        <i class="fa-solid fa-rotate me-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row  mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-success-transparent p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted fs-12 fw-semibold">TOTAL OPERATING REVENUE</span>
                        <h3 class="mb-0 fw-bold text-success mt-1">৳ <?= number_format($totalRevenue, 2) ?></h3>
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
                        <span class="text-muted fs-12 fw-semibold">TOTAL OPERATING EXPENSES</span>
                        <h3 class="mb-0 fw-bold text-danger mt-1">৳ <?= number_format($totalExpenses, 2) ?></h3>
                    </div>
                    <div class="avatar avatar-md bg-danger text-white rounded-circle">
                        <i class="fa-light fa-arrow-up-right fs-18"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm <?= $isProfit ? 'bg-primary-transparent' : 'bg-warning-transparent' ?> p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted fs-12 fw-semibold"><?= $isProfit ? 'NET PROFIT' : 'NET OPERATING LOSS' ?></span>
                        <h3 class="mb-0 fw-bold <?= $isProfit ? 'text-primary' : 'text-warning' ?> mt-1">
                            ৳ <?= number_format(abs($netIncome), 2) ?>
                        </h3>
                        <small class="text-muted fs-11">Revenue minus Expenses</small>
                    </div>
                    <div class="avatar avatar-md <?= $isProfit ? 'bg-primary' : 'bg-warning' ?> text-white rounded-circle">
                        <i class="fa-light <?= $isProfit ? 'fa-chart-line-up' : 'fa-chart-line-down' ?> fs-18"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Area -->
    <div class="printable-area mb-5">
        <?php if ($layoutMode === 'horizontal'): ?>
            <!-- HORIZONTAL T-ACCOUNT LAYOUT -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-dark text-white py-3">
                    <div class="text-center">
                        <h4 class="mb-1 fw-bold text-uppercase text-white">THE PREMIUM HOMES LTD</h4>
                        <h5 class="mb-0 text-success fw-bold">PROFIT & LOSS STATEMENT (T-ACCOUNT FORMAT)</h5>
                        <small class="text-white-50">Period: <?= date('d M Y', strtotime($filters['date_from'])) ?> to <?= date('d M Y', strtotime($filters['date_to'])) ?></small>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="row g-0">
                        <!-- Left Side: Expenses -->
                        <div class="col-md-6 border-end">
                            <div class="bg-light p-3 border-bottom text-center">
                                <h6 class="mb-0 fw-bold text-danger"><i class="fa-light fa-arrow-up-right me-1"></i> EXPENSES & WIP COSTS (DEBIT)</h6>
                            </div>
                            <div class="p-3">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr class="table-light">
                                            <th>Account Code & Particulars</th>
                                            <th class="text-end">Amount (BDT)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($expenses)): ?>
                                            <tr><td colspan="2" class="text-center text-muted py-4">No expense items recorded</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($expenses as $acc): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-light text-dark font-monospace border me-2"><?= htmlspecialchars($acc['account_code']) ?></span>
                                                        <span class="fw-semibold text-dark fs-13"><?= htmlspecialchars($acc['account_name']) ?></span>
                                                    </td>
                                                    <td class="text-end font-monospace text-danger fw-bold fs-13">৳ <?= number_format($acc['balance'], 2) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot class="border-top border-dark">
                                        <tr class="fw-bold table-light">
                                            <td>TOTAL EXPENSES</td>
                                            <td class="text-end font-monospace text-danger fs-14">৳ <?= number_format($totalExpenses, 2) ?></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Right Side: Revenue -->
                        <div class="col-md-6">
                            <div class="bg-light p-3 border-bottom text-center">
                                <h6 class="mb-0 fw-bold text-success"><i class="fa-light fa-arrow-down-left me-1"></i> REVENUES & INCOME (CREDIT)</h6>
                            </div>
                            <div class="p-3">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr class="table-light">
                                            <th>Account Code & Particulars</th>
                                            <th class="text-end">Amount (BDT)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($revenue)): ?>
                                            <tr><td colspan="2" class="text-center text-muted py-4">No revenue items recorded</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($revenue as $acc): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-light text-dark font-monospace border me-2"><?= htmlspecialchars($acc['account_code']) ?></span>
                                                        <span class="fw-semibold text-dark fs-13"><?= htmlspecialchars($acc['account_name']) ?></span>
                                                    </td>
                                                    <td class="text-end font-monospace text-success fw-bold fs-13">৳ <?= number_format($acc['balance'], 2) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        <?php if (!$isProfit): ?>
                                            <!-- Balancing Net Loss Row on Right Side -->
                                            <tr class="bg-warning-transparent">
                                                <td class="fw-bold text-warning">
                                                    <i class="fa-light fa-scale-balanced me-1"></i> NET LOSS (BALANCING ENTRY)
                                                </td>
                                                <td class="text-end font-monospace text-warning fw-bold fs-13">৳ <?= number_format(abs($netIncome), 2) ?></td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot class="border-top border-dark">
                                        <tr class="fw-bold table-light">
                                            <td>TOTAL CREDIT & BALANCING</td>
                                            <td class="text-end font-monospace text-success fs-14">৳ <?= number_format(max($totalRevenue, $totalExpenses), 2) ?></td>
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
                        <h5 class="mb-1 text-success fw-bold">PROFIT & LOSS STATEMENT (GAAP CLASSIFICATION)</h5>
                        <p class="text-muted mb-0 fs-13">
                            For the period: <strong><?= date('d M Y', strtotime($filters['date_from'])) ?></strong> to <strong><?= date('d M Y', strtotime($filters['date_to'])) ?></strong>
                        </p>
                    </div>
                </div>
                <div class="card-body p-4">
                    <!-- Operating Revenue Section -->
                    <div class="mb-5">
                        <div class="d-flex justify-content-between align-items-center bg-success-transparent p-3 rounded mb-3">
                            <h5 class="mb-0 fw-bold text-success"><i class="fa-light fa-circle-dollar text-success me-2"></i> 1. REVENUES & INCOME (রাজস্ব ও আয়)</h5>
                            <span class="badge bg-success text-white px-3 py-2 font-monospace fs-14">TOTAL REVENUE: ৳ <?= number_format($totalRevenue, 2) ?></span>
                        </div>

                        <!-- 1.1 Direct / Operating Income -->
                        <div class="mb-4 ps-3 border-start border-3 border-success">
                            <h6 class="fw-bold text-success text-uppercase mb-2"><i class="fa-solid fa-folder me-1"></i> 1.1 Direct / Operating Income (সরাসরি ও পরিচালন আয়)</h6>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="140">Account Code</th>
                                            <th>Account Description</th>
                                            <th class="text-end" width="200">Amount (BDT)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($directRevenue)): ?>
                                            <tr><td colspan="3" class="text-center text-muted py-2">No direct operating revenue recorded.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($directRevenue as $acc): ?>
                                                <?php $rowId = 'dr_mod_' . $acc['account_id']; ?>
                                                <tr class="cursor-pointer collapsed-row" data-bs-toggle="collapse" data-bs-target="#<?= $rowId ?>">
                                                    <td><span class="badge bg-light text-dark font-monospace border px-2 py-1 fs-12 fw-bold"><?= htmlspecialchars($acc['account_code']) ?></span></td>
                                                    <td class="fw-semibold text-dark fs-13">
                                                        <i class="fa-solid fa-chevron-right text-muted me-2 fs-11 collapse-icon"></i>
                                                        <?= htmlspecialchars($acc['account_name']) ?>
                                                    </td>
                                                    <td class="text-end font-monospace text-success fw-bold fs-13">৳ <?= number_format($acc['balance'], 2) ?></td>
                                                </tr>
                                                <?php if(!empty($acc['modules'])): ?>
                                                <tr id="<?= $rowId ?>" class="collapse module-breakdown-row">
                                                    <td colspan="3" class="p-0 border-0">
                                                        <div class="ms-5 ps-3 py-2 border-start border-success border-2">
                                                            <table class="table table-sm table-borderless mb-0 w-auto">
                                                                <tbody>
                                                                    <?php foreach($acc['modules'] as $modName => $modBal): if(abs($modBal) < 0.01) continue; ?>
                                                                    <tr>
                                                                        <td class="text-muted fs-12 py-1" style="width: 200px;"><i class="fa-light fa-arrow-turn-down-right me-1"></i> <?= htmlspecialchars($modName) ?></td>
                                                                        <td class="text-end text-muted font-monospace fs-12 py-1">৳ <?= number_format($modBal, 2) ?></td>
                                                                    </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="fw-bold table-light">
                                            <td colspan="2" class="text-end">Subtotal Direct Operating Income:</td>
                                            <td class="text-end font-monospace text-success">৳ <?= number_format($totalDirectRevenue, 2) ?></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- 1.2 Non-Operating Income -->
                        <?php if (!empty($indirectRevenue)): ?>
                        <div class="mb-4 ps-3 border-start border-3 border-info">
                            <h6 class="fw-bold text-info text-uppercase mb-2"><i class="fa-solid fa-folder-open me-1"></i> 1.2 Non-Operating & Other Income (পরোক্ষ ও অন্যান্য আয়)</h6>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="140">Account Code</th>
                                            <th>Account Description</th>
                                            <th class="text-end" width="200">Amount (BDT)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($indirectRevenue as $acc): ?>
                                            <?php $rowId = 'ir_mod_' . $acc['account_id']; ?>
                                            <tr class="cursor-pointer collapsed-row" data-bs-toggle="collapse" data-bs-target="#<?= $rowId ?>">
                                                <td><span class="badge bg-light text-dark font-monospace border px-2 py-1 fs-12 fw-bold"><?= htmlspecialchars($acc['account_code']) ?></span></td>
                                                <td class="fw-semibold text-dark fs-13">
                                                    <i class="fa-solid fa-chevron-right text-muted me-2 fs-11 collapse-icon"></i>
                                                    <?= htmlspecialchars($acc['account_name']) ?>
                                                </td>
                                                <td class="text-end font-monospace text-info fw-bold fs-13">৳ <?= number_format($acc['balance'], 2) ?></td>
                                            </tr>
                                            <?php if(!empty($acc['modules'])): ?>
                                            <tr id="<?= $rowId ?>" class="collapse module-breakdown-row">
                                                <td colspan="3" class="p-0 border-0">
                                                    <div class="ms-5 ps-3 py-2 border-start border-info border-2">
                                                        <table class="table table-sm table-borderless mb-0 w-auto">
                                                            <tbody>
                                                                <?php foreach($acc['modules'] as $modName => $modBal): if(abs($modBal) < 0.01) continue; ?>
                                                                <tr>
                                                                    <td class="text-muted fs-12 py-1" style="width: 200px;"><i class="fa-light fa-arrow-turn-down-right me-1"></i> <?= htmlspecialchars($modName) ?></td>
                                                                    <td class="text-end text-muted font-monospace fs-12 py-1">৳ <?= number_format($modBal, 2) ?></td>
                                                                </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="fw-bold table-light">
                                            <td colspan="2" class="text-end">Subtotal Non-Operating Income:</td>
                                            <td class="text-end font-monospace text-info">৳ <?= number_format($totalIndirectRevenue, 2) ?></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Direct Expenses & Cost of Sales Section -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center bg-danger-transparent p-3 rounded mb-3">
                            <h5 class="mb-0 fw-bold text-danger"><i class="fa-light fa-hammer me-2"></i> 2. DIRECT PROJECT & CONSTRUCTION EXPENSES (COGS / প্রজেক্ট খরচ)</h5>
                            <span class="badge bg-danger text-white px-3 py-2 font-monospace fs-14">SUBTOTAL DIRECT COSTS: ৳ <?= number_format($totalDirectExpenses, 2) ?></span>
                        </div>
                        <div class="table-responsive mb-3">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="140">Account Code</th>
                                        <th>Account Description</th>
                                        <th class="text-end" width="200">Amount (BDT)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($directExpenses)): ?>
                                        <tr><td colspan="3" class="text-center text-muted py-2">No direct construction expenses recorded.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($directExpenses as $acc): ?>
                                            <?php $rowId = 'de_mod_' . $acc['account_id']; ?>
                                            <tr class="cursor-pointer collapsed-row" data-bs-toggle="collapse" data-bs-target="#<?= $rowId ?>">
                                                <td><span class="badge bg-light text-dark font-monospace border px-2 py-1 fs-12 fw-bold"><?= htmlspecialchars($acc['account_code']) ?></span></td>
                                                <td class="fw-semibold text-dark fs-13">
                                                    <i class="fa-solid fa-chevron-right text-muted me-2 fs-11 collapse-icon"></i>
                                                    <?= htmlspecialchars($acc['account_name']) ?>
                                                </td>
                                                <td class="text-end font-monospace text-danger fw-bold fs-13">৳ <?= number_format($acc['balance'], 2) ?></td>
                                            </tr>
                                            <?php if(!empty($acc['modules'])): ?>
                                            <tr id="<?= $rowId ?>" class="collapse module-breakdown-row">
                                                <td colspan="3" class="p-0 border-0">
                                                    <div class="ms-5 ps-3 py-2 border-start border-danger border-2">
                                                        <table class="table table-sm table-borderless mb-0 w-auto">
                                                            <tbody>
                                                                <?php foreach($acc['modules'] as $modName => $modBal): if(abs($modBal) < 0.01) continue; ?>
                                                                <tr>
                                                                    <td class="text-muted fs-12 py-1" style="width: 200px;"><i class="fa-light fa-arrow-turn-down-right me-1"></i> <?= htmlspecialchars($modName) ?></td>
                                                                    <td class="text-end text-muted font-monospace fs-12 py-1">৳ <?= number_format($modBal, 2) ?></td>
                                                                </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Gross Profit KPI Box -->
                        <div class="p-3 rounded border bg-light d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h6 class="mb-0 fw-bold text-dark text-uppercase"><i class="fa-solid fa-calculator me-1 text-primary"></i> GROSS PROFIT / (LOSS)</h6>
                                <small class="text-muted">(Direct Operating Income ৳ <?= number_format($totalDirectRevenue, 2) ?> - Direct Costs ৳ <?= number_format($totalDirectExpenses, 2) ?>)</small>
                            </div>
                            <h4 class="mb-0 font-monospace fw-bold <?= $grossProfit >= 0 ? 'text-success' : 'text-danger' ?>">
                                ৳ <?= number_format($grossProfit, 2) ?>
                            </h4>
                        </div>
                    </div>

                    <!-- Operating Expenses Section -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center bg-warning-transparent p-3 rounded mb-3">
                            <h5 class="mb-0 fw-bold text-dark"><i class="fa-light fa-receipt me-2"></i> 3. ADMINISTRATIVE, SELLING & OPERATING EXPENSES (OPEX / পরিচালন খরচ)</h5>
                            <span class="badge bg-warning text-dark px-3 py-2 font-monospace fs-14">SUBTOTAL OPEX: ৳ <?= number_format($totalOperatingExpenses, 2) ?></span>
                        </div>
                        <div class="table-responsive mb-3">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="140">Account Code</th>
                                        <th>Account Description</th>
                                        <th class="text-end" width="200">Amount (BDT)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($operatingExpenses)): ?>
                                        <tr><td colspan="3" class="text-center text-muted py-2">No operating expenses recorded.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($operatingExpenses as $acc): ?>
                                            <?php $rowId = 'oe_mod_' . $acc['account_id']; ?>
                                            <tr class="cursor-pointer collapsed-row" data-bs-toggle="collapse" data-bs-target="#<?= $rowId ?>">
                                                <td><span class="badge bg-light text-dark font-monospace border px-2 py-1 fs-12 fw-bold"><?= htmlspecialchars($acc['account_code']) ?></span></td>
                                                <td class="fw-semibold text-dark fs-13">
                                                    <i class="fa-solid fa-chevron-right text-muted me-2 fs-11 collapse-icon"></i>
                                                    <?= htmlspecialchars($acc['account_name']) ?>
                                                </td>
                                                <td class="text-end font-monospace text-dark fw-bold fs-13">৳ <?= number_format($acc['balance'], 2) ?></td>
                                            </tr>
                                            <?php if(!empty($acc['modules'])): ?>
                                            <tr id="<?= $rowId ?>" class="collapse module-breakdown-row">
                                                <td colspan="3" class="p-0 border-0">
                                                    <div class="ms-5 ps-3 py-2 border-start border-warning border-2">
                                                        <table class="table table-sm table-borderless mb-0 w-auto">
                                                            <tbody>
                                                                <?php foreach($acc['modules'] as $modName => $modBal): if(abs($modBal) < 0.01) continue; ?>
                                                                <tr>
                                                                    <td class="text-muted fs-12 py-1" style="width: 200px;"><i class="fa-light fa-arrow-turn-down-right me-1"></i> <?= htmlspecialchars($modName) ?></td>
                                                                    <td class="text-end text-muted font-monospace fs-12 py-1">৳ <?= number_format($modBal, 2) ?></td>
                                                                </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Net Income Result Box -->
                    <div class="p-4 rounded border <?= $isProfit ? 'bg-success-transparent border-success' : 'bg-danger-transparent border-danger' ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1 fw-bold <?= $isProfit ? 'text-success' : 'text-danger' ?>">
                                    <i class="fa-light <?= $isProfit ? 'fa-chart-line-up text-success' : 'fa-chart-line-down text-danger' ?> me-2"></i>
                                    <?= $isProfit ? 'NET PROFIT (নিট লাভ)' : 'NET LOSS (নিট ক্ষতি)' ?>
                                </h5>
                                <small class="text-muted">Calculated as: Gross Profit (৳ <?= number_format($grossProfit, 2) ?>) - OPEX (৳ <?= number_format($totalOperatingExpenses, 2) ?>) + Other Income (৳ <?= number_format($totalIndirectRevenue, 2) ?>)</small>
                            </div>
                            <h3 class="mb-0 font-monospace fw-bold <?= $isProfit ? 'text-success' : 'text-danger' ?>">
                                ৳ <?= number_format(abs($netIncome), 2) ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

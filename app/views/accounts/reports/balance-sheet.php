<?php 
// Layout handled by Controller via master.php
$assets = $report['assets'] ?? [];
$liabilities = $report['liabilities'] ?? [];
$equity = $report['equity'] ?? [];
$nonCurrentAssets = $report['non_current_assets'] ?? [];
$currentAssets = $report['current_assets'] ?? [];
$totalNonCurrentAssets = $report['total_non_current_assets'] ?? 0;
$totalCurrentAssets = $report['total_current_assets'] ?? 0;

$nonCurrentLiabilities = $report['non_current_liabilities'] ?? [];
$currentLiabilities = $report['current_liabilities'] ?? [];
$totalNonCurrentLiabilities = $report['total_non_current_liabilities'] ?? 0;
$totalCurrentLiabilities = $report['total_current_liabilities'] ?? 0;

$totalAssets = $report['total_assets'] ?? 0;
$totalLiabilities = $report['total_liabilities'] ?? 0;
$totalEquityWithIncome = $report['total_equity_with_income'] ?? 0;
$totalLiabilitiesAndEquity = $report['total_liabilities_and_equity'] ?? 0;
$netIncome = $report['net_income'] ?? 0;
$isBalanced = $report['is_balanced'] ?? true;
$layoutMode = $_GET['layout'] ?? 'vertical';
?>

<div class="container-fluid mt-4">
    <!-- Header & Action Bar -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h2 class="mb-1 text-dark fw-bold">
                <i class="fa-light fa-building-columns text-primary me-2"></i> Balance Sheet (উদ্বৃত্তপত্র)
            </h2>
            <p class="text-muted mb-0 fs-13">
                Statement of Financial Position for period <?= date('d M Y', strtotime($filters['date_from'])) ?> to <?= date('d M Y', strtotime($filters['date_to'])) ?>
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
            <form method="GET" action="<?= $base_url ?>/reports/balance-sheet" class="row  align-items-end">
                <div class="col-md-3">
                    <label class="form-label fs-11 fw-semibold mb-1">Layout Format</label>
                    <select name="layout" class="form-select form-select-sm fw-bold text-primary" onchange="this.form.submit()">
                        <option value="vertical" <?= $layoutMode === 'vertical' ? 'selected' : '' ?>>📊 Vertical Statement Format (Standard)</option>
                        <option value="horizontal" <?= $layoutMode === 'horizontal' ? 'selected' : '' ?>>⚖️ Horizontal T-Account Format (Two Column)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fs-11 fw-semibold mb-1">Date From</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_from']) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fs-11 fw-semibold mb-1">Date To</label>
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
                    <a href="<?= $base_url ?>/reports/balance-sheet" class="btn btn-light btn-sm flex-fill text-center">
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
                        <span class="text-muted fs-12 fw-semibold">TOTAL ASSETS</span>
                        <h3 class="mb-0 fw-bold text-success mt-1">৳ <?= number_format($totalAssets, 2) ?></h3>
                    </div>
                    <div class="avatar avatar-md bg-success text-white rounded-circle">
                        <i class="fa-light fa-wallet fs-18"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-primary-transparent p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted fs-12 fw-semibold">TOTAL LIABILITIES & EQUITY</span>
                        <h3 class="mb-0 fw-bold text-primary mt-1">৳ <?= number_format($totalLiabilitiesAndEquity, 2) ?></h3>
                    </div>
                    <div class="avatar avatar-md bg-primary text-white rounded-circle">
                        <i class="fa-light fa-building-columns fs-18"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm <?= $isBalanced ? 'bg-info-transparent' : 'bg-danger-transparent' ?> p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted fs-12 fw-semibold">EQUATION BALANCE STATE</span>
                        <h3 class="mb-0 fw-bold <?= $isBalanced ? 'text-info' : 'text-danger' ?> mt-1">
                            <?= $isBalanced ? '100% Balanced ✓' : 'Unbalanced ✗' ?>
                        </h3>
                        <small class="text-muted fs-11">Assets = Liabilities + Equity</small>
                    </div>
                    <div class="avatar avatar-md <?= $isBalanced ? 'bg-info' : 'bg-danger' ?> text-white rounded-circle">
                        <i class="fa-light fa-check-double fs-18"></i>
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
                        <h5 class="mb-0 text-primary fw-bold">BALANCE SHEET (T-ACCOUNT FORMAT)</h5>
                        <small class="text-white-50">Period: <?= date('d M Y', strtotime($filters['date_from'])) ?> to <?= date('d M Y', strtotime($filters['date_to'])) ?></small>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="row g-0">
                        <!-- Left Side: Assets -->
                        <div class="col-md-6 border-end">
                            <div class="bg-light p-3 border-bottom text-center">
                                <h6 class="mb-0 fw-bold text-success"><i class="fa-light fa-wallet me-1"></i> ASSETS (PROPERTIES & CASH)</h6>
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
                                        <?php if (empty($assets)): ?>
                                            <tr><td colspan="2" class="text-center text-muted py-4">No asset items recorded</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($assets as $acc): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-light text-dark font-monospace border me-2"><?= htmlspecialchars($acc['account_code']) ?></span>
                                                        <span class="fw-semibold text-dark fs-13"><?= htmlspecialchars($acc['account_name']) ?></span>
                                                    </td>
                                                    <td class="text-end font-monospace text-success fw-bold fs-13">৳ <?= number_format($acc['balance'], 2) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot class="border-top border-dark">
                                        <tr class="fw-bold table-light">
                                            <td>TOTAL ASSETS</td>
                                            <td class="text-end font-monospace text-success fs-14">৳ <?= number_format($totalAssets, 2) ?></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Right Side: Liabilities & Equity -->
                        <div class="col-md-6">
                            <div class="bg-light p-3 border-bottom text-center">
                                <h6 class="mb-0 fw-bold text-primary"><i class="fa-light fa-building-columns me-1"></i> LIABILITIES & EQUITY</h6>
                            </div>
                            <div class="p-3">
                                <h6 class="fw-bold text-muted fs-11 text-uppercase mb-2">1. Liabilities (Payables & Client Deposits)</h6>
                                <table class="table table-hover align-middle mb-3">
                                    <tbody>
                                        <?php if (empty($liabilities)): ?>
                                            <tr><td colspan="2" class="text-center text-muted py-2">No liabilities recorded</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($liabilities as $acc): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-light text-dark font-monospace border me-2"><?= htmlspecialchars($acc['account_code']) ?></span>
                                                        <span class="fw-semibold text-dark fs-13"><?= htmlspecialchars($acc['account_name']) ?></span>
                                                    </td>
                                                    <td class="text-end font-monospace text-dark fw-bold fs-13">৳ <?= number_format($acc['balance'], 2) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>

                                <h6 class="fw-bold text-muted fs-11 text-uppercase mb-2">2. Equity & Retained Earnings</h6>
                                <table class="table table-hover align-middle mb-0">
                                    <tbody>
                                        <?php if (!empty($equity)): ?>
                                            <?php foreach ($equity as $acc): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-light text-dark font-monospace border me-2"><?= htmlspecialchars($acc['account_code']) ?></span>
                                                        <span class="fw-semibold text-dark fs-13"><?= htmlspecialchars($acc['account_name']) ?></span>
                                                    </td>
                                                    <td class="text-end font-monospace text-primary fw-bold fs-13">৳ <?= number_format($acc['balance'], 2) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        <tr>
                                            <td class="fw-semibold text-dark fs-13">
                                                <span class="badge bg-light text-dark font-monospace border me-2">P/L</span>
                                                Current Period Net Income / Retained Earnings
                                            </td>
                                            <td class="text-end font-monospace <?= $netIncome >= 0 ? 'text-success' : 'text-danger' ?> fw-bold fs-13">
                                                ৳ <?= number_format($netIncome, 2) ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="border-top border-dark">
                                        <tr class="fw-bold table-light">
                                            <td>TOTAL LIABILITIES & EQUITY</td>
                                            <td class="text-end font-monospace text-primary fs-14">৳ <?= number_format($totalLiabilitiesAndEquity, 2) ?></td>
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
                        <h5 class="mb-1 text-primary fw-bold">BALANCE SHEET (FINANCIAL POSITION STATEMENT)</h5>
                        <p class="text-muted mb-0 fs-13">
                            For the period: <strong><?= date('d M Y', strtotime($filters['date_from'])) ?></strong> to <strong><?= date('d M Y', strtotime($filters['date_to'])) ?></strong> (GAAP Classification)
                        </p>
                    </div>
                </div>
                <div class="card-body p-4">
                    <!-- Assets Section -->
                    <div class="mb-5">
                        <div class="d-flex justify-content-between align-items-center bg-success-transparent p-3 rounded mb-3">
                            <h5 class="mb-0 fw-bold text-success"><i class="fa-light fa-wallet me-2"></i> 1. ASSETS (সম্পদসমূহ)</h5>
                            <span class="badge bg-success text-white px-3 py-2 font-monospace fs-14">TOTAL ASSETS: ৳ <?= number_format($totalAssets, 2) ?></span>
                        </div>

                        <!-- 1.1 Non-Current Assets -->
                        <div class="mb-4 ps-3 border-start border-3 border-success">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold text-success text-uppercase mb-0"><i class="fa-solid fa-folder me-1"></i> 1.1 Non-Current Assets (স্থায়ী ও দীর্ঘমেয়াদী সম্পদ)</h6>
                                <a href="<?= $base_url ?>/office-assets" class="btn btn-outline-success btn-sm py-0 px-2 fs-11" title="View Physical Asset Register">
                                    <i class="fa-solid fa-boxes-stacked me-1"></i> Asset Register (Physical Assets)
                                </a>
                            </div>
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
                                        <?php if (empty($nonCurrentAssets)): ?>
                                            <tr><td colspan="3" class="text-center text-muted py-2">No non-current asset balances.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($nonCurrentAssets as $acc): ?>
                                                <tr>
                                                    <td><span class="badge bg-light text-dark font-monospace border px-2 py-1 fs-12 fw-bold"><?= htmlspecialchars($acc['account_code']) ?></span></td>
                                                    <td class="fw-semibold text-dark fs-13">
                                                        <?= htmlspecialchars($acc['account_name']) ?>
                                                        <?php if (str_starts_with((string)$acc['account_code'], '10')): ?>
                                                            <a href="<?= $base_url ?>/office-assets" class="badge bg-light text-primary border ms-1 fs-11" title="Tracked in Office Asset Register">
                                                                <i class="fa-solid fa-boxes-stacked me-1"></i> Assets
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-end font-monospace text-success fw-bold fs-13">৳ <?= number_format($acc['balance'], 2) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="fw-bold table-light">
                                            <td colspan="2" class="text-end">Subtotal Non-Current Assets:</td>
                                            <td class="text-end font-monospace text-success">৳ <?= number_format($totalNonCurrentAssets, 2) ?></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- 1.2 Current Assets -->
                        <div class="mb-4 ps-3 border-start border-3 border-success">
                            <h6 class="fw-bold text-success text-uppercase mb-2"><i class="fa-solid fa-folder-open me-1"></i> 1.2 Current Assets (চলতি সম্পদ)</h6>
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
                                        <?php if (empty($currentAssets)): ?>
                                            <tr><td colspan="3" class="text-center text-muted py-2">No current asset balances.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($currentAssets as $acc): ?>
                                                <tr>
                                                    <td><span class="badge bg-light text-dark font-monospace border px-2 py-1 fs-12 fw-bold"><?= htmlspecialchars($acc['account_code']) ?></span></td>
                                                    <td class="fw-semibold text-dark fs-13"><?= htmlspecialchars($acc['account_name']) ?></td>
                                                    <td class="text-end font-monospace text-success fw-bold fs-13">৳ <?= number_format($acc['balance'], 2) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="fw-bold table-light">
                                            <td colspan="2" class="text-end">Subtotal Current Assets:</td>
                                            <td class="text-end font-monospace text-success">৳ <?= number_format($totalCurrentAssets, 2) ?></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Liabilities Section -->
                    <div class="mb-5">
                        <div class="d-flex justify-content-between align-items-center bg-warning-transparent p-3 rounded mb-3">
                            <h5 class="mb-0 fw-bold text-dark"><i class="fa-light fa-building-columns me-2"></i> 2. LIABILITIES (দায়সমূহ)</h5>
                            <span class="badge bg-warning text-dark px-3 py-2 font-monospace fs-14">TOTAL LIABILITIES: ৳ <?= number_format($totalLiabilities, 2) ?></span>
                        </div>

                        <!-- 2.1 Non-Current Liabilities -->
                        <div class="mb-4 ps-3 border-start border-3 border-warning">
                            <h6 class="fw-bold text-dark text-uppercase mb-2"><i class="fa-solid fa-folder me-1"></i> 2.1 Non-Current Liabilities (দীর্ঘমেয়াদী দায়)</h6>
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
                                        <?php if (empty($nonCurrentLiabilities)): ?>
                                            <tr><td colspan="3" class="text-center text-muted py-2">No non-current liability balances.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($nonCurrentLiabilities as $acc): ?>
                                                <tr>
                                                    <td><span class="badge bg-light text-dark font-monospace border px-2 py-1 fs-12 fw-bold"><?= htmlspecialchars($acc['account_code']) ?></span></td>
                                                    <td class="fw-semibold text-dark fs-13"><?= htmlspecialchars($acc['account_name']) ?></td>
                                                    <td class="text-end font-monospace text-dark fw-bold fs-13">৳ <?= number_format($acc['balance'], 2) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="fw-bold table-light">
                                            <td colspan="2" class="text-end">Subtotal Non-Current Liabilities:</td>
                                            <td class="text-end font-monospace text-dark">৳ <?= number_format($totalNonCurrentLiabilities, 2) ?></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- 2.2 Current Liabilities -->
                        <div class="mb-4 ps-3 border-start border-3 border-warning">
                            <h6 class="fw-bold text-dark text-uppercase mb-2"><i class="fa-solid fa-folder-open me-1"></i> 2.2 Current Liabilities (চলতি দায়)</h6>
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
                                        <?php if (empty($currentLiabilities)): ?>
                                            <tr><td colspan="3" class="text-center text-muted py-2">No current liability balances.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($currentLiabilities as $acc): ?>
                                                <tr>
                                                    <td><span class="badge bg-light text-dark font-monospace border px-2 py-1 fs-12 fw-bold"><?= htmlspecialchars($acc['account_code']) ?></span></td>
                                                    <td class="fw-semibold text-dark fs-13"><?= htmlspecialchars($acc['account_name']) ?></td>
                                                    <td class="text-end font-monospace text-dark fw-bold fs-13">৳ <?= number_format($acc['balance'], 2) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="fw-bold table-light">
                                            <td colspan="2" class="text-end">Subtotal Current Liabilities:</td>
                                            <td class="text-end font-monospace text-dark">৳ <?= number_format($totalCurrentLiabilities, 2) ?></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Equity Section -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center bg-primary-transparent p-3 rounded mb-3">
                            <h5 class="mb-0 fw-bold text-primary"><i class="fa-light fa-chart-pie me-2"></i> 3. CAPITAL & EQUITY (মূলধন ও সংরক্ষিত আয়)</h5>
                            <span class="badge bg-primary text-white px-3 py-2 font-monospace fs-14">৳ <?= number_format($totalEquityWithIncome, 2) ?></span>
                        </div>
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
                                    <?php if (!empty($equity)): ?>
                                        <?php foreach ($equity as $acc): ?>
                                            <tr>
                                                <td><span class="badge bg-light text-dark font-monospace border px-2 py-1 fs-12 fw-bold"><?= htmlspecialchars($acc['account_code']) ?></span></td>
                                                <td class="fw-semibold text-dark fs-13"><?= htmlspecialchars($acc['account_name']) ?></td>
                                                <td class="text-end font-monospace text-primary fw-bold fs-13">৳ <?= number_format($acc['balance'], 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <tr>
                                        <td><span class="badge bg-light text-dark font-monospace border px-2 py-1 fs-12 fw-bold">P/L</span></td>
                                        <td class="fw-semibold text-dark fs-13">Current Period Net Income / Retained Earnings</td>
                                        <td class="text-end font-monospace <?= $netIncome >= 0 ? 'text-success' : 'text-danger' ?> fw-bold fs-13">৳ <?= number_format($netIncome, 2) ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Grand Total Balance Equation Box -->
                    <div class="p-4 rounded border <?= $isBalanced ? 'bg-primary-transparent border-primary' : 'bg-danger-transparent border-danger' ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1 fw-bold text-primary">
                                    <i class="fa-light fa-check-double text-primary me-2"></i>
                                    TOTAL LIABILITIES & EQUITY
                                </h5>
                                <small class="text-muted">Equation Balance Check: Assets (৳ <?= number_format($totalAssets, 2) ?>) = Liabilities & Equity (৳ <?= number_format($totalLiabilitiesAndEquity, 2) ?>)</small>
                            </div>
                            <h3 class="mb-0 font-monospace fw-bold text-primary">
                                ৳ <?= number_format($totalLiabilitiesAndEquity, 2) ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

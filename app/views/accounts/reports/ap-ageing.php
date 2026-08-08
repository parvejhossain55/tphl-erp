<?php 
// Layout handled by Controller via master.php
$summary = $report['summary'] ?? [];
$parties = $report['parties'] ?? [];
?>

<div class="container-fluid mt-4">
    <!-- Header & Action Bar -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-7">
            <h2 class="mb-1 text-dark fw-bold">
                <i class="fa-light fa-hourglass-clock text-warning me-2"></i> Accounts Payable (AP) Ageing Analysis
            </h2>
            <p class="text-muted mb-0 fs-13">
                পাওনাদার জ্যেষ্ঠতা বিশ্লেষণ — Vendor & Contractor Outstanding Payable Ageing Brackets as of <?= date('d M Y', strtotime($filters['as_of_date'])) ?>
            </p>
        </div>
        <div class="col-md-5 text-end">
            <button type="button" onclick="window.print()" class="btn btn-outline-secondary me-2">
                <i class="fa-light fa-print me-1"></i> Print Statement
            </button>
            <a href="<?= $base_url ?>/financial-dashboard" class="btn btn-outline-primary">
                <i class="fa-light fa-chart-line me-1"></i> Financial Dashboard
            </a>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary text-white p-3 rounded-3">
                <span class="fs-12 text-white-50 text-uppercase fw-bold">Total Accounts Payable</span>
                <h3 class="fw-bold mb-0 text-white mt-1">৳ <?= number_format($summary['total_payable'] ?? 0, 2) ?></h3>
                <small class="text-white-50 fs-11">Total Liability Balance</small>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm bg-success text-white p-3 rounded-3">
                <span class="fs-11 text-white-50 text-uppercase fw-bold">0-30 Days (Current)</span>
                <h4 class="fw-bold mb-0 text-white mt-1">৳ <?= number_format($summary['days_0_30'] ?? 0, 2) ?></h4>
                <small class="text-white-50 fs-11">Normal Terms</small>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm bg-info text-white p-3 rounded-3">
                <span class="fs-11 text-white-50 text-uppercase fw-bold">31-60 Days (Due Soon)</span>
                <h4 class="fw-bold mb-0 text-white mt-1">৳ <?= number_format($summary['days_31_60'] ?? 0, 2) ?></h4>
                <small class="text-white-50 fs-11">Approaching Due</small>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm bg-warning text-dark p-3 rounded-3">
                <span class="fs-11 text-dark-50 text-uppercase fw-bold">61-90 Days (Overdue)</span>
                <h4 class="fw-bold mb-0 text-dark mt-1">৳ <?= number_format($summary['days_61_90'] ?? 0, 2) ?></h4>
                <small class="text-dark-50 fs-11">Attention Required</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-danger text-white p-3 rounded-3">
                <span class="fs-11 text-white-50 text-uppercase fw-bold">90+ Days (Critical Overdue)</span>
                <h4 class="fw-bold mb-0 text-white mt-1">৳ <?= number_format($summary['days_90_plus'] ?? 0, 2) ?></h4>
                <small class="text-white-50 fs-11">Immediate Settlement Needed</small>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body py-3">
            <form method="GET" action="<?= $base_url ?>/reports/ap-ageing" class="row align-items-end">
                <div class="col-md-4">
                    <label class="form-label fs-11 fw-semibold mb-1">As of Date</label>
                    <input type="date" name="as_of_date" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['as_of_date']) ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label fs-11 fw-semibold mb-1">Filter by Project</label>
                    <select name="project_id" class="form-select form-select-sm">
                        <option value="">All Projects / Head Office</option>
                        <?php foreach ($projects as $proj): ?>
                            <option value="<?= $proj['id'] ?>" <?= ($filters['project_id'] == $proj['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($proj['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 text-end">
                    <button type="submit" class="btn btn-primary btn-sm px-4 me-1">
                        <i class="fa-light fa-filter me-1"></i> Apply Filter
                    </button>
                    <a href="<?= $base_url ?>/reports/ap-ageing" class="btn btn-light btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- AP Ageing Table -->
    <div class="card shadow-sm border-0 mb-5">
        <div class="card-header bg-white py-3 border-0">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="fa-light fa-users text-primary me-2"></i> Sub-Ledger Party Ageing Breakdown
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark fs-12">
                        <tr>
                            <th>#</th>
                            <th>Party / Vendor / Contractor</th>
                            <th>Account Code</th>
                            <th class="text-end">0-30 Days</th>
                            <th class="text-end">31-60 Days</th>
                            <th class="text-end">61-90 Days</th>
                            <th class="text-end">90+ Days</th>
                            <th class="text-end">Total Payable (BDT)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($parties)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fa-light fa-circle-info me-1 fs-16"></i> No outstanding accounts payable records found as of this date.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $sl = 1; 
                            foreach ($parties as $p): 
                            ?>
                                <tr>
                                    <td><?= $sl++ ?></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($p['party_name']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($p['account_code']) ?></span> <?= htmlspecialchars($p['account_name']) ?></td>
                                    <td class="text-end text-success font-monospace">৳ <?= number_format($p['days_0_30'], 2) ?></td>
                                    <td class="text-end text-info font-monospace">৳ <?= number_format($p['days_31_60'], 2) ?></td>
                                    <td class="text-end text-warning font-monospace">৳ <?= number_format($p['days_61_90'], 2) ?></td>
                                    <td class="text-end text-danger font-monospace fw-bold">৳ <?= number_format($p['days_90_plus'], 2) ?></td>
                                    <td class="text-end fw-bold text-dark font-monospace">৳ <?= number_format($p['total'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="3" class="text-end">TOTAL OUTSTANDING AP:</td>
                            <td class="text-end text-success font-monospace">৳ <?= number_format($summary['days_0_30'] ?? 0, 2) ?></td>
                            <td class="text-end text-info font-monospace">৳ <?= number_format($summary['days_31_60'] ?? 0, 2) ?></td>
                            <td class="text-end text-warning font-monospace">৳ <?= number_format($summary['days_61_90'] ?? 0, 2) ?></td>
                            <td class="text-end text-danger font-monospace">৳ <?= number_format($summary['days_90_plus'] ?? 0, 2) ?></td>
                            <td class="text-end text-primary font-monospace fs-15">৳ <?= number_format($summary['total_payable'] ?? 0, 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

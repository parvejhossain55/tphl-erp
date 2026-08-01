<?php 
// Layout handled by Controller via master.php
$accounts = $report['accounts'] ?? [];
$totalDebit = $report['total_debit'] ?? 0;
$totalCredit = $report['total_credit'] ?? 0;
$isBalanced = $report['is_balanced'] ?? true;
$viewType = $viewType ?? 'summary';
?>

<div class="container-fluid mt-4">
    <!-- Header & Action Bar -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h2 class="mb-1 text-dark fw-bold">
                <i class="fa-light fa-scale-balanced text-primary me-2"></i> Trial Balance (রেওয়ামিল)
            </h2>
            <p class="text-muted mb-0 fs-13">
                Statement of Double-Entry General & Sub-Ledger Balances as of <?= date('d M Y', strtotime($filters['as_of_date'])) ?>
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

    <!-- Filter Bar & View Toggle -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body py-3">
            <form method="GET" action="<?= $base_url ?>/reports/trial-balance" class="row  align-items-end">
                <div class="col-md-3">
                    <label class="form-label fs-11 fw-semibold mb-1">Report View Format</label>
                    <select name="view_type" class="form-select form-select-sm fw-bold text-primary" onchange="this.form.submit()">
                        <option value="summary" <?= $viewType === 'summary' ? 'selected' : '' ?>>📊 Summary View (Control Accounts)</option>
                        <option value="detailed" <?= $viewType === 'detailed' ? 'selected' : '' ?>>📜 Detailed Sub-Ledger View (Party & Client Breakdown)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fs-11 fw-semibold mb-1">As of Date</label>
                    <input type="date" name="as_of_date" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['as_of_date']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fs-11 fw-semibold mb-1">Project</label>
                    <select name="project_id" class="form-select form-select-sm">
                        <option value="">All Projects</option>
                        <?php foreach ($projects as $proj): ?>
                            <option value="<?= $proj['id'] ?>" <?= ($filters['project_id'] == $proj['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($proj['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill">
                        <i class="fa-solid fa-filter me-1"></i> Filter
                    </button>
                    <a href="<?= $base_url ?>/reports/trial-balance" class="btn btn-light btn-sm flex-fill text-center">
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
                        <span class="text-muted fs-12 fw-semibold">GRAND TOTAL DEBIT</span>
                        <h3 class="mb-0 fw-bold text-success mt-1">৳ <?= number_format($totalDebit, 2) ?></h3>
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
                        <span class="text-muted fs-12 fw-semibold">GRAND TOTAL CREDIT</span>
                        <h3 class="mb-0 fw-bold text-danger mt-1">৳ <?= number_format($totalCredit, 2) ?></h3>
                    </div>
                    <div class="avatar avatar-md bg-danger text-white rounded-circle">
                        <i class="fa-light fa-arrow-up-right fs-18"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm <?= $isBalanced ? 'bg-primary-transparent' : 'bg-warning-transparent' ?> p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted fs-12 fw-semibold">EQUATION BALANCE STATE</span>
                        <h3 class="mb-0 fw-bold <?= $isBalanced ? 'text-primary' : 'text-warning' ?> mt-1">
                            <?= $isBalanced ? '100% Balanced ✓' : 'Unbalanced ✗' ?>
                        </h3>
                        <small class="text-muted fs-11">Debit Total = Credit Total</small>
                    </div>
                    <div class="avatar avatar-md <?= $isBalanced ? 'bg-primary' : 'bg-warning' ?> text-white rounded-circle">
                        <i class="fa-light fa-check-double fs-18"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Trial Balance Table -->
    <div class="card border-0 shadow-sm mb-5">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-dark">
                <i class="fa-light fa-list-ol me-2 text-primary"></i> 
                <?= $viewType === 'detailed' ? 'Detailed Sub-Ledger Trial Balance (Party & Client Breakdown)' : 'General Ledger Accounts Balances (Summary View)' ?>
            </h5>
            <span class="badge bg-light text-dark border px-3 py-2 fs-12">Total Rows: <?= count($accounts) ?></span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="50" class="text-center">#</th>
                            <th width="130">Account Code</th>
                            <th>Account Name</th>
                            <?php if ($viewType === 'detailed'): ?>
                                <th width="220">Client / Vendor / Sub-Ledger Party</th>
                            <?php endif; ?>
                            <th width="140">Account Type</th>
                            <th class="text-end" width="180">Debit Balance (BDT)</th>
                            <th class="text-end" width="180">Credit Balance (BDT)</th>
                            <th width="70" class="text-center">Ledger</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($accounts)): ?>
                            <tr>
                                <td colspan="<?= $viewType === 'detailed' ? 8 : 7 ?>" class="text-center py-5 text-muted fs-13">
                                    <i class="fa-light fa-inbox fs-2 d-block mb-2 text-secondary"></i>
                                    No ledger activity recorded as of this date.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($accounts as $idx => $acc): ?>
                                <tr>
                                    <td class="text-center text-muted"><?= $idx + 1 ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark font-monospace border px-2 py-1 fs-12 fw-bold">
                                            <?= htmlspecialchars($acc['account_code']) ?>
                                        </span>
                                    </td>
                                    <td class="fw-semibold text-dark fs-13">
                                        <?= htmlspecialchars($acc['account_name']) ?>
                                    </td>
                                    <?php if ($viewType === 'detailed'): ?>
                                        <td>
                                            <?php if (!empty($acc['party_name']) && $acc['party_name'] !== 'General / Direct Entry'): ?>
                                                <span class="badge bg-info-subtle text-info border border-info px-2 py-1 fs-12 fw-semibold">
                                                    <i class="fa-light fa-user me-1"></i><?= htmlspecialchars($acc['party_name']) ?>
                                                    <?= !empty($acc['party_type']) ? ' (' . ucfirst($acc['party_type']) . ')' : '' ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border px-2 py-1 fs-12">
                                                    <i class="fa-light fa-folder me-1"></i>General Entry
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 fs-12">
                                            <?= htmlspecialchars($acc['account_type']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end font-monospace text-success fw-bold fs-13">
                                        <?= $acc['total_debit'] > 0 ? '৳ ' . number_format($acc['total_debit'], 2) : '—' ?>
                                    </td>
                                    <td class="text-end font-monospace text-danger fw-bold fs-13">
                                        <?= $acc['total_credit'] > 0 ? '৳ ' . number_format($acc['total_credit'], 2) : '—' ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= $base_url ?>/accounts/ledger/<?= $acc['account_id'] ?><?= !empty($acc['party_id']) ? '?party_id=' . $acc['party_id'] : '' ?>" class="btn btn-sm btn-outline-info btn-icon" title="View Sub-Ledger Statement">
                                            <i class="fa-light fa-book-open"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-dark">
                        <tr>
                            <td colspan="<?= $viewType === 'detailed' ? 5 : 4 ?>" class="text-end fw-bold fs-14">GRAND TOTAL:</td>
                            <td class="text-end font-monospace fw-bold fs-14 text-success">৳ <?= number_format($totalDebit, 2) ?></td>
                            <td class="text-end font-monospace fw-bold fs-14 text-warning">৳ <?= number_format($totalCredit, 2) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

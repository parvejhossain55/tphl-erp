<?php 
// Layout handled by Controller via master.php
$transactions = $ledgerData['transactions'] ?? [];
$totalDebit = $ledgerData['total_debit'] ?? 0;
$totalCredit = $ledgerData['total_credit'] ?? 0;
$netBalance = $ledgerData['net_balance'] ?? 0;
$isNormalDebit = $ledgerData['is_normal_debit'] ?? true;
?>

<div class="container-fluid mt-4">
    <!-- Header & Action Bar -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h2 class="mb-1 text-dark fw-bold">
                <i class="fa-light fa-book-bookmark text-primary me-2"></i> General & Sub-Ledger Statement
            </h2>
            <p class="text-muted mb-0 fs-13">
                Account: <span class="badge bg-light text-dark font-monospace border px-2 py-1 fs-6"><?= htmlspecialchars($account['account_code']) ?></span>
                <strong class="text-dark fs-6 ms-2"><?= htmlspecialchars($account['account_name']) ?></strong>
                <span class="badge bg-primary text-white ms-2"><?= htmlspecialchars($account['account_type']) ?></span>
            </p>
        </div>
        <div class="col-md-6 text-end">
            <button type="button" onclick="window.print()" class="btn btn-outline-secondary me-2">
                <i class="fa-light fa-print me-1"></i> Print Statement
            </button>
            <a href="<?= $base_url ?>/accounts/coa" class="btn btn-outline-primary">
                <i class="fa-light fa-sitemap me-1"></i> Chart of Accounts
            </a>
        </div>
    </div>

    <!-- Account Selector Bar -->
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card shadow-sm border-0 bg-primary-transparent border-start border-4 border-primary">
                <div class="card-body py-3">
                    <div class="row align-items-center g-2">
                        <div class="col-md-3 col-lg-2">
                            <label class="form-label fs-13 fw-bold text-primary mb-0">
                                <i class="fa-light fa-list-tree me-1 align-middle"></i> Select Account / COA:
                            </label>
                        </div>
                        <div class="col-md-9 col-lg-10">
                            <select id="accountSelect" class="form-select form-select-sm" onchange="switchAccount(this.value)">
                                <option value="">-- Search & Select Account / COA --</option>
                                <?php 
                                $activeWithTxns = array_filter($allAccounts ?? [], function($a) { return ($a['txn_count'] ?? 0) > 0; });
                                $otherAccounts  = array_filter($allAccounts ?? [], function($a) { return ($a['txn_count'] ?? 0) == 0; });
                                ?>
                                <?php if (!empty($activeWithTxns)): ?>
                                    <optgroup label="✅ Active Ledgers with Journal Entries (সক্রিয় লেনদেনকৃত লেজারসমূহ)">
                                        <?php foreach ($activeWithTxns as $acc): ?>
                                            <option value="<?= $acc['id'] ?>" <?= ($account['id'] == $acc['id']) ? 'selected' : '' ?>>
                                                [<?= htmlspecialchars($acc['account_code']) ?>] <?= htmlspecialchars($acc['account_name']) ?> (<?= htmlspecialchars($acc['account_type']) ?>) — <?= $acc['txn_count'] ?> Entries
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endif; ?>

                                <?php if (!empty($otherAccounts)): ?>
                                    <optgroup label="📁 Other Chart of Accounts (অন্যান্য ডাবল এন্ট্রি অ্যাকাউন্টসমূহ)">
                                        <?php foreach ($otherAccounts as $acc): ?>
                                            <option value="<?= $acc['id'] ?>" <?= ($account['id'] == $acc['id']) ? 'selected' : '' ?>>
                                                [<?= htmlspecialchars($acc['account_code']) ?>] <?= htmlspecialchars($acc['account_name']) ?> (<?= htmlspecialchars($acc['account_type']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Bar with Party / Vendor Dropdown -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body py-3">
            <form method="GET" action="<?= $base_url ?>/accounts/ledger/<?= $account['id'] ?>" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label fs-11 fw-semibold mb-1">Start Date</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($startDate) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fs-11 fw-semibold mb-1">End Date</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($endDate) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fs-11 fw-semibold mb-1">Vendor / Sub-Ledger Party</label>
                    <select name="party_id" class="form-select form-select-sm select2-party">
                        <option value="">-- All Vendors & Sub-Ledgers --</option>
                        <?php 
                        $vendorParties = array_filter($parties ?? [], function($p) { return ($p['party_type'] ?? '') === 'vendor'; });
                        $clientParties = array_filter($parties ?? [], function($p) { return ($p['party_type'] ?? '') === 'client'; });
                        $otherParties  = array_filter($parties ?? [], function($p) { return !in_array(($p['party_type'] ?? ''), ['vendor', 'client']); });
                        ?>
                        <?php if (!empty($vendorParties)): ?>
                            <optgroup label="🏢 Vendors (ভেন্ডরসমূহ)">
                                <?php foreach ($vendorParties as $pty): ?>
                                    <option value="<?= $pty['party_id'] ?>" <?= ($partyId == $pty['party_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($pty['party_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                        <?php if (!empty($clientParties)): ?>
                            <optgroup label="👤 Clients / Customers (গ্রাহকসমূহ)">
                                <?php foreach ($clientParties as $pty): ?>
                                    <option value="<?= $pty['party_id'] ?>" <?= ($partyId == $pty['party_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($pty['party_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                        <?php if (!empty($otherParties)): ?>
                            <optgroup label="👥 Other Parties">
                                <?php foreach ($otherParties as $pty): ?>
                                    <option value="<?= $pty['party_id'] ?>" <?= ($partyId == $pty['party_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($pty['party_name']) ?> (<?= ucfirst($pty['party_type'] ?? 'Party') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fs-11 fw-semibold mb-1">Project</label>
                    <select name="project_id" class="form-select form-select-sm">
                        <option value="">All Projects</option>
                        <?php foreach ($projects as $proj): ?>
                            <option value="<?= $proj['id'] ?>" <?= ($projectId == $proj['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($proj['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search Vendor / Narration..." value="<?= htmlspecialchars($searchQuery ?? '') ?>">
                    <button type="submit" class="btn btn-primary btn-sm px-3">
                        <i class="fa-solid fa-filter me-1"></i> Filter
                    </button>
                    <a href="<?= $base_url ?>/accounts/ledger/<?= $account['id'] ?>" class="btn btn-light btn-sm px-2 text-center" title="Reset Filters">
                        <i class="fa-solid fa-rotate"></i>
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
                        <span class="text-muted fs-12 fw-semibold">TOTAL DEBIT</span>
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
                        <span class="text-muted fs-12 fw-semibold">TOTAL CREDIT</span>
                        <h3 class="mb-0 fw-bold text-danger mt-1">৳ <?= number_format($totalCredit, 2) ?></h3>
                    </div>
                    <div class="avatar avatar-md bg-danger text-white rounded-circle">
                        <i class="fa-light fa-arrow-up-right fs-18"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-primary-transparent p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted fs-12 fw-semibold">CLOSING NET BALANCE</span>
                        <h3 class="mb-0 fw-bold text-primary mt-1">৳ <?= number_format(abs($netBalance), 2) ?></h3>
                        <small class="text-muted fs-11">
                            <?= $netBalance >= 0 ? ($isNormalDebit ? '(Debit Balance)' : '(Credit Balance)') : ($isNormalDebit ? '(Credit Balance)' : '(Debit Balance)') ?>
                        </small>
                    </div>
                    <div class="avatar avatar-md bg-primary text-white rounded-circle">
                        <i class="fa-light fa-scale-balanced fs-18"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- General & Sub-Ledger Statement Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-dark"><i class="fa-light fa-list me-2 text-primary"></i> General & Sub-Ledger Transactions Log</h5>
            <span class="badge bg-light text-dark border px-3 py-2 fs-12">Total Entries: <?= count($transactions) ?></span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="40" class="text-center">#</th>
                            <th width="110">Date</th>
                            <th width="130">Voucher No</th>
                            <th width="180">Vendor / Party</th>
                            <th>Particulars / Narration</th>
                            <th width="150">Project</th>
                            <th class="text-end" width="120">Debit (BDT)</th>
                            <th class="text-end" width="120">Credit (BDT)</th>
                            <th class="text-end" width="140">Running Balance</th>
                            <th width="60" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted fs-13">
                                    <i class="fa-light fa-inbox fs-2 d-block mb-2 text-secondary"></i>
                                    No ledger transactions recorded for this account / vendor in the selected filter.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $idx => $t): ?>
                                <tr>
                                    <td class="text-center text-muted"><?= $idx + 1 ?></td>
                                    <td class="fs-12 fw-semibold"><?= date('d M Y', strtotime($t['transaction_date'])) ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="badge bg-light text-dark border font-monospace px-2 py-1 fw-bold">
                                                <?= htmlspecialchars($t['voucher_no']) ?>
                                            </span>
                                            <?php 
                                                $st = strtolower($t['status'] ?? 'approved'); 
                                                if ($st === 'approved' || $st === 'posted'):
                                            ?>
                                                <span class="badge bg-success-subtle text-success border border-success px-1 py-0 fs-10" title="Approved">
                                                    <i class="fa-solid fa-circle-check me-1"></i>Approved
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning px-1 py-0 fs-10" title="Pending Approval">
                                                    <i class="fa-solid fa-clock me-1"></i>Pending
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($t['party_name'])): ?>
                                            <span class="badge bg-info-subtle text-info border border-info px-2 py-1 fs-12 fw-semibold">
                                                <i class="fa-light fa-building me-1"></i><?= htmlspecialchars($t['party_name']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted fs-12">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($t['opposite_account'])): ?>
                                            <div class="mb-1">
                                                <span class="badge bg-light text-primary border px-2 py-1 fs-12 font-monospace fw-bold">
                                                    <i class="fa-light fa-arrows-repeat me-1 text-primary"></i><?= htmlspecialchars($t['opposite_account']) ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="text-secondary fs-11 ms-1"><?= htmlspecialchars($t['main_narration']) ?></div>
                                    </td>
                                    <td class="fs-12">
                                        <?= !empty($t['project_name']) ? htmlspecialchars($t['project_name']) : '<span class="text-muted">Head Office</span>' ?>
                                    </td>
                                    <td class="text-end font-monospace text-success fw-semibold">
                                        <?= $t['debit'] > 0 ? number_format($t['debit'], 2) : '—' ?>
                                    </td>
                                    <td class="text-end font-monospace text-danger fw-semibold">
                                        <?= $t['credit'] > 0 ? number_format($t['credit'], 2) : '—' ?>
                                    </td>
                                    <td class="text-end font-monospace fw-bold fs-13 <?= $t['running_balance'] >= 0 ? 'text-primary' : 'text-danger' ?>">
                                        ৳ <?= number_format(abs($t['running_balance']), 2) ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= $base_url ?>/general-journal/view/<?= $t['journal_id'] ?>" class="btn btn-sm btn-outline-secondary btn-icon" title="View Journal Entry">
                                            <i class="fa-light fa-eye"></i>
                                        </a>
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

<script>
function switchAccount(accId) {
    if (accId) {
        window.location.href = '<?= $base_url ?>/accounts/ledger/' + accId;
    }
}

if (typeof jQuery !== 'undefined') {
    jQuery(document).ready(function($) {
        if ($.fn.select2) {
            $('#accountSelect').select2({
                placeholder: "-- Search & Select Account / COA --",
                width: '100%'
            }).on('change select2:select', function() {
                var accId = $(this).val();
                if (accId) {
                    window.location.href = '<?= $base_url ?>/accounts/ledger/' + accId;
                }
            });
        }
    });
}
</script>

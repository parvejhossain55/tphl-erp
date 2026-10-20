<?php
// This is the main view file for the central statement
$page_title = "Central Financial Statement";
$page = 'Accounts';
$addPage = false;
$show_breadcrumb = true;

// Get utilities transactions if utilities category is selected
$utilitiesTransactions = [];
if (isset($category) && $category === 'utilities' && isset($statement_data['start_date']) && isset($statement_data['end_date'])) {
    require_once __DIR__ . '/../../models/CentralStatement.php';
    $statementModel = new CentralStatement($GLOBALS['db']);
    $utilitiesTransactions = $statementModel->getUtilitiesTransactions(
        $statement_data['start_date'],
        $statement_data['end_date']
    );
}

// cashout_payment_details passed from controller (grouped by date, only when category === 'cashout')
$cashout_payment_details = $cashout_payment_details ?? [];
$vendor_payment_details = $vendor_payment_details ?? [];
$contractor_bill_details = $contractor_bill_details ?? [];
?>

<style>
    @media print {
        * {
            animation: none !important;
            transition: none !important;
            filter: none !important;
            box-shadow: none !important;
            text-shadow: none !important;
        }
    }

    /* Regular view styles */
    .card__icon span {
        color: var(--clr-theme-primary);
        background: rgba(108, 95, 252, 0.1);
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        text-align: center;
        justify-content: center;
        align-items: center;
        font-size: 16px;
    }

    .card__title {
        font-size: 1.25rem;
    }

    .stat-card {
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border-left: 4px solid;
        margin-bottom: 20px;
    }

    .stat-card.revenue {
        border-left-color: #28a745;
    }

    .stat-card.cash-out {
        border-left-color: #dc3545;
    }

    .stat-card.net-balance {
        border-left-color: #007bff;
    }

    .stat-card .label {
        font-size: 14px;
        color: #6c757d;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }

    .stat-card .value {
        font-size: 28px;
        font-weight: 700;
        color: #212529;
        font-family: 'Courier New', monospace;
    }

    .stat-card.revenue .value {
        color: #28a745;
    }

    .stat-card.cash-out .value {
        color: #dc3545;
    }

    .stat-card.net-balance .value {
        color: #007bff;
    }

    .positive {
        color: #28a745;
    }

    .negative {
        color: #dc3545;
    }

    .filter-group {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: flex-end;
    }

    .filter-item {
        display: flex;
        flex-direction: column;
        gap: 8px;
        min-width: 160px;
    }

    .filter-item-project {
        min-width: 320px !important;
        flex: 1 1 320px !important;
    }

    .filter-item label {
        font-size: 14px;
        font-weight: 600;
        color: #495057;
    }

    .filter-item select,
    .filter-item input {
        padding: 10px 14px;
        border: 2px solid #dee2e6;
        border-radius: 8px;
        font-size: 14px;
        background: white;
        transition: all 0.2s;
        min-width: 150px;
    }

    .filter-item-project select,
    .filter-item-project .select2-container,
    .filter-item-project .select2-container--default .select2-selection--single,
    .filter-item-project .select2-selection {
        min-width: 320px !important;
        width: 100% !important;
        height: 42px !important;
    }
    .select2-dropdown {
        min-width: 320px !important;
        z-index: 999999 !important;
    }

    .filter-item select:focus,
    .filter-item input:focus {
        border-color: var(--clr-theme-primary);
        outline: none;
        box-shadow: 0 0 0 3px rgba(108, 95, 252, 0.1);
    }

    .period-info {
        color: #fff;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 30px;
        border: 1px solid;
    }

    .period-info h3 {
        font-size: 24px;
        font-weight: 700;
    }

    .action-buttons {
        display: flex;
        gap: 10px;
    }

    .btn {
        padding: 10px 20px;
        font-size: 14px;
        font-weight: 600;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .btn-success {
        background: linear-gradient(135deg, #28a745, #1e7e34);
        color: white;
    }

    .btn-secondary {
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
    }

    .btn-danger {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
    }

    .card-filter-group {
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }

    .card-filter-btn {
        padding: 6px 12px;
        font-size: 12px;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        cursor: pointer;
    }

    .card-filter-btn.active {
        background: var(--clr-theme-primary);
        color: white;
        border-color: var(--clr-theme-primary);
    }

    .card-period-filters {
        display: flex;
        gap: 10px;
        margin-top: 10px;
        flex-wrap: wrap;
    }
    .select2-container--default .select2-selection--single {
        height: 38px !important;
        border: 1px solid #ced4da !important;
        border-radius: 4px !important;
        padding: 4px 8px;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 28px !important;
        color: #495057 !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }
</style>

<script>
    if (typeof $ !== 'undefined' && $.fn && $.fn.dataTable) {
        $.fn.dataTable.ext.errMode = 'none';
    }
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof $ !== 'undefined' && $.fn && $.fn.dataTable) {
            $.fn.dataTable.ext.errMode = 'none';
        }
        if (typeof $ !== 'undefined' && $.fn && $.fn.select2) {
            $('#projectId').select2({
                placeholder: "Search Project...",
                allowClear: true,
                width: '100%'
            });
        }
    });
</script>
<div class="row">
    <!-- Filter Controls -->
    <div class="col-xxl-12 ">
        <div class="card__wrapper">
            <div class="card__header d-flex justify-content-between align-items-center mb-3">
                <h5 class="card__title" style="margin-top: -13px;">Filter Statement</h5>
            </div>
            <div class="card__body">
                <form method="GET" action="<?= $GLOBALS['base_url'] ?>/central-statement" id="filterForm">
                    <div class="filter-group">
                        <div class="filter-item">
                            <label>Period Type</label>
                            <select name="period_type" id="periodType" class="form-select" onchange="handlePeriodChange()">
                                <option value="all_time" <?= ($period_type ?? '') === 'all_time' ? 'selected' : '' ?>>All Time</option>
                                <option value="monthly" <?= ($period_type ?? '') === 'monthly' ? 'selected' : '' ?>>Monthly (Daily Breakdown)</option>
                                <option value="6months" <?= ($period_type ?? '') === '6months' ? 'selected' : '' ?>>Last 6 Months</option>
                                <option value="yearly" <?= ($period_type ?? '') === 'yearly' ? 'selected' : '' ?>>Yearly (Monthly Breakdown)</option>
                                <option value="custom" <?= ($period_type ?? '') === 'custom' ? 'selected' : '' ?>>Custom Range</option>
                            </select>
                        </div>

<div class="filter-item" id="yearFilter">
    <label>Year</label>
    <select name="year" id="year" class="form-select">
        <?php
        $currentYear = (int)date('Y');
        $selectedYear = isset($year) ? (int)$year : $currentYear;
        
        // Generate years from current year back to 2020 (or earlier if needed)
        for ($y = $currentYear; $y >= 2020; $y--):
            $isSelected = ($selectedYear == $y) ? 'selected' : '';
        ?>
            <option value="<?= $y ?>" <?= $isSelected ?>>
                <?= $y ?>
            </option>
        <?php endfor; ?>
    </select>
</div>

                        <div class="filter-item" id="monthFilter">
                            <label>Month</label>
                            <select name="month" id="month" class="form-select">
                                <option value="all" <?= ($month ?? '') === 'all' ? 'selected' : '' ?>>All Months (Whole Year)</option>
                                <?php
                                for ($m = 1; $m <= 12; $m++):
                                    $monthName = date('F', mktime(0, 0, 0, $m, 1));
                                ?>
                                    <option value="<?= sprintf('%02d', $m) ?>" <?= ($month ?? '') == sprintf('%02d', $m) ? 'selected' : '' ?>><?= $monthName ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="filter-item" id="startDateFilter" style="display: none;">
                            <label>Start Date</label>
                            <input type="date" name="start_date" id="startDate" class="form-control" value="<?= htmlspecialchars($start_date ?? '') ?>">
                        </div>

                        <div class="filter-item" id="endDateFilter" style="display: none;">
                            <label>End Date</label>
                            <input type="date" name="end_date" id="endDate" class="form-control" value="<?= htmlspecialchars($end_date ?? '') ?>">
                        </div>

                        <div class="filter-item filter-item-project">
                            <label class="fw-bold">Project</label>
                            <select name="project_id" id="projectId" class="form-select" style="min-width: 280px !important; width: 100% !important; height: 42px; font-weight: 500; font-size: 14px;">
                                <option value="">All Projects & Head Office</option>
                                <option value="head_office" <?= (string)($project_id ?? '') === 'head_office' ? 'selected' : '' ?>>Head Office / General</option>
                                <?php if (!empty($projects)): ?>
                                    <?php foreach ($projects as $project): ?>
                                        <option value="<?= $project['id'] ?>" <?= (string)($project_id ?? '') === (string)$project['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($project['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="filter-item">
                            <label>Category</label>
                            <select name="category" id="category" class="form-select">
                                <option value="all" <?= ($category ?? '') === 'all' ? 'selected' : '' ?>>All Categories</option>
                                <optgroup label="Cash-in Categories">
                                    <option value="total_cashin" <?= ($category ?? '') === 'total_cashin' ? 'selected' : '' ?>>Total Cash IN (Combined)</option>
                                    <option value="landshare" <?= ($category ?? '') === 'landshare' ? 'selected' : '' ?>>Landshare Only</option>
                                    <option value="service_fee" <?= ($category ?? '') === 'service_fee' ? 'selected' : '' ?>>Service Fee Only</option>
                                    <option value="emi" <?= ($category ?? '') === 'emi' ? 'selected' : '' ?>>EMI Only</option>
                                    <option value="registration" <?= ($category ?? '') === 'registration' ? 'selected' : '' ?>>Registration Only</option>
                                    <option value="booking_money" <?= ($category ?? '') === 'booking_money' ? 'selected' : '' ?>>Booking Money Only</option>
                                    <option value="management_cashin" <?= ($category ?? '') === 'management_cashin' ? 'selected' : '' ?>>Management Cashin Only</option>
                                    <option value="expense_adjustment_cashin" <?= ($category ?? '') === 'expense_adjustment_cashin' ? 'selected' : '' ?>>Expense Adjustment Returns</option>
                                    <option value="procurement_returns_cashin" <?= ($category ?? '') === 'procurement_returns_cashin' ? 'selected' : '' ?>>Procurement Returns</option>
                                </optgroup>
                                <optgroup label="Bank Categories">
                                    <option value="bank_cashin" <?= ($category ?? '') === 'bank_cashin' ? 'selected' : '' ?>>Bank Cash In</option>
                                    <option value="bank_cashout" <?= ($category ?? '') === 'bank_cashout' ? 'selected' : '' ?>>Bank Cash Out</option>
                                </optgroup>
                                <optgroup label="Expense Categories">
                                    <option value="cashout" <?= ($category ?? '') === 'cashout' ? 'selected' : '' ?>>Cash Out</option>
                                    <option value="utilities" <?= ($category ?? '') === 'utilities' ? 'selected' : '' ?>>Utilities</option>
                                    <option value="vendor_payment" <?= ($category ?? '') === 'vendor_payment' ? 'selected' : '' ?>>Vendor Payments</option>
                                    <option value="contractor_bill" <?= ($category ?? '') === 'contractor_bill' ? 'selected' : '' ?>>Contractor Bills</option>
                                    <option value="office_procurement_assets" <?= ($category ?? '') === 'office_procurement_assets' ? 'selected' : '' ?>>Office Procurement Assets</option>
                                    <option value="assets_repairing" <?= ($category ?? '') === 'assets_repairing' ? 'selected' : '' ?>>Assets Repairing</option>
                                    <option value="fund_requisitions" <?= ($category ?? '') === 'fund_requisitions' ? 'selected' : '' ?>>Fund Requisitions</option>
                                </optgroup>
                            </select>
                        </div>

                        <div class="filter-item">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-light fa-search me-2"></i> Search
                            </button>
                        </div>
                        <!-- Action Buttons -->
                        <div class="filter-item">
                            <label>&nbsp;</label>
                            <div class="action-buttons">
                                <button type="button" class="btn btn-success" onclick="downloadPDF()">
                                    <i class="fa-light fa-download me-2"></i> Download PDF
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="printStatement()">
                                    <i class="fa-light fa-print me-2"></i> Print Statement
                                </button>
                                <button type="button" class="btn btn-danger" onclick="exportCSV()">
                                    <i class="fa-light fa-file-csv me-2"></i> Export CSV
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php
// Compute summary totals from $statement_data
$summary_stats = $statement_data['summary'] ?? [];

$calc_booking_money = (float)($summary_stats['total_booking_money'] ?? 0);
$calc_registration_fee = (float)($summary_stats['total_registration_payments'] ?? $summary_stats['total_registration'] ?? 0);
$calc_landshare_money = (float)($summary_stats['total_landshare_payments'] ?? $summary_stats['total_landshare'] ?? 0);
$calc_cashin = (float)($summary_stats['total_cashin'] ?? 0);
$calc_cashout = (float)($summary_stats['total_cash_out'] ?? 0);

// Fallback: Sum directly from displayed rows if summary totals are 0
if (isset($statement_data['daily_data']) && is_array($statement_data['daily_data'])) {
    $r_booking = 0; $r_reg = 0; $r_land = 0; $r_cashin = 0; $r_cashout = 0;
    foreach ($statement_data['daily_data'] as $r) {
        $r_booking += (float)($r['booking_money'] ?? 0);
        $r_reg += (float)($r['registration'] ?? $r['registration_payments'] ?? 0);
        $r_land += (float)($r['landshare_payments'] ?? 0);
        $r_cashin += (float)($r['total_cashin'] ?? 0);
        $r_cashout += (float)($r['cash_out'] ?? 0);
    }
    if ($calc_booking_money == 0) $calc_booking_money = $r_booking;
    if ($calc_registration_fee == 0) $calc_registration_fee = $r_reg;
    if ($calc_landshare_money == 0) $calc_landshare_money = $r_land;
    if ($calc_cashin == 0) $calc_cashin = $r_cashin;
    if ($calc_cashout == 0) $calc_cashout = $r_cashout;
} elseif (isset($statement_data['monthly_data']) && is_array($statement_data['monthly_data'])) {
    $r_booking = 0; $r_reg = 0; $r_land = 0; $r_cashin = 0; $r_cashout = 0;
    foreach ($statement_data['monthly_data'] as $r) {
        $r_booking += (float)($r['booking_money'] ?? 0);
        $r_reg += (float)($r['registration_payments'] ?? $r['registration'] ?? 0);
        $r_land += (float)($r['landshare_payments'] ?? 0);
        $r_cashin += (float)($r['total_cashin'] ?? 0);
        $r_cashout += (float)($r['cash_out'] ?? 0);
    }
    if ($calc_booking_money == 0) $calc_booking_money = $r_booking;
    if ($calc_registration_fee == 0) $calc_registration_fee = $r_reg;
    if ($calc_landshare_money == 0) $calc_landshare_money = $r_land;
    if ($calc_cashin == 0) $calc_cashin = $r_cashin;
    if ($calc_cashout == 0) $calc_cashout = $r_cashout;
}

// User Formula: Land Share Value = Booking Money + Registration Fee
if ($calc_landshare_money == 0 && ($calc_booking_money > 0 || $calc_registration_fee > 0)) {
    $calc_landshare_money = $calc_booking_money + $calc_registration_fee;
}
?>

<!-- Summary cards section (5 Cards) -->
<div class="row g-3 mt-1 mb-2">
    <!-- Card 1: Total Cash IN -->
    <div class="col-12 col-sm-6 col-md-4 col-xl">
        <div class="card__wrapper stat-card cash-in h-100 p-3">
            <div class="d-flex align-items-center gap-2">
                <div class="card__icon">
                    <span><i class="fa-light fa-money-bill-trend-up"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-1 text-muted fs-12">Total Cash IN</h6>
                    <h4 class="card__title fs-6 mb-0 positive">৳<?= number_format($calc_cashin, 2) ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Total Land Share Money -->
    <div class="col-12 col-sm-6 col-md-4 col-xl">
        <div class="card__wrapper stat-card land-share h-100 p-3">
            <div class="d-flex align-items-center gap-2">
                <div class="card__icon">
                    <span><i class="fa-light fa-layer-group"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-1 text-muted fs-12">Total Land Share Money</h6>
                    <h4 class="card__title fs-6 mb-0 positive">৳<?= number_format($calc_landshare_money, 2) ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Total Booking Money -->
    <div class="col-12 col-sm-6 col-md-4 col-xl">
        <div class="card__wrapper stat-card booking-money h-100 p-3">
            <div class="d-flex align-items-center gap-2">
                <div class="card__icon">
                    <span><i class="fa-light fa-handshake"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-1 text-muted fs-12">Total Booking Money</h6>
                    <h4 class="card__title fs-6 mb-0 positive">৳<?= number_format($calc_booking_money, 2) ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Total Registration Fee -->
    <div class="col-12 col-sm-6 col-md-4 col-xl">
        <div class="card__wrapper stat-card reg-fee h-100 p-3">
            <div class="d-flex align-items-center gap-2">
                <div class="card__icon">
                    <span><i class="fa-light fa-file-contract"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-1 text-muted fs-12">Total Registration Fee</h6>
                    <h4 class="card__title fs-6 mb-0 positive">৳<?= number_format($calc_registration_fee, 2) ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 5: Total Cash Out -->
    <div class="col-12 col-sm-6 col-md-4 col-xl">
        <div class="card__wrapper stat-card cash-out h-100 p-3">
            <div class="d-flex align-items-center gap-2">
                <div class="card__icon">
                    <span><i class="fa-light fa-arrow-up-from-line"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-1 text-muted fs-12">Total Cash Out</h6>
                    <h4 class="card__title fs-6 mb-0 negative">৳<?= number_format($calc_cashout, 2) ?></h4>
                </div>
            </div>
        </div>
    </div>
</div>
</div>


    <!-- Statement Data -->
<div class="col-xxl-12 mb-4 mt-4">
    <div class="card__wrapper">
        <div class="card__body">
            <!-- Bank Transactions Table (for bank_cashin / bank_cashout) -->
            <?php if (in_array($category, ['bank_cashin', 'bank_cashout']) && !empty($bank_transactions)): ?>
                <div class="table__wrapper table-responsive">
                    <table class="table mb-20 multiple_tables">
                        <thead>
                            <tr class="table__title table__sort">
                                <th>#</th>
                                <th>Date</th>
                                <th>Bank Name</th>
                                <th>Branch</th>
                                <th>Routing No</th>
                                <th>Account No</th>
                                <?php if ($category === 'bank_cashin'): ?>
                                    <th>Client / Payment Type</th>
                                    <th>Cash In Amount (TK.)</th>
                                <?php else: ?>
                                    <th>Purpose / Paid To</th>
                                    <th>Withdrawal Amount (TK.)</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="table__body">
                            <?php
                            $runningTotal = 0;
                            foreach ($bank_transactions as $idx => $bt):
                                $runningTotal += (float)($bt['amount'] ?? 0);
                            ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td><?= htmlspecialchars($bt['payment_date'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($bt['bank_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($bt['branch_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($bt['routing_number'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($bt['account_number'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($bt['description'] ?? '-') ?></td>
                                    <td class="<?= $category === 'bank_cashin' ? 'positive' : 'negative' ?>">
                                        <strong>৳<?= number_format($bt['amount'] ?? 0, 2) ?></strong>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="font-weight:700; border-top:2px solid #555;">
                                <td colspan="7" class="text-end">Total:</td>
                                <td class="<?= $category === 'bank_cashin' ? 'positive' : 'negative' ?>">
                                    <strong>৳<?= number_format($runningTotal, 2) ?></strong>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

            <?php elseif (in_array($category, ['bank_cashin', 'bank_cashout']) && empty($bank_transactions)): ?>
                <div class="alert alert-info">
                    <i class="fa-light fa-info-circle me-2"></i>
                    No <?= $category === 'bank_cashin' ? 'bank cash-in' : 'bank cash-out' ?> transactions found for the selected period.
                </div>

            <!-- Normal date-aggregate tables for other categories -->
            <?php elseif (isset($statement_data['daily_data'])): ?>
                <div class="table__wrapper table-responsive">
                    <table class="table mb-20 multiple_tables">
                        <thead>
                            <tr class="table__title table__sort">
                                <th>Date</th>
                                <th>Day</th>

                                <?php if ($category === 'all'): ?>
                                    <!-- Show all columns for 'all' category -->
                                    <th>Booking Money (TK.)</th>
                                    <th>Service Fee (TK.)</th>
                                    <th>EMI Payments (TK.)</th>
                                    <th>Landshare Payments (TK.)</th>
                                    <th>Registration (TK.)</th>
                                    <th>Management Cashin (TK.)</th>
                                    <th>Total Cash IN (TK.)</th>
                                    <th>Cash Out (TK.)</th>
                                    <th>Net Amount (TK.)</th>
                                    <th>Running Balance (TK.)</th>
                                <?php elseif ($category === 'total_cashin'): ?>
                                    <!-- For Total Cash IN only -->
                                    <th>Total Cash IN (TK.)</th>
                                    <th>Amount (TK.)</th>
                                <?php elseif ($category === 'emi'): ?>
                                    <!-- For EMI only -->
                                    <th>EMI Payments (TK.)</th>
                                    <th>Amount (TK.)</th>
                                <?php elseif ($category === 'landshare'): ?>
                                    <!-- For landshare only -->
                                    <th>Landshare Payments (TK.)</th>
                                    <th>Amount (TK.)</th>
                                <?php elseif ($category === 'service_fee'): ?>
                                    <!-- For service fee only -->
                                    <th>Service Fee (TK.)</th>
                                    <th>Amount (TK.)</th>
                                <?php elseif ($category === 'registration'): ?>
                                    <!-- For registration only -->
                                    <th>Registration (TK.)</th>
                                    <th>Amount (TK.)</th>
                                <?php elseif ($category === 'booking_money'): ?>
                                    <!-- For booking money only -->
                                    <th>Booking Money (TK.)</th>
                                    <th>Amount (TK.)</th>
                                <?php elseif ($category === 'agreement'): ?>
                                    <!-- For agreement value only -->
                                    <th>Agreement Value (TK.)</th>
                                    <th>Amount (TK.)</th>
                                <?php elseif ($category === 'management_cashin'): ?>
                                    <!-- For management cashin only -->
                                    <th>Management Cashin (TK.)</th>
                                    <th>Amount (TK.)</th>
                                <?php elseif ($category === 'expense_adjustment_cashin'): ?>
                                    <th>Expense Adjustment Returns (TK.)</th>
                                    <th>Amount (TK.)</th>
                                <?php elseif ($category === 'procurement_returns_cashin'): ?>
                                    <th>Procurement Returns (TK.)</th>
                                    <th>Amount (TK.)</th>
                                <?php elseif (in_array($category, ['cashout', 'utilities', 'other', 'office_procurement_assets', 'assets_repairing', 'fund_requisitions'])): ?>
                                    <!-- For cashout/expense categories -->
                                    <th>Cash Out (TK.)</th>
                                    <th>Amount (TK.)</th>
                                    <?php if ($category === 'cashout'): ?>
                                    <th>Source / Purpose</th>
                                    <?php endif; ?>
                                <?php elseif ($category === 'vendor_payment'): ?>
                                    <!-- For vendor payments -->
                                    <th>Vendor Payment (TK.)</th>
                                    <th>Amount (TK.)</th>
                                    <th>Vendor / Details</th>
                                <?php elseif ($category === 'contractor_bill'): ?>
                                    <!-- For contractor bills -->
                                    <th>Contractor Bill (TK.)</th>
                                    <th>Amount (TK.)</th>
                                    <th>Contractor / Details</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="table__body">
                            <?php foreach ($statement_data['daily_data'] as $row):
                                $rowDate = $row['date'] ?? '';
                            ?>
                                <tr>
                                    <td data-order="<?= htmlspecialchars($rowDate) ?>"><?= htmlspecialchars($row['formatted_date'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['day_name'] ?? '') ?></td>

                                    <?php if ($category === 'all'): ?>
                                        <!-- Show all data for 'all' category -->
                                        <td class="positive"><strong>৳<?= number_format($row['booking_money'] ?? 0, 2) ?></strong></td>
                                        <td class="positive"><strong>৳<?= number_format($row['service_fee'] ?? 0, 2) ?></strong></td>
                                        <td class="positive"><strong>৳<?= number_format($row['emi_payments'] ?? 0, 2) ?></strong></td>
                                        <td class="positive"><strong>৳<?= number_format($row['landshare_payments'] ?? 0, 2) ?></strong></td>
                                        <td class="positive"><strong>৳<?= number_format($row['registration'] ?? 0, 2) ?></strong></td>
                                        <td class="positive"><strong>৳<?= number_format($row['management_cashin'] ?? 0, 2) ?></strong></td>
                                        <td class="positive"><strong>৳<?= number_format($row['total_cashin'] ?? 0, 2) ?></strong></td>
                                        <td class="negative"><strong>৳<?= number_format($row['cash_out'] ?? 0, 2) ?></strong></td>
                                        <td class="<?= ($row['net_amount'] ?? 0) >= 0 ? 'positive' : 'negative' ?>">
                                            <strong>৳<?= number_format($row['net_amount'] ?? 0, 2) ?></strong>
                                        </td>
                                        <td class="<?= ($row['running_balance'] ?? 0) >= 0 ? 'positive' : 'negative' ?>">
                                            <strong>৳<?= number_format($row['running_balance'] ?? 0, 2) ?></strong>
                                        </td>
                                        
                                    <?php elseif ($category === 'total_cashin'): ?>
                                        <!-- Show only Total Cash IN data -->
                                        <td class="positive"><strong>৳<?= number_format($row['total_cashin'] ?? 0, 2) ?></strong></td>
                                        <td class="positive"><strong>৳<?= number_format($row['total_cashin'] ?? 0, 2) ?></strong></td>
                                    <?php elseif ($category === 'emi'): ?>
                                        <!-- Show only EMI data -->
                                        <td class="positive"><strong>৳<?= number_format($row['emi_payments'] ?? 0, 2) ?></strong></td>
                                        <td class="positive"><strong>৳<?= number_format($row['emi_payments'] ?? 0, 2) ?></strong></td>
                                    <?php elseif ($category === 'landshare'): ?>
                                        <!-- Show only landshare data -->
                                        <td class="positive"><strong>৳<?= number_format($row['landshare_payments'] ?? 0, 2) ?></strong></td>
                                        <td class="positive"><strong>৳<?= number_format($row['landshare_payments'] ?? 0, 2) ?></strong></td>
                                    <?php elseif ($category === 'service_fee'): ?>
                                        <!-- Show only service fee data -->
                                        <td class="positive"><strong>৳<?= number_format($row['service_fee'] ?? 0, 2) ?></strong></td>
                                        <td class="positive"><strong>৳<?= number_format($row['service_fee'] ?? 0, 2) ?></strong></td>
                                    <?php elseif ($category === 'registration'): ?>
                                        <!-- Show only registration data -->
                                        <td class="positive"><strong>৳<?= number_format($row['registration'] ?? 0, 2) ?></strong></td>
                                        <td class="positive"><strong>৳<?= number_format($row['registration'] ?? 0, 2) ?></strong></td>
                                    <?php elseif ($category === 'booking_money'): ?>
                                        <!-- Show only booking money data -->
                                        <td class="positive"><strong>৳<?= number_format($row['booking_money'] ?? 0, 2) ?></strong></td>
                                        <td class="positive"><strong>৳<?= number_format($row['booking_money'] ?? 0, 2) ?></strong></td>
                                    <?php elseif ($category === 'agreement'): ?>
                                        <!-- Show only agreement data -->
                                        <td class="positive"><strong>৳<?= number_format($row['agreement_value'] ?? 0, 2) ?></strong></td>
                                        <td class="positive"><strong>৳<?= number_format($row['agreement_value'] ?? 0, 2) ?></strong></td>
                                    <?php elseif ($category === 'management_cashin'): ?>
                                        <!-- Show only management cashin data -->
                                        <td class="positive"><strong>৳<?= number_format($row['management_cashin'] ?? 0, 2) ?></strong></td>
                                        <td class="positive"><strong>৳<?= number_format($row['management_cashin'] ?? 0, 2) ?></strong></td>
                                    <?php elseif ($category === 'expense_adjustment_cashin'): ?>
                                        <td class="positive"><strong>৳<?= number_format($row['expense_adjustment_cashin'] ?? 0, 2) ?></strong></td>
                                        <td class="positive"><strong>৳<?= number_format($row['expense_adjustment_cashin'] ?? 0, 2) ?></strong></td>
                                    <?php elseif ($category === 'procurement_returns_cashin'): ?>
                                        <td class="positive"><strong>৳<?= number_format($row['procurement_returns_cashin'] ?? 0, 2) ?></strong></td>
                                        <td class="positive"><strong>৳<?= number_format($row['procurement_returns_cashin'] ?? 0, 2) ?></strong></td>
                                    <?php elseif (in_array($category, ['cashout', 'utilities', 'other', 'office_procurement_assets', 'assets_repairing', 'fund_requisitions'])): ?>
                                        <!-- Show only cashout data -->
                                        <td class="negative"><strong>৳<?= number_format($row['cash_out'] ?? 0, 2) ?></strong></td>
                                        <td class="negative"><strong>৳<?= number_format($row['cash_out'] ?? 0, 2) ?></strong></td>
                                        <?php if ($category === 'cashout'): ?>
                                        <td>
                                            <?php if (!empty($cashout_payment_details[$rowDate])): ?>
                                                <?php foreach ($cashout_payment_details[$rowDate] as $payment): ?>
                                                    <div style="font-size: 12px; margin-bottom: 4px; line-height: 1.6;">
                                                        <span style="color: #555;"><?= htmlspecialchars($payment['purpose_paid_to'] ?? '—') ?></span>
                                                        <?php if (!empty($payment['project_name'])): ?>
                                                            <span style="color: #007bff; font-size: 11px;">(<?= htmlspecialchars($payment['project_name']) ?>)</span>
                                                        <?php endif; ?>
                                                        <?php if (!empty($payment['office'])): ?>
                                                            <span style="color: #888; font-size: 11px;">· <?= htmlspecialchars($payment['office']) ?></span>
                                                        <?php endif; ?>
                                                        <span style="font-size: 11px; background: #eee; padding: 1px 5px; border-radius: 3px; margin-left: 4px;">
                                                            <?= ucfirst($payment['payment_method'] ?? 'cash') ?>
                                                        </span>
                                                        <?php if (!empty($payment['transaction_ref'])): ?>
                                                            <span style="color: #888; font-size: 11px;">· Ref: <?= htmlspecialchars($payment['transaction_ref']) ?></span>
                                                        <?php endif; ?>
                                                        <strong class="negative" style="margin-left: 4px;">৳<?= number_format($payment['amount'] ?? 0, 2) ?></strong>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="text-muted fs-11 fst-italic">No record</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php endif; ?>
                                    <?php elseif ($category === 'vendor_payment'): ?>
                                        <!-- Show vendor payment data -->
                                        <td class="negative"><strong>৳<?= number_format($row['cash_out'] ?? 0, 2) ?></strong></td>
                                        <td class="negative"><strong>৳<?= number_format($row['cash_out'] ?? 0, 2) ?></strong></td>
                                        <td>
                                            <?php if (!empty($vendor_payment_details[$rowDate])): ?>
                                                <?php foreach ($vendor_payment_details[$rowDate] as $payment): ?>
                                                    <div style="font-size: 12px; margin-bottom: 4px; line-height: 1.6;">
                                                        <span style="color: #555;"><?= htmlspecialchars($payment['vendor_name'] ?? '—') ?></span>
                                                        <?php if (!empty($payment['project_name'])): ?>
                                                            <span style="color: #007bff; font-size: 11px;">(<?= htmlspecialchars($payment['project_name']) ?>)</span>
                                                        <?php endif; ?>
                                                        <?php if (!empty($payment['vpr_payment_type'])): ?>
                                                            <span style="color: #888; font-size: 11px;">· <?= htmlspecialchars($payment['vpr_payment_type']) ?></span>
                                                        <?php endif; ?>
                                                        <span style="font-size: 11px; background: #eee; padding: 1px 5px; border-radius: 3px; margin-left: 4px;">
                                                            <?= ucfirst($payment['payment_method'] ?? 'cash') ?>
                                                        </span>
                                                        <?php if (!empty($payment['transaction_ref'])): ?>
                                                            <span style="color: #888; font-size: 11px;">· Ref: <?= htmlspecialchars($payment['transaction_ref']) ?></span>
                                                        <?php endif; ?>
                                                        <strong class="negative" style="margin-left: 4px;">৳<?= number_format($payment['amount'] ?? 0, 2) ?></strong>
                                                    </div>
                                                <?php endforeach; ?>
                                         
                                            <?php endif; ?>
                                        </td>
                                    <?php elseif ($category === 'contractor_bill'): ?>
                                        <!-- Show contractor bill data -->
                                        <td class="negative"><strong>৳<?= number_format($row['cash_out'] ?? 0, 2) ?></strong></td>
                                        <td class="negative"><strong>৳<?= number_format($row['cash_out'] ?? 0, 2) ?></strong></td>
                                        <td>
                                            <?php if (!empty($contractor_bill_details[$rowDate])): ?>
                                                <?php foreach ($contractor_bill_details[$rowDate] as $payment): ?>
                                                    <div style="font-size: 12px; margin-bottom: 4px; line-height: 1.6;">
                                                        <span style="color: #555;"><?= htmlspecialchars($payment['contractor_name'] ?? '—') ?></span>
                                                        <?php if (!empty($payment['project_name'])): ?>
                                                            <span style="color: #007bff; font-size: 11px;">(<?= htmlspecialchars($payment['project_name']) ?>)</span>
                                                        <?php endif; ?>
                                                        <?php if (!empty($payment['bill_type'])): ?>
                                                            <span style="color: #888; font-size: 11px;">· <?= htmlspecialchars($payment['bill_type']) ?></span>
                                                        <?php endif; ?>
                                                        <span style="font-size: 11px; background: #eee; padding: 1px 5px; border-radius: 3px; margin-left: 4px;">
                                                            <?= ucfirst($payment['payment_method'] ?? 'cash') ?>
                                                        </span>
                                                        <?php if (!empty($payment['transaction_ref'])): ?>
                                                            <span style="color: #888; font-size: 11px;">· Ref: <?= htmlspecialchars($payment['transaction_ref']) ?></span>
                                                        <?php endif; ?>
                                                        <strong class="negative" style="margin-left: 4px;">৳<?= number_format($payment['amount'] ?? 0, 2) ?></strong>
                                                    </div>
                                                <?php endforeach; ?>
                                          
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>

                        </tbody>
                    </table>
                </div>

                <!-- For monthly_data (yearly, 6months views) -->
            <?php elseif (isset($statement_data['monthly_data'])): ?>
                <div class="table__wrapper table-responsive">
                    <table class="table mb-20 multiple_tables">
                        <thead>
                            <tr class="table__title table__sort">
                                <th>Month</th>

                                <?php if ($category === 'all'): ?>
                                    <!-- Show all columns only for 'all' category -->
                                    <th>Booking Money (TK.)</th>
                                    <th>Service Fee (TK.)</th>
                                    <th>EMI Payments (TK.)</th>
                                    <th>Landshare Payments (TK.)</th>
                                    <th>Registration (TK.)</th>
                                    <th>Management Cashin (TK.)</th>
                                    <th>Total Cash IN (TK.)</th>
                                    <th>Cash Out (TK.)</th>
                                    <th>Net Amount (TK.)</th>
                                    <th>Running Balance (TK.)</th>
                                <?php elseif ($category === 'total_cashin'): ?>
                                    <!-- For Total Cash IN only -->
                                    <th>Total Cash IN (TK.)</th>
                                    <th>Amount (TK.)</th>
                                <?php elseif ($category === 'emi'): ?>
                                    <!-- For EMI only -->
                                    <th>EMI Payments (TK.)</th>
                                    <th>Amount (TK.)</th>
                                <?php elseif ($category === 'landshare'): ?>
                                    <!-- For landshare only -->
                                    <th>Landshare Payments (TK.)</th>
                                    <th>Amount (TK.)</th>
                                <?php elseif ($category === 'service_fee'): ?>
                                    <!-- For service fee only -->
                                    <th>Service Fee (TK.)</th>
                                    <th>Amount (TK.)</th>
                                <?php elseif ($category === 'registration'): ?>
                                    <!-- For registration only -->
                                    <th>Registration (TK.)</th>
                                    <th>Amount (TK.)</th>
                                <?php elseif ($category === 'booking_money'): ?>
                                    <!-- For booking money only -->
                                    <th>Booking Money (TK.)</th>
                                    <th>Amount (TK.)</th>
                                <?php elseif ($category === 'agreement'): ?>
                                    <!-- For agreement value only -->
                                    <th>Agreement Value (TK.)</th>
                                    <th>Amount (TK.)</th>
                                <?php elseif ($category === 'management_cashin'): ?>
                                    <!-- For management cashin only -->
                                    <th>Management Cashin (TK.)</th>
                                    <th>Amount (TK.)</th>
                                <?php elseif ($category === 'expense_adjustment_cashin'): ?>
                                    <th>Expense Adjustment Returns (TK.)</th>
                                    <th>Amount (TK.)</th>
                                <?php elseif ($category === 'procurement_returns_cashin'): ?>
                                    <th>Procurement Returns (TK.)</th>
                                    <th>Amount (TK.)</th>
                                <?php elseif (in_array($category, ['cashout', 'utilities', 'other', 'office_procurement_assets', 'assets_repairing', 'fund_requisitions'])): ?>
                                    <!-- For cashout/expense categories -->
                                    <th>Cash Out (TK.)</th>
                                    <th>Amount (TK.)</th>
                                    <?php if ($category === 'cashout'): ?>
                                    <th>Source / Purpose</th>
                                    <?php endif; ?>
                                <?php elseif ($category === 'vendor_payment'): ?>
                                    <!-- For vendor payments -->
                                    <th>Vendor Payment (TK.)</th>
                                    <th>Amount (TK.)</th>
                                <?php elseif ($category === 'contractor_bill'): ?>
                                    <!-- For contractor bills -->
                                    <th>Contractor Bill (TK.)</th>
                                    <th>Amount (TK.)</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="table__body">
                            <?php foreach ($statement_data['monthly_data'] as $row):
                                $monthPrefix = ($row['year'] ?? '') . '-' . sprintf('%02d', $row['month_num'] ?? 0);
                            ?>
                                <tr>
                                    <td data-order="<?= htmlspecialchars($monthPrefix) ?>"><?= htmlspecialchars($row['month']) ?></td>

                                    <?php if ($category === 'all'): ?>
                                        <!-- Show all data for 'all' category -->
                                        <td class="positive"><strong>৳<?= number_format($row['booking_money'] ?? 0, 2) ?></strong></td>
                                        <td class="positive"><strong>৳<?= number_format($row['service_fee'] ?? 0, 2) ?></strong></td>
                                        <td class="positive"><strong>৳<?= number_format($row['emi_payments'] ?? 0, 2) ?></strong></td>
                                        <td class="positive"><strong>৳<?= number_format($row['landshare_payments'] ?? 0, 2) ?></strong></td>
                                        <td class="positive"><strong>৳<?= number_format($row['registration_payments'] ?? 0, 2) ?></strong></td>
                                        <td class="positive"><strong>৳<?= number_format($row['management_cashin'] ?? 0, 2) ?></strong></td>
                                        <td class="positive"><strong>৳<?= number_format($row['total_cashin'] ?? 0, 2) ?></strong></td>
                                        <td class="negative"><strong>৳<?= number_format($row['cash_out'] ?? 0, 2) ?></strong></td>
                                        <td class="<?= ($row['net_amount'] ?? 0) >= 0 ? 'positive' : 'negative' ?>">
                                            <strong>৳<?= number_format($row['net_amount'] ?? 0, 2) ?></strong>
                                        </td>
                                        <td class="<?= ($row['running_balance'] ?? 0) >= 0 ? 'positive' : 'negative' ?>">
                                            <strong>৳<?= number_format($row['running_balance'] ?? 0, 2) ?></strong>
                                        </td>
                                        
                                    <?php elseif ($category === 'total_cashin'): ?>
                                        <!-- Show only Total Cash IN data -->
                                        <td class="positive"><strong>৳<?= number_format($row['total_cashin'] ?? 0, 2) ?></strong></td>
                                        <td class="positive"><strong>৳<?= number_format($row['total_cashin'] ?? 0, 2) ?></strong></td>
                                    <?php elseif ($category === 'emi'): ?>
                                        <!-- Show only EMI data -->
                                        <td class="positive"><strong>৳<?= number_format($row['emi_payments'] ?? 0, 2) ?></strong></td>
                                        <td class="positive"><strong>৳<?= number_format($row['emi_payments'] ?? 0, 2) ?></strong></td>
                                    <?php elseif ($category === 'landshare'): ?>
                                        <!-- Show only landshare data -->
                                        <td class="positive"><strong>৳<?= number_format($row['landshare_payments'] ?? 0, 2) ?></strong></td>
                                        <td class="positive"><strong>৳<?= number_format($row['landshare_payments'] ?? 0, 2) ?></strong></td>
                                    <?php elseif ($category === 'service_fee'): ?>
                                        <!-- Show only service fee data -->
                                        <td class="positive"><strong>৳<?= number_format($row['service_fee'] ?? 0, 2) ?></strong></td>
                                        <td class="positive"><strong>৳<?= number_format($row['service_fee'] ?? 0, 2) ?></strong></td>
                                    <?php elseif ($category === 'registration'): ?>
                                        <!-- Show only registration data -->
                                        <td class="positive"><strong>৳<?= number_format($row['registration_payments'] ?? 0, 2) ?></strong></td>
                                        <td class="positive"><strong>৳<?= number_format($row['registration_payments'] ?? 0, 2) ?></strong></td>
                                    <?php elseif ($category === 'booking_money'): ?>
                                        <!-- Show only booking money data -->
                                        <td class="positive"><strong>৳<?= number_format($row['booking_money'] ?? 0, 2) ?></strong></td>
                                        <td class="positive"><strong>৳<?= number_format($row['booking_money'] ?? 0, 2) ?></strong></td>
                                    <?php elseif ($category === 'agreement'): ?>
                                        <!-- Show only agreement data -->
                                        <td class="positive"><strong>৳<?= number_format($row['agreement_value'] ?? 0, 2) ?></strong></td>
                                        <td class="positive"><strong>৳<?= number_format($row['agreement_value'] ?? 0, 2) ?></strong></td>
                                    <?php elseif ($category === 'management_cashin'): ?>
                                        <!-- Show only management cashin data -->
                                        <td class="positive"><strong>৳<?= number_format($row['management_cashin'] ?? 0, 2) ?></strong></td>
                                        <td class="positive"><strong>৳<?= number_format($row['management_cashin'] ?? 0, 2) ?></strong></td>
                                    <?php elseif ($category === 'expense_adjustment_cashin'): ?>
                                        <td class="positive"><strong>৳<?= number_format($row['expense_adjustment_cashin'] ?? 0, 2) ?></strong></td>
                                        <td class="positive"><strong>৳<?= number_format($row['expense_adjustment_cashin'] ?? 0, 2) ?></strong></td>
                                    <?php elseif ($category === 'procurement_returns_cashin'): ?>
                                        <td class="positive"><strong>৳<?= number_format($row['procurement_returns_cashin'] ?? 0, 2) ?></strong></td>
                                        <td class="positive"><strong>৳<?= number_format($row['procurement_returns_cashin'] ?? 0, 2) ?></strong></td>
                                    <?php elseif (in_array($category, ['cashout', 'utilities', 'other', 'office_procurement_assets', 'assets_repairing', 'fund_requisitions'])): ?>
                                        <!-- Show only cashout data -->
                                        <td class="negative"><strong>৳<?= number_format($row['cash_out'] ?? 0, 2) ?></strong></td>
                                        <td class="negative"><strong>৳<?= number_format($row['cash_out'] ?? 0, 2) ?></strong></td>
                                        <?php if ($category === 'cashout'): ?>
                                        <td>
                                            <?php
                                            $hasAny = false;
                                            foreach ($cashout_payment_details as $date => $payments):
                                                if (!str_starts_with($date, $monthPrefix)) continue;
                                                foreach ($payments as $payment):
                                                    $hasAny = true;
                                            ?>
                                                <div style="font-size: 12px; margin-bottom: 4px; line-height: 1.6;">
                                                    <span style="color: #888; font-size: 11px;"><?= htmlspecialchars($payment['payment_date'] ?? $date) ?></span>
                                                    · <span style="color: #555;"><?= htmlspecialchars($payment['purpose_paid_to'] ?? '—') ?></span>
                                                    <?php if (!empty($payment['project_name'])): ?>
                                                        <span style="color: #007bff; font-size: 11px;">(<?= htmlspecialchars($payment['project_name']) ?>)</span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($payment['office'])): ?>
                                                        <span style="color: #888; font-size: 11px;">· <?= htmlspecialchars($payment['office']) ?></span>
                                                    <?php endif; ?>
                                                    <span style="font-size: 11px; background: #eee; padding: 1px 5px; border-radius: 3px; margin-left: 4px;">
                                                        <?= ucfirst($payment['payment_method'] ?? 'cash') ?>
                                                    </span>
                                                    <?php if (!empty($payment['transaction_ref'])): ?>
                                                        <span style="color: #888; font-size: 11px;">· Ref: <?= htmlspecialchars($payment['transaction_ref']) ?></span>
                                                    <?php endif; ?>
                                                    <strong class="negative" style="margin-left: 4px;">৳<?= number_format($payment['amount'] ?? 0, 2) ?></strong>
                                                </div>
                                            <?php
                                                endforeach;
                                            endforeach;
                                            if (!$hasAny): ?>
                                                <span class="text-muted fs-11 fst-italic">No record</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php endif; ?>
                                    <?php elseif ($category === 'vendor_payment'): ?>
                                        <!-- Show vendor payment monthly data -->
                                        <td class="negative"><strong>৳<?= number_format($row['cash_out'] ?? 0, 2) ?></strong></td>
                                        <td class="negative"><strong>৳<?= number_format($row['cash_out'] ?? 0, 2) ?></strong></td>
                                    <?php elseif ($category === 'contractor_bill'): ?>
                                        <!-- Show contractor bill monthly data -->
                                        <td class="negative"><strong>৳<?= number_format($row['cash_out'] ?? 0, 2) ?></strong></td>
                                        <td class="negative"><strong>৳<?= number_format($row['cash_out'] ?? 0, 2) ?></strong></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const yearSelect = document.getElementById('year');

        // If JS destroyed options, rebuild them
        if (yearSelect && yearSelect.options.length === 0) {
            const currentYear = new Date().getFullYear();
            for (let y = currentYear; y >= currentYear - 5; y--) {
                const opt = document.createElement('option');
                opt.value = y;
                opt.textContent = y;
                yearSelect.appendChild(opt);
            }
        }

        // Initialize period display
        updateCardPeriodText();
    });

function handlePeriodChange() {
    const periodType = document.getElementById('periodType').value;

    const yearFilter = document.getElementById('yearFilter');
    const monthFilter = document.getElementById('monthFilter');
    const startDateFilter = document.getElementById('startDateFilter');
    const endDateFilter = document.getElementById('endDateFilter');

    const yearSelect = document.getElementById('year');
    const monthSelect = document.getElementById('month');
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');

    // Hide all
    yearFilter.style.display = 'none';
    monthFilter.style.display = 'none';
    startDateFilter.style.display = 'none';
    endDateFilter.style.display = 'none';

    yearSelect.disabled = true;
    monthSelect.disabled = true;
    startDate.disabled = true;
    endDate.disabled = true;

    switch (periodType) {
        case 'all_time':
            // No date filters needed for all time
            break;

        case 'monthly':
            yearFilter.style.display = 'block';
            monthFilter.style.display = 'block';
            yearSelect.disabled = false;
            monthSelect.disabled = false;
            break;

        case '6months':
            yearFilter.style.display = 'block';
            monthFilter.style.display = 'block';
            yearSelect.disabled = false;
            monthSelect.disabled = false;
            break;

        case 'yearly':
            yearFilter.style.display = 'block';
            yearSelect.disabled = false;
            break;

        case 'custom':
            startDateFilter.style.display = 'block';
            endDateFilter.style.display = 'block';
            startDate.disabled = false;
            endDate.disabled = false;
            break;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize period display
    handlePeriodChange();
    
    // Fix for year dropdown if it's empty
    const yearSelect = document.getElementById('year');
    if (yearSelect && yearSelect.options.length === 0) {
        const currentYear = new Date().getFullYear();
        for (let y = currentYear; y >= 2020; y--) {
            const option = document.createElement('option');
            option.value = y;
            option.text = y;
            yearSelect.appendChild(option);
        }
    }
    
    // Make sure selected values are visible
    const urlParams = new URLSearchParams(window.location.search);
    const selectedYear = urlParams.get('year');
    const selectedMonth = urlParams.get('month');
    
    if (selectedYear && yearSelect) {
        yearSelect.value = selectedYear;
    }
    // Ensure select2 is destroyed on #projectId so native Bootstrap select works perfectly
    if (typeof $ !== 'undefined' && $.fn.select2) {
        try {
            if ($('#projectId').hasClass('select2-hidden-accessible') || $('#projectId').data('select2')) {
                $('#projectId').select2('destroy');
            }
        } catch (e) {}
    }
});
    document.addEventListener('DOMContentLoaded', handlePeriodChange);

    function setCardPeriod(period) {
        const form = document.getElementById('filterForm');
        const cardPeriodInput = document.createElement('input');
        cardPeriodInput.type = 'hidden';
        cardPeriodInput.name = 'card_period';
        cardPeriodInput.value = period;
        form.appendChild(cardPeriodInput);

        // Remove any existing period inputs
        const existingPeriodInputs = form.querySelectorAll('input[name="card_period"]');
        existingPeriodInputs.forEach(input => {
            if (input !== cardPeriodInput) input.remove();
        });

        // Update card period text
        updateCardPeriodText();

        // Submit form
        form.submit();
    }

    function updateCardPeriodText() {
        const period = '<?= $card_period ?? "all_time" ?>';
        let periodText = '';

        switch (period) {
            case 'weekly':
                periodText = '(This Week)';
                break;
            case 'monthly':
                periodText = '(This Month)';
                break;
            case 'yearly':
                periodText = '(This Year)';
                break;
            default:
                periodText = '(All Time)';
        }

        document.getElementById('revenuePeriodText').textContent = periodText;
        document.getElementById('cashoutPeriodText').textContent = periodText;
        document.getElementById('balancePeriodText').textContent = periodText;
    }

    function exportCSV() {
        const form = document.getElementById('filterForm');
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);
        window.location.href = '<?= $GLOBALS['base_url'] ?>/central-statement-export?' + params.toString();
    }

    function printStatement() {
        const form = document.getElementById('filterForm');
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);
        window.open('<?= $GLOBALS['base_url'] ?>/central-statement?print=true&' + params.toString(), '_blank');
    }

    function downloadPDF() {
        const form = document.getElementById('filterForm');
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);
        window.open('<?= $GLOBALS['base_url'] ?>/central-statement?print=true&' + params.toString(), '_blank');
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        handlePeriodChange();
        updateCardPeriodText();
    });
</script>
<?php ?>


    <!-- DEDICATED CLIENT RECEIPTS & FLAT BREAKDOWN CARD -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-dark">
                <i class="fa-light fa-users-rectangle me-2 text-primary"></i> Client Receipts & Flat Breakdown (কাস্টমার জমা ও ফ্ল্যাট ভিত্তিক বিবরণী)
            </h5>
            <span class="badge bg-primary-transparent text-primary px-3 py-2 fs-12">
                Period: <?= htmlspecialchars($period_text ?? "") ?>
            </span>
        </div>
        <div class="card-body p-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle multiple_tables mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Client Name</th>
                            <th>Project</th>
                            <th>Flat</th>
                            <th>Payment Category</th>
                            <th>Method</th>
                            <th class="text-end">Amount (BDT)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalClientAmount = 0;
                        $allDetails = [];
                        if (!empty($client_payment_details['by_date'])) {
                            foreach ($client_payment_details['by_date'] as $dateList) {
                                foreach ($dateList as $item) { $allDetails[] = $item; }
                            }
                        } elseif (!empty($client_payment_details)) {
                            foreach ($client_payment_details as $itemOrList) {
                                if (is_array($itemOrList)) {
                                    foreach ($itemOrList as $item) {
                                        if (is_array($item) && isset($item['amount'])) { $allDetails[] = $item; }
                                    }
                                }
                            }
                        }
                        ?>
                        <?php if (empty($allDetails)): ?>
          
                            <?php foreach ($allDetails as $cp): ?>
                                <?php $totalClientAmount += (float)($cp['amount'] ?? 0); ?>
                                <tr>
                                    <td class="font-monospace fs-12"><?= date('d M Y', strtotime($cp['payment_date'])) ?></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($cp['client_name'] ?? '—') ?></td>
                                    <td>
                                        <span class="badge bg-primary-transparent text-primary fs-11">
                                            <?= htmlspecialchars($cp['project_name'] ?? '—') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info-transparent text-info fs-11">
                                            <?= htmlspecialchars($cp['flat_name'] ?? 'N/A') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border fs-11">
                                            <?= ucfirst(str_replace('_', ' ', $cp['payment_type'] ?? '')) ?>
                                        </span>
                                    </td>
                                    <td>
              
                                    <span class="badge bg-light text-dark border fs-11 text-uppercase">
                                            <?= htmlspecialchars($cp['payment_method'] ?? 'cash') ?>
                                        </span>
                                    </td>
                                    <td class="text-end font-monospace fw-bold text-success fs-13">
                                        ৳ <?= number_format($cp['amount'] ?? 0, 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td colspan="6" class="text-end">TOTAL CLIENT RECEIPTS:</td>
                            <td class="text-end font-monospace text-success fs-14">৳ <?= number_format($totalClientAmount, 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

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
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        console.log(document.getElementById('year').innerHTML);
    });
</script>
<div class="row">
    <!-- Filter Controls -->
    <div class="col-xxl-12 mb-4">
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
                                $currentYear  = (int)date('Y');
                                $selectedYear = isset($year) ? (int)$year : $currentYear;

                                for ($y = $currentYear; $y >= ($currentYear - 5); $y--):
                                ?>
                                    <option value="<?= $y ?>" <?= $selectedYear === $y ? 'selected' : '' ?>>
                                        <?= $y ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="filter-item" id="monthFilter">
                            <label>Month</label>
                            <select name="month" id="month" class="form-select">
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

                        <div class="filter-item">
                            <label>Project</label>
                            <select name="project_id" id="projectId" class="form-select">
                                <option value="">All Projects</option>
                                <?php foreach ($projects ?? [] as $project): ?>
                                    <option value="<?= $project['id'] ?>" <?= ($project_id ?? '') == $project['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($project['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-item">
                            <label>Category</label>
                            <select name="category" id="category" class="form-select">
                                <option value="all" <?= ($category ?? '') === 'all' ? 'selected' : '' ?>>All Categories</option>
                                <optgroup label="Revenue Categories">
                                    <option value="landshare" <?= ($category ?? '') === 'landshare' ? 'selected' : '' ?>>Landshare Only</option>
                                    <option value="service_fee" <?= ($category ?? '') === 'service_fee' ? 'selected' : '' ?>>Service Fee Only</option>
                                </optgroup>
                                <optgroup label="Expense Categories">
                                    <option value="cashout" <?= ($category ?? '') === 'cashout' ? 'selected' : '' ?>>Cash Out</option>
                                    <option value="utilities" <?= ($category ?? '') === 'utilities' ? 'selected' : '' ?>>Utilities</option>
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

    <!-- Add this new card as the first card in the statistics section -->
    <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper stat-card cash-in">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-money-bill-trend-up"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Cash IN</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title fs-6">৳<?= number_format($overall_stats['total_cashin'] ?? 0, 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Update existing cards to col-xxl-3 instead of col-xxl-4 -->
    <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper stat-card revenue">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-chart-line"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Revenue </h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title fs-6">৳<?= number_format($overall_stats['total_revenue'] ?? 0, 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper stat-card cash-out">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-arrow-up-from-line"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Cash Out

                    </h6>

                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title fs-6">৳<?= number_format($overall_stats['total_cash_out'] ?? 0, 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper stat-card net-balance">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-scale-balanced"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Net Balance
                    </h6>

                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title fs-6">৳<?= number_format($overall_stats['net_balance'] ?? 0, 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Period Info -->
    <div class="col-xxl-12">
        <div class="period-info">
            <div>
                <h3><?= htmlspecialchars($period_text ?? 'Financial Statement') ?></h3>
                <p>Generated on <?= date('d M, Y h:i A') ?></p>
                <?php if (isset($project_id) && $project_id): ?>
                    <?php
                    $projectName = '';
                    foreach ($projects ?? [] as $project) {
                        if ($project['id'] == $project_id) {
                            $projectName = $project['name'];
                            break;
                        }
                    }
                    if ($projectName): ?>
                        <p>Project: <?= htmlspecialchars($projectName) ?></p>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if (isset($category) && $category !== 'all'): ?>
                    <?php
                    $categoryName = ucfirst(str_replace('_', ' ', $category));
                    if ($category === 'cashout') {
                        $categoryName = 'Cash Out';
                    } elseif ($category === 'utilities') {
                        $categoryName = 'Utilities (Bill/Electricity/Wifi/etc)';
                    }
                    ?>
                    <p>Category: <?= htmlspecialchars($categoryName) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>



    <!-- Statement Data -->
    <div class="col-xxl-12 mb-4">
        <div class="card__wrapper">
            <div class="card__body">
                <!-- In the daily data table section -->
                <?php if (isset($statement_data['daily_data'])): ?>
                    <div class="table__wrapper table-responsive">
                        <table class="table mb-20 multiple_tables">
                            <thead>
                                <tr class="table__title table__sort">
                                    <th>Date</th>
                                    <th>Day</th>

                                    <?php if ($category === 'all' || $category === 'landshare'): ?>
                                        <th>Agreement Value (৳)</th> <!-- Changed from Landshare -->
                                    <?php endif; ?>

                                    <?php if ($category === 'all' || $category === 'service_fee'): ?>
                                        <th>Service Fee (৳)</th>
                                    <?php endif; ?>

                                    <?php if ($category === 'all'): ?>
                                        <th>Management Cashin (৳)</th> <!-- Added this -->
                                        <th>Total Cash IN (৳)</th> <!-- Changed from Total Revenue -->
                                    <?php endif; ?>

                                    <?php if (in_array($category, ['all', 'cashout', 'utilities', 'other'])): ?>
                                        <th>Cash Out (৳)</th>
                                    <?php endif; ?>

                                    <?php if ($category === 'all'): ?>
                                        <th>Net Amount (৳)</th>
                                        <th>Running Balance (৳)</th>
                                    <?php else: ?>
                                        <th>Amount (৳)</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody class="table__body">
                                <?php foreach ($statement_data['daily_data'] as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['formatted_date'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($row['day_name'] ?? '') ?></td>

                                        <?php if ($category === 'all' || $category === 'landshare'): ?>
                                            <td class="positive"><strong>৳<?= number_format($row['agreement_value'] ?? $row['landshare'] ?? 0, 2) ?></strong></td>
                                        <?php endif; ?>

                                        <?php if ($category === 'all' || $category === 'service_fee'): ?>
                                            <td class="positive"><strong>৳<?= number_format($row['service_fee'] ?? 0, 2) ?></strong></td>
                                        <?php endif; ?>

                                        <?php if ($category === 'all'): ?>
                                            <td class="positive"><strong>৳<?= number_format($row['management_cashin'] ?? 0, 2) ?></strong></td>
                                            <td class="positive"><strong>৳<?= number_format($row['total_cashin'] ?? $row['total_revenue'] ?? 0, 2) ?></strong></td>
                                        <?php endif; ?>

                                        <?php if (in_array($category, ['all', 'cashout', 'utilities', 'other'])): ?>
                                            <td class="negative"><strong>৳<?= number_format($row['cash_out'] ?? 0, 2) ?></strong></td>
                                        <?php endif; ?>

                                        <?php if ($category === 'all'): ?>
                                            <td class="<?= ($row['net_amount'] ?? 0) >= 0 ? 'positive' : 'negative' ?>">
                                                <strong>৳<?= number_format($row['net_amount'] ?? 0, 2) ?></strong>
                                            </td>
                                            <td class="<?= ($row['running_balance'] ?? 0) >= 0 ? 'positive' : 'negative' ?>">
                                                <strong>৳<?= number_format($row['running_balance'] ?? 0, 2) ?></strong>
                                            </td>
                                        <?php else: ?>
                                            <?php
                                            // Show specific category amount
                                            $amount = 0;
                                            if ($category === 'landshare') {
                                                $amount = $row['agreement_value'] ?? $row['landshare'] ?? 0;
                                            } elseif ($category === 'service_fee') {
                                                $amount = $row['service_fee'] ?? 0;
                                            } else {
                                                $amount = $row['cash_out'] ?? 0;
                                            }
                                            $colorClass = in_array($category, ['landshare', 'service_fee']) ? 'positive' : 'negative';
                                            ?>
                                            <td class="<?= $colorClass ?>">
                                                <strong>৳<?= number_format($amount, 2) ?></strong>
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
                                        <th>Agreement Value (৳)</th>
                                        <th>Service Fee (৳)</th>
                                        <th>Management Cashin (৳)</th>
                                        <th>Total Cash IN (৳)</th>
                                        <th>Cash Out (৳)</th>
                                        <th>Net Amount (৳)</th>
                                        <th>Running Balance (৳)</th>
                                    <?php elseif ($category === 'landshare'): ?>
                                        <!-- For landshare only -->
                                        <th>Agreement Value (৳)</th>
                                        <th>Amount (৳)</th>
                                    <?php elseif ($category === 'service_fee'): ?>
                                        <!-- For service fee only -->
                                        <th>Service Fee (৳)</th>
                                        <th>Amount (৳)</th>
                                    <?php elseif (in_array($category, ['cashout', 'utilities', 'other'])): ?>
                                        <!-- For cashout/expense categories -->
                                        <th>Cash Out (৳)</th>
                                        <th>Amount (৳)</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody class="table__body">
                                <?php foreach ($statement_data['monthly_data'] as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['month']) ?></td>

                                        <?php if ($category === 'all'): ?>
                                            <!-- Show all data for 'all' category -->
                                            <td class="positive"><strong>৳<?= number_format($row['agreement_value'] ?? $row['landshare'] ?? 0, 2) ?></strong></td>
                                            <td class="positive"><strong>৳<?= number_format($row['service_fee'] ?? 0, 2) ?></strong></td>
                                            <td class="positive"><strong>৳<?= number_format($row['management_cashin'] ?? 0, 2) ?></strong></td>
                                            <td class="positive"><strong>৳<?= number_format($row['total_cashin'] ?? $row['total_revenue'] ?? 0, 2) ?></strong></td>
                                            <td class="negative"><strong>৳<?= number_format($row['cash_out'] ?? 0, 2) ?></strong></td>
                                            <td class="<?= ($row['net_amount'] ?? 0) >= 0 ? 'positive' : 'negative' ?>">
                                                <strong>৳<?= number_format($row['net_amount'] ?? 0, 2) ?></strong>
                                            </td>
                                            <td class="<?= ($row['running_balance'] ?? 0) >= 0 ? 'positive' : 'negative' ?>">
                                                <strong>৳<?= number_format($row['running_balance'] ?? 0, 2) ?></strong>
                                            </td>
                                        <?php elseif ($category === 'landshare'): ?>
                                            <!-- Show only landshare data -->
                                            <td class="positive"><strong>৳<?= number_format($row['agreement_value'] ?? $row['landshare'] ?? 0, 2) ?></strong></td>
                                            <td class="positive"><strong>৳<?= number_format($row['agreement_value'] ?? $row['landshare'] ?? 0, 2) ?></strong></td>
                                        <?php elseif ($category === 'service_fee'): ?>
                                            <!-- Show only service fee data -->
                                            <td class="positive"><strong>৳<?= number_format($row['service_fee'] ?? 0, 2) ?></strong></td>
                                            <td class="positive"><strong>৳<?= number_format($row['service_fee'] ?? 0, 2) ?></strong></td>
                                        <?php elseif (in_array($category, ['cashout', 'utilities', 'other'])): ?>
                                            <!-- Show only cashout data -->
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

        yearSelect.disabled = false;
        monthSelect.disabled = true;
        startDate.disabled = true;
        endDate.disabled = true;

        switch (periodType) {
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
                yearFilter.style.color = '#000';
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

        // Show loading
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fa-light fa-spinner fa-spin me-2"></i>Generating PDF...';
        btn.disabled = true;

        // Open print view in new tab for PDF generation
        const printWindow = window.open('<?= $GLOBALS['base_url'] ?>/central-statement?print=true&' + params.toString(), '_blank');

        // Wait a moment for the page to load, then trigger print which can save as PDF
        setTimeout(function() {
            printWindow.print();
            btn.innerHTML = originalText;
            btn.disabled = false;
        }, 1000);
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        handlePeriodChange();
        updateCardPeriodText();
    });
</script>
<?php ?>
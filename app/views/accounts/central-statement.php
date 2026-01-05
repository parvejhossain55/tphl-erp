<?php
// This is the main view file for the central statement
$page_title = "Central Financial Statement";
$page = 'Accounts';
$addPage = false;
$show_breadcrumb = true;
?>

<style>
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
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border-left: 4px solid;
        margin-bottom: 20px;
    }
    
    .stat-card.cash-in {
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
    
    .stat-card.cash-in .value {
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
        background: linear-gradient(135deg, var(--clr-theme-primary) 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 30px;
    }
    
    .period-info h3 {
        font-size: 24px;
        font-weight: 700;
    }
    
    .action-buttons {
        position: fixed;
        top: 100px;
        right: 20px;
        z-index: 1000;
        display: flex;
        gap: 10px;
        flex-direction: column;
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
</style>

<!-- Action Buttons -->
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
                                $currentYear = date('Y');
                                for ($y = $currentYear; $y >= ($currentYear - 5); $y--):
                                ?>
                                    <option value="<?= $y ?>" <?= ($year ?? '') == $y ? 'selected' : '' ?>><?= $y ?></option>
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
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-light fa-search me-2"></i> Search
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="col-xxl-4 col-xl-4 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper stat-card cash-in">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-arrow-down-to-line"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Cash In (All Time)</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title fs-6">৳<?= number_format($overall_stats['total_cash_in'] ?? 0, 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-4 col-xl-4 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper stat-card cash-out">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-arrow-up-from-line"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Cash Out (All Time)</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title fs-6">৳<?= number_format($overall_stats['total_cash_out'] ?? 0, 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-4 col-xl-4 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper stat-card net-balance">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-scale-balanced"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Overall Net Balance</h6>
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
            </div>
        </div>
    </div>

    <!-- Statement Data -->
    <div class="col-xxl-12 mb-4">
        <div class="card__wrapper">
            <div class="card__body">
                <?php if (isset($statement_data['daily_data'])): ?>
                    <div class="table__wrapper table-responsive">
                        <table class="table mb-20 multiple_tables">
                            <thead>
                                <tr class="table__title table__sort">
                                    <th>Date</th>
                                    <th>Day</th>
                                    <th>Cash In (৳)</th>
                                    <th>Cash Out (৳)</th>
                                    <th>Net Amount (৳)</th>
                                    <th>Running Balance (৳)</th>
                                </tr>
                            </thead>
                            <tbody class="table__body">
                                <?php foreach ($statement_data['daily_data'] as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['formatted_date']) ?></td>
                                        <td><?= htmlspecialchars($row['day_name']) ?></td>
                                        <td class="positive"><strong>৳<?= number_format($row['cash_in'], 2) ?></strong></td>
                                        <td class="negative"><strong>৳<?= number_format($row['cash_out'], 2) ?></strong></td>
                                        <td class="<?= $row['net_amount'] >= 0 ? 'positive' : 'negative' ?>">
                                            <strong>৳<?= number_format($row['net_amount'], 2) ?></strong>
                                        </td>
                                        <td class="<?= $row['running_balance'] >= 0 ? 'positive' : 'negative' ?>">
                                            <strong>৳<?= number_format($row['running_balance'], 2) ?></strong>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php elseif (isset($statement_data['monthly_data'])): ?>
                    <div class="table__wrapper table-responsive">
                        <table class="table mb-20 multiple_tables">
                            <thead>
                                <tr class="table__title table__sort">
                                    <th>Month</th>
                                    <th>Cash In (৳)</th>
                                    <th>Cash Out (৳)</th>
                                    <th>Net Amount (৳)</th>
                                    <th>Running Balance (৳)</th>
                                </tr>
                            </thead>
                            <tbody class="table__body">
                                <?php foreach ($statement_data['monthly_data'] as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['month']) ?></td>
                                        <td class="positive"><strong>৳<?= number_format($row['cash_in'], 2) ?></strong></td>
                                        <td class="negative"><strong>৳<?= number_format($row['cash_out'], 2) ?></strong></td>
                                        <td class="<?= $row['net_amount'] >= 0 ? 'positive' : 'negative' ?>">
                                            <strong>৳<?= number_format($row['net_amount'], 2) ?></strong>
                                        </td>
                                        <td class="<?= $row['running_balance'] >= 0 ? 'positive' : 'negative' ?>">
                                            <strong>৳<?= number_format($row['running_balance'], 2) ?></strong>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <div class="text-muted">
                            <i class="fa-light fa-chart-line fa-2x mb-3"></i><br>
                            No statement data available. Please select a valid period to generate the statement.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Summary -->
    <?php if (isset($statement_data['summary'])): ?>
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="card__header d-flex justify-content-between align-items-center mb-3">
                <h5 class="card__title" style="margin-top: -13px;">Summary</h5>
            </div>
            <div class="card__body">
                <div class="row">
                    <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
                        <div class="d-flex justify-content-between align-items-center p-3 border rounded">
                            <span class="label">Total Cash In:</span>
                            <span class="value positive"><strong>৳<?= number_format($statement_data['summary']['total_cash_in'], 2) ?></strong></span>
                        </div>
                    </div>
                    <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
                        <div class="d-flex justify-content-between align-items-center p-3 border rounded">
                            <span class="label">Total Cash Out:</span>
                            <span class="value negative"><strong>৳<?= number_format($statement_data['summary']['total_cash_out'], 2) ?></strong></span>
                        </div>
                    </div>
                    <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
                        <div class="d-flex justify-content-between align-items-center p-3 border rounded">
                            <span class="label">Net Balance:</span>
                            <span class="value <?= $statement_data['summary']['net_balance'] >= 0 ? 'positive' : 'negative' ?>">
                                <strong>৳<?= number_format($statement_data['summary']['net_balance'], 2) ?></strong>
                            </span>
                        </div>
                    </div>
                    <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
                        <div class="d-flex justify-content-between align-items-center p-3 border rounded">
                            <span class="label">Final Balance:</span>
                            <span class="value <?= $statement_data['summary']['final_balance'] >= 0 ? 'positive' : 'negative' ?>">
                                <strong>৳<?= number_format($statement_data['summary']['final_balance'], 2) ?></strong>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
    function handlePeriodChange() {
        const periodType = document.getElementById('periodType').value;
        const yearFilter = document.getElementById('yearFilter');
        const monthFilter = document.getElementById('monthFilter');
        const startDateFilter = document.getElementById('startDateFilter');
        const endDateFilter = document.getElementById('endDateFilter');

        // Reset visibility
        yearFilter.style.display = 'flex';
        monthFilter.style.display = 'none';
        startDateFilter.style.display = 'none';
        endDateFilter.style.display = 'none';

        switch (periodType) {
            case 'monthly':
                monthFilter.style.display = 'flex';
                break;
            case '6months':
                monthFilter.style.display = 'flex';
                break;
            case 'yearly':
                // Only year needed
                break;
            case 'custom':
                yearFilter.style.display = 'none';
                startDateFilter.style.display = 'flex';
                endDateFilter.style.display = 'flex';
                break;
        }
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
    });
</script>
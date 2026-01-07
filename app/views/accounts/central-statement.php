<?php
// This is the main view file for the central statement
$page_title = "Central Financial Statement";
$page = 'Accounts';
$addPage = false;
$show_breadcrumb = true;
?>

 <style>
        /* ==================== PRINT & PDF STYLES (Unchanged) ==================== */
        @page {
            size: A4;
            margin: 15mm;
        }

        @media print {
            .no-print, .no-pdf { display: none !important; }
            body { 
                margin: 0 !important; padding: 0 !important;
                width: 210mm; height: 297mm; font-size: 10pt;
                -webkit-print-color-adjust: exact; print-color-adjust: exact;
            }
            .statement-container { 
                width: 210mm !important; min-height: 297mm !important;
                margin: 0 !important; padding: 5mm !important;
                box-shadow: none !important; border: none !important;
            }
            .company-header { flex-direction: row !important; }
            .statement-info {
                text-align: right !important;
                border-left: 1px solid #ccc !important;
                border-top: none !important;
                padding-left: 15px !important;
                padding-top: 0 !important;
            }
            .details-grid { grid-template-columns: 1fr 1fr !important; }
            .transaction-table { font-size: 8pt !important; }
        }

        /* ==================== BASE STYLES (Responsive) ==================== */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f5f5;
            color: #000;
            line-height: 1.3;
            font-size: clamp(10pt, 2vw, 12pt);
            margin: 0;
            padding: clamp(10px, 3vw, 20px);
        }

        .statement-container {
            max-width: 210mm;
            background: #fff;
            padding: clamp(12px, 3vw, 20px);
            margin: 0 auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        /* ==================== HEADER (Responsive) ==================== */
        .statement-header {
            margin-bottom: clamp(15px, 5vw, 30px);
            border-bottom: 2px solid #000;
            padding-bottom: clamp(10px, 2vw, 15px);
        }

        .company-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 15px;
        }

        .company-info { flex: 2; }

        .company-name {
            font-size: clamp(14px, 3vw, 18px);
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .company-details {
            font-size: clamp(9px, 2vw, 11px);
            line-height: 1.4;
        }

        .statement-info {
            flex: 1;
            text-align: right;
            border-left: 1px solid #ccc;
            padding-left: 15px;
        }

        .statement-title {
            font-size: clamp(14px, 3vw, 18px);
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .statement-meta { font-size: clamp(9px, 2vw, 11px); }
        .meta-row { margin-bottom: 5px; }
        .meta-label {
            font-weight: bold;
            display: inline-block;
            min-width: 90px;
        }

        /* ==================== PERIOD FILTER (Responsive) ==================== */
        .period-filter {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 14px 18px;
            margin-bottom: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
        }

        .period-filter .filter-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }

        .period-filter select,
        .period-filter input[type="date"] {
            padding: 8px 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            background-color: #fff;
        }

        #customDateRange {
            background: #f9fafb;
            padding: 6px 8px;
            border-radius: 6px;
            border: 1px dashed #d1d5db;
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .period-filter button {
            padding: 8px 16px;
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: #ffffff;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        /* ==================== DETAILS SECTION (Responsive) ==================== */
        .details-section { margin-bottom: clamp(15px, 3vw, 20px); }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: clamp(10px, 2vw, 15px);
            margin-bottom: 15px;
        }

        .details-box {
            border: 1px solid #000;
            padding: clamp(10px, 2vw, 15px);
            font-size: clamp(11px, 2vw, 12px);
        }

        .details-title {
            font-weight: bold;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #000;
            text-transform: uppercase;
            font-size: clamp(12px, 2vw, 13px);
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            gap: 10px;
        }

        .detail-value {
            font-weight: bold;
            text-align: right;
            word-break: break-word;
        }

        /* ==================== TRANSACTION TABLE (Responsive with Scroll) ==================== */
        .transaction-section { margin-bottom: 20px; }

        .section-title {
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #000;
            font-size: clamp(13px, 2.5vw, 14px);
        }

        .transaction-table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 0 -5px;
            padding: 0 5px;
        }

        .transaction-table {
            width: 100%;
            border-collapse: collapse;
            font-size: clamp(9px, 2vw, 11px);
            min-width: 343px;
        }

        .transaction-table th,
        .transaction-table td {
            border: 1px solid #000;
            padding: clamp(6px, 1.5vw, 10px) clamp(4px, 1vw, 8px);
            vertical-align: middle;
        }

        .transaction-table th {
            background: #f8f8f8;
            font-weight: bold;
            text-transform: uppercase;
            font-size: clamp(8px, 1.8vw, 10px);
            white-space: nowrap;
        }

        .transaction-table .amount {
            text-align: right;
            font-weight: bold;
            font-family: 'Courier New', monospace;
        }

        .status-paid { font-weight: bold; }
        .status-pending { font-style: italic; color: #666; }

        /* ==================== TOTALS (Responsive) ==================== */
        .totals-section {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #000;
        }

        .totals-table {
            width: 100%;
            font-size: clamp(12px, 2.5vw, 13px);
            margin-bottom: 15px;
        }

        .totals-table td { padding: 8px 0; }

        .totals-label {
            text-align: right;
            padding-right: clamp(10px, 3vw, 20px);
            font-weight: bold;
            width: 70%;
        }

        .totals-value {
            text-align: right;
            font-weight: bold;
            font-family: 'Courier New', monospace;
            border-bottom: 1px solid #000;
            width: 30%;
            padding-left: 10px;
        }

        .grand-total td {
            padding-top: 10px;
            border-top: 2px solid #000;
            font-size: clamp(13px, 2.5vw, 14px);
        }

        .amount-in-words {
            padding-top: 15px;
            font-style: italic;
            text-align: right;
            font-size: clamp(10px, 2vw, 11px);
            color: #333;
        }

        .calculation-note {
            margin-top: 15px;
            padding: 10px;
            background: #f8f8f8;
            border: 1px solid #ccc;
            font-size: clamp(9px, 1.8vw, 10px);
        }

        /* ==================== ACTION BUTTONS (Responsive) ==================== */
        .action-buttons {
            position: sticky;
            top: 10px;
            z-index: 1000;
            display: flex;
            justify-content: end;
            gap: 10px;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 18px;
            font-size: 13px;
            font-weight: 600;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
            white-space: nowrap;
            text-decoration: none;
        }

        .btn:hover {
            opacity: 0.95;
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.15);
            transform: translateY(-1px);
        }

        .btn-success { background: linear-gradient(135deg, #28a745, #1e7e34); color: #fff; }
        .btn-print { background: linear-gradient(135deg, #ff3c00, #cc2f00); color: #fff; }
        .btn-back { background: linear-gradient(135deg, #007bff, #0056b3); color: #fff; }

        /* ==================== MOBILE RESPONSIVENESS ==================== */
        @media (max-width: 768px) {
            .company-header {
                flex-direction: column;
                gap: 15px;
            }
            
            .statement-info {
                text-align: left;
                border-left: none;
                border-top: 1px solid #ccc;
                padding-left: 0;
                padding-top: 15px;
            }
            
            .period-filter .filter-row {
                flex-direction: column;
                align-items: stretch;
            }
            
            .period-filter select,
            .period-filter input[type="date"],
            .period-filter button {
                width: 100%;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .meta-label {
                min-width: 70px;
            }
        }

        @media (max-width: 480px) {
            .details-grid {
                grid-template-columns: 1fr;
            }
            
            .transaction-table {
                min-width: 343px;
                width:100%
            }
        }

        /* ==================== PDF GENERATION CLASS ==================== */
        .pdf-generating .statement-container {
            width: 210mm !important;
            min-height: 297mm !important;
            padding: 15mm !important;
        }

        .pdf-generating .company-header { flex-direction: row !important; }
        
        .pdf-generating .statement-info {
            text-align: right !important;
            border-left: 1px solid #ccc !important;
            border-top: none !important;
            padding-left: 15px !important;
            padding-top: 0 !important;
        }
        
        .pdf-generating .details-grid { grid-template-columns: 1fr 1fr !important; }
        .pdf-generating .transaction-table { font-size: 8pt !important; }
    </style>

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

                    <!-- Action Buttons -->
                    <div class="action-buttons no-print">
                        <button type="button" class="btn btn-success" onclick="downloadPDF()">
                            <i class="fa-light fa-download me-2"></i> <span class="d-none d-sm-inline">Download </span>PDF
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="printStatement()">
                            <i class="fa-light fa-print me-2"></i> <span class="d-none d-sm-inline">Print </span><span class="d-inline d-sm-none">Print</span>
                        </button>
                        <button type="button" class="btn btn-danger" onclick="exportCSV()">
                            <i class="fa-light fa-file-csv me-2"></i> <span class="d-none d-sm-inline">Export </span>CSV
                        </button>
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
                            <div class="summary-card">
                                <span class="label">Total Cash In:</span>
                                <span class="value positive"><strong>৳<?= number_format($statement_data['summary']['total_cash_in'], 2) ?></strong></span>
                            </div>
                        </div>
                        <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
                            <div class="summary-card">
                                <span class="label">Total Cash Out:</span>
                                <span class="value negative"><strong>৳<?= number_format($statement_data['summary']['total_cash_out'], 2) ?></strong></span>
                            </div>
                        </div>
                        <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
                            <div class="summary-card">
                                <span class="label">Net Balance:</span>
                                <span class="value <?= $statement_data['summary']['net_balance'] >= 0 ? 'positive' : 'negative' ?>">
                                    <strong>৳<?= number_format($statement_data['summary']['net_balance'], 2) ?></strong>
                                </span>
                            </div>
                        </div>
                        <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
                            <div class="summary-card">
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
        btn.innerHTML = '<i class="fa-light fa-spinner fa-spin me-2"></i>Generating...';
        btn.disabled = true;

        // Open print view in new tab for PDF generation
        const printWindow = window.open('<?= $GLOBALS['base_url'] ?>/central-statement?print=true&' + params.toString(), '_blank');

        // Wait a moment for the page to load, then trigger print which can save as PDF
        setTimeout(function() {
            if (printWindow) {
                printWindow.print();
            }
            btn.innerHTML = originalText;
            btn.disabled = false;
        }, 1000);
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        handlePeriodChange();
    });
</script>
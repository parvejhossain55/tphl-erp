<?php
/**
 * View: accounts/project-cashout.php
 * All variables are injected by ProjectCashoutController via $this->view()
 * Do NOT set $page_title or $page here — already passed from controller.
 */

$addPage         = false;
$show_breadcrumb = true;
?>

<style>
    @media print {
        * { animation: none !important; transition: none !important; box-shadow: none !important; }
        .no-print { display: none !important; }
        .card__wrapper { box-shadow: none !important; border: 1px solid #dee2e6 !important; }
    }

    .card__icon span {
        color: var(--clr-theme-primary);
        background: rgba(108, 95, 252, 0.1);
        width: 40px; height: 40px; border-radius: 50%;
        display: flex; text-align: center;
        justify-content: center; align-items: center; font-size: 16px;
    }
    .card__title { font-size: 1.25rem; }

    .stat-card {
        border-radius: 12px; padding: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border-left: 4px solid; margin-bottom: 20px;
    }
    .stat-card.cash-out     { border-left-color: #dc3545; }
    .stat-card.transactions { border-left-color: #fd7e14; }
    .stat-card.average      { border-left-color: #6f42c1; }
    .stat-card.running      { border-left-color: #007bff; }

    .positive { color: #28a745; }
    .negative { color: #dc3545; }

    .filter-group { display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; }
    .filter-item  { display: flex; flex-direction: column; gap: 8px; }
    .filter-item label { font-size: 14px; font-weight: 600; color: #495057; }
    .filter-item select, .filter-item input {
        padding: 10px 14px; border: 2px solid #dee2e6; border-radius: 8px;
        font-size: 14px; background: white; transition: all 0.2s; min-width: 150px;
    }
    .filter-item select:focus, .filter-item input:focus {
        border-color: var(--clr-theme-primary); outline: none;
        box-shadow: 0 0 0 3px rgba(108,95,252,0.1);
    }

    .period-badge {
        display: inline-block;
        background: linear-gradient(135deg, #dc3545, #a71d2a);
        color: #fff; border-radius: 8px; padding: 4px 14px;
        font-size: 13px; font-weight: 600; margin-left: 10px; vertical-align: middle;
    }

    .action-buttons { display: flex; gap: 10px; flex-wrap: wrap; }
    .btn {
        padding: 10px 20px; font-size: 14px; font-weight: 600;
        border: none; border-radius: 8px; cursor: pointer;
        display: inline-flex; align-items: center; gap: 8px;
        transition: all 0.2s; text-decoration: none;
    }
    .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
    .btn-success   { background: linear-gradient(135deg, #28a745, #1e7e34); color: white; }
    .btn-secondary { background: linear-gradient(135deg, #007bff, #0056b3); color: white; }
    .btn-danger    { background: linear-gradient(135deg, #dc3545, #c82333); color: white; }
    .btn-primary   { background: linear-gradient(135deg, #6f42c1, #4e2a84); color: white; }

    .detail-row { font-size: 12px; margin-bottom: 5px; line-height: 1.7; border-bottom: 1px solid #f0f0f0; padding-bottom: 4px; }
    .detail-row:last-child { border-bottom: none; margin-bottom: 0; }
    .badge-method  { font-size: 11px; background: #eee; padding: 1px 6px; border-radius: 3px; margin-left: 4px; }
    .badge-project { color: #007bff; font-size: 11px; }
    .badge-office  { color: #888; font-size: 11px; }
    .badge-ref     { color: #888; font-size: 11px; }

    .table__wrapper table thead th { white-space: nowrap; font-size: 13px; }
    tfoot tr td { font-weight: 700; background: #f8f9fa; font-size: 13px; }
</style>

<div class="row">

    <!-- ===== FILTER PANEL ===== -->
    <div class="col-xxl-12 mb-4 no-print">
        <div class="card__wrapper">
            <div class="card__header d-flex justify-content-between align-items-center mb-3">
                <h5 class="card__title" style="margin-top:-13px;">
                    Project Cashout Statement
                    <?php if (!empty($period_label)): ?>
                        <span class="period-badge"><?= htmlspecialchars($period_label) ?></span>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="card__body">
                <form method="GET" action="<?= $GLOBALS['base_url'] ?>/projects-cashout" id="cashoutFilterForm">
                    <div class="filter-group">

                        <div class="filter-item">
                            <label>Period Type</label>
                            <select name="period_type" id="periodType" class="form-select" onchange="handlePeriodChange()">
                                <option value="monthly" <?= $period_type === 'monthly'  ? 'selected' : '' ?>>Monthly (Daily Breakdown)</option>
                                <option value="6months" <?= $period_type === '6months'  ? 'selected' : '' ?>>Last 6 Months</option>
                                <option value="yearly"  <?= $period_type === 'yearly'   ? 'selected' : '' ?>>Yearly (Monthly Breakdown)</option>
                                <option value="custom"  <?= $period_type === 'custom'   ? 'selected' : '' ?>>Custom Range</option>
                            </select>
                        </div>

                        <div class="filter-item" id="yearFilter">
                            <label>Year</label>
                            <select name="year" id="year" class="form-select">
                                <?php
                                $currentYear  = (int)date('Y');
                                $selectedYear = (int)($year ?? $currentYear);
                                for ($y = $currentYear; $y >= 2020; $y--):
                                ?>
                                    <option value="<?= $y ?>" <?= $selectedYear === $y ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="filter-item" id="monthFilter">
                            <label>Month</label>
                            <select name="month" id="month" class="form-select">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= sprintf('%02d', $m) ?>"
                                        <?= ($month ?? '') == sprintf('%02d', $m) ? 'selected' : '' ?>>
                                        <?= date('F', mktime(0,0,0,$m,1)) ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="filter-item" id="startDateFilter" style="display:none;">
                            <label>Start Date</label>
                            <input type="date" name="start_date" id="startDate" class="form-control"
                                   value="<?= htmlspecialchars($start_date ?? '') ?>">
                        </div>

                        <div class="filter-item" id="endDateFilter" style="display:none;">
                            <label>End Date</label>
                            <input type="date" name="end_date" id="endDate" class="form-control"
                                   value="<?= htmlspecialchars($end_date ?? '') ?>">
                        </div>

                        <div class="filter-item">
                            <label>Project</label>
                            <select name="project_id" class="form-select">
                                <option value="">All Projects</option>
                                <?php foreach ($projects ?? [] as $proj): ?>
                                    <option value="<?= $proj['id'] ?>"
                                        <?= ($project_id ?? '') == $proj['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($proj['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-item">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-light fa-search me-1"></i> Search
                            </button>
                        </div>

                        <div class="filter-item">
                            <label>&nbsp;</label>
                            <div class="action-buttons">
                                <button type="button" class="btn btn-success" onclick="downloadPDF()">
                                    <i class="fa-light fa-download me-1"></i> PDF
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="printStatement()">
                                    <i class="fa-light fa-print me-1"></i> Print
                                </button>
                                <button type="button" class="btn btn-danger" onclick="exportCSV()">
                                    <i class="fa-light fa-file-csv me-1"></i> CSV
                                </button>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ===== STAT CARDS ===== -->
    <?php
    $total_cashout      = $overall_stats['total_cashout']      ?? 0;
    $total_transactions = $overall_stats['total_transactions'] ?? 0;
    $avg_payment        = $overall_stats['avg_payment']        ?? 0;
    $max_payment        = $overall_stats['max_payment']        ?? 0;
    ?>

    <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper stat-card cash-out">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon"><span><i class="fa-light fa-arrow-up-from-line"></i></span></div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Cash Out</h6>
                    <h4 class="card__title fs-6 negative">৳<?= number_format($total_cashout, 2) ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper stat-card transactions">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon"><span><i class="fa-light fa-receipt"></i></span></div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Transactions</h6>
                    <h4 class="card__title fs-6" style="color:#fd7e14;"><?= number_format($total_transactions) ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper stat-card average">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon"><span><i class="fa-light fa-chart-bar"></i></span></div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Avg. Payment</h6>
                    <h4 class="card__title fs-6" style="color:#6f42c1;">৳<?= number_format($avg_payment, 2) ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper stat-card running">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon"><span><i class="fa-light fa-arrow-trend-up"></i></span></div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Largest Payment</h6>
                    <h4 class="card__title fs-6" style="color:#007bff;">৳<?= number_format($max_payment, 2) ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== DATA TABLE ===== -->
    <div class="col-xxl-12 mb-4">
        <div class="card__wrapper">
            <div class="card__header d-flex justify-content-between align-items-center mb-3">
                <h5 class="card__title" style="margin-top:-13px;">
                    Cashout Details —
                    <span style="font-weight:400; font-size:14px; color:#6c757d;">
                        <?= htmlspecialchars($selected_project_name ?? 'All Projects') ?>
                        &nbsp;|&nbsp; <?= htmlspecialchars($period_label ?? '') ?>
                    </span>
                </h5>
            </div>
            <div class="card__body">

                <?php if (empty($statement_data)): ?>
                    <div class="text-center py-5">
                        <i class="fa-light fa-inbox fa-3x mb-3" style="color:#dee2e6;"></i>
                        <p class="text-muted">No cashout records found for the selected filters.</p>
                    </div>

                <?php elseif ($period_type === 'yearly'): ?>
                    <!-- ===== MONTHLY BREAKDOWN (Yearly view) ===== -->
                    <div class="table__wrapper table-responsive">
                        <table class="table mb-20 multiple_tables">
                            <thead>
                                <tr class="table__title table__sort">
                                    <th>Month</th>
                                    <th>Project</th>
                                    <th>Total Cash Out (৳)</th>
                                    <th>Transactions</th>
                                    <th>Running Total (৳)</th>
                                </tr>
                            </thead>
                            <tbody class="table__body">
                                <?php foreach ($statement_data as $row):
                                    // Collect project names for this month from payment_details
                                    $monthPrefix = ($row['year'] ?? '') . '-' . sprintf('%02d', $row['month_num'] ?? 0);
                                    $monthProjects = [];
                                    foreach ($payment_details as $date => $payments) {
                                        if (strncmp($date, $monthPrefix, 7) !== 0) continue;
                                        foreach ($payments as $pd) {
                                            if (!empty($pd['project_name']) && !in_array($pd['project_name'], $monthProjects)) {
                                                $monthProjects[] = $pd['project_name'];
                                            }
                                        }
                                    }
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['month']) ?></td>
                                        <td>
                                            <?php if (!empty($monthProjects)): ?>
                                                <?php foreach ($monthProjects as $pname): ?>
                                                    <span style="display:inline-block; background:#e8f0fe; color:#1a56db; font-size:11px; font-weight:600; padding:2px 8px; border-radius:4px; margin-bottom:2px;">
                                                        <?= htmlspecialchars($pname) ?>
                                                    </span><br>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span style="color:#aaa; font-size:12px;">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="negative"><strong>৳<?= number_format($row['total_amount'], 2) ?></strong></td>
                                        <td><?= number_format($row['transaction_count']) ?></td>
                                        <td class="negative"><strong>৳<?= number_format($row['running'], 2) ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td>Total</td>
                                    <td></td>
                                    <td class="negative">৳<?= number_format($total_cashout, 2) ?></td>
                                    <td><?= number_format($total_transactions) ?></td>
                                    <td class="negative">৳<?= number_format($total_cashout, 2) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                <?php else: ?>
                    <!-- ===== DAILY BREAKDOWN (monthly / 6months / custom) ===== -->
                    <div class="table__wrapper table-responsive">
                        <table class="table mb-20 multiple_tables">
                            <thead>
                                <tr class="table__title table__sort">
                                    <th>Date</th>
                                    <th>Day</th>
                                    <th>Project</th>
                                    <th>Cash Out (৳)</th>
                                    <th>Transactions</th>
                                    <th>Running Total (৳)</th>
                                    <th>Details (Purpose / Paid To)</th>
                                </tr>
                            </thead>
                            <tbody class="table__body">
                                <?php foreach ($statement_data as $row):
                                    $rowDate = $row['date'] ?? '';
                                    $details = $payment_details[$rowDate] ?? [];
                                ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($row['formatted_date'] ?? '') ?></strong></td>
                                        <td><?= htmlspecialchars($row['day_name'] ?? '') ?></td>
                                        <td>
                                            <?php
                                            // Collect unique project names for this date from payment details
                                            $projectNames = [];
                                            foreach ($details as $d) {
                                                if (!empty($d['project_name']) && !in_array($d['project_name'], $projectNames)) {
                                                    $projectNames[] = $d['project_name'];
                                                }
                                            }
                                            if (!empty($projectNames)):
                                                foreach ($projectNames as $pname): ?>
                                                    <span style="display:inline-block; background:#e8f0fe; color:#1a56db; font-size:11px; font-weight:600; padding:2px 8px; border-radius:4px; margin-bottom:2px;">
                                                        <?= htmlspecialchars($pname) ?>
                                                    </span><br>
                                                <?php endforeach;
                                            else: ?>
                                                <span style="color:#aaa; font-size:12px;">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="negative"><strong>৳<?= number_format($row['total_amount'], 2) ?></strong></td>
                                        <td><?= number_format($row['transaction_count']) ?></td>
                                        <td class="negative"><strong>৳<?= number_format($row['running'], 2) ?></strong></td>
                                        <td>
                                            <?php if (!empty($details)): ?>
                                                <?php foreach ($details as $d): ?>
                                                    <div class="detail-row">
                                                        <span style="color:#555;"><?= htmlspecialchars($d['purpose_paid_to'] ?? '—') ?></span>

                                                        <?php if (!empty($d['project_name'])): ?>
                                                            <span class="badge-project">(<?= htmlspecialchars($d['project_name']) ?>)</span>
                                                        <?php endif; ?>

                                                        <?php if (!empty($d['office'])): ?>
                                                            <span class="badge-office">· <?= htmlspecialchars($d['office']) ?></span>
                                                        <?php endif; ?>

                                                        <span class="badge-method"><?= ucfirst(htmlspecialchars($d['payment_method'] ?? 'cash')) ?></span>

                                                        <?php if (!empty($d['bank_name'])): ?>
                                                            <span class="badge-ref">· <?= htmlspecialchars($d['bank_name']) ?></span>
                                                        <?php endif; ?>

                                                        <?php if (!empty($d['transaction_ref'])): ?>
                                                            <span class="badge-ref">· Ref: <?= htmlspecialchars($d['transaction_ref']) ?></span>
                                                        <?php endif; ?>

                                                        <?php if (!empty($d['cheque_no'])): ?>
                                                            <span class="badge-ref">· Chq: <?= htmlspecialchars($d['cheque_no']) ?></span>
                                                        <?php endif; ?>

                                                        <strong class="negative" style="margin-left:6px;">৳<?= number_format($d['amount'] ?? 0, 2) ?></strong>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span style="color:#aaa; font-size:12px;">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2">Total</td>
                                    <td></td>
                                    <td class="negative">৳<?= number_format($total_cashout, 2) ?></td>
                                    <td><?= number_format($total_transactions) ?></td>
                                    <td class="negative">৳<?= number_format($total_cashout, 2) ?></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

</div><!-- /.row -->

<script>
(function () {
    function handlePeriodChange() {
        const periodType      = document.getElementById('periodType').value;
        const yearFilter      = document.getElementById('yearFilter');
        const monthFilter     = document.getElementById('monthFilter');
        const startDateFilter = document.getElementById('startDateFilter');
        const endDateFilter   = document.getElementById('endDateFilter');

        yearFilter.style.display      = 'none';
        monthFilter.style.display     = 'none';
        startDateFilter.style.display = 'none';
        endDateFilter.style.display   = 'none';

        document.getElementById('year').disabled      = true;
        document.getElementById('month').disabled     = true;
        document.getElementById('startDate').disabled = true;
        document.getElementById('endDate').disabled   = true;

        switch (periodType) {
            case 'monthly':
            case '6months':
                yearFilter.style.display  = 'block';
                monthFilter.style.display = 'block';
                document.getElementById('year').disabled  = false;
                document.getElementById('month').disabled = false;
                break;
            case 'yearly':
                yearFilter.style.display = 'block';
                document.getElementById('year').disabled = false;
                break;
            case 'custom':
                startDateFilter.style.display = 'block';
                endDateFilter.style.display   = 'block';
                document.getElementById('startDate').disabled = false;
                document.getElementById('endDate').disabled   = false;
                break;
        }
    }

    window.handlePeriodChange = handlePeriodChange;

    function buildParams() {
        const form = document.getElementById('cashoutFilterForm');
        // Enable disabled fields temporarily so they appear in FormData
        ['year','month','startDate','endDate'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.disabled = false;
        });
        const params = new URLSearchParams(new FormData(form)).toString();
        // Re-run to restore correct disabled state
        handlePeriodChange();
        return params;
    }

    window.exportCSV = function () {
        window.location.href = '<?= $GLOBALS['base_url'] ?>/projects-cashout?export=csv&' + buildParams();
    };

    window.printStatement = function () {
        window.open('<?= $GLOBALS['base_url'] ?>/projects-cashout?' + buildParams() + '&print=true', '_blank');
    };

    window.downloadPDF = function () {
        const btn  = event.currentTarget;
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fa-light fa-spinner fa-spin me-1"></i>Generating…';
        btn.disabled  = true;
        const pw = window.open('<?= $GLOBALS['base_url'] ?>/projects-cashout?' + buildParams() + '&print=true', '_blank');
        setTimeout(function () {
            if (pw) pw.print();
            btn.innerHTML = orig;
            btn.disabled  = false;
        }, 1200);
    };

    document.addEventListener('DOMContentLoaded', handlePeriodChange);
})();
</script>
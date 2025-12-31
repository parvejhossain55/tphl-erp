

<?php
$page_title = "Dashboard";
$page = 'Dashboard';
$show_breadcrumb = false;

// Prepare chart data from monthlyData
$chartLabels = [];
$chartData = [];
if (!empty($monthlyData)) {
    foreach ($monthlyData as $data) {
        $chartLabels[] = date('M Y', strtotime($data['month'] . '-01'));
        $chartData[] = floatval($data['total']);
    }
}
?>

<div class="row">
    <!-- View Type Selector -->
    <div class="col-12 mb-4">
        <div class="card__wrapper">
            <div class="card__body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h4 class="mb-0">Financial Dashboard</h4>
                        <p class="text-muted mb-0">Overview of your financial transactions</p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <label class="mb-0 fw-bold">View:</label>
                        <select id="viewTypeSelector" class="form-select" style="width: auto; min-width: 150px;">
                            <option value="cashin" <?= $viewType === 'cashin' ? 'selected' : '' ?>>Cash In</option>
                            <option value="cashout" <?= $viewType === 'cashout' ? 'selected' : '' ?>>Cash Out</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Overview Statistics (Always Visible) -->
    <div class="col-12 mb-3">
        <h5 class="mb-3">
            <i class="fa-light fa-chart-line me-2"></i>Overall Summary
        </h5>
    </div>

    <!-- Overview Cards Row -->
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
        <div class="card__wrapper h-100 dashboard-card card-success">
            <div class="d-flex align-items-center gap-3 h-100">
                <div class="card__icon icon-success">
                    <span><i class="fa-light fa-arrow-down"></i></span>
                </div>
                <div class="card__title-wrap flex-grow-1 overflow-hidden">
                    <h6 class="card__sub-title mb-2 text-muted">Total Cash In</h6>
                    <h4 class="card__title mb-0 text-success text-break">৳<?= number_format($overviewStats['total_cashin'], 2) ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
        <div class="card__wrapper h-100 dashboard-card card-danger">
            <div class="d-flex align-items-center gap-3 h-100">
                <div class="card__icon icon-danger">
                    <span><i class="fa-light fa-arrow-up"></i></span>
                </div>
                <div class="card__title-wrap flex-grow-1 overflow-hidden">
                    <h6 class="card__sub-title mb-2 text-muted">Total Cash Out</h6>
                    <h4 class="card__title mb-0 text-danger text-break">৳<?= number_format($overviewStats['total_cashout'], 2) ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
        <div class="card__wrapper h-100 dashboard-card card-info">
            <div class="d-flex align-items-center gap-3 h-100">
                <div class="card__icon icon-info">
                    <span><i class="fa-light fa-wallet"></i></span>
                </div>
                <div class="card__title-wrap flex-grow-1 overflow-hidden">
                    <h6 class="card__sub-title mb-2 text-muted">Net Balance</h6>
                    <h4 class="card__title mb-0 text-break <?= $overviewStats['net_balance'] >= 0 ? 'text-success' : 'text-danger' ?>">
                        ৳<?= number_format($overviewStats['net_balance'], 2) ?>
                    </h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
        <div class="card__wrapper h-100 dashboard-card card-secondary">
            <div class="d-flex align-items-center gap-3 h-100">
                <div class="card__icon icon-secondary">
                    <span><i class="fa-light fa-users"></i></span>
                </div>
                <div class="card__title-wrap flex-grow-1 overflow-hidden">
                    <h6 class="card__sub-title mb-2 text-muted">Total Clients</h6>
                    <h4 class="card__title mb-0 text-break"><?= number_format($overviewStats['total_clients']) ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="col-xxl-8 col-xl-12 col-lg-12 mb-3">
        <div class="card__wrapper height-equal">
            <div class="card__title-wrap d-flex flex-wrap gap-10 align-items-center justify-content-between mb-25">
                <h5 class="card__heading-title">Transaction Overview</h5>
                <div class="card__tab">
                    <ul class="nav nav-pills" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-bar-year-tab" data-bs-toggle="pill"
                                data-bs-target="#pills-bar-year" type="button" role="tab"
                                aria-controls="pills-bar-year" aria-selected="true">1Y</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-bar-month-tab" data-bs-toggle="pill"
                                data-bs-target="#pills-bar-month" type="button" role="tab"
                                aria-controls="pills-bar-month" aria-selected="false">1M</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="pills-bar-week-tab"
                                data-bs-toggle="pill" data-bs-target="#pills-bar-week" type="button"
                                role="tab" aria-controls="pills-bar-week"
                                aria-selected="false">1W</button>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="card__content">
                <div class="tab-content" id="pills-tabContent">
                    <div class="tab-pane fade" id="pills-bar-year" role="tabpanel"
                        aria-labelledby="pills-bar-year-tab">
                        <div class="card__line-chart">
                            <div id="transactionChartYearly"></div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="pills-bar-month" role="tabpanel"
                        aria-labelledby="pills-bar-month-tab">
                        <div class="card__line-chart">
                            <div id="transactionChartMonthly"></div>
                        </div>
                    </div>
                    <div class="tab-pane fade show active" id="pills-bar-week" role="tabpanel"
                        aria-labelledby="pills-bar-week-tab">
                        <div class="card__line-chart">
                            <div id="transactionChartWeek"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Methods Breakdown -->
    <div class="col-xxl-4 col-xl-6 col-lg-12 mb-3">
        <div class="card__wrapper height-equal">
            <div class="card__title-wrap d-flex align-items-center justify-content-between mb-20">
                <h5 class="card__heading-title">Payment Methods</h5>
            </div>
            <div class="card__content">
                <div id="paymentMethodsChart"></div>
            </div>
        </div>
    </div>

    <!-- Dynamic Statistics Section Header -->
    <div class="col-12 mb-3 mt-3">
        <h5 class="mb-3" id="sectionTitle">
            <i class="fa-light <?= $viewType === 'cashin' ? 'fa-arrow-down' : 'fa-arrow-up' ?> me-2"></i>
            <span id="sectionTitleText"><?= $viewType === 'cashin' ? 'Cash In Details' : 'Cash Out Details' ?></span>
        </h5>
    </div>

    <!-- Dynamic Statistics Cards -->
    <?php if ($viewType === 'cashin'): ?>
        <!-- Cash In Statistics -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
            <div class="card__wrapper h-100 dashboard-card">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-primary">
                        <span><i class="fa-light fa-money-bill-wave"></i></span>
                    </div>
                    <div class="card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-2 text-muted">Client Payments</h6>
                        <h4 class="card__title mb-0 text-break">৳<?= number_format($statistics['lead_payments'], 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
            <div class="card__wrapper h-100 dashboard-card">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-purple">
                        <span><i class="fa-light fa-building"></i></span>
                    </div>
                    <div class="card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-2 text-muted">Management Cash In</h6>
                        <h4 class="card__title mb-0 text-break">৳<?= number_format($statistics['management_cashin'], 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
            <div class="card__wrapper h-100 dashboard-card">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-teal">
                        <span><i class="fa-light fa-hand-holding-usd"></i></span>
                    </div>
                    <div class="card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-2 text-muted">Booking Money</h6>
                        <h4 class="card__title mb-0 text-break">৳<?= number_format($statistics['booking_money'], 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
            <div class="card__wrapper h-100 dashboard-card">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-indigo">
                        <span><i class="fa-light fa-file-certificate"></i></span>
                    </div>
                    <div class="card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-2 text-muted">Registration Money</h6>
                        <h4 class="card__title mb-0 text-break">৳<?= number_format($statistics['registration_money'], 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
            <div class="card__wrapper h-100 dashboard-card">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-orange">
                        <span><i class="fa-light fa-calendar-check"></i></span>
                    </div>
                    <div class="card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-2 text-muted">EMI Payments</h6>
                        <h4 class="card__title mb-0 text-break">৳<?= number_format($statistics['emi_payments'], 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
            <div class="card__wrapper h-100 dashboard-card">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-success">
                        <span><i class="fa-light fa-chart-line"></i></span>
                    </div>
                    <div class="card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-2 text-muted">This Month</h6>
                        <h4 class="card__title mb-0 text-break">৳<?= number_format($statistics['this_month'], 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
            <div class="card__wrapper h-100 dashboard-card">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-warning">
                        <span><i class="fa-light fa-clock"></i></span>
                    </div>
                    <div class="card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-2 text-muted">Pending Amount</h6>
                        <h4 class="card__title mb-0 text-warning text-break">৳<?= number_format($statistics['pending_amount'], 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
            <div class="card__wrapper h-100 dashboard-card">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-info">
                        <span><i class="fa-light fa-receipt"></i></span>
                    </div>
                    <div class="card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-2 text-muted">Total Transactions</h6>
                        <h4 class="card__title mb-0 text-break"><?= number_format($statistics['total_transactions']) ?></h4>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Cash Out Statistics -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
            <div class="card__wrapper h-100 dashboard-card">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-success">
                        <span><i class="fa-light fa-money-check-dollar"></i></span>
                    </div>
                    <div class="card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-2 text-muted">Paid Amount</h6>
                        <h4 class="card__title mb-0 text-break">৳<?= number_format($statistics['paid_amount'], 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
            <div class="card__wrapper h-100 dashboard-card">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-warning">
                        <span><i class="fa-light fa-clock"></i></span>
                    </div>
                    <div class="card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-2 text-muted">Pending Amount</h6>
                        <h4 class="card__title mb-0 text-warning text-break">৳<?= number_format($statistics['pending_amount'], 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
            <div class="card__wrapper h-100 dashboard-card">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-primary">
                        <span><i class="fa-light fa-chart-line"></i></span>
                    </div>
                    <div class="card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-2 text-muted">This Month</h6>
                        <h4 class="card__title mb-0 text-break">৳<?= number_format($statistics['this_month'], 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
            <div class="card__wrapper h-100 dashboard-card">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-info">
                        <span><i class="fa-light fa-receipt"></i></span>
                    </div>
                    <div class="card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-2 text-muted">Total Transactions</h6>
                        <h4 class="card__title mb-0 text-break"><?= number_format($statistics['total_transactions']) ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
            <div class="card__wrapper h-100 dashboard-card">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-success">
                        <span><i class="fa-light fa-money-bill"></i></span>
                    </div>
                    <div class="card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-2 text-muted">Cash Payments</h6>
                        <h4 class="card__title mb-0 text-break">৳<?= number_format($statistics['by_payment_method']['cash'], 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
            <div class="card__wrapper h-100 dashboard-card">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-purple">
                        <span><i class="fa-light fa-building-columns"></i></span>
                    </div>
                    <div class="card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-2 text-muted">Bank Transfers</h6>
                        <h4 class="card__title mb-0 text-break">৳<?= number_format($statistics['by_payment_method']['bank'], 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
            <div class="card__wrapper h-100 dashboard-card">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-teal">
                        <span><i class="fa-light fa-money-check"></i></span>
                    </div>
                    <div class="card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-2 text-muted">Cheque Payments</h6>
                        <h4 class="card__title mb-0 text-break">৳<?= number_format($statistics['by_payment_method']['cheque'], 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
            <div class="card__wrapper h-100 dashboard-card">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-indigo">
                        <span><i class="fa-light fa-mobile"></i></span>
                    </div>
                    <div class="card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-2 text-muted">Online Payments</h6>
                        <h4 class="card__title mb-0 text-break">৳<?= number_format($statistics['by_payment_method']['online'], 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Recent Transactions -->
    <div class="col-12 mt-3">
        <div class="card__wrapper">
            <div class="card__header d-flex justify-content-between align-items-center mb-3">
                <h5 class="card__title mb-0" id="transactionsTitle">
                    Recent <?= $viewType === 'cashin' ? 'Cash In' : 'Cash Out' ?> Transactions
                </h5>
                <a href="<?= $GLOBALS['base_url'] ?>/<?= $viewType === 'cashin' ? 'clients-cash-in' : 'management-cash-out' ?>" 
                   class="btn btn-sm btn-primary" id="viewAllLink">
                    View All <i class="fa-light fa-arrow-right ms-2"></i>
                </a>
            </div>

            <div class="table__wrapper table-responsive">
                <table class="table mb-20">
                    <thead>
                        <tr class="table__title">
                            <th>Date</th>
                            <th>Description</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="transactionsTableBody">
                        <?php if (!empty($recentTransactions)): ?>
                            <?php foreach ($recentTransactions as $transaction): ?>
                                <tr>
                                    <td><?= date('d M, Y', strtotime($transaction['date'])) ?></td>
                                    <td>
                                        <?php if ($viewType === 'cashin'): ?>
                                            <?= htmlspecialchars($transaction['description'] ?? 'N/A') ?>
                                        <?php else: ?>
                                            <strong><?= htmlspecialchars($transaction['paid_to']) ?></strong>
                                            <?php if (!empty($transaction['description'])): ?>
                                                <br><small class="text-muted"><?= htmlspecialchars($transaction['description']) ?></small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($viewType === 'cashin'): ?>
                                            <span class="bd-badge bg-info">
                                                <?= strtoupper(str_replace('_', ' ', $transaction['type'])) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="bd-badge bg-secondary">
                                                <?= htmlspecialchars($transaction['role_name']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong>৳<?= number_format($transaction['amount'], 2) ?></strong></td>
                                    <td>
                                        <span class="bd-badge bg-light text-dark">
                                            <?= ucfirst($transaction['payment_method']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($transaction['payment_status'] === 'paid'): ?>
                                            <span class="bd-badge bg-success">Paid</span>
                                        <?php elseif ($transaction['payment_status'] === 'pending'): ?>
                                            <span class="bd-badge bg-warning">Pending</span>
                                        <?php else: ?>
                                            <span class="bd-badge bg-danger">Failed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fa-light fa-inbox fa-2x mb-3"></i><br>
                                        No transactions found
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

  
 <script>
document.addEventListener('DOMContentLoaded', function() {

    const viewTypeSelector = document.getElementById('viewTypeSelector');

    viewTypeSelector.addEventListener('change', function () {
        window.location.href = '<?= $GLOBALS['base_url'] ?>/dashboard?view=' + this.value;
    });

    const dynamicChartLabels = <?= json_encode($chartLabels ?? []) ?>;
    const dynamicChartData   = <?= json_encode($chartData ?? []) ?>;

    /* ==========================
        WEEKLY CHART
    ========================== */
    const transactionChartWeek = new ApexCharts(
        document.querySelector("#transactionChartWeek"),
        {
            series: [
                { name: 'Cash In', data: [31000, 40000, 28000, 51000, 42000, 82000, 56000] },
                { name: 'Cash Out', data: [11000, 32000, 45000, 32000, 34000, 52000, 41000] }
            ],
            chart: { height: 350, type: 'area', toolbar: { show: false } },
            stroke: { curve: 'smooth', width: 2 },
            dataLabels: { enabled: false },
            xaxis: { categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] },
            colors: ['#28a745', '#dc3545'],
            yaxis: {
                labels: { formatter: val => '৳' + val.toLocaleString() }
            },
            tooltip: {
                y: { formatter: val => '৳' + val.toLocaleString() }
            },
            fill: {
                type: 'gradient',
                gradient: { opacityFrom: 0.5, opacityTo: 0.1 }
            }
        }
    );
    transactionChartWeek.render();

    /* ==========================
        MONTHLY CHART
    ========================== */
    const transactionChartMonthly = new ApexCharts(
        document.querySelector("#transactionChartMonthly"),
        {
            series: [{
                name: '<?= ucfirst($viewType) ?>',
                data: dynamicChartData.length ? dynamicChartData : [120000, 155000, 142000, 168000]
            }],
            chart: { height: 350, type: 'bar', toolbar: { show: false } },
            plotOptions: {
                bar: { columnWidth: '50%', borderRadius: 6 }
            },
            dataLabels: { enabled: false },
            xaxis: {
                categories: dynamicChartLabels.length
                    ? dynamicChartLabels
                    : ['Jan', 'Feb', 'Mar', 'Apr']
            },
            colors: ['<?= $viewType === "cashin" ? "#28a745" : "#dc3545" ?>'],
            yaxis: {
                labels: { formatter: val => '৳' + val.toLocaleString() }
            }
        }
    );
    transactionChartMonthly.render();

    /* ==========================
        YEARLY CHART
    ========================== */
    const transactionChartYearly = new ApexCharts(
        document.querySelector("#transactionChartYearly"),
        {
            series: [{
                name: '<?= ucfirst($viewType) ?>',
                data: dynamicChartData.length ? dynamicChartData : [1250000, 1420000, 1380000]
            }],
            chart: { height: 350, type: 'line', toolbar: { show: false } },
            stroke: { width: 3, curve: 'smooth' },
            xaxis: {
                categories: dynamicChartLabels.length
                    ? dynamicChartLabels
                    : ['2023', '2024', '2025']
            },
            colors: ['<?= $viewType === "cashin" ? "#28a745" : "#dc3545" ?>'],
            yaxis: {
                labels: { formatter: val => '৳' + val.toLocaleString() }
            }
        }
    );
    transactionChartYearly.render();

    /* ==========================
        PAYMENT METHODS PIE
    ========================== */
    const paymentMethodsChart = new ApexCharts(
        document.querySelector("#paymentMethodsChart"),
        {
            series: <?= json_encode(array_values($statistics['by_payment_method'] ?? [])) ?>,
            chart: { type: 'donut', height: 320 },
            labels: <?= json_encode(array_map('ucfirst', array_keys($statistics['by_payment_method'] ?? []))) ?>,
            legend: { position: 'bottom' },
            dataLabels: {
                formatter: val => val.toFixed(1) + '%'
            }
        }
    );
    paymentMethodsChart.render();

});
</script>
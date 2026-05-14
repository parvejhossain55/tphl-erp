<?php
$page_title = "Dashboard";
$page = 'Dashboard';
$show_breadcrumb = false;

// Prepare chart data from monthlyData - FIXED: Use variables passed from controller
$chartLabels = $chartLabels ?? [];
$chartData = $chartData ?? [];

// If no chart data passed, try to generate from monthlyData
if (empty($chartLabels) && !empty($monthlyData)) {
    foreach ($monthlyData as $data) {
        $chartLabels[] = date('M Y', strtotime($data['month'] . '-01'));
        $chartData[] = floatval($data['total']);
    }
}

// Period display text - CHANGED: Default to 'all_time'
$periodText = '';
switch ($period ?? 'all_time') {
    case 'daily':
        $periodText = 'Today';
        break;
    case 'weekly':
        $periodText = 'This Week';
        break;
    case 'monthly':
        $periodText = 'This Month';
        break;
    case 'yearly':
        $periodText = 'This Year';
        break;
    case 'all_time':
        $periodText = 'All Time';
        break;
    default:
        $periodText = 'All Time';
        break;
}

// Weekly data for charts
$weeklyCashInData = $weeklyCashInDataArray ?? [0, 0, 0, 0, 0, 0, 0];
$weeklyCashOutData = $weeklyCashOutDataArray ?? [0, 0, 0, 0, 0, 0, 0];

// Payment method data
$paymentMethodLabels = ['Cash', 'Bank', 'Cheque', 'Online'];
$paymentMethodData = [];
if (isset($statistics['by_payment_method'])) {
    $paymentMethodData = [
        $statistics['by_payment_method']['cash'] ?? 0,
        $statistics['by_payment_method']['bank'] ?? 0,
        $statistics['by_payment_method']['cheque'] ?? 0,
        $statistics['by_payment_method']['online'] ?? 0
    ];
} else {
    $paymentMethodData = [0, 0, 0, 0];
}

// Check if we have any data to show
$hasTransactions = !empty($recentTransactions);

// Helper function to get status badge
function getStatusBadge($status)
{
    switch ($status) {
        case 'pending':
            return '<span class="bd-badge bg-warning">Pending</span>';
        case 'approved':
            return '<span class="bd-badge bg-success">Approved</span>';
        case 'rejected':
            return '<span class="bd-badge bg-danger">Rejected</span>';
        case 'partially_paid':
            return '<span class="bd-badge bg-info">Partially Paid</span>';
        case 'fully_paid':
            return '<span class="bd-badge bg-success">Fully Paid</span>';
        default:
            return '<span class="bd-badge bg-secondary">' . ucfirst($status) . '</span>';
    }
}
?>

<style>
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

    .height-equal {
        height: 100%;
        padding-bottom: 0;
    }

    .payment-method-chart {
        margin-top: 58px;
    }

    .chart-container {
        position: relative;
        height: 350px;
    }

    .period-badge {
        font-size: 12px;
        padding: 2px 8px;
        border-radius: 4px;
        background: #e9ecef;
        color: #6c757d;
        font-weight: 600;
    }

    @media (max-width: 575px) {
        .bd-badge {
            min-width: 118px;
        }
    }

    .no-data-placeholder {
        height: 350px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        color: #6c757d;
    }

    .no-data-placeholder i {
        font-size: 48px;
        margin-bottom: 15px;
        opacity: 0.5;
    }

    /* ── Operations Quick-Link Cards ── */
    .ops-card {
        transition: all 0.25s ease;
        border-left: 3px solid transparent;
        cursor: pointer;
    }
    .ops-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.08);
        border-left-color: var(--clr-theme-primary, #6366f1);
    }

    /* Color utility helpers */
    .icon-success span  { color: #10b981; background: rgba(16,185,129,0.12); }
    .icon-primary span  { color: #6366f1; background: rgba(99,102,241,0.12); }
    .icon-danger span   { color: #ef4444; background: rgba(239,68,68,0.12);  }
    .icon-warning span  { color: #f59e0b; background: rgba(245,158,11,0.12); }
    .icon-info span     { color: #06b6d4; background: rgba(6,182,212,0.12);  }
    .icon-purple span   { color: #a855f7; background: rgba(168,85,247,0.12); }
    .icon-teal span     { color: #14b8a6; background: rgba(20,184,166,0.12); }
    .icon-orange span   { color: #f97316; background: rgba(249,115,22,0.12); }
    .icon-indigo span   { color: #6366f1; background: rgba(99,102,241,0.12); }

    .text-purple { color: #a855f7 !important; }
    .text-teal   { color: #14b8a6 !important; }
    .text-orange { color: #f97316 !important; }
</style>

<div class="row">
    <!-- View Type Selector and Period Filter -->
    <div class="col-12 mb-4">
        <div class="card__wrapper">
            <div class="card__body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h4 class="mb-0">Financial Dashboard</h4>
                        <p class="text-muted mb-0">Overview of your financial transactions</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <label class="mb-0 fw-bold">View:</label>
                        <select id="viewTypeSelector" class="form-select" style="width: auto; min-width: 150px;">
                            <option value="cashin" <?= $viewType === 'cashin' ? 'selected' : '' ?>>Cash In</option>
                            <option value="cashout" <?= $viewType === 'cashout' ? 'selected' : '' ?>>Cash Out</option>
                        </select>
                    </div>
                </div>

                <!-- Period Filter Buttons -->
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="btn__group mb-3">
                            <a href="?view=<?= $viewType ?>&period=daily" class="btn btn-sm <?= ($period ?? 'all_time') == 'daily' ? 'btn-primary' : 'btn-outline-secondary' ?>">Daily</a>
                            <a href="?view=<?= $viewType ?>&period=weekly" class="btn btn-sm <?= ($period ?? 'all_time') == 'weekly' ? 'btn-primary' : 'btn-outline-secondary' ?>">Weekly</a>
                            <a href="?view=<?= $viewType ?>&period=monthly" class="btn btn-sm <?= ($period ?? 'all_time') == 'monthly' ? 'btn-primary' : 'btn-outline-secondary' ?>">Monthly</a>
                            <a href="?view=<?= $viewType ?>&period=yearly" class="btn btn-sm <?= ($period ?? 'all_time') == 'yearly' ? 'btn-primary' : 'btn-outline-secondary' ?>">Yearly</a>
                            <a href="?view=<?= $viewType ?>&period=all_time" class="btn btn-sm <?= ($period ?? 'all_time') == 'all_time' ? 'btn-primary' : 'btn-outline-secondary' ?>">All Time</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Overview Statistics (Always Visible) -->
    <div class="col-12 mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-3">
                <i class="fa-light fa-chart-line me-2"></i>Overall Summary
            </h5>
            <span class="period-badge"><?= $periodText ?></span>
        </div>
    </div>

    <!-- Overview Cards Row -->
    <?php if ($viewType === 'cashin'): ?>
    <div class="col-xl-4 col-lg-6 col-md-6 col-sm-6 mb-3">
        <div class="card__wrapper h-100 dashboard-card card-success">
            <div class="d-flex align-items-center gap-2 h-100">
                <div class="card__icon icon-success">
                    <span><i class="fa-light fa-arrow-down"></i></span>
                </div>
                <div class="card__title-wrap flex-grow-1 overflow-hidden">
                    <h6 class="card__sub-title mb-2 text-muted">Total Cash In</h6>
                    <h4 class="card__title mb-0 text-success text-break">TK.<?= number_format($overviewStats['total_cashin'] ?? 0, 2) ?></h4>
                    <small class="text-muted" style="font-size: 0.7rem;">Payments Received</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-lg-6 col-md-6 col-sm-6 mb-3">
        <div class="card__wrapper h-100 dashboard-card card-primary">
            <div class="d-flex align-items-center gap-2 h-100">
                <div class="card__icon icon-primary">
                    <span><i class="fa-light fa-file-contract"></i></span>
                </div>
                <div class="card__title-wrap flex-grow-1 overflow-hidden">
                    <h6 class="card__sub-title mb-2 text-muted ">Total Revenue</h6>
                    <h4 class="card__title mb-0 text-success text-break">TK.<?= number_format($overviewStats['total_revenue'] ?? 0, 2) ?></h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-lg-6 col-md-6 col-sm-6 mb-3">
        <div class="card__wrapper h-100 dashboard-card card-danger">
            <div class="d-flex align-items-center gap-2 h-100">
                <div class="card__icon icon-danger">
                    <span><i class="fa-light fa-arrow-up"></i></span>
                </div>
                <div class="card__title-wrap flex-grow-1 overflow-hidden">
                    <h6 class="card__sub-title mb-2 text-muted">Total Expenses</h6>
                    <h4 class="card__title mb-0 text-danger text-break">TK.<?= number_format($overviewStats['total_cashout'] ?? 0, 2) ?></h4>
                    <small class="text-muted" style="font-size: 0.7rem;">All Outflows</small>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="col-xl-4 col-lg-6 col-md-6 col-sm-6 mb-3">
        <div class="card__wrapper h-100 dashboard-card card-danger">
            <div class="d-flex align-items-center gap-2 h-100">
                <div class="card__icon icon-danger">
                    <span><i class="fa-light fa-arrow-up"></i></span>
                </div>
                <div class="card__title-wrap flex-grow-1 overflow-hidden">
                    <h6 class="card__sub-title mb-2 text-muted">Total Expenses</h6>
                    <h4 class="card__title mb-0 text-danger text-break">TK.<?= number_format($overviewStats['total_cashout'] ?? 0, 2) ?></h4>
                    <small class="text-muted" style="font-size: 0.7rem;">All Outflows</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-lg-6 col-md-6 col-sm-6 mb-3">
        <div class="card__wrapper h-100 dashboard-card card-warning">
            <div class="d-flex align-items-center gap-2 h-100">
                <div class="card__icon icon-warning">
                    <span><i class="fa-light fa-money-bill"></i></span>
                </div>
                <div class="card__title-wrap flex-grow-1 overflow-hidden">
                    <h6 class="card__sub-title mb-2 text-muted">Cashouts</h6>
                    <h4 class="card__title mb-0 text-warning text-break">TK.<?= number_format($statistics['management_cashout'] ?? 0, 2) ?></h4>
                    <small class="text-muted" style="font-size: 0.7rem;">Operational Expenses</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-lg-6 col-md-6 col-sm-6 mb-3">
        <div class="card__wrapper h-100 dashboard-card card-info">
            <div class="d-flex align-items-center gap-2 h-100">
                <div class="card__icon icon-info">
                    <span><i class="fa-light fa-users"></i></span>
                </div>
                <div class="card__title-wrap flex-grow-1 overflow-hidden">
                    <h6 class="card__sub-title mb-2 text-muted">Salary Payments</h6>
                    <h4 class="card__title mb-0 text-info text-break">TK.<?= number_format($statistics['salary_payments'] ?? 0, 2) ?></h4>
                    <small class="text-muted" style="font-size: 0.7rem;">Payroll</small>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ══ ACCOUNTS OPERATIONS — Quick-Link Cards ══ -->
    <div class="col-12 mb-3 mt-2">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-3">
                <i class="fa-light fa-grid-2 me-2"></i>Accounts Operations
            </h5>
            <span class="period-badge">Actionable Items</span>
        </div>
    </div>

    <?php $ops = $operationsStats ?? []; ?>

    <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 mb-3">
        <a href="<?= $GLOBALS['base_url'] ?>/clients-cash-in" class="text-decoration-none">
            <div class="card__wrapper h-100 dashboard-card ops-card">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-warning">
                        <span><i class="fa-light fa-shield-check"></i></span>
                    </div>
                    <div class="card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-1 text-muted" style="font-size:0.75rem;">Pending Verifications</h6>
                        <h4 class="card__title mb-0"><?= $ops['pending_verifications'] ?? 0 ?></h4>
                        <small class="text-warning" style="font-size:0.68rem;">TK.<?= number_format($ops['pending_verification_amt'] ?? 0, 0) ?></small>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 mb-3">
        <a href="<?= $GLOBALS['base_url'] ?>/con-vendor-payment-requests" class="text-decoration-none">
            <div class="card__wrapper h-100 dashboard-card ops-card">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-purple">
                        <span><i class="fa-light fa-truck"></i></span>
                    </div>
                    <div class="card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-1 text-muted" style="font-size:0.75rem;">Vendor Payments</h6>
                        <h4 class="card__title mb-0"><?= $ops['pending_vendor_payments'] ?? 0 ?></h4>
                        <small class="text-purple" style="font-size:0.68rem;">TK.<?= number_format($ops['pending_vendor_amt'] ?? 0, 0) ?></small>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 mb-3">
        <a href="<?= $GLOBALS['base_url'] ?>/con-contractor-bills" class="text-decoration-none">
            <div class="card__wrapper h-100 dashboard-card ops-card">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-teal">
                        <span><i class="fa-light fa-helmet-safety"></i></span>
                    </div>
                    <div class="card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-1 text-muted" style="font-size:0.75rem;">Contractor Bills</h6>
                        <h4 class="card__title mb-0"><?= $ops['pending_contractor_bills'] ?? 0 ?></h4>
                        <small class="text-teal" style="font-size:0.68rem;">TK.<?= number_format($ops['pending_contractor_amt'] ?? 0, 0) ?></small>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 mb-3">
        <a href="<?= $GLOBALS['base_url'] ?>/admin-cashout-requests" class="text-decoration-none">
            <div class="card__wrapper h-100 dashboard-card ops-card">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-danger">
                        <span><i class="fa-light fa-arrow-up-from-bracket"></i></span>
                    </div>
                    <div class="card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-1 text-muted" style="font-size:0.75rem;">Cashout Requests</h6>
                        <h4 class="card__title mb-0"><?= $ops['pending_cashout_requests'] ?? 0 ?></h4>
                        <small class="text-danger" style="font-size:0.68rem;">TK.<?= number_format($ops['pending_cashout_amt'] ?? 0, 0) ?></small>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 mb-3">
        <a href="<?= $GLOBALS['base_url'] ?>/client-refunds" class="text-decoration-none">
            <div class="card__wrapper h-100 dashboard-card ops-card">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-orange">
                        <span><i class="fa-light fa-arrow-rotate-left"></i></span>
                    </div>
                    <div class="card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-1 text-muted" style="font-size:0.75rem;">Client Refunds</h6>
                        <h4 class="card__title mb-0"><?= $ops['pending_refunds'] ?? 0 ?></h4>
                        <small class="text-orange" style="font-size:0.68rem;">TK.<?= number_format($ops['pending_refund_amt'] ?? 0, 0) ?></small>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 mb-3">
        <a href="<?= $GLOBALS['base_url'] ?>/central-statement" class="text-decoration-none">
            <div class="card__wrapper h-100 dashboard-card ops-card">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-info">
                        <span><i class="fa-light fa-users"></i></span>
                    </div>
                    <div class="card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-1 text-muted" style="font-size:0.75rem;">Total Clients</h6>
                        <h4 class="card__title mb-0"><?= $ops['total_clients'] ?? 0 ?></h4>
                        <small class="text-info" style="font-size:0.68rem;">Active Bookings</small>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Charts Section -->
    <div class="col-xxl-8 col-xl-12 col-lg-12 mb-3">
        <div class="card__wrapper height-equal">
            <div class="card__title-wrap d-flex flex-wrap gap-10 align-items-center justify-content-between mb-50">
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
                        <div class="chart-container">
                            <?php if (!empty($chartLabels)): ?>
                                <canvas id="transactionChartYearly" class="card__title"></canvas>
                            <?php else: ?>
                                <div class="no-data-placeholder">
                                    <i class="fa-light fa-chart-line"></i>
                                    <p>No yearly data available</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="pills-bar-month" role="tabpanel"
                        aria-labelledby="pills-bar-month-tab">
                        <div class="chart-container">
                            <?php if (!empty($chartLabels)): ?>
                                <canvas id="transactionChartMonthly" class="card__title"></canvas>
                            <?php else: ?>
                                <div class="no-data-placeholder">
                                    <i class="fa-light fa-chart-line"></i>
                                    <p>No monthly data available</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="tab-pane fade show active" id="pills-bar-week" role="tabpanel"
                        aria-labelledby="pills-bar-week-tab">
                        <div class="chart-container">
                            <canvas id="transactionChartWeek" class="card__title"></canvas>
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
                <span class="period-badge"><?= $periodText ?></span>
            </div>
            <div class="payment-method-chart card__content">
                <div class="chart-container">
                    <?php if (array_sum($paymentMethodData) > 0): ?>
                        <canvas id="paymentMethodsChart"></canvas>
                    <?php else: ?>
                        <div class="no-data-placeholder">
                            <i class="fa-light fa-credit-card"></i>
                            <p>No payment method data</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Dynamic Statistics Section Header -->
    <div class="col-12 mb-3 mt-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-3" id="sectionTitle">
                <i class="fa-light <?= $viewType === 'cashin' ? 'fa-arrow-down' : 'fa-arrow-up' ?> me-2"></i>
                <span id="sectionTitleText"><?= $viewType === 'cashin' ? 'Cash In Details' : 'Cash Out Details' ?></span>
            </h5>
            <span class="period-badge"><?= $periodText ?></span>
        </div>
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
                        <h4 class="card__title mb-0 text-break">TK.<?= number_format($statistics['lead_payments'] ?? 0, 2) ?></h4>
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
                        <h6 class="card__sub-title mb-2 text-muted">Total Cash In</h6>
                        <h4 class="card__title mb-0 text-break">TK.<?= number_format($overviewStats['total_cashin'] ?? 0, 2) ?></h4>
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
                        <h6 class="card__sub-title mb-2 text-muted">Land Share <br><small>(Booking + Registration)</small></h6>
                        <h4 class="card__title mb-0 text-break">TK.<?= number_format(($statistics['land_share_payments'] ?? 0) + ($statistics['booking_money'] ?? 0) + ($statistics['registration_money'] ?? 0), 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
            <div class="card__wrapper h-100 dashboard-card">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-primary">
                        <span><i class="fa-light fa-tools"></i></span>
                    </div>
                    <div class="card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-2 text-muted">Development Charges</h6>
                        <h4 class="card__title mb-0 text-break">TK.<?= number_format($statistics['development_charges'] ?? 0, 2) ?></h4>
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
                        <h4 class="card__title mb-0 text-break">TK.<?= number_format($statistics['emi_payments'] ?? 0, 2) ?></h4>
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
                        <h4 class="card__title mb-0 text-break">TK.<?= number_format($statistics['this_month'] ?? 0, 2) ?></h4>
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
                        <h4 class="card__title mb-0 text-warning text-break">TK.<?= number_format($statistics['pending_amount'] ?? 0, 2) ?></h4>
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
                        <h4 class="card__title mb-0 text-break"><?= number_format($statistics['total_transactions'] ?? 0) ?></h4>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Cash Out Statistics (CORRECTED - REMOVED DUPLICATE) -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
            <div class="card__wrapper h-100 dashboard-card">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-success">
                        <span><i class="fa-light fa-money-check-dollar"></i></span>
                    </div>
                    <div class="card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-2 text-muted">Paid Amount</h6>
                        <h4 class="card__title mb-0 text-break">TK.<?= number_format($statistics['paid_amount'] ?? 0, 2) ?></h4>
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
                        <h4 class="card__title mb-0 text-warning text-break">TK.<?= number_format($statistics['pending_amount'] ?? 0, 2) ?></h4>
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
                        <h4 class="card__title mb-0 text-break">TK.<?= number_format($statistics['this_month'] ?? 0, 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
            <div class="card__wrapper h-100 dashboard-card">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-info">
                        <span><i class="fa-light fa-tools"></i></span>
                    </div>
                    <div class="card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-2 text-muted">Contractor Bills</h6>
                        <h4 class="card__title mb-0 text-break">TK.<?= number_format($statistics['contractor_bills'] ?? 0, 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
            <div class="card__wrapper h-100 dashboard-card">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-purple">
                        <span><i class="fa-light fa-truck-fast"></i></span>
                    </div>
                    <div class="card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-2 text-muted">Vendor Payments</h6>
                        <h4 class="card__title mb-0 text-break">TK.<?= number_format($statistics['vendor_payments'] ?? 0, 2) ?></h4>
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
                        <h6 class="card__sub-title mb-2 text-muted">Total Requests</h6>
                        <h4 class="card__title mb-0 text-break"><?= number_format($statistics['total_transactions'] ?? 0) ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
            <div class="card__wrapper h-100 dashboard-card">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-success">
                        <span><i class="fa-light fa-money-bill"></i></span>
                    </div>
                    <div class="card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-2 text-muted">Cash Payments</h6>
                        <h4 class="card__title mb-0 text-break">TK.<?= number_format($statistics['by_payment_method']['cash'] ?? 0, 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
            <div class="card__wrapper h-100 dashboard-card">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-purple">
                        <span><i class="fa-light fa-building-columns"></i></span>
                    </div>
                    <div class="card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-2 text-muted">Bank Transfers</h6>
                        <h4 class="card__title mb-0 text-break">TK.<?= number_format($statistics['by_payment_method']['bank'] ?? 0, 2) ?></h4>
                    </div>
                </div>
            </div>
        </div> -->

        <!-- <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
            <div class="card__wrapper h-100 dashboard-card">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-teal">
                        <span><i class="fa-light fa-money-check"></i></span>
                    </div>
                    <div class="card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-2 text-muted">Cheque Payments</h6>
                        <h4 class="card__title mb-0 text-break">TK.<?= number_format($statistics['by_payment_method']['cheque'] ?? 0, 2) ?></h4>
                    </div>
                </div>
            </div>
        </div> -->

        <!-- <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
            <div class="card__wrapper h-100 dashboard-card">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-indigo">
                        <span><i class="fa-light fa-mobile"></i></span>
                    </div>
                    <div class="card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-2 text-muted">Online Payments</h6>
                        <h4 class="card__title mb-0 text-break">TK.<?= number_format($statistics['by_payment_method']['online'] ?? 0, 2) ?></h4>
                    </div>
                </div>
            </div>
        </div> -->

    <?php endif; ?>

    <!-- Recent Transactions / Cashout Requests -->
    <div class="col-12 mt-3">
        <div class="card__wrapper">
            <div class="card__header d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="card__title mb-0" id="transactionsTitle">
                        <?php if ($viewType === 'cashin'): ?>
                            Recent Cash In Transactions
                        <?php else: ?>
                            Recent Cashout Requests
                        <?php endif; ?>
                    </h5>
                    <small class="text-muted"><?= $periodText ?></small>
                </div>
                <a href="<?= $GLOBALS['base_url'] ?>/<?= $viewType === 'cashin' ? 'clients-cash-in' : 'cashout-requests' ?>"
                    class="btn btn-sm btn-primary" id="viewAllLink">
                    View All <i class="fa-light fa-arrow-right ms-2"></i>
                </a>
            </div>

            <div class="table__wrapper table-responsive">
                <?php if ($viewType === 'cashin'): ?>
                    <!-- Cash In Transactions Table -->
                    <table class="table mb-20">
                        <thead>
                            <tr class="table__title">
                                <th>Date</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Transaction ID</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="transactionsTableBody">
                            <?php if ($hasTransactions): ?>
                                <?php foreach ($recentTransactions as $transaction): ?>
                                    <tr>
                                        <td><?= date('d M, Y', strtotime($transaction['date'])) ?></td>
                                        <td><?= htmlspecialchars($transaction['description'] ?? 'N/A') ?></td>
                                        <td><strong>TK.<?= number_format($transaction['amount'], 2) ?></strong></td>
                                        <td>
                                            <span class="bd-badge bg-light text-dark">
                                                <?= ucfirst($transaction['payment_method'] ?? 'N/A') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <code style="font-size: 11px; background: #f8f9fa; padding: 4px 8px; border-radius: 4px;">
                                                <?= htmlspecialchars($transaction['transaction_ref'] ?? 'N/A') ?>
                                            </code>
                                        </td>
                                        <td>
                                            <span class="bd-badge bg-success">Paid</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fa-light fa-inbox fa-2x mb-3"></i><br>
                                            No transactions found for <?= $periodText ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <!-- Cashout Requests Table -->
                    <table class="table mb-20">
                        <thead>
                            <tr class="table__title">
                                <th>Request Date</th>
                                <th>Requested By</th>
                                <th>Purpose / Paid To</th>
                                <th>Requested Amount</th>
                                <th>Approved Amount</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="transactionsTableBody">
                            <?php if ($hasTransactions): ?>
                                <?php foreach ($recentTransactions as $request): ?>
                                    <tr>
                                        <td>
                                            <?php
                                            // FIX: Check if 'created_at' exists, fall back to 'date' or current date
                                            $date = $request['created_at'] ?? $request['date'] ?? date('Y-m-d');
                                            echo date('d M, Y', strtotime($date));
                                            ?>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($request['requester_name'] ?? $request['person_name'] ?? $request['role_name'] ?? 'N/A') ?></strong>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($request['purpose_paid_to'] ?? $request['paid_to'] ?? 'N/A') ?></strong>
                                            <?php if (!empty($request['description'])): ?>
                                                <br><small class="text-muted"><?= htmlspecialchars(substr($request['description'], 0, 50)) ?><?= strlen($request['description']) > 50 ? '...' : '' ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong>TK.<?= number_format($request['amount'], 2) ?></strong></td>
                                        <td>
                                            <?php if (!empty($request['approved_amount'])): ?>
                                                <strong class="text-success">TK.<?= number_format($request['approved_amount'], 2) ?></strong>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="bd-badge bg-light text-dark">
                                                <?= ucfirst($request['payment_method'] ?? 'N/A') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            // FIX: Check for status in multiple possible fields
                                            $status = $request['display_status'] ?? $request['payment_status'] ?? 'pending';
                                            echo getStatusBadge($status);
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fa-light fa-inbox fa-2x mb-3"></i><br>
                                            No cashout requests found for <?= $periodText ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const viewTypeSelector = document.getElementById('viewTypeSelector');
        const periodButtons = document.querySelectorAll('.btn__group .btn');

        // View type change handler
        viewTypeSelector.addEventListener('change', function() {
            const url = new URL(window.location.href);
            url.searchParams.set('view', this.value);
            window.location.href = url.toString();
        });

        // Period button active state
        periodButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                const url = new URL(this.href);
                url.searchParams.set('view', '<?= $viewType ?>');
                this.href = url.toString();
            });
        });

        // Chart data from PHP
        const dynamicChartLabels = <?= json_encode($chartLabels) ?>;
        const dynamicChartData = <?= json_encode($chartData) ?>;
        const dynamicYearlyLabels = <?= json_encode($yearlyLabels) ?>;
        const dynamicYearlyChartData = <?= json_encode($yearlyChartData) ?>;
        const paymentMethodLabels = <?= json_encode($paymentMethodLabels) ?>;
        const paymentMethodData = <?= json_encode($paymentMethodData) ?>;

        // Currency formatter
        const formatCurrency = (value) => 'TK.' + value.toLocaleString();

        // Modern chart color palette
        const colors = {
            primary: '#6366f1',
            success: '#10b981',
            danger: '#ef4444',
            warning: '#f59e0b',
            info: '#06b6d4',
            purple: '#a855f7',
            pink: '#ec4899',
            doughnut: [
                '#8b5cf6',
                '#f02345ff',
                '#10b981',
                '#f59e0b',
                '#ef4444',
                '#ec4899'
            ]
        };

        /* ==========================
            WEEKLY CHART (Animated Area Chart)
        ========================== */
        const ctxWeek = document.getElementById('transactionChartWeek');
        if (ctxWeek) {
            const transactionChartWeek = new Chart(ctxWeek.getContext('2d'), {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                            label: 'Cash In',
                            data: <?= json_encode($weeklyCashInData) ?>,
                            borderColor: colors.success,
                            backgroundColor: colors.success + '15',
                            fill: true,
                            tension: 0.4,
                            borderWidth: 3,
                            pointRadius: 0,
                            pointHoverRadius: 6,
                            pointHoverBackgroundColor: colors.success,
                            pointHoverBorderColor: '#fff',
                            pointHoverBorderWidth: 2
                        },
                        {
                            label: 'Cash Out',
                            data: <?= json_encode($weeklyCashOutData) ?>,
                            borderColor: colors.danger,
                            backgroundColor: colors.danger + '15',
                            fill: true,
                            tension: 0.4,
                            borderWidth: 3,
                            pointRadius: 0,
                            pointHoverRadius: 6,
                            pointHoverBackgroundColor: colors.danger,
                            pointHoverBorderColor: '#fff',
                            pointHoverBorderWidth: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    animation: {
                        duration: 2000,
                        easing: 'easeOutQuart'
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: {
                                    size: 13,
                                    weight: '500'
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + formatCurrency(context.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            border: {
                                display: false
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                drawTicks: false
                            },
                            ticks: {
                                padding: 10,
                                callback: function(value) {
                                    return formatCurrency(value);
                                }
                            }
                        },
                        x: {
                            border: {
                                display: false
                            },
                            grid: {
                                display: false
                            },
                            ticks: {
                                padding: 10
                            }
                        }
                    }
                }
            });
        }

        /* ==========================
            MONTHLY CHART (Animated Bar Chart)
        ========================== */
        const ctxMonth = document.getElementById('transactionChartMonthly');
        if (ctxMonth) {
            const monthlyLabels = dynamicChartLabels.length ? dynamicChartLabels : ['Jan', 'Feb', 'Mar', 'Apr'];
            const monthlyData = dynamicChartData.length ? dynamicChartData : [120000, 155000, 142000, 168000];
            const monthlyColor = '<?= $viewType === "cashin" ? "#6366f1" : "#ef4444" ?>';

            const transactionChartMonthly = new Chart(ctxMonth.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: monthlyLabels,
                    datasets: [{
                        label: '<?= ucfirst($viewType) ?>',
                        data: monthlyData,
                        backgroundColor: monthlyColor,
                        borderRadius: 8,
                        borderSkipped: false,
                        barThickness: 35,
                        maxBarThickness: 40
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animations: {
                        y: {
                            duration: 1500,
                            from: 0,
                            easing: 'easeOutBounce'
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    return formatCurrency(context.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            border: {
                                display: false
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                drawTicks: false
                            },
                            ticks: {
                                padding: 10,
                                callback: function(value) {
                                    return formatCurrency(value);
                                }
                            }
                        },
                        x: {
                            border: {
                                display: false
                            },
                            grid: {
                                display: false
                            },
                            ticks: {
                                padding: 10
                            }
                        }
                    }
                }
            });
        }

        /* ==========================
            YEARLY CHART (Animated Line Chart)
        ========================== */
        const ctxYear = document.getElementById('transactionChartYearly');
        if (ctxYear) {
            const yearlyLabels = dynamicYearlyLabels.length ? dynamicYearlyLabels : ['2023', '2024', '2025'];
            const yearlyData = dynamicYearlyChartData.length ? dynamicYearlyChartData : [1250000, 1420000, 1380000];
            const yearlyColor = '<?= $viewType === "cashin" ? "#6366f1" : "#ef4444" ?>';

            const transactionChartYearly = new Chart(ctxYear.getContext('2d'), {
                type: 'line',
                data: {
                    labels: yearlyLabels,
                    datasets: [{
                        label: '<?= ucfirst($viewType) ?>',
                        data: yearlyData,
                        borderColor: yearlyColor,
                        backgroundColor: yearlyColor + '15',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointRadius: 6,
                        pointBackgroundColor: yearlyColor,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 3,
                        pointHoverRadius: 8,
                        pointHoverBackgroundColor: yearlyColor,
                        pointHoverBorderColor: '#fff',
                        pointHoverBorderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 2000,
                        easing: 'easeOutQuart'
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    return formatCurrency(context.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            border: {
                                display: false
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                drawTicks: false
                            },
                            ticks: {
                                padding: 10,
                                callback: function(value) {
                                    return formatCurrency(value);
                                }
                            }
                        },
                        x: {
                            border: {
                                display: false
                            },
                            grid: {
                                display: false
                            },
                            ticks: {
                                padding: 10
                            }
                        }
                    }
                }
            });
        }

        /* ==========================
            PAYMENT METHODS CHART (Animated Pie Chart)
        ========================== */
        const ctxPayment = document.getElementById('paymentMethodsChart');
        if (ctxPayment) {
            const paymentMethodsChart = new Chart(ctxPayment.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: paymentMethodLabels,
                    datasets: [{
                        data: paymentMethodData,
                        backgroundColor: colors.doughnut,
                        hoverBorderWidth: 5,
                        hoverOffset: 15
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animations: {
                        animateRotate: true,
                        animateScale: true,
                        duration: 2000,
                        easing: 'easeOutQuart'
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 15,
                                font: {
                                    size: 12
                                },
                                color: '#6b7280'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return label + ': ' + formatCurrency(value) + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
    });
</script>
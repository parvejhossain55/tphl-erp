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
        min-height: 350px;
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
                    <h4 class="card__title mb-0 text-warning text-break">TK.<?= number_format(($statistics['paid_amount'] ?? 0) - ($statistics['salary_payments'] ?? 0), 2) ?></h4>
                    <small class="text-muted" style="font-size: 0.7rem;">Operational & Project Outflows</small>
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
        <a href="<?= $GLOBALS['base_url'] ?>/con-land-requisitions" class="text-decoration-none">
            <div class="card__wrapper h-100 dashboard-card ops-card" style="border-left: 3px solid #10b981;">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-success">
                        <span><i class="fa-light fa-file-invoice-dollar"></i></span>
                    </div>
                    <div class="card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-1 text-muted" style="font-size:0.75rem;">Land Requisitions</h6>
                        <h4 class="card__title mb-0"><?= $ops['pending_land_requisitions'] ?? 0 ?></h4>
                        <small class="text-success" style="font-size:0.68rem;">TK.<?= number_format($ops['pending_land_requisition_amt'] ?? 0, 0) ?></small>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 mb-3">
        <a href="<?= $GLOBALS['base_url'] ?>/fund-requisitions" class="text-decoration-none">
            <div class="card__wrapper h-100 dashboard-card ops-card" style="border-left: 3px solid #0d6efd;">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-info">
                        <span><i class="fa-light fa-wallet"></i></span>
                    </div>
                    <div class="card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-1 text-muted" style="font-size:0.75rem;">Fund Requisitions</h6>
                        <h4 class="card__title mb-0"><?= $ops['pending_petty_cash'] ?? 0 ?></h4>
                        <small class="text-info" style="font-size:0.68rem;">TK.<?= number_format($ops['pending_petty_cash_amt'] ?? 0, 0) ?></small>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <?php if (!empty($accountsNotifications)): ?>
    <!-- ══════════════ ACCOUNTS NOTIFICATION BANNER ══════════════ -->
    <div class="col-12 mb-4 mt-2">
        <div class="d-flex align-items-center gap-2 mb-3">
            <div style="width:4px;height:22px;background:linear-gradient(180deg,#3b82f6,#8b5cf6);border-radius:4px"></div>
            <h6 class="mb-0 fw-bold" style="font-size:14px;">
                <i class="fa-light fa-bell me-2 text-primary"></i>
                Actionable Requests & Updates
            </h6>
            <span class="badge" style="background:linear-gradient(135deg,#3b82f6,#8b5cf6);color:#fff;font-size:11px;">
                <?= count($accountsNotifications) ?> Item<?= count($accountsNotifications) > 1 ? 's' : '' ?>
            </span>
        </div>

        <div class="row g-3">
            <?php foreach (array_slice($accountsNotifications, 0, 8) as $notif): ?>
            <?php
                $statusColor = match(strtolower($notif['status'])) {
                    'pending', 'submitted' => ['bg' => 'rgba(245,158,11,.10)', 'border' => '#f59e0b', 'badge' => '#f59e0b', 'text' => '#b45309', 'icon' => 'fa-clock'],
                    'qc_approved', 'verified' => ['bg' => 'rgba(16,185,129,.10)', 'border' => '#10b981', 'badge' => '#10b981', 'text' => '#047857', 'icon' => 'fa-check-double'],
                    'approved'  => ['bg' => 'rgba(34,197,94,.12)', 'border' => '#22c55e', 'badge' => '#22c55e', 'text' => '#15803d', 'icon' => 'fa-check-circle'],
                    'partially_paid' => ['bg' => 'rgba(59,130,246,.10)', 'border' => '#3b82f6', 'badge' => '#3b82f6', 'text' => '#1d4ed8', 'icon' => 'fa-coins'],
                    'fully_paid', 'paid' => ['bg' => 'rgba(45,198,83,.12)', 'border' => '#2dc653', 'badge' => '#2dc653', 'text' => '#15803d', 'icon' => 'fa-check'],
                    'rejected'  => ['bg' => 'rgba(239,68,68,.10)', 'border' => '#ef4444', 'badge' => '#ef4444', 'text' => '#b91c1c', 'icon' => 'fa-times'],
                    default     => ['bg' => 'rgba(107,114,128,.10)', 'border' => '#6b7280', 'badge' => '#6b7280', 'text' => '#374151', 'icon' => 'fa-info-circle'],
                };
                
                $typeIcon = match($notif['source_table']) {
                    'cashout_requests' => 'fa-money-bill-transfer text-danger',
                    'operational_fund_requests' => 'fa-money-bills text-success',
                    'contractor_bill_requests' => 'fa-helmet-safety text-teal',
                    'vendor_payment_requests' => 'fa-truck text-purple',
                    'expense_adjustments' => 'fa-calculator text-warning',
                    default => 'fa-file-invoice'
                };
                
                $fadeClass = !empty($notif['is_one_time']) ? 'fade-out-animation' : '';
            ?>
            <div class="col-xl-3 col-lg-4 col-md-6 notif-card <?= $fadeClass ?>">
                <div class="card border-0 shadow-sm h-100 position-relative overflow-hidden"
                     style="border-left: 4px solid <?= $statusColor['border'] ?> !important;background:<?= $statusColor['bg'] ?>;">
                    <!-- Decorative pulse dot for actionable items -->
                    <?php if (in_array(strtolower($notif['status']), ['pending', 'submitted', 'approved', 'partially_paid', 'qc_approved', 'verified'])): ?>
                    <span style="position:absolute;top:12px;right:12px;width:8px;height:8px;border-radius:50%;background:<?= $statusColor['border'] ?>;box-shadow:0 0 0 3px <?= $statusColor['bg'] ?>;animation:boq-pulse 1.5s infinite"></span>
                    <?php endif; ?>

                    <div class="card-body py-3 px-4 d-flex flex-column h-100">
                        <!-- Request Type & Purpose -->
                        <div class="fw-bold mb-2" style="font-size:13px;color:#111827">
                            <i class="fa-light <?= $typeIcon ?> me-1"></i>
                            <span class="text-muted" style="font-size:11px;"><?= $notif['request_type'] ?></span><br/>
                            <span class="text-truncate d-block mt-1" title="<?= htmlspecialchars($notif['purpose'] ?? '') ?>"><?= htmlspecialchars($notif['purpose'] ?? '') ?></span>
                        </div>

                        <div class="mt-auto">
                            <!-- Status & Amount -->
                            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-1">
                                <span class="badge" style="background:<?= $statusColor['badge'] ?>;color:#fff;font-size:10px;font-weight:700;">
                                    <i class="fa-solid <?= $statusColor['icon'] ?> me-1"></i>
                                    <?php 
                                        $displayStatus = ucwords(str_replace('_', ' ', $notif['status']));
                                        if (strtolower($notif['status']) === 'approved') {
                                            $displayStatus = 'Approved - Awaiting Payment';
                                        }
                                        echo $displayStatus;
                                    ?>
                                </span>
                                <div class="d-flex flex-column align-items-end">
                                    <span class="fw-bold" style="font-size:13px;color:<?= $statusColor['text'] ?>; line-height: 1.2;">
                                        TK.<?= number_format($notif['amount'] ?? 0, 2) ?>
                                    </span>
                                    <?php 
                                        if (strtolower($notif['status']) === 'partially_paid' || ($notif['due_amount'] !== null && $notif['due_amount'] > 0 && $notif['due_amount'] < ($notif['approved_amount'] ?? $notif['amount']))) {
                                            $baseAmt = $notif['approved_amount'] ?? $notif['amount'];
                                            $due = $notif['due_amount'] ?? 0;
                                            $paid = max(0, $baseAmt - $due);
                                            echo '<small class="text-muted mt-1" style="font-size:9px;line-height:1.2;">Paid: '.number_format($paid, 2).' | Due: <span class="text-danger fw-bold">'.number_format($due, 2).'</span></small>';
                                        }
                                    ?>
                                </div>
                            </div>

                            <!-- Requester & Action -->
                            <div class="d-flex align-items-center justify-content-between">
                                <small class="text-muted text-truncate w-50" style="font-size:11px;" title="<?= htmlspecialchars($notif['requested_by_name'] ?? 'Unknown') ?>">
                                    <i class="fa-light fa-user me-1"></i>
                                    <?= htmlspecialchars($notif['requested_by_name'] ?? 'Unknown') ?>
                                </small>
                                <a href="<?= $GLOBALS['base_url'] ?>/<?= $notif['view_url'] ?>"
                                   class="btn btn-sm fw-bold"
                                   style="font-size:11px;padding:4px 10px;border-radius:6px;background:<?= $statusColor['border'] ?>;color:#fff;border:none;text-decoration:none;">
                                    View <i class="fa-solid fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (count($accountsNotifications) > 8): ?>
        <div class="text-center mt-3">
            <span class="text-muted" style="font-size:12px;">
                Showing 8 of <?= count($accountsNotifications) ?> notifications
            </span>
        </div>
        <?php endif; ?>
    </div>

    <style>
    @keyframes boq-pulse {
        0%   { box-shadow: 0 0 0 0 rgba(59,130,246,.4); }
        70%  { box-shadow: 0 0 0 6px rgba(59,130,246,0); }
        100% { box-shadow: 0 0 0 0 rgba(59,130,246,0); }
    }
    .fade-out-animation {
        animation: fadeOutSlow 6s forwards;
        animation-delay: 2s;
    }
    @keyframes fadeOutSlow {
        0% { opacity: 1; transform: scale(1); }
        100% { opacity: 0.6; filter: grayscale(50%); }
    }
    </style>
    <?php endif; ?>

    <!-- Charts Section -->
    <div class="col-12 mb-3">
        <div class="row">
    <div class="col-xxl-8 col-xl-7 col-lg-12 mb-3">
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
                                <div id="transactionChartYearly"></div>
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
                                <div id="transactionChartMonthly"></div>
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
                            <div id="transactionChartWeek"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Methods Breakdown -->
    <div class="col-xxl-4 col-xl-5 col-lg-12 mb-3">
        <div class="card__wrapper height-equal">
            <div class="card__title-wrap d-flex align-items-center justify-content-between mb-20">
                <h5 class="card__heading-title">Payment Methods</h5>
                <span class="period-badge"><?= $periodText ?></span>
            </div>
            <div class="payment-method-chart card__content">
                <div class="chart-container">
                    <?php if (array_sum($paymentMethodData) > 0): ?>
                        <div id="paymentMethodsChart"></div>
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
    </div><!-- end charts row -->
    </div><!-- end charts col-12 -->

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

<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.49.0/dist/apexcharts.min.js"></script>

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
        const formatCurrency = (value) => 'TK.' + Number(value).toLocaleString('en-IN');

        // Color palette
        const colors = {
            cashin: '#10b981',
            cashout: '#ef4444',
            primary: '#6366f1',
            doughnut: ['#8b5cf6', '#f02345', '#10b981', '#f59e0b', '#ef4444', '#ec4899']
        };

        const viewColor = '<?= $viewType === "cashin" ? "#10b981" : "#ef4444" ?>';

        /* ==========================
            WEEKLY CHART — Area Chart (Cash In vs Cash Out)
        ========================== */
        const weekEl = document.querySelector('#transactionChartWeek');
        if (weekEl) {
            const weekChart = new ApexCharts(weekEl, {
                chart: {
                    type: 'area',
                    height: 350,
                    fontFamily: 'Inter, sans-serif',
                    toolbar: { show: false },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 1200
                    }
                },
                series: [
                    {
                        name: 'Cash In',
                        data: <?= json_encode($weeklyCashInData) ?>
                    },
                    {
                        name: 'Cash Out',
                        data: <?= json_encode($weeklyCashOutData) ?>
                    }
                ],
                colors: [colors.cashin, colors.cashout],
                dataLabels: { enabled: false },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.35,
                        opacityTo: 0.05,
                        stops: [0, 95, 100]
                    }
                },
                xaxis: {
                    categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    labels: {
                        style: { colors: '#6b7280', fontSize: '12px' }
                    }
                },
                yaxis: {
                    labels: {
                        formatter: (val) => formatCurrency(val),
                        style: { colors: '#6b7280', fontSize: '12px' }
                    }
                },
                grid: {
                    borderColor: '#f1f1f4',
                    strokeDashArray: 4,
                    xaxis: { lines: { show: false } }
                },
                tooltip: {
                    theme: 'dark',
                    y: {
                        formatter: (val) => formatCurrency(val)
                    }
                },
                legend: {
                    position: 'bottom',
                    horizontalAlign: 'center',
                    fontSize: '13px',
                    fontWeight: 500,
                    markers: { radius: 12 },
                    itemMargin: { horizontal: 12 }
                }
            });
            weekChart.render();
        }

        /* ==========================
            MONTHLY CHART — Bar Chart
        ========================== */
        const monthEl = document.querySelector('#transactionChartMonthly');
        let monthChart = null;
        if (monthEl) {
            const mLabels = dynamicChartLabels.length ? dynamicChartLabels : ['Jan', 'Feb', 'Mar', 'Apr'];
            const mData = dynamicChartData.length ? dynamicChartData : [0, 0, 0, 0];

            monthChart = new ApexCharts(monthEl, {
                chart: {
                    type: 'bar',
                    height: 350,
                    fontFamily: 'Inter, sans-serif',
                    toolbar: { show: false },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 1000,
                        animateGradually: { enabled: true, delay: 150 }
                    }
                },
                series: [{
                    name: '<?= ucfirst($viewType) ?>',
                    data: mData
                }],
                colors: [viewColor],
                plotOptions: {
                    bar: {
                        borderRadius: 8,
                        columnWidth: '55%',
                        dataLabels: { position: 'top' }
                    }
                },
                dataLabels: { enabled: false },
                xaxis: {
                    categories: mLabels,
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    labels: {
                        style: { colors: '#6b7280', fontSize: '12px' }
                    }
                },
                yaxis: {
                    labels: {
                        formatter: (val) => formatCurrency(val),
                        style: { colors: '#6b7280', fontSize: '12px' }
                    }
                },
                grid: {
                    borderColor: '#f1f1f4',
                    strokeDashArray: 4,
                    xaxis: { lines: { show: false } }
                },
                tooltip: {
                    theme: 'dark',
                    y: {
                        formatter: (val) => formatCurrency(val)
                    }
                }
            });
            monthChart.render();
        }

        /* ==========================
            YEARLY CHART — Area Line Chart
        ========================== */
        const yearEl = document.querySelector('#transactionChartYearly');
        let yearChart = null;
        if (yearEl) {
            const yLabels = dynamicYearlyLabels.length ? dynamicYearlyLabels.map(String) : ['2023', '2024', '2025'];
            const yData = dynamicYearlyChartData.length ? dynamicYearlyChartData : [0, 0, 0];

            yearChart = new ApexCharts(yearEl, {
                chart: {
                    type: 'area',
                    height: 350,
                    fontFamily: 'Inter, sans-serif',
                    toolbar: { show: false },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 1200
                    }
                },
                series: [{
                    name: '<?= ucfirst($viewType) ?>',
                    data: yData
                }],
                colors: [viewColor],
                dataLabels: { enabled: false },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.4,
                        opacityTo: 0.05,
                        stops: [0, 90, 100]
                    }
                },
                markers: {
                    size: 6,
                    strokeWidth: 3,
                    strokeColors: '#fff',
                    hover: { size: 8 }
                },
                xaxis: {
                    categories: yLabels,
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    labels: {
                        style: { colors: '#6b7280', fontSize: '12px' }
                    }
                },
                yaxis: {
                    labels: {
                        formatter: (val) => formatCurrency(val),
                        style: { colors: '#6b7280', fontSize: '12px' }
                    }
                },
                grid: {
                    borderColor: '#f1f1f4',
                    strokeDashArray: 4,
                    xaxis: { lines: { show: false } }
                },
                tooltip: {
                    theme: 'dark',
                    y: {
                        formatter: (val) => formatCurrency(val)
                    }
                }
            });
            yearChart.render();
        }

        /* ==========================
            PAYMENT METHODS — Donut Chart
        ========================== */
        const payEl = document.querySelector('#paymentMethodsChart');
        if (payEl) {
            const payChart = new ApexCharts(payEl, {
                chart: {
                    type: 'donut',
                    height: 380,
                    fontFamily: 'Inter, sans-serif',
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 1500,
                        animateGradually: { enabled: true, delay: 200 }
                    }
                },
                series: paymentMethodData,
                labels: paymentMethodLabels,
                colors: colors.doughnut,
                plotOptions: {
                    pie: {
                        donut: {
                            size: '60%',
                            labels: {
                                show: true,
                                name: {
                                    show: true,
                                    fontSize: '14px',
                                    fontWeight: 600,
                                    color: '#374151'
                                },
                                value: {
                                    show: true,
                                    fontSize: '20px',
                                    fontWeight: 700,
                                    color: '#111827',
                                    formatter: (val) => formatCurrency(Number(val))
                                },
                                total: {
                                    show: true,
                                    label: 'Total',
                                    fontSize: '14px',
                                    fontWeight: 600,
                                    color: '#6b7280',
                                    formatter: (w) => {
                                        const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                        return formatCurrency(total);
                                    }
                                }
                            }
                        }
                    }
                },
                dataLabels: { enabled: false },
                stroke: { width: 2, colors: ['#fff'] },
                legend: {
                    position: 'bottom',
                    horizontalAlign: 'center',
                    fontSize: '12px',
                    markers: { radius: 12 },
                    itemMargin: { horizontal: 8, vertical: 4 }
                },
                tooltip: {
                    theme: 'dark',
                    y: {
                        formatter: function(val, opts) {
                            const total = opts.globals.seriesTotals.reduce((a, b) => a + b, 0);
                            const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                            return formatCurrency(val) + ' (' + pct + '%)';
                        }
                    }
                }
            });
            payChart.render();
        }

        /* ==========================
            TAB SWITCH — Re-render charts in hidden tabs
        ========================== */
        document.querySelectorAll('#pills-tab button[data-bs-toggle="pill"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', function(e) {
                const target = e.target.getAttribute('data-bs-target');
                if (target === '#pills-bar-month' && monthChart) {
                    setTimeout(() => monthChart.render(), 100);
                } else if (target === '#pills-bar-year' && yearChart) {
                    setTimeout(() => yearChart.render(), 100);
                }
            });
        });
    });
</script>
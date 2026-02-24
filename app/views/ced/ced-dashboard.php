<?php
$page_title = "CED Dashboard";
$page = 'CED Dashboard';
$show_breadcrumb = true;

// ── Use variables exactly as passed by CedView() controller ──────────────────
// $clients       = getAllClients()
// $paymentStats  = getPaymentStatistics()

// ── Aggregate from $clients ───────────────────────────────────────────────────
$totalAgreementValue  = 0;
$totalCollected       = 0;
$totalOutstanding     = 0;
$overduePayments      = 0;
$monthlyCollection    = 0;
$currentMonth         = date('Y-m');

// Payment status pie: [On Track, Pending, Overdue, Completed]
$paymentStatusData = [0, 0, 0, 0];

// Monthly chart skeleton — last 6 months
$monthlyChartLabels    = [];
$monthlyCollectionMap  = [];
$monthlyOutstandingMap = [];
$weeklyCollectionData  = [0, 0, 0, 0, 0, 0, 0];

for ($i = 5; $i >= 0; $i--) {
    $mk = date('Y-m', strtotime("-$i months"));
    $monthlyChartLabels[]       = date('M Y', strtotime($mk . '-01'));
    $monthlyCollectionMap[$mk]  = 0;
    $monthlyOutstandingMap[$mk] = 0;
}

if (!empty($clients)) {
    foreach ($clients as $client) {
        $price       = (float)($client['total_price'] ?? 0);
        $paid        = (float)($client['total_paid']  ?? 0);
        $outstanding = max(0, $price - $paid);
        $payPct      = $price > 0 ? ($paid / $price) * 100 : 0;

        $totalAgreementValue += $price;
        $totalCollected      += $paid;
        $totalOutstanding    += $outstanding;

        if ($payPct >= 100)                        { $paymentStatusData[3]++; }
        elseif ($payPct >= 50)                     { $paymentStatusData[0]++; }
        elseif ($payPct < 25 && $outstanding > 0)  { $paymentStatusData[1]++; }

        if (!empty($client['last_payment_date'])) {
            $lpDate    = $client['last_payment_date'];
            $lpMonth   = date('Y-m', strtotime($lpDate));
            $daysSince = (strtotime(date('Y-m-d')) - strtotime($lpDate)) / 86400;

            if ($lpMonth === $currentMonth) {
                $monthlyCollection += $paid * 0.1;
            }
            if (isset($monthlyCollectionMap[$lpMonth])) {
                $monthlyCollectionMap[$lpMonth]  += $paid * 0.1;
                $monthlyOutstandingMap[$lpMonth]  = $totalOutstanding;
            }
            if ($daysSince <= 7) {
                $idx = max(0, min(6, 6 - (int)floor($daysSince)));
                $weeklyCollectionData[$idx] += $paid * 0.1;
            }
            if ($daysSince > 60 && $outstanding > 0) {
                $overduePayments++;
                $paymentStatusData[2]++;
            }
        } elseif ($outstanding > 0) {
            $overduePayments++;
            $paymentStatusData[2]++;
        }
    }
}

$collectionRate          = $totalAgreementValue > 0 ? ($totalCollected / $totalAgreementValue) * 100 : 0;
$monthlyCollectionArray  = array_values($monthlyCollectionMap);
$monthlyOutstandingArray = array_values($monthlyOutstandingMap);

// ── Status badge map (NO function — avoids "Cannot redeclare" fatal error) ───
$statusBadgeMap = [
    'pending'        => '<span class="bd-badge bg-warning">Pending</span>',
    'approved'       => '<span class="bd-badge bg-success">Approved</span>',
    'rejected'       => '<span class="bd-badge bg-danger">Rejected</span>',
    'partially_paid' => '<span class="bd-badge bg-info">Partially Paid</span>',
    'fully_paid'     => '<span class="bd-badge bg-success">Fully Paid</span>',
    'paid'           => '<span class="bd-badge bg-success">Paid</span>',
];
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

    .card__title { font-size: 1.25rem; }
    .height-equal { height: 100%; padding-bottom: 0; }
    .payment-method-chart { margin-top: 58px; }
    .chart-container { position: relative; height: 350px; }

    .period-badge {
        font-size: 12px;
        padding: 2px 8px;
        border-radius: 4px;
        background: #e9ecef;
        color: #6c757d;
        font-weight: 600;
    }

    .no-data-placeholder {
        height: 350px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        color: #6c757d;
    }

    .no-data-placeholder i { font-size: 48px; margin-bottom: 15px; opacity: 0.5; }

    .table__icon {
        width: 32px; height: 32px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center; justify-content: center;
        border: 1px solid #dee2e6;
        background: #fff;
        color: #6c757d;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
    }
    .table__icon:hover {
        border-color: var(--clr-theme-primary);
        background: var(--clr-theme-primary);
        color: #fff;
    }

    @media (max-width: 975px) {
        .card__title { font-size: 1rem; }
    }
    @media (max-width: 768px) {
        .btn { font-size: 14px; padding: 6px 24px; }
    }
</style>

<div class="row">

    <!-- ══ STAT CARDS ══ -->
    <div class="col-xxl-4 col-xl-4 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-light fa-users"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Clients</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title"><?= $paymentStats['total_clients'] ?? 0 ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-4 col-xl-4 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-light fa-badge-check"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Full Payment Completed</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title"><?= $paymentStats['full_payment_completed'] ?? 0 ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-4 col-xl-4 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-light fa-check-circle"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Approved Bookings</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title"><?= $paymentStats['approved_bookings'] ?? 0 ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-4 col-xl-4 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-light fa-circle-dollar"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Collected</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title">৳<?= number_format($totalCollected, 0) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-4 col-xl-4 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-light fa-percent"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Collection Rate</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title"><?= number_format($collectionRate, 1) ?>%</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-4 col-xl-4 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-light fa-handshake"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">This Month Collection</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title">৳<?= number_format($monthlyCollection, 0) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ MAIN CHART — 8 cols, same as accounts ══ -->
    <div class="col-xxl-8 col-xl-12 col-lg-12 mb-3">
        <div class="card__wrapper height-equal">
            <div class="card__title-wrap d-flex flex-wrap gap-10 align-items-center justify-content-between mb-50">
                <h5 class="card__heading-title">Collection & Outstanding Overview</h5>
                <div class="card__tab">
                    <ul class="nav nav-pills" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-bar-year-tab" data-bs-toggle="pill"
                                data-bs-target="#pills-bar-year" type="button" role="tab"
                                aria-controls="pills-bar-year" aria-selected="false">6M</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="pills-bar-week-tab"
                                data-bs-toggle="pill" data-bs-target="#pills-bar-week" type="button"
                                role="tab" aria-controls="pills-bar-week" aria-selected="true">1W</button>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="card__content">
                <div class="tab-content" id="pills-tabContent">
                    <div class="tab-pane fade" id="pills-bar-year" role="tabpanel"
                        aria-labelledby="pills-bar-year-tab">
                        <div class="chart-container">
                            <canvas id="collectionChartMonthly"></canvas>
                        </div>
                    </div>
                    <div class="tab-pane fade show active" id="pills-bar-week" role="tabpanel"
                        aria-labelledby="pills-bar-week-tab">
                        <div class="chart-container">
                            <canvas id="collectionChartWeek"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ PAYMENT STATUS PIE — 4 cols, same as accounts payment methods ══ -->
    <div class="col-xxl-4 col-xl-6 col-lg-12 mb-3">
        <div class="card__wrapper height-equal">
            <div class="card__title-wrap d-flex align-items-center justify-content-between mb-20">
                <h5 class="card__heading-title">Payment Status</h5>
                <span class="period-badge">All Clients</span>
            </div>
            <div class="payment-method-chart card__content">
                <div class="chart-container">
                    <?php if (array_sum($paymentStatusData) > 0): ?>
                        <canvas id="paymentStatusChart"></canvas>
                    <?php else: ?>
                        <div class="no-data-placeholder">
                            <i class="fa-light fa-chart-pie"></i>
                            <p>No payment data available</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ CLIENTS TABLE ══ -->
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="card__header d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="card__title mb-0">Clients Information</h5>
                    <small class="text-muted">All active clients</small>
                </div>
                <a href="<?= $GLOBALS['base_url'] ?>/clients-cashin-view" class="btn btn-sm btn-primary">
                    View All <i class="fa-light fa-arrow-right ms-2"></i>
                </a>
            </div>

            <div class="table__wrapper table-responsive">
                <table class="table mb-20 multiple_tables">
                    <thead>
                        <tr class="table__title table__sort">
                            <th>Client Name</th>
                            <th>Phone</th>
                            <th>Project</th>
                            <th>Unit / Floor</th>
                            <th>Agent</th>
                            <th>Total Price</th>
                            <th>Total Paid</th>
                            <th>Last Payment</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php if (!empty($clients)): ?>
                            <?php foreach ($clients as $client): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($client['client_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($client['phone'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($client['project_name'] ?? 'N/A') ?></td>
                                    <td>
                                        <?php if (!empty($client['unit_name']) || !empty($client['flat_floor_no'])): ?>
                                            UNIT <?= htmlspecialchars($client['unit_name'] ?? '') ?> - FL <?= htmlspecialchars($client['flat_floor_no'] ?? 'N/A') ?>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($client['agent_name'] ?? 'N/A') ?></td>
                                    <td>৳<?= number_format($client['total_price'] ?? 0, 2) ?></td>
                                    <td>৳<?= number_format($client['total_paid'] ?? 0, 2) ?></td>
                                    <td>
                                        <?php if (!empty($client['last_payment_date'])): ?>
                                            <?= date('d M, Y', strtotime($client['last_payment_date'])) ?>
                                        <?php else: ?>
                                            <span class="text-muted">No Payment</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a class="table__icon"
                                            href="<?= $GLOBALS['base_url'] ?>/clients-cash-in-history-view/<?= $client['booking_id'] ?>"
                                            title="View History">
                                            <i class="fa-regular fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- <tr>
                                <td colspan="9" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fa-light fa-users fa-2x mb-3 d-block"></i>
                                        No clients found
                                    </div>
                                </td>
                            </tr> -->
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {

        // ── PHP → JS ──────────────────────────────────────────────────────────
        const monthlyLabels      = <?= json_encode($monthlyChartLabels) ?>;
        const monthlyCollection  = <?= json_encode($monthlyCollectionArray) ?>;
        const monthlyOutstanding = <?= json_encode($monthlyOutstandingArray) ?>;
        const weeklyCollection   = <?= json_encode($weeklyCollectionData) ?>;
        const paymentStatusData  = <?= json_encode($paymentStatusData) ?>;

        const formatCurrency = (value) => '৳' + value.toLocaleString();

        const colors = {
            primary:  '#6366f1',
            success:  '#10b981',
            danger:   '#ef4444',
            warning:  '#f59e0b',
            info:     '#06b6d4',
            doughnut: ['#10b981', '#f59e0b', '#ef4444', '#06b6d4']
        };

        /* ── 1W LINE CHART (same as accounts) ────────────────────────────── */
        const ctxWeek = document.getElementById('collectionChartWeek');
        if (ctxWeek) {
            new Chart(ctxWeek.getContext('2d'), {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Collection',
                        data: weeklyCollection,
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
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    animation: { duration: 2000, easing: 'easeOutQuart' },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { usePointStyle: true, padding: 20, font: { size: 13, weight: '500' } }
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
                            border: { display: false },
                            grid: { color: 'rgba(0, 0, 0, 0.05)', drawTicks: false },
                            ticks: { padding: 10, callback: function(value) { return formatCurrency(value); } }
                        },
                        x: {
                            border: { display: false },
                            grid: { display: false },
                            ticks: { padding: 10 }
                        }
                    }
                }
            });
        }

        /* ── 6M BAR CHART (same as accounts 1M tab) ──────────────────────── */
        const ctxMonth = document.getElementById('collectionChartMonthly');
        if (ctxMonth) {
            new Chart(ctxMonth.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: monthlyLabels,
                    datasets: [
                        {
                            label: 'Collection',
                            data: monthlyCollection,
                            backgroundColor: colors.success,
                            borderRadius: 8,
                            borderSkipped: false,
                            barThickness: 25,
                            maxBarThickness: 30
                        },
                        {
                            label: 'Outstanding',
                            data: monthlyOutstanding,
                            backgroundColor: colors.danger,
                            borderRadius: 8,
                            borderSkipped: false,
                            barThickness: 25,
                            maxBarThickness: 30
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animations: { y: { duration: 1500, from: 0, easing: 'easeOutBounce' } },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { usePointStyle: true, padding: 20, font: { size: 13, weight: '500' } }
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
                            border: { display: false },
                            grid: { color: 'rgba(0, 0, 0, 0.05)', drawTicks: false },
                            ticks: { padding: 10, callback: function(value) { return formatCurrency(value); } }
                        },
                        x: {
                            border: { display: false },
                            grid: { display: false },
                            ticks: { padding: 10 }
                        }
                    }
                }
            });
        }

        /* ── PAYMENT STATUS PIE (same as accounts payment methods pie) ────── */
        const ctxPie = document.getElementById('paymentStatusChart');
        if (ctxPie) {
            new Chart(ctxPie.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: ['On Track', 'Pending', 'Overdue', 'Completed'],
                    datasets: [{
                        data: paymentStatusData,
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
                            labels: { usePointStyle: true, padding: 15, font: { size: 12 }, color: '#6b7280' }
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
                                    return label + ': ' + value + ' clients (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
    });
</script>
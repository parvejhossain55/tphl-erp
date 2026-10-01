<?php
$page_title = "CED Dashboard";
$page = 'CED Dashboard';
$show_breadcrumb = true;

// ── Use variables exactly as passed by CedView() controller ──────────────────
// $clients       = getAllBookings()
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
    $monthlyCollectionMap[$mk]  = $collectionData['monthly_data'][$mk] ?? 0;
    $monthlyOutstandingMap[$mk] = 0;
}

$monthlyCollection = $collectionData['this_month_collection'] ?? 0;
$weeklyCollectionData = $collectionData['weekly_data'] ?? [0, 0, 0, 0, 0, 0, 0];

if (!empty($clients)) {
    foreach ($clients as $client) {
        $price       = (float)($client['total_price'] ?? 0);
        $paid        = (float)($client['total_paid']  ?? 0);
        $outstanding = max(0, $price - $paid);
        $payPct      = $price > 0 ? ($paid / $price) * 100 : 0;

        $totalAgreementValue += $price;
        $totalCollected      += $paid;
        $totalOutstanding    += $outstanding;

        $hasBeenCounted = false;

        if ($payPct >= 100) { 
            $paymentStatusData[3]++; 
            $hasBeenCounted = true;
        }

        if (!empty($client['last_payment_date'])) {
            $lpDate    = $client['last_payment_date'];
            $lpMonth   = date('Y-m', strtotime($lpDate));
            $daysSince = (strtotime(date('Y-m-d')) - strtotime($lpDate)) / 86400;

            if (isset($monthlyOutstandingMap[$lpMonth])) {
                $monthlyOutstandingMap[$lpMonth] = $totalOutstanding;
            }
            
            if (!$hasBeenCounted && $daysSince > 60 && $outstanding > 0) {
                $overduePayments++;
                $paymentStatusData[2]++;
                $hasBeenCounted = true;
            }
        } elseif ($outstanding > 0 && !$hasBeenCounted) {
            $paymentStatusData[1]++;
            $hasBeenCounted = true;
        }
        
        // Fallback for remaining active payments
        if (!$hasBeenCounted) {
            if ($payPct >= 50) {
                $paymentStatusData[0]++;
            } else {
                $paymentStatusData[1]++;
            }
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
    <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
        <div class="card__wrapper h-100">
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

    <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
        <div class="card__wrapper h-100">
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



    <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
        <div class="card__wrapper h-100">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-light fa-circle-dollar"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Collected</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title">TK.<?= number_format($totalCollected, 0) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
        <div class="card__wrapper h-100">
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
                    <small class="text-muted">All active clients (<span id="cedTotalRecordsCount">0</span> records)</small>
                </div>
                <a href="<?= $GLOBALS['base_url'] ?>/clients-cashin-view" class="btn btn-sm btn-primary">
                    View All <i class="fa-light fa-arrow-right ms-2"></i>
                </a>
            </div>

            <!-- Filters & Search -->
            <div class="row mb-3 align-items-center justify-content-between">
                <div class="col-auto">
                    <div class="d-flex align-items-center">
                        <span class="me-2 text-muted">Show</span>
                        <select id="cedLimitSelect" class="form-select form-select-sm w-auto d-inline-block" onchange="loadCedDashboardPage(1)">
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span class="ms-2 text-muted">entries</span>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="input-group input-group-sm" style="max-width: 250px;">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" id="cedSearchInput" class="form-control" placeholder="Search clients..." onkeyup="if(event.key==='Enter') loadCedDashboardPage(1)">
                        <button class="btn btn-primary btn-sm" onclick="loadCedDashboardPage(1)">Search</button>
                    </div>
                </div>
            </div>

            <div class="table__wrapper table-responsive position-relative">
                <!-- Loading Overlay -->
                <div id="cedTableLoadingOverlay" class="position-absolute top-0 start-0 w-100 h-100 d-none justify-content-center align-items-center" style="background: rgba(255,255,255,0.75); z-index: 10; min-height: 150px; border-radius: 8px;">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="small text-muted mt-2 fw-semibold">Loading clients data...</div>
                    </div>
                </div>

                <table class="table mb-0" id="clientsCedTable">
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
                            <th>Current Month</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody class="table__body" id="clientsCedTableBody">
                        <!-- Loaded via AJAX -->
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
                <div class="text-muted small" id="clientsCedInfoText">
                    Showing 0 to 0 of 0 entries
                </div>
                <div id="clientsCedPaginationContainer">
                    <!-- Pagination injected via AJAX -->
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    function loadCedDashboardPage(page) {
        page = parseInt(page) || 1;
        if (page < 1) page = 1;

        const overlay = document.getElementById('cedTableLoadingOverlay');
        const tbody = document.getElementById('clientsCedTableBody');
        const pagContainer = document.getElementById('clientsCedPaginationContainer');
        const searchVal = document.getElementById('cedSearchInput') ? document.getElementById('cedSearchInput').value.trim() : '';
        const limit = document.getElementById('cedLimitSelect') ? document.getElementById('cedLimitSelect').value : '10';

        if (overlay) {
            overlay.classList.remove('d-none');
            overlay.classList.add('d-flex');
        }
        if (tbody) tbody.style.opacity = '0.4';

        const params = new URLSearchParams({
            ajax: '1',
            page: page,
            limit: limit,
            search: searchVal
        });

        fetch(`${base_url}/ced-dashboard?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (tbody) tbody.innerHTML = data.table_html;
                    if (pagContainer) pagContainer.innerHTML = data.pagination_html;
                    
                    const infoText = document.getElementById('clientsCedInfoText');
                    if (infoText) {
                        infoText.innerHTML = data.info_text;
                    }

                    const countBadge = document.getElementById('cedTotalRecordsCount');
                    if (countBadge) countBadge.innerText = new Intl.NumberFormat().format(data.totalRecords);
                } else {
                    console.error("Error loading CED Dashboard clients:", data.error);
                }
            })
            .catch(error => {
                console.error("Fetch error:", error);
                if (tbody) tbody.innerHTML = '<tr><td colspan="10" class="text-center text-danger py-4"><i class="fa-solid fa-triangle-exclamation me-2"></i>Failed to load data.</td></tr>';
            })
            .finally(() => {
                if (overlay) {
                    overlay.classList.remove('d-flex');
                    overlay.classList.add('d-none');
                }
                if (tbody) tbody.style.opacity = '1';
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        loadCedDashboardPage(1);
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {

        // ── PHP → JS ──────────────────────────────────────────────────────────
        const monthlyLabels      = <?= json_encode($monthlyChartLabels) ?>;
        const monthlyCollection  = <?= json_encode($monthlyCollectionArray) ?>;
        const monthlyOutstanding = <?= json_encode($monthlyOutstandingArray) ?>;
        const weeklyCollection   = <?= json_encode($weeklyCollectionData) ?>;
        const paymentStatusData  = <?= json_encode($paymentStatusData) ?>;

        const formatCurrency = (value) => 'TK.' + value.toLocaleString();

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
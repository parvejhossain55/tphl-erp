<style>
    body {
        background-color: #f8f9fa;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .dashboard-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem 0;
        margin-bottom: 2rem;
        border-radius: 0 0 15px 15px;
    }

    .stat-card {
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: none;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

.stat-card-icon {
    width: 52px;
    height: 53px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

    .stat-card-blue {
        background: linear-gradient(135deg, #1abc9c 0%, #198754 100%);
    }

    .stat-card-green {
        background: linear-gradient(135deg, #19ab62 0%, #60bbc1 100%);
    }

    .stat-card-green {
        background: linear-gradient(135deg, #19ab62 0%, #60bbc1 100%);
    }

    .stat-card-purple {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: bold;
        margin: 0.5rem 0;
    }

.stat-label {
    font-size: 13px;
    font-weight: 500;
    text-transform: uppercase;
    /* letter-spacing: 0.2px; */
}

    .chart-container {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .chart-title {
        font-size: 1.5rem;
        font-weight: bold;
        margin-bottom: 1.5rem;
    }

    .info-box {
        background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
        border-left: 4px solid #667eea;
        border-radius: 8px;
        padding: 1rem 1.5rem;
        margin-top: 1.5rem;
    }

    .info-box strong {
        color: #667eea;
    }
</style>

<!-- Dashboard Header -->

<div class="app__slide-wrapper">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-2">Executive Dashboard</h3>
            <p class="mb-0 opacity-75">Today's Summery & performance</p>
        </div>

    </div>
    <div class="row g-20 mb-4 align-items-stretch">
        <!-- Today's Tasks -->
        <div class="col-md-6 col-lg-4 col-xxl-3">
            <div class="card__wrapper stat-card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="stat-label mb-2">Pending Tasks</p>
                            <h2 class="stat-value text-primary"><?= $pendingTasks ?></h2>
                        </div>
                        <div class="stat-card-icon stat-card-blue text-white">
                            <i class="bi bi-check2-square"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Leads -->
        <div class="col-md-6 col-lg-4 col-xxl-3">
            <div class="card__wrapper stat-card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="stat-label mb-2">Today Leads Assigned</p>
                            <h2 class="stat-value text-success"><?= $todayLeadsCount ?? 0 ?></h2>
                        </div>
                        <div class="stat-card-icon stat-card-green text-white">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today follow up  -->
        <div class="col-md-6 col-lg-4 col-xxl-3">
            <div class="card__wrapper stat-card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="stat-label mb-2">Today FollowUp Due</p>
                            <h2 class="stat-value text-info"><?= $todayFollowUp ?? 0 ?></h2>
                        </div>
                        <div class="stat-card-icon stat-card-green text-white">
                            <i class="bi bi-credit-card"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- overdue follow up  -->
        <div class="col-md-6 col-lg-4 col-xxl-3">
            <div class="card__wrapper stat-card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="stat-label mb-2">Today Follow Up Complete</p>
                            <h2 class="stat-value text-info">
                                <?= $todayFollowUpComplete ?? 0 ?>
                                
                            </h2>

                        </div>
                        <div class="stat-card-icon stat-card-purple text-white">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- My Total Sale -->
        <div class="col-md-6 col-lg-4 col-xxl-3">
            <div class="card__wrapper stat-card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="stat-label mb-2">My Total Sale </p>
                            <h2 class="stat-value text-info">
                                <?= $myTotalShareSold['total_flats'] ?>
                            </h2>

                        </div>
                        <div class="stat-card-icon stat-card-purple text-white">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Total Leads Assigned -->
        <div class="col-md-6 col-lg-4 col-xxl-3">
            <div class="card__wrapper stat-card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="stat-label mb-2">Total Leads Assigned </p>
                            <h2 class="stat-value text-info">
                                <?= $getAllLeadsCount ?>
                            </h2>

                        </div>
                        <div class="stat-card-icon stat-card-purple text-white">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!--Monthly Visit Target -->
        <div class="col-md-6 col-lg-4 col-xxl-3">
            <div class="card__wrapper stat-card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="stat-label mb-2">Monthly Site Visit Target</p>
                            <h2 class="stat-value text-info">
                                <?= $monthlySiteVisitTarget ?>
                            </h2>

                        </div>
                        <div class="stat-card-icon stat-card-purple text-white">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!--Monthly Target -->
        <div class="col-md-6 col-lg-4 col-xxl-3">
            <div class="card__wrapper stat-card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="stat-label mb-2">My Monthly Booking Target</p>
                            <h2 class="stat-value text-info">
                                <?= $monthlyBookingTarget  ?>
                            </h2>

                        </div>
                        <div class="stat-card-icon stat-card-purple text-white">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 col-xxl-3">
            <div class="card__wrapper stat-card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="stat-label mb-2">Monthly Visit Complete <span class=" stat-value text-info ms-2"><?= $monthlyVisitComplete ?></span></p>
                            <p class="stat-label mb-2">Monthly Target Visit Achievement  <span class=" stat-value text-info ms-2"><?= $visitAchievement ?> %</span></p>


                        </div>
                        <div class="stat-card-icon stat-card-purple text-white">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 col-xxl-3">
            <div class="card__wrapper stat-card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="stat-label mb-2 "> Monthly Booking Target Complete</p>
                            <h2 class="stat-value text-info">
                                <?= $monthlyBookingComplete  ?>
                            </h2>

                        </div>
                        <div class="stat-card-icon stat-card-purple text-white">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Total Leads Assigned -->
        <div class="col-md-6 col-lg-4 col-xxl-3">
            <div class="card__wrapper stat-card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="stat-label mb-2">Deal Lost </p>
                            <h2 class="stat-value text-info">
                                <?= $dealsLostAll ?>
                            </h2>

                        </div>
                        <div class="stat-card-icon stat-card-purple text-white">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Total Leads Assigned -->
        <div class="col-md-6 col-lg-4 col-xxl-3">
            <div class="card__wrapper stat-card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="stat-label mb-2">Deal Win</p>
                            <h2 class="stat-value text-info">
                                <?= $dealsWinAll ?>
                            </h2>

                        </div>
                        <div class="stat-card-icon stat-card-purple text-white">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Performance Chart -->
    <div class="d-none row mt-4">
        <div class="col-12">
            <div class="chart-container">
                <h3 class="chart-title">Weekly Performance</h3>
                <canvas id="performanceChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Info Box -->
    <div class="row">
        <div class="col-12">
            <div class="info-box">
                <p class="mb-0">
                    <i class="bi bi-lightbulb"></i>
                    <strong>Tips:</strong> This Week your Performance is Good
                </p>
            </div>
        </div>
    </div>

    <div class="mb-5"></div>

</div>

<div class="card__wrapper shadow-sm p-3">
    <h5>My Monthly Performance (<?= date('F Y') ?>)</h5>
    <div id="monthlyPerformanceChart"></div>
</div>

<!-- ApexCharts JS -->

<script>
document.addEventListener("DOMContentLoaded", function() {

    const monthlyData = {
        labels: [
            'Site Visit Target',
            'Booking Target',
            'Visit Completed',
            'Booking Completed',
            'Visit %',
            'Booking %',
            'Deals Closed',
            'Deals Won',
            'Deals Lost'
        ],
        totals: [
            <?= $monthlySiteVisitTarget ?? 0 ?>,
            <?= $monthlyBookingTarget ?? 0 ?>,
            <?= $monthlyVisitComplete ?? 0 ?>,
            <?= $monthlyBookingComplete ?? 0 ?>,
            <?= $visitAchievement ?? 0 ?>,
            <?= $bookingAchievement ?? 0 ?>,
            <?= $dealsClosed ?? 0 ?>,
            <?= $dealsWon ?? 0 ?>,
            <?= $dealsLost ?? 0 ?>
        ]
    };

    const colors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#189441', '#808594', '#20c997', '#fd7e14'];

const chartOptions = {
    chart: {
        type: 'bar',
        height: 350,
        toolbar: { show: false }
    },
    series: [{
        name: 'Performance',
        data: monthlyData.totals
    }],
    plotOptions: {
        bar: {
            distributed: true,
            borderRadius: 4,
            columnWidth: '50%'
        }
    },
    dataLabels: { enabled: true },

    xaxis: {
        categories: monthlyData.labels,
        labels: {
            rotate: -15,
            style: {
                colors: '#1a6e40',   // GREEN
                fontSize: '12px',
                fontWeight: 700
            }
        }
    },

    yaxis: {
        min: 0,
        title: {
            text: 'Count / %',
            style: {
                color: '#1a6e40'
            }
        },
        labels: {
            style: {
                colors: '#1a6e40',  // GREEN
                fontSize: '12px',
                fontWeight: 700
            }
        }
    },

    tooltip: { y: { formatter: val => val } },
    colors: colors
};

const monthlyChart = new ApexCharts(
    document.querySelector("#monthlyPerformanceChart"),
    chartOptions
);
monthlyChart.render();


});
</script>






<!-- Chart Configuration -->
<script>
    // Static performance data
    const performanceData = {
        labels: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Saturday', 'Sunday'],
        datasets: [{
                label: 'Task Completed',
                data: [12, 15, 10, 18, 14, 8, 6],
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: '#667eea',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            },
            {
                label: 'Lead Assigned',
                data: [8, 10, 6, 12, 9, 5, 4],
                borderColor: '#f5576c',
                backgroundColor: 'rgba(245, 87, 108, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: '#f5576c',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            },
            {
                label: 'Conversion',
                data: [5, 7, 4, 8, 6, 3, 2],
                borderColor: '#00f2fe',
                backgroundColor: 'rgba(0, 242, 254, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: '#00f2fe',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }
        ]
    };

    // Chart configuration
    const config = {
        type: 'line',
        data: performanceData,
        options: {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 2.5,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
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
                    backgroundColor: 'rgba(255, 255, 255, 0.95)',
                    titleColor: '#2c3e50',
                    bodyColor: '#2c3e50',
                    borderColor: '#e5e7eb',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: true,
                    boxPadding: 6,
                    usePointStyle: true
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#f1f3f5',
                        drawBorder: false
                    },
                    ticks: {
                        font: {
                            size: 12
                        },
                        color: '#6c757d',
                        padding: 10
                    }
                },
                x: {
                    grid: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        font: {
                            size: 12
                        },
                        color: '#6c757d',
                        padding: 10
                    }
                }
            }
        }
    };

    // Initialize chart
    const ctx2 = document.getElementById('performanceChart').getContext('2d');
    const performanceChart = new Chart(ctx, config);
</script>
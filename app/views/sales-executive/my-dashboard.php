<style>

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
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        color: #718096;
    }

    @media (max-width: 576px) {
        .stat-card {
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .stat-value {
            font-size: 1.25rem;
        }
        .stat-card-icon {
            width: 40px;
            height: 40px;
            font-size: 0.875rem;
        }
        .card__icon {
            display: none !important; /* Hide large icon on very small screens to save space */
        }
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
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="mb-1">Executive Dashboard</h3>
            <p class="mb-0 opacity-75 small text-muted">Today's Summary & Performance</p>
        </div>
    </div>
    <div class="row g-20 mb-4 align-items-stretch">
        <!-- Pending Tasks -->
        <div class="col-6 col-md-3">
            <div class="card__wrapper stat-card shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="stat-label mb-2"><i class="fa-regular fa-clock me-1"></i> Pending Tasks</p>
                        <h2 class="stat-value text-primary"><?= $pendingTasks ?></h2>
                    </div>
                    <div class="card__icon"><span><i class="fa-solid fa-clock"></i></span></div>
                </div>
            </div>
        </div>

        <!-- Today Leads Assigned -->
        <div class="col-6 col-md-3">
            <div class="card__wrapper stat-card shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="stat-label mb-2"><i class="fa-solid fa-user-plus me-1"></i> Today Leads Assigned</p>
                        <h2 class="stat-value text-success"><?= $todayLeadsCount ?? 0 ?></h2>
                    </div>
                    <div class="card__icon"><span><i class="fa-solid fa-user-plus"></i></span></div>
                </div>
            </div>
        </div>

        <!-- Today FollowUp Due -->
        <div class="col-6 col-md-3">
            <div class="card__wrapper stat-card shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="stat-label mb-2"><i class="fa-solid fa-calendar-check me-1"></i> Today FollowUp Due</p>
                        <h2 class="stat-value text-info"><?= $todayFollowUp ?? 0 ?></h2>
                    </div>
                    <div class="card__icon"><span><i class="fa-solid fa-calendar-check"></i></span></div>
                </div>
            </div>
        </div>

        <!-- Today Follow Up Complete -->
        <div class="col-6 col-md-3">
            <div class="card__wrapper stat-card shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="stat-label mb-2"><i class="fa-solid fa-check-circle me-1"></i> Today Follow Up Complete</p>
                        <h2 class="stat-value text-info"><?= $todayFollowUpComplete ?? 0 ?></h2>
                    </div>
                    <div class="card__icon"><span><i class="fa-solid fa-check-circle"></i></span></div>
                </div>
            </div>
        </div>

        <!-- My Total Sale -->
        <div class="col-6 col-md-3">
            <div class="card__wrapper stat-card shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="stat-label mb-2"><i class="fa-solid fa-bangladeshi-taka-sign me-1"></i> My Total Sale</p>
                        <h2 class="stat-value text-info"><?= $myTotalShareSold['total_flats'] ?></h2>
                    </div>
                    <div class="card__icon"><span><i class="fa-solid fa-bangladeshi-taka-sign"></i></span></div>
                </div>
            </div>
        </div>

        <!-- Total Leads Assigned -->
        <div class="col-6 col-md-3">
            <div class="card__wrapper stat-card shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="stat-label mb-2"><i class="fa-solid fa-users me-1"></i> Total Leads Assigned</p>
                        <h2 class="stat-value text-info"><?= $getAllLeadsCount ?></h2>
                    </div>
                    <div class="card__icon"><span><i class="fa-solid fa-users"></i></span></div>
                </div>
            </div>
        </div>

        <!-- Monthly Site Visit Target -->
        <div class="col-6 col-md-3">
            <div class="card__wrapper stat-card shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="stat-label mb-2"><i class="fa-solid fa-map-location-dot me-1"></i> Monthly Site Visit Target</p>
                        <h2 class="stat-value text-info"><?= $monthlySiteVisitTarget ?></h2>
                    </div>
                    <div class="card__icon"><span><i class="fa-solid fa-map-location-dot"></i></span></div>
                </div>
            </div>
        </div>

        <!-- My Monthly Booking Target -->
        <div class="col-6 col-md-3">
            <div class="card__wrapper stat-card shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="stat-label mb-2"><i class="fa-solid fa-file-invoice-dollar me-1"></i> My Monthly Booking Target</p>
                        <h2 class="stat-value text-info"><?= $monthlyBookingTarget ?></h2>
                    </div>
                    <div class="card__icon"><span><i class="fa-solid fa-file-invoice-dollar"></i></span></div>
                </div>
            </div>
        </div>

        <!-- Monthly Visit Complete -->
        <div class="col-6 col-md-3">
            <div class="card__wrapper stat-card shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="stat-label mb-2"><i class="fa-solid fa-calendar-check me-1"></i> Monthly Visit Complete
                            <span class="stat-value text-info ms-2"><?= $monthlyVisitComplete ?></span>
                        </p>
                        <p class="stat-label mb-2"><i class="fa-solid fa-percent me-1"></i> Monthly Target Visit Achievement
                            <span class="stat-value text-info ms-2"><?= $visitAchievement ?>%</span>
                        </p>
                    </div>
                    <div class="card__icon"><span><i class="fa-solid fa-calendar-check"></i></span></div>
                </div>
            </div>
        </div>

        <!-- Monthly Booking Target Complete -->
        <div class="col-6 col-md-3">
            <div class="card__wrapper stat-card shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="stat-label mb-2"><i class="fa-solid fa-check me-1"></i> Monthly Booking Target Complete</p>
                        <h2 class="stat-value text-info"><?= $monthlyBookingComplete ?></h2>
                    </div>
                    <div class="card__icon"><span><i class="fa-solid fa-check"></i></span></div>
                </div>
            </div>
        </div>

        <!-- Deal Lost -->
        <div class="col-6 col-md-3">
            <div class="card__wrapper stat-card shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="stat-label mb-2"><i class="fa-solid fa-xmark me-1"></i> Deal Lost</p>
                        <h2 class="stat-value text-info"><?= $dealsLostAll ?></h2>
                    </div>
                    <div class="card__icon"><span><i class="fa-solid fa-xmark"></i></span></div>
                </div>
            </div>
        </div>

        <!-- Deal Win -->
        <div class="col-6 col-md-3">
            <div class="card__wrapper stat-card shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="stat-label mb-2"><i class="fa-solid fa-check-double me-1"></i> Deal Win</p>
                        <h2 class="stat-value text-info"><?= $dealsWinAll ?></h2>
                    </div>
                    <div class="card__icon"><span><i class="fa-solid fa-check-double"></i></span></div>
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
    <div class="row d-none ">
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

        const colors = ['#4e73df', '#8e7eff', '#36b9cc', '#f6c23e', '#e74a3b', '#7b61ff', '#808594', '#a796e0', '#fd7e14'];

        const chartOptions = {
            chart: {
                type: 'bar',
                height: 350,
                toolbar: {
                    show: false
                }
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
            dataLabels: {
                enabled: true
            },

            xaxis: {
                categories: monthlyData.labels,
                labels: {
                    rotate: -45,
                    style: {
                        colors: '#5e4cdc',
                        fontSize: '11px',
                        fontWeight: 700
                    }
                }
            },

            yaxis: {
                min: 0,
                title: {
                    text: 'Count / %',
                    style: {
                        color: '#5e4cdc'
                    }
                },
                labels: {
                    style: {
                        colors: '#5e4cdc',
                        fontSize: '11px',
                        fontWeight: 700
                    }
                }
            },

            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        height: 500
                    },
                    plotOptions: {
                        bar: {
                            horizontal: true,
                            columnWidth: '100%',
                            distributed: true,
                        }
                    },
                    xaxis: {
                        labels: {
                            rotate: 0,
                        }
                    },
                    yaxis: {
                        labels: {
                            show: true,
                            style: {
                                fontSize: '10px'
                            }
                        }
                    },
                    legend: {
                        show: false
                    }
                }
            }],

            tooltip: {
                y: {
                    formatter: val => val
                }
            },
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
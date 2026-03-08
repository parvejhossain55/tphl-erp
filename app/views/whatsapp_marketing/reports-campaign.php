<?php
$page = "Lead & Booking Management";
$show_breadcrumb = true;
?>

<style>
    /* body {
        background: #f8f9fa;
        font-family: 'Segoe UI', sans-serif;
    } */
    .dashboard-header {
        margin-bottom: 20px;
    }

    .stat-card {
        border-radius: 8px;
        padding: 20px;
        /* background: #fff; */
        box-shadow: 0 0 8px rgba(0, 0, 0, 0.05);
        text-align: center;
    }

    .stat-card h4 {
        margin: 0;
        font-size: 14px;
        color: #6c757d;
    }

    .stat-card p {
        margin: 0;
        font-size: 24px;
        font-weight: 600;
    }

    .custom-search-input {
        width: 100%;
        min-width: 260px;
        height: 38px;
        padding: 6px 12px;
        font-size: 14px;
        border: 1px solid #ced4da;
        border-radius: 6px;
        outline: none;
        transition: all 0.2s ease-in-out;
    }

    .custom-search-input:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.15rem rgba(13, 110, 253, .25);
    }

    .table th,
    .table td {
        vertical-align: middle;
    }

    .badge-delivered {
        background: #d1e7dd;
        color: #0f5132;
    }

    .badge-failed {
        background: #f8d7da;
        color: #842029;
    }
</style>

<!-- Header -->
<div class="dashboard-header mb-3">
    <h3>Message Logs & Reports</h3>
    <p class="text-muted">Track message delivery and campaign performance</p>
</div>

<!-- Filters -->
<div class="row mb-4">
    <div class="col-md-3 ">
        <input type="date" class="form-control form-control-sm">
    </div>
    <div class="col-md-3 ">
        <input type="date" class="form-control form-control-sm">
    </div>
    <div class="col-md-3 ">
        <select class="form-select form-select-sm">
            <option>Campaign</option>
            <option>Marina Heights Launch</option>
        </select>
    </div>
    <div class="col-md-3 ">
        <select class="form-select form-select-sm">
            <option>Status</option>
            <option>Delivered</option>
            <option>Failed</option>
        </select>
    </div>
</div>

<!-- Statistics -->
<div class="row  mb-4">
    <div class="col-12 col-md-6 col-lg-3 col-xl-3">
        <div class="card__wrapper stat-card">
            <div class="stat-label">Total Sent</div>
            <div class="stat-value">20,500</div>
            <div class="stat-change positive d-none">

            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3 col-xl-3">
        <div class="card__wrapper stat-card">
            <div class="stat-label">Delivered</div>
            <div class="stat-value">20,500</div>
            <div class="stat-change positive d-none">

            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3 col-xl-3">
        <div class="card__wrapper stat-card">
            <div class="stat-label">Failed</div>
            <div class="stat-value">20,500</div>
            <div class="stat-change positive d-none">

            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3 col-xl-3">
        <div class="card__wrapper stat-card">
            <div class="stat-label">Success</div>
            <div class="stat-value">93%</div>
            <div class="stat-change positive d-none">

            </div>
        </div>
    </div>

 
</div>


<!-- Graph -->
<div class="row g-20">
    <div class="col-xxl-6 col-xl-12 col-lg-12 col-12 mb-3">
        <div class="card__wrapper height-equal">
            <div class="card__title-wrap d-flex flex-wrap gap-10 align-items-center justify-content-between mb-25">
                <h5 class="card__heading-title">Message Delivery Trend</h5>
                <div class="card__tab">
                    <ul class="nav nav-pills" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-bar-year-tab" data-period="year" type="button">1Y</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="pills-bar-month-tab" data-period="month" type="button">1M</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link " id="pills-bar-week-tab" data-period="week" type="button">1W</button>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="card__content">
                <div class="card__line-chart">
                    <div id="salesChart"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-6 col-xl-12 col-lg-12 col-12 mb-3">
        <div class="card-bg card-border mb-20 h-100">
            <div class="card__wrapper style_two h-100 d-flex flex-column">

                <!-- Card Header -->
                <div class="card__title-wrap d-flex align-items-center justify-content-between">
                    <h5 class="card__heading-title">Delivery Status</h5>
                    <div class="dropdown">
                        <button>
                            <i class="fa-regular fa-ellipsis-vertical"></i>
                        </button>
                    </div>
                </div>

                <!-- Card Body -->
                <div class="card__content flex-grow-1 d-flex align-items-center">
                    <div id="pieChart" style="width:100%; height:260px;"></div>
                </div>

            </div>
        </div>
    </div>

    <!-- project wise flat sale  -->
</div>
<!-- Search & Export -->
<div class="row my-3">
    <div class="col-md-6">
        <input type="search" class="custom-search-input" placeholder="Search contact...">
    </div>
    <div class="col-md-6 text-end">
        <button class="btn btn-outline-secondary btn-sm">Export to Excel</button>
    </div>
</div>

<!-- Message Logs Table -->
<div class="card__wrapper p-3">
    <h5 class="mb-3">Message Logs</h5>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Campaign</th>
                    <th>Recipient</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Device</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Marina Heights Launch</td>
                    <td>Rajesh Kumar</td>
                    <td>+91 98765 43210</td>
                    <td><span class="badge badge-delivered rounded-pill px-2 py-1">Delivered</span></td>
                    <td>Marketing Phone 1</td>
                    <td>2024-01-16 14:32</td>
                </tr>
                <tr>
                    <td>Marina Heights Launch</td>
                    <td>Priya Sharma</td>
                    <td>+91 87654 32109</td>
                    <td><span class="badge badge-delivered rounded-pill px-2 py-1">Delivered</span></td>
                    <td>Marketing Phone 1</td>
                    <td>2024-01-16 14:31</td>
                </tr>
                <tr>
                    <td>Marina Heights Launch</td>
                    <td>Amit Patel</td>
                    <td>+91 76543 21098</td>
                    <td><span class="badge badge-failed rounded-pill px-2 py-1">Failed</span></td>
                    <td>Marketing Phone 2</td>
                    <td>2024-01-16 14:30</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Include ApexCharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<!-- Include ApexCharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // ─── LINE CHART: Message Delivery Trend ───
        var trendData = {
            year: [1200, 1500, 1300, 1800, 1700, 2000, 2100, 1900, 2200, 2300, 2500, 2700],
            month: [120, 200, 150, 250, 180, 300, 220, 280, 260, 310, 290, 350],
            week: [20, 25, 18, 30, 22, 28, 35]
        };

        var trendCategories = {
            year: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            month: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'],
            week: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
        };

        var lineOptions = {
            chart: {
                type: 'line',
                height: 350,
                toolbar: {
                    show: false
                },
                zoom: {
                    enabled: false
                },
            },
            series: [{
                name: 'Messages Delivered',
                data: trendData.month // default 1M
            }],
            stroke: {
                curve: 'smooth',
                width: 3
            },
            markers: {
                size: 5
            },
            xaxis: {
                categories: trendCategories.month
            },
            grid: {
                borderColor: '#f1f1f1'
            },
            colors: ['#4f46e5'],
            tooltip: {
                theme: 'dark'
            }
        };
        var lineChart = new ApexCharts(document.querySelector("#salesChart"), lineOptions);
        lineChart.render();

        // Tab buttons for 1Y/1M/1W
        document.querySelectorAll('.card__tab button').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.card__tab button').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                var period = this.dataset.period;
                lineChart.updateOptions({
                    series: [{
                        data: trendData[period]
                    }],
                    xaxis: {
                        categories: trendCategories[period]
                    }
                });
            });
        });

        // ─── DONUT CHART: Delivery Status ───
        var donutOptions = {
            chart: {
                type: 'donut',
                height: 300
            },
            series: [60, 25, 15], // Delivered, Pending, Failed
            labels: ['Delivered', 'Pending', 'Failed'],
            colors: ['#0b6947', '#f3cf41', '#d40c37'],
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'light',
                    type: "horizontal",
                    gradientToColors: ['#08855b', '#d36820', '#e92e2e'],
                    stops: [0, 100]
                }
            },
            legend: {
                position: 'bottom'
            },
            tooltip: {
                theme: 'dark'
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%'
                    }
                }
            }
        };
        var donutChart = new ApexCharts(document.querySelector("#pieChart"), donutOptions);
        donutChart.render();
    });
</script>
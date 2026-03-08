<!-- MAIN -->
<div class="app__slide-wrapper">
    <!-- STAT CARDS -->
    <div class="row mb-4 align-items-stretch">
        <div class="col-xxl-2 col-xl-4 col-lg-4 col-md-6 col-12">

            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-3">
                    <div class="card__icon">
                        <span><i class="fa-sharp fa-regular fa-users"></i></span>
                    </div>
                    <div class="ms-2 card__title-wrap flex-grow-1">
                        <h6 class="card__sub-title mb-2">Total Contacts</h6>
                        <div class="d-flex align-items-end gap-2">
                            <h3 class="card__title">12000</h3>
                            <span class="card__desc style_two">
                                <i class="bi bi-arrow-up-right"></i> +12.5% vs last week
                            </span>
                        </div>

                    </div>
                </div>

            </div>
        </div>
        <div class="col-xxl-2 col-xl-4 col-lg-4 col-md-6 col-12">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-3">
                    <div class="card__icon">
                        <span><i class="bi bi-phone-fill"></i></span>
                    </div>
                    <div class="ms-2 card__title-wrap flex-grow-1">
                        <h6 class="card__sub-title mb-2">Active Device</h6>
                        <div class="d-flex align-items-end gap-2">
                            <h3 class="card__title">12000</h3>
                            <span class="card__desc style_two">
                                <i class="bi bi-arrow-up-right"></i> +12.5% vs last week
                            </span>
                        </div>

                    </div>
                </div>

            </div>
        </div>
        <div class="col-xxl-2 col-xl-4 col-lg-4 col-md-6 col-12">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-3">
                    <div class="card__icon">
                        <span><i class="bi bi-send-fill"></i></span>
                    </div>
                    <div class="ms-2 card__title-wrap flex-grow-1">
                        <h6 class="card__sub-title mb-2">Massages Today</h6>
                        <div class="d-flex align-items-end gap-2">
                            <h3 class="card__title">3000</h3>
                            <span class="card__desc style_two">
                                <i class="bi bi-arrow-up-right"></i> +12.5% vs last week
                            </span>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-2 col-xl-4 col-lg-4 col-md-6 col-12">
            <div class="card__wrapper" style="--accent:var(--red)">
                <div class="d-flex align-items-center gap-3">
                    <div class="card__icon">
                        <span><i class="bi bi-exclamation-triangle-fill"></i></span>
                    </div>
                    <div class="ms-2 card__title-wrap flex-grow-1">
                        <h6 class="card__sub-title mb-2">Failed Messages</h6>
                        <div class="d-flex align-items-end gap-2">
                            <h3 class="card__title">150</h3>
                            <span class="card__desc style_two">
                                <i class="bi bi-arrow-down-right"></i> -8.5% vs yesterday
                            </span>
                        </div>

                    </div>
                </div>

            </div>
        </div>
        <div class="col-xxl-2 col-xl-4 col-lg-4 col-md-6 col-12">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-3">
                    <div class="card__icon">
                        <span><i class="bi bi-send-fill"></i></span>
                    </div>
                    <div class="ms-2 card__title-wrap flex-grow-1">
                        <h6 class="card__sub-title mb-2">Success Rate</h6>
                        <div class="d-flex align-items-end gap-2">
                            <h3 class="card__title">3000</h3>
                            <span class="card__desc style_two">
                                <i class="bi bi-arrow-up-right"></i> +2.3% vs last week
                            </span>
                        </div>

                    </div>
                </div>

            </div>
        </div>
        <div class="col-xxl-2 col-xl-4 col-lg-4 col-md-6 col-12">

            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-3">
                    <div class="card__icon">
                        <span><i class="bi bi-send-fill"></i></span>
                    </div>
                    <div class="ms-2 card__title-wrap flex-grow-1">
                        <h6 class="card__sub-title mb-2">Active Campaigns</h6>
                        <div class="d-flex align-items-end gap-2">
                            <h3 class="card__title">4</h3>
                            <span class="card__desc style_two">
                                <i class="bi bi-arrow-up-right"></i> 2 launching soon
                            </span>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
    <!-- sales overview  -->
    <div class="row align-items-stretch">
        <div class="col-xxl-6 col-xl-12 col-lg-12 col-12 mb-3 ">
            <div class="card__wrapper height-equal">

                <div class="chart-card h-100">
                    <div class="d-flex align-items-start justify-content-between mb-4">
                        <div>
                            <div class="card-title">Campaign Performance</div>
                            <div class="card-sub">Messages sent vs delivered this week</div>
                        </div>
                        <div class="d-flex gap-3" style="font-size:12px;">
                            <span style="color:var(--wa-green);"><i class="bi bi-circle-fill" style="font-size:8px;"></i> Sent</span>
                            <span style="color:#60a5fa;"><i class="bi bi-circle-fill" style="font-size:8px;"></i> Delivered</span>
                        </div>
                    </div>
                    <canvas id="performanceChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xxl-6 col-xl-12 col-lg-12 col-12 mb-3 ">
                <div class=" card__wrapper shadow-sm mb-4">
                    <div class="card-header  border-bottom">
                        <h5 class="card-title mb-0">Team Activity</h5>
                        <small class="text-muted">Member performance today</small>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Member</th>
                                    <th scope="col">Role</th>
                                    <th scope="col" class="text-end">Messages</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="position-relative">
                                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">RS</div>
                                                <span class="position-absolute bottom-0 end-0 rounded-circle border border-white bg-success" style="width: 10px; height: 10px;"></span>
                                            </div>
                                            <span class="fw-medium">Rahul Sharma</span>
                                        </div>
                                    </td>
                                    <td>Marketing Manager</td>
                                    <td class="text-end">
                                        <div class="fw-semibold">450</div>
                                        <small class="text-muted">messages</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="position-relative">
                                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">PP</div>
                                                <span class="position-absolute bottom-0 end-0 rounded-circle border border-white bg-success" style="width: 10px; height: 10px;"></span>
                                            </div>
                                            <span class="fw-medium">Priya Patel</span>
                                        </div>
                                    </td>
                                    <td>Executive</td>
                                    <td class="text-end">
                                        <div class="fw-semibold">320</div>
                                        <small class="text-muted">messages</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="position-relative">
                                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">AK</div>
                                                <span class="position-absolute bottom-0 end-0 rounded-circle border border-white bg-warning" style="width: 10px; height: 10px;"></span>
                                            </div>
                                            <span class="fw-medium">Amit Kumar</span>
                                        </div>
                                    </td>
                                    <td>Executive</td>
                                    <td class="text-end">
                                        <div class="fw-semibold">180</div>
                                        <small class="text-muted">messages</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="position-relative">
                                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">NS</div>
                                                <span class="position-absolute bottom-0 end-0 rounded-circle border border-white bg-secondary" style="width: 10px; height: 10px;"></span>
                                            </div>
                                            <span class="fw-medium">Neha Singh</span>
                                        </div>
                                    </td>
                                    <td>Executive</td>
                                    <td class="text-end">
                                        <div class="fw-semibold">95</div>
                                        <small class="text-muted">messages</small>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
        </div>

        <!-- project wise flat sale  -->

        <!-- CHART + TEAM -->

        <!-- CAMPAIGNS TABLE -->
        <div class="col-12 mt-3">
            <div class="card__wrapper">
                <div class="card__header d-flex justify-content-between align-items-center mb-3">

                    <div>
                        <div class="card-title">Recent Campaigns</div>
                        <div class="card-sub">Your latest marketing campaigns</div>
                    </div>
                    <button class="btn-new" style="background:var(--surface2);color:var(--text);border:1px solid var(--border);">
                        View All <i class="bi bi-arrow-right"></i>
                    </button>

                </div>

                <div class="table__wrapper table-responsive">
                    <table class="table mb-20">
                        <thead>
                            <tr>
                                <th>Campaign</th>
                                <th>Status</th>
                                <th style="text-align:right;">Sent</th>
                                <th style="text-align:right;">Delivered</th>
                                <th style="text-align:right;">Failed</th>
                                <th style="text-align:right;">Date</th>
                                <th style="text-align:center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="campaign-name">Project Launch - Marina Heights</div>
                                    <div class="campaign-date">Real Estate Promo</div>
                                </td>
                                <td><span class="badge-status badge-completed">Completed</span></td>
                                <td style="text-align:right;" class="num-default">1,250</td>
                                <td style="text-align:right;" class="num-green">1,180</td>
                                <td style="text-align:right;" class="num-red">70</td>
                                <td style="text-align:right;" class="campaign-date">15/01/2024</td>
                                <td style="text-align:center;">
                                    <i class="bi bi-eye" style="color:var(--muted);cursor:pointer;margin-right:10px;"></i>
                                    <i class="bi bi-graph-up" style="color:var(--muted);cursor:pointer;"></i>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="campaign-name">Offer Reminder - Q1 Sale</div>
                                    <div class="campaign-date">Retail Promotion</div>
                                </td>
                                <td><span class="badge-status badge-active">Active</span></td>
                                <td style="text-align:right;" class="num-default">850</td>
                                <td style="text-align:right;" class="num-green">820</td>
                                <td style="text-align:right;" class="num-red">30</td>
                                <td style="text-align:right;" class="campaign-date">16/01/2024</td>
                                <td style="text-align:center;">
                                    <i class="bi bi-eye" style="color:var(--muted);cursor:pointer;margin-right:10px;"></i>
                                    <i class="bi bi-pause-circle" style="color:var(--muted);cursor:pointer;"></i>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="campaign-name">New Year Flash Sale</div>
                                    <div class="campaign-date">E-commerce</div>
                                </td>
                                <td><span class="badge-status badge-paused">Paused</span></td>
                                <td style="text-align:right;" class="num-default">2,100</td>
                                <td style="text-align:right;" class="num-green">1,990</td>
                                <td style="text-align:right;" class="num-red">110</td>
                                <td style="text-align:right;" class="campaign-date">01/01/2024</td>
                                <td style="text-align:center;">
                                    <i class="bi bi-eye" style="color:var(--muted);cursor:pointer;margin-right:10px;"></i>
                                    <i class="bi bi-play-circle" style="color:var(--muted);cursor:pointer;"></i>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="campaign-name">Welcome Series - New Users</div>
                                    <div class="campaign-date">Onboarding</div>
                                </td>
                                <td><span class="badge-status badge-draft">Draft</span></td>
                                <td style="text-align:right;" class="num-default">—</td>
                                <td style="text-align:right;" class="num-default">—</td>
                                <td style="text-align:right;" class="num-default">—</td>
                                <td style="text-align:right;" class="campaign-date">18/01/2024</td>
                                <td style="text-align:center;">
                                    <i class="bi bi-pencil" style="color:var(--muted);cursor:pointer;margin-right:10px;"></i>
                                    <i class="bi bi-send" style="color:var(--muted);cursor:pointer;"></i>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
</div>
<script>
    const ctx = document.getElementById('performanceChart').getContext('2d');
    const labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    const sentData = [1350, 2100, 2050, 1500, 2750, 1200, 900];
    const deliveredData = [1300, 2000, 1950, 1420, 2600, 1100, 850];

    const gradient1 = ctx.createLinearGradient(0, 0, 0, 300);
    gradient1.addColorStop(0, 'rgba(37,211,102,0.18)');
    gradient1.addColorStop(1, 'rgba(37,211,102,0)');

    const gradient2 = ctx.createLinearGradient(0, 0, 0, 300);
    gradient2.addColorStop(0, 'rgba(96,165,250,0.15)');
    gradient2.addColorStop(1, 'rgba(96,165,250,0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                    label: 'Sent',
                    data: sentData,
                    borderColor: '#25D366',
                    borderWidth: 2.5,
                    backgroundColor: gradient1,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#25D366',
                    pointBorderColor: '#0d1117',
                    pointBorderWidth: 2,
                },
                {
                    label: 'Delivered',
                    data: deliveredData,
                    borderColor: '#60a5fa',
                    borderWidth: 2.5,
                    backgroundColor: gradient2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#60a5fa',
                    pointBorderColor: '#0d1117',
                    pointBorderWidth: 2,
                }
            ]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#1c2230',
                    borderColor: 'rgba(255,255,255,0.08)',
                    borderWidth: 1,
                    titleColor: '#e6edf3',
                    bodyColor: '#7d8590',
                    padding: 12,
                    titleFont: {
                        family: 'Sora',
                        size: 13,
                        weight: '600'
                    },
                    bodyFont: {
                        family: 'DM Mono',
                        size: 12
                    },
                    callbacks: {
                        label: ctx => ` ${ctx.dataset.label}: ${ctx.parsed.y.toLocaleString()}`
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        color: 'rgba(255,255,255,0.04)',
                        drawBorder: false
                    },
                    ticks: {
                        color: '#7d8590',
                        font: {
                            family: 'Sora',
                            size: 12
                        }
                    },
                    border: {
                        display: false
                    }
                },
                y: {
                    grid: {
                        color: 'rgba(255,255,255,0.04)',
                        drawBorder: false
                    },
                    ticks: {
                        color: '#7d8590',
                        font: {
                            family: 'DM Mono',
                            size: 11
                        },
                        callback: v => v >= 1000 ? (v / 1000) + 'k' : v
                    },
                    border: {
                        display: false
                    }
                }
            }
        }
    });
</script>
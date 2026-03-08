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
            <div class="card-bg card-border mb-20 h-100">
                <div class="card__wrapper style_two h-100 d-flex flex-column">

                    <div class="chart-card h-100">
                        <div class="card-title">Team Activity</div>
                        <div class="card-sub mb-3">Member performance today</div>

                        <div class="team-member">
                            <div class="member-avatar" style="background:rgba(37,211,102,0.15);color:var(--wa-green);">
                                RS
                                <span class="online-dot dot-green"></span>
                            </div>
                            <div>
                                <div class="member-name">Rahul Sharma</div>
                                <div class="member-role">Marketing Manager</div>
                                <div class="progress-bar-custom" style="width:130px;">
                                    <div class="progress-fill" style="width:100%"></div>
                                </div>
                            </div>
                            <div class="member-msgs">
                                <div class="msg-count">450</div>
                                <div class="msg-label">messages</div>
                            </div>
                        </div>

                        <div class="team-member">
                            <div class="member-avatar" style="background:rgba(96,165,250,0.15);color:#60a5fa;">
                                PP
                                <span class="online-dot dot-green"></span>
                            </div>
                            <div>
                                <div class="member-name">Priya Patel</div>
                                <div class="member-role">Executive</div>
                                <div class="progress-bar-custom" style="width:130px;">
                                    <div class="progress-fill" style="width:71%;background:#60a5fa;"></div>
                                </div>
                            </div>
                            <div class="member-msgs">
                                <div class="msg-count">320</div>
                                <div class="msg-label">messages</div>
                            </div>
                        </div>

                        <div class="team-member">
                            <div class="member-avatar" style="background:rgba(244,185,66,0.15);color:var(--yellow);">
                                AK
                                <span class="online-dot dot-yellow"></span>
                            </div>
                            <div>
                                <div class="member-name">Amit Kumar</div>
                                <div class="member-role">Executive</div>
                                <div class="progress-bar-custom" style="width:130px;">
                                    <div class="progress-fill" style="width:40%;background:var(--yellow);"></div>
                                </div>
                            </div>
                            <div class="member-msgs">
                                <div class="msg-count">180</div>
                                <div class="msg-label">messages</div>
                            </div>
                        </div>

                        <div class="team-member">
                            <div class="member-avatar" style="background:rgba(192,132,252,0.15);color:#c084fc;">
                                NS
                                <span class="online-dot dot-gray"></span>
                            </div>
                            <div>
                                <div class="member-name">Neha Singh</div>
                                <div class="member-role">Executive</div>
                                <div class="progress-bar-custom" style="width:130px;">
                                    <div class="progress-fill" style="width:21%;background:#c084fc;"></div>
                                </div>
                            </div>
                            <div class="member-msgs">
                                <div class="msg-count">95</div>
                                <div class="msg-label">messages</div>
                            </div>
                        </div>

                    </div>
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
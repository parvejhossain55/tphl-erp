<?php
$page_title = "CED Dashboard";
$page = 'CED Dashboard';
$show_breadcrumb = true;

// Initialize calculation variables
$activeAgreements = 0;
$emiOnTrack = 0;
$overduePayments = 0;
$monthlyCollection = 0;
$totalAgreementValue = 0;
$totalCollected = 0;
$totalOutstanding = 0;
$pendingPayments = 0;

$currentMonth = date('Y-m');

// Chart data arrays
$monthlyChartLabels = [];
$monthlyCollectionData = [];
$monthlyOutstandingData = [];
$weeklyCollectionData = [0, 0, 0, 0, 0, 0, 0];
$paymentStatusData = [0, 0, 0, 0]; // [On Track, Pending, Overdue, Completed]

// Calculate statistics from clients data
if (!empty($clients)) {
    // Initialize monthly data for last 6 months
    for ($i = 5; $i >= 0; $i--) {
        $monthDate = date('Y-m', strtotime("-$i months"));
        $monthlyChartLabels[] = date('M Y', strtotime($monthDate . '-01'));
        $monthlyCollectionData[$monthDate] = 0;
        $monthlyOutstandingData[$monthDate] = 0;
    }

    foreach ($clients as $client) {
        $agreementValue = (float)($client['total_agreement_value'] ?? 0);
        $totalPaid = (float)($client['total_paid'] ?? 0);
        $outstanding = $agreementValue - $totalPaid;

        $totalAgreementValue += $agreementValue;
        $totalCollected += $totalPaid;
        $totalOutstanding += $outstanding;

        // Count active agreements (those with outstanding balance)
        if ($outstanding > 0) {
            $activeAgreements++;
        }

        // Count EMI on track (paid more than 50% and have recent payment)
        $paymentPercentage = $agreementValue > 0 ? ($totalPaid / $agreementValue) * 100 : 0;
        if ($paymentPercentage >= 50 && $paymentPercentage < 100) {
            $emiOnTrack++;
            $paymentStatusData[0]++; // On Track
        } elseif ($paymentPercentage >= 100) {
            $paymentStatusData[3]++; // Completed
        }

        // Count pending payments (less than 25% paid)
        if ($paymentPercentage < 25 && $outstanding > 0) {
            $pendingPayments++;
            $paymentStatusData[1]++; // Pending
        }

        // Check if last payment was this month
        if (!empty($client['last_payment_date'])) {
            $lastPaymentMonth = date('Y-m', strtotime($client['last_payment_date']));
            if ($lastPaymentMonth == $currentMonth) {
                $monthlyCollection += ($totalPaid * 0.1); // Approximation
            }

            // Populate monthly data
            if (isset($monthlyCollectionData[$lastPaymentMonth])) {
                $monthlyCollectionData[$lastPaymentMonth] += ($totalPaid * 0.1);
                $monthlyOutstandingData[$lastPaymentMonth] = $totalOutstanding;
            }

            // Weekly data (last 7 days)
            $daysSincePayment = (strtotime(date('Y-m-d')) - strtotime($client['last_payment_date'])) / (60 * 60 * 24);
            if ($daysSincePayment <= 7) {
                $dayOfWeek = 6 - floor($daysSincePayment);
                if ($dayOfWeek >= 0 && $dayOfWeek <= 6) {
                    $weeklyCollectionData[$dayOfWeek] += ($totalPaid * 0.1);
                }
            }
        }

        // Count overdue (no payment in last 60 days and outstanding balance)
        if (!empty($client['last_payment_date'])) {
            $daysSincePayment = (strtotime(date('Y-m-d')) - strtotime($client['last_payment_date'])) / (60 * 60 * 24);
            if ($daysSincePayment > 60 && $outstanding > 0) {
                $overduePayments++;
                $paymentStatusData[2]++; // Overdue
            }
        } elseif ($outstanding > 0) {
            $overduePayments++;
            $paymentStatusData[2]++; // Overdue
        }
    }
}

// Convert monthly data to arrays
$monthlyCollectionDataArray = array_values($monthlyCollectionData);
$monthlyOutstandingDataArray = array_values($monthlyOutstandingData);
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

    .chart-container {
        position: relative;
        height: 350px;
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

    .client-name-cell {
        font-weight: 600;
        color: #212529;
    }

    .table__icon {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #dee2e6;
        background: #fff;
        color: #6c757d;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .table__icon:hover {
        border-color: var(--clr-theme-primary);
        background: var(--clr-theme-primary);
        color: #fff;
    }

    .progress {
        background-color: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
    }

    .bd-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-block;
    }

    .bg-success {
        background-color: #d4edda !important;
        color: #155724 !important;
    }

    .bg-info {
        background-color: #d1ecf1 !important;
        color: #0c5460 !important;
    }

    .bg-warning {
        background-color: #fff3cd !important;
        color: #856404 !important;
    }

    .bg-danger {
        background-color: #f8d7da !important;
        color: #721c24 !important;
    }

    .bg-secondary {
        background-color: #e2e3e5 !important;
        color: #383d41 !important;
    }

    @media (max-width: 768px) {
        .card__title {
            font-size: 1.25rem;
        }

        .table__wrapper {
            overflow-x: auto;
        }
    }

    @media (max-width: 575px) {
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            font-size: 12px;
            padding: 6px 8px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            font-size: 12px;
            padding: 6px 16px;
        }
    }
</style>

<div class="row">
    <!-- Statistics Cards Row 1 -->
    <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-sm-6">
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

    <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-light fa-badge-check"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Full Payment Completion</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title"><?= $paymentStats['full_payment_completed'] ?? 0 ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-sharp fa-regular fa-check"></i></span>
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





    <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-light fa-circle-dollar"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Collected</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title">৳<?= number_format($totalCollected ?? 0, 0) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-light fa-percent"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Collection Rate</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <?php
                        $collectionRate = $totalAgreementValue > 0 ? ($totalCollected / $totalAgreementValue) * 100 : 0;
                        ?>
                        <h4 class="card__title"><?= number_format($collectionRate, 1) ?>%</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-sm-6">
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

    <div class="col-lg-12">
        <!-- Charts Section -->
        <div class="col-12  mb-3">
            <div class="card__wrapper height-equal">
                <div class="card__title-wrap d-flex flex-wrap gap-10 align-items-center justify-content-between mb-50">
                    <h5 class="card__heading-title">Collection & Outstanding Overview</h5>
                    <div class="card__tab">
                        <ul class="nav nav-pills" id="pills-tab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="pills-bar-year-tab" data-bs-toggle="pill"
                                    data-bs-target="#pills-bar-year" type="button" role="tab"
                                    aria-controls="pills-bar-year" aria-selected="true">6M</button>
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
                                <canvas id="collectionChartMonthly" class="card__title"></canvas>
                            </div>
                        </div>
                        <div class="tab-pane fade show active" id="pills-bar-week" role="tabpanel"
                            aria-labelledby="pills-bar-week-tab">
                            <div class="chart-container">
                                <canvas id="collectionChartWeek" class="card__title"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    </div>

    <!-- Clients Information Table -->
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="d-flex justify-content-between mb-20">
                <h4>Clients Information</h4>
                <a href="<?= $GLOBALS['base_url'] ?>/clients-cashin-view" class="bd-btn bd-btn-primary">
                    <div class="btn btn-primary">
                        View all
                        <i class="fa-solid fa-arrow-right ms-2"></i>
                    </div>
                </a>
            </div>

            <div class="table__wrapper table-responsive">
                <table class="table mb-20 multiple_tables" id="clientsTable">
                    <thead>
                        <tr class="table__title table__sort">
                            <th>Client Name</th>
                            <th>Company</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Assigned Agent</th>
                            <th>Designation</th>
                            <th>Total Agreement Value</th>
                            <th>Total Paid</th>
                            <th>Last Payment Date</th>

                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php if (!empty($clients)): ?>
                            <?php foreach ($clients as $client): ?>
                                <tr>
                                    <td><?= htmlspecialchars($client['client_name']) ?></td>
                                    <td><?= htmlspecialchars($client['company'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($client['phone'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($client['email'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($client['agent_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($client['designation'] ?? 'N/A') ?></td>
                                    <td>৳<?= number_format($client['total_agreement_value'] ?? 0, 2) ?></td>
                                    <td>৳<?= number_format($client['total_paid'] ?? 0, 2) ?></td>
                                    <td>
                                        <?= $client['last_payment_date'] ? date('d M, Y', strtotime($client['last_payment_date'])) : 'No Payment' ?>
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center">No clients found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Projects Modal -->
<div class="modal fade" id="projectsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Land Shares - <span id="modal_client_name"></span></h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>

            <div class="modal-body">
                <div id="projects_loading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading land shares...</p>
                </div>

                <div id="projects_content" style="display: none;">
                    <div class="table__wrapper table-responsive">
                        <table class="table">
                            <thead>
                                <tr class="table__title">
                                    <th>Project Name</th>
                                    <th>Unit - Floor</th>
                                    <th>Flat Size</th>
                                    <th>Total Price</th>
                                    <th>Agreement Value</th>
                                    <th>Total Paid</th>
                                    <th>Service Fee</th>
                                    <th>Booking Status</th>
                                    <th>Land Share Status</th>
                                    <th>Last Payment</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="projects_table_body">
                                <!-- Dynamic content will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="projects_error" class="alert alert-danger" style="display: none;">
                    <i class="fa-solid fa-exclamation-triangle"></i>
                    <span id="error_message"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
    let projectsModal;

    document.addEventListener('DOMContentLoaded', function() {
        projectsModal = new bootstrap.Modal(document.getElementById('projectsModal'));

        // Chart data from PHP
        const monthlyLabels = <?= json_encode($monthlyChartLabels) ?>;
        const monthlyCollectionData = <?= json_encode($monthlyCollectionDataArray) ?>;
        const monthlyOutstandingData = <?= json_encode($monthlyOutstandingDataArray) ?>;
        const weeklyCollectionData = <?= json_encode($weeklyCollectionData) ?>;
        const paymentStatusData = <?= json_encode($paymentStatusData) ?>;

        // Currency formatter
        const formatCurrency = (value) => '৳' + value.toLocaleString();

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
                '#10b981', // On Track - Green
                '#f59e0b', // Pending - Yellow
                '#ef4444', // Overdue - Red
                '#06b6d4' // Completed - Cyan
            ]
        };

        /* ==========================
            WEEKLY CHART (Animated Area Chart)
        ========================== */
        const ctxWeek = document.getElementById('collectionChartWeek');
        if (ctxWeek) {
            const collectionChartWeek = new Chart(ctxWeek.getContext('2d'), {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Collection',
                        data: weeklyCollectionData,
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
            MONTHLY CHART (Bar Chart with Collection & Outstanding)
        ========================== */
        const ctxMonth = document.getElementById('collectionChartMonthly');
        if (ctxMonth) {
            const collectionChartMonthly = new Chart(ctxMonth.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: monthlyLabels,
                    datasets: [{
                            label: 'Collection',
                            data: monthlyCollectionData,
                            backgroundColor: colors.success,
                            borderRadius: 8,
                            borderSkipped: false,
                            barThickness: 25,
                            maxBarThickness: 30
                        },
                        {
                            label: 'Outstanding',
                            data: monthlyOutstandingData,
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
                    animations: {
                        y: {
                            duration: 1500,
                            from: 0,
                            easing: 'easeOutBounce'
                        }
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
            PAYMENT STATUS CHART (Animated Pie Chart)
        ========================== */
        const ctxPayment = document.getElementById('paymentStatusChart');
        if (ctxPayment) {
            const paymentStatusChart = new Chart(ctxPayment.getContext('2d'), {
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
                                    return label + ': ' + value + ' clients (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
    });

    function openProjectsModal(leadId, clientName) {
        console.log('Opening modal for lead ID:', leadId);

        document.getElementById('modal_client_name').textContent = clientName;

        // Reset modal state
        document.getElementById('projects_loading').style.display = 'block';
        document.getElementById('projects_content').style.display = 'none';
        document.getElementById('projects_error').style.display = 'none';

        projectsModal.show();

        const url = '<?= $GLOBALS['base_url'] ?>/get-client-lands?lead_id=' + leadId;

        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.text();
            })
            .then(text => {
                try {
                    const data = JSON.parse(text);

                    if (data.success) {
                        if (data.lands && data.lands.length > 0) {
                            displayProjects(data.lands);
                        } else {
                            showError('No land shares found for this client');
                        }
                    } else {
                        showError(data.message || 'Failed to load land shares');
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    showError('Invalid response from server');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showError('Failed to load land shares: ' + error.message);
            });
    }

    function displayProjects(lands) {
        const tbody = document.getElementById('projects_table_body');
        tbody.innerHTML = '';

        lands.forEach(land => {
            const row = document.createElement('tr');

            const bookingStatusClass = getStatusClass(land.booking_status);
            const landshareStatusClass = getStatusClass(land.landshare_status);

            row.innerHTML = `
            <td>${escapeHtml(land.project_name)}</td>
            <td>${escapeHtml(land.unit_name)} - Floor ${escapeHtml(land.flat_no)}</td>
            <td>${Number(land.flat_size).toLocaleString('en-BD')} sqft</td>
            <td>৳${Number(land.total_price).toLocaleString('en-BD', {minimumFractionDigits: 2})}</td>
            <td>৳${Number(land.agreement_value).toLocaleString('en-BD', {minimumFractionDigits: 2})}</td>
            <td>৳${Number(land.total_paid || 0).toLocaleString('en-BD', {minimumFractionDigits: 2})}</td>
            <td>
                <div>
                    <strong>Fee:</strong> ৳${Number(land.total_service_fee || 0).toLocaleString('en-BD', {minimumFractionDigits: 2})}
                </div>
                <div class="text-muted small">
                    <strong>Balance:</strong> ৳${Number(land.service_fee_balance || 0).toLocaleString('en-BD', {minimumFractionDigits: 2})}
                </div>
            </td>
            <td>
                <span class="bd-badge ${bookingStatusClass}">
                    ${escapeHtml(land.booking_status || 'N/A')}
                </span>
            </td>
            <td>
                <span class="bd-badge ${landshareStatusClass}">
                    ${escapeHtml(land.landshare_status || 'N/A')}
                </span>
            </td>
            <td>
                ${land.last_payment_date ? formatDate(land.last_payment_date) : 'No Payment'}
            </td>
            <td>
                <a class="table__icon download"
                    href="<?= $GLOBALS['base_url'] ?>/clients-cash-in-history-view/${land.booking_id}"
                    title="View Transaction History">
                    <i class="fa-regular fa-eye"></i>
                </a>
            </td>
        `;

            tbody.appendChild(row);
        });

        document.getElementById('projects_loading').style.display = 'none';
        document.getElementById('projects_content').style.display = 'block';
    }

    function showError(message) {
        document.getElementById('error_message').textContent = message;
        document.getElementById('projects_loading').style.display = 'none';
        document.getElementById('projects_error').style.display = 'block';
    }

    function getStatusClass(status) {
        if (!status) return 'bg-secondary';

        status = status.toLowerCase();

        if (status === 'booked' || status === 'active' || status === 'approved' || status === 'paid') {
            return 'bg-success';
        } else if (status === 'pending') {
            return 'bg-warning';
        } else if (status === 'cancelled' || status === 'rejected') {
            return 'bg-danger';
        } else {
            return 'bg-info';
        }
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        const options = {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        };
        return date.toLocaleDateString('en-GB', options);
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }
</script>
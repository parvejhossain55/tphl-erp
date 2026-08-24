<?php
$base_url = $GLOBALS['base_url'];
// Extract variables from $data injected by Controller
$filterDeptId = $filterDeptId ?? null;
$filterUserId = $filterUserId ?? null;
$filterDateFrom = $filterDateFrom ?? date('Y-m-01');
$filterDateTo = $filterDateTo ?? date('Y-m-d');
?>
<style>
/* Custom styles for the dashboard to match the design */
.dash-card { border: 1px solid #eaedf1; border-radius: 8px; background: #fff; padding: 20px; height: 100%; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
.dash-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.dash-title { font-size: 24px; font-weight: 700; color: #111827; margin: 0; }
.dash-subtitle { font-size: 13px; color: #6b7280; margin: 4px 0 0 0; }
.stat-value { font-size: 28px; font-weight: 700; color: #111827; line-height: 1.2; margin-top: 5px; }
.stat-label { font-size: 13px; font-weight: 600; color: #374151; display: flex; align-items: center; gap: 8px; }
.stat-subtext { font-size: 12px; color: #6b7280; margin-top: 5px; }
.icon-circle { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
.icon-circle.green { background: #dcfce7; color: #16a34a; }
.icon-circle.yellow { background: #fef3c7; color: #d97706; }
.icon-circle.red { background: #fee2e2; color: #dc2626; }
.icon-circle.blue { background: #e0e7ff; color: #4f46e5; }
.icon-circle.gray { background: #f3f4f6; color: #4b5563; }

.section-title { font-size: 16px; font-weight: 600; color: #111827; display: flex; align-items: center; gap: 8px; margin-bottom: 15px; }
.info-icon { color: #9ca3af; font-size: 14px; cursor: help; }

/* Progress bars */
.dept-progress-wrap { margin-bottom: 12px; }
.dept-progress-header { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 4px; color: #374151; font-weight: 500;}
.progress-thin { height: 6px; border-radius: 3px; background-color: #f3f4f6; overflow: hidden; }
.progress-thin .progress-bar { border-radius: 3px; }

/* Status Badges */
.status-badge { padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 600; border: 1px solid transparent; }
.status-badge.healthy { background: #dcfce7; color: #16a34a; border-color: #bbf7d0; }
.status-badge.moderate { background: #fef9c3; color: #ca8a04; border-color: #fef08a; }
.status-badge.low { background: #ffedd5; color: #ea580c; border-color: #fed7aa; }
.status-badge.critical { background: #fee2e2; color: #dc2626; border-color: #fecaca; }
.status-badge.review { background: #fef3c7; color: #d97706; border-color: #fde68a; }
.status-badge.outside { background: #f3f4f6; color: #4b5563; border-color: #e5e7eb; }

/* Table Tabs */
.custom-tabs { display: flex; gap: 20px; border-bottom: 1px solid #e5e7eb; margin-bottom: 20px; padding: 0 20px; }
.custom-tab { padding: 10px 0; font-size: 14px; font-weight: 600; color: #6b7280; cursor: pointer; border-bottom: 2px solid transparent; display: flex; align-items: center; gap: 8px; }
.custom-tab.active { color: #16a34a; border-bottom-color: #16a34a; }
.custom-tab .count { font-size: 12px; padding: 2px 6px; border-radius: 10px; background: #f3f4f6; color: #4b5563; }
.custom-tab.active .count { background: #dcfce7; color: #16a34a; }

/* Standard Usage Window Box */
.standard-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 20px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
.standard-box-item { display: flex; align-items: center; gap: 12px; }
.standard-box-divider { width: 1px; height: 30px; background: #e2e8f0; margin: 0 20px; }

/* Avatar initials */
.avatar-initials { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; color: white; flex-shrink: 0; }

.table-person td { vertical-align: middle; font-size: 13px; color: #374151; border-bottom-width: 1px;}
.table-person th { font-size: 12px; text-transform: none; color: #6b7280; border-bottom-width: 1px; font-weight: 600; }
</style>

<div class="container-fluid py-4">

    <!-- ── Header ──────────────────────────────────────────────────────────── -->
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <div class="text-muted fs-13 mb-1">HR & Payroll / User Activity</div>
            <h1 class="dash-title">ERP Usage & Adoption</h1>
            <p class="dash-subtitle">Track individual usage, working-hour activity and system adoption</p>
        </div>
        
        <form method="GET" action="<?= $base_url ?>/user-activity-log" id="filterForm" class="d-flex align-items-center gap-2">
            <!-- Date Range -->
            <div class="input-group input-group-sm" style="min-width: 320px;">
                <span class="input-group-text "><i class="fa-regular fa-calendar"></i></span>
                <input type="date" name="date_from" class="form-control form-control-sm border-start-0 px-1" value="<?= $filterDateFrom ?>" onchange="this.form.submit()">
                <span class="input-group-text border-start-0 border-end-0 px-1">-</span>
                <input type="date" name="date_to" class="form-control form-control-sm border-start-0 px-1" value="<?= $filterDateTo ?>" onchange="this.form.submit()">
            </div>
            
            <!-- Department -->
            <select name="department_id" class="form-select form-select-sm" style="width: 160px;" onchange="this.form.submit()">
                <option value="">All Departments</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?= $dept['id'] ?>" <?= ($filterDeptId == $dept['id']) ? 'selected' : '' ?>><?= htmlspecialchars($dept['name']) ?></option>
                <?php endforeach; ?>
            </select>
            
            <!-- Employee Search -->
            <select name="user_id" class="form-select form-select-sm select2" style="width: 200px;" onchange="this.form.submit()" data-placeholder="Search employee...">
                <option value="">Search employee...</option>
                <?php foreach ($allUsers as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= ($filterUserId == $u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['full_name']) ?></option>
                <?php endforeach; ?>
            </select>

            <!-- Export -->
            <button type="button" class="btn btn-sm btn-outline-success  d-flex align-items-center gap-1" data-bs-toggle="dropdown">
                <i class="fa-solid fa-download"></i> Export Report
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="?<?= http_build_query(array_merge($_GET, ['export' => 'excel'])) ?>"><i class="fa-solid fa-file-excel text-success me-2"></i> Excel</a></li>
                <li><a class="dropdown-item" href="?<?= http_build_query(array_merge($_GET, ['export' => 'pdf'])) ?>"><i class="fa-solid fa-file-pdf text-danger me-2"></i> PDF</a></li>
            </ul>
        </form>
    </div>

    <!-- ── Standard Usage Info Box ─────────────────────────────────────────── -->
    <div class="standard-box">
        <div class="d-flex align-items-center">
            <div class="standard-box-item">
                <i class="fa-regular fa-clock fs-4 text-muted"></i>
                <div>
                    <div class="fs-12 fw-semibold text-muted">Standard Usage Window</div>
                    <div class="fw-bold fs-14">9:30 AM – 6:30 PM</div>
                </div>
            </div>
            <div class="standard-box-divider"></div>
            <div class="standard-box-item">
                <i class="fa-solid fa-hourglass-half fs-4 text-success"></i>
                <div>
                    <div class="fs-12 fw-semibold text-muted">Daily Expected Active Time</div>
                    <div class="fw-bold fs-14">4h</div>
                </div>
            </div>
        </div>
        <button class="btn btn-sm btn-light border d-flex align-items-center gap-2 text-success fw-semibold">
            <i class="fa-solid fa-pen fs-12"></i> Edit
        </button>
    </div>

    <!-- ── 6 Top Summary Cards ─────────────────────────────────────────────── -->
    <div class="row  mb-3">
        <!-- 1 -->
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="dash-card p-3">
                <div class="stat-label">
                    <div class="icon-circle green"><i class="fa-solid fa-users"></i></div>
                    Total ERP Users
                </div>
                <div class="stat-value"><?= $totalUsers ?></div>
                <div class="stat-subtext">All registered users</div>
            </div>
        </div>
        <!-- 2 -->
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="dash-card p-3 position-relative">
                <div class="stat-label">
                    <div class="icon-circle green"><i class="fa-solid fa-arrow-trend-up"></i></div>
                    Active Today
                </div>
                <div class="stat-value"><?= $activeToday ?></div>
                <div class="stat-subtext text-success fw-semibold"><?= $adoptionScore ?>% adoption <i class="fa-solid fa-check"></i></div>
                <div id="spark1" class="position-absolute bottom-0 end-0 me-2 mb-2" style="width: 60px;"></div>
            </div>
        </div>
        <!-- 3 -->
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="dash-card p-3 position-relative">
                <div class="stat-label">
                    <div class="icon-circle green"><i class="fa-regular fa-circle-check"></i></div>
                    Meeting Standard
                </div>
                <div class="stat-value"><?= $meetingStandard ?></div>
                <div class="stat-subtext text-success fw-semibold">≥ 4h active</div>
                <div id="spark2" class="position-absolute bottom-0 end-0 me-2 mb-2" style="width: 60px;"></div>
            </div>
        </div>
        <!-- 4 -->
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="dash-card p-3 position-relative border border-warning" style="background-color: #fffbeb;">
                <div class="stat-label">
                    <div class="icon-circle yellow "><i class="fa-solid fa-triangle-exclamation"></i></div>
                    Below Standard
                </div>
                <div class="stat-value"><?= $belowStandard ?></div>
                <div class="stat-subtext text-warning fw-semibold">Needs attention</div>
                <div id="spark3" class="position-absolute bottom-0 end-0 me-2 mb-2" style="width: 60px;"></div>
            </div>
        </div>
        <!-- 5 -->
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="dash-card p-3 position-relative border border-danger" style="background-color: #fef2f2;">
                <div class="stat-label">
                    <div class="icon-circle red "><i class="fa-regular fa-circle-xmark"></i></div>
                    No Usage Today
                </div>
                <div class="stat-value text-danger"><?= $noUsageToday ?></div>
                <div class="stat-subtext text-danger fw-semibold">No activity logged</div>
                <div id="spark4" class="position-absolute bottom-0 end-0 me-2 mb-2" style="width: 60px;"></div>
            </div>
        </div>
        <!-- 6 -->
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="dash-card p-3 position-relative">
                <div class="stat-label">
                    <div class="icon-circle green"><i class="fa-regular fa-clock"></i></div>
                    Avg. Productive Time
                </div>
                <?php
                    $avgH = intdiv($avgProductiveMins, 60);
                    $avgM = $avgProductiveMins % 60;
                ?>
                <div class="stat-value"><?= $avgH ?>h <?= $avgM ?>m</div>
                <div class="stat-subtext text-success fw-semibold">Avg across active users</div>
                <div id="spark5" class="position-absolute bottom-0 end-0 me-2 mb-2" style="width: 60px;"></div>
            </div>
        </div>
    </div>

    <!-- ── Charts Row ──────────────────────────────────────────────────────── -->
    <div class="row  mb-3">
        <!-- Hourly Active Users -->
        <div class="col-lg-8">
            <div class="dash-card">
                <div class="section-title">Hourly Active Users <i class="fa-solid fa-circle-info info-icon" title="Unique active users per hour"></i></div>
                <div class="d-flex justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2 fs-12">
                        <span class="d-inline-block rounded-pill bg-success opacity-25" style="width:30px;height:10px;"></span>
                        <span class="text-muted fw-semibold">Standard Work Window (9:30 AM - 6:30 PM)</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 fs-12">
                        <span class="d-inline-block border-top border-2 border-dashed border-secondary" style="width:20px;"></span>
                        <span class="text-muted fw-semibold">Expected <?= round($totalUsers * 0.8) ?> active users</span>
                    </div>
                </div>
                <div id="hourlyChart" style="min-height: 250px;"></div>
            </div>
        </div>
        
        <!-- Usage Health -->
        <div class="col-lg-4">
            <div class="dash-card">
                <div class="section-title">Usage Health <i class="fa-solid fa-circle-info info-icon" title="Based on average daily active time"></i></div>
                <div class="position-relative d-flex justify-content-center align-items-center" style="height: 270px;">
                    <div id="healthChart" class="w-100 h-100"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Bottom Row (Dept Adoption & Attention) ──────────────────────────── -->
    <div class="row  mb-3">
        <!-- Dept Adoption -->
        <div class="col-lg-6">
            <div class="dash-card">
                <div class="section-title">Department Adoption <i class="fa-solid fa-circle-info info-icon"></i></div>
                <div class="d-flex justify-content-between text-muted fs-12 fw-semibold mb-3">
                    <div>Department</div>
                    <div>Adoption Rate</div>
                </div>
                
                <?php
                $colors = ['#16a34a', '#22c55e', '#eab308', '#f59e0b', '#ea580c', '#dc2626'];
                foreach ($deptAdoptionRates as $idx => $dept):
                    $c = $colors[min($idx, count($colors)-1)];
                    if ($dept['rate'] >= 80) $c = '#16a34a';
                    elseif ($dept['rate'] >= 60) $c = '#eab308';
                    else $c = '#ea580c';
                ?>
                <div class="dept-progress-wrap">
                    <div class="dept-progress-header">
                        <span><?= htmlspecialchars($dept['name']) ?></span>
                        <span class="fw-bold"><?= $dept['rate'] ?>%</span>
                    </div>
                    <div class="progress progress-thin">
                        <div class="progress-bar" role="progressbar" style="width: <?= $dept['rate'] ?>%; background-color: <?= $c ?>;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Attention Required -->
        <div class="col-lg-6">
            <div class="dash-card d-flex flex-column">
                <div class="section-title">Attention Required <i class="fa-solid fa-circle-info info-icon"></i></div>
                <div class="table-responsive flex-grow-1">
                    <table class="table table-borderless table-sm fs-13 table-person">
                        <thead>
                            <tr>
                                <th class="text-start">Employee</th>
                                <th>Department</th>
                                <th>Issue</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($attentionRequired)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-3">No critical issues found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($attentionRequired as $p): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar-initials" style="background-color: <?= stringToColorCode($p['full_name']) ?>"><?= $p['initials'] ?></div>
                                            <span class="fw-semibold text-dark"><?= htmlspecialchars($p['full_name']) ?></span>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($p['department']) ?></td>
                                    <td><span class="text-muted"><?= $p['issue'] ?></span></td>
                                    <td>
                                        <span class="status-badge <?= strtolower(str_replace(' ', '', $p['attn_status'])) ?>">
                                            <?= $p['attn_status'] ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-end mt-auto pt-2">
                    <a href="#personTable" class="text-success text-decoration-none fs-13 fw-semibold">View all users <i class="fa-solid fa-angle-right ms-1"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Person-wise Usage Performance ───────────────────────────────────── -->
    <div class="dash-card p-0" id="personTable">
        <div class="p-4 pb-0">
            <div class="section-title mb-2">Person-wise Usage Performance <i class="fa-solid fa-circle-info info-icon" title="Detailed breakdown per user"></i></div>
            <div class="text-end mb-2">
                <span class="text-muted fs-12">Usage status is calculated from active time, login consistency and idle ratio.</span>
            </div>
        </div>
        
        <?php $usageHealthDataDecoded = json_decode($usageHealthData, true); ?>
        
        <div class="custom-tabs">
            <div class="custom-tab active" data-filter="All">All <span class="count"><?= count($personWiseData) ?></span></div>
            <div class="custom-tab" data-filter="Healthy">Healthy <span class="count"><?= $usageHealthDataDecoded[0] ?? 0 ?></span></div>
            <div class="custom-tab" data-filter="Below Standard">Below Standard <span class="count"><?= ($usageHealthDataDecoded[1]??0) + ($usageHealthDataDecoded[2]??0) ?></span></div>
            <div class="custom-tab" data-filter="No Usage">No Usage <span class="count"><?= $usageHealthDataDecoded[3] ?? 0 ?></span></div>
        </div>

        <div class="table-responsive px-4 pb-4">
            <table class="table table-hover table-person w-100" id="dtPersonUsage">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Days Used</th>
                        <th>Avg. Login</th>
                        <th>Avg. Logout</th>
                        <th>Active Time / Day<br><small class="text-muted">(Productive)</small></th>
                        <!-- <th>Actions Completed</th> -->
                        <th>Idle Time</th>
                        <th>Standard Met</th>
                        <th>Usage Status</th>
                        <th>Last Active</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($personWiseData as $p): 
                        $statusClass = strtolower(str_replace(' ', '', $p['usage_status']));
                        if ($statusClass == 'moderate') $statusClass = 'review';
                        
                        $activeH = intdiv($p['active_time_day'], 60);
                        $activeM = $p['active_time_day'] % 60;
                        
                        $activeStr = '';
                        if ($activeH > 0) $activeStr .= "<span class='fw-bold text-success'>{$activeH}h</span> ";
                        $activeStr .= "<span class='fw-bold text-success'>{$activeM}m</span>";
                        if ($p['active_time_day'] == 0) $activeStr = "<span class='text-danger fw-bold'>0m</span>";
                        elseif ($p['active_time_day'] < 240) {
                            $activeStr = "<span class='text-warning fw-bold'>{$activeH}h {$activeM}m</span>";
                        }
                    ?>
                    <tr data-status="<?= $p['usage_status'] ?>">
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-initials" style="background-color: <?= stringToColorCode($p['full_name']) ?>"><?= $p['initials'] ?></div>
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($p['full_name']) ?></span>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($p['department']) ?></td>
                        <td><?= $p['days_used'] ?>/<?= $p['working_days'] ?></td>
                        <td><i class="fa-regular fa-clock text-muted me-1 fs-11"></i><?= $p['avg_login'] ?></td>
                        <td><i class="fa-regular fa-clock text-muted me-1 fs-11"></i><?= $p['avg_logout'] ?></td>
                        <td><?= $activeStr ?></td>
                        <!-- <td>-</td> -->
                        <td class="<?= $p['idle_percentage'] > 50 ? 'text-danger fw-bold' : 'text-success fw-bold' ?>"><?= $p['idle_percentage'] ?>%</td>
                        <td class="fw-bold <?= $p['standard_met_days'] > 0 ? 'text-success' : 'text-danger' ?>"><?= $p['standard_met_days'] ?> days</td>
                        <td><span class="status-badge <?= $statusClass ?>"><?= $p['usage_status'] ?></span></td>
                        <td>
                            <?php if ($p['last_login_ever']): ?>
                                <span class="d-block text-dark fs-12"><?= date('d M Y', strtotime($p['last_login_ever'])) ?></span>
                                <span class="text-muted fs-11"><?= $p['last_active'] ?></span>
                            <?php else: ?>
                                <span class="text-muted fs-12">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="javascript:void(0)" class="text-success fw-semibold text-decoration-none me-3 fs-12">View details</a>
                            <i class="fa-solid fa-ellipsis-vertical text-muted cursor-pointer px-2"></i>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// Helper for avatar colors
function stringToColorCode($str) {
    $code = dechex(crc32($str));
    $code = substr($code, 0, 6);
    return "#" . $code;
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // ApexCharts settings for Sparklines
    const commonSparklineOpts = {
        chart: { type: 'line', width: 60, height: 25, sparkline: { enabled: true } },
        stroke: { curve: 'smooth', width: 2 },
        tooltip: { fixed: { enabled: false }, x: { show: false }, y: { title: { formatter: function (seriesName) { return '' } } }, marker: { show: false } }
    };

    if(document.querySelector("#spark1")) new ApexCharts(document.querySelector("#spark1"), { ...commonSparklineOpts, series: [{ data: [12, 14, 2, 47, 42, 15, 47, 75, 65, 19, 14] }], colors: ['#16a34a'] }).render();
    if(document.querySelector("#spark2")) new ApexCharts(document.querySelector("#spark2"), { ...commonSparklineOpts, series: [{ data: [47, 45, 74, 14, 56, 37, 48, 11, 71, 15, 65] }], colors: ['#16a34a'] }).render();
    if(document.querySelector("#spark3")) new ApexCharts(document.querySelector("#spark3"), { ...commonSparklineOpts, series: [{ data: [15, 75, 47, 65, 14, 2, 41, 54, 4, 27, 15] }], colors: ['#d97706'] }).render();
    if(document.querySelector("#spark4")) new ApexCharts(document.querySelector("#spark4"), { ...commonSparklineOpts, series: [{ data: [47, 15, 2, 67, 22, 20, 36, 60, 60, 30, 50] }], colors: ['#dc2626'] }).render();
    if(document.querySelector("#spark5")) new ApexCharts(document.querySelector("#spark5"), { ...commonSparklineOpts, series: [{ data: [12, 14, 2, 47, 42, 15, 47, 75, 65, 19, 14] }], colors: ['#16a34a'] }).render();

    // Hourly Active Users Chart
    const hourlyLabels = <?= $hourlyLabels ?>;
    const hourlyData = <?= $hourlyData ?>;
    const expectedTarget = Math.round(<?= $totalUsers ?> * 0.8);

    const hourlyOptions = {
        series: [{ name: 'Active Users', data: hourlyData }],
        chart: { type: 'area', height: 250, toolbar: { show: false }, zoom: { enabled: false } },
        colors: ['#16a34a'],
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 3 },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0, stops: [0, 90, 100] } },
        xaxis: { categories: hourlyLabels, axisBorder: { show: false }, axisTicks: { show: false }, labels: { style: { colors: '#6b7280', fontSize: '12px', fontWeight: 500 } } },
        yaxis: { min: 0, tickAmount: 4, labels: { style: { colors: '#6b7280', fontSize: '12px' } } },
        grid: { borderColor: '#f3f4f6', strokeDashArray: 4, yaxis: { lines: { show: true } }, padding: { top: 0, right: 0, bottom: 0, left: 10 } },
        annotations: {
            yaxis: [{
                y: expectedTarget,
                borderColor: '#9ca3af',
                strokeDashArray: 4,
                label: { borderColor: 'transparent', style: { color: '#6b7280', background: 'transparent' }, text: 'Expected ' + expectedTarget + ' active users', position: 'right', offsetX: -10 }
            }],
            xaxis: [{
                x: '9 AM', x2: '6 PM', fillColor: '#dcfce7', opacity: 0.2
            }]
        },
        tooltip: { theme: 'light' }
    };
    if(document.querySelector("#hourlyChart")) {
        new ApexCharts(document.querySelector("#hourlyChart"), hourlyOptions).render();
    }

    // Usage Health Donut Chart
    const healthData = <?= $usageHealthData ?>; // [Healthy, Moderate, Low, No]
    const healthOptions = {
        series: healthData,
        labels: ['Healthy', 'Moderate', 'Low Usage', 'No Usage'],
        chart: { type: 'donut', height: 260 },
        colors: ['#16a34a', '#eab308', '#f97316', '#ef4444'],
        plotOptions: {
            pie: {
                donut: { 
                    size: '65%', 
                    labels: { 
                        show: true,
                        name: {
                            show: true,
                            fontSize: '11px',
                            fontWeight: 600,
                            color: '#6b7280',
                            offsetY: -5
                        },
                        value: {
                            show: true,
                            fontSize: '24px',
                            fontWeight: 700,
                            color: '#16a34a',
                            offsetY: 5,
                            formatter: function (val) {
                                return val;
                            }
                        },
                        total: {
                            show: true,
                            showAlways: true,
                            label: 'ERP Adoption Score',
                            fontSize: '11px',
                            fontWeight: 600,
                            color: '#6b7280',
                            formatter: function (w) {
                                return "<?= $adoptionScore ?>/100";
                            }
                        }
                    } 
                },
                expandOnClick: false
            }
        },
        dataLabels: { enabled: true, formatter: function (val, opts) { return opts.w.config.series[opts.seriesIndex] }, style: { fontSize: '14px', fontWeight: 'bold' }, dropShadow: { enabled: false } },
        legend: { 
            position: 'right', 
            offsetY: 0, 
            height: 200, 
            markers: { radius: 12, width: 12, height: 12 }, 
            itemMargin: { vertical: 6 }, 
            formatter: function(seriesName, opts) {
                return "<span style='color:#4b5563;font-size:13px;'>" + seriesName + "</span> <span class='fw-bold ms-3 float-end' style='color:#111827;font-size:14px;'>" + opts.w.config.series[opts.seriesIndex] + "</span>";
            }
        },
        stroke: { width: 2, colors: ['#fff'] },
        tooltip: { enabled: false }
    };
    if(document.querySelector("#healthChart")) {
        new ApexCharts(document.querySelector("#healthChart"), healthOptions).render();
    }

    // DataTable initialization for Person-wise Table
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        var table = $('#dtPersonUsage').DataTable({
            "pageLength": 15,
            "lengthChange": false,
            "searching": true,
            "info": false,
            "autoWidth": false,
            "order": [],
            "dom": '<"top">rt<"bottom"p><"clear">', // hide default search box to use custom if needed, but keeping simple for now
            "language": {
                "paginate": {
                    "previous": "<i class='fa-solid fa-angle-left'></i>",
                    "next": "<i class='fa-solid fa-angle-right'></i>"
                }
            }
        });

        // Tab Filtering Logic
        $('.custom-tab').on('click', function() {
            $('.custom-tab').removeClass('active');
            $(this).addClass('active');
            
            var filter = $(this).data('filter');
            
            // Clear previous custom search
            $.fn.dataTable.ext.search.pop();
            
            if (filter !== 'All') {
                $.fn.dataTable.ext.search.push(
                    function(settings, data, dataIndex, rowData, counter) {
                        var rowNode = table.row(dataIndex).node();
                        var status = $(rowNode).attr('data-status');
                        
                        if (filter === 'Below Standard') {
                            return (status === 'Low Usage' || status === 'Moderate');
                        }
                        return status === filter;
                    }
                );
            }
            table.draw();
        });
    }

    // Select2 init for employee search in header
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('.select2').select2();
    }
});
</script>

<?php
$base_url = $GLOBALS['base_url'];
// Extract variables from $data injected by Controller
$filterDeptId = $filterDeptId ?? null;
$filterUserId = $filterUserId ?? null;
$filterDateFrom = $filterDateFrom ?? date('Y-m-01');
$filterDateTo = $filterDateTo ?? date('Y-m-d');
$activeTab = $activeTab ?? (($filterDeptId == 1) ? 'sales' : 'adoption');
$salesTeamPerformance = $salesTeamPerformance ?? [];
$salesTotals = $salesTotals ?? [
    'total_sales_users'      => 0,
    'total_assigned_leads'   => 0,
    'total_followups'        => 0,
    'total_visited'          => 0,
    'total_hot'              => 0,
    'total_cold'             => 0,
    'total_no_followup'      => 0,
    'total_booked'           => 0,
    'total_land_share_value' => 0.0
];
?>
<style>
/* Base & Card styles */
.dash-card { border: 1px solid #eaedf1; border-radius: 10px; background: #fff; padding: 20px; height: 100%; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
.dash-title { font-size: 22px; font-weight: 700; color: #0f172a; margin: 0; }
.dash-subtitle { font-size: 13px; color: #64748b; margin: 2px 0 0 0; }
.stat-value { font-size: 26px; font-weight: 700; color: #0f172a; line-height: 1.2; margin-top: 5px; }
.stat-label { font-size: 13px; font-weight: 600; color: #334155; display: flex; align-items: center; gap: 8px; }
.stat-subtext { font-size: 12px; color: #64748b; margin-top: 4px; }

/* Filter Toolbar */
.filter-toolbar {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px 18px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    margin-bottom: 22px;
}
.filter-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.filter-item-label {
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin: 0;
}
.filter-item .select2-container {
    width: 100% !important;
}
.filter-item .select2-container .select2-selection--single {
    height: 34px !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 6px !important;
    display: flex;
    align-items: center;
}
.filter-item .select2-container .select2-selection--single .select2-selection__rendered {
    line-height: 32px !important;
    font-size: 13px !important;
    padding-left: 10px !important;
    color: #1e293b !important;
}
.filter-item .select2-container .select2-selection--single .select2-selection__arrow {
    height: 32px !important;
}

/* Icon circle badges */
.icon-circle { width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; }
.icon-circle.green { background: #dcfce7; color: #16a34a; }
.icon-circle.yellow { background: #fef3c7; color: #d97706; }
.icon-circle.red { background: #fee2e2; color: #dc2626; }
.icon-circle.blue { background: #e0e7ff; color: #3b82f6; }
.icon-circle.purple { background: #f3e8ff; color: #9333ea; }
.icon-circle.teal { background: #ccfbf1; color: #0d9488; }
.icon-circle.gray { background: #f1f5f9; color: #64748b; }

/* 5 KPI Cards Grid in EXACTLY ONE ROW */
.kpi-cards-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 14px;
    margin-bottom: 22px;
}
@media (max-width: 1200px) {
    .kpi-cards-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
@media (max-width: 768px) {
    .kpi-cards-grid {
        grid-template-columns: repeat(1, 1fr);
    }
}

.kpi-card {
    border: 1px solid #eaedf1;
    border-radius: 10px;
    background: #ffffff;
    padding: 16px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    min-height: 110px;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}
.kpi-card .kpi-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 7px;
    margin-bottom: 6px;
}
.kpi-card .kpi-value {
    font-size: 24px;
    font-weight: 700;
    line-height: 1.15;
    margin-bottom: 4px;
}
.kpi-card .kpi-subtext {
    font-size: 11px;
    color: #94a3b8;
}

/* Main Navigation Pills */
.nav-pills-custom { display: flex; gap: 8px; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 20px; }
.nav-pills-custom .nav-link {
    color: #475569;
    font-weight: 600;
    font-size: 14px;
    padding: 8px 18px;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.nav-pills-custom .nav-link:hover {
    background: #f8fafc;
    color: #0f172a;
    border-color: #94a3b8;
}
.nav-pills-custom .nav-link.active {
    background: #1e3a8a;
    border-color: #1e3a8a;
    color: #ffffff;
    box-shadow: 0 2px 4px rgba(30, 58, 138, 0.2);
}
.nav-pills-custom .nav-link.active .badge-tab {
    background: #ffffff !important;
    color: #1e3a8a !important;
}

/* Progress bars */
.dept-progress-wrap { margin-bottom: 12px; }
.dept-progress-header { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 4px; color: #334155; font-weight: 500;}
.progress-thin { height: 6px; border-radius: 3px; background-color: #f1f5f9; overflow: hidden; }
.progress-thin .progress-bar { border-radius: 3px; }

/* Status Badges */
.status-badge { padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; border: 1px solid transparent; display: inline-flex; align-items: center; gap: 4px; }
.status-badge.healthy { background: #dcfce7; color: #16a34a; border-color: #bbf7d0; }
.status-badge.moderate { background: #fef9c3; color: #ca8a04; border-color: #fef08a; }
.status-badge.low { background: #ffedd5; color: #ea580c; border-color: #fed7aa; }
.status-badge.critical { background: #fee2e2; color: #dc2626; border-color: #fecaca; }
.status-badge.review { background: #fef3c7; color: #d97706; border-color: #fde68a; }
.status-badge.outside { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }

/* Sales Table Badges */
.badge-role-manager { background: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe; font-size: 11px; font-weight: 600; padding: 2px 6px; border-radius: 4px; }
.badge-role-head { background: #fae8ff; color: #86198f; border: 1px solid #f0abfc; font-size: 11px; font-weight: 600; padding: 2px 6px; border-radius: 4px; }
.badge-role-exec { background: #ccfbf1; color: #0f766e; border: 1px solid #99f6e4; font-size: 11px; font-weight: 600; padding: 2px 6px; border-radius: 4px; }
.badge-role-other { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; font-size: 11px; font-weight: 600; padding: 2px 6px; border-radius: 4px; }

.badge-lead-visited { background: #dcfce7; color: #15803d; font-weight: 700; padding: 2px 8px; border-radius: 12px; font-size: 12px; display: inline-block; }
.badge-lead-hot { background: #fee2e2; color: #b91c1c; font-weight: 700; padding: 2px 8px; border-radius: 12px; font-size: 12px; display: inline-block; }
.badge-lead-cold { background: #e0f2fe; color: #0369a1; font-weight: 700; padding: 2px 8px; border-radius: 12px; font-size: 12px; display: inline-block; }
.badge-lead-uncontacted { background: #fff7ed; color: #c2410c; font-weight: 700; padding: 2px 8px; border-radius: 12px; font-size: 12px; border: 1px dashed #fdba74; display: inline-block; }
.badge-lead-booked { background: #ecfdf5; color: #047857; font-weight: 700; padding: 3px 10px; border-radius: 6px; font-size: 13px; border: 1px solid #a7f3d0; display: inline-flex; align-items: center; gap: 4px; }

/* Table Tabs */
.custom-tabs { display: flex; gap: 16px; border-bottom: 1px solid #e2e8f0; padding: 0 18px; }
.custom-tab { padding: 9px 0; font-size: 13px; font-weight: 600; color: #64748b; cursor: pointer; border-bottom: 2px solid transparent; display: flex; align-items: center; gap: 6px; }
.custom-tab.active { color: #16a34a; border-bottom-color: #16a34a; }
.custom-tab .count { font-size: 11px; padding: 1px 6px; border-radius: 10px; background: #f1f5f9; color: #475569; }
.custom-tab.active .count { background: #dcfce7; color: #16a34a; }

/* Standard Usage Window Box */
.standard-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 20px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
.standard-box-item { display: flex; align-items: center; gap: 12px; }
.standard-box-divider { width: 1px; height: 30px; background: #e2e8f0; margin: 0 20px; }

/* Avatar initials */
.avatar-initials { width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; color: white; flex-shrink: 0; }

.table-person td { vertical-align: middle; font-size: 13px; color: #334155; border-bottom-width: 1px;}
.table-person th { font-size: 12px; text-transform: none; color: #64748b; border-bottom-width: 1px; font-weight: 600; }
.table-sales th { background-color: #f8fafc; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.3px; border-bottom: 2px solid #cbd5e1; }
.table-sales td { vertical-align: middle; font-size: 13px; border-bottom: 1px solid #f1f5f9; }
.table-sales tr:hover td { background-color: #f8fafc; }
.table-sales tfoot td { background-color: #f8fafc; font-weight: bold; border-top: 2px solid #cbd5e1; font-size: 13px; }
</style>

<div class="container-fluid py-3 px-4">

    <!-- ── 1. Header Row (Title & Mode Switcher Tabs) ────────────────────────── -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <div class="text-muted fs-12 mb-1">HR & Payroll / User Activity & Performance</div>
            <h1 class="dash-title">ERP Usage & Activity Log</h1>
            <p class="dash-subtitle">Monitor working hours, sales team conversions, lead follow-ups, and ERP adoption</p>
        </div>

        <!-- Main Mode Switcher Tabs -->
        <ul class="nav nav-pills nav-pills-custom mb-0" id="mainReportTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $activeTab === 'sales' ? 'active' : '' ?>" id="tab-sales-btn" data-bs-toggle="pill" data-bs-target="#pane-sales" type="button" role="tab" onclick="switchMainTab('sales')">
                    <i class="fa-solid fa-chart-line text-warning"></i>
                    <span>Sales Team Performance</span>
                    <span class="badge rounded-pill bg-light text-dark badge-tab ms-1"><?= count($salesTeamPerformance) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $activeTab === 'adoption' ? 'active' : '' ?>" id="tab-adoption-btn" data-bs-toggle="pill" data-bs-target="#pane-adoption" type="button" role="tab" onclick="switchMainTab('adoption')">
                    <i class="fa-solid fa-gauge-high text-primary"></i>
                    <span>ERP Usage & Adoption</span>
                    <span class="badge rounded-pill bg-light text-dark badge-tab ms-1"><?= count($personWiseData) ?></span>
                </button>
            </li>
        </ul>
    </div>

    <!-- ── 2. Dedicated Full-Width Filter Toolbar ────────────────────────────── -->
    <div class="filter-toolbar">
        <form method="GET" action="<?= $base_url ?>/user-activity-log" id="filterForm" class="d-flex align-items-end justify-content-between flex-wrap gap-3">
            <input type="hidden" name="tab" id="activeTabFilterInput" value="<?= htmlspecialchars($activeTab) ?>">

            <!-- Filter Controls Group -->
            <div class="d-flex align-items-end gap-3 flex-wrap">
                <!-- Date Range -->
                <div class="filter-item">
                    <label class="filter-item-label"><i class="fa-regular fa-calendar text-primary me-1"></i> Date Range</label>
                    <div class="input-group input-group-sm" style="width: 290px;">
                        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= $filterDateFrom ?>" onchange="this.form.submit()">
                        <span class="input-group-text bg-light text-muted px-2">to</span>
                        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= $filterDateTo ?>" onchange="this.form.submit()">
                    </div>
                </div>
                
                <!-- Department -->
                <div class="filter-item" style="width: 190px;">
                    <label class="filter-item-label"><i class="fa-solid fa-building text-info me-1"></i> Department</label>
                    <select name="department_id" id="actLogDeptSelect" class="form-select form-select-sm select2" data-placeholder="All Departments">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['id'] ?>" <?= ($filterDeptId == $dept['id']) ? 'selected' : '' ?>><?= htmlspecialchars($dept['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Employee Search -->
                <div class="filter-item" style="width: 250px;">
                    <label class="filter-item-label"><i class="fa-solid fa-user text-secondary me-1"></i> Employee</label>
                    <select name="user_id" id="actLogUserSelect" class="form-select form-select-sm select2" data-placeholder="-- All Employees (<?= count($allUsers) ?>) --">
                        <option value="">-- All Employees (<?= count($allUsers) ?>) --</option>
                        <?php foreach ($allUsers as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($filterUserId == $u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['full_name'] . (!empty($u['employee_id']) ? ' (' . $u['employee_id'] . ')' : '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Reset Button -->
                <?php if ($filterDeptId || $filterUserId || $filterDateFrom !== date('Y-m-01') || $filterDateTo !== date('Y-m-d')): ?>
                <div class="filter-item">
                    <label class="filter-item-label">&nbsp;</label>
                    <a href="<?= $base_url ?>/user-activity-log?tab=<?= $activeTab ?>" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1" title="Reset all filters">
                        <i class="fa-solid fa-rotate-left"></i> Reset
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Export Actions Group -->
            <div class="filter-item">
                <label class="filter-item-label text-end d-none d-md-block">Actions</label>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-success d-flex align-items-center gap-1 dropdown-toggle px-3" data-bs-toggle="dropdown" aria-expanded="false" style="height: 34px;">
                        <i class="fa-solid fa-file-export"></i> Export Report
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li class="dropdown-header text-uppercase fs-11 fw-bold text-muted">Sales Performance Report</li>
                        <li>
                            <a class="dropdown-item fs-13" href="?<?= http_build_query(array_merge($_GET, ['export' => 'excel', 'export_target' => 'sales'])) ?>">
                                <i class="fa-solid fa-file-excel text-success me-2"></i> Export Sales to Excel (.xlsx)
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item fs-13" href="?<?= http_build_query(array_merge($_GET, ['export' => 'pdf', 'export_target' => 'sales'])) ?>">
                                <i class="fa-solid fa-file-pdf text-danger me-2"></i> Export Sales to PDF (.pdf)
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li class="dropdown-header text-uppercase fs-11 fw-bold text-muted">ERP Adoption Report</li>
                        <li>
                            <a class="dropdown-item fs-13" href="?<?= http_build_query(array_merge($_GET, ['export' => 'excel', 'export_target' => 'adoption'])) ?>">
                                <i class="fa-solid fa-file-excel text-success me-2"></i> Export Adoption to Excel (.xlsx)
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item fs-13" href="?<?= http_build_query(array_merge($_GET, ['export' => 'pdf', 'export_target' => 'adoption'])) ?>">
                                <i class="fa-solid fa-file-pdf text-danger me-2"></i> Export Adoption to PDF (.pdf)
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </form>
    </div>

    <!-- ── Tab Content ─────────────────────────────────────────────────────── -->
    <div class="tab-content" id="mainReportTabsContent">

        <!-- =================================================================== -->
        <!-- TAB 1: SALES TEAM PERFORMANCE                                       -->
        <!-- =================================================================== -->
        <div class="tab-pane fade <?= $activeTab === 'sales' ? 'show active' : '' ?>" id="pane-sales" role="tabpanel">

            <!-- 5 KPI Cards in EXACTLY ONE SINGLE ROW -->
            <div class="kpi-cards-grid">
                <!-- 1. Total Assigned Leads -->
                <div class="kpi-card">
                    <div class="kpi-label">
                        <div class="icon-circle blue"><i class="fa-solid fa-users"></i></div>
                        <span>Assigned Leads</span>
                    </div>
                    <div class="kpi-value text-primary"><?= number_format($salesTotals['total_assigned_leads']) ?></div>
                    <div class="kpi-subtext">Total assigned in period</div>
                </div>

                <!-- 2. Follow-ups Done -->
                <div class="kpi-card">
                    <div class="kpi-label">
                        <div class="icon-circle purple"><i class="fa-solid fa-phone-volume"></i></div>
                        <span>Follow-ups Done</span>
                    </div>
                    <div class="kpi-value" style="color:#9333ea;"><?= number_format($salesTotals['total_followups']) ?></div>
                    <div class="kpi-subtext">Calls & visits logged</div>
                </div>

                <!-- 3. Total Booked Flats -->
                <div class="kpi-card">
                    <div class="kpi-label">
                        <div class="icon-circle green"><i class="fa-solid fa-building-circle-check"></i></div>
                        <span>Total Booked</span>
                    </div>
                    <div class="kpi-value text-success"><?= number_format($salesTotals['total_booked']) ?></div>
                    <div class="kpi-subtext">Bookings in date range</div>
                </div>

                <!-- 4. Total Land Share Value -->
                <div class="kpi-card" style="background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%); border-color: #bbf7d0;">
                    <div class="kpi-label">
                        <div class="icon-circle teal"><i class="fa-solid fa-money-bill-wave"></i></div>
                        <span>Land Share Value</span>
                    </div>
                    <div class="kpi-value text-success" style="font-size: 20px;" title="৳ <?= number_format($salesTotals['total_land_share_value'], 2) ?>">
                        ৳ <?= number_format($salesTotals['total_land_share_value'], 0) ?>
                    </div>
                    <div class="kpi-subtext">Total for booked flats</div>
                </div>

                <!-- 5. Uncontacted / No Follow-up -->
                <div class="kpi-card" style="background: linear-gradient(135deg, #fffbeb 0%, #ffffff 100%); border-color: #fde68a;">
                    <div class="kpi-label">
                        <div class="icon-circle yellow"><i class="fa-solid fa-user-clock"></i></div>
                        <span>No Follow-up</span>
                    </div>
                    <div class="kpi-value" style="color:#d97706;"><?= number_format($salesTotals['total_no_followup']) ?></div>
                    <div class="kpi-subtext">0 follow-ups in period</div>
                </div>
            </div>

            <!-- Sales Performance Table Card -->
            <div class="dash-card p-0" id="salesTableSection">
                <!-- Clean Header with Search Bar -->
                <div class="p-3 px-4 border-bottom bg-white d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="icon-circle green" style="width: 28px; height: 28px; font-size: 12px;">
                                <i class="fa-solid fa-users-line"></i>
                            </div>
                            <h5 class="fw-bold text-dark m-0 fs-15">Sales Team Performance & Conversions</h5>
                        </div>
                        <div class="text-muted fs-12 mt-1 ps-4">Breakdown of assigned leads, follow-up activities, statuses, bookings & land share values</div>
                    </div>
                    
                    <!-- Search Bar -->
                    <div class="input-group input-group-sm" style="width: 280px;">
                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input type="text" id="salesTableSearch" class="form-control form-control-sm border-start-0 ps-1" placeholder="Search member, role, id..." autocomplete="off">
                        <button class="btn btn-sm btn-light border border-start-0" id="salesTableSearchClear" type="button" style="display:none;">
                            <i class="fa-solid fa-xmark text-muted"></i>
                        </button>
                    </div>
                </div>

                <!-- Quick Filter Tabs for Sales Table -->
                <div class="custom-tabs px-4 mb-0">
                    <div class="custom-tab active sales-filter-tab" data-filter="All">All Members <span class="count"><?= count($salesTeamPerformance) ?></span></div>
                    <div class="custom-tab sales-filter-tab" data-filter="Booked">With Bookings <span class="count"><?= count(array_filter($salesTeamPerformance, function($s){ return $s['total_booked'] > 0; })) ?></span></div>
                    <div class="custom-tab sales-filter-tab" data-filter="Followup">With Follow-ups <span class="count"><?= count(array_filter($salesTeamPerformance, function($s){ return $s['total_followups'] > 0; })) ?></span></div>
                    <div class="custom-tab sales-filter-tab" data-filter="Uncontacted">Has Uncontacted <span class="count"><?= count(array_filter($salesTeamPerformance, function($s){ return $s['no_followup_leads'] > 0; })) ?></span></div>
                </div>

                <!-- Table -->
                <div class="table-responsive px-4 pb-4 pt-2">
                    <table class="table table-hover table-sales w-100" id="dtSalesPerformance">
                        <thead>
                            <tr>
                                <th style="width: 35px;">#</th>
                                <th>Sales Member & Role</th>
                                <th class="text-center">Assigned Leads</th>
                                <th class="text-center">Follow-ups</th>
                                <th class="text-center">Visited</th>
                                <th class="text-center">Hot</th>
                                <th class="text-center">Cold</th>
                                <th class="text-center">No Follow-up</th>
                                <th class="text-center">Total Booked</th>
                                <th class="text-end">Land Share Value</th>
                                <th>Last Update</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $sl = 1;
                            foreach ($salesTeamPerformance as $s): 
                                $roleClass = 'badge-role-other';
                                if (stripos($s['role_name'], 'head') !== false) $roleClass = 'badge-role-head';
                                elseif (stripos($s['role_name'], 'manager') !== false) $roleClass = 'badge-role-manager';
                                elseif (stripos($s['role_name'], 'executive') !== false) $roleClass = 'badge-role-exec';

                                $lastUpdateFormatted = $s['last_status_update'] ? date('d M Y, h:i A', strtotime($s['last_status_update'])) : '—';
                            ?>
                            <tr data-booked="<?= $s['total_booked'] ?>" data-followups="<?= $s['total_followups'] ?>" data-uncontacted="<?= $s['no_followup_leads'] ?>">
                                <td class="text-muted fw-semibold"><?= $sl++ ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-initials" style="background-color: <?= stringToColorCode($s['full_name']) ?>"><?= $s['initials'] ?></div>
                                        <div>
                                            <div class="fw-bold text-dark fs-13"><?= htmlspecialchars($s['full_name']) ?></div>
                                            <div class="d-flex align-items-center gap-1 mt-1">
                                                <span class="<?= $roleClass ?>"><?= htmlspecialchars($s['role_name']) ?></span>
                                                <?php if(!empty($s['employee_id'])): ?>
                                                    <span class="text-muted fs-11 ms-1">#<?= htmlspecialchars($s['employee_id']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="fw-bold fs-13 text-dark"><?= number_format($s['assigned_leads']) ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border fs-12 px-2 py-1">
                                        <i class="fa-solid fa-phone text-muted me-1 fs-11"></i><?= number_format($s['total_followups']) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($s['visited'] > 0): ?>
                                        <span class="badge-lead-visited"><?= $s['visited'] ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($s['hot'] > 0): ?>
                                        <span class="badge-lead-hot"><i class="fa-solid fa-fire me-1 fs-10"></i><?= $s['hot'] ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($s['cold'] > 0): ?>
                                        <span class="badge-lead-cold"><?= $s['cold'] ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($s['no_followup_leads'] > 0): ?>
                                        <span class="badge-lead-uncontacted" title="<?= $s['no_followup_leads'] ?> assigned leads not yet followed up in this period">
                                            <i class="fa-solid fa-triangle-exclamation me-1 fs-10"></i><?= $s['no_followup_leads'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success fs-11 px-2 py-1"><i class="fa-solid fa-check"></i> 0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($s['total_booked'] > 0): ?>
                                        <span class="badge-lead-booked">
                                            <i class="fa-solid fa-house-chimney"></i> <?= $s['total_booked'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($s['total_land_share_value'] > 0): ?>
                                        <span class="fw-bold text-success fs-13">৳ <?= number_format($s['total_land_share_value'], 0) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">৳ 0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($s['last_status_update']): ?>
                                        <span class="d-block text-dark fs-12"><?= date('d M Y', strtotime($s['last_status_update'])) ?></span>
                                        <span class="text-muted fs-11"><?= date('h:i A', strtotime($s['last_status_update'])) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted fs-12">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2 fs-12 fw-semibold" onclick="openActivityModal(event, <?= $s['user_id'] ?>)">
                                        <i class="fa-regular fa-eye me-1"></i> Details
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" class="text-center text-dark">TOTAL (<?= count($salesTeamPerformance) ?> members)</td>
                                <td class="text-center text-primary"><?= number_format($salesTotals['total_assigned_leads']) ?></td>
                                <td class="text-center" style="color:#9333ea;"><?= number_format($salesTotals['total_followups']) ?></td>
                                <td class="text-center text-success"><?= number_format($salesTotals['total_visited']) ?></td>
                                <td class="text-center text-danger"><?= number_format($salesTotals['total_hot']) ?></td>
                                <td class="text-center text-info"><?= number_format($salesTotals['total_cold']) ?></td>
                                <td class="text-center text-warning"><?= number_format($salesTotals['total_no_followup']) ?></td>
                                <td class="text-center text-success fs-14"><?= number_format($salesTotals['total_booked']) ?></td>
                                <td class="text-end text-success fs-14">৳ <?= number_format($salesTotals['total_land_share_value'], 0) ?></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- TAB 2: ERP USAGE & ADOPTION (ORIGINAL DASHBOARD)                    -->
        <!-- =================================================================== -->
        <div class="tab-pane fade <?= $activeTab === 'adoption' ? 'show active' : '' ?>" id="pane-adoption" role="tabpanel">

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
                    <i class="fa-solid fa-pen fs-12"></i> Edit Standard
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
                        <div class="stat-subtext">Active employees</div>
                        <div id="spark1" class="position-absolute bottom-0 end-0 me-2 mb-2" style="width: 60px;"></div>
                    </div>
                </div>
                <!-- 2 -->
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="dash-card p-3">
                        <div class="stat-label">
                            <div class="icon-circle green"><i class="fa-regular fa-calendar-check"></i></div>
                            Active in Period
                        </div>
                        <div class="stat-value"><?= $activeToday ?></div>
                        <div class="stat-subtext text-success fw-semibold"><?= $totalUsers > 0 ? round(($activeToday/$totalUsers)*100) : 0 ?>% of total users</div>
                        <div id="spark2" class="position-absolute bottom-0 end-0 me-2 mb-2" style="width: 60px;"></div>
                    </div>
                </div>
                <!-- 3 -->
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="dash-card p-3">
                        <div class="stat-label">
                            <div class="icon-circle yellow"><i class="fa-solid fa-chart-line"></i></div>
                            Adoption Score
                        </div>
                        <div class="stat-value"><?= $adoptionScore ?><span class="fs-16 text-muted">/100</span></div>
                        <div class="stat-subtext text-warning fw-semibold">Period Adoption</div>
                        <div id="spark3" class="position-absolute bottom-0 end-0 me-2 mb-2" style="width: 60px;"></div>
                    </div>
                </div>
                <!-- 4 -->
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="dash-card p-3">
                        <div class="stat-label">
                            <div class="icon-circle green"><i class="fa-solid fa-circle-check"></i></div>
                            Meeting Standard
                        </div>
                        <div class="stat-value"><?= $meetingStandard ?></div>
                        <div class="stat-subtext text-success fw-semibold">≥4 hrs/day avg</div>
                    </div>
                </div>
                <!-- 5 -->
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="dash-card p-3">
                        <div class="stat-label">
                            <div class="icon-circle red"><i class="fa-solid fa-circle-xmark"></i></div>
                            Below Standard
                        </div>
                        <div class="stat-value"><?= $belowStandard ?></div>
                        <div class="stat-subtext text-danger fw-semibold">&lt;4 hrs/day avg</div>
                        <div id="spark4" class="position-absolute bottom-0 end-0 me-2 mb-2" style="width: 60px;"></div>
                    </div>
                </div>
                <!-- 6 -->
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="dash-card p-3">
                        <div class="stat-label">
                            <div class="icon-circle green"><i class="fa-solid fa-clock"></i></div>
                            Avg. Productive
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
                                            <td class="text-danger fw-semibold"><?= $p['issue'] ?></td>
                                            <td>
                                                <?php 
                                                    $bClass = 'low';
                                                    if ($p['attn_status'] == 'Critical') $bClass = 'critical';
                                                    elseif ($p['attn_status'] == 'Review') $bClass = 'review';
                                                ?>
                                                <span class="status-badge <?= $bClass ?>"><?= $p['attn_status'] ?></span>
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
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="section-title mb-0">Person-wise Usage Performance <i class="fa-solid fa-circle-info info-icon" title="Detailed breakdown per user"></i></div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="input-group input-group-sm" style="min-width: 260px;">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fa-solid fa-magnifying-glass text-muted fs-12"></i>
                                </span>
                                <input type="text" id="personTableSearch" class="form-control form-control-sm border-start-0 ps-0" placeholder="Search by name or department..." autocomplete="off">
                                <button class="btn btn-sm btn-light border" id="personTableSearchClear" title="Clear search" style="display:none;">
                                    <i class="fa-solid fa-xmark text-muted"></i>
                                </button>
                            </div>
                            <span class="text-muted fs-12 text-nowrap">Usage status is calculated from active time, login consistency and idle ratio.</span>
                        </div>
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
                                    <button type="button" class="btn btn-link text-success fw-semibold text-decoration-none p-0 fs-12" onclick="openActivityModal(event, <?= $p['user_id'] ?>)">View details</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Activity Details Modal -->
<div class="modal fade" id="activityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Activity Summary - <span id="modalUserName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="card p-3 text-center border-success bg-light">
                            <h6 class="text-muted mb-1">New Leads</h6>
                            <h4 class="mb-0 text-success" id="modalLeadsCount">0</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card p-3 text-center border-primary bg-light">
                            <h6 class="text-muted mb-1">Follow-ups</h6>
                            <h4 class="mb-0 text-primary" id="modalFollowUpsCount">0</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card p-3 text-center border-warning bg-light">
                            <h6 class="text-muted mb-1">Bookings</h6>
                            <h4 class="mb-0 text-warning" id="modalBookingsCount">0</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card p-3 text-center border-info bg-light">
                            <h6 class="text-muted mb-1">Requisitions</h6>
                            <h4 class="mb-0 text-info" id="modalFundReqsCount">0</h4>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold mb-2">Daily Active Logs</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered" id="modalDailyTable">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Sessions</th>
                                <th>First Login</th>
                                <th>Last Active</th>
                                <th>Active Time</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
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
// Switch main report tab function
function switchMainTab(tabName) {
    var input = document.getElementById('activeTabFilterInput');
    if (input) {
        input.value = tabName;
    }
    // Update URL parameter without full reload
    if (window.history && window.history.replaceState) {
        var url = new URL(window.location.href);
        url.searchParams.set('tab', tabName);
        window.history.replaceState({}, '', url.toString());
    }
}

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

    // ─────────────────────────────────────────────────────────────────────────
    // DataTable: Sales Team Performance Table
    // ─────────────────────────────────────────────────────────────────────────
    if (typeof $ !== 'undefined' && $.fn.DataTable && document.querySelector('#dtSalesPerformance')) {
        var salesTable = $('#dtSalesPerformance').DataTable({
            "pageLength": 15,
            "lengthChange": false,
            "searching": true,
            "info": false,
            "autoWidth": false,
            "order": [], // Already sorted from server
            "dom": '<"top">rt<"bottom"p><"clear">',
            "language": {
                "paginate": {
                    "previous": "<i class='fa-solid fa-angle-left'></i>",
                    "next": "<i class='fa-solid fa-angle-right'></i>"
                }
            }
        });

        // Search for Sales Table
        var $salesSearchInput = $('#salesTableSearch');
        var $salesClearBtn    = $('#salesTableSearchClear');
        var salesSearchTimer  = null;

        $salesSearchInput.on('keyup input', function() {
            clearTimeout(salesSearchTimer);
            var val = $(this).val();
            $salesClearBtn.toggle(val.length > 0);
            salesSearchTimer = setTimeout(function() {
                salesTable.search(val).draw();
            }, 200);
        });

        $salesClearBtn.on('click', function() {
            $salesSearchInput.val('').trigger('input').focus();
        });

        // Quick filter pills for Sales Table
        $('.sales-filter-tab').on('click', function() {
            $('.sales-filter-tab').removeClass('active');
            $(this).addClass('active');
            var filter = $(this).data('filter');

            $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(function(fn) {
                return fn._isSalesFilter !== true;
            });

            if (filter !== 'All') {
                var salesFilterFn = function(settings, data, dataIndex) {
                    if (settings.nTable.id !== 'dtSalesPerformance') return true;
                    var rowNode = salesTable.row(dataIndex).node();
                    if (!rowNode) return true;
                    if (filter === 'Booked') {
                        return parseInt($(rowNode).attr('data-booked') || 0) > 0;
                    }
                    if (filter === 'Followup') {
                        return parseInt($(rowNode).attr('data-followups') || 0) > 0;
                    }
                    if (filter === 'Uncontacted') {
                        return parseInt($(rowNode).attr('data-uncontacted') || 0) > 0;
                    }
                    return true;
                };
                salesFilterFn._isSalesFilter = true;
                $.fn.dataTable.ext.search.push(salesFilterFn);
            }
            salesTable.draw();
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DataTable: Person-wise Usage Table
    // ─────────────────────────────────────────────────────────────────────────
    if (typeof $ !== 'undefined' && $.fn.DataTable && document.querySelector('#dtPersonUsage')) {
        var table = $('#dtPersonUsage').DataTable({
            "pageLength": 15,
            "lengthChange": false,
            "searching": true,
            "info": false,
            "autoWidth": false,
            "order": [],
            "dom": '<"top">rt<"bottom"p><"clear">',
            "language": {
                "paginate": {
                    "previous": "<i class='fa-solid fa-angle-left'></i>",
                    "next": "<i class='fa-solid fa-angle-right'></i>"
                }
            }
        });

        // Custom Search Box
        var $searchInput  = $('#personTableSearch');
        var $clearBtn     = $('#personTableSearchClear');
        var searchTimeout = null;

        $searchInput.on('keyup input', function() {
            clearTimeout(searchTimeout);
            var val = $(this).val();
            $clearBtn.toggle(val.length > 0);
            searchTimeout = setTimeout(function() {
                table.search(val).draw();
            }, 200);
        });

        $clearBtn.on('click', function() {
            $searchInput.val('').trigger('input').focus();
        });

        // Tab Filtering Logic for Usage Table
        var activeTabFilter = 'All';

        $('.custom-tab:not(.sales-filter-tab)').on('click', function() {
            $('.custom-tab:not(.sales-filter-tab)').removeClass('active');
            $(this).addClass('active');
            activeTabFilter = $(this).data('filter');

            // Remove previous status filter
            $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(function(fn) {
                return fn._isStatusFilter !== true;
            });

            if (activeTabFilter !== 'All') {
                var statusFn = function(settings, data, dataIndex) {
                    if (settings.nTable.id !== 'dtPersonUsage') return true;
                    var rowNode = table.row(dataIndex).node();
                    if (!rowNode) return true;
                    var status  = $(rowNode).attr('data-status');
                    if (activeTabFilter === 'Below Standard') {
                        return (status === 'Low Usage' || status === 'Moderate');
                    }
                    return status === activeTabFilter;
                };
                statusFn._isStatusFilter = true;
                $.fn.dataTable.ext.search.push(statusFn);
            }
            table.draw();
        });
    }

    // Select2: auto-submit filter form on change
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('#actLogDeptSelect, #actLogUserSelect').on('change', function() {
            document.getElementById('filterForm').submit();
        });
    }
});

// Handle View Details - Pure Raw JavaScript (no jQuery dependency)
function openActivityModal(e, userId) {
    e.preventDefault();
    e.stopPropagation();

    // Get date filters using raw JS
    var dateFrom = document.querySelector('input[name="date_from"]').value;
    var dateTo   = document.querySelector('input[name="date_to"]').value;

    // Reset modal fields
    document.getElementById('modalUserName').textContent      = 'Loading...';
    document.getElementById('modalLeadsCount').textContent    = '-';
    document.getElementById('modalFollowUpsCount').textContent = '-';
    document.getElementById('modalBookingsCount').textContent  = '-';
    document.getElementById('modalFundReqsCount').textContent  = '-';
    document.getElementById('modalDailyTable').querySelector('tbody').innerHTML = '';

    // Show Bootstrap modal
    var modalEl = document.getElementById('activityModal');
    var bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
    bsModal.show();

    // Fetch data via raw XMLHttpRequest
    var baseUrl = '<?= $base_url ?>';
    var url = baseUrl + '/user-activity-log/employee-summary?user_id=' + encodeURIComponent(userId)
            + '&date_from=' + encodeURIComponent(dateFrom)
            + '&date_to='   + encodeURIComponent(dateTo);

    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 300) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.error) {
                    document.getElementById('modalUserName').textContent = response.error;
                    return;
                }
                document.getElementById('modalUserName').textContent = response.employee.full_name;

                if (response.activities) {
                    document.getElementById('modalLeadsCount').textContent     = response.activities.new_leads;
                    document.getElementById('modalFollowUpsCount').textContent = response.activities.follow_ups;
                    document.getElementById('modalBookingsCount').textContent  = response.activities.bookings;
                    document.getElementById('modalFundReqsCount').textContent  = response.activities.fund_requisitions;
                }

                var tbody = '';
                if (response.daily && response.daily.length > 0) {
                    response.daily.forEach(function(day) {
                        var hrs  = Math.floor(day.active_minutes / 60);
                        var mins = day.active_minutes % 60;
                        tbody += '<tr>';
                        tbody += '<td>' + day.log_date   + '</td>';
                        tbody += '<td>' + day.sessions   + '</td>';
                        tbody += '<td>' + day.first_login + '</td>';
                        tbody += '<td>' + day.last_active + '</td>';
                        tbody += '<td>' + (hrs > 0 ? hrs + 'h ' : '') + mins + 'm</td>';
                        tbody += '</tr>';
                    });
                } else {
                    tbody = '<tr><td colspan="5" class="text-center text-muted py-3">No activity found in this period.</td></tr>';
                }
                document.getElementById('modalDailyTable').querySelector('tbody').innerHTML = tbody;
            } catch(parseErr) {
                document.getElementById('modalUserName').textContent = 'Parse error. Check console.';
                console.error(parseErr);
            }
        } else {
            document.getElementById('modalUserName').textContent = 'Server error: ' + xhr.status;
        }
    };
    xhr.onerror = function() {
        document.getElementById('modalUserName').textContent = 'Network error. Could not load data.';
    };
    xhr.send();
}
</script>

<?php
$page_title = "CED Collection Statement";
$page = 'CED';
$show_breadcrumb = true;

$base_url = $base_url ?? ($GLOBALS['base_url'] ?? '');

$filters           = $filters ?? [];
$summary           = $summary ?? [];
$collector_summary = $collector_summary ?? ['rows' => [], 'grand' => []];
$type_summary      = $type_summary ?? ['rows' => [], 'grand' => []];
$transactions      = $transactions ?? [];
$collectors_list   = $collectors_list ?? [];
$projects_list     = $projects_list ?? [];
$pagination        = $pagination ?? ['current_page' => 1, 'total_pages' => 1, 'total_records' => 0, 'limit' => 50];

$curPeriodType = $filters['period_type'] ?? 'monthly';
$curYear       = $filters['year'] ?? (int)date('Y');
$curMonth      = $filters['month'] ?? date('m');
$curStartDate  = $filters['start_date'] ?? '';
$curEndDate    = $filters['end_date'] ?? '';
$curCollector  = $filters['collector_id'] ?? '';
$curTeamScope  = $filters['team_scope'] ?? 'ced_only';
$curProject    = $filters['project_id'] ?? '';
$curMethod     = $filters['payment_method'] ?? '';
$curType       = $filters['payment_type'] ?? '';
$curVer        = $filters['verification_status'] ?? '';
$curSearch     = $filters['search'] ?? '';

$curViewMode   = $view_mode ?? 'collectors';

// Build query params for print and export links
$queryParams = $_GET;
$printUrl     = $base_url . '/ced-collection-statement-print?' . http_build_query($queryParams);
$exportUrl    = $base_url . '/ced-collection-statement-export?' . http_build_query($queryParams);

$printColUrl  = $base_url . '/ced-collection-statement-print?' . http_build_query(array_merge($queryParams, ['print_mode' => 'collectors']));
$printTrxUrl  = $base_url . '/ced-collection-statement-print?' . http_build_query(array_merge($queryParams, ['print_mode' => 'transactions']));
$printTypeUrl = $base_url . '/ced-collection-statement-print?' . http_build_query(array_merge($queryParams, ['print_mode' => 'type_summary']));

$exportColUrl = $base_url . '/ced-collection-statement-export?' . http_build_query(array_merge($queryParams, ['export_mode' => 'collectors']));
$exportTrxUrl = $base_url . '/ced-collection-statement-export?' . http_build_query(array_merge($queryParams, ['export_mode' => 'transactions']));
?>

<link rel="stylesheet" href="<?= $base_url ?>/public/assets/css/collection-statement.css">

<div class="row">
    <!-- Header Section -->
    <div class="col-12 mb-3">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary text-white p-2 rounded-2">
                                <i class="fa-solid fa-hand-holding-dollar fs-16"></i>
                            </span>
                            <div>
                                <h4 class="mb-0 fw-bold text-dark">Client Experience Collection Statement</h4>
                                <small class="text-muted">
                                    Track Executive-wise Development Charges, Monthly EMIs, Settlements &amp; Inflow Register &bull; <strong class="text-primary"><?= htmlspecialchars($date_info['label'] ?? '') ?></strong>
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="cs-header-btns ms-md-auto">
                        <!-- Print Dropdown -->
                        <div class="dropdown cs-dropdown-wrapper">
                            <button class="btn btn-sm btn-outline-primary dropdown-toggle shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-print"></i> <span>Print Reports</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border py-2" style="min-width: 250px;">
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="<?= $printColUrl ?>" target="_blank">
                                        <i class="fa-solid fa-users-gear text-primary"></i>
                                        <div>
                                            <div class="fw-bold fs-12 text-dark">Executive Collection Matrix</div>
                                            <small class="text-muted extra-small">Print Executive-wise summary sheet</small>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="<?= $printTrxUrl ?>" target="_blank">
                                        <i class="fa-solid fa-table-list text-success"></i>
                                        <div>
                                            <div class="fw-bold fs-12 text-dark">Detailed Inflow Register</div>
                                            <small class="text-muted extra-small">Print filtered transactions</small>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="<?= $printTypeUrl ?>" target="_blank">
                                        <i class="fa-solid fa-chart-pie text-info"></i>
                                        <div>
                                            <div class="fw-bold fs-12 text-dark">Payment Head Breakdown</div>
                                            <small class="text-muted extra-small">Print payment purpose summary</small>
                                        </div>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <!-- Export Dropdown -->
                        <div class="dropdown cs-dropdown-wrapper">
                            <button class="btn btn-sm btn-outline-success dropdown-toggle shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-file-csv"></i> <span>Export CSV</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border py-2" style="min-width: 250px;">
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="<?= $exportColUrl ?>">
                                        <i class="fa-solid fa-users text-success"></i>
                                        <div>
                                            <div class="fw-bold fs-12 text-dark">Export Executive Summary</div>
                                            <small class="text-muted extra-small">Collector-wise Dev Charge &amp; EMI Matrix</small>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="<?= $exportTrxUrl ?>">
                                        <i class="fa-solid fa-list-check text-primary"></i>
                                        <div>
                                            <div class="fw-bold fs-12 text-dark">Export Detailed Transactions</div>
                                            <small class="text-muted extra-small">All filtered individual entries</small>
                                        </div>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <!-- Quick Navigation to CED Dashboard -->
                        <a href="<?= $base_url ?>/ced-dashboard" class="btn btn-sm btn-outline-secondary shadow-sm">
                            <i class="fa-solid fa-gauge-high"></i> <span>CED Dashboard</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="col-12 mb-3">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-3">
                <form method="GET" action="<?= $base_url ?>/ced-collection-statement" id="cedFilterForm">
                    <input type="hidden" name="view_mode" id="viewModeInput" value="<?= htmlspecialchars($curViewMode) ?>">

                    <div class="row g-2 align-items-end">
                        <!-- Period Type -->
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                            <label class="form-label extra-small fw-bold text-muted mb-1 text-uppercase" style="font-size: 11px;">Period Type</label>
                            <select name="period_type" id="periodTypeSelect" class="form-select form-select-sm" onchange="togglePeriodFields()">
                                <option value="monthly" <?= $curPeriodType === 'monthly' ? 'selected' : '' ?>>Monthly Breakdown</option>
                                <option value="today" <?= in_array($curPeriodType, ['today', 'daily']) ? 'selected' : '' ?>>Today</option>
                                <option value="this_week" <?= in_array($curPeriodType, ['this_week', 'weekly']) ? 'selected' : '' ?>>This Week</option>
                                <option value="yearly" <?= $curPeriodType === 'yearly' ? 'selected' : '' ?>>Full Year</option>
                                <option value="custom" <?= $curPeriodType === 'custom' ? 'selected' : '' ?>>Custom Date Range</option>
                                <option value="all_time" <?= $curPeriodType === 'all_time' ? 'selected' : '' ?>>All Time</option>
                            </select>
                        </div>

                        <!-- Year Filter -->
                        <div class="col-xl-1 col-lg-2 col-md-3 col-sm-6 period-monthly-field">
                            <label class="form-label extra-small fw-bold text-muted mb-1 text-uppercase" style="font-size: 11px;">Year</label>
                            <select name="year" class="form-select form-select-sm">
                                <?php
                                $cYear = (int)date('Y');
                                for ($y = $cYear + 1; $y >= 2020; $y--):
                                ?>
                                    <option value="<?= $y ?>" <?= (int)$curYear === $y ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <!-- Month Filter -->
                        <div class="col-xl-2 col-lg-2 col-md-3 col-sm-6 period-monthly-field">
                            <label class="form-label extra-small fw-bold text-muted mb-1 text-uppercase" style="font-size: 11px;">Month</label>
                            <select name="month" class="form-select form-select-sm">
                                <option value="all" <?= $curMonth === 'all' ? 'selected' : '' ?>>All Months</option>
                                <?php for ($m = 1; $m <= 12; $m++):
                                    $mVal = sprintf('%02d', $m);
                                    $mName = date('F', mktime(0, 0, 0, $m, 1));
                                ?>
                                    <option value="<?= $mVal ?>" <?= $curMonth === $mVal ? 'selected' : '' ?>><?= $mName ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <!-- Custom Start Date -->
                        <div class="col-xl-2 col-lg-2 col-md-3 col-sm-6 period-custom-field" style="<?= $curPeriodType !== 'custom' ? 'display:none;' : '' ?>">
                            <label class="form-label extra-small fw-bold text-muted mb-1 text-uppercase" style="font-size: 11px;">Start Date</label>
                            <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($curStartDate) ?>">
                        </div>

                        <!-- Custom End Date -->
                        <div class="col-xl-2 col-lg-2 col-md-3 col-sm-6 period-custom-field" style="<?= $curPeriodType !== 'custom' ? 'display:none;' : '' ?>">
                            <label class="form-label extra-small fw-bold text-muted mb-1 text-uppercase" style="font-size: 11px;">End Date</label>
                            <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($curEndDate) ?>">
                        </div>

                        <!-- Team Scope Toggle -->
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                            <label class="form-label extra-small fw-bold text-muted mb-1 text-uppercase" style="font-size: 11px;">Department Scope</label>
                            <select name="team_scope" class="form-select form-select-sm">
                                <option value="ced_only" <?= $curTeamScope === 'ced_only' ? 'selected' : '' ?>>CED Team Only (Default)</option>
                                <option value="all" <?= $curTeamScope === 'all' ? 'selected' : '' ?>>All Creators / Staff</option>
                            </select>
                        </div>

                        <!-- Specific Executive / Collector Filter -->
                        <!-- Specific Executive / Collector Filter (Searchable Select2 with Emp ID) -->
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                            <label class="form-label extra-small fw-bold text-muted mb-1 text-uppercase" style="font-size: 11px;">Collector / Executive</label>
                            <select name="collector_id" id="collectorSelect" class="form-select form-select-sm select2" data-placeholder="All Executives">
                                <option value="">All Executives</option>
                                <?php foreach ($collectors_list as $col): ?>
                                    <?php 
                                    $empCode = !empty($col['emp_code']) ? ' [ID: ' . $col['emp_code'] . ']' : '';
                                    $role = !empty($col['role_name']) ? ' (' . $col['role_name'] . ')' : '';
                                    $colLabel = $col['full_name'] . $empCode . $role;
                                    ?>
                                    <option value="<?= $col['user_id'] ?>" <?= (int)$curCollector === (int)$col['user_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($colLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Project Filter (Searchable Select2) -->
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                            <label class="form-label extra-small fw-bold text-muted mb-1 text-uppercase" style="font-size: 11px;">Project</label>
                            <select name="project_id" id="projectSelect" class="form-select form-select-sm select2" data-placeholder="All Projects">
                                <option value="">All Projects</option>
                                <?php foreach ($projects_list as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= (int)$curProject === (int)$p['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Payment Purpose / Type Filter -->
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                            <label class="form-label extra-small fw-bold text-muted mb-1 text-uppercase" style="font-size: 11px;">Payment Purpose</label>
                            <select name="payment_type" class="form-select form-select-sm">
                                <option value="">All Payment Types</option>
                                <option value="development_charge" <?= $curType === 'development_charge' ? 'selected' : '' ?>>Development Charge</option>
                                <option value="emi" <?= $curType === 'emi' ? 'selected' : '' ?>>Monthly EMI</option>
                                <option value="advance_emi" <?= $curType === 'advance_emi' ? 'selected' : '' ?>>Advance EMI</option>
                                <option value="emi_settlement" <?= $curType === 'emi_settlement' ? 'selected' : '' ?>>EMI Due Settlement</option>
                                <option value="booking_money" <?= $curType === 'booking_money' ? 'selected' : '' ?>>Booking Money (Rare)</option>
                                <option value="registration" <?= $curType === 'registration' ? 'selected' : '' ?>>Registration Money (Rare)</option>
                                <option value="land_share" <?= $curType === 'land_share' ? 'selected' : '' ?>>Land Share</option>
                            </select>
                        </div>

                        <!-- Payment Method Filter -->
                        <div class="col-xl-1 col-lg-2 col-md-3 col-sm-6">
                            <label class="form-label extra-small fw-bold text-muted mb-1 text-uppercase" style="font-size: 11px;">Method</label>
                            <select name="payment_method" class="form-select form-select-sm">
                                <option value="">All</option>
                                <option value="cash" <?= $curMethod === 'cash' ? 'selected' : '' ?>>Cash</option>
                                <option value="bank" <?= $curMethod === 'bank' ? 'selected' : '' ?>>Bank</option>
                                <option value="cheque" <?= $curMethod === 'cheque' ? 'selected' : '' ?>>Cheque</option>
                                <option value="online" <?= $curMethod === 'online' ? 'selected' : '' ?>>Online</option>
                            </select>
                        </div>

                        <!-- Verification Status Filter -->
                        <div class="col-xl-2 col-lg-2 col-md-3 col-sm-6">
                            <label class="form-label extra-small fw-bold text-muted mb-1 text-uppercase" style="font-size: 11px;">Verification</label>
                            <select name="verification_status" class="form-select form-select-sm">
                                <option value="">All Status</option>
                                <option value="verified" <?= $curVer === 'verified' ? 'selected' : '' ?>>Verified Only</option>
                                <option value="pending" <?= $curVer === 'pending' ? 'selected' : '' ?>>Pending Verification</option>
                                <option value="rejected" <?= $curVer === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                            </select>
                        </div>

                        <!-- Search Input -->
                        <div class="col-xl-3 col-lg-3 col-md-4 col-sm-6">
                            <label class="form-label extra-small fw-bold text-muted mb-1 text-uppercase" style="font-size: 11px;">Search Register</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-solid fa-magnifying-glass"></i></span>
                                <input type="text" name="search" class="form-control form-control-sm border-start-0" placeholder="Client name, phone, flat, ref..." value="<?= htmlspecialchars($curSearch) ?>">
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 d-flex align-items-center gap-1">
                            <button type="submit" class="btn btn-sm btn-primary w-100 fw-bold d-flex align-items-center justify-content-center gap-1 shadow-sm" style="height: 33px;">
                                <i class="fa-solid fa-filter"></i> <span>Apply Filter</span>
                            </button>
                            <a href="<?= $base_url ?>/ced-collection-statement" class="btn btn-sm btn-outline-secondary d-flex align-items-center justify-content-center" style="height: 33px;" title="Reset Filters">
                                <i class="fa-solid fa-rotate-right"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 6 KPI Summary Cards -->
    <div class="col-12 mb-3">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-6 g-2">
            <!-- 1. Total Collection -->
            <div class="col">
                <div class="ced-stat-card h-100">
                    <div class="d-flex align-items-center gap-2">
                        <div class="ced-stat-icon c-total">
                            <i class="fa-solid fa-vault"></i>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="text-muted extra-small text-uppercase fw-bold" style="font-size: 10px;">Total Inflows</div>
                                <?php if (!empty($summary['total_sold_value'])): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle py-0 px-1" style="font-size: 9px; font-weight: 700;" title="Realized Collection: <?= $summary['collection_rate'] ?>% of Sold Value"><?= $summary['collection_rate'] ?>%</span>
                                <?php endif; ?>
                            </div>
                            <div class="fw-bold text-dark fs-14 text-truncate" title="Collected: ৳<?= number_format($summary['total_amount'] ?? 0, 2) ?><?php if (!empty($summary['total_sold_value'])): ?> | Sold: <?= $summary['total_sold_value_formatted'] ?> | Due: <?= $summary['pending_due_formatted'] ?><?php endif; ?>">
                                ৳<?= number_format($summary['total_amount'] ?? 0, 2) ?>
                            </div>
                            <div class="d-flex align-items-center justify-content-between text-muted extra-small" style="font-size: 10px; line-height: 1.2;">
                                <span class="text-truncate"><?= number_format($summary['total_count'] ?? 0) ?> Trx</span>
                                <?php if (!empty($summary['total_sold_value'])): ?>
                                    <span class="text-truncate fw-semibold text-secondary ms-1" title="Total Sold: ৳<?= number_format($summary['total_sold_value'], 2) ?> (<?= number_format($summary['total_sold_units'] ?? 0) ?> units)">
                                        Sold: <strong class="text-dark"><?= $summary['total_sold_value_formatted'] ?></strong>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Development Charge -->
            <div class="col">
                <div class="ced-stat-card h-100">
                    <div class="d-flex align-items-center gap-2">
                        <div class="ced-stat-icon c-dev">
                            <i class="fa-solid fa-trowel-bricks"></i>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="text-muted extra-small text-uppercase fw-bold text-warning" style="font-size: 10px;">Dev Charge</div>
                            <div class="fw-bold text-dark fs-14 text-truncate" title="৳<?= number_format($summary['dev_charge_amount'] ?? 0, 2) ?>">
                                ৳<?= number_format($summary['dev_charge_amount'] ?? 0, 2) ?>
                            </div>
                            <small class="text-muted extra-small d-block" style="font-size: 10px;">
                                <?= number_format($summary['dev_charge_count'] ?? 0) ?> Collections
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Monthly EMI & Settlements -->
            <div class="col">
                <div class="ced-stat-card h-100">
                    <div class="d-flex align-items-center gap-2">
                        <div class="ced-stat-icon c-emi">
                            <i class="fa-solid fa-calendar-check"></i>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="text-muted extra-small text-uppercase fw-bold text-success" style="font-size: 10px;">EMI &amp; Due Settled</div>
                            <div class="fw-bold text-dark fs-14 text-truncate" title="৳<?= number_format($summary['total_emi_amount'] ?? 0, 2) ?>">
                                ৳<?= number_format($summary['total_emi_amount'] ?? 0, 2) ?>
                            </div>
                            <small class="text-muted extra-small d-block" style="font-size: 10px;">
                                <?= number_format($summary['total_emi_count'] ?? 0) ?> Installments
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Booking & Registration (Rare) -->
            <div class="col">
                <div class="ced-stat-card h-100">
                    <div class="d-flex align-items-center gap-2">
                        <div class="ced-stat-icon c-book">
                            <i class="fa-solid fa-file-signature"></i>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="text-muted extra-small text-uppercase fw-bold text-purple" style="font-size: 10px; color:#9333ea;">Booking &amp; Reg</div>
                            <div class="fw-bold text-dark fs-14 text-truncate" title="৳<?= number_format(($summary['booking_amount'] ?? 0) + ($summary['registration_amount'] ?? 0), 2) ?>">
                                ৳<?= number_format(($summary['booking_amount'] ?? 0) + ($summary['registration_amount'] ?? 0), 2) ?>
                            </div>
                            <small class="text-muted extra-small d-block" style="font-size: 10px;">
                                <?= number_format(($summary['booking_count'] ?? 0) + ($summary['registration_count'] ?? 0)) ?> Trx (Rare)
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. Cash In Hand -->
            <div class="col">
                <div class="ced-stat-card h-100">
                    <div class="d-flex align-items-center gap-2">
                        <div class="ced-stat-icon c-cash">
                            <i class="fa-solid fa-money-bill-wave"></i>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="text-muted extra-small text-uppercase fw-bold" style="font-size: 10px;">Cash Collected</div>
                            <div class="fw-bold text-success fs-14 text-truncate" title="৳<?= number_format($summary['cash_amount'] ?? 0, 2) ?>">
                                ৳<?= number_format($summary['cash_amount'] ?? 0, 2) ?>
                            </div>
                            <small class="text-muted extra-small d-block" style="font-size: 10px;">
                                <?= number_format($summary['cash_count'] ?? 0) ?> Cash Entries
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 6. Bank & Digital Inflow -->
            <div class="col">
                <div class="ced-stat-card h-100">
                    <div class="d-flex align-items-center gap-2">
                        <div class="ced-stat-icon c-bank">
                            <i class="fa-solid fa-building-columns"></i>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="text-muted extra-small text-uppercase fw-bold" style="font-size: 10px;">Bank / Digital</div>
                            <div class="fw-bold text-primary fs-14 text-truncate" title="৳<?= number_format(($summary['bank_amount'] ?? 0) + ($summary['cheque_amount'] ?? 0) + ($summary['online_amount'] ?? 0), 2) ?>">
                                ৳<?= number_format(($summary['bank_amount'] ?? 0) + ($summary['cheque_amount'] ?? 0) + ($summary['online_amount'] ?? 0), 2) ?>
                            </div>
                            <small class="text-muted extra-small d-block" style="font-size: 10px;">
                                <?= number_format(($summary['bank_count'] ?? 0) + ($summary['cheque_count'] ?? 0) + ($summary['online_count'] ?? 0)) ?> Transfers / Chq
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Mode Switcher -->
    <div class="col-12 mb-3">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-2 px-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small fw-bold text-uppercase d-none d-md-inline" style="font-size: 11px;">
                        <i class="fa-solid fa-sliders text-primary me-1"></i> Statement View:
                    </span>
                    <div class="btn-group btn-group-sm p-1 bg-light rounded-2 border" role="group">
                        <button type="button" class="btn btn-sm ced-mode-btn <?= $curViewMode === 'collectors' ? 'btn-primary shadow-sm active' : 'btn-light text-secondary' ?>" onclick="switchView('collectors')">
                            <i class="fa-solid fa-users-gear me-1"></i> Executive Matrix
                        </button>
                        <button type="button" class="btn btn-sm ced-mode-btn <?= $curViewMode === 'transactions' ? 'btn-primary shadow-sm active' : 'btn-light text-secondary' ?>" onclick="switchView('transactions')">
                            <i class="fa-solid fa-list-check me-1"></i> Detailed Register
                        </button>
                        <button type="button" class="btn btn-sm ced-mode-btn <?= $curViewMode === 'type_summary' ? 'btn-primary shadow-sm active' : 'btn-light text-secondary' ?>" onclick="switchView('type_summary')">
                            <i class="fa-solid fa-chart-pie me-1"></i> Payment Heads
                        </button>
                        <button type="button" class="btn btn-sm ced-mode-btn <?= $curViewMode === 'all' ? 'btn-primary shadow-sm active' : 'btn-light text-secondary' ?>" onclick="switchView('all')">
                            <i class="fa-solid fa-table-cells-large me-1"></i> Full Report (All)
                        </button>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <a href="<?= $printColUrl ?>" target="_blank" class="btn btn-sm btn-light border text-dark d-flex align-items-center gap-1 shadow-xs" title="Print Executive Summary">
                        <i class="fa-solid fa-print text-primary"></i> <span class="d-none d-sm-inline">Print View</span>
                    </a>
                    <a href="<?= $exportColUrl ?>" class="btn btn-sm btn-light border text-dark d-flex align-items-center gap-1 shadow-xs" title="Export CSV Summary">
                        <i class="fa-solid fa-file-csv text-success"></i> <span class="d-none d-sm-inline">Export Matrix</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 1. Executive / Collector Collection Matrix (DEFAULT VIEW) -->
    <div class="col-12 mb-4" id="section-collectors" style="<?= in_array($curViewMode, ['transactions', 'type_summary'], true) ? 'display:none;' : '' ?>">
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-1 fw-bold text-dark d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white p-2 rounded-2"><i class="fa-solid fa-users-gear"></i></span>
                        <span>Executive-wise Collection Performance Matrix</span>
                    </h5>
                    <div class="text-muted small">
                        Detailed breakdown of collections created by each executive: Development Charges, Regular EMIs, Due Settlements &amp; Down Payments
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="input-group input-group-sm cs-table-search-box">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" id="collectorTableSearch" onkeyup="filterCollectorTable()" class="form-control border-start-0" placeholder="Filter executive...">
                    </div>
                    <a href="<?= $exportColUrl ?>" class="btn btn-sm btn-outline-success d-flex align-items-center gap-1 shadow-sm">
                        <i class="fa-solid fa-file-excel"></i> <span>Export Matrix</span>
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle table-ced mb-0" id="collectorTable">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 45px;">#</th>
                            <th style="min-width: 200px;">Executive / Collector Name</th>
                            <th class="text-center" style="width: 100px;">Total Trx</th>
                            <th class="text-end" style="min-width: 140px; background-color: #fffbeb !important;">Dev Charge (BDT)</th>
                            <th class="text-end" style="min-width: 140px; background-color: #f0fdf4 !important;">Regular EMI (BDT)</th>
                            <th class="text-end" style="min-width: 140px; background-color: #f0fdf4 !important;">Due Settled (BDT)</th>
                            <th class="text-end" style="min-width: 130px;">Booking / Reg (BDT)</th>
                            <th class="text-end" style="min-width: 150px; background-color: #f8fafc !important;">Total Collected (BDT)</th>
                            <th style="min-width: 160px;">% Share</th>
                            <th class="text-center" style="width: 90px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($collector_summary['rows'])): ?>
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-users-slash fa-2x mb-2 text-secondary opacity-50"></i>
                                    <div>No collection records found for the selected period &amp; filters.</div>
                                </td>
                            </tr>
                        <?php else:
                            $cSl = 1;
                            $grandTotal = $collector_summary['grand']['total_amount'] ?? 0;
                            foreach ($collector_summary['rows'] as $row):
                                $colFilterUrl = '?' . http_build_query(array_merge($queryParams, [
                                    'collector_id' => $row['created_by'],
                                    'view_mode'    => 'transactions'
                                ])) . '#section-transactions';
                                $initials = strtoupper(substr($row['collector_name'], 0, 2));
                        ?>
                            <tr class="collector-row">
                                <td class="text-center text-muted fw-bold"><?= $cSl++ ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="collector-avatar">
                                            <?= htmlspecialchars($initials) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark collector-name-cell">
                                                <?= htmlspecialchars($row['collector_name']) ?>
                                                <?php if (!empty($row['is_ced_member'])): ?>
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle badge-ced ms-1">CED</span>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted extra-small">
                                                <?= htmlspecialchars($row['collector_role']) ?>
                                                <?= !empty($row['emp_code']) ? ' &bull; ID: ' . htmlspecialchars($row['emp_code']) : '' ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center font-monospace">
                                    <span class="badge bg-light text-dark border px-2 py-1"><?= number_format($row['total_count']) ?></span>
                                </td>
                                <!-- Dev Charge -->
                                <td class="text-end font-monospace text-warning fw-semibold" style="background-color: #fffdf5;">
                                    <?php if ($row['dev_charge_amount'] > 0): ?>
                                        <div>৳<?= number_format($row['dev_charge_amount'], 2) ?></div>
                                        <small class="text-muted extra-small">(<?= $row['dev_charge_count'] ?> Trx)</small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <!-- Regular EMI -->
                                <td class="text-end font-monospace text-success fw-semibold" style="background-color: #f7fee7;">
                                    <?php if ($row['emi_amount'] > 0): ?>
                                        <div>৳<?= number_format($row['emi_amount'], 2) ?></div>
                                        <small class="text-muted extra-small">(<?= $row['emi_count'] ?> Trx)</small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <!-- Due Settlement / Advance -->
                                <td class="text-end font-monospace text-info fw-semibold" style="background-color: #f0fdf4;">
                                    <?php if ($row['settlement_amount'] > 0): ?>
                                        <div>৳<?= number_format($row['settlement_amount'], 2) ?></div>
                                        <small class="text-muted extra-small">(<?= $row['settlement_count'] ?> Trx)</small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <!-- Booking & Reg (Rare) -->
                                <td class="text-end font-monospace">
                                    <?php 
                                    $bookRegAmt = $row['booking_amount'] + $row['registration_amount'];
                                    $bookRegCnt = $row['booking_count'] + $row['registration_count'];
                                    if ($bookRegAmt > 0): 
                                    ?>
                                        <div class="text-purple fw-semibold" style="color: #9333ea;">৳<?= number_format($bookRegAmt, 2) ?></div>
                                        <small class="text-muted extra-small">(<?= $bookRegCnt ?> Trx)</small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <!-- Total Amount -->
                                <td class="text-end font-monospace fw-bold text-dark fs-13" style="background-color: #f8fafc;">
                                    ৳<?= number_format($row['total_amount'], 2) ?>
                                </td>
                                <!-- % Share Progress Bar -->
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 6px;">
                                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?= min(100, $row['percentage']) ?>%"></div>
                                        </div>
                                        <span class="small font-monospace text-muted" style="width: 44px; font-size: 11px;"><?= number_format($row['percentage'], 1) ?>%</span>
                                    </div>
                                </td>
                                <!-- Action -->
                                <td class="text-center">
                                    <a href="<?= $colFilterUrl ?>" class="btn btn-xs btn-outline-primary py-1 px-2 rounded-2" style="font-size: 11px;" title="View all transactions created by this executive">
                                        <i class="fa-solid fa-list me-1"></i> Register
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                    <?php if (!empty($collector_summary['rows'])): 
                        $g = $collector_summary['grand'];
                    ?>
                        <tfoot class="table-light">
                            <tr class="fw-bold bg-light" style="border-top: 2px solid #cbd5e1; font-size: 13.5px;">
                                <td class="text-center text-muted"><?= count($collector_summary['rows']) ?></td>
                                <td class="text-uppercase text-dark">
                                    <i class="fa-solid fa-calculator text-primary me-1"></i> GRAND TOTAL:
                                </td>
                                <td class="text-center font-monospace">
                                    <span class="badge bg-dark text-white px-2 py-1"><?= number_format($g['total_count']) ?></span>
                                </td>
                                <td class="text-end font-monospace text-warning">
                                    ৳<?= number_format($g['dev_charge_amount'], 2) ?>
                                </td>
                                <td class="text-end font-monospace text-success">
                                    ৳<?= number_format($g['emi_amount'], 2) ?>
                                </td>
                                <td class="text-end font-monospace text-info">
                                    ৳<?= number_format($g['settlement_amount'], 2) ?>
                                </td>
                                <td class="text-end font-monospace text-purple" style="color: #9333ea;">
                                    ৳<?= number_format($g['booking_amount'] + $g['registration_amount'], 2) ?>
                                </td>
                                <td class="text-end font-monospace text-primary fs-14 bg-primary-subtle">
                                    ৳<?= number_format($g['total_amount'], 2) ?>
                                </td>
                                <td>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold">100.0%</span>
                                </td>
                                <td class="text-center">
                                    <a href="<?= $printColUrl ?>" target="_blank" class="btn btn-xs btn-primary py-1 px-2" style="font-size: 11px;">
                                        <i class="fa-solid fa-print"></i> Print
                                    </a>
                                </td>
                            </tr>
                        </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <!-- 2. Detailed Collection Records Register -->
    <div class="col-12 mb-4" id="section-transactions" style="<?= in_array($curViewMode, ['collectors', 'type_summary'], true) ? 'display:none;' : '' ?>">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <span class="badge bg-success text-white p-2 rounded-2"><i class="fa-solid fa-list-check"></i></span>
                        <span>Detailed Collection Records Register</span>
                    </h5>
                    <div class="text-muted small">
                        Showing <?= count($transactions) ?> of <?= number_format($pagination['total_records']) ?> records &bull; Page <?= $pagination['current_page'] ?> of <?= $pagination['total_pages'] ?>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <a href="<?= $printTrxUrl ?>" target="_blank" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1 shadow-sm">
                        <i class="fa-solid fa-print"></i> <span>Print Register</span>
                    </a>
                    <a href="<?= $exportTrxUrl ?>" class="btn btn-sm btn-outline-success d-flex align-items-center gap-1 shadow-sm">
                        <i class="fa-solid fa-file-excel"></i> <span>Export Register CSV</span>
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle table-ced mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 45px;">#</th>
                            <th style="min-width: 95px;">Payment Date</th>
                            <th style="min-width: 170px;">Client / Depositor</th>
                            <th style="min-width: 160px;">Project &amp; Unit</th>
                            <th style="min-width: 130px;">Payment Purpose</th>
                            <th style="min-width: 100px;">Method</th>
                            <th style="min-width: 175px;">Banking Details</th>
                            <th style="min-width: 130px;">Voucher / Ref</th>
                            <th class="text-end" style="min-width: 130px;">Amount (BDT)</th>
                            <th style="min-width: 150px;">Collector (Created By)</th>
                            <th class="text-center" style="min-width: 110px;">Verification</th>
                            <th class="text-center" style="width: 70px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="12" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-inbox fa-3x mb-3 text-secondary opacity-50"></i>
                                    <div class="fw-semibold">No collection transactions found matching the selected filters.</div>
                                    <small>Try selecting a different date range or resetting filters.</small>
                                </td>
                            </tr>
                        <?php else:
                            $sl = (($pagination['current_page'] - 1) * $pagination['limit']) + 1;
                            $pageTotal = 0;
                            foreach ($transactions as $t):
                                $amt = (float)($t['amount'] ?? 0);
                                $pageTotal += $amt;
                                $pInfo = $t['purpose_info'] ?? \CedCollectionStatementController::formatPaymentPurpose($t);
                        ?>
                            <tr>
                                <td class="text-center text-muted fw-bold"><?= $sl++ ?></td>
                                <td class="font-monospace text-nowrap">
                                    <?= date('d M, Y', strtotime($t['payment_date'])) ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($t['client_name'] ?? 'N/A') ?></div>
                                    <small class="text-muted font-monospace"><?= htmlspecialchars($t['client_phone'] ?? '') ?></small>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($t['project_name'] ?? 'N/A') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($t['flat_name'] ?? 'N/A') ?></small>
                                </td>
                                <td>
                                    <span class="badge <?= $pInfo['badge_class'] ?> px-2 py-1" title="<?= htmlspecialchars($pInfo['full_label'] ?? $pInfo['label']) ?>">
                                        <i class="<?= $pInfo['icon'] ?? 'fa-solid fa-tag' ?> me-1" style="font-size: 10px;"></i><?= htmlspecialchars($pInfo['label']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1 text-uppercase">
                                        <?= htmlspecialchars($t['payment_method'] ?? 'cash') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $hasCompanyBank = !empty($t['company_bank_name']);
                                    $hasClientBank  = !empty($t['bank_name']);
                                    $hasCheque      = !empty($t['cheque_no']);
                                    $method         = strtolower($t['payment_method'] ?? 'cash');
                                    ?>
                                    <?php if ($hasCompanyBank): ?>
                                        <div class="text-nowrap" title="Company Deposit Bank: <?= htmlspecialchars($t['company_bank_name'] . (!empty($t['company_account_name']) ? ' - ' . $t['company_account_name'] : '')) ?>">
                                            <span class="text-muted extra-small text-uppercase fw-bold" style="font-size: 9.5px; letter-spacing: 0.3px;">To:</span>
                                            <strong class="text-dark small"><i class="fa-solid fa-building-columns text-primary me-1" style="font-size: 10px;"></i><?= htmlspecialchars($t['company_bank_name']) ?></strong>
                                            <?php if (!empty($t['company_account_number'])): ?>
                                                <span class="font-monospace text-primary fw-semibold" style="font-size: 11px;">(A/C: <?= htmlspecialchars($t['company_account_number']) ?>)</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($hasClientBank): ?>
                                        <div class="text-nowrap text-muted extra-small" title="Client Sender Bank: <?= htmlspecialchars($t['bank_name'] . (!empty($t['bank_account_no']) ? ' (' . $t['bank_account_no'] . ')' : '')) ?>">
                                            <span class="text-muted text-uppercase fw-semibold" style="font-size: 9.5px;">From:</span>
                                            <span class="text-secondary"><?= htmlspecialchars($t['bank_name']) ?></span>
                                            <?php if (!empty($t['bank_account_no']) && !in_array(strtolower(trim($t['bank_account_no'])), ['n/a', '0', '00', 'none', 'null'], true)): ?>
                                                <span class="font-monospace text-muted">(A/C: <?= htmlspecialchars($t['bank_account_no']) ?>)</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($hasCheque): ?>
                                        <div class="extra-small text-muted font-monospace">
                                            <i class="fa-solid fa-money-check text-warning me-1" style="font-size: 10px;"></i>Chq: <strong class="text-dark"><?= htmlspecialchars($t['cheque_no']) ?></strong>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!$hasCompanyBank && !$hasClientBank && !$hasCheque): ?>
                                        <span class="text-muted extra-small"><?= $method === 'cash' ? 'Cash Inflow' : '-' ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="font-monospace small">
                                    <?= htmlspecialchars($t['transaction_ref'] ?? '-') ?>
                                </td>
                                <td class="text-end font-monospace fw-bold text-dark fs-13">
                                    ৳<?= number_format($amt, 2) ?>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark small"><?= htmlspecialchars($t['collector_name']) ?></div>
                                    <small class="text-muted extra-small"><?= htmlspecialchars($t['collector_role']) ?></small>
                                </td>
                                <td class="text-center">
                                    <?php if (($t['verification_status'] ?? '') === 'verified'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle badge-ced">
                                            <i class="fa-solid fa-check me-1"></i> Verified
                                        </span>
                                    <?php elseif (($t['verification_status'] ?? '') === 'rejected'): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle badge-ced">
                                            <i class="fa-solid fa-xmark me-1"></i> Rejected
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle badge-ced">
                                            <i class="fa-solid fa-clock me-1"></i> Pending
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="<?= $base_url ?>/payment-invoice/<?= $t['id'] ?>" target="_blank" class="btn btn-xs btn-outline-secondary py-1 px-2 rounded-2" style="font-size: 11px;" title="View Money Receipt">
                                        <i class="fa-solid fa-receipt"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                    <?php if (!empty($transactions)): ?>
                        <tfoot class="table-light">
                            <tr class="fw-bold bg-light" style="border-top: 2px solid #cbd5e1;">
                                <td colspan="8" class="text-end text-uppercase">Page Subtotal:</td>
                                <td class="text-end font-monospace text-primary fs-14 bg-primary-subtle">
                                    ৳<?= number_format($pageTotal, 2) ?>
                                </td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    <?php endif; ?>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="card-footer bg-white border-top py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="text-muted small">
                        Showing page <strong><?= $pagination['current_page'] ?></strong> of <strong><?= $pagination['total_pages'] ?></strong> (<?= number_format($pagination['total_records']) ?> total records)
                    </div>
                    <nav aria-label="Transaction pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <?php 
                            $cPage = $pagination['current_page'];
                            $tPages = $pagination['total_pages'];
                            $pageRange = range(max(1, $cPage - 2), min($tPages, $cPage + 2));
                            ?>
                            <li class="page-item <?= $cPage <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($queryParams, ['page' => 1])) ?>">&laquo;</a>
                            </li>
                            <li class="page-item <?= $cPage <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($queryParams, ['page' => $cPage - 1])) ?>">&lsaquo;</a>
                            </li>
                            <?php foreach ($pageRange as $pNum): ?>
                                <li class="page-item <?= $pNum === $cPage ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($queryParams, ['page' => $pNum])) ?>"><?= $pNum ?></a>
                                </li>
                            <?php endforeach; ?>
                            <li class="page-item <?= $cPage >= $tPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($queryParams, ['page' => $cPage + 1])) ?>">&rsaquo;</a>
                            </li>
                            <li class="page-item <?= $cPage >= $tPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($queryParams, ['page' => $tPages])) ?>">&raquo;</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 3. Payment Head / Purpose Matrix -->
    <div class="col-12 mb-4" id="section-type-summary" style="<?= in_array($curViewMode, ['collectors', 'transactions'], true) ? 'display:none;' : '' ?>">
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-1 fw-bold text-dark d-flex align-items-center gap-2">
                        <span class="badge bg-info text-white p-2 rounded-2"><i class="fa-solid fa-chart-pie"></i></span>
                        <span>Payment Head / Purpose Breakdown Matrix</span>
                    </h5>
                    <div class="text-muted small">
                        Inflow distribution across Development Charges, EMIs, Booking Money, Registration &amp; Other Heads
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="<?= $printTypeUrl ?>" target="_blank" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1 shadow-sm">
                        <i class="fa-solid fa-print"></i> <span>Print Heads</span>
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle table-ced mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 45px;">#</th>
                            <th style="min-width: 200px;">Payment Purpose / Head</th>
                            <th class="text-center" style="width: 100px;">Trx Count</th>
                            <th class="text-end" style="min-width: 130px;">Cash Inflow (BDT)</th>
                            <th class="text-end" style="min-width: 130px;">Bank Inflow (BDT)</th>
                            <th class="text-end" style="min-width: 130px;">Cheque Inflow (BDT)</th>
                            <th class="text-end" style="min-width: 130px;">Online Inflow (BDT)</th>
                            <th class="text-end" style="min-width: 150px; background-color: #f8fafc !important;">Total Amount (BDT)</th>
                            <th style="min-width: 160px;">% Share</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($type_summary['rows'])): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">No data available for payment heads.</td>
                            </tr>
                        <?php else:
                            $tSl = 1;
                            foreach ($type_summary['rows'] as $tRow):
                        ?>
                            <tr>
                                <td class="text-center text-muted fw-bold"><?= $tSl++ ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($tRow['label']) ?></div>
                                </td>
                                <td class="text-center font-monospace">
                                    <span class="badge bg-light text-dark border px-2 py-1"><?= number_format($tRow['count']) ?></span>
                                </td>
                                <td class="text-end font-monospace">৳<?= number_format($tRow['cash_amount'], 2) ?></td>
                                <td class="text-end font-monospace">৳<?= number_format($tRow['bank_amount'], 2) ?></td>
                                <td class="text-end font-monospace">৳<?= number_format($tRow['cheque_amount'], 2) ?></td>
                                <td class="text-end font-monospace">৳<?= number_format($tRow['online_amount'], 2) ?></td>
                                <td class="text-end font-monospace fw-bold text-dark fs-13" style="background-color: #f8fafc;">
                                    ৳<?= number_format($tRow['total_amount'], 2) ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 6px;">
                                            <div class="progress-bar bg-info" role="progressbar" style="width: <?= min(100, $tRow['percentage']) ?>%"></div>
                                        </div>
                                        <span class="small font-monospace text-muted" style="width: 44px; font-size: 11px;"><?= number_format($tRow['percentage'], 1) ?>%</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                    <?php if (!empty($type_summary['rows'])): 
                        $tg = $type_summary['grand'];
                    ?>
                        <tfoot class="table-light">
                            <tr class="fw-bold bg-light" style="border-top: 2px solid #cbd5e1;">
                                <td colspan="2" class="text-uppercase text-dark">Total:</td>
                                <td class="text-center font-monospace"><span class="badge bg-dark text-white px-2 py-1"><?= number_format($tg['count']) ?></span></td>
                                <td class="text-end font-monospace">৳<?= number_format($tg['cash_amount'], 2) ?></td>
                                <td class="text-end font-monospace">৳<?= number_format($tg['bank_amount'], 2) ?></td>
                                <td class="text-end font-monospace">৳<?= number_format($tg['cheque_amount'], 2) ?></td>
                                <td class="text-end font-monospace">৳<?= number_format($tg['online_amount'], 2) ?></td>
                                <td class="text-end font-monospace text-primary fs-14 bg-primary-subtle">৳<?= number_format($tg['total_amount'], 2) ?></td>
                                <td><span class="badge bg-success-subtle text-success border border-success-subtle fw-bold">100.0%</span></td>
                            </tr>
                        </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function togglePeriodFields() {
    var pType = document.getElementById('periodTypeSelect').value;
    var customFields = document.querySelectorAll('.period-custom-field');
    var monthlyFields = document.querySelectorAll('.period-monthly-field');

    if (pType === 'custom') {
        customFields.forEach(function(el) { el.style.display = 'block'; });
        monthlyFields.forEach(function(el) { el.style.display = 'none'; });
    } else if (pType === 'monthly') {
        customFields.forEach(function(el) { el.style.display = 'none'; });
        monthlyFields.forEach(function(el) { el.style.display = 'block'; });
    } else {
        customFields.forEach(function(el) { el.style.display = 'none'; });
        monthlyFields.forEach(function(el) { el.style.display = 'none'; });
    }
}

function switchView(mode) {
    document.getElementById('viewModeInput').value = mode;

    var secCollectors = document.getElementById('section-collectors');
    var secTrx = document.getElementById('section-transactions');
    var secType = document.getElementById('section-type-summary');

    if (mode === 'collectors') {
        secCollectors.style.display = 'block';
        secTrx.style.display = 'none';
        secType.style.display = 'none';
    } else if (mode === 'transactions') {
        secCollectors.style.display = 'none';
        secTrx.style.display = 'block';
        secType.style.display = 'none';
    } else if (mode === 'type_summary') {
        secCollectors.style.display = 'none';
        secTrx.style.display = 'none';
        secType.style.display = 'block';
    } else {
        // all
        secCollectors.style.display = 'block';
        secTrx.style.display = 'block';
        secType.style.display = 'block';
    }

    // Update active button state
    document.querySelectorAll('.ced-mode-btn').forEach(function(btn) {
        btn.classList.remove('btn-primary', 'shadow-sm', 'active');
        btn.classList.add('btn-light', 'text-secondary');
    });
    if (event && event.target) {
        var clickedBtn = event.target.closest('.ced-mode-btn');
        if (clickedBtn) {
            clickedBtn.classList.remove('btn-light', 'text-secondary');
            clickedBtn.classList.add('btn-primary', 'shadow-sm', 'active');
        }
    }
}

function filterCollectorTable() {
    var input = document.getElementById('collectorTableSearch');
    var filter = input.value.toLowerCase().trim();
    var rows = document.querySelectorAll('#collectorTable tbody tr.collector-row');

    rows.forEach(function(row) {
        var text = row.querySelector('.collector-name-cell') ? row.querySelector('.collector-name-cell').innerText.toLowerCase() : '';
        if (text.indexOf(filter) > -1) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

(function() {
    function initModuleScripts() {
        if (typeof jQuery === 'undefined' || typeof bootstrap === 'undefined') {
            setTimeout(initModuleScripts, 50);
            return;
        }

        jQuery(function($) {
            // Initialize Select2 on Collector and Project dropdowns
            if (typeof $.fn.select2 === 'function') {
                $('#collectorSelect, #projectSelect').each(function() {
                    var $el = $(this);
                    if ($el.hasClass('select2-hidden-accessible')) {
                        return;
                    }
                    var placeholder = $el.data('placeholder') || 'Select option';
                    $el.select2({
                        width: '100%',
                        placeholder: placeholder,
                        allowClear: true
                    });
                });
            }

            // Initialize any popovers or tooltips safely
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(el) {
                return new bootstrap.Tooltip(el);
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModuleScripts);
    } else {
        initModuleScripts();
    }
})();
</script>

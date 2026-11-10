<?php
$page_title = "Total Collection Statement";
$page = 'Accounts';
$show_breadcrumb = true;
$isAccExecutive = function_exists('isAccountsExecutive') ? isAccountsExecutive() : ((int)($_SESSION['user']['role_id'] ?? 0) === 36);

$base_url = $base_url ?? ($GLOBALS['base_url'] ?? '');

$filters = $filters ?? [];
$summary = $summary ?? [];
$transactions = $transactions ?? [];
$projects = $projects ?? [];
$bank_names = $bank_names ?? [];
$pagination = $pagination ?? ['current_page' => 1, 'total_pages' => 1, 'total_records' => 0, 'limit' => 50];

$curPeriodType = $filters['period_type'] ?? 'monthly';
$curYear       = $filters['year'] ?? (int)date('Y');
$curMonth      = $filters['month'] ?? date('m');
$curStartDate  = $filters['start_date'] ?? '';
$curEndDate    = $filters['end_date'] ?? '';
$curProject    = $filters['project_id'] ?? '';
$curSource     = $filters['source'] ?? 'all';
$curMethod     = $filters['payment_method'] ?? '';
$curBank       = $filters['bank_name'] ?? '';
$curType       = $filters['payment_type'] ?? '';
$curVer        = $filters['verification_status'] ?? '';
$curSearch     = $filters['search'] ?? '';

$typeSummary = $type_summary ?? ['rows' => [], 'grand' => ['count' => 0, 'total_amount' => 0, 'bank_amount' => 0, 'cash_amount' => 0, 'cheque_amount' => 0, 'online_amount' => 0]];
$curViewMode = $view_mode ?? ($_GET['view_mode'] ?? 'type_summary');

// Generate print and export URLs
$queryParams = $_GET;
$printUrl = $base_url . '/collection-statement-print?' . http_build_query($queryParams);
$exportUrl = $base_url . '/collection-statement-export?' . http_build_query($queryParams);

$printTypeUrl = $base_url . '/collection-statement-print?' . http_build_query(array_merge($queryParams, ['print_mode' => 'type_summary']));
$printBankUrl = $base_url . '/collection-statement-print?' . http_build_query(array_merge($queryParams, ['print_mode' => 'bank_summary']));
$printFullUrl = $base_url . '/collection-statement-print?' . http_build_query(array_merge($queryParams, ['print_mode' => 'full']));
$printTrxUrl  = $base_url . '/collection-statement-print?' . http_build_query(array_merge($queryParams, ['print_mode' => 'transactions']));

$exportTypeUrl = $base_url . '/collection-statement-export?' . http_build_query(array_merge($queryParams, ['export_mode' => 'type_summary']));
$exportBankUrl = $base_url . '/collection-statement-export?' . http_build_query(array_merge($queryParams, ['export_mode' => 'bank_summary']));
$exportTrxUrl  = $base_url . '/collection-statement-export?' . http_build_query(array_merge($queryParams, ['export_mode' => 'detailed']));
?>

<link rel="stylesheet" href="<?= $base_url ?>/public/assets/css/collection-statement.css">

<div class="row">
    <!-- Page Header & Action Bar -->
    <div class="col-12 mb-3">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 flex-wrap">
                    <div>
                        <h4 class="mb-1 fw-bold text-dark">
                            <i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i>Total Collection Statement
                        </h4>
                        <p class="text-muted mb-0 small">
                            Date &amp; month-wise detailed breakdown of collections by bank, cash, cheque, and payment types
                        </p>
                    </div>
                    <div class="cs-header-btns ms-md-auto">
                        <!-- Print Dropdown -->
                        <div class="dropdown cs-dropdown-wrapper">
                            <button class="btn btn-sm btn-outline-primary dropdown-toggle shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-print"></i> <span>Print Statement</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border py-2" style="min-width: 250px;">
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="<?= $printTypeUrl ?>" target="_blank">
                                        <i class="fa-solid fa-chart-pie text-primary fs-14"></i>
                                        <div>
                                            <div class="fw-bold fs-12 text-dark">Payment Type Statement (1 Page)</div>
                                            <small class="text-muted extra-small">Booking, Reg, EMI, Dev Charge Breakdown</small>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="<?= $printBankUrl ?>" target="_blank">
                                        <i class="fa-solid fa-building-columns text-info fs-14"></i>
                                        <div>
                                            <div class="fw-bold fs-12 text-dark">Bank Account Sheet</div>
                                            <small class="text-muted extra-small">Deposit Inflow breakdown by bank</small>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="<?= $printFullUrl ?>" target="_blank">
                                        <i class="fa-solid fa-file-invoice text-success fs-14"></i>
                                        <div>
                                            <div class="fw-bold fs-12 text-dark">Combined Statement (Full)</div>
                                            <small class="text-muted extra-small">Payment Types + Bank Accounts + Detailed Register</small>
                                        </div>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider my-1"></li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="<?= $printTrxUrl ?>" target="_blank">
                                        <i class="fa-solid fa-list-ol text-secondary fs-14"></i>
                                        <div>
                                            <div class="fw-bold fs-12 text-dark">Detailed Transactions Register</div>
                                            <small class="text-muted extra-small">Individual collection inflow lines</small>
                                        </div>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <!-- Export Dropdown -->
                        <div class="dropdown cs-dropdown-wrapper">
                            <button class="btn btn-sm btn-outline-success dropdown-toggle shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-file-excel"></i> <span>Export Excel/CSV</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border py-2" style="min-width: 250px;">
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="<?= $exportTypeUrl ?>">
                                        <i class="fa-solid fa-chart-simple text-success fs-14"></i>
                                        <div>
                                            <div class="fw-bold fs-12 text-dark">Export Payment Type Matrix</div>
                                            <small class="text-muted extra-small">Count, Bank, Cash, Cheque &amp; % Share</small>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="<?= $exportBankUrl ?>">
                                        <i class="fa-solid fa-building-columns text-info fs-14"></i>
                                        <div>
                                            <div class="fw-bold fs-12 text-dark">Export Bank Account Sheet</div>
                                            <small class="text-muted extra-small">Inflow breakdown across all bank accounts</small>
                                        </div>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider my-1"></li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="<?= $exportTrxUrl ?>">
                                        <i class="fa-solid fa-file-csv text-primary fs-14"></i>
                                        <div>
                                            <div class="fw-bold fs-12 text-dark">Export Detailed Transactions</div>
                                            <small class="text-muted extra-small">All filtered individual rows</small>
                                        </div>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <!-- Dashboard Link -->
                        <a href="<?= $GLOBALS['base_url'] ?>/accounts-dashboard" class="btn btn-sm btn-outline-secondary shadow-sm">
                            <i class="fa-solid fa-chart-line"></i> <span>Dashboard</span>
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
                <form method="GET" action="<?= $GLOBALS['base_url'] ?>/collection-statement" id="collectionFilterForm">
                    <div class="row g-2 align-items-end">
                        <!-- Period Type -->
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                            <label class="form-label extra-small fw-bold text-muted mb-1 text-uppercase" style="font-size: 11px;">Period Type</label>
                            <select name="period_type" id="periodTypeSelect" class="form-select form-select-sm" onchange="togglePeriodFields()">
                                <option value="monthly" <?= $curPeriodType === 'monthly' ? 'selected' : '' ?>>Monthly Breakdown</option>
                                <option value="custom" <?= $curPeriodType === 'custom' ? 'selected' : '' ?>>Custom Date Range</option>
                                <option value="today" <?= in_array($curPeriodType, ['today', 'daily']) ? 'selected' : '' ?>>Today</option>
                                <option value="this_week" <?= in_array($curPeriodType, ['this_week', 'weekly']) ? 'selected' : '' ?>>This Week</option>
                                <option value="yearly" <?= $curPeriodType === 'yearly' ? 'selected' : '' ?>>Full Year</option>
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
                        <div class="col-xl-2 col-lg-3 col-md-3 col-sm-6 period-monthly-field">
                            <label class="form-label extra-small fw-bold text-muted mb-1 text-uppercase" style="font-size: 11px;">Month</label>
                            <select name="month" class="form-select form-select-sm">
                                <option value="all" <?= $curMonth === 'all' ? 'selected' : '' ?>>All Months (Whole Year)</option>
                                <?php for ($m = 1; $m <= 12; $m++): 
                                    $mVal = sprintf('%02d', $m);
                                ?>
                                    <option value="<?= $mVal ?>" <?= $curMonth === $mVal ? 'selected' : '' ?>>
                                        <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <!-- Custom Date Range Fields (hidden unless custom selected) -->
                        <div class="col-xl-2 col-lg-3 col-md-3 col-sm-6 period-custom-field" style="display:none;">
                            <label class="form-label extra-small fw-bold text-muted mb-1 text-uppercase" style="font-size: 11px;">From Date</label>
                            <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($curStartDate) ?>">
                        </div>
                        <div class="col-xl-2 col-lg-3 col-md-3 col-sm-6 period-custom-field" style="display:none;">
                            <label class="form-label extra-small fw-bold text-muted mb-1 text-uppercase" style="font-size: 11px;">To Date</label>
                            <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($curEndDate) ?>">
                        </div>

                        <!-- Project Filter -->
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                            <label class="form-label extra-small fw-bold text-muted mb-1 text-uppercase" style="font-size: 11px;">Project</label>
                            <select name="project_id" class="form-select form-select-sm">
                                <option value="">All Projects</option>
                                <?php foreach ($projects as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= (string)$curProject === (string)$p['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Source Filter -->
                        <div class="col-xl-1 col-lg-2 col-md-3 col-sm-6">
                            <label class="form-label extra-small fw-bold text-muted mb-1 text-uppercase" style="font-size: 11px;">Source</label>
                            <select name="source" class="form-select form-select-sm">
                                <option value="all" <?= $curSource === 'all' ? 'selected' : '' ?>>All Sources</option>
                                <option value="client" <?= $curSource === 'client' ? 'selected' : '' ?>>Client</option>
                                <option value="management" <?= $curSource === 'management' ? 'selected' : '' ?>>Management</option>
                            </select>
                        </div>

                        <!-- Payment Method Filter -->
                        <div class="col-xl-2 col-lg-2 col-md-3 col-sm-6">
                            <label class="form-label extra-small fw-bold text-muted mb-1 text-uppercase" style="font-size: 11px;">Method</label>
                            <select name="payment_method" class="form-select form-select-sm">
                                <option value="">All Methods</option>
                                <option value="bank" <?= $curMethod === 'bank' ? 'selected' : '' ?>>Bank Transfer / Deposit</option>
                                <option value="cash" <?= $curMethod === 'cash' ? 'selected' : '' ?>>Cash In Hand</option>
                                <option value="cheque" <?= $curMethod === 'cheque' ? 'selected' : '' ?>>Cheque</option>
                                <option value="online" <?= $curMethod === 'online' ? 'selected' : '' ?>>Online / MFS</option>
                            </select>
                        </div>

                        <!-- Payment Type Filter -->
                        <div class="col-xl-2 col-lg-3 col-md-3 col-sm-6">
                            <label class="form-label extra-small fw-bold text-muted mb-1 text-uppercase" style="font-size: 11px;">Payment Type</label>
                            <select name="payment_type" class="form-select form-select-sm">
                                <option value="">All Payment Types</option>
                                <option value="booking_money" <?= $curType === 'booking_money' ? 'selected' : '' ?>>Booking Money</option>
                                <option value="registration" <?= $curType === 'registration' ? 'selected' : '' ?>>Registration Fee</option>
                                <option value="land_share" <?= $curType === 'land_share' ? 'selected' : '' ?>>Land Share</option>
                                <option value="emi" <?= $curType === 'emi' ? 'selected' : '' ?>>EMI / Construction</option>
                                <option value="development_charge" <?= $curType === 'development_charge' ? 'selected' : '' ?>>Development Charge</option>
                                <option value="shop_service_charge" <?= $curType === 'shop_service_charge' ? 'selected' : '' ?>>Shop Service Charge</option>
                                <option value="management" <?= $curType === 'management' ? 'selected' : '' ?>>Management Cash In</option>
                            </select>
                        </div>

                        <!-- Bank Name Filter -->
                        <div class="col-xl-2 col-lg-3 col-md-3 col-sm-6">
                            <label class="form-label extra-small fw-bold text-muted mb-1 text-uppercase" style="font-size: 11px;">Bank Account</label>
                            <select name="bank_name" class="form-select form-select-sm">
                                <option value="">All Banks</option>
                                <?php foreach ($bank_names as $bn): ?>
                                    <option value="<?= htmlspecialchars($bn) ?>" <?= $curBank === $bn ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($bn) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Verification Status -->
                        <div class="col-xl-2 col-lg-2 col-md-3 col-sm-6">
                            <label class="form-label extra-small fw-bold text-muted mb-1 text-uppercase" style="font-size: 11px;">Verification</label>
                            <select name="verification_status" class="form-select form-select-sm">
                                <option value="">All Statuses</option>
                                <option value="verified" <?= $curVer === 'verified' ? 'selected' : '' ?>>Verified</option>
                                <option value="pending" <?= $curVer === 'pending' ? 'selected' : '' ?>>Pending Verification</option>
                                <option value="rejected" <?= $curVer === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                            </select>
                        </div>

                        <!-- Search Input -->
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12">
                            <label class="form-label extra-small fw-bold text-muted mb-1 text-uppercase" style="font-size: 11px;">Search Keywords</label>
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search client, phone, flat, ref, cheque..." value="<?= htmlspecialchars($curSearch) ?>">
                        </div>

                        <!-- Buttons -->
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 d-flex gap-2">
                            <button type="submit" class="btn btn-sm btn-primary flex-fill fw-bold" style="height: 33px;">
                                <i class="fa-solid fa-filter me-1"></i> Filter
                            </button>
                            <a href="<?= $GLOBALS['base_url'] ?>/collection-statement" class="btn btn-sm btn-outline-secondary d-flex align-items-center justify-content-center" style="height: 33px;" title="Reset Filters">
                                <i class="fa-solid fa-rotate-left"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Active Filter Info Banner -->
    <div class="col-12 mb-3">
        <div class="p-2 px-3 bg-light rounded-3 border d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="small text-muted d-flex align-items-center gap-2 flex-wrap">
                <span><strong>Period:</strong> <span class="badge bg-secondary-subtle text-secondary border"><?= htmlspecialchars($date_info['label'] ?? '') ?></span></span>
                <span>•</span>
                <span><strong>Date Range:</strong> <?= date('d M, Y', strtotime($date_info['start_date'])) ?> to <?= date('d M, Y', strtotime($date_info['end_date'])) ?></span>
                <?php if (!empty($curProject)): ?>
                    <span>•</span>
                    <span><strong>Project Filter Active</strong></span>
                <?php endif; ?>
                <?php if (!empty($curMethod)): ?>
                    <span>•</span>
                    <span><strong>Method:</strong> <?= ucfirst($curMethod) ?></span>
                <?php endif; ?>
                <?php if (!empty($curType)): ?>
                    <span>•</span>
                    <span><strong>Type:</strong> <?= ucwords(str_replace('_', ' ', $curType)) ?></span>
                <?php endif; ?>
            </div>
            <div class="small fw-semibold text-primary">
                Found <?= number_format($summary['total_count'] ?? 0) ?> Collection Records
            </div>
        </div>
    </div>

    <!-- Summary Cards Row (5 Balanced Responsive Columns) -->
    <div class="col-12 mb-3">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-5 g-2 align-items-stretch" id="summaryCardsRow">
            <!-- 1. Total Collection -->
            <div class="col">
                <div class="card__wrapper card_wrapper collection-stat-card c-total h-100" onclick="toggleCardAmountFull(this)" 
                     data-sold-amount="<?= $summary['total_sold_value_formatted'] ?? '' ?>" 
                     data-due-amount="<?= $summary['pending_due_formatted'] ?? '' ?>" 
                     data-rate="<?= $summary['collection_rate'] ?? 0 ?>%"
                     title="Total Sold: <?= $summary['total_sold_value_formatted'] ?? '৳0' ?> | Collected: ৳<?= number_format($summary['total_amount'] ?? 0, 2) ?> | Due: <?= $summary['pending_due_formatted'] ?? '৳0' ?>" data-bs-toggle="tooltip" data-bs-placement="top">
                    <div class="d-flex align-items-center gap-2">
                        <div class="card-icon">
                            <i class="fa-solid fa-vault"></i>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="text-muted extra-small text-uppercase fw-bold" style="font-size: 10px;">Total Collection</div>
                                <?php if (!empty($summary['total_sold_value'])): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle py-0 px-1" style="font-size: 9px; font-weight: 700;" title="Realized Collection: <?= $summary['collection_rate'] ?>% of Sold Value"><?= $summary['collection_rate'] ?>% Realized</span>
                                <?php endif; ?>
                            </div>
                            <div class="c-amount text-primary text-truncate" data-full-amount="৳<?= number_format($summary['total_amount'] ?? 0, 2) ?>" title="Total Collected: ৳<?= number_format($summary['total_amount'] ?? 0, 2) ?> (<?= $summary['total_collection_formatted'] ?? '' ?>)">৳<?= number_format($summary['total_amount'] ?? 0, 2) ?></div>
                            <div class="d-flex align-items-center justify-content-between text-muted extra-small" style="font-size: 10px; line-height: 1.2;">
                                <span class="text-truncate"><?= number_format($summary['total_count'] ?? 0) ?> Trx</span>
                                <?php if (!empty($summary['total_sold_value'])): ?>
                                    <span class="text-truncate fw-semibold text-secondary ms-1" title="Total Sold Value: ৳<?= number_format($summary['total_sold_value'], 2) ?> (<?= number_format($summary['total_sold_units'] ?? 0) ?> units, <?= number_format($summary['total_sold_clients'] ?? 0) ?> clients)">
                                        Sold: <strong class="text-dark"><?= $summary['total_sold_value_formatted'] ?></strong>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if (empty($curVer) && !empty($summary['pending_amount'])): ?>
                                <div class="d-flex align-items-center justify-content-between border-top pt-1 mt-1 text-muted" style="font-size: 9.5px;" title="Verified by Accounts: <?= $summary['verified_amount_formatted'] ?? '' ?> | Pending Verification: <?= $summary['pending_amount_formatted'] ?? '' ?>">
                                    <span class="text-success fw-semibold" title="Confirmed / Verified Inflows"><i class="fa-solid fa-circle-check"></i> <?= $summary['verified_amount_formatted'] ?? '' ?> Ver</span>
                                    <span class="text-warning fw-semibold" title="Inflows Pending Accounts Verification"><i class="fa-solid fa-clock"></i> <?= $summary['pending_amount_formatted'] ?? '' ?> Pend</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Bank Collection -->
            <div class="col">
                <div class="card__wrapper card_wrapper collection-stat-card c-bank h-100" onclick="toggleCardAmountFull(this)" title="Click to view full amount: ৳<?= number_format($summary['bank_amount'] ?? 0, 2) ?>" data-bs-toggle="tooltip" data-bs-placement="top">
                    <div class="d-flex align-items-center gap-2">
                        <div class="card-icon">
                            <i class="fa-solid fa-building-columns"></i>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="text-muted extra-small text-uppercase fw-bold" style="font-size: 10px;">Bank Collection</div>
                            <div class="c-amount text-info text-truncate" data-full-amount="৳<?= number_format($summary['bank_amount'] ?? 0, 2) ?>" title="৳<?= number_format($summary['bank_amount'] ?? 0, 2) ?>">৳<?= number_format($summary['bank_amount'] ?? 0, 2) ?></div>
                            <small class="text-muted extra-small text-truncate d-block" style="font-size: 10px;"><?= number_format($summary['bank_count'] ?? 0) ?> Transfers / Deposits</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Cash In Hand -->
            <div class="col">
                <div class="card__wrapper card_wrapper collection-stat-card c-cash h-100" onclick="toggleCardAmountFull(this)" title="Click to view full amount: ৳<?= number_format($summary['cash_amount'] ?? 0, 2) ?>" data-bs-toggle="tooltip" data-bs-placement="top">
                    <div class="d-flex align-items-center gap-2">
                        <div class="card-icon">
                            <i class="fa-solid fa-money-bill-wave"></i>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="text-muted extra-small text-uppercase fw-bold" style="font-size: 10px;">Cash In Hand</div>
                            <div class="c-amount text-success text-truncate" data-full-amount="৳<?= number_format($summary['cash_amount'] ?? 0, 2) ?>" title="৳<?= number_format($summary['cash_amount'] ?? 0, 2) ?>">৳<?= number_format($summary['cash_amount'] ?? 0, 2) ?></div>
                            <small class="text-muted extra-small text-truncate d-block" style="font-size: 10px;"><?= number_format($summary['cash_count'] ?? 0) ?> Cash Collections</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Cheque Received -->
            <div class="col">
                <div class="card__wrapper card_wrapper collection-stat-card c-cheque h-100" onclick="toggleCardAmountFull(this)" title="Click to view full amount: ৳<?= number_format($summary['cheque_amount'] ?? 0, 2) ?>" data-bs-toggle="tooltip" data-bs-placement="top">
                    <div class="d-flex align-items-center gap-2">
                        <div class="card-icon">
                            <i class="fa-solid fa-money-check"></i>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="text-muted extra-small text-uppercase fw-bold" style="font-size: 10px;">Cheque Received</div>
                            <div class="c-amount text-warning text-truncate" data-full-amount="৳<?= number_format($summary['cheque_amount'] ?? 0, 2) ?>" title="৳<?= number_format($summary['cheque_amount'] ?? 0, 2) ?>">৳<?= number_format($summary['cheque_amount'] ?? 0, 2) ?></div>
                            <small class="text-muted extra-small text-truncate d-block" style="font-size: 10px;"><?= number_format($summary['cheque_count'] ?? 0) ?> Cheque Transactions</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. Online / MFS -->
            <div class="col">
                <div class="card__wrapper card_wrapper collection-stat-card c-online h-100" onclick="toggleCardAmountFull(this)" title="Click to view full amount: ৳<?= number_format($summary['online_amount'] ?? 0, 2) ?>" data-bs-toggle="tooltip" data-bs-placement="top">
                    <div class="d-flex align-items-center gap-2">
                        <div class="card-icon">
                            <i class="fa-solid fa-mobile-screen-button"></i>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="text-muted extra-small text-uppercase fw-bold" style="font-size: 10px;">Online / MFS</div>
                            <div class="c-amount text-danger text-truncate" data-full-amount="৳<?= number_format($summary['online_amount'] ?? 0, 2) ?>" title="৳<?= number_format($summary['online_amount'] ?? 0, 2) ?>">৳<?= number_format($summary['online_amount'] ?? 0, 2) ?></div>
                            <small class="text-muted extra-small text-truncate d-block" style="font-size: 10px;"><?= number_format($summary['online_count'] ?? 0) ?> Digital Payments</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Mode Switcher Bar -->
    <div class="col-12 mb-3">
        <div class="card__wrapper card_wrapper cs-view-switcher-bar p-2 px-3 rounded-3 shadow-sm border d-flex align-items-center justify-content-between flex-wrap gap-2 mb-0">
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small fw-bold text-uppercase d-none d-md-inline" style="font-size: 11px;">
                    <i class="fa-solid fa-sliders text-primary me-1"></i> Statement View:
                </span>
                <div class="btn-group btn-group-sm p-1 bg-light rounded-2 border" role="group">
                    <button type="button" class="btn btn-sm rounded-1 px-3 fw-bold mode-btn <?= $curViewMode === 'type_summary' ? 'btn-primary shadow-sm active' : 'btn-light text-secondary' ?>" id="btn-mode-type" onclick="switchStatementView('type_summary')">
                        <i class="fa-solid fa-chart-pie me-1"></i> Payment Types
                    </button>
                    <button type="button" class="btn btn-sm rounded-1 px-3 fw-bold mode-btn <?= $curViewMode === 'bank_summary' ? 'btn-primary shadow-sm active' : 'btn-light text-secondary' ?>" id="btn-mode-bank" onclick="switchStatementView('bank_summary')">
                        <i class="fa-solid fa-building-columns me-1"></i> Bank Accounts
                    </button>
                    <button type="button" class="btn btn-sm rounded-1 px-3 fw-bold mode-btn <?= $curViewMode === 'transactions' ? 'btn-primary shadow-sm active' : 'btn-light text-secondary' ?>" id="btn-mode-trx" onclick="switchStatementView('transactions')">
                        <i class="fa-solid fa-list-check me-1"></i> Detailed Register
                    </button>
                    <button type="button" class="btn btn-sm rounded-1 px-3 fw-bold mode-btn <?= ($curViewMode === 'both' || $curViewMode === 'all') ? 'btn-primary shadow-sm active' : 'btn-light text-secondary' ?>" id="btn-mode-both" onclick="switchStatementView('both')">
                        <i class="fa-solid fa-table-cells-large me-1"></i> Full Statement (All)
                    </button>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <a href="<?= $printTypeUrl ?>" target="_blank" class="btn btn-sm btn-light border text-dark d-flex align-items-center gap-1 shadow-xs" title="Print 1-Page Summary">
                    <i class="fa-solid fa-print text-primary"></i> <span class="d-none d-sm-inline">Quick Print</span>
                </a>
                <a href="<?= $exportTypeUrl ?>" class="btn btn-sm btn-light border text-dark d-flex align-items-center gap-1 shadow-xs" title="Export CSV Summary">
                    <i class="fa-solid fa-file-csv text-success"></i> <span class="d-none d-sm-inline">Export Types CSV</span>
                </a>
                <a href="<?= $exportBankUrl ?>" class="btn btn-sm btn-light border text-dark d-flex align-items-center gap-1 shadow-xs" title="Export Bank Sheet CSV">
                    <i class="fa-solid fa-file-excel text-info"></i> <span class="d-none d-sm-inline">Export Bank CSV</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Payment Type Wise Collection Statement Matrix -->
    <div class="col-12 mb-4" id="section-type-summary" style="<?= in_array($curViewMode, ['transactions', 'bank_summary'], true) ? 'display:none;' : '' ?>">
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-1 fw-bold text-dark d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white p-2 rounded-2"><i class="fa-solid fa-chart-pie"></i></span>
                        <span>Payment Type Wise Collection Statement</span>
                    </h5>
                    <div class="text-muted small">
                        Booking Money, Registration, Land Share, EMI Collection, Development Charge, Shop Service Charge &amp; Management Inflows
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="<?= $printTypeUrl ?>" target="_blank" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1 shadow-sm">
                        <i class="fa-solid fa-print"></i> <span>Print Head Statement</span>
                    </a>
                    <a href="<?= $exportTypeUrl ?>" class="btn btn-sm btn-outline-success d-flex align-items-center gap-1 shadow-sm">
                        <i class="fa-solid fa-file-excel"></i> <span>Export Matrix CSV</span>
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle table-collection mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 45px;">#</th>
                            <th style="min-width: 220px;">Payment Type / Purpose</th>
                            <th class="text-center" style="width: 90px;">Trx Count</th>
                            <th class="text-end" style="min-width: 130px;">Bank Inflow</th>
                            <th class="text-end" style="min-width: 130px;">Cash In Hand</th>
                            <th class="text-end" style="min-width: 120px;">Cheque</th>
                            <th class="text-end" style="min-width: 120px;">Online / MFS</th>
                            <th class="text-end fw-bold" style="min-width: 150px;">Total Collection</th>
                            <th style="min-width: 140px;">% Share</th>
                            <th class="text-center" style="width: 90px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $typeRows = $typeSummary['rows'] ?? [];
                        $grand = $typeSummary['grand'] ?? ['count' => 0, 'total_amount' => 0, 'bank_amount' => 0, 'cash_amount' => 0, 'cheque_amount' => 0, 'online_amount' => 0];
                        $tSl = 1;
                        foreach ($typeRows as $tKey => $r):
                            $hasData = ($r['total_amount'] > 0 || $r['count'] > 0);
                            $pct = (float)($r['percentage'] ?? 0);
                            
                            // Icons according to payment type
                            $typeIcon = 'fa-receipt';
                            if ($tKey === 'booking_money')          $typeIcon = 'fa-file-signature text-primary';
                            elseif ($tKey === 'registration')       $typeIcon = 'fa-stamp text-info';
                            elseif ($tKey === 'land_share')         $typeIcon = 'fa-map-location-dot text-success';
                            elseif ($tKey === 'emi')                $typeIcon = 'fa-calendar-check text-warning';
                            elseif ($tKey === 'development_charge') $typeIcon = 'fa-screwdriver-wrench text-secondary';
                            elseif ($tKey === 'shop_service_charge') $typeIcon = 'fa-store text-purple';
                            elseif ($tKey === 'management')         $typeIcon = 'fa-building-shield text-dark';

                            // Filter link for this payment type
                            $filterTypeUrl = '?' . http_build_query(array_merge($queryParams, ['payment_type' => $tKey, 'view_mode' => 'transactions']));
                        ?>
                        <tr class="<?= $hasData ? '' : 'opacity-50' ?>">
                            <td class="text-center text-muted fw-bold"><?= $tSl++ ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-2 p-1 px-2 border bg-light">
                                        <i class="fa-solid <?= $typeIcon ?>"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($r['name']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border px-2 py-1 font-monospace">
                                    <?= number_format($r['count']) ?>
                                </span>
                            </td>
                            <td class="text-end font-monospace text-primary">
                                <?= $r['bank_amount'] > 0 ? '৳' . number_format($r['bank_amount'], 2) : '<span class="text-muted">-</span>' ?>
                            </td>
                            <td class="text-end font-monospace text-success">
                                <?= $r['cash_amount'] > 0 ? '৳' . number_format($r['cash_amount'], 2) : '<span class="text-muted">-</span>' ?>
                            </td>
                            <td class="text-end font-monospace text-warning">
                                <?= $r['cheque_amount'] > 0 ? '৳' . number_format($r['cheque_amount'], 2) : '<span class="text-muted">-</span>' ?>
                            </td>
                            <td class="text-end font-monospace text-danger">
                                <?= $r['online_amount'] > 0 ? '৳' . number_format($r['online_amount'], 2) : '<span class="text-muted">-</span>' ?>
                            </td>
                            <td class="text-end font-monospace fw-bold fs-13 text-dark">
                                ৳<?= number_format($r['total_amount'], 2) ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 6px;">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?= min(100, $pct) ?>%"></div>
                                    </div>
                                    <span class="small font-monospace text-muted" style="width: 45px; font-size: 11px;"><?= number_format($pct, 1) ?>%</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <?php if ($r['count'] > 0): ?>
                                    <a href="<?= $filterTypeUrl ?>" class="btn btn-xs btn-outline-primary py-1 px-2 rounded-2" style="font-size: 11px;" title="View individual records in detailed list">
                                        <i class="fa-solid fa-filter me-1"></i> Filter
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted extra-small">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold bg-light" style="border-top: 2px solid #cbd5e1; font-size: 13.5px;">
                            <td colspan="2" class="text-uppercase text-dark">
                                <i class="fa-solid fa-calculator text-primary me-1"></i> GRAND TOTAL COLLECTION:
                            </td>
                            <td class="text-center font-monospace">
                                <span class="badge bg-dark text-white px-2 py-1"><?= number_format($grand['count']) ?></span>
                            </td>
                            <td class="text-end font-monospace text-primary">৳<?= number_format($grand['bank_amount'], 2) ?></td>
                            <td class="text-end font-monospace text-success">৳<?= number_format($grand['cash_amount'], 2) ?></td>
                            <td class="text-end font-monospace text-warning">৳<?= number_format($grand['cheque_amount'], 2) ?></td>
                            <td class="text-end font-monospace text-danger">৳<?= number_format($grand['online_amount'], 2) ?></td>
                            <td class="text-end font-monospace text-dark fs-14 bg-warning-subtle">
                                ৳<?= number_format($grand['total_amount'], 2) ?>
                            </td>
                            <td>
                                <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold">100.0%</span>
                            </td>
                            <td class="text-center">
                                <a href="<?= $printTypeUrl ?>" target="_blank" class="btn btn-xs btn-primary py-1 px-2" style="font-size: 11px;">
                                    <i class="fa-solid fa-print"></i> Print
                                </a>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Bank Account Wise Inflow Breakdown Sheet -->
    <div class="col-12 mb-4" id="section-bank-summary" style="<?= in_array($curViewMode, ['type_summary', 'transactions'], true) ? 'display:none;' : '' ?>">
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-1 fw-bold text-dark d-flex align-items-center gap-2">
                        <span class="badge bg-info text-white p-2 rounded-2"><i class="fa-solid fa-building-columns"></i></span>
                        <span>Bank Account Wise Inflow Breakdown</span>
                    </h5>
                    <div class="text-muted small">
                        Detailed deposit statement across company bank accounts &amp; financial institutions
                    </div>
                </div>
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <div class="input-group input-group-sm" style="width: 220px;">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" id="bankTableSearch" onkeyup="filterBankSheetTable()" class="form-control border-start-0" placeholder="Filter bank accounts...">
                    </div>
                    <a href="<?= $printBankUrl ?>" target="_blank" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1 shadow-sm">
                        <i class="fa-solid fa-print"></i> <span>Print Bank Sheet</span>
                    </a>
                    <a href="<?= $exportBankUrl ?>" class="btn btn-sm btn-outline-success d-flex align-items-center gap-1 shadow-sm">
                        <i class="fa-solid fa-file-excel"></i> <span>Export Bank CSV</span>
                    </a>
                </div>
            </div>

            <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
                <table class="table table-hover align-middle table-collection mb-0" id="bankSheetTable">
                    <thead class="table-light sticky-top" style="z-index: 2;">
                        <tr>
                            <th class="text-center" style="width: 50px;">#</th>
                            <th style="min-width: 280px;">Bank Account / Deposited Account</th>
                            <th class="text-center" style="width: 130px;">Deposit Trx Count</th>
                            <th class="text-end" style="min-width: 180px;">Total Inflow Amount (BDT)</th>
                            <th style="min-width: 200px;">% Share of Bank Inflow</th>
                            <th class="text-center" style="width: 100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $bankList = $summary['by_bank'] ?? [];
                        $totalBankAmt = (float)($summary['bank_amount'] ?? 0);
                        $totalBankTrx = 0;
                        if (!empty($bankList)) {
                            foreach ($bankList as $b) {
                                $totalBankTrx += (int)($b['count'] ?? 0);
                            }
                        }
                        if (empty($bankList)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-building-columns fa-2x mb-2 text-secondary opacity-50"></i>
                                    <div>No bank deposit transactions found for the selected period.</div>
                                </td>
                            </tr>
                        <?php else:
                            $bSl = 1;
                            foreach ($bankList as $bName => $bData):
                                $bAmt = (float)($bData['amount'] ?? 0);
                                $bCount = (int)($bData['count'] ?? 0);
                                $bPct = ($totalBankAmt > 0) ? round(($bAmt / $totalBankAmt) * 100, 2) : 0;
                                $filterBankUrl = '?' . http_build_query(array_merge($queryParams, [
                                    'bank_name' => ($bName === 'Cash / Unassigned' ? '' : $bName),
                                    'view_mode' => 'transactions'
                                ]));
                                $isUnassigned = ($bName === 'Cash / Unassigned' || $bName === 'N/A' || empty($bName));
                        ?>
                            <tr class="bank-row">
                                <td class="text-center text-muted fw-bold"><?= $bSl++ ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-2 p-1 px-2 border <?= $isUnassigned ? 'bg-light text-muted' : 'bg-primary-subtle text-primary' ?>">
                                            <i class="fa-solid <?= $isUnassigned ? 'fa-wallet' : 'fa-building-columns' ?>"></i>
                                        </div>
                                        <div>
                                            <span class="fw-bold bank-name-cell <?= $isUnassigned ? 'text-muted' : 'text-dark' ?>">
                                                <?= htmlspecialchars($bName) ?>
                                            </span>
                                            <?php if ($isUnassigned): ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle extra-small ms-1">Unassigned</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border px-2 py-1 font-monospace">
                                        <?= number_format($bCount) ?>
                                    </span>
                                </td>
                                <td class="text-end font-monospace fw-bold text-dark fs-13">
                                    ৳<?= number_format($bAmt, 2) ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 6px;">
                                            <div class="progress-bar bg-info" role="progressbar" style="width: <?= min(100, $bPct) ?>%"></div>
                                        </div>
                                        <span class="small font-monospace text-muted" style="width: 48px; font-size: 11px;"><?= number_format($bPct, 1) ?>%</span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php if ($bCount > 0): ?>
                                        <a href="<?= $filterBankUrl ?>" class="btn btn-xs btn-outline-primary py-1 px-2 rounded-2" style="font-size: 11px;" title="Filter detailed register for this bank">
                                            <i class="fa-solid fa-filter me-1"></i> Filter
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted extra-small">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                    <tfoot class="table-light sticky-bottom" style="z-index: 2;">
                        <tr class="fw-bold bg-light" style="border-top: 2px solid #cbd5e1; font-size: 13.5px;">
                            <td class="text-center text-muted"><?= count($bankList) ?></td>
                            <td class="text-uppercase text-dark">
                                <i class="fa-solid fa-calculator text-primary me-1"></i> TOTAL BANK INFLOW:
                            </td>
                            <td class="text-center font-monospace">
                                <span class="badge bg-dark text-white px-2 py-1"><?= number_format($totalBankTrx) ?></span>
                            </td>
                            <td class="text-end font-monospace text-primary fs-14 bg-info-subtle">
                                ৳<?= number_format($totalBankAmt, 2) ?>
                            </td>
                            <td>
                                <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold">100.0%</span>
                            </td>
                            <td class="text-center">
                                <a href="<?= $printBankUrl ?>" target="_blank" class="btn btn-xs btn-primary py-1 px-2" style="font-size: 11px;">
                                    <i class="fa-solid fa-print"></i> Print
                                </a>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="card-footer bg-white border-top py-2 px-3 d-flex justify-content-between align-items-center text-muted small">
                <span>Showing <strong><?= count($bankList) ?></strong> bank accounts &amp; deposit channels</span>
                <span>Total Recorded Inflow: <strong class="text-dark">৳<?= number_format($totalBankAmt, 2) ?></strong></span>
            </div>
        </div>
    </div>

    <!-- Detailed Collection Records Register -->
    <div class="col-12 mb-4" id="section-transactions" style="<?= in_array($curViewMode, ['type_summary', 'bank_summary'], true) ? 'display:none;' : '' ?>">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0 fw-bold text-dark">
                    <i class="fa-solid fa-table-list text-primary me-2"></i>Collection Records
                </h5>
                <div class="text-muted small">
                    Showing <?= count($transactions) ?> of <?= number_format($pagination['total_records']) ?> records
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-collection mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 45px;">#</th>
                            <th>Payment Date</th>
                            <th>Client / Depositor</th>
                            <th>Project &amp; Unit</th>
                            <th>Payment Type</th>
                            <th>Payment Method</th>
                            <th>Bank / Cheque Details</th>
                            <th>Ref / Voucher</th>
                            <th class="text-end">Amount (BDT)</th>
                            <th style="min-width: 140px;">Added By</th>
                            <th style="min-width: 175px;">Verification &amp; Verifier</th>
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
                        <?php else: ?>
                            <?php 
                            $sl = (($pagination['current_page'] - 1) * $pagination['limit']) + 1;
                            $pageTotal = 0;
                            foreach ($transactions as $t): 
                                $amt = (float)($t['amount'] ?? 0);
                                $pageTotal += $amt;
                                $method = strtolower($t['payment_method'] ?? 'cash');
                                $mBadgeClass = 'badge-subtle-cash';
                                $mIcon = 'fa-money-bill-wave';
                                if ($method === 'bank') {
                                    $mBadgeClass = 'badge-subtle-bank';
                                    $mIcon = 'fa-building-columns';
                                } elseif ($method === 'cheque') {
                                    $mBadgeClass = 'badge-subtle-cheque';
                                    $mIcon = 'fa-money-check';
                                } elseif ($method === 'online') {
                                    $mBadgeClass = 'badge-subtle-online';
                                    $mIcon = 'fa-mobile-screen-button';
                                }

                                $vStatus = $t['verification_status'] ?? 'pending';
                                $vClass = 'bg-warning-subtle text-warning border border-warning';
                                if ($vStatus === 'verified') {
                                    $vClass = 'bg-success-subtle text-success border border-success';
                                } elseif ($vStatus === 'rejected') {
                                    $vClass = 'bg-danger-subtle text-danger border border-danger';
                                }
                            ?>
                                <tr>
                                    <td class="text-center text-muted fw-bold"><?= $sl++ ?></td>
                                    <td class="text-nowrap fw-semibold">
                                        <i class="fa-regular fa-calendar text-primary me-1" style="font-size: 11px;"></i>
                                        <?= date('d M, Y', strtotime($t['date'])) ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($t['client_name'] ?? 'N/A') ?></div>
                                        <?php 
                                        $csCleanPhone = preg_replace('/^p:/i', '', trim($t['client_phone'] ?? ''));
                                        if (!empty($csCleanPhone)): 
                                        ?>
                                            <div class="text-muted extra-small"><i class="fa-solid fa-phone me-1" style="font-size: 10px;"></i><?= htmlspecialchars($csCleanPhone) ?></div>
                                        <?php endif; ?>
                                        <?php if ($t['source'] === 'management'): ?>
                                            <span class="badge bg-purple-subtle text-purple border px-1" style="font-size: 9.5px;">Management</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark text-nowrap"><?= htmlspecialchars($t['project_name'] ?? 'N/A') ?></div>
                                        <?php if (!empty($t['flat_name'])): ?>
                                            <div class="badge bg-light text-secondary border px-1" style="font-size: 10px;">
                                                <?= htmlspecialchars($t['flat_name']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                            $pInfo = $t['purpose_info'] ?? null;
                                            $pLabel = $pInfo['label'] ?? ucwords(str_replace('_', ' ', $t['payment_type'] ?? ''));
                                            $pClass = $pInfo['badge_class'] ?? 'bg-light text-dark border';
                                            $pIcon  = $pInfo['icon'] ?? 'fa-solid fa-tag';
                                        ?>
                                        <span class="badge <?= $pClass ?> px-2 py-1" style="font-size: 11px; font-weight: 600;" title="<?= htmlspecialchars($pInfo['full_label'] ?? $pLabel) ?>">
                                            <i class="<?= $pIcon ?> me-1" style="font-size: 9.5px;"></i><?= htmlspecialchars($pLabel) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $mBadgeClass ?> px-2 py-1 text-uppercase fw-bold" style="font-size: 11px;">
                                            <i class="fa-solid <?= $mIcon ?> me-1"></i><?= $method ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($t['bank_name'])): ?>
                                            <div class="fw-semibold text-truncate" style="max-width: 190px;" title="<?= htmlspecialchars($t['bank_name']) ?>">
                                                <?= htmlspecialchars($t['bank_name']) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($t['cheque_no'])): ?>
                                            <div class="text-muted extra-small">
                                                <strong>Chq:</strong> <?= htmlspecialchars($t['cheque_no']) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (empty($t['bank_name']) && empty($t['cheque_no'])): ?>
                                            <span class="text-muted extra-small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($t['transaction_ref'])): ?>
                                            <code style="font-size: 11px; background: #f8fafc; padding: 2px 6px; border-radius: 4px; border: 1px solid #e2e8f0;">
                                                <?= htmlspecialchars($t['transaction_ref']) ?>
                                            </code>
                                        <?php else: ?>
                                            <span class="text-muted extra-small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end text-nowrap fw-bold text-success fs-13">
                                        ৳<?= number_format($amt, 2) ?>
                                    </td>
                                    <td class="text-start">
                                        <div class="fw-semibold text-dark extra-small d-flex align-items-center gap-1" title="Added by: <?= htmlspecialchars($t['collector_name'] ?? 'System') ?>">
                                            <i class="fa-solid fa-user-pen text-primary" style="font-size: 10px;"></i>
                                            <span class="text-truncate" style="max-width: 135px;"><?= htmlspecialchars($t['collector_name'] ?? 'System') ?></span>
                                        </div>
                                    </td>
                                    <td class="text-start" style="min-width: 175px;">
                                        <?php if ($vStatus === 'verified'): ?>
                                            <div class="d-flex align-items-center gap-1 mb-1">
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5" style="font-size: 10px; font-weight: 700;">
                                                    <i class="fa-solid fa-circle-check me-1"></i>Verified
                                                </span>
                                                <?php if (!empty($t['verified_at'])): ?>
                                                    <span class="text-muted extra-small" style="font-size: 10px; white-space: nowrap;">
                                                        <?= date('d M, h:i A', strtotime($t['verified_at'])) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($t['verifier_name'])): ?>
                                                <div style="line-height: 1.25;">
                                                    <div class="fw-bold text-dark extra-small d-flex align-items-center gap-1" title="Verified by: <?= htmlspecialchars($t['verifier_name']) ?>">
                                                        <i class="fa-solid fa-user-check text-success" style="font-size: 10.5px;"></i>
                                                        <span class="text-truncate" style="max-width: 155px;"><?= htmlspecialchars($t['verifier_name']) ?></span>
                                                    </div>
                                                    <?php if (!empty($t['verifier_branch_name']) || !empty($t['verifier_branch_id'])): ?>
                                                        <div class="text-muted extra-small d-flex align-items-center gap-1 mt-0.5" style="font-size: 10px;">
                                                            <i class="fa-solid fa-building-user text-primary" style="font-size: 9.5px;"></i>
                                                            <span class="badge bg-light text-secondary border px-1" style="font-size: 9.5px; font-weight: 600;">
                                                                <?= htmlspecialchars($t['verifier_branch_name'] ?: 'Branch') ?>
                                                            </span>
                                                            <?php if (!empty($t['verifier_branch_id'])): ?>
                                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-1" style="font-size: 9px; font-weight: 600;">
                                                                    ID: <?= (int)$t['verifier_branch_id'] ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted extra-small">Auto-Verified</span>
                                            <?php endif; ?>
                                        <?php elseif ($vStatus === 'rejected'): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 text-capitalize" style="font-size: 10px; font-weight: 700;">
                                                <i class="fa-solid fa-circle-xmark me-1"></i>Rejected
                                            </span>
                                            <?php if (!empty($t['verifier_name'])): ?>
                                                <div class="extra-small text-muted mt-1 text-truncate" style="max-width: 140px;">
                                                    By: <?= htmlspecialchars($t['verifier_name']) ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 text-capitalize" style="font-size: 10px; font-weight: 700;">
                                                <i class="fa-solid fa-clock me-1"></i>Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($t['source'] === 'client' && !empty($t['id'])): ?>
                                            <a href="<?= $GLOBALS['base_url'] ?>/payment-invoice/<?= (int)$t['id'] ?>" target="_blank" class="btn btn-xs btn-outline-primary p-1 px-2 rounded-2" title="View / Print Receipt">
                                                <i class="fa-solid fa-receipt"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($transactions)): ?>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td colspan="8" class="text-end">PAGE SUBTOTAL:</td>
                            <td class="text-end font-monospace text-success fs-13">৳<?= number_format($pageTotal, 2) ?></td>
                            <td colspan="3"></td>
                        </tr>
                        <tr class="fw-bold bg-primary-subtle text-primary">
                            <td colspan="8" class="text-end">OVERALL FILTERED TOTAL COLLECTION:</td>
                            <td class="text-end font-monospace text-primary fs-14">৳<?= number_format($summary['total_amount'] ?? 0, 2) ?></td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>

            <!-- Pagination Bar -->
            <?php if ($pagination['total_pages'] > 1): ?>
            <div class="card-footer bg-white py-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="small text-muted">
                    Page <strong><?= $pagination['current_page'] ?></strong> of <strong><?= $pagination['total_pages'] ?></strong>
                    (Total: <?= number_format($pagination['total_records']) ?> records)
                </div>
                <ul class="pagination pagination-sm mb-0">
                    <?php
                    $curP = $pagination['current_page'];
                    $totP = $pagination['total_pages'];
                    $pRange = 3;

                    // First & Prev
                    if ($curP > 1):
                        $qFirst = array_merge($_GET, ['page' => 1]);
                        $qPrev = array_merge($_GET, ['page' => $curP - 1]);
                    ?>
                        <li class="page-item"><a class="page-link" href="?<?= http_build_query($qFirst) ?>">&laquo; First</a></li>
                        <li class="page-item"><a class="page-link" href="?<?= http_build_query($qPrev) ?>">&lsaquo; Prev</a></li>
                    <?php else: ?>
                        <li class="page-item disabled"><span class="page-link">&laquo; First</span></li>
                        <li class="page-item disabled"><span class="page-link">&lsaquo; Prev</span></li>
                    <?php endif; ?>

                    <?php
                    $startP = max(1, $curP - $pRange);
                    $endP = min($totP, $curP + $pRange);
                    for ($p = $startP; $p <= $endP; $p++):
                        $qPage = array_merge($_GET, ['page' => $p]);
                    ?>
                        <li class="page-item <?= ($p == $curP) ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query($qPage) ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>

                    <!-- Next & Last -->
                    <?php if ($curP < $totP): 
                        $qNext = array_merge($_GET, ['page' => $curP + 1]);
                        $qLast = array_merge($_GET, ['page' => $totP]);
                    ?>
                        <li class="page-item"><a class="page-link" href="?<?= http_build_query($qNext) ?>">Next &rsaquo;</a></li>
                        <li class="page-item"><a class="page-link" href="?<?= http_build_query($qLast) ?>">Last &raquo;</a></li>
                    <?php else: ?>
                        <li class="page-item disabled"><span class="page-link">Next &rsaquo;</span></li>
                        <li class="page-item disabled"><span class="page-link">Last &raquo;</span></li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function switchStatementView(mode) {
    const secType = document.getElementById('section-type-summary');
    const secBank = document.getElementById('section-bank-summary');
    const secTrx  = document.getElementById('section-transactions');
    const btnType = document.getElementById('btn-mode-type');
    const btnBank = document.getElementById('btn-mode-bank');
    const btnTrx  = document.getElementById('btn-mode-trx');
    const btnBoth = document.getElementById('btn-mode-both');

    const inactiveClass = 'btn btn-sm rounded-1 px-3 fw-bold mode-btn btn-light text-secondary';
    const activeClass   = 'btn btn-sm rounded-1 px-3 fw-bold mode-btn btn-primary shadow-sm active';

    if (btnType) btnType.className = inactiveClass;
    if (btnBank) btnBank.className = inactiveClass;
    if (btnTrx)  btnTrx.className  = inactiveClass;
    if (btnBoth) btnBoth.className = inactiveClass;

    if (mode === 'type_summary') {
        if (secType) secType.style.display = '';
        if (secBank) secBank.style.display = 'none';
        if (secTrx)  secTrx.style.display  = 'none';
        if (btnType) btnType.className = activeClass;
    } else if (mode === 'bank_summary') {
        if (secType) secType.style.display = 'none';
        if (secBank) secBank.style.display = '';
        if (secTrx)  secTrx.style.display  = 'none';
        if (btnBank) btnBank.className = activeClass;
    } else if (mode === 'transactions') {
        if (secType) secType.style.display = 'none';
        if (secBank) secBank.style.display = 'none';
        if (secTrx)  secTrx.style.display  = '';
        if (btnTrx)  btnTrx.className  = activeClass;
    } else { // both / all
        if (secType) secType.style.display = '';
        if (secBank) secBank.style.display = '';
        if (secTrx)  secTrx.style.display  = '';
        if (btnBoth) btnBoth.className = activeClass;
    }

    // Update query string in URL without page refresh
    try {
        const url = new URL(window.location.href);
        url.searchParams.set('view_mode', mode);
        window.history.replaceState({}, '', url.toString());
    } catch (e) {
        console.error(e);
    }
}

function filterBankSheetTable() {
    const q = (document.getElementById('bankTableSearch')?.value || '').toLowerCase().trim();
    const rows = document.querySelectorAll('#bankSheetTable tbody tr.bank-row');
    rows.forEach(r => {
        const nameCell = r.querySelector('.bank-name-cell');
        const txt = nameCell ? nameCell.textContent.toLowerCase() : '';
        r.style.display = (q === '' || txt.includes(q)) ? '' : 'none';
    });
}

function togglePeriodFields() {
    const pt = document.getElementById('periodTypeSelect').value;
    const monthlyFields = document.querySelectorAll('.period-monthly-field');
    const customFields = document.querySelectorAll('.period-custom-field');

    if (pt === 'monthly' || pt === 'yearly') {
        monthlyFields.forEach(el => el.style.display = '');
        customFields.forEach(el => el.style.display = 'none');
    } else if (pt === 'custom') {
        monthlyFields.forEach(el => el.style.display = 'none');
        customFields.forEach(el => el.style.display = '');
    } else {
        // today, weekly, all_time
        monthlyFields.forEach(el => el.style.display = 'none');
        customFields.forEach(el => el.style.display = 'none');
    }
}

function toggleCardAmountFull(card) {
    if (!card) return;
    const amountEl = card.querySelector('.c-amount') || card;
    if (!amountEl) return;

    // Toggle expanded state
    const isExpanded = amountEl.classList.toggle('expanded-amount');
    if (isExpanded) {
        amountEl.classList.remove('text-truncate');
    } else {
        amountEl.classList.add('text-truncate');
    }

    const fullVal = amountEl.getAttribute('data-full-amount') || amountEl.getAttribute('title');
    const label = card.querySelector('.text-muted.extra-small')?.textContent || 'Collection Amount';

    if (typeof Swal !== 'undefined' && fullVal) {
        const soldVal = card.getAttribute('data-sold-amount');
        const dueVal = card.getAttribute('data-due-amount');
        const rateVal = card.getAttribute('data-rate');
        let extraHtml = '';
        if (soldVal) {
            extraHtml = `
            <div class="mt-3 pt-2 border-top text-start small">
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Total Sold Value (Agreement):</span>
                    <strong class="text-dark">${soldVal}</strong>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Actual Realized Collection:</span>
                    <strong class="text-success">${fullVal} (${rateVal} Realized)</strong>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">Uncollected Due Amount:</span>
                    <strong class="text-danger">${dueVal}</strong>
                </div>
            </div>`;
        }
        Swal.fire({
            title: label,
            html: `<div class="fs-2 fw-bold text-success my-2">${fullVal}</div><div class="text-muted small">Total Filtered Collection Inflow</div>${extraHtml}`,
            icon: 'info',
            confirmButtonText: 'OK',
            confirmButtonColor: '#006837',
            timer: 6000,
            timerProgressBar: true
        });
    } else if (typeof toastr !== 'undefined' && fullVal) {
        toastr.info(`<strong>${fullVal}</strong>`, label, { timeOut: 3500 });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    togglePeriodFields();
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});
</script>


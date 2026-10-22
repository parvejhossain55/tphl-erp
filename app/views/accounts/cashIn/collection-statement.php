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
$printFullUrl = $base_url . '/collection-statement-print?' . http_build_query(array_merge($queryParams, ['print_mode' => 'full']));
$printTrxUrl  = $base_url . '/collection-statement-print?' . http_build_query(array_merge($queryParams, ['print_mode' => 'transactions']));

$exportTypeUrl = $base_url . '/collection-statement-export?' . http_build_query(array_merge($queryParams, ['export_mode' => 'type_summary']));
$exportBankUrl = $base_url . '/collection-statement-export?' . http_build_query(array_merge($queryParams, ['export_mode' => 'bank_summary']));
$exportTrxUrl  = $base_url . '/collection-statement-export?' . http_build_query(array_merge($queryParams, ['export_mode' => 'detailed']));
?>

<style>
    .header-action-btns {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .header-action-btns .btn,
    .header-action-btns .dropdown {
        display: inline-block !important;
        position: relative !important;
        white-space: nowrap !important;
    }
    .header-action-btns .btn {
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        height: 33px !important;
        line-height: 1 !important;
    }

    .collection-stat-card {
        border-radius: 10px;
        padding: 16px 18px;
        background: #ffffff;
        box-shadow: 0 1px 4px rgba(0,0,0,0.05);
        border: 1px solid #e2e8f0;
        border-left: 4px solid #3b82f6;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        height: 100%;
    }
    .collection-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .collection-stat-card.c-total  { border-left-color: #6366f1; }
    .collection-stat-card.c-bank   { border-left-color: #0284c7; }
    .collection-stat-card.c-cash   { border-left-color: #10b981; }
    .collection-stat-card.c-cheque { border-left-color: #f59e0b; }
    .collection-stat-card.c-online { border-left-color: #ec4899; }

    .collection-stat-card .card-icon {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 17px;
        flex-shrink: 0;
    }
    .c-total .card-icon  { background: rgba(99,102,241,0.12); color: #6366f1; }
    .c-bank .card-icon   { background: rgba(2,132,199,0.12);  color: #0284c7; }
    .c-cash .card-icon   { background: rgba(16,185,129,0.12); color: #10b981; }
    .c-cheque .card-icon { background: rgba(245,158,11,0.12); color: #f59e0b; }
    .c-online .card-icon { background: rgba(236,72,153,0.12); color: #ec4899; }

    .c-amount {
        font-size: 1.25rem;
        font-weight: 700;
        line-height: 1.2;
    }
    .badge-subtle-bank   { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
    .badge-subtle-cash   { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .badge-subtle-cheque { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .badge-subtle-online { background: #fce7f3; color: #be185d; border: 1px solid #fbcfe8; }

    .table-collection th {
        background-color: #f8fafc;
        color: #334155;
        font-weight: 700;
        font-size: 12.5px;
        border-bottom: 2px solid #cbd5e1;
        padding: 10px 12px;
        white-space: nowrap;
    }
    .table-collection td {
        padding: 9px 12px;
        font-size: 13px;
        vertical-align: middle;
    }
</style>

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
                    <div class="header-action-btns ms-md-auto">
                        <!-- Print Dropdown -->
                        <div class="dropdown">
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
                        <div class="dropdown">
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

    <!-- Summary Cards Row -->
    <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-12 mb-3">
        <div class="collection-stat-card c-total">
            <div class="d-flex align-items-center gap-3">
                <div class="card-icon">
                    <i class="fa-solid fa-vault"></i>
                </div>
                <div class="flex-grow-1 overflow-hidden">
                    <div class="text-muted extra-small text-uppercase fw-bold" style="font-size: 10px;">Total Collection</div>
                    <div class="c-amount text-primary text-truncate">৳<?= number_format($summary['total_amount'] ?? 0, 2) ?></div>
                    <small class="text-muted extra-small"><?= number_format($summary['total_count'] ?? 0) ?> Transactions (Client + Mgmt)</small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-12 mb-3">
        <div class="collection-stat-card c-bank">
            <div class="d-flex align-items-center gap-3">
                <div class="card-icon">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
                <div class="flex-grow-1 overflow-hidden">
                    <div class="text-muted extra-small text-uppercase fw-bold" style="font-size: 10px;">Bank Collection</div>
                    <div class="c-amount text-info text-truncate">৳<?= number_format($summary['bank_amount'] ?? 0, 2) ?></div>
                    <small class="text-muted extra-small"><?= number_format($summary['bank_count'] ?? 0) ?> Transfers / Deposits</small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-2 col-xl-4 col-lg-4 col-md-4 col-6 mb-3">
        <div class="collection-stat-card c-cash">
            <div class="d-flex align-items-center gap-2">
                <div class="card-icon">
                    <i class="fa-solid fa-money-bill-wave"></i>
                </div>
                <div class="flex-grow-1 overflow-hidden">
                    <div class="text-muted extra-small text-uppercase fw-bold" style="font-size: 10px;">Cash In Hand</div>
                    <div class="c-amount text-success text-truncate">৳<?= number_format($summary['cash_amount'] ?? 0, 2) ?></div>
                    <small class="text-muted extra-small"><?= number_format($summary['cash_count'] ?? 0) ?> Cash Collections</small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-2 col-xl-4 col-lg-4 col-md-4 col-6 mb-3">
        <div class="collection-stat-card c-cheque">
            <div class="d-flex align-items-center gap-2">
                <div class="card-icon">
                    <i class="fa-solid fa-money-check"></i>
                </div>
                <div class="flex-grow-1 overflow-hidden">
                    <div class="text-muted extra-small text-uppercase fw-bold" style="font-size: 10px;">Cheque Received</div>
                    <div class="c-amount text-warning text-truncate">৳<?= number_format($summary['cheque_amount'] ?? 0, 2) ?></div>
                    <small class="text-muted extra-small"><?= number_format($summary['cheque_count'] ?? 0) ?> Cheque Transactions</small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-2 col-xl-4 col-lg-4 col-md-4 col-12 mb-3">
        <div class="collection-stat-card c-online">
            <div class="d-flex align-items-center gap-2">
                <div class="card-icon">
                    <i class="fa-solid fa-mobile-screen-button"></i>
                </div>
                <div class="flex-grow-1 overflow-hidden">
                    <div class="text-muted extra-small text-uppercase fw-bold" style="font-size: 10px;">Online / MFS</div>
                    <div class="c-amount text-danger text-truncate">৳<?= number_format($summary['online_amount'] ?? 0, 2) ?></div>
                    <small class="text-muted extra-small"><?= number_format($summary['online_count'] ?? 0) ?> Digital Payments</small>
                </div>
            </div>
        </div>
    </div>

    <!-- View Mode Switcher Bar -->
    <div class="col-12 mb-3">
        <div class="bg-white p-2 px-3 rounded-3 shadow-sm border d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small fw-bold text-uppercase d-none d-md-inline" style="font-size: 11px;">
                    <i class="fa-solid fa-sliders text-primary me-1"></i> Statement View:
                </span>
                <div class="btn-group btn-group-sm p-1 bg-light rounded-2 border" role="group">
                    <button type="button" class="btn btn-sm rounded-1 px-3 fw-bold mode-btn <?= $curViewMode === 'type_summary' ? 'btn-primary shadow-sm active' : 'btn-light text-secondary' ?>" id="btn-mode-type" onclick="switchStatementView('type_summary')">
                        <i class="fa-solid fa-chart-pie me-1"></i> Payment Type Statement (হেড-ভিত্তিক স্টেটমেন্ট)
                    </button>
                    <button type="button" class="btn btn-sm rounded-1 px-3 fw-bold mode-btn <?= $curViewMode === 'transactions' ? 'btn-primary shadow-sm active' : 'btn-light text-secondary' ?>" id="btn-mode-trx" onclick="switchStatementView('transactions')">
                        <i class="fa-solid fa-list-check me-1"></i> Detailed Register (লেনদেন বিবরণী)
                    </button>
                    <button type="button" class="btn btn-sm rounded-1 px-3 fw-bold mode-btn <?= $curViewMode === 'both' ? 'btn-primary shadow-sm active' : 'btn-light text-secondary' ?>" id="btn-mode-both" onclick="switchStatementView('both')">
                        <i class="fa-solid fa-table-cells-large me-1"></i> Both Together (একত্রে উভয়টি)
                    </button>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <a href="<?= $printTypeUrl ?>" target="_blank" class="btn btn-sm btn-light border text-dark d-flex align-items-center gap-1 shadow-xs" title="Print 1-Page Summary">
                    <i class="fa-solid fa-print text-primary"></i> <span class="d-none d-sm-inline">Quick Print (১ পৃষ্ঠা)</span>
                </a>
                <a href="<?= $exportTypeUrl ?>" class="btn btn-sm btn-light border text-dark d-flex align-items-center gap-1 shadow-xs" title="Export CSV Summary">
                    <i class="fa-solid fa-file-csv text-success"></i> <span class="d-none d-sm-inline">Export Matrix</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Payment Type Wise Collection Statement Matrix -->
    <div class="col-12 mb-4" id="section-type-summary" style="<?= $curViewMode === 'transactions' ? 'display:none;' : '' ?>">
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-1 fw-bold text-dark d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white p-2 rounded-2"><i class="fa-solid fa-chart-pie"></i></span>
                        <span>Payment Type Wise Collection Statement (পেমেন্ট হেড ভিত্তিক মোট কালেকশন বিবরণী)</span>
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
                            <th style="min-width: 220px;">Payment Type / Purpose (কালেকশন খাত)</th>
                            <th class="text-center" style="width: 90px;">Trx Count</th>
                            <th class="text-end" style="min-width: 130px;">Bank Inflow (ব্যাংক)</th>
                            <th class="text-end" style="min-width: 130px;">Cash In Hand (ক্যাশ)</th>
                            <th class="text-end" style="min-width: 120px;">Cheque (চেক)</th>
                            <th class="text-end" style="min-width: 120px;">Online / MFS (অনলাইন)</th>
                            <th class="text-end fw-bold" style="min-width: 150px;">Total Collection (মোট টাকা)</th>
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
                                        <small class="text-muted extra-small" style="font-size: 11px;"><?= htmlspecialchars($r['name_bn']) ?></small>
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
                                <i class="fa-solid fa-calculator text-primary me-1"></i> GRAND TOTAL COLLECTION (সর্বমোট কালেকশন):
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

            <!-- Bank Breakdown Footer inside Card -->
            <?php if (!empty($summary['by_bank'])): ?>
            <div class="card-footer bg-white border-top p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-bold text-dark"><i class="fa-solid fa-building-columns text-primary me-1"></i> Bank Account Wise Inflow Breakdown:</span>
                    <span class="small text-muted">Total Bank Inflow: <strong>৳<?= number_format($grand['bank_amount'], 2) ?></strong></span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($summary['by_bank'] as $bName => $bData): ?>
                        <span class="badge bg-light text-dark border p-2 px-3 rounded-2" style="font-size: 11px;">
                            <strong class="text-primary"><?= htmlspecialchars($bName) ?>:</strong> 
                            <span class="font-monospace ms-1">৳<?= number_format($bData['amount'], 2) ?></span>
                            <span class="text-muted ms-1">(<?= $bData['count'] ?> txns)</span>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Detailed Collection Records Register -->
    <div class="col-12 mb-4" id="section-transactions" style="<?= $curViewMode === 'type_summary' ? 'display:none;' : '' ?>">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0 fw-bold text-dark">
                    <i class="fa-solid fa-table-list text-primary me-2"></i>Collection Records (কালেকশন বিবরণী)
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
                            <th class="text-center">Verification</th>
                            <th class="text-center" style="width: 70px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="11" class="text-center py-5 text-muted">
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
                                        <?php if (!empty($t['client_phone'])): ?>
                                            <div class="text-muted extra-small"><i class="fa-solid fa-phone me-1" style="font-size: 10px;"></i><?= htmlspecialchars($t['client_phone']) ?></div>
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
                                        <span class="badge bg-light text-dark border px-2 py-1" style="font-size: 11.5px; font-weight: 600;">
                                            <?= ucwords(str_replace('_', ' ', $t['payment_type'] ?? '')) ?>
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
                                    <td class="text-center">
                                        <span class="badge <?= $vClass ?> px-2 py-1 text-capitalize" style="font-size: 10.5px;">
                                            <?= $vStatus ?>
                                        </span>
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
                            <td colspan="2"></td>
                        </tr>
                        <tr class="fw-bold bg-primary-subtle text-primary">
                            <td colspan="8" class="text-end">OVERALL FILTERED TOTAL COLLECTION:</td>
                            <td class="text-end font-monospace text-primary fs-14">৳<?= number_format($summary['total_amount'] ?? 0, 2) ?></td>
                            <td colspan="2"></td>
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
    const secTrx = document.getElementById('section-transactions');
    const btnType = document.getElementById('btn-mode-type');
    const btnTrx = document.getElementById('btn-mode-trx');
    const btnBoth = document.getElementById('btn-mode-both');

    const inactiveClass = 'btn btn-sm rounded-1 px-3 fw-bold mode-btn btn-light text-secondary';
    const activeClass   = 'btn btn-sm rounded-1 px-3 fw-bold mode-btn btn-primary shadow-sm active';

    if (btnType) btnType.className = inactiveClass;
    if (btnTrx)  btnTrx.className  = inactiveClass;
    if (btnBoth) btnBoth.className = inactiveClass;

    if (mode === 'type_summary') {
        if (secType) secType.style.display = '';
        if (secTrx) secTrx.style.display = 'none';
        if (btnType) btnType.className = activeClass;
    } else if (mode === 'transactions') {
        if (secType) secType.style.display = 'none';
        if (secTrx) secTrx.style.display = '';
        if (btnTrx) btnTrx.className = activeClass;
    } else { // both
        if (secType) secType.style.display = '';
        if (secTrx) secTrx.style.display = '';
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

document.addEventListener('DOMContentLoaded', function() {
    togglePeriodFields();
});
</script>


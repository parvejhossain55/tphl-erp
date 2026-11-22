<?php
$baseUrl = $GLOBALS['base_url'] ?? '/TPHL-ERP';

$fmtShort = function (float $val): string {
    $abs = abs($val);
    $sign = $val < 0 ? '−' : '';
    if ($abs >= 10000000) {
        return $sign . '৳' . number_format($abs / 10000000, 2) . ' Cr';
    }
    if ($abs >= 100000) {
        return $sign . '৳' . number_format($abs / 100000, 2) . ' L';
    }
    return $sign . '৳' . number_format($abs, 0);
};

$fmtBDT = function (float $val): string {
    $abs = abs($val);
    $sign = $val < 0 ? '−' : '';
    $parts = explode('.', number_format($abs, 2, '.', ''));
    $intPart = $parts[0];
    $decPart = $parts[1];
    $len = strlen($intPart);
    if ($len > 3) {
        $lastThree = substr($intPart, -3);
        $rest = substr($intPart, 0, $len - 3);
        $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
        $formatted = $rest . ',' . $lastThree;
    } else {
        $formatted = $intPart;
    }
    return $sign . '৳' . $formatted . '.' . $decPart;
};

$s = $overview['summary'];
$selectedProjects = $filters['project_ids'] ?? [];
$selectedCategories = $filters['categories'] ?? [];
$baseQueryParams = http_build_query([
    'period' => $period,
    'start_date' => $start_date,
    'end_date' => $end_date,
    'branch_id' => $filters['branch_id'] ?? '',
    'project_ids' => $selectedProjects,
    'transaction_type' => $filters['transaction_type'] ?? 'all',
    'categories' => $selectedCategories,
    'payment_mode' => $filters['payment_mode'] ?? 'all',
    'emi_scope' => $emi_scope,
    'interval' => $interval_mode,
]);
?>

<style>
    .ca-v2-wrap {
        --ca-green: #14532d;
        --ca-green-mid: #166534;
        --ca-green-soft: #ecfdf5;
        --ca-green-border: #bbf7d0;
        --ca-slate: #94a3b8;
        --ca-text: #0f172a;
        --ca-muted: #64748b;
        --ca-border: #e2e8f0;
        --ca-bg: #f8fafc;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        color: var(--ca-text);
    }
    .ca-header-title {
        font-size: 1.55rem;
        font-weight: 700;
        letter-spacing: -0.02em;
        color: var(--ca-text);
    }
    .ca-card {
        background: #ffffff;
        border: 1px solid var(--ca-border);
        border-radius: 12px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .ca-btn-primary {
        background: var(--ca-green);
        color: #fff;
        border: 1px solid var(--ca-green);
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        padding: 0.48rem 1rem;
    }
    .ca-btn-primary:hover { background: var(--ca-green-mid); color: #fff; }
    .ca-btn-outline {
        background: #fff;
        color: var(--ca-text);
        border: 1px solid var(--ca-border);
        border-radius: 8px;
        font-weight: 500;
        font-size: 0.85rem;
        padding: 0.45rem 0.9rem;
    }
    .ca-btn-outline:hover { background: #f1f5f9; color: var(--ca-text); }
    .ca-status-bar {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 10px;
        padding: 0.55rem 1rem;
        font-size: 0.82rem;
    }
    .ca-tabs {
        display: flex;
        gap: 1.75rem;
        border-bottom: 1px solid var(--ca-border);
        padding: 0 0.25rem;
        overflow-x: auto;
    }
    .ca-tab-btn {
        background: transparent;
        border: none;
        border-bottom: 2px solid transparent;
        padding: 0.7rem 0.2rem;
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--ca-muted);
        white-space: nowrap;
        cursor: pointer;
    }
    .ca-tab-btn.active {
        color: var(--ca-green);
        border-bottom-color: var(--ca-green);
    }
    .ca-kpi-grid-5 {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 0.9rem;
    }
    .ca-kpi-grid-4 {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.9rem;
    }
    @media (max-width: 1200px) {
        .ca-kpi-grid-5, .ca-kpi-grid-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 576px) {
        .ca-kpi-grid-5, .ca-kpi-grid-4 { grid-template-columns: 1fr; }
    }
    .ca-kpi-card {
        position: relative;
        background: #fff;
        border: 1px solid var(--ca-border);
        border-radius: 12px;
        padding: 1rem 1.1rem;
    }
    .ca-info-tip {
        position: absolute;
        top: 0.72rem;
        right: 0.78rem;
        color: #94a3b8;
        font-size: 0.82rem;
        cursor: help;
        line-height: 1;
        z-index: 5;
    }
    .ca-info-tip:hover,
    .ca-info-tip:focus {
        color: var(--ca-green);
        outline: none;
    }
    .ca-tooltip-box {
        visibility: hidden;
        opacity: 0;
        pointer-events: none;
        position: absolute;
        top: calc(100% + 6px);
        right: -4px;
        width: 230px;
        background: #0f172a;
        color: #f8fafc;
        font-size: 0.73rem;
        font-weight: 400;
        line-height: 1.45;
        padding: 0.55rem 0.7rem;
        border-radius: 8px;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.22);
        transition: opacity 0.15s ease, transform 0.15s ease;
        transform: translateY(-2px);
        z-index: 30;
        text-align: left;
        white-space: normal;
    }
    .ca-info-tip:hover .ca-tooltip-box,
    .ca-info-tip:focus .ca-tooltip-box {
        visibility: visible;
        opacity: 1;
        transform: translateY(0);
    }
    .ca-kpi-label {
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--ca-muted);
        margin-bottom: 0.35rem;
    }
    .ca-kpi-val {
        font-size: 1.45rem;
        font-weight: 700;
        letter-spacing: -0.02em;
        font-variant-numeric: tabular-nums;
    }
    .ca-kpi-sub {
        font-size: 0.74rem;
        color: var(--ca-muted);
        margin-top: 0.3rem;
    }
    .ca-table th {
        font-size: 0.76rem;
        font-weight: 600;
        color: var(--ca-muted);
        background: #f8fafc;
        border-bottom: 1px solid var(--ca-border);
        padding: 0.7rem 0.9rem;
        white-space: nowrap;
    }
    .ca-table td {
        font-size: 0.84rem;
        padding: 0.72rem 0.9rem;
        border-bottom: 1px solid #f1f5f9;
        font-variant-numeric: tabular-nums;
        vertical-align: middle;
    }
    .ca-table tr.ca-total-row td {
        background: #f0fdf4;
        font-weight: 700;
        border-top: 1px solid #bbf7d0;
    }
    .ca-proj-row {
        cursor: pointer;
        transition: background 0.12s ease;
    }
    .ca-proj-row:hover { background: #f8fafc; }
    .ca-proj-row.expanded { background: #f0fdf4; font-weight: 600; }
    .ca-cat-row td:first-child { padding-left: 2.3rem; color: #334155; }
    .ca-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        background: #ecfdf5;
        color: #166534;
        border: 1px solid #bbf7d0;
        border-radius: 999px;
        padding: 0.15rem 0.65rem;
        font-size: 0.74rem;
        font-weight: 600;
    }
    .ca-alert-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        background: #fffbeb;
        color: #92400e;
        border: 1px solid #fde68a;
        border-radius: 999px;
        padding: 0.2rem 0.7rem;
        font-size: 0.75rem;
        font-weight: 600;
        text-decoration: none;
    }
    .ca-alert-pill:hover { background: #fef3c7; color: #78350f; }
    .ca-bar-chart {
        display: flex;
        align-items: flex-end;
        gap: 1rem;
        height: 170px;
        padding: 1rem 0.5rem 0.25rem;
        border-bottom: 1px solid var(--ca-border);
        overflow-x: auto;
    }
    .ca-bar-group {
        flex: 1 0 54px;
        max-width: 96px;
        display: flex;
        flex-direction: column;
        align-items: center;
        height: 100%;
        justify-content: flex-end;
    }
    .ca-bars-pair {
        display: flex;
        align-items: flex-end;
        gap: 5px;
        height: 130px;
        width: 100%;
        justify-content: center;
    }
    .ca-bar {
        width: 16px;
        border-radius: 4px 4px 0 0;
        min-height: 3px;
        transition: opacity 0.15s;
    }
    .ca-bar:hover { opacity: 0.85; }
    .ca-bar-in { background: #166534; }
    .ca-bar-out { background: #94a3b8; }
    .ca-bar-label {
        font-size: 0.7rem;
        color: var(--ca-muted);
        margin-top: 0.45rem;
        text-align: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 88px;
    }
    /* Slide-over drawer */
    .ca-drawer-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.38);
        z-index: 1050;
        display: none;
    }
    .ca-drawer-backdrop.open { display: block; }
    .ca-drawer {
        position: fixed;
        top: 0;
        right: -680px;
        width: min(680px, 100vw);
        height: 100vh;
        background: #fff;
        z-index: 1055;
        box-shadow: -8px 0 30px rgba(15, 23, 42, 0.15);
        transition: right 0.22s ease;
        display: flex;
        flex-direction: column;
    }
    .ca-drawer.open { right: 0; }
    /* Multi-select dropdown */
    .ca-ms-wrap { position: relative; }
    .ca-ms-trigger {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #fff;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        line-height: 1.5;
        color: #212529;
        cursor: pointer;
        text-align: left;
    }
    .ca-ms-trigger-text {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        padding-right: 0.4rem;
    }
    .ca-ms-menu {
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        min-width: 240px;
        width: 100%;
        background: #fff;
        border: 1px solid var(--ca-border);
        border-radius: 10px;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.14);
        z-index: 40;
        padding: 0.5rem;
        display: none;
    }
    .ca-ms-menu.open { display: block; }
    .ca-ms-list {
        max-height: 210px;
        overflow-y: auto;
        margin-top: 0.35rem;
    }
    .ca-ms-item {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.28rem 0.35rem;
        border-radius: 6px;
        font-size: 0.8rem;
        cursor: pointer;
        margin: 0;
    }
    .ca-ms-item:hover { background: #f1f5f9; }
</style>

<div class="container-fluid py-3 ca-v2-wrap">
    <!-- Top Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="text-muted" style="font-size: 0.78rem;">Accounts / Reporting</div>
            <div class="d-flex align-items-center gap-2">
                <h1 class="ca-header-title mb-0">Central Accounts</h1>
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1" style="font-size:0.7rem;">V2 Executive</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $baseUrl ?>/central-statement-v2-export?tab=<?= urlencode($active_tab) ?>&<?= $baseQueryParams ?>" class="ca-btn-outline text-decoration-none">
                <i class="fa-solid fa-file-excel text-success me-1"></i> Export Excel
            </a>
            <a href="<?= $baseUrl ?>/central-statement-v2-print?tab=<?= urlencode($active_tab) ?>&<?= $baseQueryParams ?>" target="_blank" class="ca-btn-outline text-decoration-none">
                <i class="fa-solid fa-file-pdf text-danger me-1"></i> Export PDF
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="<?= $baseUrl ?>/central-statement-v2" id="v2FilterForm" class="ca-card p-3 mb-3">
        <input type="hidden" name="tab" id="v2ActiveTabInput" value="<?= htmlspecialchars($active_tab) ?>">
        <input type="hidden" name="emi_scope" id="v2EmiScopeInput" value="<?= htmlspecialchars($emi_scope) ?>">
        <div class="row g-2 align-items-end">
            <div class="col-md-2 col-sm-6">
                <label class="form-label mb-1 text-muted" style="font-size:0.75rem; font-weight:600;">Period</label>
                <select name="period" id="v2PeriodSelect" class="form-select form-select-sm" onchange="toggleCustomDateFields()">
                    <option value="this_month" <?= $period === 'this_month' ? 'selected' : '' ?>>This Month (<?= date('M Y') ?>)</option>
                    <option value="last_month" <?= $period === 'last_month' ? 'selected' : '' ?>>Last Month (<?= date('M Y', strtotime('first day of last month')) ?>)</option>
                    <option value="last_6_months" <?= $period === 'last_6_months' ? 'selected' : '' ?>>Last 6 Months</option>
                    <option value="this_year" <?= $period === 'this_year' ? 'selected' : '' ?>>This Year (<?= date('Y') ?>)</option>
                    <option value="all_time" <?= $period === 'all_time' ? 'selected' : '' ?>>All Time</option>
                    <option value="custom" <?= $period === 'custom' ? 'selected' : '' ?>>Custom Date Range</option>
                </select>
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label mb-1 text-muted" style="font-size:0.75rem; font-weight:600;">Branch / Office</label>
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="">All Branches & General</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= (int)$b['id'] ?>" <?= ((string)($filters['branch_id'] ?? '') === (string)$b['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['name']) ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="general" <?= ($filters['branch_id'] ?? '') === 'general' ? 'selected' : '' ?>>General</option>
                </select>
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label mb-1 text-muted" style="font-size:0.75rem; font-weight:600;">Projects (Multi)</label>
                <div class="ca-ms-wrap" id="msProjects">
                    <button type="button" class="ca-ms-trigger" onclick="toggleMultiDropdown('msProjects')">
                        <span class="ca-ms-trigger-text" data-default="All Projects">All Projects</span>
                        <i class="fa-solid fa-chevron-down text-muted" style="font-size:0.65rem;"></i>
                    </button>
                    <div class="ca-ms-menu">
                        <input type="text" class="form-control form-control-sm" placeholder="Search projects..." oninput="filterMultiOptions('msProjects', this.value)">
                        <div class="d-flex justify-content-between mt-1 px-1" style="font-size:0.72rem;">
                            <button type="button" class="btn btn-link btn-sm p-0 text-success text-decoration-none" onclick="setAllMultiOptions('msProjects', true)">Select All</button>
                            <button type="button" class="btn btn-link btn-sm p-0 text-muted text-decoration-none" onclick="setAllMultiOptions('msProjects', false)">Clear</button>
                        </div>
                        <div class="ca-ms-list">
                            <?php foreach ($projects as $p): $pidStr = (string)$p['id']; ?>
                                <label class="ca-ms-item">
                                    <input type="checkbox" class="form-check-input mt-0" name="project_ids[]" value="<?= (int)$p['id'] ?>"
                                        data-label="<?= htmlspecialchars($p['name']) ?>"
                                        <?= in_array((int)$p['id'], $selectedProjects, true) ? 'checked' : '' ?>
                                        onchange="updateMultiLabel('msProjects')">
                                    <span><?= htmlspecialchars($p['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                            <label class="ca-ms-item">
                                <input type="checkbox" class="form-check-input mt-0" name="project_ids[]" value="general"
                                    data-label="General"
                                    <?= in_array('general', $selectedProjects, true) ? 'checked' : '' ?>
                                    onchange="updateMultiLabel('msProjects')">
                                <span>General</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label mb-1 text-muted" style="font-size:0.75rem; font-weight:600;">Transaction Type</label>
                <select name="transaction_type" class="form-select form-select-sm">
                    <option value="all" <?= $filters['transaction_type'] === 'all' ? 'selected' : '' ?>>All Transactions</option>
                    <option value="cash_in" <?= $filters['transaction_type'] === 'cash_in' ? 'selected' : '' ?>>Cash In</option>
                    <option value="cash_out" <?= $filters['transaction_type'] === 'cash_out' ? 'selected' : '' ?>>Cash Out</option>
                </select>
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label mb-1 text-muted" style="font-size:0.75rem; font-weight:600;">Categories (Multi)</label>
                <div class="ca-ms-wrap" id="msCategories">
                    <button type="button" class="ca-ms-trigger" onclick="toggleMultiDropdown('msCategories')">
                        <span class="ca-ms-trigger-text" data-default="All Categories">All Categories</span>
                        <i class="fa-solid fa-chevron-down text-muted" style="font-size:0.65rem;"></i>
                    </button>
                    <div class="ca-ms-menu">
                        <input type="text" class="form-control form-control-sm" placeholder="Search categories..." oninput="filterMultiOptions('msCategories', this.value)">
                        <div class="d-flex justify-content-between mt-1 px-1" style="font-size:0.72rem;">
                            <button type="button" class="btn btn-link btn-sm p-0 text-success text-decoration-none" onclick="setAllMultiOptions('msCategories', true)">Select All</button>
                            <button type="button" class="btn btn-link btn-sm p-0 text-muted text-decoration-none" onclick="setAllMultiOptions('msCategories', false)">Clear</button>
                        </div>
                        <div class="ca-ms-list">
                            <?php foreach ($categories_meta as $key => $meta): ?>
                                <label class="ca-ms-item">
                                    <input type="checkbox" class="form-check-input mt-0" name="categories[]" value="<?= htmlspecialchars($key) ?>"
                                        data-label="<?= htmlspecialchars($meta['label']) ?>"
                                        <?= in_array($key, $selectedCategories, true) ? 'checked' : '' ?>
                                        onchange="updateMultiLabel('msCategories')">
                                    <span><?= htmlspecialchars($meta['label']) ?> <small class="text-muted">(<?= $meta['type'] === 'cash_in' ? 'In' : 'Out' ?>)</small></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-sm-12 d-flex gap-2">
                <button type="submit" class="ca-btn-primary flex-grow-1">Apply</button>
                <a href="<?= $baseUrl ?>/central-statement-v2" class="ca-btn-outline text-decoration-none">Reset</a>
            </div>
        </div>

        <!-- Collapsible Advanced Filters -->
        <div class="mt-2 pt-2 border-top d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-muted" style="font-size:0.76rem; font-weight:600;">Applied:</span>
                <?php foreach ($applied_chips as $chip): ?>
                    <span class="ca-chip"><?= htmlspecialchars($chip['label']) ?>: <?= htmlspecialchars($chip['value']) ?></span>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-link btn-sm text-decoration-none text-success fw-semibold p-0" onclick="document.getElementById('v2AdvBox').classList.toggle('d-none')">
                <i class="fa-solid fa-sliders me-1"></i> Advanced Filters
            </button>
        </div>

        <div id="v2AdvBox" class="row g-2 mt-2 pt-2 border-top <?= ($period === 'custom' || $filters['payment_mode'] !== 'all' || $interval_mode !== 'auto') ? '' : 'd-none' ?>">
            <div class="col-md-3 col-sm-6">
                <label class="form-label mb-1 text-muted" style="font-size:0.75rem;">Start Date (Custom)</label>
                <input type="date" name="start_date" id="v2StartDate" value="<?= htmlspecialchars($start_date) ?>" class="form-control form-control-sm">
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="form-label mb-1 text-muted" style="font-size:0.75rem;">End Date (Custom)</label>
                <input type="date" name="end_date" id="v2EndDate" value="<?= htmlspecialchars($end_date) ?>" class="form-control form-control-sm">
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="form-label mb-1 text-muted" style="font-size:0.75rem;">Payment Mode</label>
                <select name="payment_mode" class="form-select form-select-sm">
                    <option value="all" <?= $filters['payment_mode'] === 'all' ? 'selected' : '' ?>>All Modes (Bank & Cash)</option>
                    <option value="bank" <?= $filters['payment_mode'] === 'bank' ? 'selected' : '' ?>>Bank / Cheque / Online Only</option>
                    <option value="cash" <?= $filters['payment_mode'] === 'cash' ? 'selected' : '' ?>>Cash Only</option>
                </select>
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="form-label mb-1 text-muted" style="font-size:0.75rem;">Statement Interval</label>
                <select name="interval" class="form-select form-select-sm">
                    <option value="auto" <?= $interval_mode === 'auto' ? 'selected' : '' ?>>Auto (Smart)</option>
                    <option value="daily" <?= $interval_mode === 'daily' ? 'selected' : '' ?>>Daily Breakdown</option>
                    <option value="monthly" <?= $interval_mode === 'monthly' ? 'selected' : '' ?>>Monthly Breakdown</option>
                </select>
            </div>
        </div>
    </form>

    <!-- Real-Time Executive Status & Actionable Alert Bar -->
    <div class="ca-status-bar d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div class="d-flex align-items-center gap-2 text-success fw-medium">
            <i class="fa-regular fa-clock"></i>
            <span>Live ERP Data · As of <?= date('d M Y, H:i') ?></span>
            <span class="text-muted fw-normal">| Scope: <?= htmlspecialchars($start_date) ?> to <?= htmlspecialchars($end_date) ?></span>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <?php if ($alerts['pending_verification']['count'] > 0): ?>
                <a href="<?= $baseUrl ?>/lead-payments?verification_status=pending" class="ca-alert-pill" title="Client receipts awaiting accounts verification">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <?= number_format($alerts['pending_verification']['count']) ?> Pending Verification (<?= $fmtShort($alerts['pending_verification']['amount']) ?>)
                </a>
            <?php endif; ?>
            <?php if ($alerts['unpaid_payables']['count'] > 0): ?>
                <button type="button" class="ca-alert-pill" onclick="switchV2Tab('emi_cost')" title="Approved vendor, contractor & cashout bills awaiting payment">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    <?= number_format($alerts['unpaid_payables']['count']) ?> Unpaid Approved Bills (<?= $fmtShort($alerts['unpaid_payables']['amount']) ?>)
                </button>
            <?php endif; ?>
            <?php if ($alerts['overdue_emi']['count'] > 0): ?>
                <button type="button" class="ca-alert-pill" onclick="switchV2Tab('emi_cost')" title="Overdue EMI installments">
                    <i class="fa-solid fa-calendar-xmark"></i>
                    <?= number_format($alerts['overdue_emi']['count']) ?> Overdue EMI (<?= $fmtShort($alerts['overdue_emi']['amount']) ?>)
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- 4 Navigation Tabs -->
    <div class="ca-tabs mb-3">
        <button type="button" class="ca-tab-btn <?= $active_tab === 'overview' ? 'active' : '' ?>" data-tab="overview" onclick="switchV2Tab('overview')">Overview</button>
        <button type="button" class="ca-tab-btn <?= $active_tab === 'branch' ? 'active' : '' ?>" data-tab="branch" onclick="switchV2Tab('branch')">Branch Performance</button>
        <button type="button" class="ca-tab-btn <?= $active_tab === 'project' ? 'active' : '' ?>" data-tab="project" onclick="switchV2Tab('project')">Project &amp; Category</button>
        <button type="button" class="ca-tab-btn <?= $active_tab === 'emi_cost' ? 'active' : '' ?>" data-tab="emi_cost" onclick="switchV2Tab('emi_cost')">EMI vs Project Cost &amp; Dues</button>
    </div>

    <!-- TAB 1: OVERVIEW -->
    <div id="v2-tab-overview" class="v2-tab-pane <?= $active_tab === 'overview' ? '' : 'd-none' ?>">
        <div class="ca-kpi-grid-5 mb-3">
            <div class="ca-kpi-card">
                <span class="ca-info-tip" tabindex="0"><i class="fa-regular fa-circle-info"></i><span class="ca-tooltip-box">Net verified cash &amp; bank balance accumulated from all prior transactions before the selected start date.</span></span>
                <div class="ca-kpi-label">Opening Balance</div>
                <div class="ca-kpi-val text-dark"><?= $fmtShort($s['opening_balance']) ?></div>
                <div class="ca-kpi-sub">Before <?= date('d M Y', strtotime($start_date)) ?></div>
            </div>
            <div class="ca-kpi-card">
                <span class="ca-info-tip" tabindex="0"><i class="fa-regular fa-circle-info"></i><span class="ca-tooltip-box">Total accounts-verified client receipts (Booking, EMI, Registration, Dev Charge, Land Share) plus Management Cash-In within this period.</span></span>
                <div class="ca-kpi-label">Total Cash In (Verified)</div>
                <div class="ca-kpi-val text-success"><?= $fmtShort($s['cash_in']) ?></div>
                <div class="ca-kpi-sub">Project: <?= $fmtShort($s['project_cash_in']) ?> · General: <?= $fmtShort($s['general_cash_in']) ?></div>
            </div>
            <div class="ca-kpi-card">
                <span class="ca-info-tip" tabindex="0"><i class="fa-regular fa-circle-info"></i><span class="ca-tooltip-box">Total actual paid disbursements: Vendor, Contractor, Procurement (net of returns), Fund/Land Requisitions, Payroll &amp; Cashouts.</span></span>
                <div class="ca-kpi-label">Total Cash Out (Paid)</div>
                <div class="ca-kpi-val text-dark"><?= $fmtShort($s['cash_out']) ?></div>
                <div class="ca-kpi-sub">Project: <?= $fmtShort($s['project_cash_out']) ?> · General: <?= $fmtShort($s['general_cash_out']) ?></div>
            </div>
            <div class="ca-kpi-card">
                <span class="ca-info-tip" tabindex="0"><i class="fa-regular fa-circle-info"></i><span class="ca-tooltip-box">Net surplus or deficit for the selected period (Total Cash In minus Total Cash Out).</span></span>
                <div class="ca-kpi-label">Net Cash Movement</div>
                <div class="ca-kpi-val <?= $s['net_movement'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= $fmtShort($s['net_movement']) ?></div>
                <div class="ca-kpi-sub"><?= number_format($s['txn_count']) ?> posted transactions</div>
            </div>
            <div class="ca-kpi-card" style="background:#f0fdf4; border-color:#bbf7d0;">
                <span class="ca-info-tip" tabindex="0"><i class="fa-regular fa-circle-info"></i><span class="ca-tooltip-box">Current liquid position at period end (Opening Balance + Net Movement), split between Bank/Cheque and Cash in Hand.</span></span>
                <div class="ca-kpi-label text-success">Closing Liquid Balance</div>
                <div class="ca-kpi-val text-success"><?= $fmtShort($s['closing_balance']) ?></div>
                <div class="ca-kpi-sub">Bank: <?= $fmtShort($s['closing_bank']) ?> · Cash: <?= $fmtShort($s['closing_cash']) ?></div>
            </div>
        </div>

        <!-- Cash Movement Trend Chart -->
        <div class="ca-card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="fw-bold" style="font-size:0.95rem;">Cash movement trend</div>
                <div class="d-flex gap-3" style="font-size:0.78rem;">
                    <span><span class="d-inline-block rounded-1 me-1" style="width:10px;height:10px;background:#166534;"></span> Cash In</span>
                    <span><span class="d-inline-block rounded-1 me-1" style="width:10px;height:10px;background:#94a3b8;"></span> Cash Out</span>
                </div>
            </div>
            <?php
            $chartIntervals = array_slice($overview['intervals'], -14);
            $maxChartVal = 1.0;
            foreach ($chartIntervals as $ci) {
                $maxChartVal = max($maxChartVal, $ci['cash_in'], $ci['cash_out']);
            }
            ?>
            <?php if (empty($chartIntervals)): ?>
                <div class="text-center text-muted py-4">No transactions found for the selected scope.</div>
            <?php else: ?>
                <div class="ca-bar-chart">
                    <?php foreach ($chartIntervals as $ci):
                        $inH = max(3, round(($ci['cash_in'] / $maxChartVal) * 124));
                        $outH = max(3, round(($ci['cash_out'] / $maxChartVal) * 124));
                    ?>
                        <div class="ca-bar-group" title="<?= htmlspecialchars($ci['label']) ?> | In: <?= $fmtBDT($ci['cash_in']) ?> | Out: <?= $fmtBDT($ci['cash_out']) ?>">
                            <div class="ca-bars-pair">
                                <div class="ca-bar ca-bar-in" style="height:<?= $inH ?>px;"></div>
                                <div class="ca-bar ca-bar-out" style="height:<?= $outH ?>px;"></div>
                            </div>
                            <div class="ca-bar-label"><?= htmlspecialchars($ci['label']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Central Statement Interval Table -->
        <div class="ca-card overflow-hidden">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-bold" style="font-size:0.95rem;">Central Statement</div>
                    <div class="text-muted" style="font-size:0.78rem;">Cash movement by <?= $overview['interval_type'] === 'monthly' ? 'month' : 'day' ?> with running balance</div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table ca-table mb-0">
                    <thead>
                        <tr>
                            <th>Date / Month</th>
                            <th class="text-end">Opening Balance</th>
                            <th class="text-end">Cash In</th>
                            <th class="text-end">Cash Out</th>
                            <th class="text-end">Net Movement</th>
                            <th class="text-end">Closing Balance</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($overview['intervals'])): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">No records in this period.</td></tr>
                        <?php else: ?>
                            <?php foreach ($overview['intervals'] as $row): ?>
                                <tr>
                                    <td class="fw-medium"><?= htmlspecialchars($row['label']) ?></td>
                                    <td class="text-end text-muted"><?= $fmtBDT($row['opening']) ?></td>
                                    <td class="text-end text-success fw-medium"><?= $fmtBDT($row['cash_in']) ?></td>
                                    <td class="text-end text-dark"><?= $fmtBDT($row['cash_out']) ?></td>
                                    <td class="text-end fw-semibold <?= $row['net'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= $fmtBDT($row['net']) ?></td>
                                    <td class="text-end fw-semibold"><?= $fmtBDT($row['closing']) ?></td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-link btn-sm text-success text-decoration-none fw-semibold p-0"
                                            onclick="openDrilldown('<?= htmlspecialchars($row['label']) ?>', '<?= $row['start_date'] ?>', '<?= $row['end_date'] ?>', {})">
                                            View transactions &rsaquo;
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="ca-total-row">
                            <td>Total for applied scope</td>
                            <td class="text-end"><?= $fmtBDT($s['opening_balance']) ?></td>
                            <td class="text-end text-success"><?= $fmtBDT($s['cash_in']) ?></td>
                            <td class="text-end"><?= $fmtBDT($s['cash_out']) ?></td>
                            <td class="text-end <?= $s['net_movement'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= $fmtBDT($s['net_movement']) ?></td>
                            <td class="text-end text-success"><?= $fmtBDT($s['closing_balance']) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 2: BRANCH PERFORMANCE -->
    <div id="v2-tab-branch" class="v2-tab-pane <?= $active_tab === 'branch' ? '' : 'd-none' ?>">
        <?php $bt = $branch_data['totals']; ?>
        <div class="ca-kpi-grid-4 mb-3">
            <div class="ca-kpi-card">
                <span class="ca-info-tip" tabindex="0"><i class="fa-regular fa-circle-info"></i><span class="ca-tooltip-box">Total verified receipts attributed to each office branch via transaction branch or collector's employee branch.</span></span>
                <div class="ca-kpi-label">Branch Collections</div>
                <div class="ca-kpi-val text-success"><?= $fmtShort($bt['collections']) ?></div>
                <div class="ca-kpi-sub">By receiving branch / staff</div>
            </div>
            <div class="ca-kpi-card">
                <span class="ca-info-tip" tabindex="0"><i class="fa-regular fa-circle-info"></i><span class="ca-tooltip-box">Total paid expenses, payroll, requisitions, and vendor/contractor payouts mapped to each branch.</span></span>
                <div class="ca-kpi-label">Direct Branch Cost</div>
                <div class="ca-kpi-val text-dark"><?= $fmtShort($bt['direct_cost']) ?></div>
                <div class="ca-kpi-sub">Paid expenses by branch</div>
            </div>
            <div class="ca-kpi-card">
                <span class="ca-info-tip" tabindex="0"><i class="fa-regular fa-circle-info"></i><span class="ca-tooltip-box">Net cash contribution per branch (Branch Collections minus Direct Branch Cost) for the selected period.</span></span>
                <div class="ca-kpi-label">Net Branch Contribution</div>
                <div class="ca-kpi-val <?= $bt['net'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= $fmtShort($bt['net']) ?></div>
                <div class="ca-kpi-sub">Collections less direct cost</div>
            </div>
            <div class="ca-kpi-card">
                <span class="ca-info-tip" tabindex="0"><i class="fa-regular fa-circle-info"></i><span class="ca-tooltip-box">Number of office branches with transactions in this period, including General for unmapped/head-office items.</span></span>
                <div class="ca-kpi-label">Active Branch Scopes</div>
                <div class="ca-kpi-val text-dark"><?= count($branch_data['rows']) ?></div>
                <div class="ca-kpi-sub">Includes General fallback</div>
            </div>
        </div>

        <div class="ca-card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="fw-bold" style="font-size:0.95rem;">Collections vs branch costs</div>
                <div class="d-flex gap-3" style="font-size:0.78rem;">
                    <span><span class="d-inline-block rounded-1 me-1" style="width:10px;height:10px;background:#166534;"></span> Collections</span>
                    <span><span class="d-inline-block rounded-1 me-1" style="width:10px;height:10px;background:#94a3b8;"></span> Branch Cost</span>
                </div>
            </div>
            <?php
            $maxB = 1.0;
            foreach ($branch_data['rows'] as $br) $maxB = max($maxB, $br['collections'], $br['direct_cost']);
            ?>
            <div class="ca-bar-chart">
                <?php foreach ($branch_data['rows'] as $br):
                    $h1 = max(3, round(($br['collections'] / $maxB) * 124));
                    $h2 = max(3, round(($br['direct_cost'] / $maxB) * 124));
                ?>
                    <div class="ca-bar-group" style="max-width:130px;">
                        <div class="ca-bars-pair">
                            <div class="ca-bar ca-bar-in" style="height:<?= $h1 ?>px; width:22px;"></div>
                            <div class="ca-bar ca-bar-out" style="height:<?= $h2 ?>px; width:22px;"></div>
                        </div>
                        <div class="ca-bar-label" style="max-width:120px;"><?= htmlspecialchars($br['branch_name']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="ca-card overflow-hidden">
            <div class="p-3 border-bottom">
                <div class="fw-bold" style="font-size:0.95rem;">Branch Performance</div>
                <div class="text-muted" style="font-size:0.78rem;">Resolved via transaction office_branch_id or staff branch (fallback: General)</div>
            </div>
            <div class="table-responsive">
                <table class="table ca-table mb-0">
                    <thead>
                        <tr>
                            <th>Branch / Office</th>
                            <th class="text-end">Transactions</th>
                            <th class="text-end">Collections Received</th>
                            <th class="text-end">Direct Branch Cost</th>
                            <th class="text-end">Net Movement</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($branch_data['rows'] as $br): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($br['branch_name']) ?></td>
                                <td class="text-end"><?= number_format($br['txn_count']) ?></td>
                                <td class="text-end text-success fw-medium"><?= $fmtBDT($br['collections']) ?></td>
                                <td class="text-end"><?= $fmtBDT($br['direct_cost']) ?></td>
                                <td class="text-end fw-semibold <?= $br['net'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= $fmtBDT($br['net']) ?></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-link btn-sm text-success text-decoration-none fw-semibold p-0"
                                        onclick="openDrilldown('<?= htmlspecialchars($br['branch_name'], ENT_QUOTES) ?>', '<?= $start_date ?>', '<?= $end_date ?>', {branch_id: '<?= $br['branch_id'] === 0 ? 'general' : $br['branch_id'] ?>'})">
                                        View transactions &rsaquo;
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="ca-total-row">
                            <td>Total for applied scope</td>
                            <td class="text-end"><?= number_format($bt['txn_count']) ?></td>
                            <td class="text-end text-success"><?= $fmtBDT($bt['collections']) ?></td>
                            <td class="text-end"><?= $fmtBDT($bt['direct_cost']) ?></td>
                            <td class="text-end <?= $bt['net'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= $fmtBDT($bt['net']) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 3: PROJECT & CATEGORY -->
    <div id="v2-tab-project" class="v2-tab-pane <?= $active_tab === 'project' ? '' : 'd-none' ?>">
        <?php $pt = $project_data['totals']; ?>
        <div class="ca-kpi-grid-4 mb-3">
            <div class="ca-kpi-card">
                <span class="ca-info-tip" tabindex="0"><i class="fa-regular fa-circle-info"></i><span class="ca-tooltip-box">Sum of all verified Cash-In categories across filtered projects and General within the applied date range.</span></span>
                <div class="ca-kpi-label">Project Cash In</div>
                <div class="ca-kpi-val text-success"><?= $fmtShort($pt['cash_in']) ?></div>
                <div class="ca-kpi-sub">All Cash In categories in scope</div>
            </div>
            <div class="ca-kpi-card">
                <span class="ca-info-tip" tabindex="0"><i class="fa-regular fa-circle-info"></i><span class="ca-tooltip-box">Sum of all paid Cash-Out categories (Vendor, Contractor, Procurement, Requisitions, Payroll) in scope.</span></span>
                <div class="ca-kpi-label">Project Cash Out</div>
                <div class="ca-kpi-val text-dark"><?= $fmtShort($pt['cash_out']) ?></div>
                <div class="ca-kpi-sub">All Cash Out categories in scope</div>
            </div>
            <div class="ca-kpi-card">
                <span class="ca-info-tip" tabindex="0"><i class="fa-regular fa-circle-info"></i><span class="ca-tooltip-box">Net project cash flow (Project Cash In minus Project Cash Out) for the applied scope.</span></span>
                <div class="ca-kpi-label">Net Project Movement</div>
                <div class="ca-kpi-val <?= $pt['net'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= $fmtShort($pt['net']) ?></div>
                <div class="ca-kpi-sub">Cash In less Cash Out</div>
            </div>
            <div class="ca-kpi-card">
                <span class="ca-info-tip" tabindex="0"><i class="fa-regular fa-circle-info"></i><span class="ca-tooltip-box">Total active projects with cash movement in this period, including General for non-project overhead.</span></span>
                <div class="ca-kpi-label">Project Scopes</div>
                <div class="ca-kpi-val text-dark"><?= number_format($pt['project_count']) ?></div>
                <div class="ca-kpi-sub">Includes General</div>
            </div>
        </div>

        <div class="ca-card overflow-hidden">
            <div class="p-3 border-bottom">
                <div class="fw-bold" style="font-size:0.95rem;">Project &amp; Category</div>
                <div class="text-muted" style="font-size:0.78rem;">Click any project row to expand category totals and inspect source receipts/vouchers</div>
            </div>
            <div class="table-responsive">
                <table class="table ca-table mb-0">
                    <thead>
                        <tr>
                            <th>Project / Category</th>
                            <th class="text-end">Transaction Count</th>
                            <th class="text-end">Cash In</th>
                            <th class="text-end">Cash Out</th>
                            <th class="text-end">Net Movement</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($project_data['projects'])): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No project transactions found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($project_data['projects'] as $idx => $proj):
                                $pKey = 'proj-' . $proj['project_id'];
                                $isFirst = ($idx === 0);
                            ?>
                                <tr class="ca-proj-row <?= $isFirst ? 'expanded' : '' ?>" onclick="toggleProjectGroup('<?= $pKey ?>', this)">
                                    <td>
                                        <span class="me-1 ca-chevron"><?= $isFirst ? '▾' : '›' ?></span>
                                        <?= htmlspecialchars($proj['project_name']) ?>
                                    </td>
                                    <td class="text-end"><?= number_format($proj['txn_count']) ?></td>
                                    <td class="text-end text-success"><?= $fmtBDT($proj['cash_in']) ?></td>
                                    <td class="text-end"><?= $fmtBDT($proj['cash_out']) ?></td>
                                    <td class="text-end <?= $proj['net'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= $fmtBDT($proj['net']) ?></td>
                                    <td class="text-end text-success fw-semibold" style="font-size:0.78rem;"><?= $isFirst ? 'Collapse' : 'Expand' ?></td>
                                </tr>
                                <?php foreach ($proj['categories'] as $cat): ?>
                                    <tr class="ca-cat-row <?= $pKey ?>-child <?= $isFirst ? '' : 'd-none' ?>">
                                        <td>
                                            <?= htmlspecialchars($cat['category_label']) ?>
                                            <span class="text-muted">· <?= $cat['flow_type'] === 'cash_in' ? 'Cash In' : 'Cash Out' ?></span>
                                        </td>
                                        <td class="text-end"><?= number_format($cat['txn_count']) ?></td>
                                        <td class="text-end text-success"><?= $fmtBDT($cat['cash_in']) ?></td>
                                        <td class="text-end"><?= $fmtBDT($cat['cash_out']) ?></td>
                                        <td class="text-end <?= $cat['net'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= $fmtBDT($cat['net']) ?></td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-link btn-sm text-success text-decoration-none fw-semibold p-0"
                                                onclick="event.stopPropagation(); openDrilldown('<?= htmlspecialchars($proj['project_name'] . ' · ' . $cat['category_label'], ENT_QUOTES) ?>', '<?= $start_date ?>', '<?= $end_date ?>', {project_id: '<?= $proj['project_id'] === 0 ? 'general' : $proj['project_id'] ?>', category: '<?= htmlspecialchars($cat['category_key']) ?>', transaction_type: '<?= $cat['flow_type'] ?>'})">
                                                View receipts &rsaquo;
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="ca-total-row">
                            <td>Total for applied scope</td>
                            <td class="text-end"><?= number_format($pt['txn_count']) ?></td>
                            <td class="text-end text-success"><?= $fmtBDT($pt['cash_in']) ?></td>
                            <td class="text-end"><?= $fmtBDT($pt['cash_out']) ?></td>
                            <td class="text-end <?= $pt['net'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= $fmtBDT($pt['net']) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 4: EMI VS PROJECT COST & DUES -->
    <div id="v2-tab-emi_cost" class="v2-tab-pane <?= $active_tab === 'emi_cost' ? '' : 'd-none' ?>">
        <?php $et = $emi_cost_data['totals']; ?>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div class="text-muted" style="font-size:0.82rem;">Compare project collections against paid construction/project costs, overdue EMI, and unpaid approved bills.</div>
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn <?= $emi_scope === 'emi' ? 'btn-success' : 'btn-outline-secondary' ?>" onclick="setEmiScope('emi')">EMI Only</button>
                <button type="button" class="btn <?= $emi_scope === 'all_project' ? 'btn-success' : 'btn-outline-secondary' ?>" onclick="setEmiScope('all_project')">All Project Collections</button>
            </div>
        </div>

        <div class="ca-kpi-grid-4 mb-3">
            <div class="ca-kpi-card">
                <span class="ca-info-tip" tabindex="0"><i class="fa-regular fa-circle-info"></i><span class="ca-tooltip-box">Verified project collections in this period (EMI installments only, or all client receipt types when toggled).</span></span>
                <div class="ca-kpi-label"><?= $emi_scope === 'all_project' ? 'Project Collections' : 'EMI Collected' ?></div>
                <div class="ca-kpi-val text-success"><?= $fmtShort($et['collected']) ?></div>
                <div class="ca-kpi-sub">Posted &amp; verified receipts</div>
            </div>
            <div class="ca-kpi-card">
                <span class="ca-info-tip" tabindex="0"><i class="fa-regular fa-circle-info"></i><span class="ca-tooltip-box">Total construction &amp; project costs actually paid out (vendors, contractors, site requisitions, procurement).</span></span>
                <div class="ca-kpi-label">Project Cost Paid</div>
                <div class="ca-kpi-val text-dark"><?= $fmtShort($et['cost_paid']) ?></div>
                <div class="ca-kpi-sub">Paid project vouchers &amp; bills</div>
            </div>
            <div class="ca-kpi-card">
                <span class="ca-info-tip" tabindex="0"><i class="fa-regular fa-circle-info"></i><span class="ca-tooltip-box">Self-funding ratio (Collected ÷ Project Cost Paid × 100%). Above 100% means collections fully cover site spend.</span></span>
                <div class="ca-kpi-label">Coverage Ratio</div>
                <div class="ca-kpi-val text-dark"><?= $et['coverage_pct'] !== null ? $et['coverage_pct'] . '%' : '—' ?></div>
                <div class="ca-kpi-sub">Collections divided by paid cost</div>
            </div>
            <div class="ca-kpi-card">
                <span class="ca-info-tip" tabindex="0"><i class="fa-regular fa-circle-info"></i><span class="ca-tooltip-box">Net cash surplus or shortfall on project operations (Collected minus Paid Cost), alongside live Overdue EMI.</span></span>
                <div class="ca-kpi-label">Surplus / Shortfall</div>
                <div class="ca-kpi-val <?= $et['surplus'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= $fmtShort($et['surplus']) ?></div>
                <div class="ca-kpi-sub">Overdue EMI: <?= $fmtShort($et['overdue_emi']) ?></div>
            </div>
        </div>

        <div class="ca-card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="fw-bold" style="font-size:0.95rem;"><?= $emi_scope === 'all_project' ? 'All project collections' : 'EMI collections' ?> vs paid project costs</div>
                <div class="d-flex gap-3" style="font-size:0.78rem;">
                    <span><span class="d-inline-block rounded-1 me-1" style="width:10px;height:10px;background:#166534;"></span> Collected</span>
                    <span><span class="d-inline-block rounded-1 me-1" style="width:10px;height:10px;background:#94a3b8;"></span> Project Cost Paid</span>
                </div>
            </div>
            <?php
            $maxE = 1.0;
            foreach ($emi_cost_data['rows'] as $er) $maxE = max($maxE, $er['collected'], $er['cost_paid']);
            ?>
            <div class="ca-bar-chart">
                <?php foreach ($emi_cost_data['rows'] as $er):
                    $h1 = max(3, round(($er['collected'] / $maxE) * 124));
                    $h2 = max(3, round(($er['cost_paid'] / $maxE) * 124));
                ?>
                    <div class="ca-bar-group" style="max-width:120px;">
                        <div class="ca-bars-pair">
                            <div class="ca-bar ca-bar-in" style="height:<?= $h1 ?>px; width:20px;"></div>
                            <div class="ca-bar ca-bar-out" style="height:<?= $h2 ?>px; width:20px;"></div>
                        </div>
                        <div class="ca-bar-label" style="max-width:110px;"><?= htmlspecialchars($er['project_name']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="ca-card overflow-hidden mb-3">
            <div class="p-3 border-bottom">
                <div class="fw-bold" style="font-size:0.95rem;">EMI vs Project Cost &amp; Pending Dues</div>
                <div class="text-muted" style="font-size:0.78rem;">Includes live Overdue EMI receivables and Approved Unpaid Bills per project</div>
            </div>
            <div class="table-responsive">
                <table class="table ca-table mb-0">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th class="text-end"><?= $emi_scope === 'all_project' ? 'Collections' : 'EMI Collected' ?></th>
                            <th class="text-end">Project Cost Paid</th>
                            <th class="text-end">Coverage</th>
                            <th class="text-end">Surplus / Shortfall</th>
                            <th class="text-end text-warning">Overdue EMI (Due)</th>
                            <th class="text-end text-danger">Unpaid Approved Bills</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($emi_cost_data['rows'] as $er): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($er['project_name']) ?></td>
                                <td class="text-end text-success fw-medium"><?= $fmtBDT($er['collected']) ?></td>
                                <td class="text-end"><?= $fmtBDT($er['cost_paid']) ?></td>
                                <td class="text-end"><?= $er['coverage_pct'] !== null ? $er['coverage_pct'] . '%' : '—' ?></td>
                                <td class="text-end fw-semibold <?= $er['surplus'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= $fmtBDT($er['surplus']) ?></td>
                                <td class="text-end text-warning fw-medium"><?= $fmtBDT($er['overdue_emi']) ?></td>
                                <td class="text-end text-danger fw-medium"><?= $fmtBDT($er['unpaid_bills']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="ca-total-row">
                            <td>Total for applied scope</td>
                            <td class="text-end text-success"><?= $fmtBDT($et['collected']) ?></td>
                            <td class="text-end"><?= $fmtBDT($et['cost_paid']) ?></td>
                            <td class="text-end"><?= $et['coverage_pct'] !== null ? $et['coverage_pct'] . '%' : '—' ?></td>
                            <td class="text-end <?= $et['surplus'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= $fmtBDT($et['surplus']) ?></td>
                            <td class="text-end text-warning"><?= $fmtBDT($et['overdue_emi']) ?></td>
                            <td class="text-end text-danger"><?= $fmtBDT($et['unpaid_bills']) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Actionable Receivables & Unpaid Payables Summary -->
        <div class="row g-3">
            <div class="col-md-6">
                <div class="ca-card p-3 h-100">
                    <div class="fw-bold mb-2 text-success"><i class="fa-solid fa-hand-holding-dollar me-1"></i> Receivables &amp; Verification Pipeline</div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Pending Verification Client Receipts (<?= number_format($alerts['pending_verification']['count']) ?>)</span>
                        <span class="fw-bold text-warning"><?= $fmtBDT($alerts['pending_verification']['amount']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span class="text-muted">Overdue EMI Installments (<?= number_format($alerts['overdue_emi']['count']) ?>)</span>
                        <span class="fw-bold text-danger"><?= $fmtBDT($alerts['overdue_emi']['amount']) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="ca-card p-3 h-100">
                    <div class="fw-bold mb-2 text-danger"><i class="fa-solid fa-file-invoice-dollar me-1"></i> Approved Unpaid Payables</div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Vendor Payment Requests Due</span>
                        <span class="fw-bold"><?= $fmtBDT($alerts['unpaid_payables']['vendor']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Contractor Bill Requests Due</span>
                        <span class="fw-bold"><?= $fmtBDT($alerts['unpaid_payables']['contractor']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span class="text-muted">Approved Cashout Requests Due</span>
                        <span class="fw-bold"><?= $fmtBDT($alerts['unpaid_payables']['cashout']) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide-Over Transaction Drawer -->
<div class="ca-drawer-backdrop" id="caDrawerBackdrop" onclick="closeDrilldown()"></div>
<div class="ca-drawer" id="caDrawer">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
        <div>
            <div class="fw-bold" id="caDrawerTitle" style="font-size:1rem;">Transactions</div>
            <div class="text-muted" id="caDrawerSubtitle" style="font-size:0.78rem;">Loading...</div>
        </div>
        <button type="button" class="btn-close" onclick="closeDrilldown()"></button>
    </div>
    <div class="p-2 border-bottom d-flex gap-2 align-items-center">
        <input type="text" id="caDrawerSearch" class="form-control form-control-sm" placeholder="Search party, reference, category..." oninput="filterDrawerRows()">
        <span class="badge bg-success-subtle text-success text-nowrap px-2 py-2" id="caDrawerTotals"></span>
    </div>
    <div class="flex-grow-1 overflow-auto p-3" id="caDrawerBody">
        <div class="text-center py-5 text-muted">Loading transactions...</div>
    </div>
</div>

<script>
    function toggleCustomDateFields() {
        const sel = document.getElementById('v2PeriodSelect');
        const adv = document.getElementById('v2AdvBox');
        if (sel && sel.value === 'custom' && adv) {
            adv.classList.remove('d-none');
        }
    }

    function switchV2Tab(tabName) {
        document.querySelectorAll('.ca-tab-btn').forEach(b => {
            b.classList.toggle('active', b.getAttribute('data-tab') === tabName);
        });
        document.querySelectorAll('.v2-tab-pane').forEach(p => p.classList.add('d-none'));
        const target = document.getElementById('v2-tab-' + tabName);
        if (target) target.classList.remove('d-none');
        const hiddenTab = document.getElementById('v2ActiveTabInput');
        if (hiddenTab) hiddenTab.value = tabName;

        const url = new URL(window.location.href);
        url.searchParams.set('tab', tabName);
        window.history.replaceState({}, '', url.toString());
    }

    function setEmiScope(scope) {
        document.getElementById('v2EmiScopeInput').value = scope;
        document.getElementById('v2ActiveTabInput').value = 'emi_cost';
        document.getElementById('v2FilterForm').submit();
    }

    function toggleProjectGroup(projKey, rowEl) {
        const children = document.querySelectorAll('.' + projKey + '-child');
        const isExpanded = rowEl.classList.toggle('expanded');
        children.forEach(c => c.classList.toggle('d-none', !isExpanded));
        const chev = rowEl.querySelector('.ca-chevron');
        if (chev) chev.textContent = isExpanded ? '▾' : '›';
        const actCell = rowEl.lastElementChild;
        if (actCell) actCell.textContent = isExpanded ? 'Collapse' : 'Expand';
    }

    let currentDrawerRows = [];
    function formatBDT(num) {
        return '৳' + Number(num || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function openDrilldown(title, startDate, endDate, overrides) {
        document.getElementById('caDrawerTitle').textContent = title;
        document.getElementById('caDrawerSubtitle').textContent = startDate + ' to ' + endDate;
        document.getElementById('caDrawerBody').innerHTML = '<div class="text-center py-5 text-muted"><i class="fa-solid fa-spinner fa-spin me-1"></i> Loading records...</div>';
        document.getElementById('caDrawerTotals').textContent = '';
        document.getElementById('caDrawerSearch').value = '';
        document.getElementById('caDrawerBackdrop').classList.add('open');
        document.getElementById('caDrawer').classList.add('open');

        const form = document.getElementById('v2FilterForm');
        const params = new URLSearchParams(new FormData(form));
        params.set('row_start', startDate);
        params.set('row_end', endDate);
        if (overrides) {
            if (overrides.project_id !== undefined) params.delete('project_ids[]');
            if (overrides.category !== undefined) params.delete('categories[]');
            Object.keys(overrides).forEach(k => params.set(k, overrides[k]));
        }

        fetch('<?= $baseUrl ?>/central-statement-v2-drilldown?' + params.toString())
            .then(r => r.json())
            .then(res => {
                if (res.status !== 'success') throw new Error('Failed');
                currentDrawerRows = res.data.rows || [];
                document.getElementById('caDrawerTotals').textContent =
                    'In: ' + formatBDT(res.data.total_in) + ' | Out: ' + formatBDT(res.data.total_out);
                renderDrawerRows(currentDrawerRows);
            })
            .catch(() => {
                document.getElementById('caDrawerBody').innerHTML = '<div class="alert alert-danger">Failed to load transactions.</div>';
            });
    }

    function filterDrawerRows() {
        const q = (document.getElementById('caDrawerSearch').value || '').toLowerCase().trim();
        if (!q) return renderDrawerRows(currentDrawerRows);
        const filtered = currentDrawerRows.filter(r =>
            (r.party_name || '').toLowerCase().includes(q) ||
            (r.reference_no || '').toLowerCase().includes(q) ||
            (r.category_label || '').toLowerCase().includes(q) ||
            (r.project_name || '').toLowerCase().includes(q)
        );
        renderDrawerRows(filtered);
    }

    function renderDrawerRows(rows) {
        const body = document.getElementById('caDrawerBody');
        if (!rows.length) {
            body.innerHTML = '<div class="text-center py-4 text-muted">No matching transactions.</div>';
            return;
        }
        let html = '<div class="table-responsive"><table class="table table-sm ca-table mb-0"><thead><tr>' +
            '<th>Date</th><th>Party / Source</th><th>Category / Project</th><th class="text-end">Amount</th></tr></thead><tbody>';
        rows.forEach(r => {
            const isIn = r.flow_type === 'cash_in';
            html += `<tr>
                <td class="text-nowrap">${r.txn_date}</td>
                <td><div class="fw-semibold">${escapeHtml(r.party_name)}</div><div class="text-muted" style="font-size:0.72rem;">${escapeHtml(r.reference_no)} · ${escapeHtml((r.payment_mode || '').toUpperCase())}</div></td>
                <td><div>${escapeHtml(r.category_label)}</div><div class="text-muted" style="font-size:0.72rem;">${escapeHtml(r.project_name)} · ${escapeHtml(r.branch_name)}</div></td>
                <td class="text-end fw-semibold ${isIn ? 'text-success' : 'text-danger'}">${isIn ? '+' : '−'}${formatBDT(r.amount)}</td>
            </tr>`;
        });
        html += '</tbody></table></div>';
        body.innerHTML = html;
    }

    function closeDrilldown() {
        document.getElementById('caDrawerBackdrop').classList.remove('open');
        document.getElementById('caDrawer').classList.remove('open');
    }

    function escapeHtml(str) {
        return String(str || '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
    }

    function toggleMultiDropdown(id) {
        document.querySelectorAll('.ca-ms-menu').forEach(m => {
            if (m.parentElement.id !== id) m.classList.remove('open');
        });
        const menu = document.querySelector('#' + id + ' .ca-ms-menu');
        if (menu) menu.classList.toggle('open');
    }

    function filterMultiOptions(id, q) {
        const needle = (q || '').toLowerCase().trim();
        document.querySelectorAll('#' + id + ' .ca-ms-item').forEach(item => {
            const txt = item.textContent.toLowerCase();
            item.style.display = (!needle || txt.includes(needle)) ? 'flex' : 'none';
        });
    }

    function setAllMultiOptions(id, checked) {
        document.querySelectorAll('#' + id + ' input[type="checkbox"]').forEach(cb => {
            if (cb.closest('.ca-ms-item').style.display !== 'none') {
                cb.checked = checked;
            }
        });
        updateMultiLabel(id);
    }

    function updateMultiLabel(id) {
        const wrap = document.getElementById(id);
        if (!wrap) return;
        const labelEl = wrap.querySelector('.ca-ms-trigger-text');
        const checked = Array.from(wrap.querySelectorAll('input[type="checkbox"]:checked'));
        if (!checked.length) {
            labelEl.textContent = labelEl.getAttribute('data-default');
        } else if (checked.length === 1) {
            labelEl.textContent = checked[0].getAttribute('data-label');
        } else {
            labelEl.textContent = checked.length + ' selected';
        }
    }

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.ca-ms-wrap')) {
            document.querySelectorAll('.ca-ms-menu.open').forEach(m => m.classList.remove('open'));
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        updateMultiLabel('msProjects');
        updateMultiLabel('msCategories');
    });
</script>

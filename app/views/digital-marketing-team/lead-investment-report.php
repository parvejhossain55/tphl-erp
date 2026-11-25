<?php
/**
 * Leads Investment Report (Overview, Daily Spend Entry, Ad Performance, Statements)
 * TPHL ERP - Management & Head of Digital Marketing (Role 10)
 */
$baseUrl = $GLOBALS['base_url'] ?? '/TPHL-ERP';
$todayDate = date('Y-m-d');
$todayLabel = date('d M Y');
?>
<style>
    :root {
        --lir-green: #0a7c48;
        --lir-green-dark: #076037;
        --lir-green-soft: #e8f5ee;
        --lir-green-border: #c6e6d4;
        --lir-bg: #f5f7f6;
        --lir-card: #ffffff;
        --lir-border: #e2e8f0;
        --lir-text: #0f172a;
        --lir-muted: #64748b;
        --lir-amber-bg: #fef3c7;
        --lir-amber-border: #fde68a;
        --lir-amber-text: #92400e;
    }

    .lir-wrapper {
        background: var(--lir-bg);
        padding: 1rem 1.25rem 3rem;
        min-height: calc(100vh - 70px);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        color: var(--lir-text);
    }

    /* Top Brand Strip matching screenshot */
    .lir-brand-banner {
        background: var(--lir-green);
        color: #ffffff;
        padding: 14px 22px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 18px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    }
    .lir-brand-logo {
        font-size: 20px;
        font-weight: 800;
        letter-spacing: 0.04em;
    }
    .lir-brand-tagline {
        font-size: 11px;
        font-weight: 700;
        line-height: 1.35;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        opacity: 0.95;
    }

    .lir-breadcrumb {
        font-size: 12px;
        font-weight: 600;
        color: var(--lir-green);
        margin-bottom: 6px;
    }

    .lir-page-title {
        font-size: 28px;
        font-weight: 800;
        color: #111827;
        margin-bottom: 4px;
        letter-spacing: -0.02em;
    }

    .lir-page-subtitle {
        font-size: 13px;
        color: var(--lir-muted);
        margin-bottom: 18px;
    }

    /* Primary 4-Tab Bar */
    .lir-tabs-row {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 14px;
    }

    .lir-tab-btn {
        background: #ffffff;
        color: #1e293b;
        border: 1px solid #d1d5db;
        border-radius: 7px;
        padding: 8px 16px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .lir-tab-btn:hover {
        border-color: var(--lir-green);
        color: var(--lir-green);
    }
    .lir-tab-btn.active {
        background: var(--lir-green);
        color: #ffffff;
        border-color: var(--lir-green);
        font-weight: 700;
    }

    /* Period Sub-Pills (Daily, Weekly, Monthly, Yearly, Custom range) */
    .lir-period-row {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 14px;
    }

    .lir-period-btn {
        background: #ffffff;
        color: #1e293b;
        border: 1px solid #d1d5db;
        border-radius: 7px;
        padding: 6px 14px;
        font-size: 12.5px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .lir-period-btn:hover {
        border-color: var(--lir-green);
    }
    .lir-period-btn.active {
        background: var(--lir-green);
        color: #ffffff;
        border-color: var(--lir-green);
        font-weight: 700;
    }

    /* Filter Bar Card */
    .lir-filter-card {
        background: #ffffff;
        border: 1px solid var(--lir-border);
        border-radius: 8px;
        padding: 14px 16px;
        margin-bottom: 14px;
        display: grid;
        grid-template-columns: 1.25fr 1fr 1fr 1.2fr auto;
        gap: 12px;
        align-items: end;
    }
    @media (max-width: 1100px) {
        .lir-filter-card {
            grid-template-columns: 1fr 1fr;
        }
    }
    @media (max-width: 576px) {
        .lir-filter-card {
            grid-template-columns: 1fr;
        }
    }

    .lir-field-label {
        display: block;
        font-size: 10.5px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 5px;
    }

    .lir-input, .lir-select {
        width: 100%;
        height: 38px;
        padding: 6px 12px;
        font-size: 13px;
        color: #0f172a;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        outline: none;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .lir-input:focus, .lir-select:focus {
        border-color: var(--lir-green);
        box-shadow: 0 0 0 2px rgba(10, 124, 72, 0.14);
    }
    .lir-input[readonly] {
        background: #f8fafc;
        color: #334155;
    }

    .lir-btn-green {
        background: var(--lir-green);
        color: #ffffff;
        border: 1px solid var(--lir-green);
        border-radius: 6px;
        height: 38px;
        padding: 0 18px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: background 0.15s ease;
        white-space: nowrap;
    }
    .lir-btn-green:hover {
        background: var(--lir-green-dark);
        color: #ffffff;
    }

    .lir-btn-outline {
        background: #ffffff;
        color: #1e293b;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        height: 38px;
        padding: 0 16px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: all 0.15s ease;
        white-space: nowrap;
    }
    .lir-btn-outline:hover {
        border-color: #94a3b8;
        background: #f8fafc;
    }

    /* 4 KPI Cards Grid */
    .lir-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        margin-bottom: 14px;
    }
    @media (max-width: 992px) {
        .lir-kpi-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 576px) {
        .lir-kpi-grid { grid-template-columns: 1fr; }
    }

    .lir-kpi-card {
        background: #ffffff;
        border: 1px solid var(--lir-border);
        border-radius: 8px;
        padding: 16px 18px;
    }
    .lir-kpi-label {
        font-size: 12px;
        color: #64748b;
        margin-bottom: 6px;
        font-weight: 500;
    }
    .lir-kpi-value {
        font-size: 28px;
        font-weight: 800;
        color: var(--lir-green);
        line-height: 1.15;
    }
    .lir-kpi-sub {
        font-size: 11px;
        color: #94a3b8;
        margin-top: 4px;
    }

    /* Alert Banner */
    .lir-alert-banner {
        background: #fef3c7;
        border: 1px solid #fde68a;
        border-radius: 7px;
        padding: 12px 16px;
        margin-bottom: 16px;
    }
    .lir-alert-title {
        font-size: 12.5px;
        font-weight: 700;
        color: #92400e;
        margin-bottom: 2px;
    }
    .lir-alert-desc {
        font-size: 12px;
        color: #78350f;
    }

    /* Section Card */
    .lir-section-card {
        background: #ffffff;
        border: 1px solid var(--lir-border);
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 16px;
    }
    .lir-section-title {
        font-size: 16px;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 4px;
    }
    .lir-section-sub {
        font-size: 12px;
        color: #64748b;
        margin-bottom: 16px;
    }

    /* Horizontal Investment Bars */
    .lir-bar-row {
        display: grid;
        grid-template-columns: 180px 1fr auto;
        align-items: center;
        gap: 14px;
        margin-bottom: 10px;
    }
    @media (max-width: 768px) {
        .lir-bar-row {
            grid-template-columns: 1fr;
            gap: 4px;
        }
    }
    .lir-bar-project {
        font-size: 13px;
        font-weight: 700;
        color: #1e293b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .lir-bar-track {
        background: #f1f5f9;
        height: 16px;
        border-radius: 3px;
        overflow: hidden;
        position: relative;
    }
    .lir-bar-fill {
        background: var(--lir-green);
        height: 100%;
        border-radius: 3px;
        transition: width 0.3s ease;
    }
    .lir-bar-metrics {
        font-size: 12.5px;
        color: #334155;
        font-weight: 500;
        white-space: nowrap;
    }

    /* Tables */
    .lir-table-wrap {
        width: 100%;
        overflow-x: auto;
    }
    .lir-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    .lir-table thead th {
        background: var(--lir-green-soft);
        color: var(--lir-green);
        font-size: 11.5px;
        font-weight: 700;
        padding: 11px 14px;
        text-align: left;
        border-bottom: 1px solid var(--lir-green-border);
        white-space: nowrap;
    }
    .lir-table tbody td {
        padding: 13px 14px;
        border-bottom: 1px solid #f1f5f9;
        color: #1e293b;
        vertical-align: middle;
    }
    .lir-table tbody tr:hover td {
        background: #f8fafc;
    }
    .lir-table tfoot td {
        background: var(--lir-green-soft);
        color: var(--lir-green);
        font-weight: 800;
        padding: 11px 14px;
        border-top: 1px solid var(--lir-green-border);
    }

    /* Green Match Preview Strip in Spend Form */
    .lir-match-strip {
        background: var(--lir-green-soft);
        border: 1px solid var(--lir-green-border);
        border-radius: 7px;
        padding: 14px 18px;
        display: grid;
        grid-template-columns: 1fr 1fr 1.5fr;
        gap: 16px;
        margin-top: 16px;
        margin-bottom: 12px;
    }
    @media (max-width: 768px) {
        .lir-match-strip { grid-template-columns: 1fr; }
    }
    .lir-match-label {
        font-size: 12px;
        font-weight: 700;
        color: var(--lir-green);
        margin-bottom: 2px;
    }
    .lir-match-val {
        font-size: 16px;
        font-weight: 800;
        color: var(--lir-green);
    }

    /* Decision Notes Box */
    .lir-decision-box {
        background: var(--lir-green-soft);
        border: 1px solid var(--lir-green-border);
        border-radius: 8px;
        padding: 18px 20px;
        margin-top: 16px;
    }
    .lir-decision-title {
        font-size: 16px;
        font-weight: 800;
        color: var(--lir-green);
        margin-bottom: 10px;
    }
    .lir-decision-item {
        font-size: 13px;
        color: #1e293b;
        margin-bottom: 8px;
    }

    .lir-action-link {
        color: #0f172a;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
    }
    .lir-action-link:hover {
        color: var(--lir-green);
        text-decoration: underline;
    }

    /* Compact Single / Custom Range Mode Pills in Spend Form */
    .lir-mode-toggle {
        display: inline-flex;
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        border-radius: 5px;
        padding: 1px;
    }
    .lir-mode-pill {
        border: none;
        background: transparent;
        color: #475569;
        font-size: 10px;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 4px;
        cursor: pointer;
        line-height: 1.3;
        transition: all 0.15s ease;
    }
    .lir-mode-pill.active {
        background: var(--lir-green);
        color: #ffffff;
    }
    .lir-days-badge {
        background: var(--lir-green-soft);
        color: var(--lir-green);
        border: 1px solid var(--lir-green-border);
        font-size: 10px;
        font-weight: 700;
        padding: 1px 6px;
        border-radius: 4px;
        white-space: nowrap;
    }

    /* Print styling for Statement */
    @media print {
        body * {
            visibility: hidden;
        }
        #statementPrintableCard, #statementPrintableCard * {
            visibility: visible;
        }
        #statementPrintableCard {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            border: none !important;
            box-shadow: none !important;
        }
    }
</style>

<div class="lir-wrapper">
    <!-- Top Brand Strip -->
    <div class="lir-brand-banner">
        <div class="lir-brand-logo">TPHL</div>
        <div class="lir-brand-tagline">
            THE PREMIUM HOMES LTD.<br>
            YOUR DREAM HOME AWAITS
        </div>
    </div>

    <!-- Breadcrumb & Dynamic Header -->
    <div class="lir-breadcrumb">Marketing / Leads Investment Report</div>
    <h1 class="lir-page-title" id="lirMainHeading">Leads Investment Report</h1>
    <p class="lir-page-subtitle" id="lirMainSubheading">
        Spend &rarr; matched paid leads &rarr; automatic CPL &rarr; investment decisions. &bull; Real-time ERP data
    </p>

    <!-- Primary 4 Tabs -->
    <div class="lir-tabs-row">
        <button type="button" class="lir-tab-btn active" id="tabBtn-overview" onclick="switchLirTab('overview')">Overview</button>
        <button type="button" class="lir-tab-btn" id="tabBtn-spend" onclick="switchLirTab('spend')">Daily spend entry</button>
        <button type="button" class="lir-tab-btn" id="tabBtn-adperf" onclick="switchLirTab('adperf')">Ad performance</button>
        <button type="button" class="lir-tab-btn" id="tabBtn-statements" onclick="switchLirTab('statements')">Statements</button>
    </div>

    <!-- Shared Period & Filter Bar (Shown on Overview, Ad performance, Statements) -->
    <div id="sharedFilterSection">
        <div class="lir-period-row">
            <button type="button" class="lir-period-btn active" data-period="daily" onclick="setLirPeriod('daily')">Daily</button>
            <button type="button" class="lir-period-btn" data-period="weekly" onclick="setLirPeriod('weekly')">Weekly</button>
            <button type="button" class="lir-period-btn" data-period="monthly" onclick="setLirPeriod('monthly')">Monthly</button>
            <button type="button" class="lir-period-btn" data-period="yearly" onclick="setLirPeriod('yearly')">Yearly</button>
            <button type="button" class="lir-period-btn" data-period="custom" onclick="setLirPeriod('custom')">Custom range</button>
        </div>

        <div class="lir-filter-card">
            <div>
                <label class="lir-field-label">DATE RANGE</label>
                <div class="d-flex align-items-center gap-1">
                    <input type="date" id="filterStartDate" class="lir-input" value="<?= htmlspecialchars($todayDate) ?>" onchange="onFilterDateInputChanged()">
                    <span class="text-muted fw-bold" id="filterDateDash" style="display:none;">&ndash;</span>
                    <input type="date" id="filterEndDate" class="lir-input" value="<?= htmlspecialchars($todayDate) ?>" style="display:none;" onchange="onFilterDateInputChanged()">
                </div>
            </div>

            <div>
                <label class="lir-field-label">PROJECT</label>
                <select id="filterProjectId" class="lir-select">
                    <option value="">All projects</option>
                    <option value="0">Common / Unmapped Project</option>
                    <?php foreach ($projects as $proj): ?>
                        <option value="<?= (int)$proj['id'] ?>"><?= htmlspecialchars($proj['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="lir-field-label">PLATFORM</label>
                <select id="filterPlatform" class="lir-select">
                    <option value="">All platforms</option>
                    <option value="Meta Ads">Meta Ads</option>
                    <option value="Google Ads">Google Ads</option>
                    <option value="WhatsApp Ads">WhatsApp Ads</option>
                    <option value="TikTok Ads">TikTok Ads</option>
                </select>
            </div>

            <div>
                <label class="lir-field-label">AD ACCOUNT / CAMPAIGN</label>
                <input type="text" id="filterAccountCampaign" class="lir-input" list="datalistCampaignsAndAccounts" placeholder="All accounts / campaigns">
                <datalist id="datalistCampaignsAndAccounts">
                    <?php foreach ($adAccounts as $acc): ?>
                        <option value="<?= htmlspecialchars($acc) ?>"></option>
                    <?php endforeach; ?>
                    <?php foreach ($campaigns as $camp): ?>
                        <option value="<?= htmlspecialchars($camp) ?>"></option>
                    <?php endforeach; ?>
                </datalist>
            </div>

            <div>
                <button type="button" class="lir-btn-green" onclick="loadLirDashboardData()">Apply</button>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- TAB 1: OVERVIEW                                                     -->
    <!-- =================================================================== -->
    <div id="tabPane-overview" class="lir-tab-pane">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div class="text-muted" style="font-size: 12.5px;">
                Reporting currency: <strong>USD</strong> &bull; Timezone: <strong>Asia/Dhaka</strong>
                &bull; <span id="overviewScopeDateLabel"><?= htmlspecialchars($todayLabel . ' – ' . $todayLabel) ?></span>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <button type="button" class="lir-btn-outline" onclick="openBudgetModal()">
                    <i class="fa-solid fa-sliders text-success"></i> Set budget / target CPL
                </button>
                <button type="button" class="lir-btn-outline" onclick="switchLirTab('statements')">
                    Download statement
                </button>
                <button type="button" class="lir-btn-green" onclick="switchLirTab('spend')">
                    + Add daily spend
                </button>
            </div>
        </div>

        <!-- 4 KPI Cards -->
        <div class="lir-kpi-grid">
            <div class="lir-kpi-card">
                <div class="lir-kpi-label">Actual spend</div>
                <div class="lir-kpi-value" id="kpiActualSpend">$0.00</div>
                <div class="lir-kpi-sub" id="kpiActualSpendBdt">BDT 0.00</div>
            </div>
            <div class="lir-kpi-card">
                <div class="lir-kpi-label">Matched paid leads</div>
                <div class="lir-kpi-value" id="kpiMatchedPaidLeads">0</div>
                <div class="lir-kpi-sub">Synced by lead creation date</div>
            </div>
            <div class="lir-kpi-card">
                <div class="lir-kpi-label">Weighted CPL</div>
                <div class="lir-kpi-value" id="kpiWeightedCpl">&mdash;</div>
                <div class="lir-kpi-sub">Actual spend &divide; paid leads</div>
            </div>
            <div class="lir-kpi-card">
                <div class="lir-kpi-label">Budget utilisation</div>
                <div class="lir-kpi-value" id="kpiBudgetUtilisation">0.0%</div>
                <div class="lir-kpi-sub" id="kpiBudgetSub">Allocated budget: $0.00</div>
            </div>
        </div>

        <!-- Dynamic Yellow Alert Banner -->
        <div class="lir-alert-banner" id="overviewAlertBanner" style="display: none;">
            <div class="lir-alert-title" id="overviewAlertTitle"></div>
            <div class="lir-alert-desc" id="overviewAlertDesc"></div>
        </div>

        <!-- Where is investment working? -->
        <div class="lir-section-card">
            <div class="lir-section-title">Where is investment working?</div>
            <div class="lir-section-sub">Selected period &bull; Lower CPL alone does not establish lead quality.</div>
            <div id="investmentWorkingBarsContainer">
                <div class="text-muted py-3 text-center">Loading investment distribution...</div>
            </div>
        </div>

        <!-- Project x platform performance -->
        <div class="lir-section-card mb-2">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div class="lir-section-title mb-0">Project &times; platform performance</div>
                <button type="button" class="btn btn-sm btn-link text-success text-decoration-none fw-semibold p-0" style="font-size: 12px;" onclick="openBudgetModal()">
                    <i class="fa-solid fa-pen-to-square me-1"></i>Configure Project Budgets &amp; Target CPL
                </button>
            </div>
            <div class="lir-table-wrap">
                <table class="lir-table">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Platform</th>
                            <th>Budget</th>
                            <th>Actual spend</th>
                            <th>Paid leads</th>
                            <th>CPL</th>
                            <th>Remaining</th>
                            <th>Decision</th>
                        </tr>
                    </thead>
                    <tbody id="overviewProjectPlatformTbody">
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">Loading performance data...</td>
                        </tr>
                    </tbody>
                    <tfoot id="overviewProjectPlatformTfoot"></tfoot>
                </table>
            </div>
        </div>
        <div class="text-muted mt-2" style="font-size: 11.5px;">
            Paid CPL = matched actual spend &divide; matched paid leads. No leads &rarr; &ldquo;&mdash; / No leads&rdquo;, never zero CPL.
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- TAB 2: DAILY SPEND ENTRY                                            -->
    <!-- =================================================================== -->
    <div id="tabPane-spend" class="lir-tab-pane" style="display: none;">
        <div class="lir-section-card">
            <input type="hidden" id="spendEditId" value="">
            <div class="row g-3">
                <!-- Row 1 -->
                <div class="col-lg-3 col-md-6" id="entryDateCol">
                    <div class="d-flex justify-content-between align-items-center" style="margin-bottom: 5px;">
                        <label class="lir-field-label mb-0" id="entrySpendDateLabel">SPEND DATE *</label>
                        <div class="d-inline-flex align-items-center gap-1">
                            <span class="lir-days-badge" id="entryDaysBadge" style="display:none;">1 day</span>
                            <div class="lir-mode-toggle" role="group" aria-label="Spend date mode">
                                <button type="button" class="lir-mode-pill active" id="spendModeSingleBtn" onclick="setEntryDateMode('single')">Single</button>
                                <button type="button" class="lir-mode-pill" id="spendModeRangeBtn" onclick="setEntryDateMode('range')">Custom range</button>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <input type="date" id="entrySpendDate" class="lir-input" value="<?= htmlspecialchars($todayDate) ?>" onchange="onEntryDateChanged()">
                        <span class="text-muted fw-bold" id="entryDateRangeDash" style="display:none;">&ndash;</span>
                        <input type="date" id="entrySpendEndDate" class="lir-input" value="<?= htmlspecialchars($todayDate) ?>" style="display:none;" onchange="onEntryDateChanged()">
                    </div>
                </div>
                <div class="col-lg-3 col-md-6" id="entryPlatformCol">
                    <label class="lir-field-label">PLATFORM *</label>
                    <select id="entryPlatform" class="lir-select" onchange="updateSpendMatchPreview()">
                        <option value="Meta Ads">Meta Ads</option>
                        <option value="Google Ads">Google Ads</option>
                        <option value="WhatsApp Ads">WhatsApp Ads</option>
                        <option value="TikTok Ads">TikTok Ads</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-6" id="entryAccountCol">
                    <label class="lir-field-label">AD ACCOUNT *</label>
                    <input type="text" id="entryAdAccount" class="lir-input" list="datalistAdAccounts" value="" placeholder="e.g. TPHL_ATI" oninput="updateSpendMatchPreview()">
                    <datalist id="datalistAdAccounts">
                        <?php foreach ($adAccounts as $acc): ?>
                            <option value="<?= htmlspecialchars($acc) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-lg-3 col-md-6" id="entryProjectCol">
                    <label class="lir-field-label">PROJECT *</label>
                    <select id="entryProjectId" class="lir-select" onchange="onEntryProjectChanged()">
                        <option value="">-- Select Project --</option>
                        <option value="0">Common / Unmapped Project</option>
                        <?php foreach ($projects as $proj): ?>
                            <option value="<?= (int)$proj['id'] ?>"><?= htmlspecialchars($proj['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Row 2 -->
                <div class="col-lg-4 col-md-6">
                    <label class="lir-field-label">CAMPAIGN *</label>
                    <input type="text" id="entryCampaignName" class="lir-input" list="datalistProjectCampaigns" placeholder="e.g. Royal City • Lead Generation" oninput="updateSpendMatchPreview()">
                    <datalist id="datalistProjectCampaigns">
                        <?php foreach ($campaigns as $camp): ?>
                            <option value="<?= htmlspecialchars($camp) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-lg-4 col-md-6">
                    <label class="lir-field-label">AD SET</label>
                    <input type="text" id="entryAdSetName" class="lir-input" placeholder="e.g. Prospecting">
                </div>
                <div class="col-lg-4 col-md-12">
                    <label class="lir-field-label">AD / AD ID</label>
                    <input type="text" id="entryAdInput" class="lir-input" placeholder="e.g. RC Video 01 / DEMO-AD-001" oninput="updateSpendMatchPreview()">
                </div>

                <div class="col-12 mt-1 mb-1">
                    <div class="text-muted" style="font-size: 11.5px;">
                        Need ad-level decisions? Select the ad ID. Project-only spend remains visible but cannot be assigned to individual ads.
                    </div>
                </div>

                <!-- Row 3 -->
                <div class="col-lg-3 col-md-6">
                    <label class="lir-field-label">CURRENCY *</label>
                    <select id="entryCurrency" class="lir-select" onchange="recalcBdtEquivalent()">
                        <option value="USD">USD</option>
                        <option value="BDT">BDT</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="lir-field-label">ACTUAL SPEND *</label>
                    <input type="number" step="0.01" min="0" id="entryActualSpend" class="lir-input" value="" placeholder="e.g. 50.00" oninput="recalcBdtEquivalent()">
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="lir-field-label">FX RATE &bull; BDT PER USD</label>
                    <input type="number" step="0.01" min="1" id="entryFxRate" class="lir-input" value="133.00" oninput="recalcBdtEquivalent()">
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="lir-field-label">BDT EQUIVALENT &bull; AUTO</label>
                    <input type="text" id="entryBdtEquivalent" class="lir-input" value="0.00" readonly>
                </div>

                <!-- Row 4 -->
                <div class="col-lg-6 col-md-6">
                    <label class="lir-field-label">SOURCE / REFERENCE</label>
                    <input type="text" id="entrySourceRef" class="lir-input" value="" placeholder="e.g. Meta daily spend report">
                </div>
                <div class="col-lg-6 col-md-6">
                    <label class="lir-field-label">NOTES</label>
                    <input type="text" id="entryNotes" class="lir-input" placeholder="Optional context or correction reason">
                </div>
            </div>

            <!-- Real-time Green Match Strip -->
            <div class="lir-match-strip">
                <div>
                    <div class="lir-match-label">Matched paid leads</div>
                    <div class="lir-match-val" id="previewMatchedLeads">0</div>
                </div>
                <div>
                    <div class="lir-match-label">Calculated CPL</div>
                    <div class="lir-match-val" id="previewCalculatedCpl">&mdash;</div>
                </div>
                <div>
                    <div class="lir-match-label" style="color: #334155; font-weight: 600;">Match basis</div>
                    <div style="font-size: 13px; color: #1e293b;" id="previewMatchBasis">Same date + project + platform + ad ID</div>
                </div>
            </div>

            <div class="text-muted mb-3" style="font-size: 11.5px;" id="duplicateNoticeText">
                Duplicate date/account/ad/project entries prompt an update. No double counting with platform or project totals.
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <button type="button" class="lir-btn-outline" onclick="resetSpendForm()">Cancel</button>
                <button type="button" class="lir-btn-outline" onclick="submitSpendEntry(true)">Save &amp; add another</button>
                <button type="button" class="lir-btn-green" id="btnSaveSpendMain" onclick="submitSpendEntry(false)">Save spend</button>
            </div>
        </div>

        <!-- Today's / Selected Period's Spend Entries Table -->
        <div class="lir-section-card">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div class="lir-section-title mb-0" id="spendEntriesTableHeading">Today's spend entries</div>
                <span class="badge bg-light text-secondary border" id="spendEntriesCountBadge">0 entries</span>
            </div>
            <div class="lir-table-wrap">
                <table class="lir-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Project / platform</th>
                            <th>Ad</th>
                            <th>Spend</th>
                            <th>Paid leads</th>
                            <th>CPL</th>
                            <th>Updated by</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="spendEntriesTbody">
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No spend entries found for the selected period.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- TAB 3: AD PERFORMANCE                                               -->
    <!-- =================================================================== -->
    <div id="tabPane-adperf" class="lir-tab-pane" style="display: none;">
        <div class="lir-section-card">
            <div class="lir-section-title">Ads ranked by CPL</div>
            <div id="adPerfUnmappedSub" class="mb-3" style="font-size: 12.5px; font-weight: 700; color: #b45309; display: none;"></div>
            <div class="lir-table-wrap">
                <table class="lir-table">
                    <thead>
                        <tr>
                            <th>Ad / ID</th>
                            <th>Project</th>
                            <th>Platform</th>
                            <th>Spend</th>
                            <th>Paid leads</th>
                            <th>CPL</th>
                            <th>Target CPL</th>
                            <th>Signal</th>
                        </tr>
                    </thead>
                    <tbody id="adPerformanceTbody">
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">Loading ad performance rankings...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Decision Notes Card -->
        <div class="lir-decision-box">
            <div class="lir-decision-title">Decision notes</div>
            <div id="adPerfDecisionNotesList"></div>
            <div class="text-muted mt-2" style="font-size: 11.5px;">
                Missing spend &ne; zero spend. Organic leads are shown separately; unidentified sources remain unmatched.
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- TAB 4: STATEMENTS                                                   -->
    <!-- =================================================================== -->
    <div id="tabPane-statements" class="lir-tab-pane" style="display: none;">
        <!-- Export Toolbar -->
        <div class="lir-section-card py-3 px-3 mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2" style="max-width: 680px;">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <button type="button" class="lir-btn-green" onclick="downloadStatementPdf()">Download PDF</button>
                <button type="button" class="lir-btn-outline" onclick="exportStatementTable('excel')">Export Excel</button>
                <button type="button" class="lir-btn-outline" onclick="exportStatementTable('csv')">Export CSV</button>
            </div>
            <div class="text-muted" style="font-size: 12px;">
                Scope: current filters &bull; Detail: project &times; platform
            </div>
        </div>

        <!-- Printable Official Statement Card -->
        <div class="lir-section-card p-4" id="statementPrintableCard">
            <div style="font-size: 18px; font-weight: 800; color: var(--lir-green); letter-spacing: 0.02em; margin-bottom: 6px;">
                THE PREMIUM HOMES LTD.
            </div>
            <div style="font-size: 22px; font-weight: 800; color: #0f172a; margin-bottom: 6px;">
                Leads investment statement
            </div>
            <div class="text-muted mb-1" style="font-size: 12.5px;" id="stmtScopeLine">
                <?= htmlspecialchars($todayLabel) ?> &bull; Asia/Dhaka &bull; All projects &bull; All platforms &bull; USD
            </div>
            <div class="text-muted mb-3" style="font-size: 11px;">
                LIVE ERP REPORT &bull; Matched against automated campaign leads
            </div>

            <div class="lir-table-wrap mb-3">
                <table class="lir-table" id="statementExportTable">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Platform</th>
                            <th>Actual spend</th>
                            <th>Paid leads</th>
                            <th>CPL</th>
                            <th>Budget</th>
                            <th>Remaining</th>
                        </tr>
                    </thead>
                    <tbody id="statementTbody">
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Loading statement...</td>
                        </tr>
                    </tbody>
                    <tfoot id="statementTfoot"></tfoot>
                </table>
            </div>

            <div style="font-size: 14px; font-weight: 800; color: var(--lir-green); margin-bottom: 10px;" id="stmtTotalSummaryLine">
                Total spend: USD 0.00 &bull; BDT equivalent: 0.00 at rate 133.00
            </div>

            <div class="text-muted mb-1" style="font-size: 11.5px;" id="stmtMethodLine">
                Method: summed spend &divide; summed matched paid leads. Organic leads excluded. Ad-unmapped spend: $0.00.
            </div>
            <div class="text-muted mb-3" style="font-size: 11.5px;">
                For multi-day exports, BDT total uses each entry's saved FX rate. Page headers repeat; grand total appears once.
            </div>

            <div class="d-flex justify-content-between align-items-center text-muted pt-2 border-top" style="font-size: 11.5px; max-width: 560px;">
                <span>Generated by: <strong><?= htmlspecialchars($currentUserName) ?></strong> &bull; <span id="stmtGeneratedAtText"><?= date('d M Y, h:i A') ?></span></span>
                <span>Page 1 of 1 &bull; Confidential</span>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Set Project x Platform Budget & Target CPL -->
<div class="modal fade" id="budgetSetupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-white border-bottom py-3">
                <h6 class="modal-title fw-bold text-dark mb-0">
                    <i class="fa-solid fa-sliders text-success me-1"></i> Set Project Budget &amp; Target CPL
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="mb-3">
                    <label class="lir-field-label">PROJECT *</label>
                    <select id="budgetModalProjectId" class="lir-select">
                        <option value="">-- Select Project --</option>
                        <option value="0">Common / Unmapped Project</option>
                        <?php foreach ($projects as $proj): ?>
                            <option value="<?= (int)$proj['id'] ?>"><?= htmlspecialchars($proj['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="lir-field-label">PLATFORM *</label>
                    <select id="budgetModalPlatform" class="lir-select">
                        <option value="Meta Ads">Meta Ads</option>
                        <option value="Google Ads">Google Ads</option>
                        <option value="WhatsApp Ads">WhatsApp Ads</option>
                        <option value="TikTok Ads">TikTok Ads</option>
                    </select>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="lir-field-label">DAILY BUDGET (USD) *</label>
                        <input type="number" step="0.01" min="0" id="budgetModalDailyUsd" class="lir-input" value="100.00" oninput="document.getElementById('budgetModalMonthlyUsd').value = (parseFloat(this.value || 0) * 30).toFixed(2)">
                    </div>
                    <div class="col-6">
                        <label class="lir-field-label">MONTHLY BUDGET (USD)</label>
                        <input type="number" step="0.01" min="0" id="budgetModalMonthlyUsd" class="lir-input" value="3000.00">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="lir-field-label">TARGET CPL THRESHOLD (USD) *</label>
                    <input type="number" step="0.01" min="0.01" id="budgetModalTargetCpl" class="lir-input" value="0.45">
                    <div class="text-muted mt-1" style="font-size: 11px;">
                        Used to evaluate whether Project &amp; Ad CPL is "On target", "Efficient", or "Above target CPL".
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="lir-btn-outline" style="height:34px;" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="lir-btn-green" style="height:34px;" onclick="saveBudgetAndTargetCpl()">Save Settings</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Spend Entry Edit History -->
<div class="modal fade" id="spendHistoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-white border-bottom py-3">
                <h6 class="modal-title fw-bold text-dark mb-0">
                    <i class="fa-solid fa-clock-rotate-left text-success me-1"></i> Spend Entry Audit History
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="lir-table-wrap">
                    <table class="lir-table">
                        <thead>
                            <tr>
                                <th>Date &amp; Time</th>
                                <th>Action</th>
                                <th>Old Spend (USD)</th>
                                <th>New Spend (USD)</th>
                                <th>FX Rate</th>
                                <th>Updated By</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody id="spendHistoryModalTbody">
                            <tr>
                                <td colspan="7" class="text-center py-3 text-muted">Loading history...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const lirBaseUrl = <?= json_encode($baseUrl) ?>;
    let currentLirTab = 'overview';
    let currentLirPeriod = 'daily';
    let entryDateMode = 'single';
    let lastDashboardData = null;
    let previewDebounceTimer = null;

    document.addEventListener('DOMContentLoaded', () => {
        recalcBdtEquivalent();
        loadLirDashboardData();
    });

    function switchLirTab(tabName) {
        currentLirTab = tabName;
        ['overview', 'spend', 'adperf', 'statements'].forEach(t => {
            const btn = document.getElementById('tabBtn-' + t);
            const pane = document.getElementById('tabPane-' + t);
            if (btn) btn.classList.toggle('active', t === tabName);
            if (pane) pane.style.display = (t === tabName) ? 'block' : 'none';
        });

        // Show/hide shared filter bar (Daily spend entry has its own date/project form)
        const filterSec = document.getElementById('sharedFilterSection');
        if (filterSec) {
            filterSec.style.display = (tabName === 'spend') ? 'none' : 'block';
        }

        // Dynamic Title & Subtitle matching the 4 screenshots
        const h1 = document.getElementById('lirMainHeading');
        const sub = document.getElementById('lirMainSubheading');
        if (tabName === 'overview') {
            h1.textContent = 'Leads Investment Report';
            sub.innerHTML = 'Spend &rarr; matched paid leads &rarr; automatic CPL &rarr; investment decisions. &bull; Real-time ERP data';
        } else if (tabName === 'spend') {
            h1.textContent = 'Add daily actual spend';
            sub.textContent = 'Single-date or custom date-range entry by platform, project and ad. Budget is a separate planning amount.';
            updateSpendMatchPreview();
        } else if (tabName === 'adperf') {
            h1.textContent = 'Ad performance';
            sub.textContent = 'Compare actual spend and matched paid leads at ad level.';
        } else if (tabName === 'statements') {
            h1.textContent = 'Investment statement';
            sub.textContent = 'Review the exact reporting scope, then export PDF, Excel or CSV.';
        }
    }

    function setLirPeriod(period) {
        currentLirPeriod = period;
        document.querySelectorAll('.lir-period-btn').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-period') === period);
        });

        const endInput = document.getElementById('filterEndDate');
        const dash = document.getElementById('filterDateDash');
        if (period === 'custom') {
            endInput.style.display = 'block';
            dash.style.display = 'inline';
        } else {
            endInput.style.display = 'none';
            dash.style.display = 'none';
        }

        loadLirDashboardData();
    }

    function onFilterDateInputChanged() {
        if (currentLirPeriod !== 'custom' && currentLirPeriod !== 'daily') {
            // Keep period or auto-reload
        }
        loadLirDashboardData();
    }

    async function loadLirDashboardData() {
        const startDate = document.getElementById('filterStartDate').value;
        const endDate = document.getElementById('filterEndDate').value || startDate;
        const projectId = document.getElementById('filterProjectId').value;
        const platform = document.getElementById('filterPlatform').value;
        const accountQ = document.getElementById('filterAccountCampaign').value;

        const qs = new URLSearchParams({
            period: currentLirPeriod,
            start_date: startDate,
            end_date: endDate,
            project_id: projectId,
            platform: platform,
            account_q: accountQ,
            _t: Date.now()
        });

        try {
            const res = await fetch(`${lirBaseUrl}/lead-investment-report/api/dashboard?${qs.toString()}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            const json = await res.json();
            if (json.status !== 'success') {
                console.error(json.message);
                return;
            }
            lastDashboardData = json.data;
            renderOverviewTab(json.data);
            renderSpendEntriesTable(json.data);
            renderAdPerformanceTab(json.data);
            renderStatementsTab(json.data);
        } catch (err) {
            console.error('Failed to load Leads Investment Report data:', err);
        }
    }

    function formatUsd(val) {
        if (val === null || val === undefined || isNaN(val)) return '—';
        return '$' + Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function formatNumber(val) {
        return Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderOverviewTab(data) {
        const kpis = data.kpis || {};
        const dateInfo = data.date_info || {};

        document.getElementById('overviewScopeDateLabel').textContent = dateInfo.label || '';
        document.getElementById('kpiActualSpend').textContent = formatUsd(kpis.actual_spend_usd || 0);
        document.getElementById('kpiActualSpendBdt').textContent = 'BDT ' + formatNumber(kpis.actual_spend_bdt || 0);
        document.getElementById('kpiMatchedPaidLeads').textContent = Number(kpis.matched_paid_leads || 0).toLocaleString();
        document.getElementById('kpiWeightedCpl').textContent = kpis.weighted_cpl_usd !== null ? formatUsd(kpis.weighted_cpl_usd) : '—';
        document.getElementById('kpiBudgetUtilisation').textContent = Number(kpis.budget_utilisation_pct || 0).toFixed(1) + '%';
        document.getElementById('kpiBudgetSub').textContent = 'Allocated budget: ' + formatUsd(kpis.total_budget_usd || 0);

        // Alert Banner
        const alertBanner = document.getElementById('overviewAlertBanner');
        const unmappedCnt = Number(kpis.unmapped_spend_rows || 0);
        const organicCnt = Number(kpis.organic_leads_excluded || 0);
        if (unmappedCnt > 0 || organicCnt > 0) {
            const parts = [];
            if (unmappedCnt > 0) parts.push(`${unmappedCnt} spend row${unmappedCnt > 1 ? 's need' : ' needs'} ad mapping`);
            if (organicCnt > 0) parts.push(`${organicCnt} organic lead${organicCnt > 1 ? 's' : ''} excluded from paid CPL`);
            document.getElementById('overviewAlertTitle').textContent = parts.join(' • ');
            document.getElementById('overviewAlertDesc').textContent = unmappedCnt > 0
                ? `Spend ${formatUsd(kpis.actual_spend_usd)} includes ${formatUsd(kpis.unmapped_spend_usd)} of project-only spend. Project CPL is available; ad-level CPL for that row is unavailable.`
                : `Organic/brochure leads (${organicCnt}) are excluded from paid CPL calculations so ad efficiency stays accurate.`;
            alertBanner.style.display = 'block';
        } else {
            alertBanner.style.display = 'none';
        }

        // Investment working bars
        const barsContainer = document.getElementById('investmentWorkingBarsContainer');
        const bars = data.investment_bars || [];
        if (bars.length === 0) {
            barsContainer.innerHTML = `<div class="text-muted py-3 text-center">No spend or matched paid leads recorded for this scope yet. Click <strong>+ Add daily spend</strong> to log ad spend.</div>`;
        } else {
            barsContainer.innerHTML = bars.map(b => {
                const cplText = b.cpl_usd !== null ? `CPL ${formatUsd(b.cpl_usd)}` : 'CPL —';
                return `
                    <div class="lir-bar-row">
                        <div class="lir-bar-project" title="${escapeHtml(b.project_name)}">${escapeHtml(b.project_name)}</div>
                        <div class="lir-bar-track">
                            <div class="lir-bar-fill" style="width: ${b.width_pct}%;"></div>
                        </div>
                        <div class="lir-bar-metrics">
                            ${formatUsd(b.spend_usd)} &bull; ${b.paid_leads} leads &bull; ${cplText}
                        </div>
                    </div>
                `;
            }).join('');
        }

        // Project x Platform Table
        const tbody = document.getElementById('overviewProjectPlatformTbody');
        const tfoot = document.getElementById('overviewProjectPlatformTfoot');
        const rows = data.project_platform_rows || [];

        if (rows.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted">No project &times; platform activity found for the selected filters.</td></tr>`;
            tfoot.innerHTML = '';
        } else {
            tbody.innerHTML = rows.map(r => {
                const cplCell = r.cpl_usd !== null ? formatUsd(r.cpl_usd) : (r.paid_leads === 0 && r.spend_usd > 0 ? '— / No leads' : '—');
                return `
                    <tr>
                        <td class="fw-semibold">${escapeHtml(r.project_name)}</td>
                        <td>${escapeHtml(r.platform.replace(' Ads', ''))}</td>
                        <td>
                            ${formatUsd(r.budget_usd)}
                            <button type="button" class="btn btn-link btn-sm p-0 ms-1 text-muted" title="Edit Budget & Target CPL" onclick="openBudgetModalForProject(${r.project_id}, '${escapeHtml(r.platform)}', ${r.daily_budget}, ${r.target_cpl})">
                                <i class="fa-solid fa-pen" style="font-size:10px;"></i>
                            </button>
                        </td>
                        <td>${formatUsd(r.spend_usd)}</td>
                        <td>${r.paid_leads}</td>
                        <td>${cplCell}</td>
                        <td>${formatUsd(r.remaining)}</td>
                        <td>${escapeHtml(r.decision)}</td>
                    </tr>
                `;
            }).join('');

            tfoot.innerHTML = `
                <tr>
                    <td>Total</td>
                    <td>All</td>
                    <td>${formatUsd(kpis.total_budget_usd || 0)}</td>
                    <td>${formatUsd(kpis.actual_spend_usd || 0)}</td>
                    <td>${Number(kpis.matched_paid_leads || 0)}</td>
                    <td>${kpis.weighted_cpl_usd !== null ? formatUsd(kpis.weighted_cpl_usd) : '—'}</td>
                    <td>${formatUsd(kpis.total_remaining_usd || 0)}</td>
                    <td>Weighted total</td>
                </tr>
            `;
        }
    }

    function renderSpendEntriesTable(data) {
        const entries = data.spend_entries || [];
        const dateInfo = data.date_info || {};
        const tbody = document.getElementById('spendEntriesTbody');
        const badge = document.getElementById('spendEntriesCountBadge');
        const heading = document.getElementById('spendEntriesTableHeading');
        if (badge) badge.textContent = `${entries.length} ${entries.length === 1 ? 'entry' : 'entries'}`;
        if (heading) {
            const todayIso = <?= json_encode($todayDate) ?>;
            heading.textContent = (dateInfo.start_date === todayIso && dateInfo.end_date === todayIso)
                ? "Today's spend entries"
                : `Spend entries (${dateInfo.label || ''})`;
        }

        if (entries.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted">No spend entries recorded for the selected date range yet.</td></tr>`;
            return;
        }

        tbody.innerHTML = entries.map(e => {
            const adLabel = e.ad_name
                ? (e.ad_id && e.ad_id !== e.ad_name ? `${e.ad_name} (${e.ad_id})` : e.ad_name)
                : (e.ad_id || 'Project-only • Unmapped');
            const cplCell = e.cpl_usd !== null ? formatUsd(e.cpl_usd) : '—';
            const platShort = e.platform.replace(' Ads', '');
            const spendCell = e.is_prorated
                ? `<div>${formatUsd(e.spend_usd)}</div><div class="text-muted" style="font-size:11px;">of ${formatUsd(e.full_spend_usd)} total</div>`
                : formatUsd(e.spend_usd);

            return `
                <tr>
                    <td class="text-muted" style="font-size:12px;">${escapeHtml(e.spend_date_label)}</td>
                    <td class="fw-semibold">${escapeHtml(e.project_name)} / ${escapeHtml(platShort)}</td>
                    <td>${escapeHtml(adLabel)}</td>
                    <td>${spendCell}</td>
                    <td>${e.paid_leads}</td>
                    <td>${cplCell}</td>
                    <td>${escapeHtml(e.updated_by_name)}</td>
                    <td>
                        <span class="lir-action-link" onclick='populateSpendFormForEdit(${JSON.stringify(e).replace(/'/g, "&#39;")})'>Edit</span>
                        &bull;
                        <span class="lir-action-link" onclick="openSpendHistoryModal(${e.id})">History</span>
                        &bull;
                        <span class="lir-action-link text-danger" onclick="deleteSpendEntry(${e.id})">Delete</span>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderAdPerformanceTab(data) {
        const kpis = data.kpis || {};
        const rows = data.ad_performance_rows || [];
        const notes = data.decision_notes || [];

        const unmappedSub = document.getElementById('adPerfUnmappedSub');
        if (Number(kpis.unmapped_spend_usd || 0) > 0) {
            unmappedSub.textContent = `Project-only spend: ${formatUsd(kpis.unmapped_spend_usd)} • Excluded from ad rankings; retained in project totals.`;
            unmappedSub.style.display = 'block';
        } else {
            unmappedSub.style.display = 'none';
        }

        const tbody = document.getElementById('adPerformanceTbody');
        if (rows.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted">No ad spend entries found for this filter scope.</td></tr>`;
        } else {
            tbody.innerHTML = rows.map(r => {
                const adCell = r.is_mapped
                    ? `<div class="fw-semibold">${escapeHtml(r.ad_name)}</div><div class="text-muted" style="font-size:11.5px;">${escapeHtml(r.ad_id)}</div>`
                    : `<div class="fw-semibold">Project-only &bull; Unmapped</div>`;
                const platShort = r.platform === 'Meta Ads' ? 'Meta' : r.platform;
                return `
                    <tr>
                        <td>${adCell}</td>
                        <td>${escapeHtml(r.project_name)}</td>
                        <td>${escapeHtml(platShort)}</td>
                        <td>${formatUsd(r.spend_usd)}</td>
                        <td>${r.paid_leads}</td>
                        <td>${r.cpl_usd !== null ? formatUsd(r.cpl_usd) : '—'}</td>
                        <td>${r.target_cpl !== null ? formatUsd(r.target_cpl) : '—'}</td>
                        <td>${escapeHtml(r.signal)}</td>
                    </tr>
                `;
            }).join('');
        }

        const notesList = document.getElementById('adPerfDecisionNotesList');
        notesList.innerHTML = notes.map(n => `<div class="lir-decision-item">${escapeHtml(n)}</div>`).join('');
    }

    function renderStatementsTab(data) {
        const kpis = data.kpis || {};
        const dateInfo = data.date_info || {};
        const rows = data.project_platform_rows || [];

        const projSel = document.getElementById('filterProjectId');
        const projText = projSel.options[projSel.selectedIndex]?.text || 'All projects';
        const platSel = document.getElementById('filterPlatform');
        const platText = platSel.options[platSel.selectedIndex]?.text || 'All platforms';

        document.getElementById('stmtScopeLine').textContent =
            `${dateInfo.label || ''} • Asia/Dhaka • ${projText} • ${platText} • USD`;

        const tbody = document.getElementById('statementTbody');
        const tfoot = document.getElementById('statementTfoot');
        tfoot.innerHTML = '';

        if (rows.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">No records in selected reporting scope.</td></tr>`;
        } else {
            const rowsHtml = rows.map(r => {
                const platShort = r.platform === 'Meta Ads' ? 'Meta' : r.platform;
                return `
                    <tr>
                        <td>${escapeHtml(r.project_name)}</td>
                        <td>${escapeHtml(platShort)}</td>
                        <td>${formatUsd(r.spend_usd)}</td>
                        <td>${r.paid_leads}</td>
                        <td>${r.cpl_usd !== null ? formatUsd(r.cpl_usd) : '—'}</td>
                        <td>${formatUsd(r.budget_usd)}</td>
                        <td>${formatUsd(r.remaining)}</td>
                    </tr>
                `;
            }).join('');

            // Render Grand total as the final row inside tbody (never in tfoot) so browser print never repeats it on every page
            const grandTotalHtml = `
                <tr style="background: #e8f5ee; color: #0a7c48; font-weight: 800; border-top: 2px solid #c6e6d4;">
                    <td style="background: #e8f5ee; color: #0a7c48; font-weight: 800;">Grand total</td>
                    <td style="background: #e8f5ee; color: #0a7c48; font-weight: 800;">All</td>
                    <td style="background: #e8f5ee; color: #0a7c48; font-weight: 800;">${formatUsd(kpis.actual_spend_usd || 0)}</td>
                    <td style="background: #e8f5ee; color: #0a7c48; font-weight: 800;">${Number(kpis.matched_paid_leads || 0)}</td>
                    <td style="background: #e8f5ee; color: #0a7c48; font-weight: 800;">${kpis.weighted_cpl_usd !== null ? formatUsd(kpis.weighted_cpl_usd) : '—'}</td>
                    <td style="background: #e8f5ee; color: #0a7c48; font-weight: 800;">${formatUsd(kpis.total_budget_usd || 0)}</td>
                    <td style="background: #e8f5ee; color: #0a7c48; font-weight: 800;">${formatUsd(kpis.total_remaining_usd || 0)}</td>
                </tr>
            `;
            tbody.innerHTML = rowsHtml + grandTotalHtml;
        }

        document.getElementById('stmtTotalSummaryLine').textContent =
            `Total spend: USD ${formatNumber(kpis.actual_spend_usd || 0)} • BDT equivalent: ${formatNumber(kpis.actual_spend_bdt || 0)} at rate ${formatNumber(kpis.effective_fx_rate || 133)}`;

        document.getElementById('stmtMethodLine').textContent =
            `Method: summed spend ÷ summed matched paid leads. Organic leads excluded (${kpis.organic_leads_excluded || 0}). Ad-unmapped spend: ${formatUsd(kpis.unmapped_spend_usd || 0)}.`;
    }

    // =========================================================================
    // TAB 2: DAILY / CUSTOM-RANGE SPEND ENTRY FORM LOGIC & REAL-TIME PREVIEW
    // =========================================================================
    function setEntryDateMode(mode) {
        entryDateMode = (mode === 'range') ? 'range' : 'single';
        const isRange = (entryDateMode === 'range');

        document.getElementById('spendModeSingleBtn')?.classList.toggle('active', !isRange);
        document.getElementById('spendModeRangeBtn')?.classList.toggle('active', isRange);

        const labelEl = document.getElementById('entrySpendDateLabel');
        if (labelEl) labelEl.textContent = isRange ? 'SPEND DATE RANGE *' : 'SPEND DATE *';

        const endInput = document.getElementById('entrySpendEndDate');
        const dashEl = document.getElementById('entryDateRangeDash');
        const badgeEl = document.getElementById('entryDaysBadge');
        if (endInput) endInput.style.display = isRange ? 'block' : 'none';
        if (dashEl) dashEl.style.display = isRange ? 'inline' : 'none';
        if (badgeEl) badgeEl.style.display = isRange ? 'inline-block' : 'none';

        // Adjust Bootstrap column widths smoothly so 2 date inputs have generous room in Range mode
        const dateCol = document.getElementById('entryDateCol');
        const platCol = document.getElementById('entryPlatformCol');
        const accCol = document.getElementById('entryAccountCol');
        if (dateCol && platCol && accCol) {
            dateCol.className = isRange ? 'col-lg-5 col-md-12' : 'col-lg-3 col-md-6';
            platCol.className = isRange ? 'col-lg-2 col-md-4' : 'col-lg-3 col-md-6';
            accCol.className = isRange ? 'col-lg-2 col-md-4' : 'col-lg-3 col-md-6';
        }

        onEntryDateChanged();
    }

    function getEffectiveEntryEndDate() {
        const startVal = document.getElementById('entrySpendDate').value;
        if (entryDateMode !== 'range') return startVal;
        const endVal = document.getElementById('entrySpendEndDate').value || startVal;
        return endVal >= startVal ? endVal : startVal;
    }

    function onEntryDateChanged() {
        const startInput = document.getElementById('entrySpendDate');
        const endInput = document.getElementById('entrySpendEndDate');
        const badgeEl = document.getElementById('entryDaysBadge');

        if (startInput && endInput) {
            if (entryDateMode !== 'range') {
                endInput.value = startInput.value;
            } else {
                endInput.min = startInput.value || '';
                if (!endInput.value || endInput.value < startInput.value) {
                    endInput.value = startInput.value;
                }
            }

            if (badgeEl && startInput.value && endInput.value) {
                const sMs = Date.parse(startInput.value + 'T00:00:00');
                const eMs = Date.parse(endInput.value + 'T00:00:00');
                const days = (!isNaN(sMs) && !isNaN(eMs) && eMs >= sMs)
                    ? Math.round((eMs - sMs) / 86400000) + 1
                    : 1;
                badgeEl.textContent = `${days} ${days === 1 ? 'day' : 'days'}`;
            }
        }

        updateSpendMatchPreview();
    }

    function recalcBdtEquivalent() {
        const currency = document.getElementById('entryCurrency').value;
        const spend = parseFloat(document.getElementById('entryActualSpend').value || 0);
        const fx = parseFloat(document.getElementById('entryFxRate').value || 133);
        const bdtField = document.getElementById('entryBdtEquivalent');

        const bdtVal = currency === 'BDT' ? spend : (spend * fx);
        bdtField.value = formatNumber(bdtVal);
        updateSpendMatchPreview();
    }

    async function onEntryProjectChanged() {
        const pid = document.getElementById('entryProjectId').value;
        updateSpendMatchPreview();
        try {
            const res = await fetch(`${lirBaseUrl}/lead-investment-report/api/campaigns-list?project_id=${encodeURIComponent(pid)}`);
            const json = await res.json();
            if (json.status === 'success' && Array.isArray(json.data)) {
                const dl = document.getElementById('datalistProjectCampaigns');
                dl.innerHTML = json.data.map(c => `<option value="${escapeHtml(c)}"></option>`).join('');
                if (json.data.length > 0 && !document.getElementById('entryCampaignName').value) {
                    document.getElementById('entryCampaignName').value = json.data[0];
                    updateSpendMatchPreview();
                }
            }
        } catch (e) {}
    }

    function updateSpendMatchPreview() {
        if (previewDebounceTimer) clearTimeout(previewDebounceTimer);
        previewDebounceTimer = setTimeout(runSpendMatchPreviewAjax, 180);
    }

    async function runSpendMatchPreviewAjax() {
        const spendDate = document.getElementById('entrySpendDate').value;
        const spendEndDate = getEffectiveEntryEndDate();
        const platform = document.getElementById('entryPlatform').value;
        const adAccount = document.getElementById('entryAdAccount').value;
        const projectId = document.getElementById('entryProjectId').value;
        const campaignName = document.getElementById('entryCampaignName').value;
        const adInput = document.getElementById('entryAdInput').value.trim();
        const currency = document.getElementById('entryCurrency').value;
        const actualSpend = document.getElementById('entryActualSpend').value;
        const fxRate = document.getElementById('entryFxRate').value;

        let adId = '';
        if (adInput.includes('/')) {
            adId = adInput.split('/')[1].trim();
        } else {
            adId = adInput;
        }

        const defaultDupText = 'Duplicate date/range + account + ad + project entries prompt an update. No double counting with platform or project totals.';

        if (!projectId) {
            document.getElementById('previewMatchedLeads').textContent = '0';
            document.getElementById('previewCalculatedCpl').textContent = '—';
            document.getElementById('previewMatchBasis').textContent = 'Select a project to preview matched paid leads';
            const dupNotice = document.getElementById('duplicateNoticeText');
            if (dupNotice) dupNotice.textContent = defaultDupText;
            return;
        }

        const qs = new URLSearchParams({
            spend_date: spendDate,
            spend_end_date: spendEndDate,
            platform: platform,
            ad_account: adAccount,
            project_id: projectId,
            campaign_name: campaignName,
            ad_id: adId,
            currency: currency,
            actual_spend: actualSpend,
            fx_rate: fxRate
        });

        try {
            const res = await fetch(`${lirBaseUrl}/lead-investment-report/api/match-preview?${qs.toString()}`);
            const json = await res.json();
            // Guard against late response arriving after form was cleared
            if (!document.getElementById('entryProjectId').value) {
                return;
            }
            if (json.status === 'success' && json.data) {
                const d = json.data;
                document.getElementById('previewMatchedLeads').textContent = Number(d.matched_paid_leads || 0);
                document.getElementById('previewCalculatedCpl').textContent = d.calculated_cpl_usd !== null
                    ? 'USD ' + Number(d.calculated_cpl_usd).toFixed(2)
                    : '— / No leads';

                let basisText = d.match_basis || 'Same date + project + platform';
                if (Number(d.days_count || 1) > 1 && Number(d.spend_usd || 0) > 0) {
                    basisText += ` • Avg $${Number(d.daily_avg_usd || 0).toFixed(2)}/day`;
                }
                document.getElementById('previewMatchBasis').textContent = basisText;

                const dupNotice = document.getElementById('duplicateNoticeText');
                const hasAdAccount = adAccount.trim() !== '';
                if (hasAdAccount && d.existing_duplicate_id && !document.getElementById('spendEditId').value) {
                    dupNotice.innerHTML = `<strong class="text-warning-emphasis">Note:</strong> An entry for this exact Date/Range + Platform + Account + Project + Ad already exists (#${d.existing_duplicate_id}). Saving will update that entry without double counting.`;
                } else if (hasAdAccount && Number(d.overlapping_count || 0) > 0 && !document.getElementById('spendEditId').value) {
                    dupNotice.innerHTML = `<strong class="text-warning-emphasis">Heads up:</strong> ${d.overlapping_count} existing spend entry overlaps part of this selected date range for the same project/account/ad. Verify dates to avoid overlapping spend.`;
                } else {
                    dupNotice.textContent = defaultDupText;
                }
            }
        } catch (err) {
            console.error(err);
        }
    }

    async function submitSpendEntry(addAnother = false) {
        const editId = document.getElementById('spendEditId').value;
        const spendDate = document.getElementById('entrySpendDate').value;
        const spendEndDate = getEffectiveEntryEndDate();
        const platform = document.getElementById('entryPlatform').value;
        const adAccount = document.getElementById('entryAdAccount').value.trim();
        const projectId = document.getElementById('entryProjectId').value;
        const campaignName = document.getElementById('entryCampaignName').value.trim();
        const adSetName = document.getElementById('entryAdSetName').value.trim();
        const adInput = document.getElementById('entryAdInput').value.trim();
        const currency = document.getElementById('entryCurrency').value;
        const actualSpend = document.getElementById('entryActualSpend').value;
        const fxRate = document.getElementById('entryFxRate').value;
        const sourceRef = document.getElementById('entrySourceRef').value.trim();
        const notes = document.getElementById('entryNotes').value.trim();

        if (!spendDate) {
            alert('Please select a Spend Date.');
            return;
        }
        if (spendEndDate < spendDate) {
            alert('Spend End Date cannot be earlier than Start Date.');
            return;
        }
        if (!adAccount) {
            alert('Please enter an Ad Account.');
            return;
        }
        if (!projectId) {
            alert('Please select a Project.');
            return;
        }
        if (!campaignName) {
            alert('Please enter a Campaign name.');
            return;
        }
        if (!actualSpend || parseFloat(actualSpend) <= 0) {
            alert('Please enter a valid Actual Spend amount greater than 0.');
            return;
        }

        const btn = document.getElementById('btnSaveSpendMain');
        const origText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Saving...';

        try {
            const res = await fetch(`${lirBaseUrl}/lead-investment-report/api/save-spend`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    id: editId,
                    spend_date: spendDate,
                    spend_end_date: spendEndDate,
                    platform: platform,
                    ad_account: adAccount,
                    project_id: projectId,
                    campaign_name: campaignName,
                    ad_set_name: adSetName,
                    ad_input: adInput,
                    currency: currency,
                    actual_spend: actualSpend,
                    fx_rate: fxRate,
                    source_reference: sourceRef,
                    notes: notes
                })
            });
            const json = await res.json();
            if (json.status !== 'success') {
                alert(json.message || 'Failed to save spend entry.');
                return;
            }

            // Sync shared filter date/range so the saved entry (and its full date range) is immediately visible
            if (spendEndDate > spendDate) {
                currentLirPeriod = 'custom';
                document.querySelectorAll('.lir-period-btn').forEach(b => {
                    b.classList.toggle('active', b.getAttribute('data-period') === 'custom');
                });
                document.getElementById('filterStartDate').value = spendDate;
                document.getElementById('filterEndDate').value = spendEndDate;
                document.getElementById('filterEndDate').style.display = 'block';
                document.getElementById('filterDateDash').style.display = 'inline';
            } else {
                document.getElementById('filterStartDate').value = spendDate;
                if (currentLirPeriod === 'custom' && document.getElementById('filterEndDate').value < spendDate) {
                    document.getElementById('filterEndDate').value = spendDate;
                }
            }
            await loadLirDashboardData();

            if (addAnother) {
                // Keep date/range, platform & account for quick batch entry, but clear project/campaign/ad/spend & duplicate note
                if (previewDebounceTimer) clearTimeout(previewDebounceTimer);
                document.getElementById('spendEditId').value = '';
                document.getElementById('entryProjectId').value = '';
                document.getElementById('entryCampaignName').value = '';
                document.getElementById('entryAdSetName').value = '';
                document.getElementById('entryAdInput').value = '';
                document.getElementById('entryActualSpend').value = '';
                document.getElementById('entryBdtEquivalent').value = '0.00';
                document.getElementById('entryNotes').value = '';
                btn.textContent = 'Save spend';
                document.getElementById('previewMatchedLeads').textContent = '0';
                document.getElementById('previewCalculatedCpl').textContent = '—';
                document.getElementById('previewMatchBasis').textContent = 'Select a project to preview matched paid leads';
                document.getElementById('duplicateNoticeText').textContent =
                    'Duplicate date/range + account + ad + project entries prompt an update. No double counting with platform or project totals.';
            } else {
                resetSpendForm();
            }
        } catch (err) {
            alert('Error saving spend entry: ' + err.message);
        } finally {
            btn.disabled = false;
            if (btn.textContent === 'Saving...') btn.textContent = origText;
        }
    }

    function populateSpendFormForEdit(entry) {
        switchLirTab('spend');
        document.getElementById('spendEditId').value = entry.id;
        const startD = entry.spend_date;
        const endD = entry.spend_end_date || entry.spend_date;
        document.getElementById('entrySpendDate').value = startD;
        document.getElementById('entrySpendEndDate').value = endD;
        setEntryDateMode(endD > startD ? 'range' : 'single');

        document.getElementById('entryPlatform').value = entry.platform;
        document.getElementById('entryAdAccount').value = entry.ad_account;
        document.getElementById('entryProjectId').value = entry.project_id;
        document.getElementById('entryCampaignName').value = entry.campaign_name || '';
        document.getElementById('entryAdSetName').value = entry.ad_set_name || '';

        const combinedAd = (entry.ad_name && entry.ad_id && entry.ad_name !== entry.ad_id)
            ? `${entry.ad_name} / ${entry.ad_id}`
            : (entry.ad_name || entry.ad_id || '');
        document.getElementById('entryAdInput').value = combinedAd;

        document.getElementById('entryCurrency').value = entry.currency || 'USD';
        document.getElementById('entryActualSpend').value = Number(entry.actual_spend || 0).toFixed(2);
        document.getElementById('entryFxRate').value = Number(entry.fx_rate || 133).toFixed(2);
        document.getElementById('entrySourceRef').value = entry.source_reference || '';
        document.getElementById('entryNotes').value = entry.notes || '';
        document.getElementById('btnSaveSpendMain').textContent = 'Update spend #' + entry.id;

        recalcBdtEquivalent();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function resetSpendForm() {
        if (previewDebounceTimer) clearTimeout(previewDebounceTimer);
        const todayIso = <?= json_encode($todayDate) ?>;

        document.getElementById('spendEditId').value = '';
        document.getElementById('entrySpendDate').value = todayIso;
        document.getElementById('entrySpendEndDate').value = todayIso;
        document.getElementById('entryPlatform').value = 'Meta Ads';
        document.getElementById('entryAdAccount').value = '';
        document.getElementById('entryProjectId').value = '';
        document.getElementById('entryCampaignName').value = '';
        document.getElementById('entryAdSetName').value = '';
        document.getElementById('entryAdInput').value = '';
        document.getElementById('entryCurrency').value = 'USD';
        document.getElementById('entryActualSpend').value = '';
        document.getElementById('entryFxRate').value = '133.00';
        document.getElementById('entryBdtEquivalent').value = '0.00';
        document.getElementById('entrySourceRef').value = '';
        document.getElementById('entryNotes').value = '';
        document.getElementById('btnSaveSpendMain').textContent = 'Save spend';

        setEntryDateMode('single');
        if (previewDebounceTimer) clearTimeout(previewDebounceTimer);

        document.getElementById('previewMatchedLeads').textContent = '0';
        document.getElementById('previewCalculatedCpl').textContent = '—';
        document.getElementById('previewMatchBasis').textContent = 'Select a project to preview matched paid leads';
        document.getElementById('duplicateNoticeText').textContent =
            'Duplicate date/range + account + ad + project entries prompt an update. No double counting with platform or project totals.';
    }

    async function deleteSpendEntry(id) {
        if (!confirm('Are you sure you want to delete this spend entry?')) return;
        try {
            const res = await fetch(`${lirBaseUrl}/lead-investment-report/api/delete-spend`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ id })
            });
            const json = await res.json();
            if (json.status === 'success') {
                loadLirDashboardData();
            } else {
                alert(json.message || 'Could not delete entry.');
            }
        } catch (err) {
            alert('Error: ' + err.message);
        }
    }

    async function openSpendHistoryModal(spendId) {
        const tbody = document.getElementById('spendHistoryModalTbody');
        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-3 text-muted">Loading audit history...</td></tr>`;
        const modal = new bootstrap.Modal(document.getElementById('spendHistoryModal'));
        modal.show();

        try {
            const res = await fetch(`${lirBaseUrl}/lead-investment-report/api/spend-history?spend_id=${spendId}`);
            const json = await res.json();
            const list = json.data || [];
            if (list.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center py-3 text-muted">No history records found.</td></tr>`;
                return;
            }
            tbody.innerHTML = list.map(h => `
                <tr>
                    <td>${escapeHtml(h.changed_at)}</td>
                    <td><span class="badge bg-success-subtle text-success border">${escapeHtml(h.action_type)}</span></td>
                    <td>${h.old_spend_usd !== null ? formatUsd(h.old_spend_usd) : '—'}</td>
                    <td class="fw-bold">${formatUsd(h.new_spend_usd)}</td>
                    <td>${Number(h.new_fx_rate || 133).toFixed(2)}</td>
                    <td>${escapeHtml(h.changed_by_name || 'Operator')}</td>
                    <td>${escapeHtml(h.change_note || '')}</td>
                </tr>
            `).join('');
        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-3 text-danger">Failed to load history.</td></tr>`;
        }
    }

    // =========================================================================
    // BUDGET & TARGET CPL MODAL
    // =========================================================================
    function openBudgetModal() {
        const modal = new bootstrap.Modal(document.getElementById('budgetSetupModal'));
        modal.show();
    }

    function openBudgetModalForProject(projectId, platform, dailyBudget, targetCpl) {
        document.getElementById('budgetModalProjectId').value = projectId;
        document.getElementById('budgetModalPlatform').value = platform;
        document.getElementById('budgetModalDailyUsd').value = Number(dailyBudget || 100).toFixed(2);
        document.getElementById('budgetModalMonthlyUsd').value = (Number(dailyBudget || 100) * 30).toFixed(2);
        document.getElementById('budgetModalTargetCpl').value = Number(targetCpl || 0.45).toFixed(2);
        openBudgetModal();
    }

    async function saveBudgetAndTargetCpl() {
        const projectId = document.getElementById('budgetModalProjectId').value;
        const platform = document.getElementById('budgetModalPlatform').value;
        const dailyUsd = document.getElementById('budgetModalDailyUsd').value;
        const monthlyUsd = document.getElementById('budgetModalMonthlyUsd').value;
        const targetCpl = document.getElementById('budgetModalTargetCpl').value;

        if (!projectId) {
            alert('Please select a project.');
            return;
        }

        try {
            const res = await fetch(`${lirBaseUrl}/lead-investment-report/api/save-budget`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    project_id: projectId,
                    platform: platform,
                    daily_budget_usd: dailyUsd,
                    monthly_budget_usd: monthlyUsd,
                    target_cpl_usd: targetCpl
                })
            });
            const json = await res.json();
            if (json.status === 'success') {
                bootstrap.Modal.getInstance(document.getElementById('budgetSetupModal'))?.hide();
                loadLirDashboardData();
            } else {
                alert(json.message || 'Failed to save budget.');
            }
        } catch (err) {
            alert('Error saving budget: ' + err.message);
        }
    }

    // =========================================================================
    // MULTI-PAGE FORMATTED PDF PRINT & EXCEL (.XLSX) / CSV EXPORT
    // =========================================================================
    function downloadStatementPdf() {
        if (!lastDashboardData) {
            alert('Statement data is still loading. Please try again in a moment.');
            return;
        }

        const kpis = lastDashboardData.kpis || {};
        const rows = lastDashboardData.project_platform_rows || [];
        const scopeText = document.getElementById('stmtScopeLine')?.innerText || '';
        const totalLineText = document.getElementById('stmtTotalSummaryLine')?.innerText || '';
        const methodLineText = document.getElementById('stmtMethodLine')?.innerText || '';
        const operatorName = <?= json_encode($currentUserName) ?>;
        const nowObj = new Date();
        const generatedAtFormatted = nowObj.toLocaleDateString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        }) + ', ' + nowObj.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });

        // Chunk rows into pages (18 rows per page so every page fits cleanly on A4 portrait with repeating header & footer)
        const rowsPerPage = 18;
        const pages = [];
        if (rows.length === 0) {
            pages.push([]);
        } else {
            for (let i = 0; i < rows.length; i += rowsPerPage) {
                pages.push(rows.slice(i, i + rowsPerPage));
            }
        }

        const totalPages = pages.length;

        const pagesHtml = pages.map((pageRows, pageIdx) => {
            const pageNumber = pageIdx + 1;
            const isLastPage = (pageNumber === totalPages);

            const bodyRowsHtml = pageRows.length === 0
                ? `<tr><td colspan="7" style="text-align:center; padding: 24px; color:#64748b;">No records in selected reporting scope.</td></tr>`
                : pageRows.map(r => {
                    const platShort = r.platform === 'Meta Ads' ? 'Meta' : r.platform;
                    return `
                        <tr>
                            <td style="font-weight: 600;">${escapeHtml(r.project_name)}</td>
                            <td>${escapeHtml(platShort)}</td>
                            <td>${formatUsd(r.spend_usd)}</td>
                            <td>${r.paid_leads}</td>
                            <td>${r.cpl_usd !== null ? formatUsd(r.cpl_usd) : '—'}</td>
                            <td>${formatUsd(r.budget_usd)}</td>
                            <td>${formatUsd(r.remaining)}</td>
                        </tr>
                    `;
                }).join('');

            const grandTotalRowHtml = isLastPage && rows.length > 0 ? `
                <tr class="grand-total-row">
                    <td>Grand total</td>
                    <td>All</td>
                    <td>${formatUsd(kpis.actual_spend_usd || 0)}</td>
                    <td>${Number(kpis.matched_paid_leads || 0)}</td>
                    <td>${kpis.weighted_cpl_usd !== null ? formatUsd(kpis.weighted_cpl_usd) : '—'}</td>
                    <td>${formatUsd(kpis.total_budget_usd || 0)}</td>
                    <td>${formatUsd(kpis.total_remaining_usd || 0)}</td>
                </tr>
            ` : '';

            const summaryFooterBlockHtml = isLastPage ? `
                <div class="stmt-summary-block">
                    <div class="stmt-total-highlight">${escapeHtml(totalLineText)}</div>
                    <div class="stmt-method-note">${escapeHtml(methodLineText)}</div>
                    <div class="stmt-method-note">For multi-day exports, BDT total uses each entry's saved FX rate. Page headers repeat; grand total appears once.</div>
                </div>
            ` : `
                <div class="stmt-continued-note">Continued on Page ${pageNumber + 1} of ${totalPages}...</div>
            `;

            return `
                <div class="pdf-page${isLastPage ? ' last-page' : ''}">
                    <div>
                        <!-- Repeating Top Brand & Statement Header on Every Page -->
                        <div class="pdf-brand-bar">
                            <span class="pdf-brand-logo">TPHL</span>
                            <span class="pdf-brand-sub">THE PREMIUM HOMES LTD. &bull; YOUR DREAM HOME AWAITS</span>
                        </div>
                        <div class="pdf-header-block">
                            <div class="pdf-company-name">THE PREMIUM HOMES LTD.</div>
                            <div class="pdf-doc-title">Leads investment statement</div>
                            <div class="pdf-scope-line">${escapeHtml(scopeText)}</div>
                            <div class="pdf-meta-line">OFFICIAL ERP REPORT &bull; Page ${pageNumber} of ${totalPages}</div>
                        </div>

                        <!-- Repeating Table Header + Page Rows -->
                        <table class="pdf-table">
                            <thead>
                                <tr>
                                    <th style="width: 30%;">Project</th>
                                    <th style="width: 13%;">Platform</th>
                                    <th style="width: 13%;">Actual spend</th>
                                    <th style="width: 11%;">Paid leads</th>
                                    <th style="width: 11%;">CPL</th>
                                    <th style="width: 11%;">Budget</th>
                                    <th style="width: 11%;">Remaining</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${bodyRowsHtml}
                                ${grandTotalRowHtml}
                            </tbody>
                        </table>

                        ${summaryFooterBlockHtml}
                    </div>

                    <!-- Repeating Bottom Page Footer -->
                    <div class="pdf-page-footer">
                        <span>Generated by: <strong>${escapeHtml(operatorName)}</strong> &bull; ${escapeHtml(generatedAtFormatted)}</span>
                        <span>Page ${pageNumber} of ${totalPages} &bull; Confidential</span>
                    </div>
                </div>
            `;
        }).join('');

        const printDocHtml = `<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>&nbsp;</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0mm !important;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            color: #0f172a;
            background: #ffffff;
        }
        .pdf-page {
            width: 100%;
            min-height: 296mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            page-break-after: always;
            break-after: page;
            padding: 12mm 14mm;
        }
        .pdf-page.last-page {
            page-break-after: auto;
            break-after: auto;
        }
        .pdf-brand-bar {
            background: #0a7c48;
            color: #ffffff;
            padding: 9px 14px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        .pdf-brand-logo {
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.05em;
        }
        .pdf-brand-sub {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.04em;
        }
        .pdf-header-block {
            margin-bottom: 10px;
        }
        .pdf-company-name {
            font-size: 15px;
            font-weight: 800;
            color: #0a7c48;
            margin-bottom: 2px;
        }
        .pdf-doc-title {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .pdf-scope-line {
            font-size: 11.5px;
            color: #475569;
            margin-bottom: 2px;
        }
        .pdf-meta-line {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .pdf-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11.5px;
            margin-top: 8px;
        }
        .pdf-table thead th {
            background: #e8f5ee;
            color: #0a7c48;
            font-weight: 700;
            padding: 8px 10px;
            text-align: left;
            border-top: 1px solid #c6e6d4;
            border-bottom: 1px solid #c6e6d4;
        }
        .pdf-table tbody td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
        }
        .pdf-table tbody tr.grand-total-row td {
            background: #e8f5ee;
            color: #0a7c48;
            font-weight: 800;
            border-top: 1.5px solid #0a7c48;
            border-bottom: 1.5px solid #0a7c48;
            padding: 9px 10px;
        }
        .stmt-summary-block {
            margin-top: 14px;
            padding-top: 8px;
        }
        .stmt-total-highlight {
            font-size: 13px;
            font-weight: 800;
            color: #0a7c48;
            margin-bottom: 6px;
        }
        .stmt-method-note {
            font-size: 10.5px;
            color: #64748b;
            margin-bottom: 3px;
        }
        .stmt-continued-note {
            margin-top: 10px;
            font-size: 10.5px;
            font-style: italic;
            color: #64748b;
            text-align: right;
        }
        .pdf-page-footer {
            margin-top: 14px;
            padding-top: 8px;
            border-top: 1px solid #cbd5e1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 10.5px;
            color: #475569;
        }
    </style>
</head>
<body>
    ${pagesHtml}
</body>
</html>`;

        // Print using hidden iframe so it never gets blocked by popup blockers and prints only the clean A4 pages
        let printFrame = document.getElementById('lirHiddenPrintFrame');
        if (!printFrame) {
            printFrame = document.createElement('iframe');
            printFrame.id = 'lirHiddenPrintFrame';
            printFrame.style.position = 'fixed';
            printFrame.style.right = '0';
            printFrame.style.bottom = '0';
            printFrame.style.width = '0';
            printFrame.style.height = '0';
            printFrame.style.border = '0';
            document.body.appendChild(printFrame);
        }

        const frameDoc = printFrame.contentWindow || printFrame.contentDocument;
        const doc = frameDoc.document || frameDoc;
        doc.open();
        doc.write(printDocHtml);
        doc.close();

        setTimeout(() => {
            printFrame.contentWindow.focus();
            printFrame.contentWindow.print();
        }, 250);
    }

    function exportStatementTable(type) {
        const startDate = document.getElementById('filterStartDate').value;
        const endDate = document.getElementById('filterEndDate').value || startDate;
        const projectId = document.getElementById('filterProjectId').value;
        const platform = document.getElementById('filterPlatform').value;
        const accountQ = document.getElementById('filterAccountCampaign').value;

        if (type === 'excel') {
            // Download formatted .xlsx generated by PhpSpreadsheet on the server
            const qs = new URLSearchParams({
                period: currentLirPeriod,
                start_date: startDate,
                end_date: endDate,
                project_id: projectId,
                platform: platform,
                account_q: accountQ
            });
            window.location.href = `${lirBaseUrl}/lead-investment-report/export-excel?${qs.toString()}`;
            return;
        }

        const table = document.getElementById('statementExportTable');
        if (!table) return;

        // Export clean CSV with metadata header & summary
        const scopeLine = document.getElementById('stmtScopeLine')?.innerText || '';
        const totalLine = document.getElementById('stmtTotalSummaryLine')?.innerText || '';
        const rows = Array.from(table.querySelectorAll('tr'));
        const csvLines = [
            `"THE PREMIUM HOMES LTD. - Leads Investment Statement"`,
            `"${scopeLine.replace(/"/g, '""')}"`,
            `""`
        ];
        rows.forEach(row => {
            const cells = Array.from(row.querySelectorAll('th, td'));
            csvLines.push(cells.map(c => `"${c.innerText.replace(/"/g, '""').trim()}"`).join(','));
        });
        csvLines.push(`""`);
        csvLines.push(`"${totalLine.replace(/"/g, '""')}"`);

        const csvContent = '\uFEFF' + csvLines.join('\r\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `TPHL_Leads_Investment_Statement_${startDate}.csv`;
        link.click();
    }
</script>


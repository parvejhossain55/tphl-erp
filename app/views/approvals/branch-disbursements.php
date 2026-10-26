<?php
// TPHL-ERP: Office-wise Payout Audit & Disbursed Transactions Dashboard
$currentFilters = $filters ?? [];
$selectedBranch = $currentFilters['branch_id'] ?? '';
$selectedDisburser = $currentFilters['disburser_id'] ?? '';
$selectedModule = $currentFilters['module'] ?? '';
$selectedDateFrom = $currentFilters['date_from'] ?? '';
$selectedDateTo = $currentFilters['date_to'] ?? '';
$searchQuery = $currentFilters['search'] ?? '';

$branchSummary = $branchSummary ?? $overview ?? [];
$branchesData = $branchSummary['branches'] ?? [];
$totals = $branchSummary['totals'] ?? [
    'total_amount' => 0,
    'transaction_count' => 0,
    'active_officers_count' => 0,
    'active_branches_count' => 0,
];

$officers = $officers ?? $disbursersList ?? [];
if (!is_array($officers)) {
    $officers = [];
}
$transactions = $transactions ?? [];

// Module options map for filter
$moduleOptions = [
    'con_financial_transactions' => 'Vendor, Contractor & Operational Fund (CF)',
    'fund_requisitions' => 'Project Fund Requisitions (FR)',
    'con_labor_requisitions' => 'Contractor Labor Bills (LB)',
    'project_extra_assets' => 'Project Extra Assets & Materials (PR)',
    'project_other_costs' => 'Project Other Costs (OC)',
    'land_fund_requisitions' => 'Land Fund Requisitions (LFR)',
    'payslips' => 'Monthly Staff Payroll (HR)',
    'project_incentive_payments' => 'Sales Incentive Claims (INC)',
    'cashout_requests' => 'Agent Cashout Requests (CO)',
    'expense_adjustments' => 'Expense Adjustments & Vouchers',
];
?>



<style>
    /* =========================================================
       BRANCH CARDS & RESPONSIVE GRID (PC: 5 CARDS, LAPTOP: 3 CARDS)
       ========================================================= */
    .branch-cards-row {
        display: flex;
        flex-wrap: wrap;
        margin-right: -0.5rem;
        margin-left: -0.5rem;
    }
    .branch-card-col {
        padding-right: 0.5rem;
        padding-left: 0.5rem;
        margin-bottom: 1rem;
        display: flex;
        flex-direction: column;
        flex: 0 0 100%;
        max-width: 100%;
    }
    .branch-card-col > a {
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        height: 100%;
    }
    @media (min-width: 576px) {
        .branch-card-col {
            flex: 0 0 50%;
            max-width: 50%;
        }
    }
    @media (min-width: 992px) {
        /* Laptop screens: 3 cards per row */
        .branch-card-col {
            flex: 0 0 33.333333%;
            max-width: 33.333333%;
        }
    }
    @media (min-width: 1400px) {
        /* PC / Wide Desktop screens: All 5 cards in the same row */
        .branch-card-col {
            flex: 0 0 20%;
            max-width: 20%;
        }
    }

    .branch-card {
        border-radius: 12px;
        transition: all 0.25s cubic-bezier(0.165, 0.84, 0.44, 1);
        border: 1px solid #e2e8f0;
        background: #ffffff;
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .branch-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
        border-color: #0d6efd;
    }
    .branch-card.active-branch-card {
        border-color: #0d6efd;
        background: #f8faff;
        box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.2);
    }
    .branch-card-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    .branch-card-amount {
        font-size: clamp(1.15rem, 1.2vw, 1.4rem);
        font-weight: 700;
        line-height: 1.2;
    }
    .officer-badge-item {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 4px 8px;
        font-size: 11px;
        transition: background 0.15s ease;
    }
    .officer-badge-item:hover {
        background: #e2e8f0;
    }
    .kpi-summary-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        transition: all 0.2s ease;
    }
    .search-filter-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }
    .table-disbursements th {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 700;
        color: #475569;
        background-color: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
    }
    .table-disbursements td {
        vertical-align: middle;
        font-size: 13px;
    }
    .stat-pill {
        display: inline-flex;
        align-items: center;
        padding: 2px 8px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }

    /* =========================================================
       DARK MODE THEME COMPATIBILITY (Supports [bd-theme="bd-theme-dark"])
       ========================================================= */
    [bd-theme="bd-theme-dark"] .branch-card,
    html[data-theme-mode="dark"] .branch-card {
        background: #111c2e !important;
        border-color: rgba(255, 255, 255, 0.08) !important;
        color: #f1f5f9 !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25) !important;
    }
    [bd-theme="bd-theme-dark"] .branch-card:hover,
    html[data-theme-mode="dark"] .branch-card:hover {
        border-color: #38bdf8 !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5), 0 0 15px rgba(56, 189, 248, 0.15) !important;
        transform: translateY(-3px);
    }
    [bd-theme="bd-theme-dark"] .branch-card.active-branch-card,
    html[data-theme-mode="dark"] .branch-card.active-branch-card {
        border-color: #38bdf8 !important;
        background: #152338 !important;
        box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.3) !important;
    }
    [bd-theme="bd-theme-dark"] .branch-card h6,
    [bd-theme="bd-theme-dark"] .branch-card h4,
    [bd-theme="bd-theme-dark"] .branch-card .text-dark,
    html[data-theme-mode="dark"] .branch-card h6,
    html[data-theme-mode="dark"] .branch-card h4,
    html[data-theme-mode="dark"] .branch-card .text-dark {
        color: #f8fafc !important;
    }
    [bd-theme="bd-theme-dark"] .branch-card .text-muted,
    html[data-theme-mode="dark"] .branch-card .text-muted {
        color: #94a3b8 !important;
    }
    [bd-theme="bd-theme-dark"] .branch-card .border-top,
    html[data-theme-mode="dark"] .branch-card .border-top {
        border-color: rgba(255, 255, 255, 0.08) !important;
    }
    [bd-theme="bd-theme-dark"] .branch-card .badge.bg-light,
    [bd-theme="bd-theme-dark"] .badge.bg-light,
    html[data-theme-mode="dark"] .branch-card .badge.bg-light,
    html[data-theme-mode="dark"] .badge.bg-light {
        background-color: #0b1320 !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        color: #e2e8f0 !important;
    }

    /* Officer badge inside branch card in Dark Mode */
    [bd-theme="bd-theme-dark"] .officer-badge-item,
    html[data-theme-mode="dark"] .officer-badge-item {
        background: #0b1320 !important;
        border-color: rgba(255, 255, 255, 0.07) !important;
        color: #e2e8f0 !important;
    }
    [bd-theme="bd-theme-dark"] .officer-badge-item:hover,
    html[data-theme-mode="dark"] .officer-badge-item:hover {
        background: #17263c !important;
        border-color: rgba(255, 255, 255, 0.15) !important;
    }
    [bd-theme="bd-theme-dark"] .officer-badge-item .text-dark,
    html[data-theme-mode="dark"] .officer-badge-item .text-dark {
        color: #f1f5f9 !important;
    }

    /* Top KPI Summary Cards in Dark Mode */
    [bd-theme="bd-theme-dark"] .kpi-summary-card,
    html[data-theme-mode="dark"] .kpi-summary-card {
        background: #111c2e !important;
        border-color: rgba(255, 255, 255, 0.08) !important;
        color: #f1f5f9 !important;
    }
    [bd-theme="bd-theme-dark"] .kpi-summary-card .text-dark,
    html[data-theme-mode="dark"] .kpi-summary-card .text-dark {
        color: #f8fafc !important;
    }
    [bd-theme="bd-theme-dark"] .kpi-summary-card .text-muted,
    html[data-theme-mode="dark"] .kpi-summary-card .text-muted {
        color: #94a3b8 !important;
    }

    /* Search & Filter Toolbar in Dark Mode */
    [bd-theme="bd-theme-dark"] .search-filter-card,
    html[data-theme-mode="dark"] .search-filter-card {
        background: #111c2e !important;
        border-color: rgba(255, 255, 255, 0.08) !important;
        color: #f1f5f9 !important;
    }
    [bd-theme="bd-theme-dark"] .search-filter-card .form-control,
    [bd-theme="bd-theme-dark"] .search-filter-card .form-select,
    [bd-theme="bd-theme-dark"] .search-filter-card .input-group-text,
    [bd-theme="bd-theme-dark"] #tableLiveFilter,
    html[data-theme-mode="dark"] .search-filter-card .form-control,
    html[data-theme-mode="dark"] .search-filter-card .form-select,
    html[data-theme-mode="dark"] .search-filter-card .input-group-text,
    html[data-theme-mode="dark"] #tableLiveFilter {
        background-color: #0b1320 !important;
        border-color: rgba(255, 255, 255, 0.12) !important;
        color: #f8fafc !important;
    }
    [bd-theme="bd-theme-dark"] .search-filter-card label,
    html[data-theme-mode="dark"] .search-filter-card label {
        color: #94a3b8 !important;
    }

    /* Table & Container Card in Dark Mode */
    [bd-theme="bd-theme-dark"] .disbursements-table-card,
    html[data-theme-mode="dark"] .disbursements-table-card {
        background: #111c2e !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
    }
    [bd-theme="bd-theme-dark"] .table-disbursements th,
    html[data-theme-mode="dark"] .table-disbursements th {
        background-color: #0b1320 !important;
        border-color: rgba(255, 255, 255, 0.08) !important;
        color: #94a3b8 !important;
    }
    [bd-theme="bd-theme-dark"] .table-disbursements td,
    html[data-theme-mode="dark"] .table-disbursements td {
        border-color: rgba(255, 255, 255, 0.06) !important;
        color: #cbd5e1 !important;
    }
    [bd-theme="bd-theme-dark"] .table-disbursements tr.disbursement-row:hover td,
    html[data-theme-mode="dark"] .table-disbursements tr.disbursement-row:hover td {
        background-color: rgba(255, 255, 255, 0.03) !important;
    }
    [bd-theme="bd-theme-dark"] .table-disbursements .text-dark,
    html[data-theme-mode="dark"] .table-disbursements .text-dark {
        color: #f8fafc !important;
    }
    [bd-theme="bd-theme-dark"] .table-disbursements tfoot,
    [bd-theme="bd-theme-dark"] .table-disbursements tfoot th,
    html[data-theme-mode="dark"] .table-disbursements tfoot,
    html[data-theme-mode="dark"] .table-disbursements tfoot th {
        background-color: #0b1320 !important;
        border-color: rgba(255, 255, 255, 0.08) !important;
        color: #f8fafc !important;
    }
    [bd-theme="bd-theme-dark"] .card-footer.table-pagination-footer,
    html[data-theme-mode="dark"] .card-footer.table-pagination-footer {
        background: #0b1320 !important;
        border-color: rgba(255, 255, 255, 0.08) !important;
        color: #94a3b8 !important;
    }
    [bd-theme="bd-theme-dark"] .card-footer.table-pagination-footer strong,
    html[data-theme-mode="dark"] .card-footer.table-pagination-footer strong {
        color: #f8fafc !important;
    }
    [bd-theme="bd-theme-dark"] .pagination .page-link,
    html[data-theme-mode="dark"] .pagination .page-link {
        background-color: #111c2e !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        color: #cbd5e1 !important;
    }
    [bd-theme="bd-theme-dark"] .pagination .page-item.active .page-link,
    html[data-theme-mode="dark"] .pagination .page-item.active .page-link {
        background-color: #0d6efd !important;
        border-color: #0d6efd !important;
        color: #ffffff !important;
    }
    [bd-theme="bd-theme-dark"] .pagination .page-item.disabled .page-link,
    html[data-theme-mode="dark"] .pagination .page-item.disabled .page-link {
        background-color: #070d18 !important;
        border-color: rgba(255, 255, 255, 0.05) !important;
        color: #64748b !important;
    }

    /* Subtle Dark Mode Variants for Branch Card Icons */
    [bd-theme="bd-theme-dark"] .branch-card-icon-0,
    html[data-theme-mode="dark"] .branch-card-icon-0 { background: rgba(56, 189, 248, 0.16) !important; color: #38bdf8 !important; }
    [bd-theme="bd-theme-dark"] .branch-card-icon-1,
    html[data-theme-mode="dark"] .branch-card-icon-1 { background: rgba(52, 211, 153, 0.16) !important; color: #34d399 !important; }
    [bd-theme="bd-theme-dark"] .branch-card-icon-2,
    html[data-theme-mode="dark"] .branch-card-icon-2 { background: rgba(251, 191, 36, 0.16) !important; color: #fbbf24 !important; }
    [bd-theme="bd-theme-dark"] .branch-card-icon-3,
    html[data-theme-mode="dark"] .branch-card-icon-3 { background: rgba(192, 132, 252, 0.16) !important; color: #c084fc !important; }
    [bd-theme="bd-theme-dark"] .branch-card-icon-4,
    html[data-theme-mode="dark"] .branch-card-icon-4 { background: rgba(248, 113, 113, 0.16) !important; color: #f87171 !important; }
</style>

<div class="container-fluid px-3 py-3">
    <!-- Header Breadcrumb & Title -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 pb-2 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="<?= $base_url ?>/all-approval-requests" class="text-decoration-none text-muted small">
                    <i class="fa-solid fa-arrow-left me-1"></i>All Approvals
                </a>
                <span class="text-muted small">/</span>
                <span class="text-primary small fw-semibold">Office Payout Audit</span>
            </div>
            <h4 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                <i class="fa-solid fa-building-columns text-success"></i>
                Office-wise Payout Audit & Disbursed Transactions
            </h4>
            <p class="text-muted small mb-0 mt-1">
                Real-time tracking of Accounts disbursement officers and total fund payouts across each office branch.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= $base_url ?>/all-approval-requests" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="fa-solid fa-list-check me-1"></i>Approval Queue
            </a>
            <a href="<?= $base_url ?>/expense-analytics" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                <i class="fa-solid fa-chart-pie me-1"></i>Expense Analytics
            </a>
            <button onclick="window.print()" class="btn btn-light btn-sm border rounded-pill px-3 shadow-sm">
                <i class="fa-solid fa-print me-1"></i>Print Report
            </button>
        </div>
    </div>

    <!-- Company High-Level KPI Summary Row -->
    <div class="row mb-4">
        <!-- Total Disbursed Amount -->
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
            <div class="card kpi-summary-card border-0 shadow-sm rounded-3 h-100 border-start border-4 border-success">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold text-uppercase">Total Disbursed Amount</span>
                        <div class="avatar avatar-sm bg-success-subtle text-success rounded-2 p-2">
                            <i class="fa-solid fa-money-bill-wave"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1 text-success">
                        ৳ <?= number_format($totals['total_amount'], 2) ?>
                    </h3>
                    <span class="text-muted extra-small">Across all company office branches</span>
                </div>
            </div>
        </div>

        <!-- Total Transactions Count -->
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
            <div class="card kpi-summary-card border-0 shadow-sm rounded-3 h-100 border-start border-4 border-primary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold text-uppercase">Paid Transactions</span>
                        <div class="avatar avatar-sm bg-primary-subtle text-primary rounded-2 p-2">
                            <i class="fa-solid fa-receipt"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1 text-primary">
                        <?= number_format($totals['transaction_count']) ?> <span class="fs-6 fw-normal text-muted">Vouchers</span>
                    </h3>
                    <span class="text-muted extra-small">Across 14 Expense & Requisition Modules</span>
                </div>
            </div>
        </div>

        <!-- Active Disbursing Officers -->
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
            <div class="card kpi-summary-card border-0 shadow-sm rounded-3 h-100 border-start border-4 border-info">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold text-uppercase">Disbursing Officers</span>
                        <div class="avatar avatar-sm bg-info-subtle text-info rounded-2 p-2">
                            <i class="fa-solid fa-user-check"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1 text-info">
                        <?= number_format($totals['active_officers_count']) ?> <span class="fs-6 fw-normal text-muted">Officers</span>
                    </h3>
                    <span class="text-muted extra-small">Authorized Accounts & Finance personnel</span>
                </div>
            </div>
        </div>

        <!-- Active Branches -->
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
            <div class="card kpi-summary-card border-0 shadow-sm rounded-3 h-100 border-start border-4 border-warning">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold text-uppercase">Active Office Branches</span>
                        <div class="avatar avatar-sm bg-warning-subtle text-warning rounded-2 p-2">
                            <i class="fa-solid fa-building-circle-check"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1 text-dark">
                        <?= number_format($totals['active_branches_count']) ?> <span class="fs-6 fw-normal text-muted">Branches</span>
                    </h3>
                    <span class="text-muted extra-small">Corporate & Site Office locations</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Office Branch Cards Grid (PC: 5 cards in same row, Laptop: 3 cards per row) -->
    <div class="mb-4">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="fa-solid fa-network-wired text-primary"></i>
                Office Branch Disbursement Breakdown
            </h6>
            <span class="badge bg-light text-secondary border">Click on any card to filter by that office</span>
        </div>

        <div class="row branch-cards-row g-2">
            <?php 
            $branchColorIndex = 0;
            $palette = [
                ['icon_bg' => '#e0f2fe', 'icon_color' => '#0284c7', 'border' => '#0284c7'],
                ['icon_bg' => '#dcfce7', 'icon_color' => '#16a34a', 'border' => '#16a34a'],
                ['icon_bg' => '#fef3c7', 'icon_color' => '#d97706', 'border' => '#d97706'],
                ['icon_bg' => '#f3e8ff', 'icon_color' => '#9333ea', 'border' => '#9333ea'],
                ['icon_bg' => '#fee2e2', 'icon_color' => '#dc2626', 'border' => '#dc2626'],
            ];

            foreach ($branchesData as $bId => $branch): 
                $iconVariantIndex = $branchColorIndex % count($palette);
                $style = $palette[$iconVariantIndex];
                $branchColorIndex++;
                $isSelected = ($selectedBranch !== '' && (string)$selectedBranch === (string)$bId);
                $branchUrl = $base_url . '/branch-disbursements?' . http_build_query(array_merge($currentFilters, ['branch_id' => $bId]));
                if ($isSelected) {
                    // Clicking active clears branch filter
                    $clearFilters = $currentFilters;
                    unset($clearFilters['branch_id']);
                    $branchUrl = $base_url . '/branch-disbursements?' . http_build_query($clearFilters);
                }
            ?>
                <div class="branch-card-col">
                    <a href="<?= $branchUrl ?>" class="text-decoration-none">
                        <div class="branch-card p-3 h-100 <?= $isSelected ? 'active-branch-card' : '' ?>">
                            <div>
                                <div class="d-flex align-items-start justify-content-between mb-3">
                                    <div class="d-flex align-items-center gap-2 overflow-hidden">
                                        <div class="branch-card-icon branch-card-icon-<?= $iconVariantIndex ?>" style="background-color: <?= $style['icon_bg'] ?>; color: <?= $style['icon_color'] ?>;">
                                            <i class="fa-solid fa-building"></i>
                                        </div>
                                        <div class="overflow-hidden">
                                            <h6 class="fw-bold mb-0 text-dark text-truncate" title="<?= htmlspecialchars($branch['branch_name']) ?>"><?= htmlspecialchars($branch['branch_name']) ?></h6>
                                            <span class="text-muted extra-small">
                                                Code: <?= htmlspecialchars($branch['branch_code'] ?? 'N/A') ?>
                                            </span>
                                        </div>
                                    </div>
                                    <?php if ($isSelected): ?>
                                        <span class="badge bg-primary rounded-pill extra-small flex-shrink-0 ms-1"><i class="fa-solid fa-check me-1"></i>Filtered</span>
                                    <?php endif; ?>
                                </div>

                                <!-- Amount Paid from this Office -->
                                <div class="mb-3">
                                    <div class="text-muted extra-small text-uppercase fw-semibold">Total Paid from Office</div>
                                    <h4 class="fw-bold mb-0 text-dark branch-card-amount">
                                        ৳ <?= number_format($branch['total_amount'], 2) ?>
                                    </h4>
                                    <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                                        <span class="badge bg-light text-dark border extra-small">
                                            <i class="fa-solid fa-receipt me-1 text-primary"></i><?= number_format($branch['transaction_count']) ?> payouts
                                        </span>
                                        <span class="badge bg-light text-dark border extra-small">
                                            <i class="fa-solid fa-users me-1 text-success"></i><?= count($branch['officers']) ?> officers
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Who Disbursed from this Office (Ke Ke Payment Koreche) -->
                            <div class="border-top pt-2 mt-2">
                                <div class="text-muted extra-small fw-semibold mb-1 d-flex justify-content-between align-items-center">
                                    <span><i class="fa-solid fa-user-check me-1 text-secondary"></i>Officers at this Office:</span>
                                    <span class="extra-small text-primary fw-bold"><?= count($branch['officers']) ?></span>
                                </div>
                                <?php if (!empty($branch['officers'])): ?>
                                    <div class="d-flex flex-column gap-1">
                                        <?php 
                                        $topOfficers = array_slice($branch['officers'], 0, 3);
                                        foreach ($topOfficers as $off): 
                                        ?>
                                            <div class="officer-badge-item d-flex align-items-center justify-content-between">
                                                <div class="text-truncate me-2" title="<?= htmlspecialchars($off['name']) ?>">
                                                    <i class="fa-solid fa-circle-user text-muted me-1"></i>
                                                    <span class="text-dark fw-semibold"><?= htmlspecialchars($off['name']) ?></span>
                                                    <?php if (!empty($off['code'])): ?>
                                                        <span class="text-muted">(<?= htmlspecialchars($off['code']) ?>)</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-end text-nowrap">
                                                    <span class="fw-bold text-success">৳ <?= number_format($off['amount'] / 1000, 1) ?>k</span>
                                                    <span class="text-muted extra-small">(<?= $off['count'] ?>)</span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>

                                        <?php if (count($branch['officers']) > 3): ?>
                                            <div class="extra-small text-muted text-center pt-1">
                                                + <?= count($branch['officers']) - 3 ?> more officer(s)
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-muted extra-small fst-italic">No transactions recorded yet</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="search-filter-card p-3 mb-4">
        <form method="GET" action="<?= $base_url ?>/branch-disbursements" id="filterForm">
            <div class="row g-2 align-items-end">
                <!-- Search Box -->
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label extra-small fw-bold text-muted text-uppercase mb-1">
                        <i class="fa-solid fa-magnifying-glass me-1"></i>Search Transactions
                    </label>
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Ref #, Payee, Purpose, Officer, Project..."
                           value="<?= htmlspecialchars($searchQuery) ?>" id="searchQueryInput">
                </div>

                <!-- Office Branch Dropdown -->
                <div class="col-xl-2 col-lg-4 col-md-6">
                    <label class="form-label extra-small fw-bold text-muted text-uppercase mb-1">
                        <i class="fa-solid fa-building me-1"></i>Office Branch
                    </label>
                    <select name="branch_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Branches (<?= count($branchesData) ?>)</option>
                        <?php foreach ($branchesData as $bId => $b): ?>
                            <option value="<?= $bId ?>" <?= ($selectedBranch !== '' && (string)$selectedBranch === (string)$bId) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($b['branch_name']) ?> (৳ <?= number_format($b['total_amount']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Disbursing Officer Dropdown -->
                <div class="col-xl-2 col-lg-4 col-md-6">
                    <label class="form-label extra-small fw-bold text-muted text-uppercase mb-1">
                        <i class="fa-solid fa-user-check me-1"></i>Disbursed By
                    </label>
                    <select name="disburser_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Officers (<?= count($officers) ?>)</option>
                        <?php foreach ($officers as $officer): ?>
                            <option value="<?= $officer['user_id'] ?>" <?= ($selectedDisburser !== '' && (string)$selectedDisburser === (string)$officer['user_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($officer['name']) ?> (<?= htmlspecialchars($officer['employee_code'] ?? 'ID:' . $officer['user_id']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Module Dropdown -->
                <div class="col-xl-2 col-lg-4 col-md-6">
                    <label class="form-label extra-small fw-bold text-muted text-uppercase mb-1">
                        <i class="fa-solid fa-layer-group me-1"></i>Expense Module
                    </label>
                    <select name="module" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Modules (14)</option>
                        <?php foreach ($moduleOptions as $modKey => $modTitle): ?>
                            <option value="<?= $modKey ?>" <?= ($selectedModule === $modKey) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($modTitle) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Date Range (From - To) -->
                <div class="col-xl-2 col-lg-4 col-md-6">
                    <label class="form-label extra-small fw-bold text-muted text-uppercase mb-1">
                        <i class="fa-solid fa-calendar me-1"></i>Date Range
                    </label>
                    <div class="input-group input-group-sm">
                        <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($selectedDateFrom) ?>" title="Date From">
                        <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($selectedDateTo) ?>" title="Date To">
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="col-xl-1 col-lg-4 col-md-6 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill" title="Apply Filters">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                    <a href="<?= $base_url ?>/branch-disbursements" class="btn btn-outline-secondary btn-sm" title="Reset All Filters">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Active Filters Tags & Results Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="fw-bold text-dark fs-6">
                Transactions List
            </span>
            <span class="badge bg-secondary-subtle text-secondary rounded-pill">
                <?= number_format($totalTransactionsCount ?? count($transactions)) ?> Records Found
            </span>

            <?php if (!empty($selectedBranch)): ?>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">
                    Branch: <?= htmlspecialchars($branchesData[$selectedBranch]['branch_name'] ?? $selectedBranch) ?>
                    <a href="<?= $base_url ?>/branch-disbursements?<?= http_build_query(array_diff_key($currentFilters, ['branch_id' => ''])) ?>" class="text-primary ms-1"><i class="fa-solid fa-xmark"></i></a>
                </span>
            <?php endif; ?>

            <?php if (!empty($selectedDisburser)): ?>
                <?php
                $selOfficerName = 'ID ' . $selectedDisburser;
                foreach ($officers as $o) {
                    if ((string)$o['user_id'] === (string)$selectedDisburser) {
                        $selOfficerName = $o['name'];
                        break;
                    }
                }
                ?>
                <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill">
                    Disbursed By: <?= htmlspecialchars($selOfficerName) ?>
                    <a href="<?= $base_url ?>/branch-disbursements?<?= http_build_query(array_diff_key($currentFilters, ['disburser_id' => ''])) ?>" class="text-info ms-1"><i class="fa-solid fa-xmark"></i></a>
                </span>
            <?php endif; ?>

            <?php if (!empty($selectedModule)): ?>
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill">
                    Module: <?= htmlspecialchars($moduleOptions[$selectedModule] ?? $selectedModule) ?>
                    <a href="<?= $base_url ?>/branch-disbursements?<?= http_build_query(array_diff_key($currentFilters, ['module' => ''])) ?>" class="text-warning ms-1"><i class="fa-solid fa-xmark"></i></a>
                </span>
            <?php endif; ?>

            <?php if (!empty($searchQuery)): ?>
                <span class="badge bg-light text-dark border rounded-pill">
                    Search: "<?= htmlspecialchars($searchQuery) ?>"
                    <a href="<?= $base_url ?>/branch-disbursements?<?= http_build_query(array_diff_key($currentFilters, ['search' => ''])) ?>" class="text-dark ms-1"><i class="fa-solid fa-xmark"></i></a>
                </span>
            <?php endif; ?>
        </div>

        <!-- Quick Live Filter -->
        <div class="d-flex align-items-center gap-2">
            <div class="input-group input-group-sm" style="max-width: 250px;">
                <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-filter text-muted"></i></span>
                <input type="text" id="tableLiveFilter" class="form-control border-start-0" placeholder="Quick filter on page...">
            </div>
            <button class="btn btn-outline-success btn-sm rounded-pill" onclick="exportDisbursementsTableToCSV()">
                <i class="fa-solid fa-file-excel me-1"></i>Export CSV
            </button>
        </div>
    </div>

    <!-- Main Transactions Table -->
    <div class="card disbursements-table-card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover table-disbursements mb-0" id="disbursementsTable">
                <thead>
                    <tr>
                        <th class="ps-3" style="width: 50px;">#</th>
                        <th style="min-width: 100px;">Date</th>
                        <th style="min-width: 140px;">Module & Ref</th>
                        <th style="min-width: 200px;">Purpose / Project</th>
                        <th style="min-width: 140px;">Payee / Beneficiary</th>
                        <th class="text-end" style="min-width: 120px;">Amount (৳)</th>
                        <th style="min-width: 170px;">Disbursed By (Officer)</th>
                        <th style="min-width: 160px;">Office Branch</th>
                        <th style="min-width: 110px;">Method</th>
                        <th class="text-end pe-3" style="min-width: 110px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center justify-content-center">
                                    <div class="avatar avatar-lg bg-light text-muted rounded-circle p-3 mb-2">
                                        <i class="fa-solid fa-inbox fs-2"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark">No Disbursed Transactions Found</h6>
                                    <p class="text-muted small mb-3">Try adjusting your filters or date range.</p>
                                    <a href="<?= $base_url ?>/branch-disbursements" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        <i class="fa-solid fa-rotate-left me-1"></i>Reset All Filters
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $serial = (($currentPage ?? 1) - 1) * ($perPage ?? 50) + 1;
                        $pageTotal = 0;
                        foreach ($transactions as $txn): 
                            $pageTotal += (float)($txn['amount'] ?? 0);
                        ?>
                            <tr class="disbursement-row">
                                <td class="ps-3 text-muted extra-small fw-semibold"><?= $serial++ ?></td>
                                
                                <!-- Date -->
                                <td class="text-nowrap">
                                    <div class="fw-semibold text-dark">
                                        <?= !empty($txn['payout_date']) ? date('d M, Y', strtotime($txn['payout_date'])) : '<span class="text-muted">N/A</span>' ?>
                                    </div>
                                    <?php if (!empty($txn['payout_date']) && strlen($txn['payout_date']) > 10): ?>
                                        <div class="extra-small text-muted"><?= date('h:i A', strtotime($txn['payout_date'])) ?></div>
                                    <?php endif; ?>
                                </td>

                                <!-- Module & Reference -->
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill extra-small px-2 py-1 mb-1 d-inline-block">
                                        <?= htmlspecialchars($txn['module_label'] ?? $txn['module_name'] ?? 'Requisition') ?>
                                    </span>
                                    <div class="fw-bold text-dark font-monospace extra-small">
                                        <?= htmlspecialchars($txn['ref_no'] ?? $txn['reference_no'] ?? ('REF-' . ($txn['source_id'] ?? ''))) ?>
                                    </div>
                                </td>

                                <!-- Purpose & Project -->
                                <td>
                                    <div class="fw-semibold text-dark text-truncate" style="max-width: 250px;" title="<?= htmlspecialchars($txn['purpose'] ?? $txn['title'] ?? '') ?>">
                                        <?= htmlspecialchars($txn['purpose'] ?? $txn['title'] ?? 'Payment Settlement') ?>
                                    </div>
                                    <?php if (!empty($txn['project_name'])): ?>
                                        <div class="extra-small text-muted text-truncate" style="max-width: 250px;">
                                            <i class="fa-solid fa-building me-1 text-secondary"></i><?= htmlspecialchars($txn['project_name']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Payee -->
                                <td>
                                    <div class="fw-semibold text-dark text-truncate" style="max-width: 180px;" title="<?= htmlspecialchars($txn['payee_name'] ?? '') ?>">
                                        <?= htmlspecialchars($txn['payee_name'] ?? 'N/A') ?>
                                    </div>
                                </td>

                                <!-- Amount -->
                                <td class="text-end">
                                    <div class="fw-bold text-success fs-6">
                                        ৳ <?= number_format((float)($txn['amount'] ?? 0), 2) ?>
                                    </div>
                                </td>

                                <!-- Disbursed By (Officer) -->
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar avatar-xs bg-success-subtle text-success rounded-circle p-1">
                                            <i class="fa-solid fa-user-check"></i>
                                        </div>
                                        <div class="overflow-hidden">
                                            <div class="fw-bold text-dark text-truncate extra-small" title="<?= htmlspecialchars($txn['disbursed_by_name'] ?? $txn['disburser_name'] ?? 'Accounts Staff') ?>">
                                                <?= htmlspecialchars($txn['disbursed_by_name'] ?? $txn['disburser_name'] ?? 'Accounts Staff') ?>
                                            </div>
                                            <div class="extra-small text-muted text-truncate">
                                                <?= htmlspecialchars(!empty($txn['disbursed_by_code']) ? $txn['disbursed_by_code'] : (!empty($txn['disburser_code']) ? $txn['disburser_code'] : 'ID: ' . ($txn['disbursed_by_id'] ?? $txn['disburser_id'] ?? ''))) ?>
                                                <?php if (!empty($txn['disbursed_by_role']) || !empty($txn['disburser_role'])): ?>
                                                    &bull; <?= htmlspecialchars($txn['disbursed_by_role'] ?? $txn['disburser_role'] ?? 'Accounts') ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Office Branch -->
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1 extra-small">
                                        <i class="fa-solid fa-building me-1 text-primary"></i>
                                        <?= htmlspecialchars($txn['branch_name'] ?? 'Corporate Gulshan Office') ?>
                                    </span>
                                </td>

                                <!-- Method -->
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary extra-small text-uppercase">
                                        <?= htmlspecialchars($txn['payment_method'] ?? 'Cash') ?>
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="text-end pe-3 text-nowrap">
                                    <div class="btn-group btn-group-sm">
                                        <?php if (!empty($txn['voucher_url'])): ?>
                                            <a href="<?= $txn['voucher_url'] ?>" target="_blank" class="btn btn-outline-success btn-xs" title="Print Official Debit Voucher">
                                                <i class="fa-solid fa-print"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($txn['view_url'])): ?>
                                            <a href="<?= $txn['view_url'] ?>" target="_blank" class="btn btn-outline-primary btn-xs" title="View Source Module Details">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($transactions)): ?>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="5" class="text-end fw-bold text-dark ps-3">Total (This Page):</th>
                            <th class="text-end fw-bold text-success fs-6">৳ <?= number_format($pageTotal, 2) ?></th>
                            <th colspan="4" class="text-muted extra-small">
                                Showing <?= count($transactions) ?> of <?= number_format($totalTransactionsCount ?? count($transactions)) ?> transactions
                            </th>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>

        <!-- Pagination Controls -->
        <?php if (($totalPages ?? 1) > 1): ?>
            <div class="card-footer table-pagination-footer border-top py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="small text-muted">
                    Showing <strong><?= number_format((($currentPage ?? 1) - 1) * ($perPage ?? 50) + 1) ?></strong> to 
                    <strong><?= number_format(min(($currentPage ?? 1) * ($perPage ?? 50), $totalTransactionsCount ?? count($transactions))) ?></strong> of 
                    <strong><?= number_format($totalTransactionsCount ?? count($transactions)) ?></strong> transactions
                </div>
                <nav aria-label="Transactions Pagination">
                    <ul class="pagination pagination-sm mb-0">
                        <?php 
                        $pageQuery = $currentFilters;
                        if (($currentPage ?? 1) > 1): 
                            $pageQuery['page'] = $currentPage - 1;
                        ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= $base_url ?>/branch-disbursements?<?= http_build_query($pageQuery) ?>">&laquo; Prev</a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled"><span class="page-link">&laquo; Prev</span></li>
                        <?php endif; ?>

                        <?php
                        $startPage = max(1, ($currentPage ?? 1) - 2);
                        $endPage = min($totalPages ?? 1, ($currentPage ?? 1) + 2);
                        if ($startPage > 1): 
                            $pageQuery['page'] = 1;
                        ?>
                            <li class="page-item"><a class="page-link" href="<?= $base_url ?>/branch-disbursements?<?= http_build_query($pageQuery) ?>">1</a></li>
                            <?php if ($startPage > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                        <?php endif; ?>

                        <?php for ($p = $startPage; $p <= $endPage; $p++): 
                            $pageQuery['page'] = $p;
                        ?>
                            <li class="page-item <?= ($p === ($currentPage ?? 1)) ? 'active' : '' ?>">
                                <a class="page-link" href="<?= $base_url ?>/branch-disbursements?<?= http_build_query($pageQuery) ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($endPage < ($totalPages ?? 1)): 
                            $pageQuery['page'] = $totalPages;
                        ?>
                            <?php if ($endPage < ($totalPages ?? 1) - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                            <li class="page-item"><a class="page-link" href="<?= $base_url ?>/branch-disbursements?<?= http_build_query($pageQuery) ?>"><?= $totalPages ?></a></li>
                        <?php endif; ?>

                        <?php 
                        if (($currentPage ?? 1) < ($totalPages ?? 1)): 
                            $pageQuery['page'] = ($currentPage ?? 1) + 1;
                        ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= $base_url ?>/branch-disbursements?<?= http_build_query($pageQuery) ?>">Next &raquo;</a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled"><span class="page-link">Next &raquo;</span></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Client-side instant filter for the transactions table
document.addEventListener('DOMContentLoaded', function () {
    const liveFilterInput = document.getElementById('tableLiveFilter');
    if (liveFilterInput) {
        liveFilterInput.addEventListener('keyup', function () {
            const term = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('#disbursementsTable tbody tr.disbursement-row');
            let visibleCount = 0;

            rows.forEach(function (row) {
                const text = row.innerText.toLowerCase();
                if (text.indexOf(term) > -1) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});

// CSV Export functionality
function exportDisbursementsTableToCSV() {
    const rows = document.querySelectorAll('#disbursementsTable tr');
    let csv = [];
    
    rows.forEach(function (row) {
        if (row.style.display === 'none') return;
        let rowData = [];
        const cols = row.querySelectorAll('th, td');
        
        // Skip the last action column
        for (let i = 0; i < cols.length - 1; i++) {
            let text = cols[i].innerText.replace(/(\r\n|\n|\r)/gm, ' ').replace(/"/g, '""').trim();
            rowData.push('"' + text + '"');
        }
        if (rowData.length > 0) {
            csv.push(rowData.join(','));
        }
    });

    const csvFile = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const downloadLink = document.createElement('a');
    downloadLink.download = 'Office_Disbursements_Report_' + (new Date().toISOString().slice(0, 10)) + '.csv';
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
</script>




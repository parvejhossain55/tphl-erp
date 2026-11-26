<?php
$base_url            = $GLOBALS['base_url'];
$refunds             = $refunds ?? [];
$summaryStats        = $summaryStats ?? [];
$projects            = $projects ?? [];
$companyBanks        = $companyBanks ?? [];
$allBangladeshBanks  = $allBangladeshBanks ?? [];
$filters             = $filters ?? [];
$page                = $page ?? 1;
$perPage             = $perPage ?? 'all';
$totalRecords        = $totalRecords ?? count($refunds);
$totalPages          = $totalPages ?? 1;
$canCreate           = $canCreate ?? false;
$userRole            = $userRole ?? ($_SESSION['user']['role_id'] ?? 0);
$currentUserId       = $_SESSION['user']['id'] ?? 0;

$statusMap = [
    'pending'  => ['label' => 'Pending',  'class' => 'refund-badge refund-badge-pending',  'icon' => 'fa-clock'],
    'approved' => ['label' => 'Approved', 'class' => 'refund-badge refund-badge-approved', 'icon' => 'fa-badge-check'],
    'refunded' => ['label' => 'Paid',     'class' => 'refund-badge refund-badge-refunded', 'icon' => 'fa-check-double'],
    'rejected' => ['label' => 'Rejected', 'class' => 'refund-badge refund-badge-rejected', 'icon' => 'fa-circle-xmark'],
];
?>

<style>
/* Client Refund Report — Clean & Minimal UI */
.refund-kpi-grid {
    display: grid !important;
    grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
    gap: 12px !important;
    width: 100% !important;
}
.refund-kpi-grid::before,
.refund-kpi-grid::after {
    display: none !important;
    content: none !important;
}
.refund-kpi-grid > div {
    min-width: 0 !important;
    width: 100% !important;
}
@media (max-width: 1399.98px) {
    .refund-kpi-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
    }
}
@media (max-width: 991.98px) {
    .refund-kpi-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    }
}
@media (max-width: 575.98px) {
    .refund-kpi-grid {
        grid-template-columns: 1fr !important;
    }
}
.refund-kpi-card {
    transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    background: #fff;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
    cursor: pointer;
    user-select: none;
}
.refund-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
    border-color: #cbd5e1;
}
.refund-kpi-card.active-card {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25), 0 4px 12px rgba(59, 130, 246, 0.1) !important;
}
.refund-kpi-card.active-card.pending-card {
    border-color: #f59e0b !important;
    box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.25), 0 4px 12px rgba(245, 158, 11, 0.12) !important;
}
.refund-kpi-card.active-card.paid-card {
    border-color: #10b981 !important;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.25), 0 4px 12px rgba(16, 185, 129, 0.12) !important;
}
.refund-kpi-card.active-card.rejected-card {
    border-color: #ef4444 !important;
    box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.25), 0 4px 12px rgba(239, 68, 68, 0.12) !important;
}
.refund-kpi-label {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.4px;
    text-transform: uppercase;
    color: #64748b;
}
.refund-kpi-value {
    font-size: 21px;
    font-weight: 700;
    color: #0f172a;
    font-variant-numeric: tabular-nums;
    letter-spacing: -0.3px;
}
.refund-kpi-meta {
    font-size: 11.5px;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.kpi-icon-wrap {
    width: 42px;
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    font-size: 17px;
    flex-shrink: 0;
}
.kpi-icon-amber  { background: rgba(245, 158, 11, 0.12); color: #d97706; }
.kpi-icon-green  { background: rgba(16, 185, 129, 0.12); color: #059669; }
.kpi-icon-blue   { background: rgba(59, 130, 246, 0.12); color: #2563eb; }
.kpi-icon-purple { background: rgba(139, 92, 246, 0.12); color: #7c3aed; }
.kpi-icon-red    { background: rgba(239, 68, 68, 0.12); color: #dc2626; }

/* Minimal Soft Status Badges */
.refund-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
}
.refund-badge-pending  { background: #fef3c7; color: #92400e; }
.refund-badge-approved { background: #e0f2fe; color: #075985; }
.refund-badge-refunded { background: #dcfce7; color: #166534; }
.refund-badge-rejected { background: #fee2e2; color: #991b1b; }

.kpi-mini-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
}

/* Project-Wise Horizontal Strip */
.project-track-strip {
    display: flex;
    flex-wrap: nowrap;
    gap: 8px;
    overflow-x: auto;
    padding-bottom: 4px;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}
.project-track-strip.expanded {
    flex-wrap: wrap;
    overflow-x: visible;
}
.project-track-strip::-webkit-scrollbar { height: 5px; }
.project-track-strip::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

.project-track-pill {
    cursor: pointer;
    transition: all 0.16s ease;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    border-radius: 8px;
    padding: 7px 11px;
    min-width: 175px;
    flex-shrink: 0;
    text-decoration: none;
    display: inline-block;
    color: #1e293b;
}
.project-track-pill:hover {
    border-color: #94a3b8;
    background: #f1f5f9;
    color: #0f172a;
}
.project-track-pill.active {
    background: #0f172a;
    color: #fff !important;
    border-color: #0f172a;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.15);
}
.project-track-pill.active .text-muted,
.project-track-pill.active small,
.project-track-pill.active .proj-ret,
.project-track-pill.active .proj-ref {
    color: rgba(255,255,255,0.85) !important;
}
.project-track-pill.active .badge-sub {
    background: rgba(255,255,255,0.2) !important;
    color: #fff !important;
}
.badge-sub {
    font-size: 10.5px;
    background: #e2e8f0;
    color: #475569;
    border-radius: 999px;
    padding: 1px 7px;
    font-weight: 600;
}

/* Clean Minimal Table */
.refund-card-surface {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
}
.refund-filter-bar {
    display: grid;
    grid-template-columns: minmax(180px, 1.8fr) minmax(160px, 1.4fr) minmax(130px, 1fr) minmax(230px, 1.4fr) 76px auto;
    gap: 10px;
    align-items: end;
}
.refund-filter-bar .rf-label {
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    margin-bottom: 4px;
    display: block;
}
.refund-filter-bar .form-control,
.refund-filter-bar .form-select,
.refund-filter-bar .input-group-text,
.refund-filter-bar .btn {
    height: 34px;
    font-size: 12.5px;
    border-color: #e2e8f0;
}
.refund-filter-bar .form-control:focus,
.refund-filter-bar .form-select:focus {
    border-color: #94a3b8;
    box-shadow: none;
}
@media (max-width: 1199.98px) {
    .refund-filter-bar {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}
@media (max-width: 767.98px) {
    .refund-filter-bar {
        grid-template-columns: 1fr;
    }
}
.table-report {
    border-collapse: separate;
    border-spacing: 0;
}
.table-report thead th {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    background-color: #f8fafc;
    color: #64748b;
    font-weight: 600;
    vertical-align: middle;
    padding: 11px 14px;
    border-bottom: 1px solid #e2e8f0;
    border-top: none;
    white-space: nowrap;
}
.table-report tbody td {
    font-size: 13px;
    vertical-align: middle;
    padding: 12px 14px;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
}
.table-report tbody tr:hover td {
    background-color: #f8fafc;
}
.table-report tfoot td {
    padding: 12px 14px;
    background-color: #f8fafc;
    border-top: 1px solid #e2e8f0;
    font-size: 12.5px;
}
.loading-overlay {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(255, 255, 255, 0.75);
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    backdrop-filter: blur(1px);
}
.client-name-cell {
    max-width: 250px;
}
.client-reason-text {
    font-size: 11.5px;
    color: #64748b;
    max-width: 235px;
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.amount-cell {
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}
.retention-highlight {
    background: rgba(220, 38, 38, 0.07);
    padding: 3px 8px;
    border-radius: 6px;
    font-weight: 600;
    color: #dc2626;
    white-space: nowrap;
}
.refund-highlight {
    background: rgba(5, 150, 105, 0.08);
    padding: 3px 8px;
    border-radius: 6px;
    font-weight: 700;
    color: #059669;
    white-space: nowrap;
}
.proj-tag {
    display: inline-block;
    font-size: 11px;
    font-weight: 600;
    color: #2563eb;
    background: #eff6ff;
    padding: 2px 8px;
    border-radius: 6px;
    margin-bottom: 3px;
}

/* ========================================================================= */
/* PRINT-SPECIFIC CSS: CLEAN, COMPACT LANDSCAPE REPORT (NO LOGO / NO SPACE)  */
/* ========================================================================= */
@media print {
    @page {
        size: A4 landscape;
        margin: 6mm 7mm;
    }
    html, body {
        background: #fff !important;
        color: #111 !important;
        font-size: 9.5px !important;
        line-height: 1.25 !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        height: auto !important;
        overflow: visible !important;
    }

    /* 1. HIDE UNIVERSAL PRINT PAD (NO LOGO, NO BORDERS, NO WATERMARK, NO PAD FOOTER) */
    .universal-print-pad,
    .upp-left-border,
    .upp-right-border,
    .upp-watermark,
    .upp-header,
    .upp-footer {
        display: none !important;
        visibility: hidden !important;
        height: 0 !important;
        width: 0 !important;
    }

    /* 2. REMOVE ALL EXTRA LAYOUT MARGINS / PADDING (NO BLANK SPACE) */
    .page__full-wrapper,
    .page__body-wrapper,
    .app__slide-wrapper,
    .main-content,
    .page-body,
    .container-fluid {
        position: static !important;
        margin: 0 !important;
        padding: 0 !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        min-height: 0 !important;
        background: #fff !important;
    }

    /* 3. HIDE ALL WEB UI, SIDEBARS, NAV, SYSTEM FOOTERS, BREADCRUMBS, MODALS */
    #sidebar,
    .app-sidebar,
    .app__sidebar,
    .main-sidebar,
    .sidebar,
    .app__header__area,
    .app__header-inner,
    .preloader,
    .body__overlay,
    .main-header,
    .app-header,
    .navbar,
    .header-wrapper,
    footer,
    .main-footer,
    .app__footer__area,
    .footer,
    .btn,
    button,
    .modal,
    .modal-backdrop,
    #tableLoadingOverlay,
    .page-header-breadcrumb,
    .d-print-none,
    #kpiStatsRow,
    #projectTrackCard,
    #filterCard,
    .card-footer,
    .refund-kpi-card {
        display: none !important;
        visibility: hidden !important;
        height: 0 !important;
        width: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    /* 4. COMPACT PRINT HEADER (NO LOGO, ZERO EXTRA GAP) */
    .print-report-header {
        display: block !important;
        margin: 0 0 6px 0 !important;
        padding: 0 0 5px 0 !important;
        border-bottom: 1.5px solid #1e293b !important;
    }
    .print-report-header h4 {
        font-size: 13px !important;
        line-height: 1.2 !important;
        margin: 0 !important;
    }
    .print-summary-strip {
        display: flex !important;
        flex-wrap: wrap !important;
        justify-content: space-between !important;
        gap: 8px !important;
        margin-top: 4px !important;
        padding: 4px 6px !important;
        background: #f8fafc !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 3px !important;
        font-size: 9px !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* 5. CLEAN FULL-WIDTH TABLE CONTAINER */
    .refund-card-surface,
    .card.custom-card,
    .card,
    .card-body {
        border: none !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        margin: 0 !important;
        padding: 0 !important;
        background: transparent !important;
        overflow: visible !important;
        width: 100% !important;
    }

    .card-header {
        display: none !important;
    }

    .table-responsive {
        overflow: visible !important;
        display: block !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    /* 6. FIT ALL 9 COLUMNS CLEANLY WITHOUT CLIPPING */
    .table-report {
        width: 100% !important;
        table-layout: fixed !important;
        border-collapse: collapse !important;
        border: 1px solid #334155 !important;
        font-size: 9px !important;
        margin: 0 !important;
    }

    .table-report thead {
        display: table-header-group !important;
    }

    .table-report tfoot {
        display: table-row-group !important;
        font-weight: 700 !important;
        background-color: #f1f5f9 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .table-report tr {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    .table-report thead th {
        background-color: #f1f5f9 !important;
        color: #0f172a !important;
        font-weight: 700 !important;
        border: 1px solid #94a3b8 !important;
        padding: 4px 5px !important;
        font-size: 8.5px !important;
        letter-spacing: 0.2px !important;
        min-width: 0 !important;
        white-space: normal !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .table-report tbody td,
    .table-report tfoot td {
        border: 1px solid #cbd5e1 !important;
        padding: 4px 5px !important;
        color: #111827 !important;
        font-size: 9px !important;
        line-height: 1.25 !important;
        min-width: 0 !important;
        max-width: none !important;
        vertical-align: top !important;
        word-wrap: break-word !important;
        overflow-wrap: break-word !important;
    }

    /* Exact proportional column widths (sum = 100%, 10th Action col hidden) */
    .table-report th:nth-child(1), .table-report td:nth-child(1) { width: 3.5% !important; }
    .table-report th:nth-child(2), .table-report td:nth-child(2) { width: 12% !important; }
    .table-report th:nth-child(3), .table-report td:nth-child(3) { width: 20.5% !important; }
    .table-report th:nth-child(4), .table-report td:nth-child(4) { width: 15.5% !important; }
    .table-report th:nth-child(5), .table-report td:nth-child(5) { width: 10.5% !important; }
    .table-report th:nth-child(6), .table-report td:nth-child(6) { width: 9.5% !important; }
    .table-report th:nth-child(7), .table-report td:nth-child(7) { width: 10.5% !important; }
    .table-report th:nth-child(8), .table-report td:nth-child(8) { width: 7.5% !important; }
    .table-report th:nth-child(9), .table-report td:nth-child(9) { width: 10.5% !important; }

    .table-report .amount-cell {
        white-space: nowrap !important;
        font-size: 9px !important;
    }

    .table-report .text-nowrap {
        white-space: normal !important;
    }

    .table-report .client-name-cell {
        max-width: none !important;
    }

    .table-report .client-reason-text {
        max-width: none !important;
        white-space: normal !important;
        overflow: visible !important;
        text-overflow: clip !important;
        font-size: 8.5px !important;
        color: #475569 !important;
        margin-top: 2px !important;
    }

    .table-report .fs-13,
    .table-report .fs-12 {
        font-size: 9px !important;
    }

    .table-report .fs-11 {
        font-size: 8px !important;
    }

    .proj-tag {
        background: transparent !important;
        color: #1e40af !important;
        padding: 0 !important;
        margin-bottom: 1px !important;
        font-size: 8.5px !important;
        font-weight: 700 !important;
        border-radius: 0 !important;
    }

    .retention-highlight {
        background: transparent !important;
        color: #b91c1c !important;
        font-weight: 700 !important;
        padding: 0 !important;
    }

    .refund-highlight {
        background: transparent !important;
        color: #15803d !important;
        font-weight: 700 !important;
        padding: 0 !important;
        font-size: 9px !important;
    }

    .refund-badge {
        padding: 1px 5px !important;
        font-size: 8px !important;
        border: 1px solid #94a3b8 !important;
        border-radius: 3px !important;
        gap: 3px !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .refund-badge i {
        display: none !important;
    }

    .badge {
        border: 1px solid #666 !important;
        color: #000 !important;
        background: transparent !important;
        padding: 1px 4px !important;
        font-size: 8px !important;
    }

    .col-action {
        display: none !important;
    }
}
</style>

<!-- ========================================================================= -->
<!-- PRINT-ONLY COMPACT CORPORATE REPORT HEADER (NO LOGO, NO EXTRA SPACE)      -->
<!-- ========================================================================= -->
<?php
    $pDateFrom = trim((string)($filters['date_from'] ?? ''));
    $pDateTo   = trim((string)($filters['date_to'] ?? ''));
    if ($pDateFrom !== '' && $pDateTo !== '') {
        $printPeriodLabel = date('d M Y', strtotime($pDateFrom)) . ' to ' . date('d M Y', strtotime($pDateTo));
    } elseif ($pDateFrom !== '') {
        $printPeriodLabel = 'From ' . date('d M Y', strtotime($pDateFrom)) . ' to Present';
    } elseif ($pDateTo !== '') {
        $printPeriodLabel = 'Up to ' . date('d M Y', strtotime($pDateTo));
    } else {
        $rowTimestamps = [];
        foreach ($refunds as $rr) {
            if (!empty($rr['created_at'])) {
                $ts = strtotime($rr['created_at']);
                if ($ts) $rowTimestamps[] = $ts;
            }
        }
        if (!empty($rowTimestamps)) {
            $printPeriodLabel = 'All Dates (' . date('d M Y', min($rowTimestamps)) . ' to ' . date('d M Y', max($rowTimestamps)) . ')';
        } else {
            $printPeriodLabel = 'All Dates';
        }
    }
?>
<div class="print-report-header d-none d-print-block">
    <div class="d-flex align-items-end justify-content-between">
        <div>
            <h4 class="fw-bold mb-0 text-dark" style="letter-spacing: 0.3px;">
                <?= htmlspecialchars(strtoupper($siteSettings['site_title'] ?? 'THE PREMIUM HOMES LTD.')) ?>
            </h4>
            <div style="font-size: 10px; color: #475569; font-weight: 600;">
                Client Refund Statement &amp; Transaction Registry
                &nbsp;|&nbsp;
                <span style="color: #0f172a;"><strong>Report Period:</strong> <span id="printDateRange"><?= htmlspecialchars($printPeriodLabel) ?></span></span>
            </div>
        </div>
        <div class="text-end" style="font-size: 9px; color: #475569;">
            <div><strong>Print Date:</strong> <?= date('d M Y, h:i A') ?></div>
            <div><strong>Printed By:</strong> <?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Accounts Department') ?></div>
        </div>
    </div>
    <div class="print-summary-strip text-dark">
        <div><strong>Records:</strong> <span id="printTotalRecords"><?= $totalRecords ?></span> Cases</div>
        <div><strong>Total Paid:</strong> <span id="printTotalPaid">৳ <?= number_format($summaryStats['total_verified_paid'] ?? 0, 2) ?></span></div>
        <div><strong>Retention:</strong> <span id="printTotalRetention">৳ <?= number_format($summaryStats['total_retention'] ?? 0, 2) ?></span></div>
        <div><strong>Net Refund:</strong> <span id="printTotalRefund">৳ <?= number_format($summaryStats['total_refund_amount'] ?? 0, 2) ?></span></div>
        <div><strong>Disbursed Paid:</strong> <span id="printTotalDisbursed">৳ <?= number_format($summaryStats['total_refunded_paid'] ?? 0, 2) ?></span></div>
    </div>
</div>

<!-- PAGE HEADER (WEB VIEW ONLY) -->
<div class="d-md-flex d-block align-items-center justify-content-between my-3 page-header-breadcrumb d-print-none">
    <div>
        <h4 class="page-title fw-bold fs-18 mb-1">
            <i class="fa-light fa-hand-holding-dollar text-primary me-2"></i>Client Refund Requests & Statement Report
        </h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 fs-12">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/accounts-dashboard">Accounts</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/clients-cash-in-history">Cash In</a></li>
                <li class="breadcrumb-item active" aria-current="page">Client Refunds</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2 mt-md-0 mt-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.location.reload();" title="Refresh Data">
            <i class="fa-light fa-arrows-rotate me-1"></i> Refresh
        </button>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print();" title="Print Statement Report">
            <i class="fa-light fa-print me-1"></i> Print Report
        </button>
    </div>
</div>

<div class="container-fluid px-0">

    <div class="d-print-none">
        <?php include __DIR__ . '/../../layouts/management_approval_notice.php'; ?>
    </div>

    <!-- ========================================================================= -->
    <!-- 1. SUMMARY KPI STATS (WEB VIEW ONLY) -->
    <!-- ========================================================================= -->
    <div class="d-print-none mb-3">
        <div class="refund-kpi-grid" id="kpiStatsRow">
        <!-- 1. Total Refund Amount (All Cases) -->
        <div>
            <div class="card refund-kpi-card h-100 mb-0 active-card" id="cardTotalRefund" onclick="filterByStatus('all')" title="Click to view All refunds">
                <div class="card-body p-3">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div class="min-w-0">
                            <div class="refund-kpi-label">Total Refund</div>
                            <div class="refund-kpi-value mt-1 mb-1" id="kpiTotalRefund">
                                ৳ <?= number_format($summaryStats['total_refund_amount'] ?? 0, 2) ?>
                            </div>
                            <div class="refund-kpi-meta d-flex align-items-center gap-1">
                                <span class="fw-semibold text-primary" id="kpiTotalRefundCount"><?= number_format($summaryStats['active_count'] ?? 0) ?> Cases</span>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-10 px-1 py-0.5" style="border-radius:4px;"><i class="fa-light fa-list-check me-0.5"></i>All</span>
                            </div>
                        </div>
                        <div class="kpi-icon-wrap kpi-icon-blue">
                            <i class="fa-solid fa-money-bill-transfer"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Pending Refund (Clickable Filter Card) -->
        <div>
            <div class="card refund-kpi-card h-100 mb-0 pending-card" id="cardPendingRefund" onclick="filterByStatus('pending')" title="Click to view only Pending refunds">
                <div class="card-body p-3">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div class="min-w-0">
                            <div class="refund-kpi-label text-warning">Pending Refund</div>
                            <div class="refund-kpi-value mt-1 mb-1 text-warning" id="kpiPendingRefund">
                                ৳ <?= number_format($summaryStats['pending_refund_amount'] ?? 0, 2) ?>
                            </div>
                            <div class="refund-kpi-meta d-flex align-items-center gap-1">
                                <span class="fw-semibold text-warning" id="kpiPendingCount"><?= number_format($summaryStats['pending_count'] ?? 0) ?> Cases</span>
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle fs-10 px-1 py-0.5" style="border-radius:4px;"><i class="fa-light fa-filter me-0.5"></i>Filter</span>
                            </div>
                        </div>
                        <div class="kpi-icon-wrap kpi-icon-amber">
                            <i class="fa-solid fa-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Total Retention Amount -->
        <div>
            <div class="card refund-kpi-card h-100 mb-0" id="cardTotalRetention">
                <div class="card-body p-3">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div class="min-w-0">
                            <div class="refund-kpi-label">Total Retention</div>
                            <div class="refund-kpi-value mt-1 mb-1" id="kpiTotalRetention">
                                ৳ <?= number_format($summaryStats['total_retention'] ?? 0, 2) ?>
                            </div>
                            <div class="refund-kpi-meta">
                                <span class="fw-semibold text-warning" id="kpiRetentionCount"><?= number_format($summaryStats['retention_count'] ?? 0) ?> Cases</span>
                            </div>
                        </div>
                        <div class="kpi-icon-wrap kpi-icon-amber">
                            <i class="fa-solid fa-piggy-bank"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Total Refunded / Paid Amount (Clickable Filter Card) -->
        <div>
            <div class="card refund-kpi-card h-100 mb-0 paid-card" id="cardPaidRefund" onclick="filterByStatus('refunded')" title="Click to view only Paid refunds">
                <div class="card-body p-3">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div class="min-w-0">
                            <div class="refund-kpi-label">Total Paid</div>
                            <div class="refund-kpi-value mt-1 mb-1" id="kpiTotalDisbursed">
                                ৳ <?= number_format($summaryStats['total_refunded_paid'] ?? 0, 2) ?>
                            </div>
                            <div class="refund-kpi-meta d-flex align-items-center gap-1">
                                <span class="fw-semibold text-success" id="kpiPaidCount"><?= number_format($summaryStats['refunded_count'] ?? 0) ?> Cases</span>
                                <span class="badge bg-success-subtle text-success border border-success-subtle fs-10 px-1 py-0.5" style="border-radius:4px;"><i class="fa-light fa-filter me-0.5"></i>Filter</span>
                            </div>
                        </div>
                        <div class="kpi-icon-wrap kpi-icon-green">
                            <i class="fa-solid fa-money-check-dollar"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Total Rejected Amount (Clickable Filter Card) -->
        <div>
            <div class="card refund-kpi-card h-100 mb-0 rejected-card" id="cardRejectedRefund" onclick="filterByStatus('rejected')" title="Click to view Rejected refunds">
                <div class="card-body p-3">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div class="min-w-0">
                            <div class="refund-kpi-label">Total Rejected</div>
                            <div class="refund-kpi-value mt-1 mb-1" id="kpiRejectedAmount">
                                ৳ <?= number_format($summaryStats['rejected_amount'] ?? 0, 2) ?>
                            </div>
                            <div class="refund-kpi-meta d-flex align-items-center gap-1">
                                <span class="fw-semibold text-danger" id="kpiRejectedCount"><?= number_format($summaryStats['rejected_count'] ?? 0) ?> Cases</span>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-10 px-1 py-0.5" style="border-radius:4px;"><i class="fa-light fa-filter me-0.5"></i>Filter</span>
                            </div>
                        </div>
                        <div class="kpi-icon-wrap kpi-icon-red">
                            <i class="fa-solid fa-circle-xmark"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. CLEAN MINIMAL SEARCH & FILTER BAR (WEB VIEW ONLY) -->
    <!-- ========================================================================= -->
    <div class="refund-card-surface mb-3 d-print-none" id="filterCard">
        <div class="p-2 px-3 py-2">
            <form id="refundFilterForm" class="refund-filter-bar" onsubmit="event.preventDefault(); applyFilters(1);">
                <!-- Search Keyword -->
                <div>
                    <label class="rf-label" for="filterSearch">Search</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted pe-1"><i class="fa-light fa-magnifying-glass"></i></span>
                        <input type="text" id="filterSearch" name="search" class="form-control border-start-0 ps-1"
                               placeholder="Client, phone, flat, reason..."
                               value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                    </div>
                </div>

                <!-- Project Select -->
                <div>
                    <label class="rf-label" for="filterProject">Project</label>
                    <select id="filterProject" name="project_id" class="form-select form-select-sm" onchange="applyFilters(1)">
                        <option value="all">All Projects</option>
                        <?php foreach ($projects as $pr): ?>
                            <option value="<?= $pr['id'] ?>" <?= (!empty($filters['project_id']) && $filters['project_id'] == $pr['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pr['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Status Select -->
                <div>
                    <label class="rf-label" for="filterStatus">Status</label>
                    <select id="filterStatus" name="status" class="form-select form-select-sm" onchange="updateCardActiveState(this.value); applyFilters(1)">
                        <option value="all">All Statuses</option>
                        <option value="pending" <?= (!empty($filters['status']) && $filters['status'] === 'pending') ? 'selected' : '' ?>>Pending</option>
                        <option value="approved" <?= (!empty($filters['status']) && $filters['status'] === 'approved') ? 'selected' : '' ?>>Approved</option>
                        <option value="refunded" <?= (!empty($filters['status']) && $filters['status'] === 'refunded') ? 'selected' : '' ?>>Paid</option>
                        <option value="rejected" <?= (!empty($filters['status']) && $filters['status'] === 'rejected') ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>

                <!-- Date Range -->
                <div>
                    <label class="rf-label">Date Range</label>
                    <div class="d-flex align-items-center gap-1">
                        <input type="date" id="filterDateFrom" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>" title="From Date" onchange="applyFilters(1)">
                        <span class="text-muted fs-11">–</span>
                        <input type="date" id="filterDateTo" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>" title="To Date" onchange="applyFilters(1)">
                    </div>
                </div>

                <!-- Per Page (Default: All Rows) -->
                <div>
                    <label class="rf-label" for="filterPerPage">Show</label>
                    <select id="filterPerPage" name="per_page" class="form-select form-select-sm" onchange="applyFilters(1)">
                        <option value="all" <?= ($perPage === 'all' || $perPage >= 1000) ? 'selected' : '' ?>>All Rows</option>
                        <option value="15" <?= $perPage == 15 ? 'selected' : '' ?>>15</option>
                        <option value="25" <?= $perPage == 25 ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= $perPage == 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $perPage == 100 ? 'selected' : '' ?>>100</option>
                    </select>
                </div>

                <!-- Actions -->
                <div class="d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm px-3 fw-semibold" title="Apply Filter">
                        <i class="fa-light fa-filter me-1"></i> Filter
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm px-2" onclick="resetFilters()" title="Reset Filters">
                        <i class="fa-light fa-rotate-left"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 4. REFUND REPORT DATA TABLE (PRINT & WEB) -->
    <!-- ========================================================================= -->
    <div class="refund-card-surface position-relative overflow-hidden">
        <!-- Spinner Overlay -->
        <div id="tableLoadingOverlay" class="loading-overlay d-print-none" style="display:none;">
            <div class="spinner-border text-primary" role="status" style="width: 2.2rem; height: 2.2rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <div class="px-3 py-2.5 border-bottom d-flex align-items-center justify-content-between d-print-none">
            <div class="d-flex align-items-center gap-2">
                <h6 class="mb-0 fw-semibold fs-14 text-dark">
                    Refund Transactions
                </h6>
                <span class="badge-sub" id="recordCountBadge">
                    <?= $totalRecords ?>
                </span>
            </div>
            <div class="fs-12 text-muted" id="paginationSummaryText">
                <?php if ($perPage === 'all' || !is_numeric($perPage) || (int)$perPage >= $totalRecords || $totalPages <= 1): ?>
                    Showing all <?= $totalRecords ?> entries
                <?php else: ?>
                    Showing <?= min(1, $totalRecords) ?>–<?= min((int)$perPage, $totalRecords) ?> of <?= $totalRecords ?> entries
                <?php endif; ?>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-report mb-0" id="refundTable">
                    <thead>
                        <tr>
                            <th style="width: 44px;" class="text-center">#</th>
                            <th style="min-width: 145px;">Requested By</th>
                            <th style="min-width: 200px;">Client &amp; Note</th>
                            <th style="min-width: 165px;">Project &amp; Flat</th>
                            <th style="min-width: 115px;" class="text-end">Total Paid</th>
                            <th style="min-width: 110px;" class="text-end">Retention</th>
                            <th style="min-width: 120px;" class="text-end">Net Refund</th>
                            <th style="min-width: 110px;" class="text-center">Status</th>
                            <th style="min-width: 130px;">Approved By</th>
                            <th style="min-width: 190px;" class="text-center col-action d-print-none">Action</th>
                        </tr>
                    </thead>
                    <tbody id="refundTableBody">
                        <?php if (empty($refunds)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fa-light fa-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        <h6 class="fs-14 fw-semibold mb-1">No refund records found</h6>
                                        <p class="fs-12 text-muted mb-0">Try changing your search keywords or clear the filters.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($refunds as $idx => $r): 
                                $sInfo = $statusMap[$r['status']] ?? ['label' => ucfirst($r['status']), 'class' => 'refund-badge bg-light text-secondary', 'icon' => 'fa-circle-info'];
                                $isAllRows = ($perPage === 'all' || !is_numeric($perPage));
                                $perPageNum = $isAllRows ? count($refunds) : (int)$perPage;
                                $rowNum = $isAllRows ? ($idx + 1) : (($page - 1) * $perPageNum + $idx + 1);
                            ?>
                            <tr id="refund-row-<?= $r['id'] ?>">
                                <td class="text-center text-muted font-monospace fs-12"><?= $rowNum ?></td>
                                <td class="fs-12 text-nowrap">
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($r['requested_by_name'] ?? '—') ?></div>
                                    <span class="text-muted fs-11 d-block mt-0.5">
                                        <?= !empty($r['created_at']) ? date('d M Y, h:i A', strtotime($r['created_at'])) : '—' ?>
                                    </span>
                                </td>
                                <td class="client-name-cell">
                                    <div class="fw-semibold text-dark fs-13"><?= htmlspecialchars($r['client_name'] ?? '—') ?></div>
                                    <?php if (!empty($r['client_phone'])): ?>
                                        <div class="text-muted fs-11 mt-0.5">
                                            <i class="fa-light fa-phone me-1"></i><?= htmlspecialchars($r['client_phone']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($r['reason'])): ?>
                                        <span class="client-reason-text mt-1" title="<?= htmlspecialchars($r['reason']) ?>">
                                            <?= htmlspecialchars($r['reason']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="proj-tag">
                                        <?= htmlspecialchars($r['project_name'] ?? '—') ?>
                                    </span>
                                    <div class="fw-medium text-dark fs-12">
                                        <?= htmlspecialchars($r['flat_name'] ?? '—') ?>
                                        <?php if (!empty($r['floor_no'])): ?>
                                            <span class="text-muted fs-11">(Floor <?= $r['floor_no'] ?>)</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-end fw-semibold text-dark font-monospace amount-cell">
                                    ৳ <?= number_format($r['total_paid'], 2) ?>
                                </td>
                                <td class="text-end font-monospace amount-cell">
                                    <span class="retention-highlight">
                                        ৳ <?= number_format($r['retention_amount'], 2) ?>
                                    </span>
                                </td>
                                <td class="text-end font-monospace amount-cell">
                                    <span class="refund-highlight fs-13">
                                        ৳ <?= number_format($r['refund_amount'], 2) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="<?= $sInfo['class'] ?>">
                                        <i class="fa-solid <?= $sInfo['icon'] ?>"></i><?= $sInfo['label'] ?>
                                    </span>
                                </td>
                                <td class="fs-12">
                                    <?php if (!empty($r['approved_by_name'])): ?>
                                        <div class="fw-medium text-dark"><?= htmlspecialchars($r['approved_by_name']) ?></div>
                                        <span class="text-muted fs-11 d-block mt-0.5">
                                            <?= !empty($r['approved_at']) ? date('d M Y, h:i A', strtotime($r['approved_at'])) : '—' ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center text-nowrap col-action d-print-none">
                                    <div class="d-inline-flex align-items-center justify-content-center gap-1 text-nowrap">
                                        <a href="<?= $base_url ?>/client-refund/<?= $r['id'] ?>" class="btn btn-xs btn-outline-primary py-1 px-2" title="View Details">
                                            <i class="fa-light fa-eye"></i> View
                                        </a>
                                        <?php if (in_array($r['status'], ['approved', 'refunded'], true)): ?>
                                            <a href="<?= $base_url ?>/client-refund-receipt/<?= $r['id'] ?>" target="_blank" class="btn btn-xs btn-outline-success py-1 px-1.5" title="Print Refund Money Receipt">
                                                <i class="fa-light fa-receipt"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($r['status'] === 'approved' && in_array($userRole, [1, 3, 6, 10, 30, 36])): ?>
                                            <button type="button" class="btn btn-xs btn-success py-1 px-2 text-white" 
                                                    onclick="openQuickPayModal(<?= htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8') ?>)" 
                                                    title="Disburse Payment">
                                                <i class="fa-light fa-money-bill-wave me-1"></i> Pay
                                            </button>
                                        <?php elseif ($r['status'] === 'pending' && in_array($userRole, [1, 3, 6, 7, 9, 10, 20, 30, 33, 37])): ?>
                                            <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2" 
                                                    onclick="openQuickRetentionModal(<?= htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8') ?>)" 
                                                    title="Cut Retention Amount or %">
                                                <i class="fa-light fa-scissors"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if (!empty($r['booking_id'])): ?>
                                            <a href="<?= $base_url ?>/clients-cash-in-history/<?= (int)$r['booking_id'] ?>" target="_blank"
                                               class="btn btn-xs btn-outline-dark py-1 px-2"
                                               title="Open Client Ledger & Verify Pending Payments">
                                                <i class="fa-light fa-book-open me-1"></i> Ledger
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($r['status'] === 'pending' && $r['requested_by'] == $currentUserId): ?>
                                            <form action="<?= $base_url ?>/client-refund-delete/<?= $r['id'] ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete this refund request?');">
                                                <button type="submit" class="btn btn-xs btn-outline-danger py-1 px-1.5" title="Delete">
                                                    <i class="fa-light fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="fw-bold font-monospace">
                        <tr>
                            <td colspan="4" class="text-end text-uppercase fs-11 text-muted">Total:</td>
                            <td class="text-end amount-cell" id="tfootTotalPaid">৳ <?= number_format($summaryStats['total_verified_paid'] ?? 0, 2) ?></td>
                            <td class="text-end text-danger amount-cell" id="tfootTotalRetention">৳ <?= number_format($summaryStats['total_retention'] ?? 0, 2) ?></td>
                            <td class="text-end text-success amount-cell" id="tfootTotalRefund">৳ <?= number_format($summaryStats['total_refund_amount'] ?? 0, 2) ?></td>
                            <td colspan="2" class="text-center font-sans-serif fs-11 text-muted fw-normal">
                                <?= $summaryStats['total_requests'] ?? 0 ?> Cases (<?= $summaryStats['pending_count'] ?? 0 ?> Pending · <?= $summaryStats['approved_count'] ?? 0 ?> Approved · <?= $summaryStats['refunded_count'] ?? 0 ?> Paid)
                            </td>
                            <td class="col-action d-print-none"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Card Footer with Pagination Controls (WEB VIEW ONLY) -->
        <div class="card-footer bg-light py-2 px-3 border-top d-flex flex-wrap align-items-center justify-content-between gap-2 d-print-none">
            <div class="fs-12 text-muted" id="footerPaginationText">
                Showing page <strong id="curPageNum"><?= $page ?></strong> of <strong id="totalPageNum"><?= max(1, $totalPages) ?></strong>
            </div>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0" id="paginationControls">
                    <!-- Populated by JS -->
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 4.9 QUICK RETENTION CUT-OFF MODAL (Dual % and ৳ with Live Calculations) -->
<!-- ========================================================================= -->
<div class="modal fade d-print-none" id="quickRetentionModal" tabindex="-1" aria-labelledby="quickRetentionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark py-2.5 px-3">
                <h5 class="modal-title fs-15 fw-bold" id="quickRetentionModalLabel">
                    <i class="fa-light fa-scissors me-2"></i>Accounts Retention Cut-Off (<span id="qretModalRefundId">#0</span>)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickRetentionForm" action="" method="POST">
                <input type="hidden" name="ajax" value="1">
                <div class="modal-body p-3">
                    <!-- Client & Total Paid Banner -->
                    <div class="p-3 rounded-2 bg-light border mb-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="text-muted small d-block">Client & Flat:</span>
                                <strong class="text-dark fs-14" id="qretClientName">—</strong>
                                <div class="text-muted small" id="qretProjectFlat">—</div>
                            </div>
                            <div class="text-end">
                                <span class="text-muted small d-block">Total Verified Paid:</span>
                                <strong class="text-primary fs-16 font-monospace" id="qretTotalPaidDisplay">৳ 0.00</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Quick % Buttons -->
                    <div class="mb-3">
                        <label class="form-label fs-12 fw-semibold text-muted d-flex justify-content-between mb-1">
                            <span>Quick Cut-Off Percentage (%)</span>
                            <span class="text-danger fw-normal fs-11">Deducted from Total Paid</span>
                        </label>
                        <div class="d-flex flex-wrap gap-1">
                            <?php foreach ([0, 5, 10, 15, 20, 25, 30] as $pct): ?>
                                <button type="button" class="btn btn-xs btn-outline-warning py-1 px-2.5 text-dark fw-bold qret-pct-btn" data-pct="<?= $pct ?>"><?= $pct ?>%</button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Dual Inputs: % and ৳ -->
                    <div class="row g-2 mb-3">
                        <div class="col-5">
                            <label class="form-label fs-12 fw-semibold text-danger">Retention (%)</label>
                            <div class="input-group input-group-sm">
                                <input type="number" step="0.01" min="0" max="100" name="retention_percent" id="qretPercentInput" class="form-control fw-bold border-danger text-danger" placeholder="0 - 100">
                                <span class="input-group-text bg-danger text-white fw-bold">%</span>
                            </div>
                        </div>
                        <div class="col-7">
                            <label class="form-label fs-12 fw-semibold text-danger">Retention Amount (৳)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light fw-bold">৳</span>
                                <input type="number" step="0.01" min="0" name="retention_amount" id="qretAmountInput" class="form-control fw-bold border-danger text-danger" placeholder="0.00" required>
                            </div>
                        </div>
                    </div>

                    <!-- Net Refund Amount Preview -->
                    <div class="mb-2">
                        <label class="form-label fs-12 fw-semibold text-success">Net Refund to Client (৳)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-success text-white fw-bold">৳</span>
                            <input type="number" step="0.01" id="qretNetRefundDisplay" class="form-control fw-bold border-success text-success bg-light" readonly>
                        </div>
                        <small class="text-muted fs-11 d-block mt-1">
                            • Net Refund = Total Paid - Company Retention.<br>
                            • Once saved, this request will be forwarded for Management &amp; Role 20 approval.
                        </small>
                    </div>

                    <div id="qretErrorAlert" class="alert alert-danger py-1.5 px-2 small mb-0 mt-2" style="display: none;"></div>
                </div>
                <div class="modal-footer py-2 px-3 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="qretSubmitBtn" class="btn btn-warning btn-sm fw-bold px-3">
                        <i class="fa-light fa-check-double me-1"></i> Save Retention Cut-Off
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 5. QUICK DISBURSE PAYMENT MODAL (Accounts Payout matching Image 2) -->
<!-- ========================================================================= -->
<div class="modal fade d-print-none" id="quickPayModal" tabindex="-1" aria-labelledby="quickPayModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white py-2.5 px-3">
                <h5 class="modal-title fs-15 fw-bold text-white" id="quickPayModalLabel">
                    <i class="fa-solid fa-money-bill-transfer me-2"></i>Accounts Refund Disbursement Payout
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickPayForm" action="" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-3">
                    <!-- Client & Amount Summary Banner -->
                    <div class="p-3 rounded-2 bg-light border mb-3">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <span class="text-muted small d-block">Client & Flat:</span>
                                <strong class="text-dark fs-14" id="modalClientName">—</strong>
                                <div class="text-muted small" id="modalProjectFlat">—</div>
                                <div class="mt-1 fs-12 text-primary" id="modalBankExpectedNote" style="display:none;"></div>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <span class="text-muted small d-block">Approved Net Refund to Pay:</span>
                                <strong class="text-success fs-18 font-monospace" id="modalRefundAmount">৳ 0.00</strong>
                                <div class="text-danger small" id="modalRetentionNote">Retention: ৳ 0.00</div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Form matching Image 2 -->
                    <div class="row ">
                        <!-- Payment Method * -->
                        <div class="col-md-6">
                            <label class="form-label fs-12 fw-semibold text-dark">Payment Method <span class="text-danger">*</span></label>
                            <select name="payment_method" id="modalPaymentMethod" class="form-select form-select-sm" required onchange="toggleModalFields(this.value)">
                                <option value="bank" selected>Bank</option>
                                <option value="cheque">Cheque</option>
                                <option value="cash">Cash</option>
                                <option value="online">Online</option>
                            </select>
                        </div>

                        <!-- Transfer From / Company Bank * -->
                        <div class="col-md-6" id="modalCompanyBankCol">
                            <label class="form-label fs-12 fw-semibold text-dark">Transfer To / Company Bank <span class="text-danger">*</span></label>
                            <select name="company_bank_id" id="modalCompanyBankSelect" class="form-select form-select-sm">
                                <option value="">-- Select Transfer To / Company Bank --</option>
                                <?php foreach ($companyBanks as $cb): ?>
                                    <option value="<?= $cb['id'] ?>">
                                        <?= htmlspecialchars($cb['bank_name'] . (!empty($cb['account_name']) ? ' - ' . $cb['account_name'] : '') . (!empty($cb['account_number']) ? ' (' . $cb['account_number'] . ')' : '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Transfer To / Client Bank * -->
                        <div class="col-md-6" id="modalClientBankCol">
                            <label class="form-label fs-12 fw-semibold text-dark">Transfer From / Client Bank <span class="text-danger">*</span></label>
                            <select name="bank_name" id="modalClientBankSelect" class="form-select form-select-sm" style="width: 100%;">
                                <option value="">-- Select Client Bank --</option>
                                <option value="Other Country / Foreign Bank">Other Country / Foreign Bank</option>
                                <option value="Other Bank">Other Bank</option>
                                <?php foreach ($allBangladeshBanks as $bName): ?>
                                    <option value="<?= htmlspecialchars($bName) ?>"><?= htmlspecialchars($bName) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Client Bank A/C No. (Optional) -->
                        <div class="col-md-6" id="modalClientAcCol">
                            <label class="form-label fs-12 fw-semibold text-dark">Client Bank A/C No. <span class="text-muted">(Optional)</span></label>
                            <input type="text" name="bank_account_no" id="modalClientAcNo" class="form-control form-control-sm" placeholder="Account Number">
                        </div>

                        <!-- Cheque Number (When Cheque selected) -->
                        <div class="col-md-6" id="modalChequeCol" style="display:none;">
                            <label class="form-label fs-12 fw-semibold text-dark">Cheque Number <span class="text-danger">*</span></label>
                            <input type="text" name="cheque_no" id="modalChequeNo" class="form-control form-control-sm" placeholder="Enter cheque number">
                        </div>

                        <!-- Payment Date * -->
                        <div class="col-md-6">
                            <label class="form-label fs-12 fw-semibold text-dark">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" id="modalPaymentDate" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <!-- Transaction Reference -->
                        <div class="col-md-6" id="modalRefCol">
                            <label class="form-label fs-12 fw-semibold text-dark">Transaction Reference</label>
                            <input type="text" name="transaction_ref" id="modalTransactionRef" class="form-control form-control-sm" placeholder="Required reference number">
                        </div>

                        <!-- Attachment Image (Mandatory) -->
                        <div class="col-md-12">
                            <label class="form-label fs-12 fw-semibold text-dark">
                                <i class="fa-light fa-paperclip text-primary me-1"></i> Attachment Image / Voucher Proof <span class="text-danger">*</span>
                            </label>
                            <input type="file" name="disbursement_proof" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" required>
                            <small class="text-muted fs-11 d-block mt-1">
                                Mandatory: Upload scanned copy/photo of cheque, bank transfer slip, or signed voucher (Max 10MB).
                            </small>
                        </div>

                        <!-- Remarks / Notes -->
                        <div class="col-md-12">
                            <label class="form-label fs-12 fw-semibold text-dark">Remarks / Payment Notes</label>
                            <textarea name="remarks" class="form-control form-control-sm" rows="2" placeholder="Optional payout details or remarks..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm fw-bold px-3">
                        <i class="fa-light fa-check me-1"></i> Disburse & Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 6. AJAX FILTERING & PAGINATION JAVASCRIPT -->
<!-- ========================================================================= -->
<script>
let currentPage = <?= (int)$page ?>;
let totalPages  = <?= (int)max(1, $totalPages) ?>;
let currentFilterProjectId = '<?= htmlspecialchars($filters['project_id'] ?? 'all') ?>';
let userRole = <?= (int)$userRole ?>;
let currentUserId = <?= (int)$currentUserId ?>;
let baseUrl = '<?= $base_url ?>';

const statusMapJs = {
    'pending':  { label: 'Pending',  class: 'refund-badge refund-badge-pending',  icon: 'fa-clock' },
    'approved': { label: 'Approved', class: 'refund-badge refund-badge-approved', icon: 'fa-badge-check' },
    'refunded': { label: 'Paid',     class: 'refund-badge refund-badge-refunded', icon: 'fa-check-double' },
    'rejected': { label: 'Rejected', class: 'refund-badge refund-badge-rejected', icon: 'fa-circle-xmark' }
};

function toggleProjectStrip() {
    const strip = document.getElementById('projectTrackContainer');
    const btn   = document.getElementById('toggleProjectsBtn');
    if (!strip || !btn) return;
    const isExpanded = strip.classList.toggle('expanded');
    const count = strip.querySelectorAll('.project-track-pill').length - 1;
    btn.innerHTML = isExpanded
        ? `<i class="fa-light fa-chevrons-up me-1"></i> Collapse`
        : `<i class="fa-light fa-chevrons-down me-1"></i> Expand all (${count})`;
}

function formatMoney(amount) {
    let num = parseFloat(amount || 0);
    return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function escapeHtml(text) {
    if (!text) return '';
    return text.toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function filterByProject(projectId, pillEl) {
    currentFilterProjectId = projectId.toString();
    const projSelect = document.getElementById('filterProject');
    if (projSelect) {
        projSelect.value = currentFilterProjectId;
    }
    // Update pills active status
    document.querySelectorAll('.project-track-pill').forEach(el => el.classList.remove('active'));
    if (pillEl) {
        pillEl.classList.add('active');
    }
    applyFilters(1);
}

function filterByStatus(status) {
    const statusSelect = document.getElementById('filterStatus');
    if (!statusSelect) return;

    if (statusSelect.value === status) {
        statusSelect.value = 'all';
    } else {
        statusSelect.value = status;
    }

    updateCardActiveState(statusSelect.value);
    applyFilters(1);
}

function updateCardActiveState(activeStatus) {
    document.querySelectorAll('.refund-kpi-card').forEach(c => c.classList.remove('active-card'));
    if (!activeStatus || activeStatus === 'all') {
        const totalCard = document.getElementById('cardTotalRefund');
        if (totalCard) totalCard.classList.add('active-card');
    } else if (activeStatus === 'pending') {
        const pendingCard = document.getElementById('cardPendingRefund');
        if (pendingCard) pendingCard.classList.add('active-card');
    } else if (activeStatus === 'refunded') {
        const paidCard = document.getElementById('cardPaidRefund');
        if (paidCard) paidCard.classList.add('active-card');
    } else if (activeStatus === 'rejected') {
        const rejCard = document.getElementById('cardRejectedRefund');
        if (rejCard) rejCard.classList.add('active-card');
    }
}

function resetFilters() {
    document.getElementById('filterSearch').value = '';
    document.getElementById('filterProject').value = 'all';
    document.getElementById('filterStatus').value = 'all';
    document.getElementById('filterDateFrom').value = '';
    document.getElementById('filterDateTo').value = '';
    document.getElementById('filterPerPage').value = 'all';
    currentFilterProjectId = 'all';

    document.querySelectorAll('.project-track-pill').forEach(el => el.classList.remove('active'));
    const firstPill = document.querySelector('.project-track-pill');
    if (firstPill) firstPill.classList.add('active');

    updateCardActiveState('all');
    applyFilters(1);
}

function applyFilters(page = 1) {
    currentPage = page;
    const overlay = document.getElementById('tableLoadingOverlay');
    if (overlay) overlay.style.display = 'flex';

    const search    = document.getElementById('filterSearch').value.trim();
    const projectId = document.getElementById('filterProject').value;
    const status    = document.getElementById('filterStatus').value;
    const dateFrom  = document.getElementById('filterDateFrom').value;
    const dateTo    = document.getElementById('filterDateTo').value;
    const perPage   = document.getElementById('filterPerPage').value;

    const params = new URLSearchParams({
        ajax: '1',
        page: currentPage,
        per_page: perPage,
        search: search,
        project_id: projectId,
        status: status,
        date_from: dateFrom,
        date_to: dateTo
    });

    fetch(`${baseUrl}/client-refunds?${params.toString()}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (overlay) overlay.style.display = 'none';
        if (data.success) {
            renderTableRows(data.data, data.page, data.per_page);
            renderPagination(data.page, data.total_pages, data.total);
            updateSummaryKpis(data.summary_stats);
        } else {
            console.error('Failed to load refund data', data);
        }
    })
    .catch(err => {
        if (overlay) overlay.style.display = 'none';
        console.error('AJAX Error:', err);
    });
}

function formatDateTime(dtStr) {
    if (!dtStr) return '—';
    try {
        const d = new Date(dtStr.replace(/-/g, '/'));
        if (isNaN(d.getTime())) return dtStr;
        const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        const day = String(d.getDate()).padStart(2, '0');
        const month = months[d.getMonth()];
        const year = d.getFullYear();
        let hours = d.getHours();
        const minutes = String(d.getMinutes()).padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12;
        const strHours = String(hours).padStart(2, '0');
        return `${day} ${month} ${year}, ${strHours}:${minutes} ${ampm}`;
    } catch(e) {
        return dtStr;
    }
}

function renderTableRows(rows, page, perPage) {
    const tbody = document.getElementById('refundTableBody');
    if (!tbody) return;

    if (!rows || rows.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" class="text-center py-5">
                    <div class="text-muted">
                        <i class="fa-light fa-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                        <h6 class="fs-14 fw-semibold mb-1">No refund records found</h6>
                        <p class="fs-12 text-muted mb-0">Try changing your search keywords or clear the filters.</p>
                    </div>
                </td>
            </tr>`;
        return;
    }

    let html = '';
    const accountsRoles = [1, 3, 6, 10, 30, 36];

    const isAll = (perPage === 'all' || !perPage || isNaN(parseInt(perPage)));
    const perPageNum = isAll ? rows.length : parseInt(perPage);

    rows.forEach((r, idx) => {
        const rowNum = isAll ? (idx + 1) : ((page - 1) * perPageNum + idx + 1);
        const sInfo = statusMapJs[r.status] || { label: r.status, class: 'bg-secondary text-white', icon: 'fa-circle-info' };
        
        let clientPhoneHtml = r.client_phone ? `<div class="text-muted fs-11 mt-0.5"><i class="fa-light fa-phone me-1"></i>${escapeHtml(r.client_phone)}</div>` : '';
        let noteHtml = r.reason ? `<span class="client-reason-text mt-1" title="${escapeHtml(r.reason)}">${escapeHtml(r.reason)}</span>` : '';

        let floorHtml = r.floor_no ? `<span class="text-muted fs-11">(Floor ${escapeHtml(r.floor_no)})</span>` : '';
        let approverHtml = r.approved_by_name ? `
            <div class="fw-medium text-dark">${escapeHtml(r.approved_by_name)}</div>
            <span class="text-muted fs-11 d-block mt-0.5">${formatDateTime(r.approved_at)}</span>` : '<span class="text-muted">—</span>';

        let retentionBtnHtml = '';
        const retentionRoles = [1, 3, 6, 7, 9, 10, 20, 30, 33, 37];
        if (r.status === 'pending' && retentionRoles.includes(userRole)) {
            retentionBtnHtml = `
                <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2" 
                        onclick='openQuickRetentionModal(${JSON.stringify(r)})' 
                        title="Cut Retention Amount or %">
                    <i class="fa-light fa-scissors"></i>
                </button>`;
        }

        let payBtnHtml = '';
        if (r.status === 'approved' && accountsRoles.includes(userRole)) {
            payBtnHtml = `
                <button type="button" class="btn btn-xs btn-success py-1 px-2 text-white" 
                        onclick='openQuickPayModal(${JSON.stringify(r)})' 
                        title="Disburse Payment">
                    <i class="fa-light fa-money-bill-wave me-1"></i> Pay
                </button>`;
        }

        let deleteBtnHtml = '';
        if (r.status === 'pending' && r.requested_by == currentUserId) {
            deleteBtnHtml = `
                <form action="${baseUrl}/client-refund-delete/${r.id}" method="POST" class="d-inline" onsubmit="return confirm('Delete this refund request?');">
                    <button type="submit" class="btn btn-xs btn-outline-danger py-1 px-1.5" title="Delete">
                        <i class="fa-light fa-trash"></i>
                    </button>
                </form>`;
        }

        let receiptBtnHtml = (r.status === 'refunded' || r.status === 'approved') ? `
            <a href="${baseUrl}/client-refund-receipt/${r.id}" target="_blank"
               class="btn btn-xs btn-outline-success py-1 px-1.5"
               title="Print Refund Money Receipt">
                <i class="fa-light fa-receipt"></i>
            </a>` : '';

        let ledgerBtnHtml = r.booking_id ? `
            <a href="${baseUrl}/clients-cash-in-history/${r.booking_id}" target="_blank"
               class="btn btn-xs btn-outline-dark py-1 px-2"
               title="Open Client Ledger & Verify Pending Payments">
                <i class="fa-light fa-book-open me-1"></i> Ledger
            </a>` : '';

        html += `
            <tr id="refund-row-${r.id}">
                <td class="text-center text-muted font-monospace fs-12">${rowNum}</td>
                <td class="fs-12 text-nowrap">
                    <div class="fw-semibold text-dark">${escapeHtml(r.requested_by_name || '—')}</div>
                    <span class="text-muted fs-11 d-block mt-0.5">${formatDateTime(r.created_at)}</span>
                </td>
                <td class="client-name-cell">
                    <div class="fw-semibold text-dark fs-13">${escapeHtml(r.client_name || '—')}</div>
                    ${clientPhoneHtml}
                    ${noteHtml}
                </td>
                <td>
                    <span class="proj-tag">
                        ${escapeHtml(r.project_name || '—')}
                    </span>
                    <div class="fw-medium text-dark fs-12">
                        ${escapeHtml(r.flat_name || '—')} ${floorHtml}
                    </div>
                </td>
                <td class="text-end fw-semibold text-dark font-monospace amount-cell">৳ ${formatMoney(r.total_paid)}</td>
                <td class="text-end font-monospace amount-cell">
                    <span class="retention-highlight">৳ ${formatMoney(r.retention_amount)}</span>
                </td>
                <td class="text-end font-monospace amount-cell">
                    <span class="refund-highlight fs-13">৳ ${formatMoney(r.refund_amount)}</span>
                </td>
                <td class="text-center">
                    <span class="${sInfo.class}">
                        <i class="fa-solid ${sInfo.icon}"></i>${sInfo.label}
                    </span>
                </td>
                <td class="fs-12">${approverHtml}</td>
                <td class="text-center text-nowrap col-action d-print-none">
                    <div class="d-inline-flex align-items-center justify-content-center gap-1 text-nowrap">
                        <a href="${baseUrl}/client-refund/${r.id}" class="btn btn-xs btn-outline-primary py-1 px-2" title="View Details">
                            <i class="fa-light fa-eye"></i> View
                        </a>
                        ${receiptBtnHtml}
                        ${payBtnHtml}
                        ${retentionBtnHtml}
                        ${ledgerBtnHtml}
                        ${deleteBtnHtml}
                    </div>
                </td>
            </tr>`;
    });

    tbody.innerHTML = html;
}

function renderPagination(page, totalPgs, totalRecs) {
    currentPage = page;
    totalPages  = Math.max(1, totalPgs);

    const badge = document.getElementById('recordCountBadge');
    if (badge) badge.innerText = `${totalRecs}`;

    const curPageSpan = document.getElementById('curPageNum');
    if (curPageSpan) curPageSpan.innerText = page;

    const totPageSpan = document.getElementById('totalPageNum');
    if (totPageSpan) totPageSpan.innerText = totalPages;

    const perPageVal = document.getElementById('filterPerPage')?.value;
    const isAll = (perPageVal === 'all' || !perPageVal || isNaN(parseInt(perPageVal)));
    const perPage = isAll ? totalRecs : parseInt(perPageVal);
    const start = totalRecs === 0 ? 0 : (isAll ? 1 : (page - 1) * perPage + 1);
    const end   = isAll ? totalRecs : Math.min(page * perPage, totalRecs);

    const summaryText = document.getElementById('paginationSummaryText');
    if (summaryText) {
        if (isAll || totalPgs <= 1) {
            summaryText.innerText = `Showing all ${totalRecs} entries`;
        } else {
            summaryText.innerText = `Showing ${start} to ${end} of ${totalRecs} entries`;
        }
    }

    const container = document.getElementById('paginationControls');
    if (!container) return;

    if (isAll || totalPgs <= 1) {
        container.innerHTML = '';
        return;
    }

    let html = '';

    // First & Previous
    html += `
        <li class="page-item ${page <= 1 ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0);" onclick="applyFilters(1)" title="First"><i class="fa-solid fa-angles-left"></i></a>
        </li>
        <li class="page-item ${page <= 1 ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0);" onclick="applyFilters(${page - 1})" title="Previous"><i class="fa-solid fa-angle-left"></i></a>
        </li>`;

    let startPage = Math.max(1, page - 2);
    let endPage   = Math.min(totalPages, page + 2);

    for (let p = startPage; p <= endPage; p++) {
        html += `
            <li class="page-item ${p === page ? 'active' : ''}">
                <a class="page-link" href="javascript:void(0);" onclick="applyFilters(${p})">${p}</a>
            </li>`;
    }

    // Next & Last
    html += `
        <li class="page-item ${page >= totalPages ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0);" onclick="applyFilters(${page + 1})" title="Next"><i class="fa-solid fa-angle-right"></i></a>
        </li>
        <li class="page-item ${page >= totalPages ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0);" onclick="applyFilters(${totalPages})" title="Last"><i class="fa-solid fa-angles-right"></i></a>
        </li>`;

    container.innerHTML = html;
}

function updateSummaryKpis(stats) {
    if (!stats) return;
    if (document.getElementById('kpiTotalRefund')) {
        document.getElementById('kpiTotalRefund').innerText = '৳ ' + formatMoney(stats.total_refund_amount);
    }
    if (document.getElementById('kpiTotalRefundCount')) {
        document.getElementById('kpiTotalRefundCount').innerText = (stats.active_count || 0) + ' Cases';
    }
    if (document.getElementById('kpiPendingRefund')) {
        document.getElementById('kpiPendingRefund').innerText = '৳ ' + formatMoney(stats.pending_refund_amount || 0);
    }
    if (document.getElementById('kpiPendingCount')) {
        document.getElementById('kpiPendingCount').innerText = (stats.pending_count || 0) + ' Cases';
    }
    if (document.getElementById('kpiTotalRetention')) {
        document.getElementById('kpiTotalRetention').innerText = '৳ ' + formatMoney(stats.total_retention);
    }
    if (document.getElementById('kpiRetentionCount')) {
        document.getElementById('kpiRetentionCount').innerText = (stats.retention_count || 0) + ' Cases';
    }
    if (document.getElementById('kpiTotalDisbursed')) {
        document.getElementById('kpiTotalDisbursed').innerText = '৳ ' + formatMoney(stats.total_refunded_paid || 0);
    }
    if (document.getElementById('kpiPaidCount')) {
        document.getElementById('kpiPaidCount').innerText = (stats.refunded_count || 0) + ' Cases';
    }
    if (document.getElementById('kpiRejectedAmount')) {
        document.getElementById('kpiRejectedAmount').innerText = '৳ ' + formatMoney(stats.rejected_amount || 0);
    }
    if (document.getElementById('kpiRejectedCount')) {
        document.getElementById('kpiRejectedCount').innerText = (stats.rejected_count || 0) + ' Cases';
    }

    // Also update tfoot totals
    if (document.getElementById('tfootTotalPaid')) {
        document.getElementById('tfootTotalPaid').innerText = '৳ ' + formatMoney(stats.total_verified_paid);
    }
    if (document.getElementById('tfootTotalRetention')) {
        document.getElementById('tfootTotalRetention').innerText = '৳ ' + formatMoney(stats.total_retention);
    }
    if (document.getElementById('tfootTotalRefund')) {
        document.getElementById('tfootTotalRefund').innerText = '৳ ' + formatMoney(stats.total_refund_amount);
    }

    // Also update print header summary strip
    if (document.getElementById('printTotalRecords')) {
        document.getElementById('printTotalRecords').innerText = stats.total_requests || 0;
    }
    if (document.getElementById('printTotalPaid')) {
        document.getElementById('printTotalPaid').innerText = '৳ ' + formatMoney(stats.total_verified_paid);
    }
    if (document.getElementById('printTotalRetention')) {
        document.getElementById('printTotalRetention').innerText = '৳ ' + formatMoney(stats.total_retention);
    }
    if (document.getElementById('printTotalRefund')) {
        document.getElementById('printTotalRefund').innerText = '৳ ' + formatMoney(stats.total_refund_amount);
    }
    if (document.getElementById('printTotalDisbursed')) {
        document.getElementById('printTotalDisbursed').innerText = '৳ ' + formatMoney(stats.total_refunded_paid || 0);
    }
    updatePrintDateRange();
}

function formatPrintDate(isoDate) {
    if (!isoDate) return '';
    const parts = isoDate.split('-');
    if (parts.length !== 3) return isoDate;
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const mIdx = parseInt(parts[1], 10) - 1;
    return `${parts[2]} ${months[mIdx] || parts[1]} ${parts[0]}`;
}

function updatePrintDateRange() {
    const el = document.getElementById('printDateRange');
    if (!el) return;
    const fromVal = (document.getElementById('filterDateFrom')?.value || '').trim();
    const toVal   = (document.getElementById('filterDateTo')?.value || '').trim();

    if (fromVal && toVal) {
        el.innerText = `${formatPrintDate(fromVal)} to ${formatPrintDate(toVal)}`;
    } else if (fromVal) {
        el.innerText = `From ${formatPrintDate(fromVal)} to Present`;
    } else if (toVal) {
        el.innerText = `Up to ${formatPrintDate(toVal)}`;
    } else {
        // Compute earliest & latest date from currently visible table rows
        const dateCells = document.querySelectorAll('#refundTableBody tr td:nth-child(2) span');
        const parsed = [];
        dateCells.forEach(sp => {
            const txt = (sp.innerText || '').trim();
            const d = Date.parse(txt);
            if (!isNaN(d)) parsed.push(d);
        });
        if (parsed.length > 0) {
            const minD = new Date(Math.min(...parsed));
            const maxD = new Date(Math.max(...parsed));
            const fmt = dt => dt.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
            el.innerText = `All Dates (${fmt(minD)} to ${fmt(maxD)})`;
        } else {
            el.innerText = 'All Dates';
        }
    }
}

window.addEventListener('beforeprint', updatePrintDateRange);

// Quick Pay Modal Handler
function openQuickPayModal(refund) {
    const form = document.getElementById('quickPayForm');
    if (!form || !refund) return;

    form.action = `${baseUrl}/client-refund-disburse/${refund.id}?redirect=client-refunds`;

    document.getElementById('modalClientName').innerText = (refund.client_name || 'Client') + (refund.client_phone ? ' (' + refund.client_phone + ')' : '');
    document.getElementById('modalProjectFlat').innerText = (refund.project_name || 'Project') + ' — Flat: ' + (refund.flat_name || 'N/A') + (refund.floor_no ? ' (Floor ' + refund.floor_no + ')' : '');
    document.getElementById('modalRefundAmount').innerText = '৳ ' + formatMoney(refund.refund_amount);
    document.getElementById('modalRetentionNote').innerText = 'Retention: ৳ ' + formatMoney(refund.retention_amount) + ' | Total Paid: ৳ ' + formatMoney(refund.total_paid);

    // Payment method & client bank pre-filling from sales submission
    const preMethod = refund.preferred_payment_method || 'bank';
    document.getElementById('modalPaymentMethod').value = preMethod;
    toggleModalFields(preMethod);

    const clientBankSel = document.getElementById('modalClientBankSelect');
    if (clientBankSel && refund.client_bank_name) {
        clientBankSel.value = refund.client_bank_name;
    }
    const clientAcInput = document.getElementById('modalClientAcNo');
    if (clientAcInput && refund.client_bank_account_no) {
        clientAcInput.value = refund.client_bank_account_no;
    }

    const noteEl = document.getElementById('modalBankExpectedNote');
    if (noteEl) {
        let notes = [];
        if (refund.client_bank_name) notes.push('Client Bank: <strong>' + refund.client_bank_name + '</strong>' + (refund.client_bank_account_no ? ' (A/C: ' + refund.client_bank_account_no + ')' : ''));
        if (refund.expected_refund_date) notes.push('Expected: <strong>' + refund.expected_refund_date + '</strong>');
        if (notes.length > 0) {
            noteEl.innerHTML = '<i class="fa-solid fa-circle-info me-1"></i> ' + notes.join(' | ');
            noteEl.style.display = 'block';
        } else {
            noteEl.style.display = 'none';
        }
    }

    const modal = new bootstrap.Modal(document.getElementById('quickPayModal'));
    modal.show();
}

function toggleModalFields(method) {
    const coBankCol   = document.getElementById('modalCompanyBankCol');
    const clientBankCol = document.getElementById('modalClientBankCol');
    const clientAcCol   = document.getElementById('modalClientAcCol');
    const chequeCol     = document.getElementById('modalChequeCol');
    const refCol        = document.getElementById('modalRefCol');
    const coBankSel     = document.getElementById('modalCompanyBankSelect');
    const clientBankSel = document.getElementById('modalClientBankSelect');
    const chequeInput   = document.getElementById('modalChequeNo');

    if (method === 'bank') {
        if (coBankCol) coBankCol.style.display = 'block';
        if (clientBankCol) clientBankCol.style.display = 'block';
        if (clientAcCol) clientAcCol.style.display = 'block';
        if (chequeCol) chequeCol.style.display = 'none';
        if (refCol) refCol.style.display = 'block';
        if (coBankSel) coBankSel.required = true;
        if (clientBankSel) clientBankSel.required = true;
        if (chequeInput) chequeInput.required = false;
    } else if (method === 'cheque') {
        if (coBankCol) coBankCol.style.display = 'block';
        if (clientBankCol) clientBankCol.style.display = 'none';
        if (clientAcCol) clientAcCol.style.display = 'none';
        if (chequeCol) chequeCol.style.display = 'block';
        if (refCol) refCol.style.display = 'none';
        if (coBankSel) coBankSel.required = true;
        if (clientBankSel) clientBankSel.required = false;
        if (chequeInput) chequeInput.required = true;
    } else if (method === 'online') {
        if (coBankCol) coBankCol.style.display = 'block';
        if (clientBankCol) clientBankCol.style.display = 'block';
        if (clientAcCol) clientAcCol.style.display = 'block';
        if (chequeCol) chequeCol.style.display = 'none';
        if (refCol) refCol.style.display = 'block';
        if (coBankSel) coBankSel.required = true;
        if (clientBankSel) clientBankSel.required = false;
        if (chequeInput) chequeInput.required = false;
    } else { // cash
        if (coBankCol) coBankCol.style.display = 'none';
        if (clientBankCol) clientBankCol.style.display = 'none';
        if (clientAcCol) clientAcCol.style.display = 'none';
        if (chequeCol) chequeCol.style.display = 'none';
        if (refCol) refCol.style.display = 'none';
        if (coBankSel) coBankSel.required = false;
        if (clientBankSel) clientBankSel.required = false;
        if (chequeInput) chequeInput.required = false;
    }
}

// Initial pagination render on page load
document.addEventListener('DOMContentLoaded', function() {
    updateCardActiveState('<?= htmlspecialchars($filters['status'] ?? 'all') ?>');
    renderPagination(<?= (int)$page ?>, <?= (int)max(1, $totalPages) ?>, <?= (int)$totalRecords ?>);

    // Debounced search
    let searchTimeout;
    document.getElementById('filterSearch')?.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            applyFilters(1);
        }, 400);
    });

    document.getElementById('filterProject')?.addEventListener('change', function() {
        currentFilterProjectId = this.value;
        document.querySelectorAll('.project-track-pill').forEach(el => el.classList.remove('active'));
        applyFilters(1);
    });

    document.getElementById('filterStatus')?.addEventListener('change', function() {
        updateCardActiveState(this.value);
        applyFilters(1);
    });

    document.getElementById('filterDateFrom')?.addEventListener('change', function() {
        applyFilters(1);
    });

    document.getElementById('filterDateTo')?.addEventListener('change', function() {
        applyFilters(1);
    });

    // =========================================================================
    // QUICK RETENTION CUT-OFF MODAL HANDLERS
    // =========================================================================
    const qretPercent = document.getElementById('qretPercentInput');
    const qretAmount  = document.getElementById('qretAmountInput');
    const qretNet     = document.getElementById('qretNetRefundDisplay');

    if (qretPercent && qretAmount && qretNet) {
        qretPercent.addEventListener('input', function() {
            let p = parseFloat(this.value);
            if (isNaN(p) || p < 0) p = 0;
            if (p > 100) p = 100;
            let retAmt = (currentQretTotalPaid > 0) ? parseFloat(((currentQretTotalPaid * p) / 100).toFixed(2)) : 0;
            qretAmount.value = retAmt.toFixed(2);
            qretNet.value = Math.max(0, currentQretTotalPaid - retAmt).toFixed(2);
        });

        qretAmount.addEventListener('input', function() {
            let ret = parseFloat(this.value);
            if (isNaN(ret) || ret < 0) ret = 0;
            if (ret > currentQretTotalPaid) ret = currentQretTotalPaid;
            let p = (currentQretTotalPaid > 0) ? parseFloat(((ret / currentQretTotalPaid) * 100).toFixed(2)) : 0;
            qretPercent.value = p;
            qretNet.value = Math.max(0, currentQretTotalPaid - ret).toFixed(2);
        });

        document.querySelectorAll('.qret-pct-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                let p = parseFloat(this.dataset.pct);
                qretPercent.value = p;
                let retAmt = (currentQretTotalPaid > 0) ? parseFloat(((currentQretTotalPaid * p) / 100).toFixed(2)) : 0;
                qretAmount.value = retAmt.toFixed(2);
                qretNet.value = Math.max(0, currentQretTotalPaid - retAmt).toFixed(2);
            });
        });
    }

    const qretForm = document.getElementById('quickRetentionForm');
    if (qretForm) {
        qretForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('qretSubmitBtn');
            const errorAlert = document.getElementById('qretErrorAlert');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';
            }
            if (errorAlert) errorAlert.style.display = 'none';

            const formData = new FormData(qretForm);

            fetch(qretForm.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fa-light fa-check-double me-1"></i> Save Retention Cut-Off';
                }
                if (data.success) {
                    if (quickRetentionModalObj) quickRetentionModalObj.hide();
                    applyFilters(currentPage);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Retention Saved!',
                            text: data.message || 'Retention cut-off has been successfully saved.',
                            timer: 2500,
                            showConfirmButton: false
                        });
                    } else {
                        alert(data.message || 'Retention cut-off saved successfully!');
                    }
                } else {
                    if (errorAlert) {
                        errorAlert.innerText = data.message || 'Failed to save retention cut-off.';
                        errorAlert.style.display = 'block';
                    } else {
                        alert(data.message || 'Failed to save retention.');
                    }
                }
            })
            .catch(err => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fa-light fa-check-double me-1"></i> Save Retention Cut-Off';
                }
                if (errorAlert) {
                    errorAlert.innerText = 'Network error: ' + err.message;
                    errorAlert.style.display = 'block';
                }
            });
        });
    }
});

// Quick Retention Modal Opener
let currentQretTotalPaid = 0;
let quickRetentionModalObj = null;

function openQuickRetentionModal(refund) {
    if (!refund) return;
    const form = document.getElementById('quickRetentionForm');
    if (!form) return;

    form.action = `${baseUrl}/client-refund-adjust/${refund.id}?redirect=client-refunds`;

    document.getElementById('qretModalRefundId').innerText = '#' + refund.id;
    document.getElementById('qretClientName').innerText = (refund.client_name || 'Client') + (refund.client_phone ? ' (' + refund.client_phone + ')' : '');
    document.getElementById('qretProjectFlat').innerText = (refund.project_name || 'Project') + ' — ' + (refund.flat_name || 'N/A') + (refund.floor_no ? ' (Floor ' + refund.floor_no + ')' : '');

    currentQretTotalPaid = parseFloat(refund.total_paid || 0);
    document.getElementById('qretTotalPaidDisplay').innerText = '৳ ' + formatMoney(currentQretTotalPaid);

    let retAmt = parseFloat(refund.retention_amount || 0);
    let retPct = (currentQretTotalPaid > 0) ? parseFloat(((retAmt / currentQretTotalPaid) * 100).toFixed(2)) : 0;
    let netAmt = Math.max(0, currentQretTotalPaid - retAmt);

    document.getElementById('qretAmountInput').value = retAmt.toFixed(2);
    document.getElementById('qretAmountInput').max = currentQretTotalPaid;
    document.getElementById('qretPercentInput').value = retPct > 0 ? retPct : '';
    document.getElementById('qretNetRefundDisplay').value = netAmt.toFixed(2);
    
    const errAlert = document.getElementById('qretErrorAlert');
    if (errAlert) errAlert.style.display = 'none';

    if (!quickRetentionModalObj) {
        quickRetentionModalObj = new bootstrap.Modal(document.getElementById('quickRetentionModal'));
    }
    quickRetentionModalObj.show();
}
</script>
